<?php

namespace Database\Seeders;

use App\Models\CatalogSubphase;
use App\Models\ManualPhase;
use Illuminate\Database\Seeder;

class CatalogSubphasesSeeder extends Seeder
{
    public function run(): void
    {
        $ordinals = ['PRIMER', 'SEGUNDO', 'TERCER', 'CUARTO', 'QUINTO', 'SEXTO', 'SÉPTIMO', 'OCTAVO'];
        $definitions = [
            'planning' => ['ANÁLISIS', 'FACTIBILIDAD'],
            'research' => [],
            'experimentation' => ['DISPONER LA EXPERIMENTACIÓN', 'INDUCCIÓN Y LINEAMIENTOS AL COMITÉ DE EXPERIMENTACIÓN', 'ENTREGA DEL CRONOGRAMA DE ACTIVIDADES'],
            'edition' => ['CORRECCIÓN IDIOMÁTICA', 'CORRECCIÓN GRÁFICA', 'CORRECCIONES FINALES ARTE DISEÑO EDITORIAL', 'TRÁMITE DE LEGALIZACIÓN', 'ENVIADO GRUPO ASESOR', 'PUBLICACIÓN EN EL SIFTE'],
            'cgft' => ['PENDIENTE', 'LEGALIZADO'],
        ];
        foreach ($definitions as $slug => $names) {
            $phase = ManualPhase::where('slug', $slug)->firstOrFail();
            if ($slug === 'research') {
                foreach (self::researchDefinitions() as $index => [$key, $name, $advance]) {
                    CatalogSubphase::updateOrCreate(['activity_key' => $key], [
                        'suphase_name' => $name, 'manual_phases_id' => $phase->id,
                        'position' => $index + 1, 'advance_number' => $advance,
                    ]);
                }

                continue;
            }
            if ($slug === 'experimentation') {
                foreach ($ordinals as $index => $ordinal) {
                    $names[] = $ordinal.' AVANCE DE LA EXPERIMENTACIÓN';
                }
                $names = array_merge($names, ['INFORME FINAL DE LA EXPERIMENTACIÓN', 'VALIDACIÓN DE LA EXPERIMENTACIÓN DEL PROYECTO DOCTRINARIO', 'ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES']);
            }
            foreach ($names as $index => $name) {
                $advance = $slug === 'experimentation' && $index >= 3 && $index <= 10 ? $index - 2 : null;
                CatalogSubphase::updateOrCreate(['activity_key' => $slug.'.'.($index + 1)], [
                    'suphase_name' => $name, 'manual_phases_id' => $phase->id, 'position' => $index + 1, 'advance_number' => $advance,
                ]);
            }
        }
    }

    public static function researchDefinitions(): array
    {
        return [
            ['research.1', 'CONFORMAR LOS COMITES DE INVESTIGACIÓN', null],
            ['research.2', 'INDUCCIÓN Y LINEAMIENTOS', null],
            ['research.3', 'ENTREGA DEL PERFIL Y CRONOGRAMA', null],
            ['research.three-months', 'TRES MES DE INVESTIGACIÓN', null],
            ['research.4', '1. AVANCE DE LA INVESTIGACIÓN', 1],
            ['research.5', '2. AVANCE DE LA INVESTIGACIÓN', 2],
            ['research.6', '3. AVANCE DE LA INVESTIGACIÓN', 3],
            ['research.7', '4. AVANCE DE LA INVESTIGACIÓN', 4],
            ['research.8', '5. AVANCE DE LA INVESTIGACIÓN', 5],
            ['research.9', '6. AVANCE DE LA INVESTIGACIÓN', 6],
            ['research.12', 'ENTREGA DE LA CARPETA DE INVESTIGACIÓN', null],
            ['research.13', 'VALIDACIÓN DE LA INVESTIGACIÓN', null],
            ['research.validation-meeting.1', '1. REUNIÓN DE VALIDACIÓN', null],
            ['research.validation-meeting.2', '2. REUNIÓN DE VALIDACIÓN', null],
            ['research.validation-meeting.3', '3. REUNIÓN DE VALIDACIÓN', null],
            ['research.14', 'ENTREGA DEL PROYECTO DOCTRINARIO CORREGIDO Y FINALIZADO', null],
        ];
    }
}
