@extends('layouts.artemis')
@section('title', 'Matriz de '.$phase->phase_name)
@section('content')
@php($nextPhaseName=App\Models\ManualPhase::where('position', '>', $phase->position)->orderBy('position')->value('phase_name'))
@php($boundaryKey=App\Models\ManualPhaseState::VALIDATION_BOUNDARIES[$phase->slug] ?? null)
@php($boundaryPosition=$boundaryKey ? $activities->firstWhere('activity_key',$boundaryKey)?->position : null)
@php($activityGroups=$activities->mapWithKeys(fn($activity)=>[$activity->id=>$boundaryPosition ? ($activity->position <= $boundaryPosition ? 'activity-development' : 'activity-validation') : '']))

<div class="page-heading"><div><div class="eyebrow">Control de avance</div><h1>Matriz de manuales</h1><p class="page-subtitle">Registra el cumplimiento y su fecha en cada actividad. El avance se guarda automáticamente.</p></div>@can('create', App\Models\Manual::class)<a class="btn btn-primary" href="{{ route('manuals.newManual') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo manual</a>@endcan</div>
<nav class="phase-tabs" aria-label="Fases">@foreach($phases as $item)<a href="{{ route('manuals.index', ['phase' => $item->slug]) }}" class="{{ $item->id === $phase->id && !request()->boolean('published') ? 'active' : '' }}">{{ $item->position }}. {{ $item->phase_name }}</a>@endforeach
@if($phases->contains('slug','cgft'))<a class="{{ request()->boolean('published') ? 'active' : '' }}" href="{{ route('manuals.index', ['phase'=>'cgft','published'=>1]) }}"><i class="fas fa-check-circle" aria-hidden="true"></i> Publicados</a>@endif</nav>
<div class="panel">
<div class="matrix-heading">
<div class="matrix-heading-title"><div class="eyebrow">{{ request()->boolean('published') ? 'Archivo de publicaciones' : 'Seguimiento por fase' }}</div><h2>{{ request()->boolean('published') ? 'Manuales publicados' : $phase->phase_name }}</h2><p>Resumen de todos los manuales que coinciden con los filtros.</p></div>
<div class="matrix-summary" aria-label="Resumen de manuales" aria-live="polite">
<div class="matrix-stat matrix-stat-total"><span class="matrix-stat-label">Total de manuales</span><strong data-matrix-count="total">{{ $summary->total }}</strong></div>
@if(in_array($phase->slug,['research','experimentation']))
<div class="matrix-stat matrix-stat-development"><span class="matrix-stat-label">En desarrollo</span><strong data-matrix-count="development">{{ $summary->development }}</strong></div>
<div class="matrix-stat matrix-stat-validation"><span class="matrix-stat-label">En validación</span><strong data-matrix-count="validation">{{ $summary->validation }}</strong></div>
@endif
<div class="matrix-stat matrix-stat-ready"><span class="matrix-stat-label">{{ $phase->slug === 'cgft' ? 'Listos para publicar' : 'Listos para '.$nextPhaseName }}</span><strong data-matrix-count="ready">{{ $summary->ready }}</strong></div>
</div></div>
@include('partials.filters', ['action' => route('manuals.index'), 'matrix' => true])
@if($phase->slug !== 'planning')<div class="px-4 py-2 small text-muted">Rojo: más de 3 meses en esta fase. Alerta adicional: más de 1 año desde el inicio de Investigación. Registra el inicio antes de completar actividades.</div>@endif
<div class="matrix-wrap">
<table class="matrix" id="workflow-matrix" data-phase="{{ $phase->slug }}" data-phase-name="{{ $phase->phase_name }}" data-next-phase="{{ $nextPhaseName }}" data-previous-phase="{{ App\Models\ManualPhase::where('position', '<', $phase->position)->orderByDesc('position')->value('phase_name') }}">
<thead><tr><th class="identity sticky-name" scope="col">MANUAL</th><th class="map-column" scope="col">MAPA DOCTRINARIO</th><th class="type-column" scope="col">TIPO</th>@foreach($activities as $activity)<th class="{{ $activityGroups[$activity->id] }} {{ $phase->slug === 'edition' ? 'edition-heading' : '' }}" scope="col" title="{{ $activity->suphase_name }}">{{ $activity->suphase_name }}@if($activityGroups[$activity->id])<small class="activity-group-label">{{ $activityGroups[$activity->id] === 'activity-development' ? 'Desarrollo' : 'Validación' }}</small>@endif</th>@endforeach<th scope="col">AVANCE DE FASE</th><th scope="col">INICIO Y TIEMPOS</th><th scope="col">ACCIONES</th></tr></thead>
<tbody>
@forelse($manuals as $manual)
@php($timing=$manual->timing())
@php($substate=$manual->currentSubstate())
<tr data-summary-substate="{{ $substate['code'] ?? '' }}" data-summary-ready="{{ !$manual->is_published && $manual->progress() >= 100 ? 1 : 0 }}" data-manual="{{ $manual->id }}" data-version="{{ $manual->lock_version }}" data-url="{{ route('manuals.activity',$manual->id) }}">
<td class="sticky-name"><div class="manual-title">{{ $manual->manual_name }}</div><span class="manual-substate {{ $substate ? 'substate-'.$substate['code'] : '' }}" @if(!$substate) hidden @endif title="{{ $substate ? $substate['label'].' de '.$phase->phase_name.' · Desde '.$substate['started_on'] : '' }}">{{ $substate['label'] ?? '' }}</span><div class="manual-meta">ART-{{ str_pad($manual->id,4,'0',STR_PAD_LEFT) }} · {{ $manual->research_advances }} avances INV / {{ $manual->experimentation_advances }} EXP</div><div class="manual-meta row-activity mt-2">{{ $manual->currentActivity() }}</div></td>
<td class="map-label map-column">{{ $manual->manualType->doctrinalMap->name }}</td>
<td class="type-column"><span class="type-pill" title="{{ $manual->manualType->type_name }}">{{ $manual->manualType->code }}</span></td>
@foreach($activities as $activity)
@php($state=$manual->manualPhaseSuphases->firstWhere('catalog_subphases_id',$activity->id))
@if(!$state)<td class="not-applicable {{ $activityGroups[$activity->id] }}">No aplica</td>@else
<td class="matrix-cell {{ array_key_exists($activity->activity_key,App\Services\EditionActivity::NAMES) ? 'edition-cell' : '' }} {{ $activityGroups[$activity->id] }} {{ $state->is_completed ? 'done' : '' }}" data-activity="{{ $activity->id }}" data-saved="{{ $state->is_completed ? '1' : '0' }}" data-date="{{ $state->completation_date?->format('Y-m-d') }}">
@if(array_key_exists($activity->activity_key,App\Services\EditionActivity::NAMES))@include('partials.edition-fields')@endif
@if($phase->slug === 'edition')<span class="edition-label">Cumplimiento</span>@endif
<input type="checkbox" class="completion-toggle" aria-label="{{ $manual->manual_name }}: {{ $activity->suphase_name }}" @checked($state->is_completed)>
@if($phase->slug === 'edition')<span class="edition-label">Fecha de cumplimiento</span>@endif
<input type="date" class="completion-date" aria-label="Fecha de {{ $activity->suphase_name }} para {{ $manual->manual_name }}" max="{{ today()->format('Y-m-d') }}" value="{{ $state->completation_date?->format('Y-m-d') }}" @disabled(!$state->is_completed)>
<span class="cell-state" aria-live="polite">{{ $state->is_completed ? 'Guardado' : 'Pendiente' }}</span>
</td>@endif
@endforeach
<td><div class="progress-block"><span class="progress-label">{{ number_format($manual->progress(),2,',','.') }} %</span><div class="progress-track" role="progressbar" aria-label="Avance de {{ $manual->manual_name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $manual->progress() }}"><span style="width:{{ $manual->progress() }}%"></span></div></div></td>
<td class="timing-cell {{ $timing['phase_overdue'] ? 'overdue' : '' }}"><div class="phase-start-container"><label class="visually-hidden" for="start-{{ $manual->id }}">Inicio de fase</label><input id="start-{{ $manual->id }}" class="form-control phase-start" type="date" max="{{ today()->format('Y-m-d') }}" value="{{ $manual->currentStart()?->format('Y-m-d') }}"><button class="btn btn-sm btn-outline-secondary save-start" type="button" aria-label="Registrar inicio de fase" title="Registrar inicio de fase"><i class="fas fa-save" aria-hidden="true"></i></button></div><div class="timing mt-2"><div>Fase: <strong class="phase-days">{{ $timing['phase_days'] === null ? 'Sin iniciar' : $timing['phase_days'].' días' }}</strong></div><div>Total: <strong class="total-days">{{ $timing['total_days'] }} días</strong></div><span class="warning-label phase-alert" @if(!$timing['phase_overdue']) hidden @endif>Más de 3 meses en la fase</span><span class="warning-label research-alert" @if(!$timing['research_overdue']) hidden @endif>Más de 1 año desde Investigación</span>@if($manual->is_published)<span class="badge-soft">Publicado · tiempos detenidos</span>@endif</div></td>
<td><div class="matrix-actions"><a class="btn btn-sm btn-outline-success" href="{{ route('manuals.editOneManual',['id'=>$manual->id,'return_to'=>request()->getRequestUri()]) }}"><i class="fas fa-edit" aria-hidden="true"></i> Editar</a><a class="btn btn-sm btn-light" href="{{ route('manuals.history',$manual->id) }}">Historial</a>@if($phase->slug!=='planning')<button class="btn btn-sm btn-light retreat" type="button">Retroceder</button>@endif<button class="btn btn-sm advance" type="button" @disabled($manual->progress()<100 || $manual->is_published) title="Completa todas las actividades y sus fechas para habilitar el cambio">{{ $manual->is_published ? 'Publicado' : ($phase->slug === 'cgft' ? 'Publicar manual' : ($phase->slug === 'edition' ? 'Enviar a CGFT' : 'Cambiar de fase')) }}</button></div></td>
</tr>
@empty<tr><td colspan="{{ $activities->count()+6 }}"><div class="empty-state"><i class="fas fa-layer-group" aria-hidden="true"></i><strong>No hay manuales en esta selección</strong><p>Cambia los filtros o elige otra fase.</p></div></td></tr>@endforelse
</tbody></table>
</div><div class="panel-body">{{ $manuals->links() }}</div>
</div>
<p class="page-subtitle"><i class="fas fa-info-circle" aria-hidden="true"></i> Cada actividad aplicable tiene el mismo peso. Las celdas «No aplica» no afectan el porcentaje.</p>
@endsection
@push('scripts')@if($phase->slug === 'edition')<script src="{{ asset('js/edition-responsibles.js') }}?v={{ filemtime(public_path('js/edition-responsibles.js')) }}" defer></script>@endif<script src="{{ asset('js/matrix.js') }}?v={{ filemtime(public_path('js/matrix.js')) }}" defer></script>@endpush

