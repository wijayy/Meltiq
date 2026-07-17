<?php

use App\Actions\SaveSystemSettings;
use App\Models\Location;
use App\Models\Setting;

it('saves default warehouse and expired location settings', function () {
    $warehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $expiredLocation = Location::factory()->create(['type' => 'virtual', 'isActive' => true]);

    app(SaveSystemSettings::class)->handle($warehouse, $expiredLocation);

    expect(Setting::query()->where('key', 'default_warehouse_location')->value('value'))->toBe((string) $warehouse->id)
        ->and(Setting::query()->where('key', 'default_expired_location')->value('value'))->toBe((string) $expiredLocation->id);
});

it('rejects locations with invalid types or inactive state', function () {
    $outlet = Location::factory()->create(['type' => 'outlet', 'isActive' => true]);
    $expiredLocation = Location::factory()->create(['type' => 'virtual', 'isActive' => true]);

    expect(fn () => app(SaveSystemSettings::class)->handle($outlet, $expiredLocation))
        ->toThrow(InvalidArgumentException::class, 'warehouse aktif');
});

it('protects configured locations from deactivation', function () {
    $warehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $expiredLocation = Location::factory()->create(['type' => 'virtual', 'isActive' => true]);
    app(SaveSystemSettings::class)->handle($warehouse, $expiredLocation);

    expect($warehouse->isSystemLocation())->toBeTrue()
        ->and($expiredLocation->isSystemLocation())->toBeTrue()
        ->and($warehouse->canDeactivate())->toBeFalse()
        ->and($expiredLocation->canDeactivate())->toBeFalse();
});

it('allows non-default locations to deactivate only when their required type remains available', function () {
    $defaultWarehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $otherWarehouse = Location::factory()->create(['type' => 'warehouse', 'isActive' => true]);
    $expiredLocation = Location::factory()->create(['type' => 'virtual', 'isActive' => true]);
    app(SaveSystemSettings::class)->handle($defaultWarehouse, $expiredLocation);

    expect($otherWarehouse->canDeactivate())->toBeTrue();
});
