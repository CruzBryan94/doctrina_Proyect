<?php

namespace App\Http\Controllers;

use App\Models\DoctrinalMap;
use App\Models\ManualPhase;
use App\Services\DashboardQuery;
use App\Services\ManualProjection;
use App\Services\PhaseDurationService;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    public function index(Request $request, DashboardQuery $dashboard, PhaseDurationService $durations, ManualProjection $projection)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:250'], 'map' => ['nullable', 'integer'],
            'type' => ['nullable', 'integer'], 'classification' => ['nullable', 'in:generated,update,unknown'],
            'phase' => ['nullable', 'in:planning,research,experimentation,edition,cgft'],
            'year' => ['nullable', 'integer', 'between:2000,2200'], 'start_month' => ['nullable', 'integer', 'between:1,12'],
            'months' => ['nullable', 'integer', 'between:1,24'],
        ]);
        $query = $dashboard->query($request)->where('manuals.is_published', false);
        $manuals = $query->with(DashboardQuery::RELATIONS)->orderBy('manuals.manual_phases_id')
            ->orderBy('manuals.manual_name')->orderBy('manuals.id')->paginate(20)->withQueryString();
        $values = $durations->all();
        $year = (int) $request->input('year', today()->year);
        $month = (int) $request->input('start_month', $year === today()->year ? today()->month : 1);
        $months = (int) $request->input('months', 12);
        $rangeStart = today()->copy()->setDate($year, $month, 1)->startOfMonth();
        $calendar = collect(range(0, $months - 1))->map(fn ($i) => $rangeStart->copy()->addMonthsNoOverflow($i));
        $rows = $manuals->getCollection()->map(fn ($manual) => ['manual' => $manual, 'timing' => $manual->timing(),
            'projection' => $projection->build($manual, $values, today()->copy())]);
        $maps = DoctrinalMap::with('manualTypes')->get();
        $phases = ManualPhase::orderBy('position')->get();
        $rangeStart = $calendar->first()->copy()->startOfMonth();
        return view('projection.index', compact('manuals', 'rows', 'calendar', 'year', 'month', 'months', 'maps', 'phases', 'rangeStart'));
    }
}
