<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::query()->where('type', 'warehouse')->firstOrFail();
        $expiredLocation = Location::query()->where('type', 'virtual')->firstOrFail();

        Setting::query()->updateOrCreate(
            ['key' => 'default_warehouse_location'],
            ['value' => (string) $warehouse->id, 'type' => 'number'],
        );
        Setting::query()->updateOrCreate(
            ['key' => 'default_expired_location'],
            ['value' => (string) $expiredLocation->id, 'type' => 'number'],
        );
    }
}
