<?php

namespace App\Services;

use App\Models\DoctrinalMap;
use App\Models\InstitutionalReference;
use App\Models\InstitutionalReferenceAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstitutionalReferenceService
{
    /** Counts active manuals in one query, without hydrating tracking or participant data. */
    public function countsByMap(): array
    {
        return DB::table('manuals')
            ->join('manual_types', 'manual_types.id', '=', 'manuals.manual_types_id')
            ->where('manuals.is_active', true)
            ->select('manual_types.doctrinal_map_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('manual_types.doctrinal_map_id')
            ->pluck('total', 'doctrinal_map_id')->map(fn ($value) => (int) $value)->all();
    }

    /** Source detail counts share the same complete active-manual scope as the total. */
    public function breakdown(int $mapId)
    {
        return DB::table('manuals')
            ->join('manual_types', 'manual_types.id', '=', 'manuals.manual_types_id')
            ->join('manual_phases', 'manual_phases.id', '=', 'manuals.manual_phases_id')
            ->where('manuals.is_active', true)
            ->where('manual_types.doctrinal_map_id', $mapId)
            ->select('manuals.is_published', 'manual_phases.phase_name', 'manuals.classification')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('manuals.is_published', 'manual_phases.phase_name', 'manuals.classification')
            ->orderBy('manuals.is_published')->orderBy('manual_phases.phase_name')
            ->get();
    }

    public function update(DoctrinalMap $map, ?int $newValue, int $expectedVersion, User $actor): bool
    {
        if (($newValue !== null && ($newValue < 0 || $newValue > 1000000)) || $expectedVersion < 0) {
            throw new \InvalidArgumentException('La referencia debe estar entre 0 y 1.000.000 y la versión no puede ser negativa.');
        }
        return DB::transaction(function () use ($map, $newValue, $expectedVersion, $actor) {
            $user = User::whereKey($actor->id)->lockForUpdate()->first();
            abort_unless($user && $user->is_active && $user->hasRole('admin'), 403);

            // Serializing on the parent map makes first-time reference creation safe too.
            DoctrinalMap::whereKey($map->id)->lockForUpdate()->firstOrFail();
            $reference = InstitutionalReference::firstOrCreate(['doctrinal_map_id' => $map->id], ['expected_total' => null, 'lock_version' => 0]);
            $reference = InstitutionalReference::whereKey($reference->id)->lockForUpdate()->firstOrFail();
            if ((int) $reference->lock_version !== $expectedVersion) {
                return false;
            }
            $before = $reference->expected_total === null ? null : (int) $reference->expected_total;
            if ($before === $newValue) {
                return true;
            }
            $reference->expected_total = $newValue;
            $reference->lock_version++;
            $reference->save();
            InstitutionalReferenceAudit::create([
                'doctrinal_map_id' => $map->id, 'user_id' => $user->id,
                'before_total' => $before, 'after_total' => $newValue,
            ]);
            return true;
        });
    }
}
