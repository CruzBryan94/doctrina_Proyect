<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Frozen definition: existing keys retain the same activity IDs and tracking.
    private const ACTIVITIES = [
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

    public function up(): void
    {
        DB::transaction(function () {
            $phase = DB::table('manual_phases')->where('slug', 'research')->first();
            if (! $phase) {
                return; // Fresh installations receive the corrected catalog from the seeder.
            }
            $oldCatalog = DB::table('catalog_subphases')->where('manual_phases_id', $phase->id)->get();
            $oldFinalId = $oldCatalog->firstWhere('activity_key', 'research.14')?->id;
            $now = now();
            foreach (self::ACTIVITIES as $index => [$key, $label, $advance]) {
                $values = ['suphase_name' => $label, 'manual_phases_id' => $phase->id, 'position' => $index + 1, 'advance_number' => $advance, 'updated_at' => $now];
                if ($existing = $oldCatalog->firstWhere('activity_key', $key)) {
                    DB::table('catalog_subphases')->where('id', $existing->id)->update($values);
                } else {
                    DB::table('catalog_subphases')->insert($values + ['activity_key' => $key, 'created_at' => $now]);
                }
            }
            $catalog = DB::table('catalog_subphases')->where('manual_phases_id', $phase->id)->whereIn('activity_key', array_column(self::ACTIVITIES, 0))->get();
            DB::table('manuals as manuals')->join('manual_phases as phases', 'phases.id', '=', 'manuals.manual_phases_id')
                ->select('manuals.*', 'phases.position as phase_position')->orderBy('manuals.id')->lockForUpdate()
                ->chunkById(200, function ($manuals) use ($phase, $oldCatalog, $oldFinalId, $catalog, $now) {
                    foreach ($manuals as $manual) {
                        $before = DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->where('manual_phases_id', $phase->id)->get();
                        $count = max(2, min(6, $manual->research_advances));
                        $closed = $manual->phase_position > $phase->position || $manual->is_published;
                        $final = $before->firstWhere('catalog_subphases_id', $oldFinalId);
                        $closedOn = $final && $final->is_completed && $final->completation_date
                            ? $final->completation_date : $before->where('is_completed', true)->max('completation_date');
                        $removeIds = $oldCatalog->filter(fn ($activity) => $activity->advance_number > $count)->pluck('id');
                        DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->whereIn('catalog_subphases_id', $removeIds)->delete();
                        foreach ($catalog as $activity) {
                            if ($activity->advance_number && $activity->advance_number > $count) {
                                continue;
                            }
                            DB::table('manual_phase_suphases')->insertOrIgnore([
                                'manuals_id' => $manual->id, 'manual_phases_id' => $phase->id, 'catalog_subphases_id' => $activity->id,
                                // Authorized normalization of test scenarios; never reopen a later phase.
                                'is_completed' => $closed && $closedOn !== null,
                                'completation_date' => $closed ? $closedOn : null,
                                'created_at' => $now, 'updated_at' => $now,
                            ]);
                        }
                        DB::table('manuals')->where('id', $manual->id)->update(['research_advances' => $count, 'lock_version' => $manual->lock_version + 1, 'updated_at' => $now]);
                        $after = DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->where('manual_phases_id', $phase->id)->get();
                        DB::table('manual_events')->insert([
                            'manual_id' => $manual->id, 'user_id' => null, 'action' => 'research_catalog_updated',
                            'before' => json_encode(['research_advances' => $manual->research_advances, 'activities' => $before, 'catalog' => $oldCatalog], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'after' => json_encode(['research_advances' => $count, 'activities' => $after, 'closed_research' => (bool) $closed, 'new_activity_completion_date' => $closed ? $closedOn : null], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'reason' => 'Corrección autorizada de datos de prueba: máximo 6 avances; tareas nuevas en fases cerradas usan la fecha de cierre registrada.',
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }, 'manuals.id', 'id');
            DB::table('catalog_subphases')->where('manual_phases_id', $phase->id)->where('advance_number', '>', 6)->delete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración corrige datos de prueba y guarda el antes/después en manual_events. Para deshacerla debe restaurarse el respaldo previo; no se eliminan datos nuevos automáticamente.');
    }
};
