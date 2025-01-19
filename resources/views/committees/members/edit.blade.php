@extends('adminlte::page')

@section('title', 'EDITAR MIEMBRO')

@section('content_header')
    <div class="row justify-content-center align-item-center">
        <div class="col text-center">
            <h1>EDITAR MIEMBRO</h1>
        </div>
    </div>

@stop

@section('content')

    <div class="" id="">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">EDITAR MIEMBRO</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="container">
                    <form id="editUserForm" class ="row" action="{{ route('members.update') }}" method="POST">
                        @csrf

                        <input type="hidden" name="id" value="{{ $committeeMember->id }}">

                        <div class="form-group col-6">
                            <label for="full_name">Apellidos y Nombres:</label>
                            <input type="text" name="full_name" class="form-control"
                                value="{{ $committeeMember->full_name }}" maxlength="240" required>
                        </div>

                        <div class="form-group col-6">
                            <label for="identification">Cédula de identidad:</label>
                            <input type="texxt" name="identification" class="form-control"
                                oninput="validateIdentification()" id="identification"
                                value="{{ $committeeMember->identification }}" required>
                            <small id="idError" class="text-danger" style="display: none;">
                                Deben ser 10 dígitos numéricos.
                            </small>
                        </div>

                        <div class="form-group col-3">
                            <label for="grades_id">Grado:</label>
                            <select name="grades_id" class="form-control" required>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->code }}" @if ($grade->id == $committeeMember->grade->code) selected @endif>
                                        {{ $grade->grade_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="p-4 bg-white rounded shadow-sm mx-auto" style="max-width: 400px;">
                            <div class="form-group d-flex justify-content-between">
                                <a href="{{ route('members.index') }}" class="btn btn-danger m-2">Regresar</a>
                                <button class="btn btn-success m-2" id="saveButton">Guardar Cambios</button>
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
    <script>
        function validateIdentification() {
            const input = document.getElementById('identification');
            const error = document.getElementById('idError');
            const saveButton = document.getElementById('saveButton');

            const value = input.value;
            const isValid = /^\d{10}$/.test(value); // Verifica que sean 10 números exactos

            if (isValid) {
                error.style.display = 'none';
                saveButton.disabled = false; // Habilita el botón
            } else {
                error.style.display = 'block';
                saveButton.disabled = true; // Deshabilita el botón
            }
        }
    </script>
    @stop
