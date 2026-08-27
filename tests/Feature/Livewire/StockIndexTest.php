<?php

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('stock-index')
        ->assertStatus(200);
});

it('filters stock by location type', function () {
    $product = Product::factory()->create();
    $warehouse = Location::factory()->create(['name' => 'Gudang Filter', 'type' => 'warehouse']);
    $outlet = Location::factory()->create(['name' => 'Outlet Filter', 'type' => 'outlet']);

    CurrentStock::factory()->for($product)->for($warehouse)->create(['stock' => 10]);
    CurrentStock::factory()->for($product)->for($outlet)->create(['stock' => 5]);

    $component = Livewire::test('stock-index')
        ->set('locationType', 'warehouse');

    expect($component->instance()->stocks()->pluck('location_name')->all())
        ->toBe(['Gudang Filter']);
});

it('resets an invalid location type filter', function () {
    Livewire::test('stock-index')
        ->set('locationType', 'invalid')
        ->assertSet('locationType', '');
});
