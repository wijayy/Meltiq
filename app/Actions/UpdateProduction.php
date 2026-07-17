<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use LogicException;

class UpdateProduction
{
    /**
     * @param  array<int, array{product_id: int, qty: int}>  $details
     */
    public function handle(
        Production $production,
        Location $warehouse,
        string $productionDate,
        ?string $notes,
        array $details,
    ): Production {
        return DB::transaction(function () use ($production, $warehouse, $productionDate, $notes, $details): Production {
            $production = Production::query()
                ->with('details.stockMovements')
                ->lockForUpdate()
                ->findOrFail($production->id);

            $movements = $production->details->flatMap(
                fn (ProductionDetail $detail) => $detail->stockMovements,
            );

            if ($this->hasCapturedMovement($movements)) {
                throw new LogicException('Production tidak dapat diubah karena stoknya sudah masuk stock snapshot.');
            }

            foreach ($movements as $movement) {
                $this->decreaseCurrentStock($movement);
                $movement->delete();
            }

            $production->details()->delete();
            $production->update([
                'production_date' => $productionDate,
                'notes' => $notes,
            ]);

            foreach ($details as $detail) {
                $productionDetail = $production->details()->create($detail);
                $productionDetail->stockMovements()->create([
                    'movement_date' => now(),
                    'movement_type' => 'production',
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                    'from_location_id' => null,
                    'to_location_id' => $warehouse->id,
                    'reference_no' => $production->production_no,
                ]);

                $this->increaseCurrentStock($detail['product_id'], $warehouse->id, $detail['qty']);
            }

            return $production->refresh();
        });
    }

    /** @param Collection<int, StockMovement> $movements */
    private function hasCapturedMovement(Collection $movements): bool
    {
        return $movements->contains(function (StockMovement $movement): bool {
            $locationIds = array_filter([$movement->from_location_id, $movement->to_location_id]);

            return StockSnapshot::query()
                ->where('product_id', $movement->product_id)
                ->whereIn('location_id', $locationIds)
                ->where('snapshot_date', '>=', $movement->movement_date)
                ->exists();
        });
    }

    private function decreaseCurrentStock(StockMovement $movement): void
    {
        if (! $movement->to_location_id) {
            return;
        }

        $stock = CurrentStock::query()
            ->where('product_id', $movement->product_id)
            ->where('location_id', $movement->to_location_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($stock->stock < $movement->qty) {
            throw new LogicException('Production tidak dapat diubah karena stok warehouse sudah digunakan.');
        }

        $stock->decrement('stock', $movement->qty);
    }

    private function increaseCurrentStock(int $productId, int $locationId, int $qty): void
    {
        $stock = CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('stock', $qty);

            return;
        }

        CurrentStock::query()->create([
            'product_id' => $productId,
            'location_id' => $locationId,
            'stock' => $qty,
        ]);
    }
}
