<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualPhaseState;
use App\Models\ManualPhaseSuphase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManualWorkflow
{
    public const RELATIONS = ['manualType.doctrinalMap', 'manualPhase', 'manualPhaseSuphases.catalogSubphase.manualPhase', 'phaseStates', 'manualCommitteeMembers', 'manualMilitaryUnits'];

    public function configure(Manual $manual): void
    {
        foreach (ManualPhase::orderBy('position')->get() as $phase) {
            ManualPhaseState::firstOrCreate(['manual_id' => $manual->id, 'manual_phase_id' => $phase->id]);
            $limit = $phase->slug === 'research' ? $manual->research_advances : ($phase->slug === 'experimentation' ? $manual->experimentation_advances : 8);
            foreach ($phase->catalogSubphases as $activity) {
                if ($activity->advance_number && $activity->advance_number > $limit) {
                    continue;
                }
                ManualPhaseSuphase::firstOrCreate([
                    'manuals_id' => $manual->id, 'catalog_subphases_id' => $activity->id,
                ], ['manual_phases_id' => $phase->id, 'is_completed' => false]);
            }
        }
    }

    public function event(Manual $manual, string $action, array $before, array $after, ?string $reason = null): void
    {
        ManualEvent::create(['manual_id' => $manual->id, 'user_id' => auth()->id(), 'action' => $action, 'before' => $before, 'after' => $after, 'reason' => $reason]);
    }

    public function synchronizeSubstates(Manual $manual): void
    {
        foreach (ManualPhaseState::VALIDATION_BOUNDARIES as $slug => $key) {
            $boundary = $manual->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->activity_key === $key);
            if (! $boundary) {
                continue;
            }
            $state = $manual->phaseStates->firstWhere('manual_phase_id', $boundary->manual_phases_id);
            if (! $state) {
                continue;
            }
            $validationDate = $state->started_on && $boundary->is_completed ? $boundary->completation_date : null;
            $state->fill([
                'substate' => $state->started_on ? ($validationDate ? 'validation' : 'development') : null,
                'validation_started_on' => $validationDate,
            ]);
            if ($state->isDirty()) {
                $state->save();
            }
        }
    }

    public function lock(int $id, int $version): Manual
    {
        $manual = Manual::with(self::RELATIONS)->lockForUpdate()->findOrFail($id);
        Gate::authorize('update', $manual);
        abort_if($manual->lock_version !== $version, 409, 'Otro usuario modificó este manual. Recarga la fila antes de continuar.');

        return $manual;
    }

    public function change(int $id, array $data): Manual
    {
        return DB::transaction(function () use ($id, $data) {
            $manual = $this->lock($id, $data['version']);
            $phase = $manual->manualPhase;
            $before = ['phase' => $phase->slug, 'published' => $manual->is_published, 'substate' => $manual->currentSubstate()];
            if ($data['action'] === 'start') {
                $date = $data['started_on'];
                $previousDates = $manual->manualPhaseSuphases->filter(fn ($row) => $row->manual_phases_id !== $phase->id && $row->is_completed && $row->catalogSubphase->manualPhase->position < $phase->position)->pluck('completation_date');
                $ownDates = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->where('is_completed', true)->pluck('completation_date');
                if ($date < $manual->process_started_on->toDateString() || $previousDates->contains(fn ($d) => $d && $d->toDateString() > $date) || $ownDates->contains(fn ($d) => $d && $d->toDateString() < $date)) {
                    throw ValidationException::withMessages(['started_on' => 'El inicio debe respetar las fechas de las fases anteriores y no superar el cumplimiento ya registrado.']);
                }
                $state = $manual->phaseStates->firstWhere('manual_phase_id', $phase->id);
                $before['started_on'] = $state->started_on?->toDateString();
                $state->update(['started_on' => $date]);
                if ($phase->slug === 'research') {
                    $manual->research_started_on = ! $manual->research_started_on || $date < $manual->research_started_on->toDateString() ? $date : $manual->research_started_on;
                }
            } elseif ($data['action'] === 'activity') {
                $row = $manual->manualPhaseSuphases->firstWhere('catalog_subphases_id', $data['activity_id']);
                abort_unless($row && $row->manual_phases_id === $phase->id, 422, 'Esta actividad no pertenece a la fase actual del manual.');
                $start = $manual->currentStart();
                if (! $start) {
                    throw ValidationException::withMessages(['started_on' => 'Registra primero la fecha de inicio de esta fase.']);
                }
                $completed = (bool) $data['is_completed'];
                $date = $completed ? ($data['completation_date'] ?? null) : null;
                if ($completed && (! $date || $date < $start->toDateString())) {
                    throw ValidationException::withMessages(['completation_date' => 'La fecha es obligatoria y debe ser igual o posterior al inicio de la fase.']);
                }
                $before['activity'] = $row->only(['catalog_subphases_id', 'is_completed', 'completation_date']);
                $row->update(['is_completed' => $completed, 'completation_date' => $date]);
                $manual->load('manualPhaseSuphases.catalogSubphase');
                $this->advanceIfComplete($manual);
            } elseif ($data['action'] === 'retreat') {
                $this->requireConfirmation($data);
                if ($phase->slug === 'planning' || $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->contains('is_completed', true)) {
                    throw ValidationException::withMessages(['phase' => 'Solo se puede retroceder si toda la fase está pendiente. Planificación es el límite inicial.']);
                }
                $previous = ManualPhase::where('position', '<', $phase->position)->orderByDesc('position')->firstOrFail();
                $manual->manual_phases_id = $previous->id;
                $manual->is_published = false;
                $manual->published_on = null;
            } else {
                $this->requireConfirmation($data);
                $this->advanceIfComplete($manual, true);
            }
            $this->synchronizeSubstates($manual);
            $manual->lock_version++;
            $manual->save();
            $manual->load(self::RELATIONS);
            $this->event($manual, $data['action'], $before, [
                'phase' => $manual->manualPhase->slug, 'published' => $manual->is_published,
                'substate' => $manual->currentSubstate(),
                'activity_id' => $data['activity_id'] ?? null, 'is_completed' => $data['is_completed'] ?? null,
                'completation_date' => $data['completation_date'] ?? null, 'started_on' => $data['started_on'] ?? null,
            ], $data['reason'] ?? null);

            return $manual;
        });
    }

    private function advanceIfComplete(Manual $manual, bool $explicit = false): void
    {
        $phase = $manual->manualPhase;
        $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id);
        $expected = $manual->expectedActivities($phase->slug);
        $complete = $rows->count() === $expected && $expected > 0 && $rows->every(fn ($row) => $row->is_completed && $row->completation_date);
        if (! $complete) {
            $manual->is_published = false;
            $manual->published_on = null;
            if ($explicit) {
                throw ValidationException::withMessages(['phase' => 'Faltan actividades o fechas para completar esta fase.']);
            }

            return;
        }
        if (! $manual->currentStart()) {
            throw ValidationException::withMessages(['started_on' => 'Registra la fecha de inicio.']);
        }
        if (in_array($phase->slug, ['research', 'experimentation'], true)) {
            $types = $phase->slug === 'research' ? [1, 2] : [3, 4];
            foreach ($types as $type) {
                if (! $manual->manualCommitteeMembers->contains('committee_type_id', $type)) {
                    throw ValidationException::withMessages(['committees' => 'Asigna los dos comités propios de esta fase desde Editar manual antes de finalizarla.']);
                }
            }
        }
        foreach (ManualPhase::where('position', '<', $phase->position)->get() as $previous) {
            $previousRows = $manual->manualPhaseSuphases->where('manual_phases_id', $previous->id);
            if ($previousRows->count() !== $manual->expectedActivities($previous->slug) || ! $previousRows->every(fn ($row) => $row->is_completed && $row->completation_date)) {
                throw ValidationException::withMessages(['phase' => 'Una fase anterior está incompleta. Corrige el recorrido antes de avanzar.']);
            }
        }
        // Completing an activity saves its progress; transitions require a separate confirmation.
        if (! $explicit && ! $manual->is_published) {
            return;
        }
        if ($phase->slug === 'cgft') {
            $manual->is_published = true;
            $manual->published_on = $rows->max('completation_date')->toDateString();
        } else {
            $next = ManualPhase::where('position', '>', $phase->position)->orderBy('position')->firstOrFail();
            $manual->manual_phases_id = $next->id;
            $manual->is_published = false;
            $manual->published_on = null;
        }
    }

    private function requireConfirmation(array $data): void
    {
        if (! ($data['confirmed'] ?? false)) {
            throw ValidationException::withMessages(['confirmed' => 'Confirma el cambio de fase antes de continuar.']);
        }
    }

    public function resize(Manual $manual, string $slug, int $count): void
    {
        $phase = ManualPhase::where('slug', $slug)->firstOrFail();
        $field = $slug === 'research' ? 'research_advances' : 'experimentation_advances';
        if ($count === $manual->$field) {
            return;
        }
        if ($manual->is_published || $phase->position < $manual->manualPhase->position) {
            throw ValidationException::withMessages([$field => 'Esta fase ya fue superada. No se puede cambiar su configuración desde la edición general.']);
        }
        $remove = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)
            ->filter(fn ($row) => $row->catalogSubphase->advance_number > $count);
        foreach ($remove as $row) {
            $hasHistory = $manual->events()->where('action', 'activity')->get()->contains(fn ($event) => ($event->after['activity_id'] ?? null) == $row->catalog_subphases_id);
            if ($row->is_completed || $hasHistory) {
                throw ValidationException::withMessages([$field => 'No se pueden retirar avances con cumplimiento o historial.']);
            }
        }
        if ($remove->isNotEmpty()) {
            ManualPhaseSuphase::whereIn('id', $remove->pluck('id'))->delete();
        }
        $manual->$field = $count;
        $manual->save();
        $this->configure($manual);
    }
}
