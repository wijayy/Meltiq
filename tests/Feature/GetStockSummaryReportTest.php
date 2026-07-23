<?php

use App\Actions\GetStockSummaryReport;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;

it('summarizes physical sold returned and expired stock per product and location', function () {
    $product = Product::factory()->create();
    $outlet = Location::factory()->create(['type' => 'outlet']);
    CurrentStock::factory()->for($product)->for($outlet)->create(['stock' => 8]);

    foreach (['sale' => 6, 'return' => 2, 'expired' => 1] as $type => $qty) {
        StockMovement::factory()->create([
            'movement_date' => now()->subHour(),
            'movement_type' => $type,
            'product_id' => $product->id,
            'from_location_id' => $outlet->id,
            'to_location_id' => null,
            'qty' => $qty,
        ]);
    }

    $row = app(GetStockSummaryReport::class)->handle(
        productId: $product->id,
        locationId: $outlet->id,
    )->sole();

    expect($row['physical'])->toBe(8)
        ->and($row['sales'])->toBe(6)
        ->and($row['returned'])->toBe(2)
        ->and($row['expired'])->toBe(1)
        ->and($row['total'])->toBe(17);
});
