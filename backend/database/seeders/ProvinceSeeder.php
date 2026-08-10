<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlFile = file_get_contents(storage_path('/sqlFiles/provinces.sql'));
        DB::unprepared($sqlFile);
    }
}
