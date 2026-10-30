<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualPhase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ManualProjection
{
    private ?Collection $phaseList = null;

    public function build(Manual $manual, array $durations, Carbon $asOf): array
    {
        if ($manual->is_published || ! $manual->manualPhase) {
            return ['start' => null, 'phases' => [], 'example' => false];
        }
        $phaseList = $this->phaseList ??= ManualPhase::orderBy('position')->get();
        $currentIndex = $phaseList->search(fn ($p) => $p->id === $manual->manual_phases_id);
        $state = $manual->phaseStates->firstWhere('manual_phase_id', $manual->manual_phases_id);
        $currentStart = $state?->started_on;
        if ($currentIndex === false || ! $currentStart) {
            return ['start' => null, 'phases' => [], 'example' => false];
        }

        $start = $currentStart->copy()->startOfDay();
        $deadline = $start->copy()->addMonthsNoOverflow($durations[$manual->manual_phases_id] ?? 3);
        $expired = $asOf->gt($deadline);
        $late = $manual->manualPhase->slug !== 'planning' && $expired;
        $phases = [];
        $currentPhase = $phaseList[$currentIndex];
        $actualEnd = $asOf->greaterThan($start) ? $asOf->copy() : $start->copy();
        $phases[] = ['phase' => $currentPhase, 'start' => $start, 'end' => $actualEnd,
            'projected' => false, 'overdue' => $currentPhase->slug !== 'planning' && $late, 'example' => false];
        // The remaining configured time is forecast, never presented as observed history.
        if (! $expired && $deadline->greaterThan($asOf)) {
            $phases[] = ['phase' => $currentPhase, 'start' => $asOf->copy()->startOfDay(), 'end' => $deadline->copy(),
                'projected' => true, 'overdue' => false, 'example' => false];
        }
        $cursor = $expired ? $asOf->copy()->startOfDay() : $deadline->copy();
        foreach ($phaseList->slice($currentIndex + 1)->values() as $phase) {
            $phaseStart = $cursor->copy();
            $phaseEnd = $phaseStart->copy()->addMonthsNoOverflow($durations[$phase->id] ?? 3);
            $phases[] = ['phase' => $phase, 'start' => $phaseStart, 'end' => $phaseEnd,
                'projected' => true, 'overdue' => false, 'example' => $expired];
            $cursor = $phaseEnd;
        }
        return ['start' => $start, 'phases' => $phases, 'example' => $expired, 'late' => $late];
    }
}
