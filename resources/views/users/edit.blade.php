@extends('layouts.artemis')
@section('title','Editar usuario')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Administración de accesos</div><h1>{{ $user->name }}</h1><p class="page-subtitle">{{ $user->email }}</p></div><a class="btn btn-light" href="{{ route('users.index') }}">Volver</a></div>
@if(auth()->user()?->is_active && auth()->user()?->hasRole('admin'))
<div class="panel mb-3"><div class="panel-body d-flex justify-content-between align-items-center"><span>{{ $user->must_change_password ? 'La cuenta debe elegir una nueva contraseña al iniciar sesión.' : 'La contraseña actual está vigente.' }}</span><form id="reset-password-form" method="POST" action="{{ route('users.resetPassword',$user->id) }}">@csrf<button type="button" class="btn btn-outline-danger" id="reset-password-button" @disabled(!$user->is_active)>Restablecer contraseña</button></form></div></div>
@endif
<form method="POST" action="{{ route('users.update',$user->id) }}">@csrf @method('PUT')
<div class="form-grid"><div class="panel"><div class="panel-heading"><h2>Roles y funciones</h2></div><div class="panel-body"><input type="hidden" name="roles" value=""><div class="check-list" style="max-height:none">
@foreach($roles as $role)<label><input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name,old('roles',$user->getRoleNames()->all()) ?: []))><span><strong>{{ ucfirst($role->name) }}</strong><span class="form-text d-block">{{ ['admin'=>'Control completo del sistema y usuarios.','none'=>'Sin acceso a módulos. Se retira al asignar otro rol.','supervisor'=>'Dashboard y reportes, sin operación de manuales.','planificador'=>'Operación de manuales en Planificación.','investigador'=>'Operación de manuales en Investigación.','experimentador'=>'Operación de manuales en Experimentación.','edición'=>'Operación de manuales en Edición.','cgft'=>'Operación de manuales en CGFT.','publicados'=>'Incorporar y editar metadatos de publicados.'][$role->name] ?? '' }}</span></span></label>@endforeach</div></div></div>
<div class="panel"><div class="panel-heading"><h2>Datos administrativos</h2></div><div class="panel-body"><label for="name" class="form-label">Nombre</label><input id="name" name="name" value="{{ old('name',$user->name) }}" class="form-control mb-3" required maxlength="255"><label for="email" class="form-label">Correo</label><input id="email" name="email" type="email" value="{{ old('email',$user->email) }}" class="form-control mb-3" required maxlength="255"><label for="grade" class="form-label">Grado</label><select name="grade" id="grade" class="form-select mb-3"><option value="">Sin grado</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(old('grade',$user->grade)==$grade->id)>{{ $grade->grade_name }}</option>@endforeach</select><label class="form-label" for="is_active">Estado de la cuenta</label><select name="is_active" id="is_active" class="form-select mb-4"><option value="1" @selected(old('is_active',$user->is_active)==1)>Activo</option><option value="0" @selected(old('is_active',$user->is_active)==0)>Inactivo</option></select><button class="btn btn-primary w-100">Guardar accesos</button></div></div></div></form>
@push('scripts')
<script>
document.getElementById('reset-password-button')?.addEventListener('click', async function () {
    if (await window.ArtemisAlerts.confirm('La contraseña actual se reemplazará por una provisional. La persona deberá elegir una nueva antes de usar los módulos.', 'Restablecer contraseña', 'Restablecer')) {
        document.getElementById('reset-password-form').requestSubmit();
    }
});
</script>
@endpush
@include('partials.alert-modal')
@endsection
