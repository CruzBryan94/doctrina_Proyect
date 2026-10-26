<?php

namespace App\Http\Controllers;

use App\Models\CommitteeType;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualPhaseVisit;
use App\Services\ManualDossier;
use Illuminate\Support\Facades\Schema;

class ManualDossierController extends Controller
{
    public function show(int $id, ManualDossier $dossier)
    {
        $manual = Manual::with('manualPhase')->findOrFail($id);
        $this->authorize('view', $manual);
        $manual->load([
            'manualType.doctrinalMap', 'phaseStates',
            'manualPhaseSuphases.catalogSubphase.manualPhase', 'manualPhaseSuphases.responsibleUser',
            'legacyActivities.catalogSubphase.manualPhase', 'legacyActivities.responsibleUser',
            'manualCommitteeMembers.committeeMember.grade', 'manualMilitaryUnits.militaryUnit',
        ]);
        $phases = ManualPhase::orderBy('position')->get();
        $phaseDetails = $dossier->phases($manual, $phases);
        $committeeTypes = CommitteeType::orderBy('code')->get();
        $events = $manual->events()->whereIn('action', array_keys(ManualDossier::EVENT_LABELS))->with('user:id,name')
            ->paginate(20, ['*'], 'events_page')->withQueryString();
        $events->getCollection()->transform(fn ($event) => [
            'label' => ManualDossier::EVENT_LABELS[$event->action], 'recorded_at' => $event->created_at,
            'actor' => $event->user?->name ?? 'Autor no disponible',
            'changes' => $dossier->eventChanges($event->before ?? [], $event->after ?? [], $phases),
        ]);
        $visitsAvailable = Schema::hasTable('manual_phase_visits');
        $visits = $visitsAvailable ? ManualPhaseVisit::where('manual_id', $manual->id)->with('phase')
            ->orderBy('entered_recorded_at')->orderBy('id')->paginate(15, ['*'], 'visits_page')->withQueryString() : null;

        return view('manuals.show', compact('manual', 'phaseDetails', 'committeeTypes', 'events', 'visits', 'visitsAvailable'));
    }
}
