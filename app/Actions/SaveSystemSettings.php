<?php

namespace App\Actions;

use App\Models\Location;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaveSystemSettings
{
    public function handle(Location $warehouse, Location $expiredLocation): void
    {
        if (! $warehouse->isActive || $warehouse->type !== 'warehouse') {
            throw new InvalidArgumentException('Default warehouse harus menggunakan warehouse aktif.');
        }

        if (! $expiredLocation->isActive || $expiredLocation->type !== 'virtual') {
            throw new InvalidArgumentException('Expired location harus menggunakan virtual location aktif.');
        }

        DB::transaction(function () use ($warehouse, $expiredLocation): void {
            Setting::query()->updateOrCreate(
                ['key' => 'default_warehouse_location'],
                ['value' => (string) $warehouse->id, 'type' => 'number'],
            );
            Setting::query()->updateOrCreate(
                ['key' => 'default_expired_location'],
                ['value' => (string) $expiredLocation->id, 'type' => 'number'],
            );
        });
    }
}
