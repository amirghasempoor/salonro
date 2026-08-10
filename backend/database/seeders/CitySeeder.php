<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlFile = file_get_contents(storage_path('/sqlFiles/cities.sql'));
        DB::unprepared($sqlFile);
    }
}
