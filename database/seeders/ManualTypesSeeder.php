<?php

namespace Database\Seeders;

use App\Models\DoctrinalMap;
use App\Models\ManualType;
use Illuminate\Database\Seeder;

class ManualTypesSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'army' => ['MAPA DOCTRINARIO DEL EJÉRCITO', ['MFE' => 'MANUALES FUNDAMENTALES DEL EJÉRCITO', 'MFRE' => 'MANUALES FUNDAMENTALES DE REFERENCIA DEL EJÉRCITO', 'MCE' => 'MANUALES DE CAMPAÑA DEL EJÉRCITO', 'MTE' => 'MANUALES DE TÉCNICAS DEL EJÉRCITO']],
            'publications' => ['MAPA DE PUBLICACIONES MILITARES', ['MEM' => 'EDUCACIÓN MILITAR', 'MME' => 'MANTENIMIENTO', 'MSI' => 'SEGURIDAD INTEGRADA', 'MAF' => 'ADMINISTRATIVO FUNCIONAL', 'RAE' => 'REGLAMENTOS ADMINISTRATIVOS', 'CTE' => 'CATÁLOGOS']],
        ];
        foreach ($groups as $code => [$name, $types]) {
            $map = DoctrinalMap::updateOrCreate(['code' => $code], ['name' => $name]);
            foreach ($types as $typeCode => $typeName) {
                ManualType::updateOrCreate(['code' => $typeCode], ['type_name' => $typeName, 'doctrinal_map_id' => $map->id]);
            }
        }
    }
}
