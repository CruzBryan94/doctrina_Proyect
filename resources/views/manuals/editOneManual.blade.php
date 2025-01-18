@extends('adminlte::page')

@section('title', 'EDITAR MANUAL')

@section('content_header')
    <div class="title-container mb-0">
        <h2 class="dashboard-title " style="font-size: x-large;">EDITAR: {{ $manual->manual_name }}</h2>
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

@section('content')
    <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="warningModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Advertencia
                    </h5>
                </div>
                <div class="modal-body">
                    <p>
                        Si desea cambiar los miembros de cada comité o las unidades de cada comité, primero deberá eliminar
                        todos los usuarios o unidades de cada comité y luego agregar los nuevos miembros o unidades.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeModalButton" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-body">
            <form action="{{ route('manuals.updateOneManual', $manual->id) }}" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="military_units_investigation" id="military_units_investigation">
                <input type="hidden" name="military_units_experimentation" id="military_units_experimentation">
                <input type="hidden" name="military_units_experiment_validation" id="military_units_experiment_validation">
                <input type="hidden" name="committee_research_members" id="committee_research_members">
                <input type="hidden" name="committee_validation_members" id="committee_validation_members">
                <input type="hidden" name="committee_experiment_members" id="committee_experiment_members">
                <input type="hidden" name="committee_experiment_validation_members"
                    id="committee_experiment_validation_members">


                <!-- Nombre del Manual -->
                <div class="form-group">
                    <label for="manual_name">Nombre del Manual o Reglamento</label>
                    <input type="text" name="manual_name" id="manual_name" class="form-control"
                        placeholder="Ingrese el nombre del manual" value="{{ $manual->manual_name }}" required>
                </div>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Advertencia</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">
                            Si desea cambiar los miembros de cada comité o las unidades de cada comité, primero deberá
                            eliminar
                            todos los usuarios o unidades de cada comité y luego agregar los nuevos miembros o unidades.
                        </p>
                    </div>
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
                            {{-- Se añaden los miembros que ya habían sido escogidos --}}
                            <ul id="selected_committee_research_members" class="list-group col-11">
                                @foreach ($researchCommitteeMembers as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $member->committeeMember->grade->grade_name }}
                                        {{ $member->committeeMember->full_name }}
                                        <input type="hidden" name="committee_research_members[]"
                                            value="{{ $member->committee_members_id }}">

                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $member->committee_members_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
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
                            {{-- Se añaden los miembros que ya habían sido escogidos --}}
                            <ul id="selected_committee_validation_members" class="list-group col-11">
                                @foreach ($validationCommitteeMembers as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $member->committeeMember->grade->grade_name }}
                                        {{ $member->committeeMember->full_name }}
                                        <input type="hidden" name="committee_validation_members[]"
                                            value="{{ $member->committee_members_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $member->committee_members_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
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
                            {{-- Se añaden las unidades que ya habían sido escogidas --}}
                            <ul id="selected_units_investigation" class="list-group col-11">
                                @foreach ($militaryUnits as $unit)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $unit->militaryUnit->unit_acronym }} - {{ $unit->militaryUnit->unit_name }}
                                        <input type="hidden" name="military_units_investigation[]"
                                            value="{{ $unit->military_units_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $unit->military_units_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <!-- Tipo de Manual -->
                        <div class="form-group col-md-6 col-sm-12">
                            <label for="manual_type">Tipo de Manual</label>
                            {{-- se selecciona el tipo de manual --}}
                            <select class="form-control" id="manual_type" name="manual_type_id"
                                onchange="manualTypeChanged(this.value)">
                                <option value="" disabled selected>Seleccione un tipo de manual</option>
                                @foreach ($manualTypes as $manualType)
                                    <option value="{{ $manualType->id }}"
                                        @if ($manualType->id == $manual->manual_types_id) selected @endif>{{ $manualType->type_name }}
                                    </option>
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
                            {{-- Se añaden los miembros que ya habían sido escogidos --}}
                            <ul id="selected_committee_experiment_members" class="list-group col-11">
                                @foreach ($experimentationCommitteeMembers as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $member->committeeMember->grade->grade_name }}
                                        {{ $member->committeeMember->full_name }}
                                        <input type="hidden" name="committee_experiment_members[]"
                                            value="{{ $member->committee_members_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $member->committee_members_id }}">Eliminar</button>
                                    </li>
                                @endforeach
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
                            {{-- Se añaden los miembros que ya habían sido escogidos --}}
                            <ul id="selected_committee_experiment_validation_members" class="list-group col-11">
                                @foreach ($expValidCommitteeMembers as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $member->committeeMember->grade->grade_name }}
                                        {{ $member->committeeMember->full_name }}
                                        <input type="hidden" name="committee_experiment_validation_members[]"
                                            value="{{ $member->committee_members_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $member->committee_members_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
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
                            {{-- Se añaden las unidades que ya habían sido escogidas --}}
                            <ul id="selected_units_experimentation" class="list-group col-11">
                                @foreach ($militaryUnitsExp as $unit)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $unit->militaryUnit->unit_acronym }} - {{ $unit->militaryUnit->unit_name }}
                                        <input type="hidden" name="military_units_experimentation[]"
                                            value="{{ $unit->military_units_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $unit->military_units_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
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
                            {{-- Se añaden las unidades que ya habían sido escogidas --}}
                            <ul id="selected_units_experiment_validation" class="list-group col-11">
                                @foreach ($militaryUnitsExpVal as $unit)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $unit->militaryUnit->unit_acronym }} - {{ $unit->militaryUnit->unit_name }}
                                        <input type="hidden" name="military_units_experimentation_validation[]"
                                            value="{{ $unit->military_units_id }}">
                                        <button type="button" class="btn btn-danger btn-sm remove-item"
                                            data-id="{{ $unit->military_units_id }}">Eliminar</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                    <label for="observations">Observaciones</label>
                    <textarea name="observations" id="observations" rows="5" class="form-control"
                        placeholder="Ingrese observaciones sobre el manual">{{ $manual->observations }}
                    </textarea>
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


        /* Reducir la altura de las filas */
        .table-sm td,
        .table-sm th {
            padding: 5px !important;
            vertical-align: middle !important;
        }

        /* Título principal */
        .dashboard-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
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
            //MUESTRA EL modal DE ADVERTENCIA
            $('#warningModal').modal('show');
            // Cerrar el modal al hacer clic en "Entendido"
            $('#understoodButton, #closeModalButton').on('click', function() {
                console.log('click');
                $('#warningModal').modal('hide');
            });

            // Plantillas de datos
            const committeeMembersTemplate = @json($committeeMembersTemplate);
            const militaryUnitsTemplate = @json($militaryUnitsTemplate);

            // Inicializar listas seleccionadas con elementos precargados
            let selectedResearchCommitteeMembers = @json($researchCommitteeMembers).map(member => ({
                id: member.committee_members_id,
                full_name: member.committee_member?.full_name || 'Nombre no disponible',
                grade: member.committee_member?.grade?.grade_name || 'Grado no disponible'
            }));

            let selectedValidationCommitteeMembers = @json($validationCommitteeMembers).map(member => ({
                id: member.committee_members_id,
                full_name: member.committee_member.full_name,
                grade: member.committee_member.grade.grade_name
            }));

            let selectedExperimentCommitteeMembers = @json($experimentationCommitteeMembers).map(member => ({
                id: member.committee_members_id,
                full_name: member.committee_member.full_name,
                grade: member.committee_member.grade.grade_name
            }));

            let selectedExperimentValidationCommitteeMembers = @json($expValidCommitteeMembers).map(member => ({
                id: member.committee_members_id,
                full_name: member.committee_member.full_name,
                grade: member.committee_member.grade.grade_name
            }));

            let selectedInvestigationUnits = @json($militaryUnits).map(unit => ({
                id: unit.military_units_id,
                unit_acronym: unit.military_unit.unit_acronym,
                unit_name: unit.military_unit.unit_name
            }));

            let selectedExperimentUnits = @json($militaryUnitsExp).map(unit => ({
                id: unit.military_units_id,
                unit_acronym: unit.military_unit.unit_acronym,
                unit_name: unit.military_unit.unit_name
            }));

            let selectedExperimentValidationUnits = @json($militaryUnitsExpVal).map(unit => ({
                id: unit.military_units_id,
                unit_acronym: unit.military_unit.unit_acronym,
                unit_name: unit.military_unit.unit_name
            }));


            function addItem(searchValue, template, selectedList, listSelector, hiddenInput, alertSelector) {
                const item = template.find(t =>
                    t.full_name?.toLowerCase().includes(searchValue) ||
                    t.identification?.includes(searchValue) ||
                    t.unit_acronym?.toLowerCase().includes(searchValue)
                );

                if (item && !selectedList.some(selected => selected.id === item.id)) {
                    selectedList.push(item);
                    $(listSelector).append(`
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${item.grade ? `${item.grade} ` : ''}${item.full_name || `${item.unit_acronym} - ${item.unit_name}`}
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-id="${item.id}">Eliminar</button>
                </li>
            `);
                    updateHiddenInput(selectedList, hiddenInput);
                    $(alertSelector).addClass('d-none');
                } else {
                    $(alertSelector).removeClass('d-none');
                }
            }

            function updateHiddenInput(selectedList, hiddenInput) {
                const ids = selectedList.map(item => item.id);
                $(hiddenInput).val(JSON.stringify(ids));
            }


            /****************************************************************************************************/
            // AÑADIR datos
            /****************************************************************************************************/
            // Agregar miembros a los comités
            $('#add_committee_research_member').on('click', function() {
                const searchValue = $('#committee_research_search').val().toLowerCase();
                addItem(
                    searchValue,
                    committeeMembersTemplate,
                    selectedResearchCommitteeMembers,
                    '#selected_committee_research_members',
                    '#committee_research_members',
                    '#no_research_member_alert'
                );
                $('#committee_research_search').val('');
            });
            $('#add_committee_validation_member').on('click', function() {
                const searchValue = $('#committee_validation_search').val().toLowerCase();
                addItem(
                    searchValue,
                    committeeMembersTemplate,
                    selectedValidationCommitteeMembers,
                    '#selected_committee_validation_members',
                    '#committee_validation_members',
                    '#no_validation_member_alert'
                );
                $('#committee_validation_search').val('');
            });
            $('#add_committee_experiment_member').on('click', function() {
                const searchValue = $('#committee_experiment_search').val().toLowerCase();
                addItem(
                    searchValue,
                    committeeMembersTemplate,
                    selectedExperimentCommitteeMembers,
                    '#selected_committee_experiment_members',
                    '#committee_experiment_members',
                    '#no_experiment_member_alert'
                );
                $('#committee_experiment_search').val('');
            });
            $('#add_committee_experiment_validation_member').on('click', function() {
                const searchValue = $('#committee_experiment_validation_search').val().toLowerCase();
                addItem(
                    searchValue,
                    committeeMembersTemplate,
                    selectedExperimentValidationCommitteeMembers,
                    '#selected_committee_experiment_validation_members',
                    '#committee_experiment_validation_members',
                    '#no_experiment_validation_member_alert'
                );
                $('#committee_experiment_validation_search').val('');
            });

            // Agregar unidades a las categorías
            $('#add_unit_investigation').on('click', function() {
                const searchValue = $('#unit_search_investigation').val().toLowerCase();
                addItem(
                    searchValue,
                    militaryUnitsTemplate,
                    selectedInvestigationUnits,
                    '#selected_units_investigation',
                    '#military_units_investigation',
                    '#no_unit_alert_investigation'
                );
                $('#unit_search_investigation').val('');
            });
            $('#add_unit_experiment').on('click', function() {
                const searchValue = $('#unit_search_experiment').val().toLowerCase();
                addItem(
                    searchValue,
                    militaryUnitsTemplate,
                    selectedExperimentUnits,
                    '#selected_units_experimentation',
                    '#military_units_experimentation',
                    '#no_unit_alert_experiment'
                );
                $('#unit_search_experiment').val('');
            });
            $('#add_unit_experiment_validation').on('click', function() {
                const searchValue = $('#unit_search_experiment_validation').val().toLowerCase();
                addItem(
                    searchValue,
                    militaryUnitsTemplate,
                    selectedExperimentValidationUnits,
                    '#selected_units_experiment_validation',
                    '#military_units_experiment_validation',
                    '#no_unit_alert_experiment_validation'
                );
                $('#unit_search_experiment_validation').val('');
            });
            /****************************************************************************************************/
            //FIN DE AÑADIR datos
            /****************************************************************************************************/



            function removeItem(itemId, selectedList, listSelector) {
                const index = selectedList.findIndex(item => item.id === itemId);
                console.log(index);
                if (index !== -1) {
                    selectedList.splice(index, 1);
                    $(listSelector).find(`button[data-id="${itemId}"]`).closest('li').remove();
                }
            }
            // Eliminar elementos (miembros y unidades)
            $(document).on('click', '.remove-item', function() {
                const itemId = $(this).data('id');
                const listSelector = $(this).closest('ul').attr('id');

                if (listSelector === 'selected_committee_research_members') {
                    removeItem(itemId, selectedResearchCommitteeMembers,
                        '#selected_committee_research_members');
                } else if (listSelector === 'selected_committee_validation_members') {
                    removeItem(itemId, selectedValidationCommitteeMembers,
                        '#selected_committee_validation_members');
                } else if (listSelector === 'selected_units_investigation') {
                    removeItem(itemId, selectedInvestigationUnits, '#selected_units_investigation');
                } else if (listSelector === 'selected_committee_experiment_members') {
                    removeItem(itemId, selectedExperimentCommitteeMembers,
                        '#selected_committee_experiment_members');
                } else if (listSelector === 'selected_committee_experiment_validation_members') {
                    removeItem(itemId, selectedExperimentValidationCommitteeMembers,
                        '#selected_committee_experiment_validation_members');
                } else if (listSelector === 'selected_units_experimentation') {
                    removeItem(itemId, selectedExperimentUnits, '#selected_units_experimentation');
                } else if (listSelector === 'selected_units_experiment_validation') {
                    removeItem(itemId, selectedExperimentValidationUnits,
                        '#selected_units_experiment_validation');
                }
            });

            $('form').on('submit', function(e) {


                                let validationMessages = [];

                // Validar que haya al menos un miembro en cada comité
                if (selectedResearchCommitteeMembers.length === 0) {
                    validationMessages.push(
                        'Debe seleccionar al menos un miembro del Comité de Investigación.');
                }
                if (selectedValidationCommitteeMembers.length === 0) {
                    validationMessages.push(
                        'Debe seleccionar al menos un miembro del Comité de Validación.');
                }


                // Validar que haya al menos una unidad seleccionada en cada sección
                if (selectedInvestigationUnits.length === 0) {
                    validationMessages.push(
                        'Debe seleccionar al menos una Unidad para el Comité de Investigación.'
                    );
                }
              

                // Mostrar mensajes de validación si existen errores
                if (validationMessages.length > 0) {
                    alert(validationMessages.join('\n'));
                    e.preventDefault(); // Evitar que el formulario se envíe
                    return;
                }

                // Actualizar inputs ocultos con los datos seleccionados
                $('#committee_research_members').val(JSON.stringify(selectedResearchCommitteeMembers.map(
                    member => member.id)));
                $('#committee_validation_members').val(JSON.stringify(selectedValidationCommitteeMembers
                    .map(member => member.id)));
                $('#committee_experiment_members').val(JSON.stringify(selectedExperimentCommitteeMembers
                    .map(member => member.id)));
                $('#committee_experiment_validation_members').val(JSON.stringify(
                    selectedExperimentValidationCommitteeMembers.map(member => member.id)));
                $('#military_units_investigation').val(JSON.stringify(selectedInvestigationUnits.map(unit =>
                    unit.id)));
                $('#military_units_experimentation').val(JSON.stringify(selectedExperimentUnits.map(unit =>
                    unit.id)));
                $('#military_units_experiment_validation').val(JSON.stringify(
                    selectedExperimentValidationUnits.map(unit => unit.id)));

            });

            //ELIMINAR MENSAJE DE ERROR
            setTimeout(function() {
                $('.alert').alert('close');
            }, 3000);

        });
    </script>
@stop
