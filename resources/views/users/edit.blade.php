@extends('adminlte::page')

@section('title', 'EDITAR USUARIO')

@section('content_header')
    <div class="row justify-content-center align-item-center">
        <div class="col text-center">
            <h1>EDITAR DE USUARIO</h1>
        </div>
    </div>

@stop

@section('content')

    <div class="" id="">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">EDITAR USUARIO</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="container">
                    {{-- <form id="forNewVehicle" class ="row" action="{{ route('users.update') }}" method="POST"
                        enctype="multipart/form-data"> --}}
                        <form id="editUserForm" class ="row" action="{{ route('users.update') }}" method="POST">
                         @csrf

                        <input type="hidden" name="id" value="{{ $user->id }}">

                        <div class="form-group col-6">
                            <label for="last_names">Apellidos y Nombres:</label>
                            <input type="text" name="last_names" class="form-control" value="{{ $user->name }}"
                                required disabled>
                        </div>

                        <div class="form-group col-6">
                            <label for="email">Correo:</label>
                            <input type="mail" name="email" class="form-control" value="{{ $user->email }}" required disabled>
                        </div>

                        <div class="form-group col-3">
                            <label for="grade">Grado:</label>
                            <select name="grade" class="form-control" required>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->code }}" @if ($grade->code == $user->grade) selected @endif>
                                        {{ $grade->grade_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-3">
                            <label for="role_id">Rol:</label>
                            <select name="role_id" class="form-control" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @if ($role->id == $roleId) selected @endif>
                                        {{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-3">
                            <label for="is_active">Estado:</label>
                            <select name="is_active" class="form-control" required>
                                <option value="1" @if ($user->is_active == 1) selected @endif>Activo</option>
                                <option value="0" @if ($user->is_active == 0) selected @endif>Inactivo</option>
                            </select>
                        </div>


                        <div class="p-4 bg-white rounded shadow-sm mx-auto" style="max-width: 400px;">
                            <div class="form-group d-flex justify-content-between">
                                <a href="{{ route('users.index') }}" class="btn btn-danger m-2">Regresar</a>
                                <button class="btn btn-success m-2">Guardar Cambios</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @stop

    @section('css')
        <link rel="stylesheet" href="/css/admin_custom.css">
    @stop

    @section('js')
        <script></script>
    @stop
