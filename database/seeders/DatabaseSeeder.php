<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data is for development only: it creates accounts with published
     * passwords. Production installations start with `php artisan users:create-admin`.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data must not be seeded in production. Use php artisan users:create-admin instead.');
        }

        $this->call(DemoDataSeeder::class);
    }
}
