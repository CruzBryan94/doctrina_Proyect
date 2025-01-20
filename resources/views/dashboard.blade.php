@extends('adminlte::page')

@section('title', 'GENERACIÓN DE DOCTRINA')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">CONTROL DEL AVANCE DE LA GENERACIÓN DE DOCTRINA</h1>
    </div>
@stop

@section('content')

    <div class="row">
        @php
            $icons = ['fas fa-search', 'fas fa-flask', 'fas fa-edit', 'fas fa-search-location', 'fas fa-tasks'];
            $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-secondary', 'bg-danger'];
            $ord = 1;
        @endphp

        @foreach ($data as $index => $item)
            <div class="col">
                <div class="info-box">
                    <!-- Ícono con color dinámico -->
                    <span class="info-box-icon {{ $colors[$index % count($colors)] }} fs-1">
                        <i class="{{ $icons[$index % count($icons)] }} "></i>
                    </span>

                    <!-- Contenido de la tarjeta -->
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold" style="font-size: 1.2rem;">
                            {{ $item['title'] }}
                        </span>
                        <span class="info-box-number font-weight-bold" style="font-size: 1.5rem;">
                            {{ $item['count'] }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Tabla DataTable -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title font-weight-bold">Avance de Manuales y Reglamentos
                <a href="{{ route('dashboard.generatePDFManual') }}" class="btn btn-secondary btn-sm px-2 py-1">
                    <i class="fas fa-print"></i> Imprimir Reporte
                </a>
            </h3>
        </div>
        <div class="card-body">
            <table id="manualsTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="text-center">Ord</th>
                        <th class="text-center">Fase</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Manuales y Reglamentos</th>
                        <th class="text-center">Actividad</th>
                        <th class="text-center">Porcentaje</th>
                        <th class="text-center">Tiempo de Edición</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manualData as $row)
                        <tr>
                            <!-- Columna de Número -->
                            <td class="text-center">{{ $ord++ }}</td>

                            <!-- Columna de Fase -->
                            <td
                                @if ($row['phase_name'] === 'Investigación') style="background-color: rgba(0, 123, 255, 0.3);"
                                @elseif ($row['phase_name'] === 'Experimentación')
                                    style="background-color: rgba(40, 167, 69, 0.3);"
                                @elseif ($row['phase_name'] === 'Edición')
                                    style="background-color: rgba(255, 193, 7, 0.3);" @endif>
                                {{ $row['phase_name'] }}
                            </td>

                            <!-- Columna de Tipo -->
                            <td class="text-center">{{ $row['type_code'] }}</td>

                            <!-- Columna de Manuales -->
                            <td>{{ $row['manual_name'] }}</td>

                            <!-- Columna de Actividad -->
                            <td>{{ $row['current_activity'] }}</td>

                            <!-- Columna de Porcentaje -->
                            <td class="text-center"
                                @if ($row['progress'] == 100) style="background-color: rgba(21, 200, 63, 0.333);" @endif>
                                {{ $row['progress'] }}%
                            </td>

                            <!-- Columna de Tiempo de Edición -->
                            <td @if ($row['days_edition'] > 365) style="background-color: rgba(249, 48, 48, 0.3);" @endif>
                                {{ $row['time_edition'] }}
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
        /* Aumentar el tamaño de los textos y darle más énfasis */
        .info-box .info-box-text {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .info-box .info-box-number {
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Suavizar el hover de las tarjetas */
        .info-box {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

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
