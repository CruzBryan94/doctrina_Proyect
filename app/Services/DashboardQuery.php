<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardQuery
{
    // Only the displayed/exported rows need workflow data; aggregates never hydrate it.
    public const RELATIONS = [
        'manualType.doctrinalMap', 'manualPhase', 'phaseStates',
        'manualPhaseSuphases.catalogSubphase.manualPhase',
    ];

    public function query(Request $request): Builder
    {
        $query = Manual::query()->where('manuals.is_active', true);
        $query->when($request->filled('q'), fn ($q) => $q->where('manual_name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('map'), fn ($q) => $q->whereHas('manualType', fn ($q) => $q->where('doctrinal_map_id', $request->integer('map'))))
            ->when($request->filled('type'), fn ($q) => $q->where('manual_types_id', $request->integer('type')));
        if ($request->query('classification') === 'unknown') {
            $query->whereNull('classification');
        } elseif ($request->filled('classification')) {
            $query->where('classification', $request->query('classification'));
        }

        if ($request->query('phase') === 'published') {
            $query->where('is_published', true);
        } elseif ($request->filled('phase')) {
            $query->where('is_published', false)
                ->whereHas('manualPhase', fn ($q) => $q->where('slug', $request->query('phase')));
        }

        return $query;
    }

    public function summary(Builder $query, $phases): array
    {
        $groups = (clone $query)->select(['manual_phases_id', 'is_published'])->selectRaw('COUNT(*) as quantity')
            ->groupBy('manual_phases_id', 'is_published')->get();
        $counts = $phases->mapWithKeys(fn ($phase) => [$phase->slug => (int) $groups->where('manual_phases_id', $phase->id)->where('is_published', false)->sum('quantity')]);
        $published = (int) $groups->where('is_published', true)->sum('quantity');
        $total = (int) $groups->sum('quantity');
        $inProcess = $total - $published;
        $classificationCounts = (clone $query)->select('classification')->selectRaw('COUNT(*) as quantity')
            ->groupBy('classification')->get()->mapWithKeys(fn ($row) => [$row->classification ?? 'unknown' => (int) $row->quantity]);
        $mapCounts = (clone $query)->join('manual_types', 'manual_types.id', '=', 'manuals.manual_types_id')
            ->select('manual_types.doctrinal_map_id')->selectRaw('COUNT(*) as quantity')
            ->groupBy('manual_types.doctrinal_map_id')->pluck('quantity', 'doctrinal_map_id');
        $overdue = $this->overdueCount($query);

        return compact('counts', 'published', 'total', 'inProcess', 'mapCounts', 'classificationCounts', 'overdue');
    }

    private function overdueCount(Builder $query): int
    {
        $durations = app(PhaseDurationService::class)->all();
        $rows = (clone $query)->where('is_published', false)
            ->join('manual_phases as alert_phase', 'alert_phase.id', '=', 'manuals.manual_phases_id')
            ->leftJoin('manual_phase_states as alert_state', function ($join) {
                $join->on('alert_state.manual_id', '=', 'manuals.id')->on('alert_state.manual_phase_id', '=', 'manuals.manual_phases_id');
            })->where('alert_phase.slug', '!=', 'planning')
            ->select(['manuals.manual_phases_id', 'alert_state.started_on as alert_started_on', 'manuals.research_started_on'])->toBase()->cursor();

        // Carbon preserves the model's calendar-month/year boundary, including month ends.
        // This cursor reads dates only, without manuals, participants or activity collections.
        $overdue = 0;
        foreach ($rows as $row) {
            $months = $durations[$row->manual_phases_id] ?? 3;
            if (($row->alert_started_on && today()->gt(Carbon::parse($row->alert_started_on)->addMonthsNoOverflow($months)))
                || ($row->research_started_on && today()->gt(Carbon::parse($row->research_started_on)->addYearNoOverflow()))) {
                $overdue++;
            }
        }

        return $overdue;
    }
}
