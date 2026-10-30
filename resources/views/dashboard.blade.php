@extends('layouts.artemis')
@section('title', 'Panel general')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Situación actual</div><h1>Control de generación de doctrina</h1><p class="page-subtitle">Manuales activos por fase y mapa. Los publicados se cuentan por separado.</p></div><a class="btn btn-outline-success" href="{{ route('reports.manuals',request()->except('page')) }}"><i class="fas fa-download" aria-hidden="true"></i> Exportar selección a PDF</a></div>
@include('partials.dashboard-tabs')
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
@php
    $baseFilters = request()->except('page');
    $chartRows = [
        'situation' => [
            ['key' => 'process', 'label' => 'En proceso', 'value' => (int) $inProcess, 'color' => '#29445C', 'url' => route('dashboard', array_merge($baseFilters, ['phase' => 'process']))],
            ['key' => 'published', 'label' => 'Publicados', 'value' => (int) $published, 'color' => '#00A7B5', 'url' => route('dashboard', array_merge($baseFilters, ['phase' => 'published']))],
        ],
        'classification' => collect(App\Models\Manual::CLASSIFICATIONS + ['unknown' => 'Sin información'])->map(fn ($label, $key) => [
            'key' => $key, 'label' => $label, 'value' => (int) ($classificationCounts[$key] ?? 0),
            'color' => ['generated' => '#29445C', 'update' => '#00A7B5', 'unknown' => '#D8A64B'][$key],
            'url' => route('dashboard', array_merge($baseFilters, ['classification' => $key])),
        ])->values()->all(),
        'phases' => $phases->map(fn ($phase, $index) => [
            'key' => $phase->slug, 'label' => $phase->phase_name, 'value' => (int) ($counts[$phase->slug] ?? 0),
            'color' => ['#29445C', '#00A7B5', '#D8A64B', '#B52C39', '#152438'][$index % 5],
            'url' => route('dashboard', array_merge($baseFilters, ['phase' => $phase->slug])),
        ])->push(['key' => 'published', 'label' => 'Publicados', 'value' => (int) $published, 'color' => '#697B8C', 'url' => route('dashboard', array_merge($baseFilters, ['phase' => 'published']))])->values()->all(),
        'maps' => $maps->map(fn ($map, $index) => [
            'key' => (string) $map->id, 'label' => $map->name, 'value' => (int) ($mapCounts[$map->id] ?? 0),
            'color' => ['#29445C', '#00A7B5', '#D8A64B', '#B52C39', '#697B8C'][$index % 5],
            'url' => route('dashboard', array_merge($baseFilters, ['map' => $map->id, 'type' => ''])),
        ])->values()->all(),
    ];
@endphp
<section class="dashboard-analytics" aria-label="Gráficos de situación actual">
    <div class="analytics-grid">
        <article class="panel chart-panel"><div class="panel-heading"><div><h2>Situación actual</h2><p>En proceso frente a publicados</p></div><span class="badge-soft">{{ $total }} en la selección</span></div><div class="chart-content"><div class="donut-chart" data-donut="situation" aria-label="Gráfico de situación actual"></div><ul class="chart-legend">@foreach($chartRows['situation'] as $item)<li><a href="{{ $item['url'] }}"><span class="legend-swatch" style="--chart-color:{{ $item['color'] }}"></span><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong><small>{{ $total ? number_format(100*$item['value']/$total,1,',','.') : '0,0' }} %</small></a></li>@endforeach</ul></div><p class="chart-note">Denominador: {{ $total }} manuales activos; las publicaciones futuras se cuentan una sola vez.</p></article>
        <article class="panel chart-panel"><div class="panel-heading"><div><h2>Clasificación</h2><p>Incluye registros sin información</p></div><span class="badge-soft">{{ $total }} en la selección</span></div><div class="chart-content"><div class="donut-chart" data-donut="classification" aria-label="Gráfico de clasificación"></div><ul class="chart-legend">@foreach($chartRows['classification'] as $item)<li><a href="{{ $item['url'] }}"><span class="legend-swatch" style="--chart-color:{{ $item['color'] }}"></span><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong><small>{{ $total ? number_format(100*$item['value']/$total,1,',','.') : '0,0' }} %</small></a></li>@endforeach</ul></div><p class="chart-note">La clasificación desconocida permanece visible y no se completa automáticamente.</p></article>
    </div>
    <div class="analytics-grid analytics-grid-wide">
        @foreach(['phases' => 'Manuales por fase', 'maps' => 'Manuales por mapa'] as $series => $heading)
        <article class="panel chart-panel"><div class="panel-heading"><div><h2>{{ $heading }}</h2><p>{{ $series === 'phases' ? 'Publicados se muestran como categoría independiente' : 'Selecciona una barra para filtrar el mapa' }}</p></div><span class="badge-soft">{{ $total }} en la selección</span></div><div class="bar-chart" role="list" aria-label="{{ $heading }}">
        @foreach($chartRows[$series] as $item)@php($percent=$total ? 100*$item['value']/$total : 0)
        <a class="chart-bar-row" role="listitem" href="{{ $item['url'] }}" aria-label="{{ $item['label'] }}: {{ $item['value'] }} de {{ $total }} ({{ number_format($percent,1,',','.') }} por ciento)"><span class="chart-bar-label">{{ $item['label'] }}</span><span class="chart-bar-track"><span class="chart-bar-fill" style="--bar-width:{{ $percent }}%;--chart-color:{{ $item['color'] }}"></span></span><strong>{{ $item['value'] }}</strong><small>{{ number_format($percent,1,',','.') }} %</small></a>
        @endforeach
        </div><p class="chart-note">Barras y valores son enlaces accesibles; al filtrar se conservan los demás criterios.</p></article>
        @endforeach
    </div>
    <script type="application/json" id="dashboard-chart-data">@json($chartRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
</section>
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
@push('scripts')<script src="{{ asset('js/dashboard-charts.js') }}?v={{ filemtime(public_path('js/dashboard-charts.js')) }}" defer></script>@endpush
