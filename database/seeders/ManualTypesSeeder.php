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
            ['code' => 'MFE', 'type_name' => 'MANUALES FUNDAMENTALES DEL EJÉRCITO'],
            ['code' => 'MFRE', 'type_name' => 'MANUALES FUNDAMENTALES DE REFERENCIA DEL EJÉRCITO'],
            ['code' => 'MCE', 'type_name' => 'MANUALES DE CAMPAÑA DEL EJÉRCITO'],
            ['code' => 'MTE', 'type_name' => 'MANUALES DE TÉCNICAS DEL EJÉRCITO'],
            ['code' => 'MEM', 'type_name' => 'EDUCACIÓN MILITAR'],
            ['code' => 'MME', 'type_name' => 'MANTENIMIENTO'],
            ['code' => 'MSI', 'type_name' => 'SEGURIDAD INTEGRADA'],
            ['code' => 'MAF', 'type_name' => 'ADMINISTRATIVO FUNCIONAL'],
            ['code' => 'RAE', 'type_name' => 'REGLAMENTOS ADMINISTRATIVOS'],
            ['code' => 'CTE', 'type_name' => 'CATÁLOGOS'],
        ];

        DB::table('manual_types')->insert($manualTypes);
    }


    :
:
:
:

:  (MEM)
:  (MME)
:  (MSI)
:  (MAF)
RAE:
CTE:  (CTE)


}
