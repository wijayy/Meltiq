<?php

use App\Livewire\StockMovementIndex;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

it('filters movements and calculates net movement by product and location', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $warehouse = Location::factory()->create(['name' => 'Warehouse Utama']);
    $outlet = Location::factory()->create(['name' => 'Outlet Renon']);
    $expired = Location::factory()->create(['name' => 'Expired', 'type' => 'virtual']);
    Setting::query()->updateOrCreate(['key' => 'default_expired_location'], ['value' => (string) $expired->id]);

    StockMovement::factory()->create([
        'movement_date' => '2026-07-10 10:00:00',
        'movement_type' => 'transfer',
        'product_id' => $product->id,
        'from_location_id' => $warehouse->id,
        'to_location_id' => $outlet->id,
        'qty' => 10,
    ]);
    StockMovement::factory()->create([
        'movement_date' => '2026-07-10 11:00:00',
        'movement_type' => 'expired',
        'product_id' => $product->id,
        'from_location_id' => $outlet->id,
        'to_location_id' => $expired->id,
        'qty' => 3,
    ]);
    StockMovement::factory()->create([
        'movement_date' => '2026-06-10 10:00:00',
        'product_id' => $otherProduct->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(StockMovementIndex::class)
        ->set('periodBegin', '2026-07-01')
        ->set('periodEnd', '2026-07-31')
        ->set('productSlug', $product->slug)
        ->assertSee($product->name);

    expect($component->instance()->movements())->toHaveCount(2)
        ->and($component->instance()->movements()->first()->product_id)->toBe($product->id)
        ->and($component->get('summary'))
        ->toMatchArray([$product->id => [
            $warehouse->id => ['increase' => 0, 'decrease' => 10],
            $outlet->id => ['increase' => 10, 'decrease' => 3],
            $expired->id => ['increase' => 3, 'decrease' => 0],
        ]]);
});
