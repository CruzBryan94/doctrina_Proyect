<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualPhase;
use Illuminate\Support\Collection;

class ManualDossier
{
    public const EVENT_LABELS = [
        'created' => 'Incorporación', 'edited' => 'Datos generales y participantes',
        'activity' => 'Seguimiento de actividad', 'start' => 'Inicio de fase',
        'advance' => 'Continuación de fase', 'retreat' => 'Retroceso de fase',
        'variable_catalog_reopened' => 'Reapertura con catálogo vigente',
        'deleted' => 'Enviado a papelera', 'restored' => 'Recuperado de papelera',
    ];

    public const EVENT_FIELDS = [
        'manual_name' => 'Nombre', 'classification' => 'Clasificación', 'code' => 'Código',
        'phase' => 'Fase', 'published' => 'Publicado', 'published_on' => 'Fecha de publicación',
        'process_started_on' => 'Inicio del proceso', 'research_started_on' => 'Primer inicio conocido de Investigación',
        'started_on' => 'Inicio de fase', 'is_completed' => 'Actividad cumplida',
        'completation_date' => 'Fecha de cumplimiento', 'effective_on' => 'Fecha efectiva',
    ];

    public function phases(Manual $manual, Collection $phases): Collection
    {
        return $phases->map(function (ManualPhase $phase) use ($manual) {
            $state = $manual->phaseStates->firstWhere('manual_phase_id', $phase->id);
            $activities = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)
                ->sortBy(fn ($row) => $row->catalogSubphase?->position ?? 0)->values();
            $evidence = $state?->started_on || $activities->contains(fn ($row) => $row->is_completed || $row->completation_date);
            $isCurrent = ! $manual->is_published && $phase->id === $manual->manual_phases_id;
            $isFuture = ! $manual->is_published && $phase->position > $manual->manualPhase->position && ! $evidence;
            $known = $evidence || $isCurrent;
            $completed = $activities->filter(fn ($row) => $row->isWorkflowComplete())->count();
            $expected = $manual->expectedActivities($phase->slug);

            return [
                'phase' => $phase, 'state' => $state, 'activities' => $activities,
                'known' => $known, 'future' => $isFuture, 'current' => $isCurrent,
                'label' => $isCurrent ? 'Fase actual' : ($evidence ? 'Seguimiento registrado' : ($isFuture ? 'Fase futura' : 'Antecedentes no acreditados')),
                'progress' => $known && $activities->isNotEmpty() ? round(100 * $completed / $expected, 2) : null,
                'completed' => $completed, 'expected' => $expected,
            ];
        });
    }

    public function eventChanges(array $before, array $after, Collection $phases): array
    {
        $changes = [];
        foreach (self::EVENT_FIELDS as $key => $label) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ($old === $new || (! is_scalar($old) && $old !== null) || (! is_scalar($new) && $new !== null)) {
                continue;
            }
            $format = function ($value) use ($key, $phases) {
                if ($value === null || $value === '') {
                    return 'Sin información';
                }
                if (is_bool($value)) {
                    return $value ? 'Sí' : 'No';
                }
                if ($key === 'phase') {
                    return $phases->firstWhere('slug', $value)?->phase_name ?? $value;
                }
                if ($key === 'classification') {
                    return Manual::CLASSIFICATIONS[$value] ?? 'Sin clasificar';
                }

                return (string) $value;
            };
            $changes[] = ['label' => $label, 'before' => $format($old), 'after' => $format($new)];
        }

        return $changes;
    }
}
