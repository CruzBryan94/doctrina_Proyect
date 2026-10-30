@php($pickerId=$kind.'-'.$committee->id)
<div class="assignment-picker" data-kind="{{ $kind }}" data-field="{{ $kind }}[{{ $committee->id }}][]" data-url="{{ $manual->exists ? route('manuals.assignmentOptions',$manual->id) : route('manuals.newAssignmentOptions',['entry_phase'=>$entryPhase]) }}">
    <label class="visually-hidden" for="search-{{ $pickerId }}">{{ $kind === 'members' ? 'Buscar integrantes' : 'Buscar unidades' }} de {{ $committee->name }}</label>
    <div class="assignment-search">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input id="search-{{ $pickerId }}" type="search" maxlength="100" autocomplete="off" class="assignment-query" placeholder="{{ $kind === 'members' ? 'Buscar por cédula, apellido o nombre…' : 'Buscar por código o nombre de la unidad…' }}" aria-controls="results-{{ $pickerId }}">
        <button type="button" class="assignment-clear" aria-label="Limpiar búsqueda" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
    </div>
    <div class="assignment-selected" aria-label="Selecciones de {{ $committee->name }}">
    @foreach($options as $option)
        @php($optionLabel=$kind === 'members' ? ($option->grade?->grade_name ?? 'Sin grado').' · '.$option->full_name : $option->unit_acronym.' · '.$option->unit_name)
        <span class="assignment-chip" data-id="{{ $option->id }}" data-label="{{ $optionLabel }}">
            <input type="hidden" name="{{ $kind }}[{{ $committee->id }}][]" value="{{ $option->id }}">
            <span>{{ $optionLabel }}</span><button type="button" aria-label="Retirar {{ $optionLabel }}">×</button>
        </span>
    @endforeach
    </div>
    <div class="assignment-results check-list" id="results-{{ $pickerId }}" role="group" aria-label="{{ $kind === 'members' ? 'Integrantes' : 'Unidades' }} de {{ $committee->name }}"></div>
    <div class="assignment-footer"><span class="assignment-state" role="status" aria-live="polite"></span><button type="button" class="btn btn-sm btn-light assignment-more" hidden>Mostrar más</button></div>
    <noscript><p class="form-text">Activa JavaScript para buscar y modificar las asignaciones. Las selecciones actuales se conservarán.</p></noscript>
</div>
