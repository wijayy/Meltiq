<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetFinancialReport
{
    /** @return array<string, mixed> */
    public function handle(string $dateFrom, string $dateTo, ?int $productId = null, ?int $locationId = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();
        $movements = StockMovement::query()
            ->with(['product:id,name,sku', 'fromLocation:id,name', 'toLocation:id,name'])
            ->whereBetween('movement_date', [
                $from,
                $to,
            ])
            ->when($productId, fn (Builder $query, int $id) => $query->where('product_id', $id))
            ->when($locationId, fn (Builder $query, int $id) => $query->where(
                fn (Builder $query) => $query->where('from_location_id', $id)->orWhere('to_location_id', $id),
            ))
            ->get();

        $sales = $movements->whereIn('movement_type', ['sale', 'discount'])
            ->when($locationId, fn (Collection $rows) => $rows->where('from_location_id', $locationId))
            ->groupBy('product_id')
            ->map(function (Collection $rows): array {
                /** @var StockMovement $first */
                $first = $rows->first();
                $revenue = (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_sell_price);
                $costOfGoodsSold = (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_cost);
                $transferValue = (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_transfer_price);

                return [
                    'product' => $first->product->name,
                    'sku' => $first->product->sku,
                    'quantity' => (int) $rows->sum('qty'),
                    'revenue' => $revenue,
                    'cost_of_goods_sold' => $costOfGoodsSold,
                    'gross_profit' => $revenue - $costOfGoodsSold,
                    'outlet_margin' => $revenue - $transferValue,
                ];
            })->sortByDesc('revenue')->values();

        $productions = $movements->where('movement_type', 'production')
            ->when($locationId, fn (Collection $rows) => $rows->where('to_location_id', $locationId))
            ->groupBy('product_id')
            ->map(function (Collection $rows): array {
                /** @var StockMovement $first */
                $first = $rows->first();

                return [
                    'product' => $first->product->name,
                    'sku' => $first->product->sku,
                    'quantity' => (int) $rows->sum('qty'),
                    'cost_value' => (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_cost),
                ];
            })->sortByDesc('cost_value')->values();

        $transfers = $movements->where('movement_type', 'transfer')
            ->groupBy(fn (StockMovement $movement): string => $movement->product_id.':'.$movement->to_location_id)
            ->map(function (Collection $rows): array {
                /** @var StockMovement $first */
                $first = $rows->first();
                $costValue = (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_cost);
                $transferValue = (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_transfer_price);

                return [
                    'product' => $first->product->name,
                    'sku' => $first->product->sku,
                    'destination' => $first->toLocation->name,
                    'quantity' => (int) $rows->sum('qty'),
                    'cost_value' => $costValue,
                    'transfer_value' => $transferValue,
                    'internal_margin' => $transferValue - $costValue,
                ];
            })->sortByDesc('transfer_value')->values();

        $losses = $movements->whereIn('movement_type', ['expired', 'damaged'])
            ->when($locationId, fn (Collection $rows) => $rows->where('from_location_id', $locationId))
            ->groupBy('product_id')
            ->map(function (Collection $rows): array {
                /** @var StockMovement $first */
                $first = $rows->first();

                return [
                    'product' => $first->product->name,
                    'sku' => $first->product->sku,
                    'quantity' => (int) $rows->sum('qty'),
                    'value' => (int) $rows->sum(fn (StockMovement $movement): int => $movement->qty * $movement->unit_cost),
                ];
            })->sortByDesc('value')->values();

        $inventory = CurrentStock::query()
            ->with(['product:id,name,sku,costPrice,transferPrice', 'location:id,name,type'])
            ->when($productId, fn (Builder $query, int $id) => $query->where('product_id', $id))
            ->when($locationId, fn (Builder $query, int $id) => $query->where('location_id', $id))
            ->where('stock', '>', 0)
            ->get()
            ->map(fn (CurrentStock $stock): array => [
                'product' => $stock->product->name,
                'sku' => $stock->product->sku,
                'location' => $stock->location->name,
                'location_type' => $stock->location->type,
                'quantity' => $stock->stock,
                'cost_value' => $stock->stock * $stock->product->costPrice,
                'transfer_value' => $stock->stock * $stock->product->transferPrice,
            ])->sortByDesc('cost_value')->values();

        $revenue = (int) $sales->sum('revenue');
        $costOfGoodsSold = (int) $sales->sum('cost_of_goods_sold');
        $lossValue = (int) $losses->sum('value');
        $productionValue = (int) $productions->sum('cost_value');
        $transferValue = (int) $transfers->sum('transfer_value');
        $openingStocks = app(GetStockReport::class)->handle($from->copy()->subSecond(), $productId, $locationId)
            ->keyBy(fn (array $stock): string => $stock['product_id'].':'.$stock['location_id']);
        $closingStocks = app(GetStockReport::class)->handle($to, $productId, $locationId)
            ->keyBy(fn (array $stock): string => $stock['product_id'].':'.$stock['location_id']);
        $stockKeys = $openingStocks->keys()->merge($closingStocks->keys())->unique();

        $reconciliation = $stockKeys->map(function (string $key) use ($openingStocks, $closingStocks, $movements): array {
            $opening = $openingStocks->get($key);
            $closing = $closingStocks->get($key);
            $stock = $closing ?? $opening;
            $productId = (int) $stock['product_id'];
            $locationId = (int) $stock['location_id'];
            $incoming = (int) $movements
                ->where('product_id', $productId)
                ->where('to_location_id', $locationId)
                ->sum('qty');
            $outgoing = (int) $movements
                ->where('product_id', $productId)
                ->where('from_location_id', $locationId)
                ->sum('qty');
            $openingQuantity = (int) ($opening['stock'] ?? 0);
            $closingQuantity = (int) ($closing['stock'] ?? 0);

            return [
                'product' => $stock['product_name'],
                'sku' => $stock['sku'],
                'location' => $stock['location_name'],
                'opening' => $openingQuantity,
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'expected_closing' => $openingQuantity + $incoming - $outgoing,
                'closing' => $closingQuantity,
                'variance' => $closingQuantity - ($openingQuantity + $incoming - $outgoing),
            ];
        })->sortBy(fn (array $row): string => $row['location'].'|'.$row['product'])->values();

        return [
            'summary' => [
                'revenue' => $revenue,
                'cost_of_goods_sold' => $costOfGoodsSold,
                'gross_profit' => $revenue - $costOfGoodsSold,
                'gross_margin' => $revenue > 0 ? (($revenue - $costOfGoodsSold) / $revenue) * 100 : 0,
                'loss_value' => $lossValue,
                'net_contribution' => $revenue - $costOfGoodsSold - $lossValue,
                'inventory_cost_value' => (int) $inventory->sum('cost_value'),
                'production_value' => $productionValue,
                'transfer_value' => $transferValue,
            ],
            'sales' => $sales,
            'productions' => $productions,
            'transfers' => $transfers,
            'losses' => $losses,
            'inventory' => $inventory,
            'reconciliation' => $reconciliation,
        ];
    }
}
