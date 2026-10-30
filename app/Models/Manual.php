<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manual extends Model
{
    use SoftDeletes;

    public const CLASSIFICATIONS = ['generated' => 'Generado', 'update' => 'Actualización'];
    protected $fillable = ['classification', 'manual_types_id', 'manual_name', 'manual_phases_id', 'code', 'observations', 'publication_year', 'is_published', 'is_active', 'research_advances', 'experimentation_advances', 'process_started_on', 'research_started_on', 'published_on'];

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
        return $this->hasMany(ManualPhaseSuphase::class, 'manuals_id')->where('is_legacy', false);
    }

    public function legacyActivities()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'manuals_id')->where('is_legacy', true);
    }

    public function phaseStates()
    {
        return $this->hasMany(ManualPhaseState::class);
    }

    public function events()
    {
        return $this->hasMany(ManualEvent::class)->latest('id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function progress(?int $phaseId = null): float
    {
        $rows = $this->manualPhaseSuphases->where('manual_phases_id', $phaseId ?? $this->manual_phases_id);
        $slug = $phaseId && $phaseId !== $this->manual_phases_id ? ManualPhase::findOrFail($phaseId)->slug : $this->manualPhase->slug;

        return round(100 * $rows->filter(fn ($row) => $row->isWorkflowComplete())->count() / $this->expectedActivities($slug), 2);
    }

    public function expectedActivities(string $slug): int
    {
        $edition = $slug === 'edition' ? $this->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->manualPhase->slug === 'edition') : null;
        $legacyEdition = $edition && $this->phaseStates->firstWhere('manual_phase_id', $edition->manual_phases_id)?->catalog_version === 1;
        $research = $slug === 'research' ? $this->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->manualPhase->slug === 'research') : null;
        $researchVersion = $research ? $this->phaseStates->firstWhere('manual_phase_id', $research->manual_phases_id)?->catalog_version : null;

        return match ($slug) {
            'planning', 'cgft' => 2, 'research' => ($researchVersion >= 3 ? 11 : 10) + $this->research_advances, 'experimentation' => 6 + $this->experimentation_advances, 'edition' => $legacyEdition ? 6 : 3, default => throw new \LogicException('Fase desconocida')
        };
    }

    public function currentActivity(): string
    {
        $row = $this->manualPhaseSuphases->where('manual_phases_id', $this->manual_phases_id)
            ->sortBy(fn ($row) => $row->catalogSubphase->position)->first(fn ($row) => ! $row->isWorkflowComplete());

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
        $end = ($this->is_published && $this->published_on && $this->published_on->lte(today()) ? $this->published_on : today())->copy()->startOfDay();
        $start = $this->currentStart();
        $total = $this->process_started_on;
        $durationService = app(\App\Services\PhaseDurationService::class);
        $phaseMonths = $durationService->forPhase($this->manualPhase);
        $phaseLimit = $start?->copy()->addMonthsNoOverflow($phaseMonths);
        $researchLimit = $this->research_started_on?->copy()->addYearNoOverflow();

        return [
            'total_days' => $total ? (int) $total->copy()->startOfDay()->diffInDays($end) : null,
            'phase_days' => $start ? (int) $start->copy()->startOfDay()->diffInDays($end) : null,
            'phase_duration_months' => $phaseMonths,
            'phase_deadline' => $phaseLimit?->toDateString(),
            'phase_overdue' => ! $this->is_published && $this->manualPhase->slug !== 'planning' && $phaseLimit && today()->gt($phaseLimit),
            'research_overdue' => ! $this->is_published && $this->manualPhase->slug !== 'planning' && $researchLimit && today()->gt($researchLimit),
        ];
    }
}
