@extends('layouts.artemis')
@section('title', 'Productividad histórica')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Consulta por fechas efectivas</div><h1>Productividad histórica</h1><p class="page-subtitle">Manuales distintos por fase y período. Los reingresos no duplican el conteo de la fase.</p></div><a class="btn btn-outline-success" href="{{ route('reports.history',request()->except('page')) }}">Exportar selección a PDF</a></div>
@include('partials.dashboard-tabs')
<div class="panel">
<form class="filters" action="{{ route('dashboard.history') }}" method="GET">
<input type="hidden" name="metric" value="{{ $metric }}">
@if($selectedPhase)
<input type="hidden" name="phase" value="{{ $selectedPhase }}">
@endif
<div><label for="history-year">Año</label><input class="form-control" type="number" id="history-year" name="year" min="1900" max="2200" value="{{ $period['year'] }}"></div>
<div><label for="history-from">Desde (opcional)</label><input class="form-control" type="date" id="history-from" name="from" value="{{ request('from') }}"></div>
<div><label for="history-to">Hasta (opcional)</label><input class="form-control" type="date" id="history-to" name="to" value="{{ request('to') }}"></div>
<div><label for="history-q">Nombre del manual</label><input class="form-control" id="history-q" name="q" maxlength="250" value="{{ request('q') }}"></div>
<div><label for="history-map">Mapa</label><select class="form-select" id="history-map" name="map"><option value="">Todos los mapas</option>
@foreach($maps as $map)
<option value="{{ $map->id }}" @selected(request('map')==$map->id)>{{ $map->name }}</option>
@endforeach
</select></div>
<div><label for="history-type">Tipo</label><select class="form-select" id="history-type" name="type"><option value="">Todos los tipos</option>
@foreach($maps as $map)
@foreach($map->manualTypes as $type)
<option value="{{ $type->id }}" @selected(request('type')==$type->id)>{{ $type->code }}</option>
@endforeach
@endforeach
</select></div>
<div><label for="history-classification">Clasificación</label><select class="form-select" id="history-classification" name="classification"><option value="">Todas las clasificaciones</option>
@foreach(App\Models\Manual::CLASSIFICATIONS + ['unknown'=>'Sin información'] as $value=>$label)
<option value="{{ $value }}" @selected(request('classification')===$value)>{{ $label }}</option>
@endforeach
</select></div>
<button class="btn btn-primary" type="submit">Consultar</button><a class="btn btn-light" href="{{ route('dashboard.history') }}">Limpiar</a>
</form>
<div class="panel-body small">Período: <strong>{{ $period['from'] }} a {{ $period['to'] }}</strong>. Comparación: {{ $period['previous_from'] }} a {{ $period['previous_to'] }}. Si defines Desde y Hasta, ese rango reemplaza el año.</div>
</div>
@include('partials.productivity-coverage')
@php($seriesMax = max(1, collect($monthlySeries)->flatMap(fn ($row) => [$row['publications'], $row['cgft']])->max() ?? 0))
<section class="panel historical-chart" aria-labelledby="history-chart-title">
<div class="panel-heading"><div><h2 id="history-chart-title">Publicaciones y envíos a CGFT por mes</h2><p>Publicación según fecha declarada; CGFT según fecha efectiva de transferencia.</p></div><span class="badge-soft">{{ count($monthlySeries) }} meses</span></div>
<div class="panel-body small text-muted">La escala comparte el máximo mensual de esta selección. Un punto sin evidencia no se estima; consulta la cobertura histórica antes de interpretar períodos vacíos.</div>
<div class="monthly-bars" role="list" aria-label="Totales mensuales de publicaciones y transferencias">
@foreach($monthlySeries as $month)
@php($monthFilters = array_merge(request()->except('page','metric','phase','from','to'), ['from'=>$month['from'],'to'=>$month['to']]))
<div class="monthly-bar-group" role="listitem"><strong class="monthly-bar-month">{{ $month['label'] }}@if($month['future_publications'])<small class="future-publication-label">{{ $month['future_publications'] }} con fecha futura</small>@endif</strong><a class="monthly-bar-link" href="{{ route('dashboard.history', array_merge($monthFilters,['metric'=>'publications'])) }}" aria-label="{{ $month['label'] }}: {{ $month['publications'] }} publicaciones por fecha declarada{{ $month['future_publications'] ? '; '.$month['future_publications'].' con fecha futura declarada' : '' }}"><span class="monthly-bar-kind">Publicaciones</span><span class="monthly-bar-track"><span class="monthly-bar-fill publication" style="--bar-width:{{ 100*$month['publications']/$seriesMax }}%"></span></span><strong>{{ $month['publications'] }}</strong></a><a class="monthly-bar-link" href="{{ route('dashboard.history', array_merge($monthFilters,['metric'=>'cgft'])) }}" aria-label="{{ $month['label'] }}: {{ $month['cgft'] }} envíos a CGFT por fecha efectiva"><span class="monthly-bar-kind">Envíos CGFT</span><span class="monthly-bar-track"><span class="monthly-bar-fill transfer" style="--bar-width:{{ 100*$month['cgft']/$seriesMax }}%"></span></span><strong>{{ $month['cgft'] }}</strong></a></div>
@endforeach
</div><div class="chart-legend-inline"><span><i class="legend-swatch publication"></i>Publicaciones</span><span><i class="legend-swatch transfer"></i>Envíos a CGFT</span></div>
<div class="table-responsive"><table class="data-table historical-chart-table"><caption class="visually-hidden">Tabla alternativa de la serie mensual</caption><thead><tr><th>Mes</th><th>Publicaciones</th><th>Fecha futura declarada</th><th>Envíos a CGFT</th></tr></thead><tbody>@foreach($monthlySeries as $month)@php($tableFilters = array_merge(request()->except('page','metric','phase','from','to'), ['from'=>$month['from'],'to'=>$month['to']]))<tr><th scope="row">{{ $month['label'] }}</th><td><a href="{{ route('dashboard.history', array_merge($tableFilters,['metric'=>'publications'])) }}">{{ $month['publications'] }}</a></td><td>{{ $month['future_publications'] }}</td><td><a href="{{ route('dashboard.history', array_merge($tableFilters,['metric'=>'cgft'])) }}">{{ $month['cgft'] }}</a></td></tr>@endforeach</tbody></table></div>
</section>
<div class="panel"><div class="panel-heading"><h2>Comparación de períodos</h2><span class="badge-soft">Alcance: {{ $coverage['total'] }} manuales actualmente activos</span></div>
<div class="table-responsive"><table class="data-table"><thead><tr><th>Indicador</th><th>Período seleccionado</th><th>Mismo período del año anterior</th><th>Diferencia</th></tr></thead><tbody>
<tr><td><a href="{{ route('dashboard.history',array_merge(request()->except('page','phase'),['metric'=>'publications'])) }}">Publicaciones por fecha declarada</a></td><td>{{ $summary['publications'] }}</td><td>{{ $comparison['publications'] }}</td><td>{{ $summary['publications']-$comparison['publications'] }}</td></tr>
<tr><td><a href="{{ route('dashboard.history',array_merge(request()->except('page','phase'),['metric'=>'cgft'])) }}">Manuales enviados a CGFT</a></td><td>{{ $summary['cgft'] }}</td><td>{{ $comparison['cgft'] }}</td><td>{{ $summary['cgft']-$comparison['cgft'] }}</td></tr>
@foreach($phases as $phase)
<tr><td><a href="{{ route('dashboard.history',array_merge(request()->except('page'),['metric'=>'phase','phase'=>$phase->slug])) }}">Permanencia en {{ $phase->phase_name }}</a></td><td>{{ $summary['phases'][$phase->slug] ?? 0 }}</td><td>{{ $comparison['phases'][$phase->slug] ?? 0 }}</td><td>{{ ($summary['phases'][$phase->slug] ?? 0)-($comparison['phases'][$phase->slug] ?? 0) }}</td></tr>
@endforeach
</tbody></table></div><div class="panel-body small text-muted">Un manual puede permanecer en varias fases durante el período; la suma de fases no representa manuales distintos de todo el proceso. Las publicaciones futuras cuentan en su fecha declarada; las permanencias solo se acreditan hasta hoy.</div></div>
<div class="panel"><div class="panel-heading"><h2>{{ $metric === 'publications' ? 'Publicaciones' : ($metric === 'cgft' ? 'Envíos a CGFT' : 'Permanencia en '.$phases->firstWhere('slug',$selectedPhase)?->phase_name) }}</h2><span class="badge-soft">{{ $manuals->total() }} manuales distintos</span></div>
<div class="table-responsive"><table class="data-table"><thead><tr><th>Manual / mapa</th><th>Tipo / clasificación</th><th>{{ $metric === 'publications' ? 'Publicación declarada' : 'Primera entrada acreditada de las visitas coincidentes' }}</th><th>Evidencia en el período</th></tr></thead><tbody>
@forelse($manuals as $manual)
<tr><td><a href="{{ route('manuals.show',$manual->manual_id) }}"><strong>{{ $manual->manual_name }}</strong></a><div class="manual-meta">{{ $manual->map_name }}</div></td><td>{{ $manual->type_code }} · {{ App\Models\Manual::CLASSIFICATIONS[$manual->classification] ?? 'Sin información' }}</td><td>{{ $manual->effective_on }} {{ $metric === 'publications' && $manual->effective_on > today()->toDateString() ? '· Fecha futura declarada' : '' }}</td><td>{{ $metric === 'publications' ? 'Fecha vigente de publicación' : $manual->matching_visits.' visitas acreditadas; cuenta una vez' }}</td></tr>
@empty
<tr><td colspan="4" class="empty-state">No hay evidencia fechada para esta selección. Consulta la cobertura antes de interpretar el cero.</td></tr>
@endforelse
</tbody></table></div><div class="panel-body">{{ $manuals->links() }}</div></div>
@endsection
