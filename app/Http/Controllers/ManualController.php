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
use App\Models\User;
use App\Services\ManualWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ManualController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Manual::class);
        $phases = ManualPhase::orderBy('position')->get();
        $slug = $request->query('phase', $phases->first()?->slug);
        $phase = $phases->firstWhere('slug', $slug);
        abort_unless($phase, 403);
        $query = Manual::with(ManualWorkflow::RELATIONS)->where('manual_phases_id', $phase->id)->where('is_active', true);
        $query->where('is_published', $request->boolean('published'));
        $this->filters($query, $request);
        $summaryQuery = (clone $query)->setEagerLoads([])->select(['manuals.id', 'manuals.research_advances', 'manuals.experimentation_advances', 'manuals.is_published'])
            ->withCount([
                'manualPhaseSuphases as phase_activities' => fn ($q) => $q->where('manual_phases_id', $phase->id),
                'manualPhaseSuphases as completed_activities' => fn ($q) => $q->where('manual_phases_id', $phase->id)->completedForWorkflow(),
            ])->withMax(['phaseStates as edition_catalog_version' => fn ($q) => $q->where('manual_phase_id', $phase->id)], 'catalog_version')->withExists([
                'phaseStates as in_development' => fn ($q) => $q->where('manual_phase_id', $phase->id)->where('substate', 'development'),
                'phaseStates as in_validation' => fn ($q) => $q->where('manual_phase_id', $phase->id)->where('substate', 'validation'),
            ]);
        $expected = match ($phase->slug) {
            'research' => '(CASE WHEN research_catalog_version >= 3 THEN 11 ELSE 10 END) + research_advances', 'experimentation' => '6 + experimentation_advances', 'edition' => 'CASE WHEN edition_catalog_version = 1 THEN 6 ELSE 3 END', default => '2',
        };
        $summaryQuery->withMax(['phaseStates as research_catalog_version' => fn ($q) => $q->where('manual_phase_id', $phase->id)], 'catalog_version');
        $summary = DB::query()->fromSub($summaryQuery->toBase(), 'matrix_summary')->selectRaw(
            'COUNT(*) as total, COALESCE(SUM(in_development), 0) as development, COALESCE(SUM(in_validation), 0) as validation, '
            .'COALESCE(SUM(CASE WHEN is_published = 0 AND phase_activities = ('.$expected.') AND completed_activities = ('.$expected.') THEN 1 ELSE 0 END), 0) as ready'
        )->first();
        $manuals = $query->orderBy('manual_name')->paginate(20)->withQueryString();
        $activities = $phase->catalogSubphases;
        $maps = DoctrinalMap::with('manualTypes')->get();
        $transferredVisits = app(\App\Services\TransferredPhaseVisits::class)->paginate($phase, $request);

        return view('manuals.matrix', compact('manuals', 'phase', 'phases', 'activities', 'maps', 'summary', 'transferredVisits'));
    }

    public function filters($query, Request $request): void
    {
        $query->when($request->filled('q'), fn ($q) => $q->where('manual_name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('map'), fn ($q) => $q->whereHas('manualType', fn ($q) => $q->where('doctrinal_map_id', $request->integer('map'))))
            ->when($request->filled('type'), fn ($q) => $q->where('manual_types_id', $request->integer('type')))
            ->when($request->filled('classification'), fn ($q) => $request->query('classification') === 'unknown' ? $q->whereNull('classification') : $q->where('classification', $request->query('classification')));
    }

    public function create()
    {
        $this->authorize('create', Manual::class);

        $entryPhase = request()->query('entry_phase', auth()->user()->accessiblePhaseSlugs()[0] ?? 'published');
        $this->authorize('createInPhase', [Manual::class, $entryPhase]);

        return $this->form(new Manual, $entryPhase);
    }

    public function edit(int $id)
    {
        $manual = Manual::with(ManualWorkflow::RELATIONS)->findOrFail($id);
        $this->authorize('updateMetadata', $manual);

        return $this->form($manual);
    }

    private function form(Manual $manual, ?string $entryPhase = null)
    {
        $returnTo = $this->returnTo($manual);
        $maps = DoctrinalMap::with('manualTypes')->get();
        $entryPhase = $manual->exists ? ($manual->is_published ? 'published' : $manual->manualPhase->slug) : $entryPhase;
        $slug = $entryPhase;
        $entryPhases = ManualPhase::orderBy('position')->get()->filter(fn ($phase) => Gate::allows('createInPhase', [Manual::class, $phase->slug]))->pluck('phase_name', 'slug')->all();
        if (Gate::allows('createPublished', Manual::class)) {
            $entryPhases['published'] = 'Publicado';
        }
        $committeeTypes = CommitteeType::whereIn('code', match ($slug) {
            'research' => [1, 2], 'experimentation' => [3, 4], default => []
        })->get();
        $memberIds = collect(old('members', $manual->manualCommitteeMembers->whereIn('committee_type_id', $committeeTypes->pluck('id'))->pluck('committee_members_id')->all()))->flatten()->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id));
        $unitIds = collect(old('units', $manual->manualMilitaryUnits->whereIn('committee_type_id', $committeeTypes->pluck('id'))->pluck('military_units_id')->all()))->flatten()->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id));
        $members = CommitteeMember::with('grade')->whereIn('id', $memberIds)->orderBy('full_name')->get();
        $units = MilitaryUnit::whereIn('id', $unitIds)->orderBy('unit_name')->get();

        return view('manuals.form', compact('manual', 'maps', 'members', 'units', 'committeeTypes', 'returnTo', 'entryPhase', 'entryPhases'));
    }

    public function newAssignmentOptions(Request $request)
    {
        $phase = $request->query('entry_phase');
        abort_unless(in_array($phase, ['research', 'experimentation'], true), 422);
        $this->authorize('createInPhase', [Manual::class, $phase]);

        return $this->searchAssignments($request);
    }

    public function assignmentOptions(Request $request, int $id)
    {
        $manual = Manual::with('manualPhase')->findOrFail($id);
        $this->authorize('update', $manual);
        abort_unless(in_array($manual->manualPhase->slug, ['research', 'experimentation'], true), 403);

        return $this->searchAssignments($request);
    }

    private function searchAssignments(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:members,units'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'between:1,10000'],
        ]);
        $isMember = $data['kind'] === 'members';
        $query = $isMember ? CommitteeMember::with('grade') : MilitaryUnit::query();
        $columns = $isMember ? ['full_name', 'identification'] : ['unit_name', 'unit_acronym'];
        if ($isMember) {
            app(\App\Services\MemberDirectory::class)->search($query, $data['q'] ?? '');
        }
        foreach ($isMember ? [] : preg_split('/\s+/u', trim($data['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function ($query) use ($columns, $term) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }
        $results = $query->orderBy($isMember ? 'full_name' : 'unit_name')->orderBy('id')->simplePaginate(25);

        return response()->json([
            'items' => $results->getCollection()->map(fn ($item) => [
                'id' => $item->id,
                'label' => $isMember ? ($item->grade?->grade_name ?? 'Sin grado').' · '.$item->full_name : $item->unit_acronym.' · '.$item->unit_name,
                'detail' => $isMember ? 'Cédula: '.$item->identification.($item->cellphone ? ' · Celular: '.$item->cellphone : '') : '',
            ])->values(),
            'more' => $results->hasMorePages(),
        ]);
    }

    public function responsibleOptions(Request $request, int $id)
    {
        $manual = Manual::with('manualPhase')->findOrFail($id);
        $this->authorize('update', $manual);
        abort_unless($manual->manualPhase->slug === 'edition', 403);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = User::where('is_active', true);
        foreach (preg_split('/\s+/u', trim($data['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$word.'%')->orWhere('email', 'like', '%'.$word.'%'));
        }

        return response()->json(['items' => $query->orderBy('name')->orderBy('id')->limit(25)->get(['id', 'name', 'email'])->map(fn ($user) => ['id' => $user->id, 'label' => $user->name.' · '.$user->email])]);
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
        $entryPhase = $data['entry_phase'] ?? 'planning';
        $this->authorize('createInPhase', [Manual::class, $entryPhase]);
        $manual = DB::transaction(function () use ($data, $workflow, $entryPhase) {
            $phase = ManualPhase::where('slug', $entryPhase === 'published' ? 'cgft' : $entryPhase)->firstOrFail();
            $published = $entryPhase === 'published';
            $manual = Manual::create(collect($data)->only(['manual_name', 'manual_types_id', 'observations', 'classification', 'research_advances', 'experimentation_advances', 'process_started_on'])->all() + [
                'manual_phases_id' => $phase->id, 'is_active' => true, 'is_published' => $published,
                'published_on' => $published ? $data['published_on'] : null,
                'research_started_on' => $entryPhase === 'research' ? ($data['phase_started_on'] ?? null) : null,
            ]);
            if (! $published) {
                $workflow->configure($manual, true);
                ManualPhaseState::where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->update(['started_on' => $data['phase_started_on'] ?? ($entryPhase === 'planning' ? ($data['process_started_on'] ?? null) : null)]);
                $this->syncAssignments($manual, $data);
                $manual->load(ManualWorkflow::RELATIONS);
                $workflow->synchronizeSubstates($manual);
            } else {
                abort_if(! empty($data['members']) || ! empty($data['units']), 422, 'El alta de publicados no crea comités anteriores.');
            }
            $workflow->event($manual, 'created', [], $manual->toArray(), $published ? 'Incorporación directa de publicado; antecedentes no acreditados.' : 'Incorporación en '.$phase->phase_name.'; antecedentes previos no acreditados.');

            return $manual;
        });

        return redirect()->route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0])->with('success', $manual->is_published ? 'Manual publicado incorporado.' : 'Manual creado con 0 % en su fase de incorporación.');
    }

    public function update(ManualRequest $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validated();
        $manual = DB::transaction(function () use ($data, $id, $workflow) {
            $manual = $workflow->lock($id, $data['version'], 'updateMetadata');
            $before = $manual->toArray();
            foreach (['research' => 'research_advances', 'experimentation' => 'experimentation_advances'] as $slug => $field) {
                $workflow->resize($manual, $slug, (int) $data[$field]);
            }
            $manual->fill(collect($data)->only(['manual_name', 'manual_types_id', 'observations', 'classification'])->all());
            if ($manual->is_published && array_key_exists('published_on', $data)) {
                $manual->published_on = $data['published_on'];
            }
            $this->syncAssignments($manual, $data);
            $manual->lock_version++;
            $manual->save();
            $workflow->event($manual, 'edited', $before, $manual->fresh()->load(['manualCommitteeMembers', 'manualMilitaryUnits'])->toArray(), 'Edición de datos y comités');

            return $manual;
        });

        return redirect($this->returnTo($manual))->with('success', 'Manual actualizado. Se conservó el historial de cumplimiento.');
    }

    private function syncAssignments(Manual $manual, array $data): void
    {
        $allowed = $manual->is_published ? [] : match ($manual->manualPhase->slug) {
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
    }

    public function activity(Request $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'action' => ['required', 'in:activity,start,retreat,advance'],
            'activity_id' => ['required_if:action,activity', 'integer'],
            'is_completed' => ['required_if:action,activity', 'boolean'],
            'completation_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'started_on' => ['required_if:action,start', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'follow_up' => ['sometimes', 'nullable', 'string', 'max:500'],
            'due_on' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'responsible_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'published_on' => ['sometimes', 'date_format:Y-m-d'],
            'confirmed' => ['exclude_unless:action,retreat,advance', 'required', 'accepted'],
        ]);
        $manual = $workflow->change($id, $data);
        $canAccess = Gate::allows('update', $manual);
        $activityState = isset($data['activity_id']) ? $manual->manualPhaseSuphases->firstWhere('catalog_subphases_id', $data['activity_id']) : null;

        return response()->json([
            'version' => $manual->lock_version, 'phase' => $manual->manualPhase->slug,
            'phase_name' => $manual->manualPhase->phase_name, 'progress' => $manual->progress(),
            'published' => $manual->is_published, 'timing' => $manual->timing(),
            'activity' => $manual->currentActivity(),
            'substate' => $manual->currentSubstate(),
            'activity_state' => $activityState ? [
                'is_completed' => $activityState->is_completed, 'completation_date' => $activityState->completation_date?->toDateString(),
                'follow_up' => $activityState->follow_up, 'started_on' => $activityState->started_on?->toDateString(),
                'due_on' => $activityState->due_on?->toDateString(), 'responsible_user_id' => $activityState->responsible_user_id,
                'deadline_status' => $activityState->deadlineStatus(),
            ] : null,
            'can_access' => $canAccess,
            'url' => $canAccess ? route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0]) : route('dashboard'),
        ]);
    }

    public function history(int $id)
    {
        $manual = Manual::with(['manualPhase', 'legacyActivities.catalogSubphase', 'legacyActivities.responsibleUser'])->findOrFail($id);
        $this->authorize('view', $manual);
        $events = $manual->events()->with('user')->paginate(30);

        return view('manuals.history', compact('manual', 'events'));
    }
}
