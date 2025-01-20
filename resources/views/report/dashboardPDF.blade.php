<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Generación de Doctrina</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }

        .title-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .text-color-b {
            color: #202225;
        }

        .dashboard-title {
            font-size: 15px;
            font-weight: bold;
            color: #202225;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .dashboard-sub-title {
            font-size: 14px;
            font-weight: bold;
            color: #202225;
            letter-spacing: 1.2px;
        }

        .dashboard-title::after {
            content: "";
            display: block;
            width: 80px;
            height: 3px;
            background: #1a9620;
            margin: 8px auto 0;
            border-radius: 2px;
        }

        .info-box {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            padding: 3px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-box-icon {
            font-size: 14px;
            margin-right: 10px;
            padding: 10px;
            color: #fff;
            border-radius: 50%;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-text {
            font-size: 12px;
            font-weight: bold;
        }

        .info-box-number {
            font-size: 14px;
            font-weight: bold;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 11px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }

        .table th {
            background-color: #f8f9fa;
            font-size: 12px;
        }

        .table-hover tbody tr:hover {
            background-color: #e8f5e9;
        }
    </style>
</head>

<body>
    <div class="title-container">
        <h1 class="dashboard-title">CEDMT - DEPARTAMENTO DE DOCTRINA</h1>
        <h1 class="dashboard-sub-title">REPORTE DE LOS MANUALES EN DESARROLLO DEL DEPARTAMENTO DE GENERACIÓN DE DOCTRINA
        </h1>
    </div>

    <div class="title-container">
        <h1 class="dashboard-sub-title text-color-b">Consolidado de Manuales</h1>
    </div>

    <!-- Tarjetas dinámicas organizadas en una tabla de 2 filas y 3 columnas -->
    <table style="width: 100%; border-spacing: 1px; text-align: center;">
        <tbody>
            @php
                $icons = [
                    'fas fa-search',
                    'fas fa-flask',
                    'fas fa-edit',
                    'fas fa-search-location',
                    'fas fa-tasks',
                    'fas fa-cog',
                ];
                $colors = ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#6c757d', '#dc3545'];
                $dataChunks = collect($data)->chunk(3);
            @endphp

            @foreach ($dataChunks as $row)
                <tr>
                    @foreach ($row as $index => $item)
                        <td style="padding: 5px; vertical-align: top;">
                            <div class="info-box"
                                style="text-align: center; border: 1px solid #ccc; border-radius: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); padding: 5px;">
                                {{-- <div class="info-box-icon" style="background-color: {{ $colors[$index % count($colors)] }}; color: white; font-size: 1rem; width: 20px; height: 20px; line-height: 20px; margin: 0 auto; border-radius: 50%;">
                        <i class="{{ $icons[$index % count($icons)] }}"></i>
                    </div> --}}
                                <div class="info-box-content" style="margin-top: 5px;">
                                    <span class="info-box-text text-color-b"
                                        style="font-size: 12px; font-weight: bold;">{{ $item['title'] }}: </span>
                                    <span class="info-box-number text-color-b"
                                        style="font-size: 12px;">{{ $item['count'] }}</span>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="title-container">
        <h1 class="dashboard-sub-title text-color-b">Detalle de Manuales</h1>
    </div>


    <!-- Tabla dinámica -->
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Ord</th>
                <th>Fase</th>
                <th>Tipo</th>
                <th>Manuales y Reglamentos</th>
                <th>Actividad</th>
                <th>Porcentaje</th>
                <th>Tiempo de Edición</th>
            </tr>
        </thead>
        <tbody>
            @php $ord = 1; @endphp
            @foreach ($manualData as $row)
                <tr>
                    <td>{{ $ord++ }}</td>
                    <td
                        @if ($row['phase_name'] === 'Investigación') style="background-color: rgba(0, 123, 255, 0.3);"
                        @elseif ($row['phase_name'] === 'Experimentación')
                            style="background-color: rgba(40, 167, 69, 0.3);"
                        @elseif ($row['phase_name'] === 'Edición')
                            style="background-color: rgba(255, 193, 7, 0.3);" @endif>
                        {{ $row['phase_name'] }}
                    </td>
                    <td>{{ $row['type_code'] }}</td>
                    <td>{{ $row['manual_name'] }}</td>
                    <td>{{ $row['current_activity'] }}</td>
                    <td @if ($row['progress'] == 100) style="background-color: rgba(21, 200, 63, 0.333);" @endif>
                        {{ $row['progress'] }}%
                    </td>
                    <td @if ($row['days_edition'] > 365) style="background-color: rgba(249, 48, 48, 0.3);" @endif>
                        {{ $row['time_edition'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <div class="text-color-b" style="margin-top: 8px;">Reporte Generado el: {{ $fechaHoy }}</div>
</body>

</html>
