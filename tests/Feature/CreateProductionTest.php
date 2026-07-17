<?php

use App\Actions\CreateProduction;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\StockMovement;
use App\Models\User;

it('creates an auditable production and updates warehouse stock', function () {
    $user = User::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $products = Product::factory()->count(2)->create(['isActive' => true]);

    CurrentStock::factory()->create([
        'product_id' => $products[0]->id,
        'location_id' => $warehouse->id,
        'stock' => 4,
    ]);

    $production = app(CreateProduction::class)->handle(
        creator: $user,
        warehouse: $warehouse,
        productionDate: now()->toDateString(),
        notes: 'Produksi pagi',
        details: [
            ['product_id' => $products[0]->id, 'qty' => 6],
            ['product_id' => $products[1]->id, 'qty' => 8],
        ],
    );

    $production->load('details.stockMovements');
    $movements = $production->details->flatMap->stockMovements;

    expect($production)->toBeInstanceOf(Production::class)
        ->and($production->details)->toHaveCount(2)
        ->and($movements)->toHaveCount(2)
        ->and($production->created_by)->toBe($user->id)
        ->and($movements->every(
            fn (StockMovement $movement): bool => $movement->movement_type === 'production'
                && $movement->from_location_id === null
                && $movement->to_location_id === $warehouse->id
                && $movement->reference_type === ProductionDetail::class
        ))->toBeTrue()
        ->and(CurrentStock::query()->where('product_id', $products[0]->id)->where('location_id', $warehouse->id)->value('stock'))->toBe(10)
        ->and(CurrentStock::query()->where('product_id', $products[1]->id)->where('location_id', $warehouse->id)->value('stock'))->toBe(8);
});
