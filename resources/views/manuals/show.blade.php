@extends('layouts.artemis')
@section('title', 'Ficha del manual')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">Consulta de manuales</div><h1>{{ $manual->manual_name }}</h1><p class="page-subtitle">Ficha individual · ART-{{ str_pad($manual->id, 4, '0', STR_PAD_LEFT) }}</p></div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light" href="{{ route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0]) }}">Ver matriz de {{ $manual->is_published ? 'publicados' : $manual->manualPhase->phase_name }}</a>
        @can('updateMetadata', $manual)<a class="btn btn-primary" href="{{ route('manuals.editOneManual', ['id' => $manual->id]) }}">Editar datos del manual</a>@endcan
        @if (!$manual->is_published)
            @can('update', $manual)<a class="btn btn-outline-secondary" href="{{ route('manuals.index', ['phase' => $manual->manualPhase->slug, 'q' => $manual->manual_name]) }}">Registrar seguimiento</a>@endcan
        @endif
    </div>
</div>
<nav class="phase-tabs" aria-label="Secciones de la ficha"><a href="#general">Datos generales</a><a href="#seguimiento">Proceso y actividades</a><a href="#participantes">Participantes</a><a href="#permanencias">Permanencias</a><a href="#historial">Historial</a></nav>
<section class="panel" id="general" aria-labelledby="general-heading">
    <div class="panel-heading"><h2 id="general-heading">Datos generales</h2></div>
    <div class="panel-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Estado</dt><dd class="col-sm-9">{{ $manual->is_active ? 'Activo' : 'Inactivo' }} · {{ $manual->is_published ? 'Publicado' : 'En proceso' }}</dd>
            <dt class="col-sm-3">Mapa</dt><dd class="col-sm-9">{{ $manual->manualType?->doctrinalMap?->name ?? 'Sin información' }}</dd>
            <dt class="col-sm-3">Tipo</dt><dd class="col-sm-9">{{ $manual->manualType?->code ?? 'Sin información' }} · {{ $manual->manualType?->type_name ?? '' }}</dd>
            <dt class="col-sm-3">Clasificación</dt><dd class="col-sm-9">{{ App\Models\Manual::CLASSIFICATIONS[$manual->classification] ?? 'Sin clasificar (antecedente desconocido)' }}</dd>
            <dt class="col-sm-3">Código</dt><dd class="col-sm-9">{{ $manual->code ?: 'Sin información' }}</dd>
            <dt class="col-sm-3">Fase actual</dt><dd class="col-sm-9">{{ $manual->is_published ? 'Publicado' : $manual->manualPhase->phase_name }} @if (!$manual->is_published && $manual->currentSubstate()) · {{ $manual->currentSubstate()['label'] }} @endif</dd>
            <dt class="col-sm-3">Actividad actual</dt><dd class="col-sm-9">{{ $manual->is_published ? 'Proceso publicado' : ($manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id)->isNotEmpty() ? $manual->currentActivity() : 'Sin seguimiento acreditado') }}</dd>
            <dt class="col-sm-3">Inicio del proceso</dt><dd class="col-sm-9">{{ $manual->process_started_on?->format('d/m/Y') ?? 'Desconocido' }}</dd>
            <dt class="col-sm-3">Primer inicio conocido de Investigación</dt><dd class="col-sm-9">{{ $manual->research_started_on?->format('d/m/Y') ?? 'Desconocido' }}</dd>
            <dt class="col-sm-3">Fecha declarada de publicación</dt><dd class="col-sm-9">{{ $manual->published_on?->format('d/m/Y') ?? 'Sin publicación registrada' }} @if ($manual->is_published && $manual->published_on?->isFuture()) · Fecha futura declarada; el manual ya está publicado. @endif</dd>
            <dt class="col-sm-3">Observación general</dt><dd class="col-sm-9 text-break">{{ $manual->observations ?: 'Sin observación general' }}</dd>
        </dl>
    </div>
</section>
<section class="panel" id="seguimiento" aria-labelledby="seguimiento-heading">
    <div class="panel-heading"><h2 id="seguimiento-heading">Proceso y actividades</h2></div>
    <div class="panel-body">
        <p class="form-text">Se muestran los datos conservados. Antecedentes no acreditados significa ausencia de evidencia y no incumplimiento. Las actividades futuras son previsiones del catálogo, no inicios reales.</p>
        @foreach ($phaseDetails as $detail)
            <details class="mb-3" @if ($detail['current']) open @endif>
                <summary><strong>{{ $detail['phase']->phase_name }}</strong> · {{ $detail['label'] }} @if ($detail['progress'] !== null) · {{ number_format($detail['progress'], 2, ',', '.') }} % de esta fase @endif</summary>
                @if ($detail['known'])
                    <p class="small mt-2">Inicio registrado: {{ $detail['state']?->started_on?->format('d/m/Y') ?? 'Desconocido' }}. @if ($detail['progress'] !== null) {{ $detail['completed'] }} de {{ $detail['expected'] }} actividades completas. @endif @if ($detail['phase']->slug === 'edition' && $detail['state']?->catalog_version === 1) Catálogo histórico de Edición: seis actividades conservadas. @endif</p>
                @elseif ($detail['future'])
                    <p class="small mt-2">Sin inicio registrado para esta fase futura.</p>
                @else
                    <p class="small mt-2">No hay antecedentes suficientes para acreditar el recorrido por esta fase.</p>
                @endif
                @if ($detail['activities']->isNotEmpty() && ($detail['known'] || $detail['future']))
                    <div class="table-responsive"><table class="data-table">
                        <caption class="visually-hidden">Actividades de {{ $detail['phase']->phase_name }}</caption>
                        <thead><tr><th scope="col">Actividad</th><th scope="col">Estado</th><th scope="col">Cumplimiento</th><th scope="col">Datos de Edición</th></tr></thead>
                        <tbody>@foreach ($detail['activities'] as $activity)
                            <tr><td>{{ $activity->catalogSubphase?->suphase_name ?? 'Actividad sin plantilla disponible' }}</td>
                                <td>{{ $detail['future'] ? 'Prevista' : ($activity->isWorkflowComplete() ? 'Cumplida' : ($activity->is_completed ? 'Cumplimiento con datos incompletos' : 'Pendiente')) }} @if ($activity->deadlineStatus()) · {{ $activity->deadlineStatus() }} @endif</td>
                                <td>{{ $activity->completation_date?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                <td>@if ($detail['phase']->slug === 'edition')
                                    @if ($activity->follow_up)<div class="text-break">{{ $activity->follow_up }}</div>@endif
                                    @if ($activity->started_on)<div>Inicio específico: {{ $activity->started_on->format('d/m/Y') }}</div>@endif
                                    @if ($activity->due_on)<div>Fecha máxima: {{ $activity->due_on->format('d/m/Y') }}</div>@endif
                                    @if ($activity->responsibleUser)<div>Responsable: {{ $activity->responsibleUser->name }}{{ $activity->responsibleUser->is_active ? '' : ' (cuenta inactiva)' }}</div>@endif
                                    @if (!$activity->follow_up && !$activity->started_on && !$activity->due_on && !$activity->responsibleUser) Sin datos adicionales @endif
                                @else — @endif</td>
                            </tr>
                        @endforeach</tbody>
                    </table></div>
                @endif
            </details>
        @endforeach
        @if ($manual->legacyActivities->isNotEmpty())
            <details><summary>Seguimiento archivado conservado ({{ $manual->legacyActivities->count() }} actividades)</summary>
                <p class="form-text">Evidencia anterior conservada; no se suma al avance vigente ni se sustituye su contenido.</p>
                <ul>@foreach ($manual->legacyActivities as $legacy)
                    <li>{{ $legacy->catalogSubphase?->manualPhase?->phase_name }} · {{ $legacy->catalogSubphase?->suphase_name ?? 'Actividad histórica' }} · {{ $legacy->is_completed ? 'Cumplida' : 'Pendiente' }} · {{ $legacy->completation_date?->format('d/m/Y') ?? 'Sin fecha' }} @if ($legacy->responsibleUser) · Responsable: {{ $legacy->responsibleUser->name }} @endif</li>
                @endforeach</ul>
            </details>
        @endif
    </div>
</section>
<section class="panel" id="participantes" aria-labelledby="participantes-heading">
    <div class="panel-heading"><h2 id="participantes-heading">Participantes actualmente registrados</h2></div>
    <div class="panel-body">
        <p class="form-text">Esta composición corresponde a los vínculos conservados actualmente. No acredita la composición de comités en fechas anteriores.</p>
        <div class="row">
        @foreach ($committeeTypes as $type)
            <div class="col-lg-6 mb-3"><h3 class="h6">{{ $type->name }}</h3>
                <strong class="small">Integrantes</strong>
                <ul>@forelse ($manual->manualCommitteeMembers->where('committee_type_id', $type->id) as $assignment)
                    <li>{{ $assignment->committeeMember?->grade?->grade_name ?? 'Sin grado' }} · {{ $assignment->committeeMember?->full_name ?? 'Persona no disponible' }}</li>
                @empty<li>Sin integrantes registrados.</li>@endforelse</ul>
                <strong class="small">Unidades</strong>
                <ul>@forelse ($manual->manualMilitaryUnits->where('committee_type_id', $type->id) as $assignment)
                    <li>{{ $assignment->militaryUnit?->unit_acronym }} · {{ $assignment->militaryUnit?->unit_name ?? 'Unidad no disponible' }}</li>
                @empty<li>Sin unidades registradas (opcionales).</li>@endforelse</ul>
            </div>
        @endforeach
        </div>
    </div>
</section>
<section class="panel" id="permanencias" aria-labelledby="permanencias-heading">
    <div class="panel-heading"><h2 id="permanencias-heading">Permanencias acreditadas</h2></div>
    <div class="panel-body">
        <p class="form-text">Fechas efectivas y fechas de registro se distinguen. Las fechas desconocidas permanecen desconocidas; la ausencia de una permanencia no prueba que una fase se haya completado. Los reingresos se muestran por separado.</p>
        @if (!$visitsAvailable)<p>El registro de permanencias está pendiente de instalación. Se conserva la consulta de seguimiento y eventos.</p>
        @elseif ($visits->isEmpty())<p>Sin permanencias acreditadas para este manual. El seguimiento y los eventos disponibles se muestran por separado.</p>
        @else
            <div class="table-responsive"><table class="data-table"><thead><tr><th scope="col">Fase</th><th scope="col">Entrada efectiva</th><th scope="col">Salida efectiva</th><th scope="col">Registro de entrada/salida</th><th scope="col">Origen</th></tr></thead><tbody>
                @foreach ($visits as $visit)<tr><td>{{ $visit->phase?->phase_name ?? 'Fase no disponible' }}</td><td>{{ $visit->entered_on?->format('d/m/Y') ?? 'Desconocida' }}</td><td>{{ $visit->exited_on?->format('d/m/Y') ?? ($visit->exit_event_id ? 'Salida acreditada, fecha desconocida' : 'Sin salida acreditada') }}</td><td>{{ $visit->entered_recorded_at?->format('d/m/Y H:i') ?? 'Sin registro' }} / {{ $visit->exited_recorded_at?->format('d/m/Y H:i') ?? 'Sin registro' }}</td><td>{{ $visit->source === 'reconstructed' ? 'Reconstruida desde evidencia' : 'Registro de operación' }} @if ($visit->entry_basis === 'unknown') · Entrada efectiva desconocida @endif</td></tr>@endforeach
            </tbody></table></div>
            {{ $visits->fragment('permanencias')->links() }}
        @endif
    </div>
</section>
<section class="panel" id="historial" aria-labelledby="historial-heading">
    <div class="panel-heading"><h2 id="historial-heading">Historial general</h2></div>
    <div class="panel-body">
        <p class="form-text">Cronología por fecha de registro. Los detalles muestran campos generales permitidos; las notas de fase pertenecen exclusivamente a su contexto.</p>
        <div class="timeline">@forelse ($events as $event)
            <article class="timeline-item"><strong>{{ $event['label'] }}</strong><div>{{ $event['recorded_at']->format('d/m/Y H:i') }} · {{ $event['actor'] }}</div>
                @if ($event['changes'])<details><summary>Ver cambios registrados</summary><ul>@foreach ($event['changes'] as $change)<li>{{ $change['label'] }}: {{ $change['before'] }} → {{ $change['after'] }}</li>@endforeach</ul></details>@endif
            </article>
        @empty<p>Sin eventos generales disponibles.</p>@endforelse</div>
        {{ $events->fragment('historial')->links() }}
    </div>
</section>
@endsection
