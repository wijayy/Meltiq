<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\InventorySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Meltiq',
            'email' => 'admin@meltiq.site',
        ]);
        User::factory()->create([
            'name' => 'User Meltiq',
            'email' => 'user@meltiq.site',
        ]);

        $this->call([
            CategorySeeder::class,
            LocationSeeder::class,
            SettingSeeder::class,
            // ProductSeeder::class
        ]);
    }
}
