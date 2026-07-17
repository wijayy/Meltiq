<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetStockReport
{
    /**
     * @return Collection<int, array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int}>
     */
    public function handle(?CarbonInterface $at = null, ?int $productId = null, ?int $locationId = null): Collection
    {
        return $at === null
            ? $this->currentStocks($productId, $locationId)
            : $this->historicalStocks($at, $productId, $locationId);
    }

    /** @return Collection<int, array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int}> */
    private function currentStocks(?int $productId, ?int $locationId): Collection
    {
        return CurrentStock::query()
            ->select(['id', 'product_id', 'location_id', 'stock'])
            ->with(['product:id,name,sku', 'location:id,name,type'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->get()
            ->map(fn (CurrentStock $stock): array => $this->stockRow(
                $stock->product,
                $stock->location,
                $stock->stock,
            ))
            ->sortBy(fn (array $stock): string => $stock['location_name'].'|'.$stock['product_name'])
            ->values();
    }

    /** @return Collection<int, array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int}> */
    private function historicalStocks(CarbonInterface $at, ?int $productId, ?int $locationId): Collection
    {
        $snapshotDate = StockSnapshot::query()->where('snapshot_date', '<=', $at)->max('snapshot_date');
        $stocks = collect();

        if ($snapshotDate) {
            StockSnapshot::query()
                ->where('snapshot_date', $snapshotDate)
                ->when($productId, fn ($query) => $query->where('product_id', $productId))
                ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
                ->get(['product_id', 'location_id', 'stock'])
                ->each(function (StockSnapshot $snapshot) use ($stocks): void {
                    $stocks->put($this->stockKey($snapshot->product_id, $snapshot->location_id), [
                        'product_id' => $snapshot->product_id,
                        'location_id' => $snapshot->location_id,
                        'stock' => $snapshot->stock,
                    ]);
                });
        }

        $movementQuery = StockMovement::query()
            ->where('movement_date', '<=', $at)
            ->when($snapshotDate, fn ($query) => $query->where('movement_date', '>', $snapshotDate))
            ->when($productId, fn ($query) => $query->where('product_id', $productId));

        (clone $movementQuery)
            ->whereNotNull('to_location_id')
            ->when($locationId, fn ($query) => $query->where('to_location_id', $locationId))
            ->selectRaw('product_id, to_location_id as location_id, SUM(qty) as stock_change')
            ->groupBy('product_id', 'to_location_id')
            ->get()
            ->each(fn (StockMovement $movement) => $this->applyMovement(
                $stocks,
                $movement->product_id,
                (int) $movement->getAttribute('location_id'),
                (int) $movement->getAttribute('stock_change'),
            ));

        (clone $movementQuery)
            ->whereNotNull('from_location_id')
            ->when($locationId, fn ($query) => $query->where('from_location_id', $locationId))
            ->selectRaw('product_id, from_location_id as location_id, SUM(qty) as stock_change')
            ->groupBy('product_id', 'from_location_id')
            ->get()
            ->each(fn (StockMovement $movement) => $this->applyMovement(
                $stocks,
                $movement->product_id,
                (int) $movement->getAttribute('location_id'),
                -((int) $movement->getAttribute('stock_change')),
            ));

        $products = Product::query()->whereKey($stocks->pluck('product_id')->unique())->get(['id', 'name', 'sku'])->keyBy('id');
        $locations = Location::query()->whereKey($stocks->pluck('location_id')->unique())->get(['id', 'name', 'type'])->keyBy('id');

        return $stocks
            ->map(function (array $stock) use ($products, $locations): array {
                $product = $products->get($stock['product_id']);
                $location = $locations->get($stock['location_id']);

                return $this->stockRow($product, $location, $stock['stock']);
            })
            ->sortBy(fn (array $stock): string => $stock['location_name'].'|'.$stock['product_name'])
            ->values();
    }

    /** @param Collection<string, array{product_id: int, location_id: int, stock: int}> $stocks */
    private function applyMovement(Collection $stocks, int $productId, int $locationId, int $change): void
    {
        $key = $this->stockKey($productId, $locationId);
        $stock = $stocks->get($key, ['product_id' => $productId, 'location_id' => $locationId, 'stock' => 0]);
        $stock['stock'] += $change;
        $stocks->put($key, $stock);
    }

    private function stockKey(int $productId, int $locationId): string
    {
        return $productId.':'.$locationId;
    }

    /** @return array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int} */
    private function stockRow(Product $product, Location $location, int $stock): array
    {
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'location_type' => $location->type,
            'stock' => $stock,
        ];
    }
}
