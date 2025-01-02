<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManualTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manualTypes = [
            ['code' => 'MTE', 'type_name' => 'Manual de Técnicas del Ejército'],
            ['code' => 'MCE', 'type_name' => 'Manual de Campaña del Ejército'],
        ];

        DB::table('manual_types')->insert($manualTypes);
    }
}
