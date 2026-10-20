@extends('layouts.artemis')
@section('title', 'Matriz de '.$phase->phase_name)
@section('content')
<div class="page-heading"><div><div class="eyebrow">Control de avance</div><h1>Matriz de manuales</h1><p class="page-subtitle">Registra el cumplimiento y su fecha en cada actividad. El avance se guarda automáticamente.</p></div>@can('create', App\Models\Manual::class)<a class="btn btn-primary" href="{{ route('manuals.newManual') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo manual</a>@endcan</div>
<nav class="phase-tabs" aria-label="Fases">@foreach($phases as $item)<a href="{{ route('manuals.index', ['phase' => $item->slug]) }}" class="{{ $item->id === $phase->id && !request()->boolean('published') ? 'active' : '' }}">{{ $item->position }}. {{ $item->phase_name }}</a>@endforeach
@if($phases->contains('slug','cgft'))<a class="{{ request()->boolean('published') ? 'active' : '' }}" href="{{ route('manuals.index', ['phase'=>'cgft','published'=>1]) }}"><i class="fas fa-check-circle" aria-hidden="true"></i> Publicados</a>@endif</nav>
<div class="panel">
<div class="panel-heading"><div><h2>{{ request()->boolean('published') ? 'Manuales publicados' : $phase->phase_name }}</h2><small class="text-muted">{{ $manuals->total() }} manuales · {{ $activities->count() }} actividades de referencia</small></div><span class="badge-soft">Guardado por actividad</span></div>
@include('partials.filters', ['action' => route('manuals.index'), 'matrix' => true])
@if($phase->slug !== 'planning')<div class="px-4 py-2 small text-muted">Rojo: más de 3 meses en esta fase. Alerta adicional: más de 1 año desde el inicio de Investigación. Registra el inicio antes de completar actividades.</div>@endif
<div class="matrix-wrap">
<table class="matrix" id="workflow-matrix" data-phase="{{ $phase->slug }}">
<thead><tr><th class="identity sticky-name" scope="col">MANUAL</th><th class="sticky-map" scope="col">MAPA DOCTRINARIO</th><th class="sticky-type" scope="col">TIPO</th>@foreach($activities as $activity)<th scope="col" title="{{ $activity->suphase_name }}">{{ $activity->suphase_name }}</th>@endforeach<th scope="col">AVANCE DE FASE</th><th scope="col">INICIO Y TIEMPOS</th><th scope="col">ACCIONES</th></tr></thead>
<tbody>
@forelse($manuals as $manual)
@php($timing=$manual->timing())
<tr data-manual="{{ $manual->id }}" data-version="{{ $manual->lock_version }}" data-url="{{ route('manuals.activity',$manual->id) }}">
<td class="sticky-name"><div class="manual-title">{{ $manual->manual_name }}</div><div class="manual-meta">ART-{{ str_pad($manual->id,4,'0',STR_PAD_LEFT) }} · {{ $manual->research_advances }} avances INV / {{ $manual->experimentation_advances }} EXP</div><div class="manual-meta row-activity mt-2">{{ $manual->currentActivity() }}</div></td>
<td class="map-label sticky-map">{{ $manual->manualType->doctrinalMap->name }}</td>
<td class="sticky-type"><span class="type-pill" title="{{ $manual->manualType->type_name }}">{{ $manual->manualType->code }}</span></td>
@foreach($activities as $activity)
@php($state=$manual->manualPhaseSuphases->firstWhere('catalog_subphases_id',$activity->id))
@if(!$state)<td class="not-applicable">No aplica</td>@else
<td class="matrix-cell {{ $state->is_completed ? 'done' : '' }}" data-activity="{{ $activity->id }}" data-saved="{{ $state->is_completed ? '1' : '0' }}" data-date="{{ $state->completation_date?->format('Y-m-d') }}">
<input type="checkbox" class="completion-toggle" aria-label="{{ $manual->manual_name }}: {{ $activity->suphase_name }}" @checked($state->is_completed)>
<input type="date" class="completion-date" aria-label="Fecha de {{ $activity->suphase_name }} para {{ $manual->manual_name }}" max="{{ today()->format('Y-m-d') }}" value="{{ $state->completation_date?->format('Y-m-d') }}" @disabled(!$state->is_completed)>
<span class="cell-state" aria-live="polite">{{ $state->is_completed ? 'Guardado' : 'Pendiente' }}</span>
</td>@endif
@endforeach
<td><div class="progress-block"><span class="progress-label">{{ number_format($manual->progress(),2,',','.') }} %</span><div class="progress-track" role="progressbar" aria-label="Avance de {{ $manual->manual_name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $manual->progress() }}"><span style="width:{{ $manual->progress() }}%"></span></div></div></td>
<td class="timing-cell {{ $timing['phase_overdue'] ? 'overdue' : '' }}"><div class="phase-start-container"><label class="manual-meta" for="start-{{ $manual->id }}">Inicio de fase</label><input id="start-{{ $manual->id }}" class="form-control phase-start" type="date" max="{{ today()->format('Y-m-d') }}" value="{{ $manual->currentStart()?->format('Y-m-d') }}"><button class="btn btn-sm btn-outline-secondary save-start mt-1" type="button">Registrar inicio</button></div><div class="timing mt-2"><div>Fase: <strong class="phase-days">{{ $timing['phase_days'] === null ? 'Sin iniciar' : $timing['phase_days'].' días' }}</strong></div><div>Total: <strong class="total-days">{{ $timing['total_days'] }} días</strong></div><span class="warning-label phase-alert" @if(!$timing['phase_overdue']) hidden @endif>Más de 3 meses en la fase</span><span class="warning-label research-alert" @if(!$timing['research_overdue']) hidden @endif>Más de 1 año desde Investigación</span>@if($manual->is_published)<span class="badge-soft">Publicado · tiempos detenidos</span>@endif</div></td>
<td><div class="d-grid gap-2"><a class="btn btn-sm btn-outline-success" href="{{ route('manuals.editOneManual',['id'=>$manual->id,'return_to'=>request()->getRequestUri()]) }}"><i class="fas fa-edit" aria-hidden="true"></i> Editar</a><a class="btn btn-sm btn-light" href="{{ route('manuals.history',$manual->id) }}">Historial</a>@if($phase->slug!=='planning')<button class="btn btn-sm btn-light retreat" type="button">Retroceder</button>@endif<button class="btn btn-sm btn-light advance" type="button" @if($manual->progress()<100 || $phase->slug==='cgft') hidden @endif>Continuar fase</button></div></td>
</tr>
@empty<tr><td colspan="{{ $activities->count()+6 }}"><div class="empty-state"><i class="fas fa-layer-group" aria-hidden="true"></i><strong>No hay manuales en esta selección</strong><p>Cambia los filtros o elige otra fase.</p></div></td></tr>@endforelse
</tbody></table>
</div><div class="panel-body">{{ $manuals->links() }}</div>
</div>
<p class="page-subtitle"><i class="fas fa-info-circle" aria-hidden="true"></i> Cada actividad aplicable tiene el mismo peso. Las celdas «No aplica» no afectan el porcentaje.</p>
@endsection
@push('scripts')<script src="{{ asset('js/matrix.js') }}" defer></script>@endpush

