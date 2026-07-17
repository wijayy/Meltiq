<?php

use App\Actions\GetStockReport;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockSnapshot;

it('shows current stocks and applies product and location filters', function () {
    $firstProduct = Product::factory()->create();
    $secondProduct = Product::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse']);
    $outlet = Location::factory()->create(['type' => 'outlet']);

    CurrentStock::factory()->create(['product_id' => $firstProduct->id, 'location_id' => $warehouse->id, 'stock' => 12]);
    CurrentStock::factory()->create(['product_id' => $secondProduct->id, 'location_id' => $outlet->id, 'stock' => 7]);

    $stocks = app(GetStockReport::class)->handle(productId: $firstProduct->id, locationId: $warehouse->id);

    expect($stocks)->toHaveCount(1)
        ->and($stocks->first()['product_id'])->toBe($firstProduct->id)
        ->and($stocks->first()['location_id'])->toBe($warehouse->id)
        ->and($stocks->first()['stock'])->toBe(12);
});

it('calculates stock at a datetime from the latest snapshot and following movements', function () {
    $product = Product::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse']);
    $outlet = Location::factory()->create(['type' => 'outlet']);
    $snapshotAt = now()->subDay()->startOfDay();
    $reportAt = $snapshotAt->copy()->addHours(12);

    StockSnapshot::factory()->create([
        'snapshot_date' => $snapshotAt,
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'stock' => 20,
    ]);
    StockSnapshot::factory()->create([
        'snapshot_date' => $snapshotAt,
        'product_id' => $product->id,
        'location_id' => $outlet->id,
        'stock' => 5,
    ]);

    StockMovement::factory()->create([
        'movement_date' => $snapshotAt->copy()->addHours(2),
        'product_id' => $product->id,
        'qty' => 4,
        'from_location_id' => $warehouse->id,
        'to_location_id' => $outlet->id,
    ]);
    StockMovement::factory()->create([
        'movement_date' => $reportAt->copy()->addHour(),
        'product_id' => $product->id,
        'qty' => 10,
        'from_location_id' => null,
        'to_location_id' => $warehouse->id,
    ]);

    $stocks = app(GetStockReport::class)->handle(at: $reportAt)->keyBy('location_id');

    expect($stocks[$warehouse->id]['stock'])->toBe(16)
        ->and($stocks[$outlet->id]['stock'])->toBe(9);
});

it('calculates historical stock from movements when no earlier snapshot exists', function () {
    $product = Product::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse']);
    $reportAt = now();

    StockMovement::factory()->create([
        'movement_date' => $reportAt->copy()->subHour(),
        'product_id' => $product->id,
        'qty' => 8,
        'from_location_id' => null,
        'to_location_id' => $warehouse->id,
    ]);

    $stocks = app(GetStockReport::class)->handle(at: $reportAt);

    expect($stocks)->toHaveCount(1)
        ->and($stocks->first()['stock'])->toBe(8);
});
