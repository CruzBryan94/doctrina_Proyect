<?php

namespace App\Http\Controllers;

use App\Models\DoctrinalMap;
use App\Models\ManualPhase;
use App\Services\DashboardQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function data(Request $request, bool $export = false): array
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:250'], 'map' => ['nullable', 'integer'],
            'type' => ['nullable', 'integer'],
            'phase' => ['nullable', 'in:planning,research,experimentation,edition,cgft,published'],
            'classification' => ['nullable', 'in:generated,update,unknown'],
        ]);
        $source = app(DashboardQuery::class);
        $query = $source->query($request);
        $phases = ManualPhase::orderBy('position')->get();
        $summary = $source->summary($query, $phases);
        $listing = (clone $query)->with(DashboardQuery::RELATIONS)->orderBy('manual_phases_id')->orderBy('manual_name')->orderBy('manuals.id');
        $manuals = $export ? $listing->get() : $listing->paginate(20)->withQueryString();
        $maps = DoctrinalMap::with('manualTypes')->get();

        return compact('manuals', 'phases', 'maps') + $summary;
    }

    public function index(Request $request)
    {
        return view('dashboard', $this->data($request));
    }

    public function report(Request $request)
    {
        return Pdf::loadView('report.dashboardPDF', $this->data($request, true))->setPaper('a4', 'landscape')
            ->download('ARTEMIS-manuales-'.now()->format('Ymd-Hi').'.pdf');
    }
}
