<?php

namespace App\Http\Controllers;

use App\Models\DoctrinalMap;
use App\Models\InstitutionalReference;
use App\Models\InstitutionalReferenceAudit;
use App\Services\InstitutionalReferenceService;
use Illuminate\Http\Request;

class InstitutionalReferenceController extends Controller
{
    public function index(Request $request, InstitutionalReferenceService $service)
    {
        abort_unless($request->user()?->is_active && $request->user()->hasRole('admin'), 403);
        $maps = DoctrinalMap::query()->orderBy('id')->get();
        $counts = $service->countsByMap();
        $references = InstitutionalReference::query()->get()->keyBy('doctrinal_map_id');
        $breakdowns = $maps->mapWithKeys(fn ($map) => [$map->id => $service->breakdown($map->id)]);
        $audits = $request->user()->hasRole('admin')
            ? InstitutionalReferenceAudit::with(['map:id,name', 'user:id,name'])->latest('id')->paginate(15)
            : collect();
        return view('references.index', compact('maps', 'counts', 'references', 'breakdowns', 'audits'));
    }

    public function update(Request $request, int $doctrinalMap, InstitutionalReferenceService $service)
    {
        abort_unless($request->user()?->is_active && $request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'expected_total' => ['present', 'nullable', 'integer', 'between:0,1000000'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ]);
        $map = DoctrinalMap::findOrFail($doctrinalMap);
        if (! $service->update($map, $data['expected_total'] === null ? null : (int) $data['expected_total'], (int) $data['lock_version'], $request->user())) {
            abort(409, 'La referencia cambió en otra sesión. Recarga la página antes de guardar.');
        }
        return back()->with('success', 'Referencia institucional guardada.');
    }
}
