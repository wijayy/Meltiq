<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $locations = [
            [
                'name' => 'Warehouse',
                'type' => 'warehouse',
            ],
            [
                'name' => 'Big M',
                'type' => 'outlet',
            ],
            [
                'name' => 'Bintang Supermarket',
                'type' => 'outlet',
            ],
            [
                'name' => 'Grand Lucky',
                'type' => 'outlet',
            ],
            [
                'name' => 'Rusak',
                'type' => 'virtual',
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
