@extends('layouts.artemis')
@section('title', 'Conciliación institucional')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">Control estadístico</div><h1>Conciliación institucional</h1>
        <p class="page-subtitle">Compara la referencia registrada con todos los manuales activos del mapa: cinco fases en proceso y publicaciones, incluidas las futuras. Se incluyen Generados y Actualizaciones.</p></div>
</div>
@include('partials.dashboard-tabs')
@foreach($maps as $map)
    @php
        $reference = $references->get($map->id);
        $expected = $reference?->expected_total;
        $actual = $counts[$map->id] ?? 0;
        $difference = $expected === null ? null : $actual - $expected;
    @endphp
    <section class="panel mb-4" aria-labelledby="map-{{ $map->id }}">
        <div class="panel-heading"><h2 id="map-{{ $map->id }}">{{ $map->name }}</h2>
            <p>Registrados activos: <strong>{{ $actual }}</strong> · Referencia: <strong>{{ $expected === null ? 'Sin configurar' : $expected }}</strong>
                · Diferencia (registrados − referencia): <strong>{{ $difference === null ? 'No disponible' : ($difference > 0 ? '+' : '').$difference }}</strong></p></div>
        <div class="table-responsive"><table class="data-table"><thead><tr><th>Origen</th><th>Clasificación</th><th>Cantidad</th></tr></thead><tbody>
        @forelse($breakdowns[$map->id] as $row)
            <tr><td>{{ $row->is_published ? 'Publicado' : $row->phase_name }}</td>
                <td>{{ \App\Models\Manual::CLASSIFICATIONS[$row->classification] ?? 'Sin clasificar' }}</td><td>{{ $row->total }}</td></tr>
        @empty<tr><td colspan="3">No hay manuales activos en este mapa.</td></tr>@endforelse
        </tbody></table></div>
        <p class="mt-2"><a href="{{ route('dashboard', ['map' => $map->id]) }}">Ver registros de origen en el dashboard</a></p>
        @if(auth()->user()->hasRole('admin'))
            <div class="d-flex flex-wrap align-items-end gap-2 mt-3">
            <form class="d-flex flex-wrap align-items-end gap-2" method="POST" action="{{ route('references.update', $map->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="lock_version" value="{{ $reference?->lock_version ?? 0 }}">
                <div><label for="reference-{{ $map->id }}">Total institucional esperado (0–1.000.000)</label>
                    <input class="form-control" id="reference-{{ $map->id }}" name="expected_total" type="number" min="0" max="1000000" step="1" value="{{ $expected }}" placeholder="Sin configurar"></div>
                <button class="btn btn-primary" type="submit">Guardar referencia</button>
            </form>
            @if($expected !== null)<form method="POST" action="{{ route('references.update', $map->id) }}">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ $reference->lock_version }}"><input type="hidden" name="expected_total" value=""><button class="btn btn-outline-secondary" type="submit">Limpiar referencia</button></form>@endif
            </div>
        @endif
    </section>
@endforeach
@if(auth()->user()->hasRole('admin'))
<section class="panel"><h2>Últimos cambios auditados</h2>
    <div class="table-responsive"><table class="data-table"><thead><tr><th>Fecha</th><th>Mapa</th><th>Usuario</th><th>Antes</th><th>Después</th></tr></thead><tbody>
    @forelse($audits as $audit)<tr><td>{{ $audit->created_at?->format('d/m/Y H:i') }}</td><td>{{ $audit->map?->name }}</td><td>{{ $audit->user?->name ?? 'Cuenta eliminada' }}</td><td>{{ $audit->before_total ?? 'Sin configurar' }}</td><td>{{ $audit->after_total ?? 'Sin configurar' }}</td></tr>
    @empty<tr><td colspan="5">Todavía no hay cambios auditados.</td></tr>@endforelse
    </tbody></table></div>{{ $audits->links() }}
</section>
@endif
@endsection
