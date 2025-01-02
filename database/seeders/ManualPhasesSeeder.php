<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class ManualPhasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phases = [
            ['code' => 1, 'phase_name' => 'Investigación'],
            ['code' => 2, 'phase_name' => 'Experimentación'],
            ['code' => 3, 'phase_name' => 'Edición'],
            ['code' => 4, 'phase_name' => 'Nota de Aula'],
        ];

        DB::table('manual_phases')->insert($phases);
    }
}
