@extends('adminlte::page')

@section('title', 'GENERACIÓN DE DOCTRINA')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">Manuales en Proceso de Generación de Doctrina</h1>
    </div>
@stop

@section('content')
    @php
        $ord = 1;
    @endphp

    <!-- Tabla DataTable -->
    <div class="card mt-1">
        <div class="card-body p-2">
            <!-- Botón para crear un nuevo manual -->
            <div class="d-flex justify-content-center mb-3">
                <a href="{{ route('manuals.newManual') }}" class="btn btn-info btn-sm shadow-sm px-3 py-1">
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Manual
                </a>
            </div>

            <table id="manualsTable" class="table table-bordered table-hover table-sm">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="font-size: 0.85rem;">Ord</th>
                        <th style="font-size: 0.85rem;">Fase</th>
                        <th class="text-center" style="font-size: 0.85rem;">Tipo</th>
                        <th style="font-size: 0.85rem;">Manuales y Reglamento</th>
                        <th style="font-size: 0.85rem;">Observaciones</th>
                        <th style="font-size: 0.85rem;">Actividad Actual</th>
                        <th class="text-center" style="font-size: 0.85rem;">Progreso</th>
                        <th class="text-center" style="font-size: 0.85rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                        <tr style="font-size: 0.8rem; height: 30px;">
                            <td class="text-center">{{ $ord++ }}</td>
                            <td>{{ $row['phase_name'] }}</td>
                            <td class="text-center">{{ $row['type_code'] }}</td>
                            <td>{{ $row['manual_name'] }}</td>
                            <td>{{ $row['observations'] }}</td>
                            <td>{{ $row['current_activity'] }}</td>
                            <td class="text-center">{{ $row['progress'] }}%</td>
                            <td class="text-center">
                                <a href="{{ route('manuals.editManual', $row['id']) }}"
                                    class="btn btn-secondary btn-sm px-2 py-1">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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

        /* Personalización de la tabla */
        .table th {
            text-align: center;
            background-color: #f8f9fa;
        }

        .table td,
        .table th {
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #e8f5e9;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            $('#manualsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' // Español
                },
                ordering: true,
                paging: true,
                searching: true
            });
        });
    </script>
@stop
