<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilitaryUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $militaryUnits = [
            ['unit_name' => 'Batallón de Infantería N1 Constitución', 'unit_acronym' => 'BC1'],
            ['unit_name' => 'Grupo de Caballería Blindada N4 Febres Cordero', 'unit_acronym' => 'GCB4'],
            ['unit_name' => 'Escuela de Fuerzas Especiales', 'unit_acronym' => 'EFE9'],
        ];

        DB::table('military_units')->insert($militaryUnits);
    }
}
