<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
@page{margin:26px}body{font-family:DejaVu Sans,Arial,sans-serif;font-size:8px;color:#152438}h1{font-size:17px;margin:0}h2{font-size:11px}table{width:100%;border-collapse:collapse;margin:10px 0}th{background:#29445C;color:#fff;text-align:left;padding:7px}td{padding:6px;border-bottom:1px solid #D9E2EA;vertical-align:top}tr{page-break-inside:avoid}.muted{font-size:7px;color:#29445C}.status-banner{background:#FBF5E8;padding:9px;border:1px solid #D8A64B;font-size:8px}footer{position:fixed;bottom:-12px;font-size:7px}
</style></head><body>
<h1>ARTEMIS · Productividad histórica</h1>
<p>{{ now()->timezone('America/Guayaquil')->format('d/m/Y H:i') }} · America/Guayaquil</p>
<p><strong>Período:</strong> {{ $period['from'] }} a {{ $period['to'] }}. <strong>Comparación:</strong> {{ $period['previous_from'] }} a {{ $period['previous_to'] }}.</p>
<p class="muted">Nombre: {{ $filters['q'] ?? 'Todos' }} · Mapa: {{ empty($filters['map']) ? 'Todos' : ($maps->firstWhere('id',(int)$filters['map'])?->name ?? 'Sin coincidencias') }} · Tipo: {{ empty($filters['type']) ? 'Todos' : ($maps->flatMap->manualTypes->firstWhere('id',(int)$filters['type'])?->code ?? 'Sin coincidencias') }} · Clasificación: {{ App\Models\Manual::CLASSIFICATIONS[$filters['classification'] ?? ''] ?? (($filters['classification'] ?? '') === 'unknown' ? 'Sin información' : 'Todas') }}.</p>
@include('partials.productivity-coverage')
<table><thead><tr><th>Indicador</th><th>Período seleccionado</th><th>Año anterior, mismo período</th><th>Diferencia</th></tr></thead><tbody>
<tr><td>Publicaciones por fecha declarada</td><td>{{ $summary['publications'] }}</td><td>{{ $comparison['publications'] }}</td><td>{{ $summary['publications']-$comparison['publications'] }}</td></tr>
<tr><td>Manuales enviados a CGFT</td><td>{{ $summary['cgft'] }}</td><td>{{ $comparison['cgft'] }}</td><td>{{ $summary['cgft']-$comparison['cgft'] }}</td></tr>
@foreach($phases as $phase)
<tr><td>Permanencia en {{ $phase->phase_name }}</td><td>{{ $summary['phases'][$phase->slug] ?? 0 }}</td><td>{{ $comparison['phases'][$phase->slug] ?? 0 }}</td><td>{{ ($summary['phases'][$phase->slug] ?? 0)-($comparison['phases'][$phase->slug] ?? 0) }}</td></tr>
@endforeach
</tbody></table>
<p class="muted">Una vez por manual/fase/período; no sumar fases como manuales diferentes. Publicaciones futuras por fecha declarada; permanencias acreditadas hasta hoy. No se usan fechas de digitación como fechas efectivas.</p>
<h2>{{ $metric === 'publications' ? 'Publicaciones' : ($metric === 'cgft' ? 'Envíos a CGFT' : 'Permanencia en '.$phases->firstWhere('slug',$selectedPhase)?->phase_name) }} · {{ $manuals->count() }} manuales distintos</h2>
<table><thead><tr><th>Manual / mapa</th><th>Tipo / clasificación</th><th>{{ $metric === 'publications' ? 'Publicación declarada' : 'Primera entrada acreditada de las visitas coincidentes' }}</th><th>Evidencia</th></tr></thead><tbody>
@forelse($manuals as $manual)
<tr><td>{{ $manual->manual_name }}<div class="muted">{{ $manual->map_name }}</div></td><td>{{ $manual->type_code }} · {{ App\Models\Manual::CLASSIFICATIONS[$manual->classification] ?? 'Sin información' }}</td><td>{{ $manual->effective_on }} {{ $metric === 'publications' && $manual->effective_on > today()->toDateString() ? '· Fecha futura declarada' : '' }}</td><td>{{ $metric === 'publications' ? 'Fecha vigente de publicación' : $manual->matching_visits.' visitas; cuenta una vez' }}</td></tr>
@empty
<tr><td colspan="4">No hay evidencia fechada para esta selección; consultar cobertura.</td></tr>
@endforelse
</tbody></table><footer>ARTEMIS · Datos vigentes y evidencia fechada disponible. Sin reconstrucción de antecedentes desconocidos.</footer></body></html>
