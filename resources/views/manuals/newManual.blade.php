@extends('adminlte::page')

@section('title', 'NUEVO MANUAL')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">NUEVO MANUAL</h1>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
            {{ session('error') }}
        </div>
    @endif
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
                        maxlength="240" placeholder="Ingrese el nombre del manual" required>
                </div>

                <!-- INVESTIGACIÓN -->
                <div class="investigation-section">
                    <div class="col-12 text-center">
                        <h5 class="text-uppercase text-bold">INVESTIGACIÓN</h5>
                    </div>
                    <div class="row">
                        <!-- Miembros del Comité de Investigación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="committee_research_search">Miembros del Comité de Investigación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="committee_research_search" class="form-control"
                                    placeholder="Buscar por apellido o cédula de identidad">
                                <div class="input-group-append">
                                    <button type="button" id="add_committee_research_member"
                                        class="btn btn-info">Añadir</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_research_member_alert">No se encontró ningún
                                miembro con ese criterio de búsqueda.</div>
                            <ul id="selected_committee_research_members" class="list-group col-11"></ul>
                            <input type="hidden" name="committee_research_members" id="committee_research_members">
                        </div>

                        <!-- Miembros del Comité de Validación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="committee_validation_search">Miembros del Comité de Validación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="committee_validation_search" class="form-control"
                                    placeholder="Buscar por apellido o cédula de identidad">
                                <div class="input-group-append">
                                    <button type="button" id="add_committee_validation_member"
                                        class="btn btn-info">Añadir</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_validation_member_alert">No se encontró ningún
                                miembro con ese criterio de búsqueda.</div>
                            <ul id="selected_committee_validation_members" class="list-group col-11"></ul>
                            <input type="hidden" name="committee_validation_members" id="committee_validation_members">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Unidades del Comité de Investigación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="unit_search_investigation">Unidades del Comité de Investigación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="unit_search_investigation" class="form-control"
                                    placeholder="Buscar unidad por abreviatura">
                                <div class="input-group-append">
                                    <button type="button" id="add_unit_investigation" class="btn btn-info">Añadir
                                        Unidad</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_unit_alert_investigation">No se encontró ninguna
                                unidad con ese criterio de búsqueda.</div>
                            <ul id="selected_units_investigation" class="list-group col-11"></ul>
                            <input type="hidden" name="military_units_investigation" id="military_units_investigation">
                        </div>

                        <!-- Tipo de Manual -->
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
                </div>

                <!-- EXPERIMENTACIÓN -->
                <div class="experiment-section">
                    <div class="col-12 text-center">
                        <h5 class="text-uppercase text-bold">EXPERIMENTACIÓN</h5>
                    </div>
                    <div class="row">
                        <!-- Miembros del Comité de Experimentación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="committee_experiment_search">Miembros del Comité de Experimentación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="committee_experiment_search" class="form-control"
                                    placeholder="Buscar por apellido o cédula de identidad">
                                <div class="input-group-append">
                                    <button type="button" id="add_committee_experiment_member"
                                        class="btn btn-info">Añadir</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_experiment_member_alert">No se encontró ningún
                                miembro con ese criterio de búsqueda.</div>
                            <ul id="selected_committee_experiment_members" class="list-group col-11"></ul>
                            <input type="hidden" name="committee_experiment_members" id="committee_experiment_members">
                        </div>

                        <!-- Miembros del Comité de Validación de Experimentación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="committee_experiment_validation_search">Miembros del Comité de Validación de
                                Experimentación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="committee_experiment_validation_search" class="form-control"
                                    placeholder="Buscar por apellido o cédula de identidad">
                                <div class="input-group-append">
                                    <button type="button" id="add_committee_experiment_validation_member"
                                        class="btn btn-info">Añadir</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_experiment_validation_member_alert">No se
                                encontró ningún miembro con ese criterio de búsqueda.</div>
                            <ul id="selected_committee_experiment_validation_members" class="list-group col-11"></ul>
                            <input type="hidden" name="committee_experiment_validation_members"
                                id="committee_experiment_validation_members">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Unidades Comité de Experimentación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="unit_search_experiment">Unidades del Comité de Experimentación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="unit_search_experiment" class="form-control"
                                    placeholder="Buscar unidad por abreviatura">
                                <div class="input-group-append">
                                    <button type="button" id="add_unit_experiment" class="btn btn-info">Añadir
                                        Unidad</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_unit_alert_experiment">No se encontró ninguna
                                unidad con ese criterio de búsqueda.</div>
                            <ul id="selected_units_experiment" class="list-group col-11"></ul>
                            <input type="hidden" name="military_units_experiment" id="military_units_experiment">
                        </div>

                        <!-- Unidades Comité de Validación de Experimentación -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="unit_search_experiment_validation">Unidades del Comité de Validación de
                                Experimentación</label>
                            <div class="input-group mb-3">
                                <input type="text" id="unit_search_experiment_validation" class="form-control"
                                    placeholder="Buscar unidad por abreviatura">
                                <div class="input-group-append">
                                    <button type="button" id="add_unit_experiment_validation"
                                        class="btn btn-info">Añadir Unidad</button>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="no_unit_alert_experiment_validation">No se encontró
                                ninguna unidad con ese criterio de búsqueda.</div>
                            <ul id="selected_units_experiment_validation" class="list-group col-11"></ul>
                            <input type="hidden" name="military_units_experiment_validation"
                                id="military_units_experiment_validation">
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                    <label for="observations">Observaciones</label>
                    <textarea name="observations" id="observations" rows="5" class="form-control"
                        placeholder="Ingrese observaciones sobre el manual"></textarea>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-center mt-4">
                    <a href="{{ route('manuals.index') }}"
                        class="btn btn-outline-secondary m-1 btn-md me-3 px-4 py-2 shadow-sm">
                        <i class="fas fa-arrow-left"></i> Cancelar y Regresar
                    </a>
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

        .investigation-section {
            background-color: rgba(0, 123, 255, 0.1);
            padding: 8px;
            border-radius: 5px;
        }

        .experiment-section {
            margin-top: 7px;
            border-bottom: 1px solid #e5e5e5;
            background-color: rgba(40, 167, 69, 0.15);
            padding: 8px;
            border-radius: 5px;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Variables iniciales
            const committeeMembers = @json($committeeMembers);
            const militaryUnits = @json($militaryUnits);

            // Arrays para almacenar miembros y unidades seleccionadas
            let selectedResearchCommitteeMembers = []; // Comité de Investigación
            let selectedValidationCommitteeMembers = []; // Comité de Validación
            let selectedExperimentCommitteeMembers = []; // Comité de Experimentación
            let selectedExperimentValidationCommitteeMembers = []; // Comité de Validación de Experimentación
            let selectedInvestigationUnits = []; // Unidades de Investigación
            let selectedExperimentUnits = []; // Unidades de Experimentación
            let selectedExperimentValidationUnits = []; // Unidades de Validación de Experimentación

            // Función genérica para añadir miembros a un comité
            function addCommitteeMember(searchValue, selectedMembers, listSelector, hiddenInput, alertSelector) {
                const member = committeeMembers.find(
                    (m) =>
                    m.full_name.toLowerCase().includes(searchValue) ||
                    m.identification.includes(searchValue)
                );

                if (member && !selectedMembers.some((m) => m.id === member.id)) {
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

            // Función genérica para actualizar el valor del input oculto
            function updateHiddenInput(selectedItems, hiddenInput) {
                const itemIds = selectedItems.map((item) => item.id);
                $(hiddenInput).val(JSON.stringify(itemIds));
            }

            // Eventos para añadir miembros a los diferentes comités
            $('#add_committee_research_member').on('click', function() {
                const searchValue = $('#committee_research_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedResearchCommitteeMembers,
                    '#selected_committee_research_members',
                    '#committee_research_members',
                    '#no_research_member_alert'
                );
                $('#committee_research_search').val('');
            });

            $('#add_committee_validation_member').on('click', function() {
                const searchValue = $('#committee_validation_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedValidationCommitteeMembers,
                    '#selected_committee_validation_members',
                    '#committee_validation_members',
                    '#no_validation_member_alert'
                );
                $('#committee_validation_search').val('');
            });

            $('#add_committee_experiment_member').on('click', function() {
                const searchValue = $('#committee_experiment_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedExperimentCommitteeMembers,
                    '#selected_committee_experiment_members',
                    '#committee_experiment_members',
                    '#no_experiment_member_alert'
                );
                $('#committee_experiment_search').val('');
            });

            $('#add_committee_experiment_validation_member').on('click', function() {
                const searchValue = $('#committee_experiment_validation_search').val().toLowerCase();
                addCommitteeMember(
                    searchValue,
                    selectedExperimentValidationCommitteeMembers,
                    '#selected_committee_experiment_validation_members',
                    '#committee_experiment_validation_members',
                    '#no_experiment_validation_member_alert'
                );
                $('#committee_experiment_validation_search').val('');
            });

            // Evento para eliminar miembros de cualquier lista
            $(document).on('click', '.remove-member', function() {
                const memberId = $(this).data('id');
                selectedResearchCommitteeMembers = selectedResearchCommitteeMembers.filter((m) => m.id !==
                    memberId);
                selectedValidationCommitteeMembers = selectedValidationCommitteeMembers.filter((m) => m
                    .id !== memberId);
                selectedExperimentCommitteeMembers = selectedExperimentCommitteeMembers.filter((m) => m
                    .id !== memberId);
                selectedExperimentValidationCommitteeMembers = selectedExperimentValidationCommitteeMembers
                    .filter((m) => m.id !== memberId);
                $(this).parent().remove();
            });

            // Función genérica para añadir unidades
            function addUnit(searchValue, selectedUnits, listSelector, hiddenInput, alertSelector) {
                const unit = militaryUnits.find((u) =>
                    u.unit_acronym.toLowerCase().includes(searchValue)
                );

                if (unit && !selectedUnits.some((u) => u.id === unit.id)) {
                    selectedUnits.push(unit);
                    $(listSelector).append(`
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${unit.unit_acronym} - ${unit.unit_name}
                    <button type="button" class="btn btn-danger btn-sm remove-unit" data-id="${unit.id}">Eliminar</button>
                </li>
            `);
                    updateHiddenInput(selectedUnits, hiddenInput);
                    $(alertSelector).addClass('d-none');
                } else {
                    $(alertSelector).removeClass('d-none');
                }
            }

            // Eventos para añadir unidades a las diferentes categorías
            $('#add_unit_investigation').on('click', function() {
                const searchValue = $('#unit_search_investigation').val().toLowerCase();
                addUnit(
                    searchValue,
                    selectedInvestigationUnits,
                    '#selected_units_investigation',
                    '#military_units_investigation',
                    '#no_unit_alert_investigation'
                );
                $('#unit_search_investigation').val('');
            });

            $('#add_unit_experiment').on('click', function() {
                const searchValue = $('#unit_search_experiment').val().toLowerCase();
                addUnit(
                    searchValue,
                    selectedExperimentUnits,
                    '#selected_units_experiment',
                    '#military_units_experiment',
                    '#no_unit_alert_experiment'
                );
                $('#unit_search_experiment').val('');
            });

            $('#add_unit_experiment_validation').on('click', function() {
                const searchValue = $('#unit_search_experiment_validation').val().toLowerCase();
                addUnit(
                    searchValue,
                    selectedExperimentValidationUnits,
                    '#selected_units_experiment_validation',
                    '#military_units_experiment_validation',
                    '#no_unit_alert_experiment_validation'
                );
                $('#unit_search_experiment_validation').val('');
            });

            // Evento para eliminar unidades
            $(document).on('click', '.remove-unit', function() {
                const unitId = $(this).data('id');
                selectedInvestigationUnits = selectedInvestigationUnits.filter((u) => u.id !== unitId);
                selectedExperimentUnits = selectedExperimentUnits.filter((u) => u.id !== unitId);
                selectedExperimentValidationUnits = selectedExperimentValidationUnits.filter((u) => u.id !==
                    unitId);
                $(this).parent().remove();
            });

            // Validación del formulario antes de enviarlo
            $('form').on('submit', function(e) {
                let isValid = true;
                let messages = [];

                // Validar que haya al menos un miembro en cada comité
                if (selectedResearchCommitteeMembers.length === 0) {
                    isValid = false;
                    messages.push('Debe seleccionar al menos un miembro del Comité de Investigación.');
                }
                if (selectedValidationCommitteeMembers.length === 0) {
                    isValid = false;
                    messages.push('Debe seleccionar al menos un miembro del Comité de Validación.');
                }

                // Validar que haya al menos una unidad seleccionada
                if (
                    selectedInvestigationUnits.length === 0 &&
                    selectedExperimentUnits.length === 0 &&
                    selectedExperimentValidationUnits.length === 0
                ) {
                    isValid = false;
                    messages.push('Debe seleccionar al menos una Unidad.');
                }

                // Validar que se haya seleccionado un tipo de manual
                const manualType = $('#manual_type').val();
                if (!manualType) {
                    isValid = false;
                    messages.push('Debe seleccionar un tipo de Manual.');
                }

                // Mostrar alertas si no es válido
                if (!isValid) {
                    e.preventDefault(); // Evitar que el formulario se envíe
                    alert(messages.join('\n'));
                }
            });
        });
    </script>


@stop
