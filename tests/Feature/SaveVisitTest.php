<?php

use App\Actions\SaveVisit;
use App\Livewire\VisitCreate;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use App\Models\User;
use App\Models\VisitDetail;

function visitLocations(): array
{
    return [
        Location::factory()->create(['type' => 'outlet']),
        Location::factory()->create(['type' => 'warehouse']),
        Location::factory()->create(['type' => 'virtual']),
    ];
}

it('records visit stock movements and updates every affected location', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();
    $user = User::factory()->create();
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $outlet->id, 'stock' => 20]);
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $warehouse->id, 'stock' => 50]);

    $visit = app(SaveVisit::class)->handle($user, $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id,
        'stockBefore' => 20,
        'physicalStock' => 12,
        'discountQty' => 3,
        'damagedQty' => 2,
        'newDeliveryQty' => 5,
    ]]);

    $detail = $visit->details()->with('stockMovements')->sole();
    $movements = $detail->stockMovements->keyBy('movement_type');

    expect($visit->status)->toBe('completed')
        ->and($detail->stockBefore)->toBe(20)
        ->and($movements)->toHaveCount(4)
        ->and($movements['sale']->qty)->toBe(3)
        ->and($movements['discount']->qty)->toBe(3)
        ->and($movements['discount']->unit_sell_price)->toBe(10000)
        ->and($movements['discount']->unit_transfer_price)->toBe(10000)
        ->and($movements['damaged']->qty)->toBe(2)
        ->and($movements['transfer']->qty)->toBe(5)
        ->and($movements->every(fn (StockMovement $movement): bool => $movement->reference_type === VisitDetail::class))->toBeTrue()
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($outlet)->value('stock'))->toBe(17)
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($warehouse)->value('stock'))->toBe(45)
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($damagedLocation)->value('stock'))->toBe(2);
});

it('reverses previous movements before updating a visit', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();
    $user = User::factory()->create();
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $outlet->id, 'stock' => 20]);
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $warehouse->id, 'stock' => 50]);
    $visit = app(SaveVisit::class)->handle($user, $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id, 'stockBefore' => 20, 'physicalStock' => 12, 'discountQty' => 3, 'damagedQty' => 2, 'newDeliveryQty' => 5,
    ]]);

    app(SaveVisit::class)->handle($user, $outlet, $warehouse, $damagedLocation, now()->toDateString(), 'Koreksi', [[
        'product_id' => $product->id, 'stockBefore' => 20, 'physicalStock' => 15, 'discountQty' => 2, 'damagedQty' => 1, 'newDeliveryQty' => 4,
    ]], $visit);

    expect($visit->refresh()->notes)->toBe('Koreksi')
        ->and(StockMovement::query()->where('reference_type', VisitDetail::class)->count())->toBe(4)
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($outlet)->value('stock'))->toBe(19)
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($warehouse)->value('stock'))->toBe(46)
        ->and(CurrentStock::query()->whereBelongsTo($product)->whereBelongsTo($damagedLocation)->value('stock'))->toBe(1);
});

it('rejects visit changes after a related snapshot', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();
    $user = User::factory()->create();
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $outlet->id, 'stock' => 10]);
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $warehouse->id, 'stock' => 10]);
    $visit = app(SaveVisit::class)->handle($user, $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id, 'stockBefore' => 10, 'physicalStock' => 8, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 0,
    ]]);
    $movement = $visit->details()->firstOrFail()->stockMovements()->firstOrFail();
    StockSnapshot::factory()->create(['snapshot_date' => $movement->movement_date->addMinute(), 'product_id' => $product->id, 'location_id' => $outlet->id]);

    expect(fn () => app(SaveVisit::class)->handle($user, $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id, 'stockBefore' => 10, 'physicalStock' => 9, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 0,
    ]], $visit))->toThrow(LogicException::class, 'sudah masuk rekaman stok');
});

it('rejects inconsistent physical and damaged quantities', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();
    CurrentStock::factory()->create(['product_id' => $product->id, 'location_id' => $outlet->id, 'stock' => 10]);

    expect(fn () => app(SaveVisit::class)->handle(User::factory()->create(), $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id, 'stockBefore' => 10, 'physicalStock' => 9, 'discountQty' => 0, 'damagedQty' => 2, 'newDeliveryQty' => 0,
    ]]))->toThrow(LogicException::class, 'tidak boleh melebihi');
});

it('requires every product currently stocked at the outlet', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $requiredProduct = Product::factory()->create();
    $submittedProduct = Product::factory()->create();
    CurrentStock::factory()->create(['product_id' => $requiredProduct->id, 'location_id' => $outlet->id, 'stock' => 5]);

    expect(fn () => app(SaveVisit::class)->handle(User::factory()->create(), $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $submittedProduct->id, 'stockBefore' => 0, 'physicalStock' => 0, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 1,
    ]]))->toThrow(LogicException::class, 'Semua produk');
});

it('allows only delivery quantities for products not previously stocked at the outlet', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();

    expect(fn () => app(SaveVisit::class)->handle(User::factory()->create(), $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [[
        'product_id' => $product->id, 'stockBefore' => 0, 'physicalStock' => 1, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 1,
    ]]))->toThrow(LogicException::class, 'Produk baru hanya');
});

it('loads all outlet products and their stock before into mandatory rows', function () {
    $outlet = Location::factory()->create(['type' => 'outlet']);
    $warehouse = Location::factory()->create(['type' => 'warehouse']);
    Setting::query()->create(['key' => 'default_warehouse_location', 'value' => (string) $warehouse->id, 'type' => 'number']);
    $products = Product::factory()->count(2)->create();
    CurrentStock::factory()->create(['product_id' => $products[0]->id, 'location_id' => $outlet->id, 'stock' => 7]);
    CurrentStock::factory()->create(['product_id' => $products[1]->id, 'location_id' => $outlet->id, 'stock' => 3]);
    CurrentStock::factory()->create(['product_id' => $products[0]->id, 'location_id' => $warehouse->id, 'stock' => 20]);
    CurrentStock::factory()->create(['product_id' => $products[1]->id, 'location_id' => $warehouse->id, 'stock' => 30]);
    $component = new VisitCreate;
    $component->locationId = (string) $outlet->id;

    $component->updatedLocationId();

    expect($component->details)->toHaveCount(2)
        ->and(collect($component->details)->pluck('stockBefore')->sort()->values()->all())->toBe([3, 7])
        ->and(collect($component->details)->pluck('warehouseStock')->sort()->values()->all())->toBe([20, 30])
        ->and(collect($component->details)->every(fn (array $detail): bool => $detail['isOutletStock']))->toBeTrue();

    $component->removeDetail(0);
    expect($component->details)->toHaveCount(2);
});

it('rejects duplicate products at the transaction boundary', function () {
    [$outlet, $warehouse, $damagedLocation] = visitLocations();
    $product = Product::factory()->create();

    expect(fn () => app(SaveVisit::class)->handle(User::factory()->create(), $outlet, $warehouse, $damagedLocation, now()->toDateString(), null, [
        ['product_id' => $product->id, 'stockBefore' => 0, 'physicalStock' => 0, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 1],
        ['product_id' => $product->id, 'stockBefore' => 0, 'physicalStock' => 0, 'discountQty' => 0, 'damagedQty' => 0, 'newDeliveryQty' => 2],
    ]))->toThrow(LogicException::class, 'satu kali');
});
