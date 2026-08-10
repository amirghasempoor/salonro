<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservationStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reservation_states')->insert([
            ['id' => 1, 'name' => 'reserve', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'cancel', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'done', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
