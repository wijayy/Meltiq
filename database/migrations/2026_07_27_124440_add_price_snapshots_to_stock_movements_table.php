<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_cost')->nullable()->after('qty');
            $table->unsignedBigInteger('unit_transfer_price')->nullable()->after('unit_cost');
            $table->unsignedBigInteger('unit_sell_price')->nullable()->after('unit_transfer_price');
        });

        DB::table('stock_movements')->select('id', 'product_id')->orderBy('id')
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
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'unit_transfer_price', 'unit_sell_price']);
        });
    }
};
