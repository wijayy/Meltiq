<?php

namespace App\Actions;

use App\Models\StockMovement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetStockSummaryReport
{
    /**
     * @return Collection<int, array{
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     location_id: int,
     *     location_name: string,
     *     location_type: string,
     *     physical: int,
     *     sales: int,
     *     returned: int,
     *     expired: int,
     *     total: int
     * }>
     */
    public function handle(?CarbonInterface $at = null, ?int $productId = null, ?int $locationId = null): Collection
    {
        $stocks = app(GetStockReport::class)->handle($at, $productId, $locationId);
        $movements = StockMovement::query()
            ->whereIn('movement_type', ['sale', 'return', 'expired'])
            ->whereNotNull('from_location_id')
            ->when($at, fn ($query) => $query->where('movement_date', '<=', $at))
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($locationId, fn ($query) => $query->where('from_location_id', $locationId))
            ->selectRaw('product_id, from_location_id as location_id, movement_type, SUM(qty) as total_qty')
            ->groupBy('product_id', 'from_location_id', 'movement_type')
            ->get()
            ->groupBy(fn (StockMovement $movement): string => $movement->product_id.':'.$movement->getAttribute('location_id'));

        return $stocks->map(function (array $stock) use ($movements): array {
            $movementByType = $movements
                ->get($stock['product_id'].':'.$stock['location_id'], collect())
                ->keyBy('movement_type');
            $physical = $stock['stock'];
            $sales = (int) ($movementByType->get('sale')?->getAttribute('total_qty') ?? 0);
            $returned = (int) ($movementByType->get('return')?->getAttribute('total_qty') ?? 0);
            $expired = (int) ($movementByType->get('expired')?->getAttribute('total_qty') ?? 0);

            return [
                'product_id' => $stock['product_id'],
                'product_name' => $stock['product_name'],
                'sku' => $stock['sku'],
                'location_id' => $stock['location_id'],
                'location_name' => $stock['location_name'],
                'location_type' => $stock['location_type'],
                'physical' => $physical,
                'sales' => $sales,
                'returned' => $returned,
                'expired' => $expired,
                'total' => $physical + $sales + $returned + $expired,
            ];
        });
    }
}
