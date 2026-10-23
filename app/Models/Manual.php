<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manual extends Model
{
    protected $fillable = ['manual_types_id', 'manual_name', 'manual_phases_id', 'code', 'observations', 'publication_year', 'is_published', 'is_active', 'research_advances', 'experimentation_advances', 'process_started_on', 'research_started_on', 'published_on'];

    protected $casts = [
        'is_published' => 'boolean', 'is_active' => 'boolean', 'manual_phases_id' => 'integer',
        'research_advances' => 'integer', 'experimentation_advances' => 'integer',
        'lock_version' => 'integer', 'process_started_on' => 'date',
        'research_started_on' => 'date', 'published_on' => 'date',
    ];

    public function manualType()
    {
        return $this->belongsTo(ManualType::class, 'manual_types_id');
    }

    public function manualPhase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phases_id');
    }

    public function manualCommitteeMembers()
    {
        return $this->hasMany(ManualCommitteeMember::class, 'manuals_id');
    }

    public function manualMilitaryUnits()
    {
        return $this->hasMany(ManualMilitaryUnit::class, 'manuals_id');
    }

    public function manualPhaseSuphases()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'manuals_id');
    }

    public function phaseStates()
    {
        return $this->hasMany(ManualPhaseState::class);
    }

    public function events()
    {
        return $this->hasMany(ManualEvent::class)->latest('id');
    }

    public function progress(?int $phaseId = null): float
    {
        $rows = $this->manualPhaseSuphases->where('manual_phases_id', $phaseId ?? $this->manual_phases_id);
        $slug = $phaseId && $phaseId !== $this->manual_phases_id ? ManualPhase::findOrFail($phaseId)->slug : $this->manualPhase->slug;

        return round(100 * $rows->filter(fn ($row) => $row->is_completed && $row->completation_date)->count() / $this->expectedActivities($slug), 2);
    }

    public function expectedActivities(string $slug): int
    {
        return match ($slug) {
            'planning', 'cgft' => 2, 'research' => 10 + $this->research_advances, 'experimentation' => 6 + $this->experimentation_advances, 'edition' => 6, default => throw new \LogicException('Fase desconocida')
        };
    }

    public function currentActivity(): string
    {
        $row = $this->manualPhaseSuphases->where('manual_phases_id', $this->manual_phases_id)
            ->sortBy(fn ($row) => $row->catalogSubphase->position)->first(fn ($row) => ! $row->is_completed);

        return $row?->catalogSubphase->suphase_name ?? 'Fase completada';
    }

    public function currentStart()
    {
        return $this->phaseStates->firstWhere('manual_phase_id', $this->manual_phases_id)?->started_on;
    }

    public function currentSubstate(): ?array
    {
        if (! isset(ManualPhaseState::VALIDATION_BOUNDARIES[$this->manualPhase->slug])) {
            return null;
        }
        $state = $this->phaseStates->firstWhere('manual_phase_id', $this->manual_phases_id);
        if (! $state?->substate) {
            return null;
        }

        return [
            'code' => $state->substate,
            'label' => $state->substate === 'validation' ? 'Validación' : 'Desarrollo',
            'started_on' => ($state->substate === 'validation' ? $state->validation_started_on : $state->started_on)?->toDateString(),
        ];
    }

    public function timing(): array
    {
        $end = ($this->published_on ?? today())->copy()->startOfDay();
        $start = $this->currentStart();
        $total = $this->process_started_on ?? $this->created_at;

        return [
            'total_days' => $total ? (int) $total->copy()->startOfDay()->diffInDays($end) : null,
            'phase_days' => $start ? (int) $start->copy()->startOfDay()->diffInDays($end) : null,
            'phase_overdue' => ! $this->is_published && $this->manualPhase->slug !== 'planning' && $start && today()->gt($start->copy()->addMonthsNoOverflow(3)),
            'research_overdue' => ! $this->is_published && $this->manualPhase->slug !== 'planning' && $this->research_started_on && today()->gt($this->research_started_on->copy()->addYearNoOverflow()),
        ];
    }
}
