<?php

use App\Livewire\ProductionCreate;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

function createDefaultWarehouse(): Location
{
    $warehouse = Location::factory()->create([
        'name' => 'Main Warehouse',
        'type' => 'warehouse',
        'isActive' => true,
    ]);

    Setting::query()->forceCreate([
        'key' => 'default_warehouse_location',
        'value' => (string) $warehouse->id,
        'type' => 'number',
    ]);

    return $warehouse;
}

it('renders successfully with the configured warehouse', function () {
    $warehouse = createDefaultWarehouse();

    Livewire::test(ProductionCreate::class)
        ->assertSuccessful()
        ->assertSee($warehouse->name)
        ->assertSet('productionDate', now()->toDateString())
        ->assertCount('details', 1);
});

it('adds and removes product rows but keeps at least one row', function () {
    createDefaultWarehouse();

    Livewire::test(ProductionCreate::class)
        ->call('addDetail')
        ->assertCount('details', 2)
        ->call('removeDetail', 1)
        ->assertCount('details', 1)
        ->call('removeDetail', 0)
        ->assertCount('details', 1);
});

it('stores production details movements and current warehouse stock atomically', function () {
    $warehouse = createDefaultWarehouse();
    $user = User::factory()->create();
    $products = Product::factory()->count(2)->create(['isActive' => true]);

    CurrentStock::factory()->create([
        'product_id' => $products[0]->id,
        'location_id' => $warehouse->id,
        'stock' => 5,
    ]);

    Livewire::actingAs($user)
        ->test(ProductionCreate::class)
        ->set('productionDate', now()->toDateString())
        ->set('notes', 'Produksi harian')
        ->set('details', [
            ['product_id' => $products[0]->id, 'qty' => 10],
            ['product_id' => $products[1]->id, 'qty' => 7],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('productions.index'));

    $production = Production::query()->with('details.stockMovements')->sole();
    $movements = $production->details->flatMap->stockMovements;

    expect($production->details)->toHaveCount(2)
        ->and($movements)->toHaveCount(2)
        ->and($production->created_by)->toBe($user->id)
        ->and($production->notes)->toBe('Produksi harian')
        ->and($movements->pluck('product_id')->sort()->values()->all())
        ->toBe($products->pluck('id')->sort()->values()->all())
        ->and($movements->every(
            fn (StockMovement $movement): bool => $movement->movement_type === 'production'
                && $movement->from_location_id === null
                && $movement->to_location_id === $warehouse->id
                && $movement->reference_type === ProductionDetail::class
        ))->toBeTrue();

    expect(CurrentStock::query()->whereBelongsTo($products[0])->whereBelongsTo($warehouse)->value('stock'))->toBe(15)
        ->and(CurrentStock::query()->whereBelongsTo($products[1])->whereBelongsTo($warehouse)->value('stock'))->toBe(7);
});

it('rejects duplicate products in one production', function () {
    createDefaultWarehouse();
    $product = Product::factory()->create(['isActive' => true]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProductionCreate::class)
        ->set('details', [
            ['product_id' => $product->id, 'qty' => 5],
            ['product_id' => $product->id, 'qty' => 3],
        ])
        ->call('save')
        ->assertHasErrors(['details.0.product_id', 'details.1.product_id']);
});

it('rejects inactive products', function () {
    createDefaultWarehouse();
    $product = Product::factory()->create(['isActive' => false]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProductionCreate::class)
        ->set('details', [['product_id' => $product->id, 'qty' => 5]])
        ->call('save')
        ->assertHasErrors(['details.0.product_id' => 'exists']);
});

it('rejects a future production date', function () {
    createDefaultWarehouse();
    $product = Product::factory()->create(['isActive' => true]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProductionCreate::class)
        ->set('productionDate', now()->addDay()->toDateString())
        ->set('details', [['product_id' => $product->id, 'qty' => 5]])
        ->call('save')
        ->assertHasErrors(['productionDate' => 'before_or_equal']);
});

it('rejects quantities below one', function () {
    createDefaultWarehouse();
    $product = Product::factory()->create(['isActive' => true]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProductionCreate::class)
        ->set('details', [['product_id' => $product->id, 'qty' => 0]])
        ->call('save')
        ->assertHasErrors(['details.0.qty' => 'min']);
});

it('requires a configured active warehouse', function () {
    Livewire::test(ProductionCreate::class)
        ->assertSuccessful()
        ->assertSee('Default warehouse belum dikonfigurasi atau tidak aktif.')
        ->assertSee('Belum dikonfigurasi');
});
