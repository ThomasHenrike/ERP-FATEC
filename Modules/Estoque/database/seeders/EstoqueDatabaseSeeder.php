<?php

declare(strict_types=1);

namespace Modules\Estoque\Database\Seeders;

use Illuminate\Database\Seeder;

class EstoqueDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}