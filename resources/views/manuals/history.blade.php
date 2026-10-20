@extends('layouts.artemis')
@section('title','Historial del manual')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Trazabilidad</div><h1>Historial del manual</h1><p class="page-subtitle">{{ $manual->manual_name }}</p></div><a class="btn btn-light" href="{{ route('manuals.index',['phase'=>$manual->manualPhase->slug,'published'=>$manual->is_published?1:0]) }}">Volver a la matriz</a></div>
<div class="panel"><div class="panel-body"><div class="timeline">@forelse($events as $event)<article class="timeline-item"><strong>{{ ['created'=>'Creación','edited'=>'Datos generales y comités','activity'=>'Cumplimiento de actividad','start'=>'Inicio de fase','advance'=>'Continuación de fase','retreat'=>'Retroceso de fase'][$event->action] ?? $event->action }}</strong><span>{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->user?->name ?? 'Sistema / datos de prueba' }}</span><div>{{ $event->reason }}</div>@if($event->after['phase'] ?? false)<div class="manual-meta">Fase: {{ $event->before['phase'] ?? '—' }} → {{ $event->after['phase'] }}</div>@endif
<details><summary>Ver detalle de cambios</summary><pre class="small text-wrap">{{ json_encode(['antes'=>$event->before,'después'=>$event->after],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details></article>@empty<p>Sin eventos registrados.</p>@endforelse</div>{{ $events->links() }}</div></div>
@endsection

