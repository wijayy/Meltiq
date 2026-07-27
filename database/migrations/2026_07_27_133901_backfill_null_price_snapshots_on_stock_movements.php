<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('stock_movements')
            ->where(function ($query): void {
                $query->whereNull('unit_cost')
                    ->orWhereNull('unit_transfer_price')
                    ->orWhereNull('unit_sell_price');
            })
            ->select('id', 'product_id')
            ->orderBy('id')
            ->chunkById(500, function ($movements): void {
                $prices = DB::table('products')
                    ->whereIn('id', $movements->pluck('product_id')->unique())
                    ->get(['id', 'costPrice', 'transferPrice', 'salePrice'])
                    ->keyBy('id');

                foreach ($movements as $movement) {
                    $price = $prices->get($movement->product_id);

                    if ($price !== null) {
                        DB::table('stock_movements')->where('id', $movement->id)->update([
                            'unit_cost' => $price->costPrice,
                            'unit_transfer_price' => $price->transferPrice,
                            'unit_sell_price' => $price->salePrice,
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
