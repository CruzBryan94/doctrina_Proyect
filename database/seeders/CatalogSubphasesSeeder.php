<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class CatalogSubphasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subphases = [
           // Subfases de Investigación
           ['suphase_name' => 'CONFORMAR LOS COMITES DE INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'INDUCCIÓN Y LINEAMIENTOS', 'manual_phases_id' => 1],
           ['suphase_name' => 'ENTREGA DEL PERFIL DEL PROYECTO Y CRONOGRAMA', 'manual_phases_id' => 1],
           ['suphase_name' => 'PRIMER AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'SEGUNDO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'TERCER AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'CUARTO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'QUINTO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'SEXTO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'SEPTIMO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'OCTAVO AVANCE DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'ENTREGA DE LA CARPETA DE INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'VALIDACIÓN DE LA INVESTIGACIÓN', 'manual_phases_id' => 1],
           ['suphase_name' => 'ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES', 'manual_phases_id' => 1],

            // Subfases de Experimentación
            ['suphase_name' => 'DISPONER LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'INDUCCIÓN Y LINEAMIENTOS AL COMITÉ DE EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'ENTREGA DEL CRONOGRAMA DE ACTIVIDADES', 'manual_phases_id' => 2],
            ['suphase_name' => 'PRIMER AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'SEGUNDO AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'TERCER AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'CUARTO AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'QUINTO AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'SEXTO AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'SÉPTIMO AVANCE DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'INFORME FINAL DE LA EXPERIMENTACIÓN', 'manual_phases_id' => 2],
            ['suphase_name' => 'VALIDACIÓN DE LA EXPERIMENTACIÓN DEL PROYECTO DOCTRINARIO', 'manual_phases_id' => 2],
            ['suphase_name' => 'ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES', 'manual_phases_id' => 2],



            // Subfases de Edición
            ['suphase_name' => 'CORRECCIÓN IDIOMATICA', 'manual_phases_id' => 3],
            ['suphase_name' => 'CORRECCIÓN GRAFICA', 'manual_phases_id' => 3],
            ['suphase_name' => 'CORRECCIONES FINALES ARTE DISEÑO EDITORIAL', 'manual_phases_id' => 3],
            ['suphase_name' => 'TRÁMITE DE LEGALIZACION', 'manual_phases_id' => 3],
            ['suphase_name' => 'PUBLICACIÓN EL EL SIFTE', 'manual_phases_id' => 3],
        ];

        DB::table('catalog_subphases')->insert($subphases);
    }
}
