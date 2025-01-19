@extends('adminlte::page')

@section('title', 'EDITAR UNIDAD MILITAR')

@section('content_header')
    <div class="row justify-content-center align-item-center">
        <div class="col text-center">
            <h1>EDITAR UNIDAD MILITAR</h1>
        </div>
    </div>

@stop

@section('content')

    <div class="" id="">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">EDITAR UNIDAD MILITAR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="container">
                    <form id="newUserForm" class="row" action="{{ route('militaryUnits.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $militaryUnit->id }}">
                        <div class="form-group col-6">
                            <label for="unit_name">Nombre de la Unidad:</label>
                            <input type="text" name="unit_name" class="form-control" maxlength="140"
                                value="{{ $militaryUnit->unit_name }}" required>
                        </div>

                        <div class="form-group col-6">
                            <label for="unit_acronym">Abreviatura de la unidad:</label>
                            <input type="text" id="unit_acronym" name="unit_acronym" class="form-control"
                                value="{{ $militaryUnit->unit_acronym }}" maxlength="10" required>
                        </div>
                        <div class="p-4 bg-white rounded shadow-sm mx-auto" style="max-width: 400px;">
                            <div class="form-group d-flex justify-content-between">
                                <a href="{{ route('militaryUnits.index') }}" class="btn btn-danger m-2">Regresar</a>
                                <button class="btn btn-success m-2" id="saveButton">Editar Unidad Militar</button>
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
