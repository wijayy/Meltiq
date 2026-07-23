<?php

use App\Livewire\LocationIndex;
use App\Models\Location;
use Livewire\Livewire;

it('renders the location list successfully', function () {
    Livewire::test(LocationIndex::class)
        ->assertStatus(200);
});

it('does not deactivate a warehouse', function () {
    $warehouse = Location::factory()->create(['type' => 'warehouse']);

    Livewire::test(LocationIndex::class)
        ->set('locationId', $warehouse->id)
        ->call('deleteLocation')
        ->assertSee('Minimal satu lokasi untuk tipe ini harus tetap aktif.');

    expect($warehouse->fresh()->isActive)->toBeTrue();
});

it('does not deactivate the virtual expired location', function () {
    $expired = Location::factory()->create(['type' => 'virtual']);

    Livewire::test(LocationIndex::class)
        ->set('locationId', $expired->id)
        ->call('deleteLocation')
        ->assertSee('Minimal satu lokasi untuk tipe ini harus tetap aktif.');

    expect($expired->fresh()->isActive)->toBeTrue();
});

it('deactivates an outlet without deleting it', function () {
    $outlet = Location::factory()->create(['type' => 'outlet']);

    Livewire::test(LocationIndex::class)
        ->set('locationId', $outlet->id)
        ->call('deleteLocation')
        ->assertSee('Outlet berhasil dinonaktifkan.');

    expect($outlet->fresh())->not->toBeNull()
        ->and($outlet->fresh()->isActive)->toBeFalse();
});

it('does not include inactive locations in the list', function () {
    $active = Location::factory()->create(['isActive' => true]);
    $inactive = Location::factory()->create(['isActive' => false]);

    Livewire::test(LocationIndex::class)
        ->assertSee($active->name)
        ->assertDontSee($inactive->name);
});

it('filters nonactive locations', function () {
    $active = Location::factory()->create(['isActive' => true]);
    $inactive = Location::factory()->create(['isActive' => false]);

    Livewire::test(LocationIndex::class)
        ->set('status', 'nonactive')
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

it('shows active and nonactive locations when filtering all', function () {
    $active = Location::factory()->create(['isActive' => true]);
    $inactive = Location::factory()->create(['isActive' => false]);

    Livewire::test(LocationIndex::class)
        ->set('status', 'all')
        ->assertSee($active->name)
        ->assertSee($inactive->name);
});

it('restores an inactive location', function () {
    $location = Location::factory()->create(['isActive' => false]);

    Livewire::test(LocationIndex::class)
        ->call('restoreLocation', $location->id)
        ->assertSee('Lokasi berhasil dipulihkan.');

    expect($location->fresh()->isActive)->toBeTrue();
});
