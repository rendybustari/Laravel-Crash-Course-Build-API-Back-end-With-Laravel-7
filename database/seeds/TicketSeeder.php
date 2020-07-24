<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tickets')->insert([
            'to' => 'Jakarta',
            'price' => 350000,
            'seat' => 10
        ]);

        DB::table('tickets')->insert([
            'to' => 'Semarang',
            'price' => 450000,
            'seat' => 1
        ]);

        DB::table('tickets')->insert([
            'to' => 'Yogyakarta',
            'price' => 350000,
            'seat' => 10
        ]);
    }
}
