<?php

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockSnapshot;

it('creates stock snapshots from current stock records', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
        'sku' => 'SKU-001',
        'slug' => 'test-product',
        'description' => 'Test description',
        'transferPrice' => 1000,
        'salePrice' => 1500,
        'costPrice' => 1200,
    ]);
    $location = Location::factory()->create([
        'name' => 'Main Location',
        'slug' => 'main-location',
    ]);

    CurrentStock::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'stock' => 25,
    ]);

    $this->artisan('stock:snapshot')->assertSuccessful();

    $this->assertDatabaseHas('stock_snapshots', [
        'product_id' => $product->id,
        'location_id' => $location->id,
        'stock' => 25,
    ]);

    expect(StockSnapshot::count())->toBe(1);
});
