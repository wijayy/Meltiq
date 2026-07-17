<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Support\Facades\DB;
use LogicException;

class SaveVisit
{
    /**
     * @param  array<int, array{product_id: int, stockBefore: int, physicalStock: int, returnedQty: int, expiredQty: int, newDeliveryQty: int}>  $details
     */
    public function handle(
        User $creator,
        Location $outlet,
        Location $warehouse,
        Location $expiredLocation,
        string $visitDate,
        ?string $notes,
        array $details,
        ?Visit $visit = null,
    ): Visit {
        return DB::transaction(function () use ($creator, $outlet, $warehouse, $expiredLocation, $visitDate, $notes, $details, $visit): Visit {
            if ($visit) {
                $visit = Visit::query()->with('details.stockMovements')->lockForUpdate()->findOrFail($visit->id);
                $this->reverseExistingVisit($visit);
                $visit->update([
                    'visit_date' => $visitDate,
                    'notes' => $notes,
                    'location_id' => $outlet->id,
                ]);
            } else {
                $visit = Visit::query()->create([
                    'visit_no' => Visit::generateVisitNo(),
                    'visit_date' => $visitDate,
                    'notes' => $notes,
                    'location_id' => $outlet->id,
                    'created_by' => $creator->id,
                    'status' => 'completed',
                ]);
            }

            $requiredProductIds = CurrentStock::query()
                ->where('location_id', $outlet->id)
                ->where('stock', '>', 0)
                ->pluck('product_id');
            $submittedProductIds = collect($details)->pluck('product_id');

            if ($submittedProductIds->count() !== $submittedProductIds->unique()->count()) {
                throw new LogicException('Setiap produk hanya boleh muncul satu kali dalam Visit.');
            }

            if ($requiredProductIds->diff($submittedProductIds)->isNotEmpty()) {
                throw new LogicException('Semua produk yang memiliki stock di outlet wajib diisi pada Visit.');
            }

            foreach ($details as $detailData) {
                $this->storeDetail($visit, $outlet, $warehouse, $expiredLocation, $detailData);
            }

            return $visit->refresh();
        });
    }

    private function reverseExistingVisit(Visit $visit): void
    {
        $movements = $visit->details->flatMap(fn (VisitDetail $detail) => $detail->stockMovements);

        foreach ($movements as $movement) {
            if ($this->isCaptured($movement)) {
                throw new LogicException('Visit tidak dapat diubah karena stoknya sudah masuk stock snapshot.');
            }

            if ($movement->to_location_id) {
                $this->changeStock($movement->product_id, $movement->to_location_id, -$movement->qty);
            }

            if ($movement->from_location_id) {
                $this->changeStock($movement->product_id, $movement->from_location_id, $movement->qty);
            }

            $movement->delete();
        }

        $visit->details()->delete();
    }

    /** @param array{product_id: int, stockBefore: int, physicalStock: int, returnedQty: int, expiredQty: int, newDeliveryQty: int} $data */
    private function storeDetail(Visit $visit, Location $outlet, Location $warehouse, Location $expiredLocation, array $data): void
    {
        $outletStock = $this->stock($data['product_id'], $outlet->id);
        $warehouseStock = $this->stock($data['product_id'], $warehouse->id);

        if ($outletStock->stock !== $data['stockBefore']) {
            throw new LogicException('Stock outlet berubah. Muat ulang data Visit sebelum menyimpan.');
        }

        if ($data['stockBefore'] === 0
            && ($data['physicalStock'] !== 0 || $data['returnedQty'] !== 0 || $data['expiredQty'] !== 0)) {
            throw new LogicException('Produk baru hanya dapat diisi pada new delivery qty.');
        }

        if ($data['physicalStock'] + $data['expiredQty'] > $data['stockBefore']) {
            throw new LogicException('Physical stock dan expired tidak boleh melebihi stock before.');
        }

        if ($data['returnedQty'] > $data['physicalStock']) {
            throw new LogicException('Returned qty tidak boleh melebihi physical stock.');
        }

        if ($data['newDeliveryQty'] > $warehouseStock->stock + $data['returnedQty']) {
            throw new LogicException('Stock warehouse tidak mencukupi untuk new delivery.');
        }

        $detail = $visit->details()->create($data);
        $detail->setRelation('visit', $visit);
        $soldQty = $data['stockBefore'] - $data['physicalStock'] - $data['expiredQty'];

        $this->movement($detail, 'sale', $soldQty, $outlet->id, null);
        $this->movement($detail, 'return', $data['returnedQty'], $outlet->id, $warehouse->id);
        $this->movement($detail, 'expired', $data['expiredQty'], $outlet->id, $expiredLocation->id);
        $this->movement($detail, 'transfer', $data['newDeliveryQty'], $warehouse->id, $outlet->id);

        $finalOutletStock = $data['physicalStock'] - $data['returnedQty'] + $data['newDeliveryQty'];
        $outletStock->update(['stock' => $finalOutletStock]);
        $warehouseStock->increment('stock', $data['returnedQty'] - $data['newDeliveryQty']);

        if ($data['expiredQty'] > 0) {
            $this->changeStock($data['product_id'], $expiredLocation->id, $data['expiredQty']);
        }
    }

    private function movement(VisitDetail $detail, string $type, int $qty, ?int $from, ?int $to): void
    {
        if ($qty === 0) {
            return;
        }

        $detail->stockMovements()->create([
            'movement_date' => now(),
            'movement_type' => $type,
            'product_id' => $detail->product_id,
            'qty' => $qty,
            'from_location_id' => $from,
            'to_location_id' => $to,
            'reference_no' => $detail->visit->visit_no,
        ]);
    }

    private function stock(int $productId, int $locationId): CurrentStock
    {
        $stock = CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        return $stock ?? CurrentStock::query()->create([
            'product_id' => $productId,
            'location_id' => $locationId,
            'stock' => 0,
        ]);
    }

    private function changeStock(int $productId, int $locationId, int $change): void
    {
        $stock = $this->stock($productId, $locationId);

        if ($stock->stock + $change < 0) {
            throw new LogicException('Visit tidak dapat diubah karena stock hasil transaksi sudah digunakan.');
        }

        $stock->increment('stock', $change);
    }

    private function isCaptured(StockMovement $movement): bool
    {
        return StockSnapshot::query()
            ->where('product_id', $movement->product_id)
            ->whereIn('location_id', array_filter([$movement->from_location_id, $movement->to_location_id]))
            ->where('snapshot_date', '>=', $movement->movement_date)
            ->exists();
    }
}
