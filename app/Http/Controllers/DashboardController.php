<?php

namespace App\Http\Controllers;

use App\Models\DoctrinalMap;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Services\ManualWorkflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function data(Request $request): array
    {
        $query = Manual::with(ManualWorkflow::RELATIONS)->where('is_active', true);
        app(ManualController::class)->filters($query, $request);
        if ($request->filled('phase')) {
            $query->whereHas('manualPhase', fn ($q) => $q->where('slug', $request->query('phase')));
        }
        $manuals = $query->orderBy('manual_phases_id')->orderBy('manual_name')->get();
        $phases = ManualPhase::orderBy('position')->get();
        $counts = $phases->mapWithKeys(fn ($phase) => [$phase->slug => $manuals->where('manual_phases_id', $phase->id)->where('is_published', false)->count()]);
        $published = $manuals->where('is_published', true)->count();
        $overdue = $manuals->filter(fn ($manual) => $manual->timing()['phase_overdue'] || $manual->timing()['research_overdue'])->count();
        $maps = DoctrinalMap::with('manualTypes')->get();

        return compact('manuals', 'phases', 'counts', 'published', 'overdue', 'maps');
    }

    public function index(Request $request)
    {
        return view('dashboard', $this->data($request));
    }

    public function report(Request $request)
    {
        return Pdf::loadView('report.dashboardPDF', $this->data($request))->setPaper('a4', 'landscape')
            ->download('ARTEMIS-manuales-'.now()->format('Ymd-Hi').'.pdf');
    }
}
