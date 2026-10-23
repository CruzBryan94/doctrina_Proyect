@extends('layouts.artemis')
@section('title', $manual->exists ? 'Editar manual' : 'Nuevo manual')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Gestión del manual</div><h1>{{ $manual->exists ? 'Editar manual' : 'Crear nuevo manual' }}</h1><p class="page-subtitle">{{ $manual->exists ? 'Los cambios generales conservan el avance y el historial.' : 'El manual comienza en Planificación, sin actividades cumplidas.' }}</p></div><a href="{{ $returnTo }}" class="btn btn-light">Volver a la matriz</a></div>
<form method="POST" action="{{ $manual->exists ? route('manuals.updateOneManual',$manual->id) : route('manuals.store') }}" id="manual-form">
@csrf <input type="hidden" name="return_to" value="{{ $returnTo }}"> @if($manual->exists)@method('PUT')<input type="hidden" name="version" value="{{ $manual->lock_version }}">@endif
<div class="form-grid"><div>
<div class="panel"><div class="panel-heading"><h2>Datos generales</h2><span class="badge-soft">{{ $manual->exists ? $manual->manualPhase->phase_name : 'Planificación' }}</span></div><div class="panel-body"><div class="row g-3">
<div class="col-12"><label class="form-label" for="manual_name">Nombre del manual</label><input class="form-control" id="manual_name" name="manual_name" maxlength="250" value="{{ old('manual_name',$manual->manual_name) }}" required></div>
<div class="col-md-6"><label class="form-label" for="map">Mapa doctrinario</label><select class="form-select" name="doctrinal_map_id" id="map" required><option value="">Selecciona un mapa</option>@foreach($maps as $map)<option value="{{ $map->id }}" @selected(old('doctrinal_map_id',$manual->exists ? $manual->manualType->doctrinal_map_id : null)==$map->id)>{{ $map->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="manual_type">Tipo de manual</label><select id="manual_type" name="manual_types_id" class="form-select" required><option value="">Selecciona un tipo</option>@foreach($maps as $map)@foreach($map->manualTypes as $type)<option data-map="{{ $map->id }}" value="{{ $type->id }}" @selected(old('manual_types_id',$manual->manual_types_id)==$type->id)>{{ $type->code }} · {{ $type->type_name }}</option>@endforeach @endforeach</select></div>
<div class="col-12"><label class="form-label" for="observations">Observaciones</label><textarea class="form-control" name="observations" id="observations" maxlength="255" rows="3">{{ old('observations',$manual->observations) }}</textarea></div>
@if(!$manual->exists)<div class="col-md-6"><label for="process_started_on" class="form-label">Fecha de inicio de Planificación</label><input type="date" class="form-control" name="process_started_on" id="process_started_on" required max="{{ today()->format('Y-m-d') }}" value="{{ old('process_started_on',today()->format('Y-m-d')) }}"></div>@endif
</div></div></div>
@if($committeeTypes->isNotEmpty())
<div class="panel"><div class="panel-heading"><h2>Comités de {{ $manual->manualPhase->phase_name }}</h2></div><div class="panel-body"><p class="form-text">Asigna los integrantes de ambos comités antes de finalizar esta fase. Las unidades son asociaciones independientes.</p>
@foreach($committeeTypes as $committee)
@php($selectedMembers=old('members.'.$committee->code,$manual->manualCommitteeMembers->where('committee_type_id',$committee->id)->pluck('committee_members_id')->all()))
<div class="mb-4"><h3 class="form-section-title">{{ $committee->name }}</h3>
@include('partials.assignment-picker', ['kind'=>'members', 'options'=>$members->whereIn('id',$selectedMembers)])
<p class="form-text">Busca y marca los integrantes de este comité. Las selecciones se conservan al buscar. Dejarlo vacío retira sus asignaciones.</p><input type="hidden" name="present_members[]" value="{{ $committee->id }}">
@if(in_array($committee->code,[1,3,4]))
@php($selectedUnits=old('units.'.$committee->code,$manual->manualMilitaryUnits->where('committee_type_id',$committee->id)->pluck('military_units_id')->all()))
<div class="form-label mt-2">Unidades militares</div>
@include('partials.assignment-picker', ['kind'=>'units', 'options'=>$units->whereIn('id',$selectedUnits)])
<input type="hidden" name="present_units[]" value="{{ $committee->id }}">
@endif
</div>@endforeach</div></div>
@else<div class="status-banner"><i class="fas fa-info-circle" aria-hidden="true"></i> {{ !$manual->exists || $manual->manualPhase->slug==='planning' ? 'En Planificación no se asignan comités.' : 'Esta fase conserva los comités de las fases anteriores.' }} Los comités se gestionan únicamente durante Investigación y Experimentación, cada fase con sus propios integrantes.</div>@endif
</div><div>
<div class="panel"><div class="panel-heading"><h2>Avances por fase</h2></div><div class="panel-body"><p class="form-text">Investigación admite de 2 a 6 avances y Experimentación de 2 a 8. Las actividades fijas se mantienen.</p>
@foreach(['research_advances'=>'Investigación','experimentation_advances'=>'Experimentación'] as $field=>$label)
<div class="mb-4"><label class="form-label" for="{{ $field }}">{{ $label }}</label><input class="form-control advance-count" type="number" min="2" max="{{ $field === 'research_advances' ? 6 : 8 }}" required name="{{ $field }}" id="{{ $field }}" value="{{ old($field,$manual->$field ?? 2) }}"><div class="form-text">{{ $field === 'research_advances' ? 10 : 6 }} actividades fijas + avances configurados.</div></div>@endforeach
@if($manual->exists)<p class="form-text">Aumentar avances puede reducir el porcentaje. No se pueden retirar avances con historial ni cambiar fases ya superadas.</p>@endif
<hr><button class="btn btn-primary w-100" type="submit"><i class="fas fa-save" aria-hidden="true"></i> {{ $manual->exists ? 'Guardar cambios' : 'Crear manual' }}</button></div></div>
</div></div></form>
@endsection
@push('scripts')
<script src="{{ asset('js/assignment-picker.js') }}?v={{ filemtime(public_path('js/assignment-picker.js')) }}" defer></script>
<script>
const mapSelect=document.getElementById('map'), typeSelect=document.getElementById('manual_type');
function filterTypes(){[...typeSelect.options].forEach(option=>{if(!option.value)return;option.hidden=option.dataset.map!==mapSelect.value;option.disabled=option.hidden;});if(typeSelect.selectedOptions[0]?.disabled)typeSelect.value='';}
mapSelect.addEventListener('change',filterTypes);filterTypes();
</script>
@endpush

