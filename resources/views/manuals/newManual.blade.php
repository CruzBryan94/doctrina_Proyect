@extends('adminlte::page')

@section('title', 'NUEVO MANUAL')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">NUEVO MANUAL</h1>
    </div>

@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('manuals.store') }}" method="POST">
                @csrf

                <!-- Nombre del Manual -->
                <div class="form-group">
                    <label for="manual_name">Nombre del Manual o Reglamento</label>
                    <input type="text" name="manual_name" id="manual_name" class="form-control"
                        placeholder="Ingrese el nombre del manual" required>
                </div>

                <div class="row">
                    <!-- Miembros del Comité de Investigación -->
                    <div class="form-group col-md-6 col-sm-12">
                        <label for="committee_research_members">Miembros del Comité de Investigación</label>
                        <div class="input-group mb-3">
                            <input type="text" id="committee_research_search" class="form-control"
                                placeholder="Buscar por apellido o cédula de identidad">
                            <div class="input-group-append">
                                <button type="button" id="add_committee_research_member"
                                    class="btn btn-info">Añadir</button>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none" id="no_research_member_alert">No se encontró ningún miembro
                            con ese criterio de búsqueda.</div>
                        <ul id="selected_committee_research_members" class="list-group col-11">
                            <!-- Aquí se mostrarán los miembros seleccionados -->
                        </ul>
                        <input type="hidden" name="committee_research_members" id="committee_research_members"
                            value="">
                    </div>

                    <!-- Miembros del Comité de Validación -->
                    <div class="form-group col-md-6 col-sm-12">
                        <label for="committee_validation_members">Miembros del Comité de Validación</label>
                        <div class="input-group mb-3">
                            <input type="text" id="committee_validation_search" class="form-control"
                                placeholder="Buscar por apellido o cédula de identidad">
                            <div class="input-group-append">
                                <button type="button" id="add_committee_validation_member"
                                    class="btn btn-info">Añadir</button>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none" id="no_validation_member_alert">No se encontró ningún miembro
                            con ese criterio de búsqueda.</div>
                        <ul id="selected_committee_validation_members" class="list-group col-11">
                            <!-- Aquí se mostrarán los miembros seleccionados -->
                        </ul>
                        <input type="hidden" name="committee_validation_members" id="committee_validation_members"
                            value="">
                    </div>
                </div>

                <div class="row">

                    <!-- Unidades Seleccionadas -->
                    <div class="form-group col-md-6 col-sm-12">
                        <label for="military_units">Unidades Seleccionadas</label>
                        <div class="input-group mb-3">
                            <input type="text" id="unit_search" class="form-control"
                                placeholder="Buscar unidad por su abreviatura">
                            <div class="input-group-append">
                                <button type="button" id="add_unit" class="btn btn-info">Añadir</button>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none" id="no_unit_alert">No se encontró ninguna unidad con ese
                            criterio
                            de búsqueda.</div>
                        <ul id="selected_units" class="list-group col-11">
                            <!-- Aquí se mostrarán las unidades seleccionadas -->
                        </ul>
                        <input type="hidden" name="military_units" id="military_units" value="">
                    </div>

                    <div class="form-group col-md-6 col-sm-12">
                        <label for="manual_type">Tipo de Manual</label>
                        <select class="form-control" id="manual_type" name="manual_type_id">
                            <option value="" disabled selected>Seleccione un tipo de manual</option>
                            @foreach ($manualTypes as $manualType)
                                <option value="{{ $manualType->id }}">{{ $manualType->type_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                    <label for="observations">Observaciones</label>
                    <textarea name="observations" id="observations" rows="5" class="form-control"
                        placeholder="Ingrese observaciones sobre el manual"></textarea>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <!-- Botón "Cancelar y Regresar" -->
                    <a href="{{ route('manuals.index') }}" class="btn btn-outline-secondary m-1 btn-md me-3 px-4 py-2 shadow-sm">
                        <i class="fas fa-arrow-left"></i> Cancelar y Regresar
                    </a>
                    <!-- Botón "Guardar Manual" -->
                    <button type="submit" class="btn btn-info m-1 btn-md px-4 py-2 shadow-sm">
                        <i class="fas fa-save"></i> Guardar Manual
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Estilo del contenedor del título */
        .title-container {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        /* Título principal */
        .dashboard-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #343a40;
            /* Gris oscuro (puedes usar el color de tu sistema) */
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Decoración debajo del título */
        .dashboard-title::after {
            content: "";
            display: block;
            width: 100px;
            height: 4px;
            background: #1a9620;
            /* Color primario */
            margin: 10px auto 0;
            border-radius: 2px;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            const committeeMembers = @json($committeeMembers);
            const militaryUnits = @json($militaryUnits);

            let selectedCommitteeMembersResearch = [];
            let selectedCommitteeMembersValidation = [];
            let selectedUnits = [];

            // Función genérica para añadir miembros
            function addCommitteeMember(searchValue, selectedMembers, listSelector, hiddenInput, alertSelector) {
                const member = committeeMembers.find(m => m.full_name.toLowerCase().includes(searchValue) || m
                    .identification.includes(searchValue));

                if (member && !selectedMembers.some(m => m.id === member.id)) {
                    selectedMembers.push(member);
                    $(listSelector).append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${member.grade} ${member.full_name} (${member.identification})
                        <button type="button" class="btn btn-danger btn-sm remove-member" data-id="${member.id}">Eliminar</button>
                    </li>
                `);
                    updateHiddenInput(selectedMembers, hiddenInput);
                    $(alertSelector).addClass('d-none');
                } else {
                    $(alertSelector).removeClass('d-none');
                }
            }

            // Función genérica para actualizar el campo oculto
            function updateHiddenInput(selectedMembers, hiddenInput) {
                const memberIds = selectedMembers.map(m => m.id);
                $(hiddenInput).val(JSON.stringify(memberIds));
            }

            // Miembros del Comité de Investigación
            $('#add_committee_research_member').on('click', function() {
                const searchValue = $('#committee_research_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedCommitteeMembersResearch,
                    '#selected_committee_research_members',
                    '#committee_research_members',
                    '#no_research_member_alert'
                );
                $('#committee_research_search').val('');
            });

            // Miembros del Comité de Validación
            $('#add_committee_validation_member').on('click', function() {
                const searchValue = $('#committee_validation_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedCommitteeMembersValidation,
                    '#selected_committee_validation_members',
                    '#committee_validation_members',
                    '#no_validation_member_alert'
                );
                $('#committee_validation_search').val('');
            });

            // Eliminar Miembro
            $(document).on('click', '.remove-member', function() {
                const memberId = $(this).data('id');
                selectedCommitteeMembersResearch = selectedCommitteeMembersResearch.filter(m => m.id !==
                    memberId);
                selectedCommitteeMembersValidation = selectedCommitteeMembersValidation.filter(m => m.id !==
                    memberId);
                $(this).parent().remove();
                updateHiddenInput(selectedCommitteeMembersResearch, '#committee_research_members');
                updateHiddenInput(selectedCommitteeMembersValidation, '#committee_validation_members');
            });

            // Unidades
            $('#add_unit').on('click', function() {
                const searchValue = $('#unit_search').val().toLowerCase();
                const unit = militaryUnits.find(u => u.unit_acronym.toLowerCase().includes(searchValue));

                if (unit && !selectedUnits.some(u => u.id === unit.id)) {
                    selectedUnits.push(unit);
                    $('#selected_units').append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${unit.unit_acronym} - ${unit.unit_name}
                        <button type="button" class="btn btn-danger btn-sm remove-unit" data-id="${unit.id}">Eliminar</button>
                    </li>
                `);
                    updateHiddenInput(selectedUnits, '#military_units');
                    $('#no_unit_alert').addClass('d-none');
                } else {
                    $('#no_unit_alert').removeClass('d-none');
                }
                $('#unit_search').val('');
            });

            $(document).on('click', '.remove-unit', function() {
                const unitId = $(this).data('id');
                selectedUnits = selectedUnits.filter(u => u.id !== unitId);
                $(this).parent().remove();
                updateHiddenInput(selectedUnits, '#military_units');
            });

            // Validar el formulario antes de enviarlo
            $('form').on('submit', function(e) {
                let isValid = true;
                let messages = [];

                // Validar que haya al menos un miembro en cada comité
                if (selectedCommitteeMembersResearch.length === 0) {
                    isValid = false;
                    messages.push("Debe seleccionar al menos un miembro del Comité de Investigación.");
                }

                if (selectedCommitteeMembersValidation.length === 0) {
                    isValid = false;
                    messages.push("Debe seleccionar al menos un miembro del Comité de Validación.");
                }

                // Validar que haya al menos una unidad seleccionada
                if (selectedUnits.length === 0) {
                    isValid = false;
                    messages.push("Debe seleccionar al menos una Unidad.");
                }

                // Validar que se haya seleccionado un tipo de manual
                const manualType = $('#manual_type').val();
                if (!manualType) {
                    isValid = false;
                    messages.push("Debe seleccionar un tipo de Manual.");
                }

                // Mostrar alertas si no es válido
                if (!isValid) {
                    e.preventDefault(); // Evitar que el formulario se envíe
                    alert(messages.join("\n"));
                }
            });
        });
    </script>
@stop
