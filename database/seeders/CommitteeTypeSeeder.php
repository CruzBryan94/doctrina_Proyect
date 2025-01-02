<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommitteeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $committeeTypes = [
            ['code' => 1, 'name' => 'Comité de Investigación'],
            ['code' => 2, 'name' => 'Comité de Validación en Fase de Investigación'],
            ['code' => 3, 'name' => 'Comité de Experimentación'],
            ['code' => 4, 'name' => 'Comité de Validación en Fase de Experimentación'],
        ];

        DB::table('committee_type')->insert($committeeTypes);
    }
}
