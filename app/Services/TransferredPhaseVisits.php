<?php

namespace App\Services;

use App\Models\ManualPhase;
use App\Models\ManualPhaseVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TransferredPhaseVisits
{
    public function paginate(ManualPhase $phase, Request $request)
    {
        return ManualPhaseVisit::query()
            ->where('manual_phase_id', $phase->id)
            ->whereNotNull('exit_event_id')
            ->whereHas('manual', function (Builder $manual) use ($request) {
                $manual->where('is_active', true)
                    ->where('is_published', $request->boolean('published'))
                    ->when($request->filled('q'), fn ($query) => $query->where('manual_name', 'like', '%'.$request->string('q').'%'))
                    ->when($request->filled('map'), fn ($query) => $query->whereHas('manualType', fn ($type) => $type->where('doctrinal_map_id', $request->integer('map'))))
                    ->when($request->filled('type'), fn ($query) => $query->where('manual_types_id', $request->integer('type')))
                    ->when($request->filled('classification'), fn ($query) => $request->query('classification') === 'unknown'
                        ? $query->whereNull('classification') : $query->where('classification', $request->query('classification')));
            })
            ->with([
                'phase:id,phase_name,slug',
                'manual:id,manual_name,manual_types_id,manual_phases_id,classification,is_active',
                'manual.manualType:id,type_name,code,doctrinal_map_id',
                'manual.manualType.doctrinalMap:id,name',
                'entryEvent:id,action,created_at', 'exitEvent:id,action,created_at',
            ])
            ->orderByDesc('exited_recorded_at')->orderByDesc('id')
            ->paginate(15, ['*'], 'transferred_page')->withQueryString();
    }
}
