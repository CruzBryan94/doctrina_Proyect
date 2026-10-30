<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualPhaseNote;
use App\Models\ManualPhaseNoteAudit;
use App\Models\ManualPhaseSuphase;
use App\Services\ManualDossier;
use App\Services\ManualPhaseNotes;
use Illuminate\Http\Request;

class ManualPhaseNoteController extends Controller
{
    public function show(int $manualId, int $phaseId, ManualPhaseNotes $notes)
    {
        $manual = Manual::query()->with('manualPhase')->findOrFail($manualId);
        $phase = ManualPhase::query()->findOrFail($phaseId);
        abort_unless($notes->canView(request()->user()), 403);

        $note = ManualPhaseNote::query()->where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->value('note');
        $audits = ManualPhaseNoteAudit::query()->where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)
            ->with('user:id,name')->latest('created_at')->latest('id')->paginate(15, ['*'], 'note_audits_page')->withQueryString();
        $activities = ManualPhaseSuphase::query()->where('manuals_id', $manual->id)->where('manual_phases_id', $phase->id)
            ->where('is_legacy', false)->with('catalogSubphase:id,suphase_name,activity_key,position')
            ->get()->sortBy(fn ($row) => $row->catalogSubphase?->position ?? 0)->values();
        $phaseEvents = $manual->events()->whereIn('action', ['activity', 'start', 'advance', 'retreat'])
            ->where(fn ($query) => $query->where('before->phase', $phase->slug)->orWhere('after->phase', $phase->slug))
            ->with('user:id,name')->latest('created_at')->paginate(15, ['*'], 'phase_events_page')->withQueryString();
        $phases = ManualPhase::query()->orderBy('position')->get();
        $phaseEvents->getCollection()->transform(fn ($event) => [
            'label' => ManualDossier::EVENT_LABELS[$event->action], 'recorded_at' => $event->created_at,
            'actor' => $event->user?->name ?? 'Usuario no disponible',
            'changes' => app(ManualDossier::class)->eventChanges($event->before ?? [], $event->after ?? [], $phases),
        ]);

        return view('manuals.phase-note', [
            'manual' => $manual, 'phase' => $phase, 'note' => $note, 'audits' => $audits,
            'canEdit' => $notes->canEdit(request()->user(), $phase), 'activities' => $activities, 'phaseEvents' => $phaseEvents,
        ]);
    }

    public function update(Request $request, int $manualId, int $phaseId, ManualPhaseNotes $notes)
    {
        $notes->save($manualId, $phaseId, $request->user(), $request->input('lock_version'), $request->input('note'));

        return redirect()->route('manuals.phase-notes.show', [$manualId, $phaseId])->with('success', 'Nota de fase guardada.');
    }
}
