<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProduction
{
    /**
     * @param  array<int, array{product_id: int, qty: int}>  $details
     */
    public function handle(
        User $creator,
        Location $warehouse,
        string $productionDate,
        ?string $notes,
        array $details,
    ): Production {
        return DB::transaction(function () use ($creator, $warehouse, $productionDate, $notes, $details): Production {
            $production = Production::query()->create([
                'production_no' => Production::generateProductionNo(),
                'production_date' => $productionDate,
                'notes' => $notes,
                'created_by' => $creator->id,
            ]);

            foreach ($details as $detail) {
                $productionDetail = $production->details()->create([
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                ]);

                $productionDetail->stockMovements()->create([
                    'movement_date' => now(),
                    'movement_type' => 'production',
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                    'from_location_id' => null,
                    'to_location_id' => $warehouse->id,
                    'reference_no' => $production->production_no,
                ]);

                $currentStock = CurrentStock::query()
                    ->where('product_id', $detail['product_id'])
                    ->where('location_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();

                if ($currentStock) {
                    $currentStock->increment('stock', $detail['qty']);
                } else {
                    CurrentStock::query()->create([
                        'product_id' => $detail['product_id'],
                        'location_id' => $warehouse->id,
                        'stock' => $detail['qty'],
                    ]);
                }
            }

            return $production;
        });
    }
}
