<?php

namespace App\Http\Controllers;

use App\Models\ManualPhase;
use App\Services\PhaseDurationService;
use Illuminate\Http\Request;

class PhaseDurationController extends Controller
{
    public function index(PhaseDurationService $durations)
    {
        abort_unless(auth()->user()?->is_active && auth()->user()->hasRole('admin'), 403);
        $phases = ManualPhase::orderBy('position')->get();
        $values = $durations->all();
        return view('phase-durations.index', compact('phases', 'values'));
    }

    public function update(Request $request, int $manualPhase, PhaseDurationService $durations)
    {
        abort_unless($request->user()?->is_active && $request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'duration_months' => ['required', 'integer', 'between:1,120'],
            'old_months' => ['required', 'integer', 'between:1,120'],
        ]);
        $phase = ManualPhase::findOrFail($manualPhase);
        if (! $durations->update($phase, (int) $data['duration_months'], (int) $data['old_months'], $request->user()->id)) {
            abort(409, 'La duración cambió en otra sesión. Recarga la página antes de guardar.');
        }
        return back()->with('success', 'Duración guardada. Todas las proyecciones se recalcularán al consultarlas.');
    }
}
