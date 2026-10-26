<?php

namespace App\Http\Controllers;

use App\Models\DoctrinalMap;
use App\Models\ManualPhase;
use App\Services\ManualProductivity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class HistoricalDashboardController extends Controller
{
    public function data(Request $request, bool $export = false): array
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:1900,2200'],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:250'], 'map' => ['nullable', 'integer'], 'type' => ['nullable', 'integer'],
            'classification' => ['nullable', 'in:generated,update,unknown'],
            'metric' => ['nullable', 'in:publications,cgft,phase'],
            'phase' => ['nullable', 'required_if:metric,phase', 'in:planning,research,experimentation,edition,cgft'],
        ]);
        $source = app(ManualProductivity::class);
        $period = $source->periods($filters);
        $summary = $source->summary($filters, $period['from'], $period['to']);
        $comparison = $source->summary($filters, $period['previous_from'], $period['previous_to']);
        $coverage = $source->coverage($filters);
        $metric = $filters['metric'] ?? 'publications';
        $selectedPhase = $filters['phase'] ?? null;
        $listing = $source->listing($filters, $period['from'], $period['to'], $metric, $selectedPhase);
        $manuals = $export ? $listing->get() : $listing->paginate(25)->withQueryString();
        $maps = DoctrinalMap::with('manualTypes')->get();
        $phases = ManualPhase::orderBy('position')->get();

        return compact('filters', 'period', 'summary', 'comparison', 'coverage', 'metric', 'selectedPhase', 'manuals', 'maps', 'phases');
    }

    public function index(Request $request)
    {
        return view('dashboard-history', $this->data($request));
    }

    public function report(Request $request)
    {
        return Pdf::loadView('report.historyPDF', $this->data($request, true))->setPaper('a4', 'landscape')
            ->download('ARTEMIS-productividad-'.now()->format('Ymd-Hi').'.pdf');
    }
}
