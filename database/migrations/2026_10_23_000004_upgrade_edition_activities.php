<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $phase = DB::table('manual_phases')->where('slug', 'edition')->first();
            if (! $phase) {
                return;
            }
            $names = [
                'edition.idiomatic-start' => 'INICIAR CORRECCIÓN IDIOMÁTICA',
                'edition.idiomatic-final-review' => 'CORRECCIÓN IDIOMÁTICA REVISIÓN FINAL',
                'edition.editorial-layout' => 'CORRECCIÓN EDITORIAL Y GRÁFICA / MAQUETACIÓN',
            ];
            $legacyIds = DB::table('catalog_subphases')->where('manual_phases_id', $phase->id)->whereNotIn('activity_key', array_keys($names))->pluck('id');
            DB::table('catalog_subphases')->whereIn('id', $legacyIds)->update(['is_active' => false]);
            $now = now();
            foreach ($names as $key => $label) {
                DB::table('catalog_subphases')->updateOrInsert(['activity_key' => $key], ['manual_phases_id' => $phase->id, 'suphase_name' => $label, 'position' => array_search($key, array_keys($names), true) + 1, 'advance_number' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
            $newIds = DB::table('catalog_subphases')->whereIn('activity_key', array_keys($names))->pluck('id');
            DB::table('manuals as m')->join('manual_phases as p', 'p.id', '=', 'm.manual_phases_id')->select('m.*', 'p.position as phase_position')->orderBy('m.id')->lockForUpdate()->chunkById(100, function ($manuals) use ($phase, $legacyIds, $newIds, $now) {
                foreach ($manuals as $manual) {
                    $old = DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->whereIn('catalog_subphases_id', $legacyIds)->get();
                    $closed = $manual->phase_position > $phase->position || $manual->is_published;
                    DB::table('manual_phase_states')->updateOrInsert(['manual_id' => $manual->id, 'manual_phase_id' => $phase->id], ['catalog_version' => $closed ? 1 : 2, 'updated_at' => $now]);
                    if (! $closed) {
                        DB::table('manual_phase_suphases')->where('manuals_id', $manual->id)->whereIn('catalog_subphases_id', $legacyIds)->update(['is_legacy' => true]);
                        foreach ($newIds as $id) {
                            DB::table('manual_phase_suphases')->insertOrIgnore(['manuals_id' => $manual->id, 'catalog_subphases_id' => $id, 'manual_phases_id' => $phase->id, 'is_completed' => false, 'is_legacy' => false, 'created_at' => $now, 'updated_at' => $now]);
                        }
                    }
                    DB::table('manuals')->where('id', $manual->id)->update(['lock_version' => $manual->lock_version + 1, 'updated_at' => $now]);
                    DB::table('manual_events')->insert([
                        'manual_id' => $manual->id, 'user_id' => null, 'action' => 'edition_catalog_updated',
                        'before' => json_encode(['activities' => $old], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'after' => json_encode(['catalog_version' => $closed ? 1 : 2, 'closed_edition_preserved' => (bool) $closed], JSON_THROW_ON_ERROR),
                        'reason' => $closed ? 'Edición histórica cerrada conservada; sin inventar datos del nuevo flujo.' : 'Tres actividades nuevas pendientes. Seguimiento anterior conservado en el historial.',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }, 'm.id', 'id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restaurar el respaldo previo para revertir el catálogo sin eliminar seguimientos posteriores.');
    }
};
