@extends('layouts.artemis')
@section('title', 'Panel general')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Situación actual</div><h1>Control de generación de doctrina</h1><p class="page-subtitle">Manuales activos por fase y mapa. Los publicados se cuentan por separado.</p></div><a class="btn btn-outline-success" href="{{ route('reports.manuals',request()->except('page')) }}"><i class="fas fa-download" aria-hidden="true"></i> Exportar selección a PDF</a></div>
<div class="panel">
@include('partials.filters',['action'=>route('dashboard'),'matrix'=>false])
</div>
<div class="metric-grid">
<div class="metric featured"><div class="label">TOTAL EN LA SELECCIÓN</div><div class="value">{{ $total }}</div><span>{{ $inProcess }} en proceso · {{ $published }} publicados</span></div>
@foreach($phases as $phase)
<a class="metric text-decoration-none" href="{{ route('dashboard',array_merge(request()->except('page'),['phase'=>$phase->slug])) }}" aria-label="Consultar {{ $phase->phase_name }} con los filtros actuales"><div class="label">{{ mb_strtoupper($phase->phase_name) }}</div><div class="value">{{ $counts[$phase->slug] }}</div><span>{{ $total ? number_format(100*$counts[$phase->slug]/$total,1,',','.') : '0,0' }} % de la selección</span></a>
@endforeach
<a class="metric text-decoration-none" href="{{ route('dashboard',array_merge(request()->except('page'),['phase'=>'published'])) }}" aria-label="Consultar publicados con los filtros actuales"><div class="label">PUBLICADOS</div><div class="value">{{ $published }}</div><span>{{ $total ? number_format(100*$published/$total,1,',','.') : '0,0' }} % de la selección</span></a>
</div>
@if($overdue)<div class="status-banner"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <strong>{{ $overdue }} manuales de esta selección requieren atención.</strong> Superaron la duración global configurada de su fase o un año calendario desde el inicio de Investigación. Planificación no genera alertas de plazo.</div>@endif
<div class="panel"><div class="panel-heading"><h2>Clasificación de procesos</h2><span class="badge-soft">Mismo alcance de la selección</span></div><div class="panel-body d-flex gap-3 flex-wrap">
@foreach(App\Models\Manual::CLASSIFICATIONS + ['unknown'=>'Sin información'] as $value=>$label)<a href="{{ route('dashboard',array_merge(request()->except('page'),['classification'=>$value])) }}">{{ $label }}: <strong>{{ $classificationCounts[$value] ?? 0 }}</strong></a>@endforeach
</div></div>
<div class="panel"><div class="panel-heading"><h2>Distribución por mapa</h2><span class="badge-soft">Porcentaje del total filtrado: {{ $total }}</span></div><div class="panel-body">
@foreach($maps as $map)@php($quantity=(int)($mapCounts[$map->id] ?? 0))
<div class="mb-3"><a href="{{ route('dashboard',array_merge(request()->except('page'),['map'=>$map->id,'type'=>''])) }}">{{ $map->name }} · <strong>{{ $quantity }}</strong> ({{ $total ? number_format(100*$quantity/$total,1,',','.') : '0,0' }} %)</a><div class="progress-track" role="progressbar" aria-label="{{ $map->name }}" aria-valuemin="0" aria-valuemax="{{ $total ?: 1 }}" aria-valuenow="{{ $quantity }}"><span style="width:{{ $total ? 100*$quantity/$total : 0 }}%"></span></div></div>
@endforeach
</div></div>
<div class="panel"><div class="panel-heading"><h2>Consolidado de manuales</h2><span class="badge-soft">{{ $total }} activos en la selección · {{ now()->format('d/m/Y') }}</span></div>
<div class="table-responsive"><table class="data-table"><thead><tr><th>Manual / mapa</th><th>Tipo</th><th>Fase</th><th>Actividad pendiente</th><th>Avance de fase</th><th>Tiempo total</th><th>Tiempo en fase</th></tr></thead><tbody>
@forelse($manuals as $manual)@php($time=$manual->timing())
<tr><td><div class="manual-title">@can('view',$manual)<a href="{{ route('manuals.show',$manual->id) }}">{{ $manual->manual_name }}</a>@else{{ $manual->manual_name }}@endcan</div><span class="manual-meta">{{ $manual->manualType->doctrinalMap->name }} · {{ App\Models\Manual::CLASSIFICATIONS[$manual->classification] ?? 'Sin información de clasificación' }}</span></td><td><span class="type-pill" title="{{ $manual->manualType->type_name }}">{{ $manual->manualType->code }}</span></td><td><span class="badge-soft">{{ $manual->is_published ? 'Publicado' : $manual->manualPhase->phase_name }}</span>@php($substate=$manual->currentSubstate())@if(!$manual->is_published && $substate)<span class="manual-substate substate-{{ $substate['code'] }}">{{ $substate['label'] }}</span>@endif</td><td>{{ $manual->is_published ? 'Publicado' : $manual->currentActivity() }}</td><td>
@php($hasNoPriorTracking=$manual->is_published && $manual->manualPhaseSuphases->isEmpty())
@if($hasNoPriorTracking)
<span class="manual-meta">Sin seguimiento previo</span>
@else
<strong>{{ number_format($manual->progress(),2,',','.') }} %</strong><div class="progress-track"><span style="width:{{ $manual->progress() }}%"></span></div>
@endif
</td><td>{{ $time['total_days'] === null ? 'Sin inicio conocido' : $time['total_days'].' días' }} @if($time['research_overdue'])<span class="warning-label">Más de un año calendario desde Investigación</span>@endif</td><td class="{{ $time['phase_overdue'] ? 'overdue' : '' }}">{{ $time['phase_days']===null ? 'Sin inicio registrado' : $time['phase_days'].' días' }} @if($time['phase_overdue'])<span class="warning-label">Más de {{ $time['phase_duration_months'] }} meses en esta fase</span>@endif @if($manual->is_published)<span class="manual-meta">{{ $manual->published_on?->isFuture() ? 'Publicación futura declarada' : 'Fecha declarada' }}: {{ $manual->published_on?->format('d/m/Y') ?? 'Sin información' }}{{ $manual->published_on?->isFuture() ? ' · tiempos hasta hoy' : '' }}
</span>
@endif</td></tr>
@empty<tr><td colspan="7" class="empty-state">No hay manuales para estos filtros.</td></tr>@endforelse
</tbody></table></div><div class="panel-body">{{ $manuals->links() }}</div></div>
@endsection
