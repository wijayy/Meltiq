<?php

use App\Actions\CreateProduction;
use App\Actions\UpdateProduction;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductionDetail;
use App\Models\StockSnapshot;
use App\Models\User;

it('updates production details and recalculates current stock before snapshot', function () {
    $user = User::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    [$firstProduct, $secondProduct] = Product::factory()->count(2)->create(['isActive' => true]);

    $production = app(CreateProduction::class)->handle(
        $user,
        $warehouse,
        now()->toDateString(),
        null,
        [['product_id' => $firstProduct->id, 'qty' => 10]],
    );

    app(UpdateProduction::class)->handle(
        $production,
        $warehouse,
        now()->toDateString(),
        'Dikoreksi',
        [
            ['product_id' => $firstProduct->id, 'qty' => 4],
            ['product_id' => $secondProduct->id, 'qty' => 7],
        ],
    );

    $production->refresh()->load('details.stockMovements');
    $movements = $production->details->flatMap->stockMovements;

    expect($production->notes)->toBe('Dikoreksi')
        ->and($production->details)->toHaveCount(2)
        ->and($movements)->toHaveCount(2)
        ->and($movements->every(fn ($movement): bool => $movement->reference_type === ProductionDetail::class))->toBeTrue()
        ->and(CurrentStock::query()->whereBelongsTo($firstProduct)->whereBelongsTo($warehouse)->value('stock'))->toBe(4)
        ->and(CurrentStock::query()->whereBelongsTo($secondProduct)->whereBelongsTo($warehouse)->value('stock'))->toBe(7);
});

it('rejects production updates after its stock has been captured', function () {
    $user = User::factory()->create();
    $warehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $product = Product::factory()->create(['isActive' => true]);
    $production = app(CreateProduction::class)->handle(
        $user,
        $warehouse,
        now()->toDateString(),
        null,
        [['product_id' => $product->id, 'qty' => 10]],
    );
    $movement = $production->details()->firstOrFail()->stockMovements()->firstOrFail();

    StockSnapshot::factory()->create([
        'snapshot_date' => $movement->movement_date->addMinute(),
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'stock' => 10,
    ]);

    expect(fn () => app(UpdateProduction::class)->handle(
        $production,
        $warehouse,
        now()->toDateString(),
        null,
        [['product_id' => $product->id, 'qty' => 5]],
    ))->toThrow(\LogicException::class, 'sudah masuk stock snapshot');

    expect(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($warehouse)->value('stock'))->toBe(10)
        ->and($production->refresh()->details()->value('qty'))->toBe(10);
});
