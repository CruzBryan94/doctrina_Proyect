@php($editionKey=$activity->activity_key)
@if(in_array($editionKey,[App\Services\EditionActivity::START,App\Services\EditionActivity::REVIEW]))
<label class="edition-label" for="follow-{{ $state->id }}">Seguimiento</label>
<textarea id="follow-{{ $state->id }}" class="edition-field form-control" data-field="follow_up" rows="2" maxlength="500" placeholder="Detalle breve del seguimiento">{{ $state->follow_up }}</textarea>
@endif
@if($editionKey===App\Services\EditionActivity::LAYOUT)
<label class="edition-label" for="activity-start-{{ $state->id }}">Inicio de maquetación</label>
<input id="activity-start-{{ $state->id }}" class="edition-field form-control" data-field="started_on" type="date" max="{{ today()->format('Y-m-d') }}" value="{{ $state->started_on?->format('Y-m-d') }}">
@endif
@if(in_array($editionKey,[App\Services\EditionActivity::REVIEW,App\Services\EditionActivity::LAYOUT]))
<label class="edition-label" for="due-{{ $state->id }}">Fecha máxima de trámite</label>
<input id="due-{{ $state->id }}" class="edition-field form-control" data-field="due_on" type="date" value="{{ $state->due_on?->format('Y-m-d') }}">
@endif
@if($editionKey===App\Services\EditionActivity::LAYOUT)
<label class="edition-label" for="responsible-search-{{ $state->id }}">Buscar responsable por nombre o correo</label>
<input id="responsible-search-{{ $state->id }}" type="search" class="responsible-query form-control" maxlength="100" placeholder="Buscar usuario activo…" data-url="{{ route('manuals.responsibleOptions',$manual->id) }}">
<label class="edition-label" for="responsible-{{ $state->id }}">Responsable</label>
<select id="responsible-{{ $state->id }}" class="edition-field responsible-select form-select" data-field="responsible_user_id"><option value="">Sin asignar</option>@if($state->responsibleUser)<option value="{{ $state->responsible_user_id }}" selected>{{ $state->responsibleUser->name }} · {{ $state->responsibleUser->email }}{{ !$state->responsibleUser->is_active ? ' (inactivo)' : '' }}</option>@endif</select>
<small class="responsible-state" role="status">Busca para consultar hasta 25 usuarios activos.</small>
@endif
<span class="deadline-state">{{ $state->deadlineStatus() }}</span>
