<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualRequest;
use App\Models\CommitteeMember;
use App\Models\CommitteeType;
use App\Models\DoctrinalMap;
use App\Models\Manual;
use App\Models\ManualCommitteeMember;
use App\Models\ManualMilitaryUnit;
use App\Models\ManualPhase;
use App\Models\ManualPhaseState;
use App\Models\MilitaryUnit;
use App\Services\ManualWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ManualController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Manual::class);
        $phases = ManualPhase::whereIn('slug', $request->user()->accessiblePhaseSlugs())->orderBy('position')->get();
        $slug = $request->query('phase', $phases->first()?->slug);
        $phase = $phases->firstWhere('slug', $slug);
        abort_unless($phase, 403);
        $query = Manual::with(ManualWorkflow::RELATIONS)->where('manual_phases_id', $phase->id)->where('is_active', true);
        $query->where('is_published', $request->boolean('published'));
        $this->filters($query, $request);
        $manuals = $query->orderBy('manual_name')->paginate(20)->withQueryString();
        $activities = $phase->catalogSubphases;
        $maps = DoctrinalMap::with('manualTypes')->get();

        return view('manuals.matrix', compact('manuals', 'phase', 'phases', 'activities', 'maps'));
    }

    public function filters($query, Request $request): void
    {
        $query->when($request->filled('q'), fn ($q) => $q->where('manual_name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('map'), fn ($q) => $q->whereHas('manualType', fn ($q) => $q->where('doctrinal_map_id', $request->integer('map'))))
            ->when($request->filled('type'), fn ($q) => $q->where('manual_types_id', $request->integer('type')));
    }

    public function create()
    {
        $this->authorize('create', Manual::class);

        return $this->form(new Manual);
    }

    public function edit(int $id)
    {
        $manual = Manual::with(ManualWorkflow::RELATIONS)->findOrFail($id);
        $this->authorize('update', $manual);

        return $this->form($manual);
    }

    private function form(Manual $manual)
    {
        $returnTo = $this->returnTo($manual);
        $maps = DoctrinalMap::with('manualTypes')->get();
        $members = CommitteeMember::with('grade')->orderBy('full_name')->get();
        $units = MilitaryUnit::orderBy('unit_name')->get();
        $slug = $manual->exists ? $manual->manualPhase->slug : 'planning';
        $committeeTypes = CommitteeType::whereIn('code', match ($slug) {
            'research' => [1, 2], 'experimentation' => [3, 4], default => []
        })->get();

        return view('manuals.form', compact('manual', 'maps', 'members', 'units', 'committeeTypes', 'returnTo'));
    }

    private function returnTo(Manual $manual): string
    {
        $value = request()->input('return_to');
        if (is_string($value) && preg_match('~^/manuals(?:\?|$)~', $value)) {
            return $value;
        }

        return route('manuals.index', $manual->exists ? ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0] : []);
    }

    public function store(ManualRequest $request, ManualWorkflow $workflow)
    {
        $this->authorize('create', Manual::class);
        $data = $request->validated();
        DB::transaction(function () use ($data, $workflow) {
            $phase = ManualPhase::where('slug', 'planning')->firstOrFail();
            $manual = Manual::create(collect($data)->only(['manual_name', 'manual_types_id', 'observations', 'research_advances', 'experimentation_advances', 'process_started_on'])->all() + [
                'manual_phases_id' => $phase->id, 'is_active' => true, 'is_published' => false,
            ]);
            $workflow->configure($manual);
            ManualPhaseState::where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->update(['started_on' => $data['process_started_on']]);
            $workflow->event($manual, 'created', [], $manual->toArray(), 'Creación en Planificación');
        });

        return redirect()->route('manuals.index', ['phase' => 'planning'])->with('success', 'Manual creado en Planificación, con 0 % de avance.');
    }

    public function update(ManualRequest $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validated();
        $manual = DB::transaction(function () use ($data, $id, $workflow) {
            $manual = $workflow->lock($id, $data['version']);
            $before = $manual->toArray();
            foreach (['research' => 'research_advances', 'experimentation' => 'experimentation_advances'] as $slug => $field) {
                $workflow->resize($manual, $slug, (int) $data[$field]);
            }
            $manual->fill(collect($data)->only(['manual_name', 'manual_types_id', 'observations'])->all());
            $slug = $manual->manualPhase->slug;
            $allowed = match ($slug) {
                'research' => [1, 2], 'experimentation' => [3, 4], default => []
            };
            foreach (['members' => [ManualCommitteeMember::class, 'committee_members_id'], 'units' => [ManualMilitaryUnit::class, 'military_units_id']] as $key => [$class, $foreign]) {
                foreach ($data[$key] ?? [] as $type => $ids) {
                    abort_unless(in_array((int) $type, $allowed, true), 422, 'Los comités solo se editan en su fase correspondiente.');
                    $query = $class::where('manuals_id', $manual->id)->where('committee_type_id', $type);
                    $current = (clone $query)->pluck($foreign)->all();
                    $query->whereNotIn($foreign, $ids)->delete();
                    foreach (array_diff($ids, $current) as $member) {
                        $class::create(['manuals_id' => $manual->id, 'committee_type_id' => $type, $foreign => $member]);
                    }
                }
            }
            $manual->lock_version++;
            $manual->save();
            $workflow->event($manual, 'edited', $before, $manual->fresh()->load(['manualCommitteeMembers', 'manualMilitaryUnits'])->toArray(), 'Edición de datos y comités');

            return $manual;
        });

        return redirect($this->returnTo($manual))->with('success', 'Manual actualizado. Se conservó el historial de cumplimiento.');
    }

    public function activity(Request $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'action' => ['required', 'in:activity,start,retreat,advance'],
            'activity_id' => ['required_if:action,activity', 'integer'],
            'is_completed' => ['required_if:action,activity', 'boolean'],
            'completation_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'started_on' => ['required_if:action,start', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason' => ['required_if:action,retreat', 'nullable', 'string', 'max:255'],
        ]);
        $manual = $workflow->change($id, $data);
        $canAccess = Gate::allows('update', $manual);

        return response()->json([
            'version' => $manual->lock_version, 'phase' => $manual->manualPhase->slug,
            'phase_name' => $manual->manualPhase->phase_name, 'progress' => $manual->progress(),
            'published' => $manual->is_published, 'timing' => $manual->timing(),
            'activity' => $manual->currentActivity(),
            'can_access' => $canAccess,
            'url' => $canAccess ? route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0]) : route('dashboard'),
        ]);
    }

    public function history(int $id)
    {
        $manual = Manual::with('manualPhase')->findOrFail($id);
        $this->authorize('view', $manual);
        $events = $manual->events()->with('user')->paginate(30);

        return view('manuals.history', compact('manual', 'events'));
    }
}
