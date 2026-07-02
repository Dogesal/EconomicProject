<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        // Sample data to work with during development. Production/mobile
        // installs never reach this: the device only runs migrations, so it
        // starts clean with just the catalog.
        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
