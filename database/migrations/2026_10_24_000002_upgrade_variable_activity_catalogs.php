<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $research = DB::table('manual_phases')->where('slug', 'research')->first();
            $experimentation = DB::table('manual_phases')->where('slug', 'experimentation')->first();
            if (! $research || ! $experimentation) {
                return; // A fresh installation receives the current catalog through its targeted seeders.
            }
            $now = now();
            $oldCatalog = DB::table('catalog_subphases')->whereIn('manual_phases_id', [$research->id, $experimentation->id])->get();
            DB::table('catalog_subphases')->updateOrInsert(['activity_key' => 'research.validation-meeting.4'], [
                'manual_phases_id' => $research->id, 'suphase_name' => '4. REUNIÓN DE VALIDACIÓN',
                'position' => 16, 'advance_number' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('catalog_subphases')->where('activity_key', 'research.14')->update(['position' => 17, 'updated_at' => $now]);
            $fourth = DB::table('catalog_subphases')->where('activity_key', 'research.validation-meeting.4')->value('id');
            $retiredIds = DB::table('catalog_subphases')->where('manual_phases_id', $experimentation->id)->where('advance_number', '>', 5)->pluck('id');
            DB::table('catalog_subphases')->whereIn('id', $retiredIds)->update(['is_active' => false, 'updated_at' => $now]);
            foreach (['experimentation.12' => 9, 'experimentation.13' => 10, 'experimentation.14' => 11] as $key => $position) {
                DB::table('catalog_subphases')->where('activity_key', $key)->update(['position' => $position, 'updated_at' => $now]);
            }
            DB::table('manuals as m')->join('manual_phases as p', 'p.id', '=', 'm.manual_phases_id')
                ->select('m.*', 'p.position as phase_position')->orderBy('m.id')->lockForUpdate()
                ->chunkById(100, function ($manuals) use ($research, $experimentation, $fourth, $retiredIds, $oldCatalog, $now) {
                    foreach ($manuals as $manual) {
                        if (DB::table('manual_events')->where('manual_id', $manual->id)->where('action', 'variable_catalog_updated')->exists()) {
                            continue;
                        }
                        $before = [];
                        $after = [];
                        foreach ([$research, $experimentation] as $phase) {
                            $state = DB::table('manual_phase_states')->where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->first();
                            if ($state && $state->catalog_version >= 3) {
                                continue;
                            }
                            $closed = $manual->is_published || $manual->phase_position > $phase->position;
                            $rows = DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->where('manual_phases_id', $phase->id)->get();
                            $before[$phase->slug] = ['state' => $state, 'activities' => $rows];
                            // Missing historical phases remain unknown; do not create fictitious phase states.
                            if ($state) {
                                DB::table('manual_phase_states')->where('id', $state->id)->update(['catalog_version' => $closed ? 2 : 3, 'updated_at' => $now]);
                            }
                            if (! $closed && $state) {
                                if ($phase->slug === 'research') {
                                    DB::table('manual_phase_suphases')->insertOrIgnore([
                                        'manuals_id' => $manual->id, 'manual_phases_id' => $phase->id,
                                        'catalog_subphases_id' => $fourth, 'is_completed' => false, 'is_legacy' => false,
                                        'created_at' => $now, 'updated_at' => $now,
                                    ]);
                                } else {
                                    DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->whereIn('catalog_subphases_id', $retiredIds)->update(['is_legacy' => true, 'updated_at' => $now]);
                                    if ($manual->experimentation_advances > 5) {
                                        DB::table('manuals')->where('id', $manual->id)->update(['experimentation_advances' => 5]);
                                    }
                                }
                            }
                            $after[$phase->slug] = ['catalog_version' => $state ? ($closed ? 2 : 3) : null, 'closed_history_preserved' => (bool) $closed];
                        }
                        if (! $before) {
                            continue;
                        }
                        DB::table('manuals')->where('id', $manual->id)->update(['lock_version' => $manual->lock_version + 1, 'updated_at' => $now]);
                        DB::table('manual_events')->insert([
                            'manual_id' => $manual->id, 'user_id' => null, 'action' => 'variable_catalog_updated',
                            'before' => json_encode(['phases' => $before, 'experimentation_advances' => $manual->experimentation_advances, 'catalog' => $oldCatalog], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'after' => json_encode(['phases' => $after, 'experimentation_advances' => DB::table('manuals')->where('id', $manual->id)->value('experimentation_advances')], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'reason' => 'Cuarta reunión pendiente y máximo cinco avances en fases abiertas; evidencia anterior archivada sin borrar fechas ni reconstruir cumplimientos.',
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }, 'm.id', 'id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restaurar el respaldo previo para revertir el catálogo sin perder nueva evidencia.');
    }
};
