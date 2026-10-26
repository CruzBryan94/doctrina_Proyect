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
    public const RELATIONS = ['manualType.doctrinalMap', 'manualPhase', 'manualPhaseSuphases.catalogSubphase.manualPhase', 'manualPhaseSuphases.responsibleUser', 'phaseStates', 'manualCommitteeMembers', 'manualMilitaryUnits'];

    public function configure(Manual $manual, bool $fromEntry = false): void
    {
        foreach (ManualPhase::when($fromEntry, fn ($q) => $q->where('position', '>=', $manual->manualPhase->position))->orderBy('position')->get() as $phase) {
            $state = ManualPhaseState::firstOrCreate(['manual_id' => $manual->id, 'manual_phase_id' => $phase->id], ['catalog_version' => in_array($phase->slug, ['research', 'experimentation'], true) ? 3 : 2]);
            if ($phase->slug === 'edition' && $state->catalog_version === 1) {
                continue;
            }
            if (in_array($phase->slug, ['research', 'experimentation'], true) && $state->catalog_version < 3 && ($manual->is_published || $phase->position < $manual->manualPhase->position)) {
                continue; // Closed historical phases are evidence, not templates to reconstruct.
            }
            $limit = $phase->slug === 'research' ? $manual->research_advances : ($phase->slug === 'experimentation' ? $manual->experimentation_advances : 8);
            foreach ($phase->catalogSubphases as $activity) {
                if ($phase->slug === 'research' && $state->catalog_version < 3 && $activity->activity_key === 'research.validation-meeting.4') {
                    continue;
                }
                if ($activity->advance_number && $activity->advance_number > $limit) {
                    continue;
                }
                ManualPhaseSuphase::firstOrCreate([
                    'manuals_id' => $manual->id, 'catalog_subphases_id' => $activity->id,
                ], ['manual_phases_id' => $phase->id, 'is_completed' => false]);
            }
        }
    }

    public function reopenEdition(Manual $manual, ManualPhase $phase): void
    {
        $state = $manual->phaseStates->firstWhere('manual_phase_id', $phase->id);
        if ($state?->catalog_version !== 1) {
            return;
        }
        $manual->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->update(['is_legacy' => true]);
        $state->update(['catalog_version' => 2]);
        $this->configure($manual);
        $manual->load(self::RELATIONS);
    }

    public function reopenVariablePhase(Manual $manual, ManualPhase $phase): void
    {
        if (! in_array($phase->slug, ['research', 'experimentation'], true)) {
            return;
        }
        $state = $manual->phaseStates->firstWhere('manual_phase_id', $phase->id);
        if (! $state || $state->catalog_version >= 3) {
            return;
        }
        $before = ['catalog_version' => $state->catalog_version, 'experimentation_advances' => $manual->experimentation_advances];
        if ($phase->slug === 'experimentation' && $manual->experimentation_advances > 5) {
            $ids = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->filter(fn ($row) => $row->catalogSubphase->advance_number > 5)->pluck('id');
            ManualPhaseSuphase::whereIn('id', $ids)->update(['is_legacy' => true]);
            $manual->experimentation_advances = 5;
            $manual->save();
        }
        $state->update(['catalog_version' => 3]);
        $this->configure($manual);
        $manual->load(self::RELATIONS);
        $this->event($manual, 'variable_catalog_reopened', $before, ['catalog_version' => 3, 'experimentation_advances' => $manual->experimentation_advances], 'Reapertura con catálogo vigente; evidencia retirada conservada como histórica.');
    }

    public function event(Manual $manual, string $action, array $before, array $after, ?string $reason = null): void
    {
        DB::transaction(function () use ($manual, $action, $before, $after, $reason) {
            Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            $event = ManualEvent::create(['manual_id' => $manual->id, 'user_id' => auth()->id(), 'action' => $action, 'before' => $before, 'after' => $after, 'reason' => $reason]);
            app(ManualPhaseHistory::class)->record($manual, $event);
        });
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

    public function lock(int $id, int $version, string $ability = 'update'): Manual
    {
        $manual = Manual::with(self::RELATIONS)->lockForUpdate()->findOrFail($id);
        Gate::authorize($ability, $manual);
        abort_if($manual->lock_version !== $version, 409, 'Otro usuario modificó este manual. Recarga la fila antes de continuar.');

        return $manual;
    }

    public function change(int $id, array $data): Manual
    {
        return DB::transaction(function () use ($id, $data) {
            $manual = $this->lock($id, $data['version']);
            $phase = $manual->manualPhase;
            if ($manual->is_published && (! $manual->phaseStates->contains('manual_phase_id', $phase->id) || $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->isEmpty())) {
                throw ValidationException::withMessages(['phase' => 'La publicación incorporada directamente no tiene seguimiento de fases. Sus metadatos se editan desde la ficha.']);
            }
            $before = ['phase' => $phase->slug, 'published' => $manual->is_published, 'substate' => $manual->currentSubstate()];
            if ($data['action'] === 'start') {
                $date = $data['started_on'];
                $previousDates = $manual->manualPhaseSuphases->filter(fn ($row) => $row->manual_phases_id !== $phase->id && $row->is_completed && $row->catalogSubphase->manualPhase->position < $phase->position)->pluck('completation_date');
                $ownDates = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->where('is_completed', true)->pluck('completation_date');
                if (($manual->process_started_on && $date < $manual->process_started_on->toDateString()) || $previousDates->contains(fn ($d) => $d && $d->toDateString() > $date) || $ownDates->contains(fn ($d) => $d && $d->toDateString() < $date)) {
                    throw ValidationException::withMessages(['started_on' => 'El inicio debe respetar las fechas de las fases anteriores y no superar el cumplimiento ya registrado.']);
                }
                $state = $manual->phaseStates->firstWhere('manual_phase_id', $phase->id);
                $before['started_on'] = $state->started_on?->toDateString();
                $state->update(['started_on' => $date]);
                foreach ($manual->manualPhaseSuphases->where('manual_phases_id', $phase->id) as $activity) {
                    EditionActivity::validate($activity, [], $state->started_on);
                }
                if ($phase->slug === 'research') {
                    $manual->research_started_on = ! $manual->research_started_on || $date < $manual->research_started_on->toDateString() ? $date : $manual->research_started_on;
                }
            } elseif ($data['action'] === 'activity') {
                $row = $manual->manualPhaseSuphases->firstWhere('catalog_subphases_id', $data['activity_id']);
                abort_unless($row && $row->manual_phases_id === $phase->id, 422, 'Esta actividad no pertenece a la fase actual del manual.');
                $start = $manual->currentStart();
                if (! $start && ($phase->slug !== 'edition' || (bool) $data['is_completed'])) {
                    throw ValidationException::withMessages(['started_on' => 'Registra primero la fecha de inicio de esta fase.']);
                }
                $completed = (bool) $data['is_completed'];
                $date = $completed ? ($data['completation_date'] ?? null) : null;
                if ($completed && (! $date || $date < $start->toDateString())) {
                    throw ValidationException::withMessages(['completation_date' => 'La fecha es obligatoria y debe ser igual o posterior al inicio de la fase.']);
                }
                $before['activity'] = $row->only(['catalog_subphases_id', 'is_completed', 'completation_date', 'follow_up', 'started_on', 'due_on', 'responsible_user_id']);
                $row->fill(['is_completed' => $completed, 'completation_date' => $date]);
                EditionActivity::validate($row, $data, $start);
                $row->save();
                $data['saved_activity'] = $row->fresh()->toArray();
                $manual->load('manualPhaseSuphases.catalogSubphase');
                $this->advanceIfComplete($manual);
            } elseif ($data['action'] === 'retreat') {
                $this->requireConfirmation($data);
                if ($phase->slug === 'planning' || $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->contains('is_completed', true)) {
                    throw ValidationException::withMessages(['phase' => 'Solo se puede retroceder si toda la fase está pendiente. Planificación es el límite inicial.']);
                }
                $previous = ManualPhase::where('position', '<', $phase->position)->orderByDesc('position')->firstOrFail();
                $manual->manual_phases_id = $previous->id;
                if ($previous->slug === 'edition') {
                    $this->reopenEdition($manual, $previous);
                }
                $this->reopenVariablePhase($manual, $previous);
                $manual->is_published = false;
                $manual->published_on = null;
            } else {
                $this->requireConfirmation($data);
                $this->advanceIfComplete($manual, true, $data['published_on'] ?? null);
            }
            $this->synchronizeSubstates($manual);
            $manual->lock_version++;
            $manual->save();
            $manual->load(self::RELATIONS);
            $effectiveDate = null;
            if ($before['phase'] !== $manual->manualPhase->slug || $before['published'] !== $manual->is_published) {
                $effectiveDate = in_array($data['action'], ['retreat', 'activity'], true) ? today()->toDateString()
                    : $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->max('completation_date')?->toDateString();
                $entry = \App\Models\ManualPhaseVisit::where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->whereNull('exit_event_id')->latest('id')->first();
                if ($entry?->entered_on && $effectiveDate && $effectiveDate < $entry->entered_on->toDateString()) {
                    $effectiveDate = $entry->entered_on->toDateString(); // A re-entry cannot end before its accredited arrival.
                }
            }
            $this->event($manual, $data['action'], $before, [
                'phase' => $manual->manualPhase->slug, 'published' => $manual->is_published,
                'effective_on' => $effectiveDate, 'published_on' => $manual->published_on?->toDateString(),
                'substate' => $manual->currentSubstate(),
                'activity_id' => $data['activity_id'] ?? null, 'is_completed' => $data['is_completed'] ?? null,
                'completation_date' => $data['completation_date'] ?? null, 'started_on' => $data['started_on'] ?? null,
                'activity' => $data['saved_activity'] ?? null,
            ], $data['reason'] ?? null);

            return $manual;
        });
    }

    private function advanceIfComplete(Manual $manual, bool $explicit = false, ?string $publicationDate = null): void
    {
        $phase = $manual->manualPhase;
        $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id);
        $expected = $manual->expectedActivities($phase->slug);
        $complete = $rows->count() === $expected && $expected > 0 && $rows->every(fn ($row) => $row->isWorkflowComplete());
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
        foreach ($rows as $row) {
            EditionActivity::validate($row, [], $manual->currentStart());
        }
        if (in_array($phase->slug, ['research', 'experimentation'], true)) {
            $types = $phase->slug === 'research' ? [1, 2] : [3, 4];
            foreach ($types as $type) {
                if (! $manual->manualCommitteeMembers->contains('committee_type_id', $type)) {
                    throw ValidationException::withMessages(['committees' => 'Asigna los dos comités propios de esta fase desde Editar manual antes de finalizarla.']);
                }
            }
        }
        // Completing an activity saves its progress; transitions require a separate confirmation.
        if (! $explicit && ! $manual->is_published) {
            return;
        }
        if ($phase->slug === 'cgft') {
            $manual->is_published = true;
            $manual->published_on = $publicationDate ?? $rows->max('completation_date')->toDateString();
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
        $field = $slug === 'research' ? 'research_advances' : 'experimentation_advances';
        if ($count === $manual->$field) {
            return; // Historical quantities remain intact when editing unrelated metadata.
        }
        if ($count < 2 || $count > ($slug === 'research' ? 6 : 5)) {
            throw ValidationException::withMessages([$slug === 'research' ? 'research_advances' : 'experimentation_advances' => 'Cantidad de avances fuera del rango permitido.']);
        }
        $phase = ManualPhase::where('slug', $slug)->firstOrFail();
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
