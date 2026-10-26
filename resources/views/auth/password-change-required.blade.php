@extends('layouts.app')
@section('content')
<div class="container py-5" style="max-width:640px">
    <div class="card"><div class="card-header">Actualiza tu contraseña</div><div class="card-body">
        <p>Antes de continuar, establece una contraseña personal. Confírmala para guardar el cambio.</p>
        <form method="POST" action="{{ route('password.change.update') }}">@csrf @method('PUT')
            <div class="mb-3"><label for="password" class="form-label">Nueva contraseña</label><input id="password" name="password" type="password" class="form-control" required autocomplete="new-password"></div>
            <div class="mb-3"><label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password"></div>
            <button class="btn btn-primary">Guardar contraseña</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
            <button class="btn btn-link px-0">Cerrar sesión</button>
        </form>
    </div></div>
</div>
@endsection
