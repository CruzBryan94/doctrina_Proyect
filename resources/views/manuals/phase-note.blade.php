@extends('layouts.artemis')
@section('title', 'Nota de fase · '.$phase->phase_name)
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">ARTEMIS · Contexto de fase</div><h1>Nota de {{ $phase->phase_name }}</h1><p class="page-subtitle">{{ $manual->manual_name }} · ART-{{ str_pad($manual->id, 4, '0', STR_PAD_LEFT) }}</p></div>
    <a class="btn btn-light" href="{{ route('manuals.index', ['phase' => $phase->slug, 'q' => $manual->manual_name]) }}">Volver a la matriz</a>
</div>
<section class="panel p-4 mb-4" aria-labelledby="phase-note-heading">
    <h2 id="phase-note-heading">Observación de la fase</h2>
    @if($canEdit)
        <form method="POST" action="{{ route('manuals.phase-notes.update', [$manual->id, $phase->id]) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="lock_version" value="{{ $manual->lock_version }}">
            <label class="form-label" for="phase-note">Nota (máximo 1000 caracteres)</label>
            <textarea class="form-control" id="phase-note" name="note" maxlength="1000" rows="5">{{ old('note', $note) }}</textarea>
            <div class="form-text">Deja el campo vacío y guarda para limpiar la nota.</div>
            <button class="btn btn-primary mt-3" type="submit">Guardar nota</button>
        </form>
    @else
        <p class="mb-0">{{ $note !== null && $note !== '' ? $note : 'No hay una nota registrada para esta fase.' }}</p>
    @endif
</section>
<section class="panel p-4 mb-4" aria-labelledby="phase-activities-heading">
    <h2 id="phase-activities-heading">Actividades registradas en {{ $phase->phase_name }}</h2>
    @forelse($activities as $activity)
        <div class="border-bottom py-2"><strong>{{ $activity->catalogSubphase?->suphase_name ?? 'Actividad histórica' }}</strong> · {{ $activity->is_completed ? 'Cumplida' : 'Pendiente' }} · {{ $activity->completation_date?->format('d/m/Y') ?? 'Sin fecha' }}</div>
    @empty
        <p class="text-muted mb-0">No hay actividades registradas para esta fase.</p>
    @endforelse
</section>
<section class="panel p-4 mb-4" aria-labelledby="phase-events-heading">
    <h2 id="phase-events-heading">Eventos de {{ $phase->phase_name }}</h2>
    @forelse($phaseEvents as $event)
        <article class="border-bottom py-3">
            <strong>{{ $event['label'] }}</strong><div class="small text-muted">{{ $event['recorded_at']->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $event['actor'] }}</div>
            @foreach($event['changes'] as $change)
                <div>{{ $change['label'] }}: {{ $change['before'] }} → {{ $change['after'] }}</div>
            @endforeach
        </article>
    @empty
        <p class="text-muted mb-0">No hay eventos registrados para esta fase.</p>
    @endforelse
    <div class="mt-3">{{ $phaseEvents->links() }}</div>
</section>
<section class="panel p-4" aria-labelledby="phase-note-history-heading">
    <h2 id="phase-note-history-heading">Cambios de esta nota</h2>
    @forelse($audits as $audit)
        <article class="border-bottom py-3">
            <div class="small text-muted">{{ $audit->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $audit->user?->name ?? 'Usuario no disponible' }}</div>
            <div class="row mt-2"><div class="col-md-6"><strong>Antes</strong><p>{{ $audit->before_note ?? 'Sin nota' }}</p></div><div class="col-md-6"><strong>Después</strong><p>{{ $audit->after_note ?? 'Sin nota' }}</p></div></div>
        </article>
    @empty
        <p class="text-muted mb-0">Aún no hay cambios registrados.</p>
    @endforelse
    <div class="mt-3">{{ $audits->links() }}</div>
</section>
@endsection
