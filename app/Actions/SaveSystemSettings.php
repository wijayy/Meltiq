<?php

namespace App\Actions;

use App\Models\Location;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaveSystemSettings
{
    public function handle(Location $warehouse, Location $damagedLocation): void
    {
        if (! $warehouse->isActive || $warehouse->type !== 'warehouse') {
            throw new InvalidArgumentException('Default warehouse harus menggunakan warehouse aktif.');
        }

        if (! $damagedLocation->isActive || $damagedLocation->type !== 'virtual') {
            throw new InvalidArgumentException('Lokasi rusak harus menggunakan lokasi virtual aktif.');
        }

        DB::transaction(function () use ($warehouse, $damagedLocation): void {
            Setting::query()->updateOrCreate(
                ['key' => 'default_warehouse_location'],
                ['value' => (string) $warehouse->id, 'type' => 'number'],
            );
            Setting::query()->updateOrCreate(
                ['key' => 'default_damaged_location'],
                ['value' => (string) $damagedLocation->id, 'type' => 'number'],
            );
        });
    }
}
