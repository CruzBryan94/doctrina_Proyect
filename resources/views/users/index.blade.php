@extends('layouts.artemis')
@section('title','Usuarios y roles')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Administración del sistema</div><h1>Usuarios y roles</h1><p class="page-subtitle">Cada usuario puede reunir varios roles. Los permisos se combinan según sus funciones.</p></div><a class="btn btn-primary" href="{{ route('users.create') }}">Crear cuenta</a></div>
<div class="panel"><div class="table-responsive"><table class="data-table"><thead><tr><th>Usuario</th><th>Grado</th><th>Roles asignados</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong><div class="manual-meta">{{ $user->email }}</div></td><td>{{ $user->gradeRecord?->grade_name ?? 'Sin grado' }}</td><td>@forelse($user->roles as $role)<span class="badge-soft">{{ $role->name }}</span>@empty<span class="manual-meta">Sin roles</span>@endforelse</td><td><span class="badge-soft">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><a class="btn btn-sm btn-outline-success" href="{{ route('users.edit',$user->id) }}">Editar roles</a></td></tr>@endforeach
</tbody></table></div><div class="panel-body">{{ $users->links() }}</div></div>
@endsection
