@extends('layouts.artemis')
@section('title', 'Papelera de manuales')
@section('content')
<div class="page-heading artemis-page-title">
    <div><div class="eyebrow artemis-page-kicker">ARTEMIS · Manuales</div><h1>Papelera</h1><p class="page-subtitle">Los manuales conservan su estado e historial mientras están aquí. Cada usuario puede recuperarlos durante siete días; administración puede recuperarlos después.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('manuals.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a manuales</a>
</div>
<div class="panel">
    @if($manuals->isEmpty())
        <div class="empty-state"><i class="fas fa-trash-restore" aria-hidden="true"></i><strong>La papelera está vacía</strong><p>Los manuales eliminados que puedes recuperar aparecerán aquí.</p></div>
    @else
        <div class="table-responsive"><table class="data-table"><thead><tr><th>Manual</th><th>Fase</th><th>Eliminado</th><th>Eliminó</th><th>Recuperación</th></tr></thead><tbody>
        @foreach($manuals as $manual)
            @php($deadline = $manual->deleted_at->copy()->addDays(7))
            <tr>
                <td><div class="manual-title">{{ $manual->manual_name }}</div><span class="manual-meta">{{ $manual->manualType->doctrinalMap->name }} · {{ $manual->manualType->type_name }}</span></td>
                <td>{{ $manual->manualPhase->phase_name }}{{ $manual->is_published ? ' · Publicado' : '' }}</td>
                <td>{{ $manual->deleted_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ $manual->deletedBy?->name ?? 'Cuenta no disponible' }}</td>
                <td>
                    @can('restore', $manual)
                        <form method="POST" action="{{ route('manuals.restore', $manual->id) }}" data-confirm-action data-confirm-message="¿Recuperar «{{ $manual->manual_name }}» con su último estado e historial?">
                            @csrf<input type="hidden" name="version" value="{{ $manual->lock_version }}">
                            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-trash-restore" aria-hidden="true"></i> Recuperar</button>
                        </form>
                        @if(auth()->user()->hasRole('admin') && now()->gt($deadline))<small class="d-block mt-1 text-muted">Plazo de usuario vencido</small>@endif
                    @else
                        <span class="text-muted">Plazo vencido · requiere administración</span>
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody></table></div>
        <div class="panel-body">{{ $manuals->links() }}</div>
    @endif
</div>
@endsection
@push('scripts')<script src="{{ asset('js/manual-deletion.js') }}?v={{ filemtime(public_path('js/manual-deletion.js')) }}" defer></script>@endpush
