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
            'research' => ['CONFORMAR LOS COMITÉS DE INVESTIGACIÓN', 'INDUCCIÓN Y LINEAMIENTOS', 'ENTREGA DEL PERFIL DEL PROYECTO Y CRONOGRAMA'],
            'experimentation' => ['DISPONER LA EXPERIMENTACIÓN', 'INDUCCIÓN Y LINEAMIENTOS AL COMITÉ DE EXPERIMENTACIÓN', 'ENTREGA DEL CRONOGRAMA DE ACTIVIDADES'],
            'edition' => ['CORRECCIÓN IDIOMÁTICA', 'CORRECCIÓN GRÁFICA', 'CORRECCIONES FINALES ARTE DISEÑO EDITORIAL', 'TRÁMITE DE LEGALIZACIÓN', 'ENVIADO GRUPO ASESOR', 'PUBLICACIÓN EN EL SIFTE'],
            'cgft' => ['PENDIENTE', 'LEGALIZADO'],
        ];
        foreach ($definitions as $slug => $names) {
            $phase = ManualPhase::where('slug', $slug)->firstOrFail();
            if (in_array($slug, ['research', 'experimentation'], true)) {
                foreach ($ordinals as $index => $ordinal) {
                    $names[] = $ordinal.' AVANCE DE LA '.($slug === 'research' ? 'INVESTIGACIÓN' : 'EXPERIMENTACIÓN');
                }
                $names = array_merge($names, $slug === 'research'
                    ? ['ENTREGA DE LA CARPETA DE INVESTIGACIÓN', 'VALIDACIÓN DE LA INVESTIGACIÓN', 'ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES']
                    : ['INFORME FINAL DE LA EXPERIMENTACIÓN', 'VALIDACIÓN DE LA EXPERIMENTACIÓN DEL PROYECTO DOCTRINARIO', 'ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES']);
            }
            foreach ($names as $index => $name) {
                $advance = in_array($slug, ['research', 'experimentation'], true) && $index >= 3 && $index <= 10 ? $index - 2 : null;
                CatalogSubphase::updateOrCreate(['activity_key' => $slug.'.'.($index + 1)], [
                    'suphase_name' => $name, 'manual_phases_id' => $phase->id, 'position' => $index + 1, 'advance_number' => $advance,
                ]);
            }
        }
    }
}
