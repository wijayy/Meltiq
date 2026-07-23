<?php

use App\Models\Category;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use Database\Seeders\MeltiqCatalogSeeder;

it('seeds the meltiq catalog and warehouse stock idempotently', function () {
    $warehouse = Location::factory()->create([
        'type' => 'warehouse',
        'isActive' => true,
    ]);

    Setting::query()->create([
        'key' => 'default_warehouse_location',
        'value' => (string) $warehouse->id,
        'type' => 'number',
    ]);

    $this->seed(MeltiqCatalogSeeder::class);
    $this->seed(MeltiqCatalogSeeder::class);

    expect(Category::query()->whereIn('name', ['Bolu Kering', 'Cake Layer'])->count())->toBe(2)
        ->and(Product::query()->whereIn('sku', [
            'SKU-962',
            'SKU-106',
            'SKU-765',
            'SKU-590',
            'SKU-969',
            'SKU-804',
            'SKU-401',
            'SKU-198',
        ])->count())->toBe(8)
        ->and(Product::query()->where('sku', 'SKU-106')->value('salePrice'))->toBe(39000)
        ->and(Product::query()->where('sku', 'SKU-969')->value('salePrice'))->toBe(26000);

    $toples = Product::query()->where('sku', 'SKU-106')->firstOrFail();
    $redVelvet = Product::query()->where('sku', 'SKU-969')->firstOrFail();

    expect(CurrentStock::query()->whereBelongsTo($toples)->whereBelongsTo($warehouse)->value('stock'))->toBe(15)
        ->and(CurrentStock::query()->whereBelongsTo($redVelvet)->whereBelongsTo($warehouse)->value('stock'))->toBe(20)
        ->and(CurrentStock::query()->where('location_id', $warehouse->id)->count())->toBe(8);
});
