<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IndoRegionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndoRegionProvinceSeeder::class,
            IndoRegionRegencySeeder::class,
            IndoRegionDistrictSeeder::class,
            IndoRegionVillageSeeder::class,
        ]);
    }
}
