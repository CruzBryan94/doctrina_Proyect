@extends('layouts.legacy')

@section('title', 'IMPORTAR INTEGRANTES')

@section('content_header')
    <h1>Importar integrantes desde CSV</h1>
@stop

@section('content')
    <a href="{{ route('members.index') }}" class="btn btn-outline-secondary mb-3">Volver al catálogo</a>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if (session('import_result'))
        @php($result = session('import_result'))
        <div class="alert alert-success" role="status">
            Importación terminada: {{ $result['inserted'] }} incorporados, {{ $result['omitted'] }} omitidos y {{ $result['errors'] }} filas con errores sin incorporar.
        </div>
    @endif
    <div class="card"><div class="card-body">
        <p>Archivo UTF-8 con cabecera <code>cedula,nombre,grado_codigo,celular</code>. Se admite coma o punto y coma. Celular es opcional; conserve su columna. Máximo 8 MB y 30.000 filas por archivo.</p>
        <p>La cédula debe contener 10 dígitos y se conserva como texto, incluidos los ceros iniciales. Los integrantes existentes no se modifican. Las filas con errores y repetidas se omiten. La importación no crea cuentas de acceso.</p>
        <details class="mb-3"><summary>Códigos de grado disponibles</summary>
            <ul>@foreach ($grades as $grade)<li>{{ $grade->code }}: {{ $grade->grade_name }}</li>@endforeach</ul>
        </details>
        <form method="POST" action="{{ route('members.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group"><label for="csv">Archivo CSV</label><input type="file" id="csv" name="csv" accept=".csv,.txt" class="form-control-file" required></div>
            <button class="btn btn-primary" type="submit">Revisar archivo</button>
        </form>
    </div></div>
    @if ($preview)
        <div class="card"><div class="card-body">
            <h2 class="h4">Revisión previa</h2>
            <p role="status">{{ $preview['summary']['new'] }} nuevos; {{ $preview['summary']['existing'] }} existentes; {{ $preview['summary']['duplicate'] }} repetidos en el archivo; {{ $preview['summary']['error'] }} con errores.</p>
            <p>Revise las filas antes de incorporar. La revisión está disponible durante una hora. Si otra importación agrega una cédula antes de confirmar, también se omitirá.</p>
            <div class="table-responsive"><table class="table table-sm table-bordered">
                <caption>Filas del archivo revisado</caption>
                <thead><tr><th scope="col">Fila</th><th scope="col">Cédula</th><th scope="col">Nombre</th><th scope="col">Grado</th><th scope="col">Celular</th><th scope="col">Resultado</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr><td>{{ $row['line'] }}</td><td>{{ $row['data']['identification'] }}</td><td>{{ $row['data']['full_name'] }}</td>
                            <td>{{ $grades->firstWhere('id', $row['data']['grades_id'])?->grade_name ?? 'Sin grado válido' }}</td>
                            <td>{{ $row['data']['cellphone'] ?? 'Sin registrar' }}</td>
                            <td>{{ ['new' => 'Nuevo', 'existing' => 'Existente: se omite', 'duplicate' => 'Repetido: se omite', 'error' => 'Error: se omite'][$row['status']] }}
                                @if ($row['errors'])<ul class="mb-0">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                            </td></tr>
                    @endforeach
                </tbody>
            </table></div>
            {{ $rows->links('pagination::bootstrap-4') }}
            <form method="POST" action="{{ route('members.import.commit') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <button type="submit" class="btn btn-primary" @disabled($preview['summary']['new'] === 0)>Incorporar {{ $preview['summary']['new'] }} integrantes nuevos</button>
            </form>
        </div></div>
    @endif
@stop
