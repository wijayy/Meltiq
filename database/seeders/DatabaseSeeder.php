<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'password' => Hash::make('EquinoxA5!'),
        ]);
        User::factory()->create([
            'name' => 'User Meltiq',
            'email' => 'user@meltiq.site',
            'password' => Hash::make('Meltiq289305'),

        ]);

        $this->call([
            MeltiqCatalogSeeder::class,
        ]);
    }
}
