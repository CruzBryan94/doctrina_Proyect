@extends('layouts.artemis')
@section('title', 'Duraciones por fase')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Administración</div><h1>Duración global por fase</h1><p class="page-subtitle">El cambio recalcula alertas y proyecciones de todos los manuales en proceso. No modifica fechas reales ni seguimiento.</p></div></div>
<div class="panel"><div class="table-responsive"><table class="data-table"><thead><tr><th>Fase</th><th>Duración configurada</th><th>Acción</th></tr></thead><tbody>
@foreach($phases as $phase)<tr><td><strong>{{ $phase->phase_name }}</strong></td><td><form class="phase-duration-form" method="POST" action="{{ route('phase-durations.update', $phase->id) }}">@csrf @method('PUT')<input type="hidden" name="old_months" value="{{ $values[$phase->id] ?? 3 }}"><label class="visually-hidden" for="duration-{{ $phase->id }}">Duración de {{ $phase->phase_name }} en meses</label><input class="form-control phase-duration-input" id="duration-{{ $phase->id }}" name="duration_months" type="number" min="1" max="120" step="1" required value="{{ $values[$phase->id] ?? 3 }}"><span>meses (1–120)</span><button class="btn btn-primary" type="submit">Guardar</button></form></td><td>Alerta al superar esta duración; Planificación no genera alerta.</td></tr>@endforeach
</tbody></table></div></div>
@endsection
