@extends('adminlte::page')

@section('title', 'NUEVO MANUAL')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">ADMINISTRACIÓN: {{ $manual->manual_name }}</h1>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form id="manualForm" action="{{ route('manuals.update', $manual->id) }}" method="POST">
            @csrf
            @method('PUT')

            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th class="text-center">INVESTIGACIÓN</th>
                        <th class="text-center">CUMPLIDO</th>
                        <th class="text-center">FECHA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($researchPhases as $researchPhase)
                        @php
                            $subphaseData = $researchPhase->manualPhaseSuphases->first();
                        @endphp

                        <tr>
                            <td>{{ $researchPhase->suphase_name }}</td>
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    name="phases[{{ $researchPhase->id }}][is_completed]"
                                    value="1"
                                    class="completion-checkbox"
                                    data-id="{{ $researchPhase->id }}"
                                    {{ $subphaseData && $subphaseData->is_completed ? 'checked' : '' }}
                                >
                            </td>
                            <td class="text-center">
                                <input
                                    type="date"
                                    name="phases[{{ $researchPhase->id }}][completation_date]"
                                    class="form-control completion-date"
                                    data-id="{{ $researchPhase->id }}"
                                    value="{{ $subphaseData ? $subphaseData->completation_date : '' }}"
                                    {{ $subphaseData && $subphaseData->is_completed ? '' : 'disabled' }}
                                >
                                <small class="text-danger d-none" id="error-{{ $researchPhase->id }}">Debe seleccionar una fecha si está marcado como cumplido.</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-between mt-3">
                <!-- Botón de Regresar -->
                <a href="{{ route('manuals.index') }}" class="btn btn-danger">Regresar sin Guardar</a>

                <!-- Botón de Guardar -->
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
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
        .table-sm td, .table-sm th {
            padding: 5px !important;
            vertical-align: middle !important;
        }

        /* Título principal */
        .dashboard-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Habilitar/deshabilitar fecha de cumplimiento basado en el estado de la casilla de verificación
            $('.completion-checkbox').on('change', function() {
                const id = $(this).data('id');
                const isChecked = $(this).is(':checked');

                const dateInput = $(`.completion-date[data-id="${id}"]`);
                const errorText = $(`#error-${id}`);

                if (isChecked) {
                    dateInput.prop('disabled', false);
                } else {
                    dateInput.prop('disabled', true).val('');
                    errorText.addClass('d-none');
                }
            });

            // Validar fecha de cumplimiento antes de enviar el formulario
            $('#manualForm').on('submit', function(e) {
                let isValid = true;

                $('.completion-checkbox').each(function() {
                    const id = $(this).data('id');
                    const isChecked = $(this).is(':checked');
                    const dateInput = $(`.completion-date[data-id="${id}"]`);
                    const errorText = $(`#error-${id}`);

                    if (isChecked && !dateInput.val()) {
                        isValid = false;
                        errorText.removeClass('d-none');
                    } else {
                        errorText.addClass('d-none');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Por favor, asegúrese de que todas las fases cumplidas tengan una fecha de cumplimiento.');
                }
            });
        });
    </script>
@stop
