<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

/** Главный сидер базы данных. */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Заполнить базу данных начальными записями.
     *
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
    }
}
