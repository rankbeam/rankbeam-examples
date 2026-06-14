<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Rankbeam\Examples\ContractSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // One shared seeder across every stack in the matrix.
        $this->call(ContractSeeder::class);
    }
}
