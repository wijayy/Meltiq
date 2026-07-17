<?php

use App\Livewire\LocationCreate;
use App\Models\Location;
use Livewire\Livewire;

it('renders the location form successfully', function () {
    Livewire::test(LocationCreate::class)
        ->assertStatus(200);
});

it('creates a location', function () {
    Livewire::test(LocationCreate::class)
        ->set('name', 'Outlet Sanur')
        ->set('type', 'outlet')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('locations', [
        'name' => 'Outlet Sanur',
        'type' => 'outlet',
    ]);
});

it('updates a location', function () {
    $location = Location::factory()->create(['type' => 'outlet']);

    Livewire::test(LocationCreate::class)
        ->call('openEditModal', $location->id)
        ->set('name', 'Outlet Ubud')
        ->set('type', 'outlet')
        ->call('save')
        ->assertHasNoErrors();

    expect($location->fresh()->name)->toBe('Outlet Ubud');
});

it('does not change the type of the last required warehouse', function () {
    $warehouse = Location::factory()->create(['type' => 'warehouse']);

    Livewire::test(LocationCreate::class)
        ->call('openEditModal', $warehouse->id)
        ->set('type', 'outlet')
        ->call('save')
        ->assertHasErrors(['type']);

    expect($warehouse->fresh()->type)->toBe('warehouse');
});
