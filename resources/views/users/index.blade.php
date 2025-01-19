@extends('adminlte::page')

@section('title', 'ADMINISTRACIÓN DE USUARIOS')

@section('content_header')
    <div class="title-container">
        <h1 class="dashboard-title">ADMINISTRACIÓN DE USUARIOS</h1>
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
    @php
        $ord = 1;
    @endphp

    <!-- Tabla DataTable -->
    <div class="card mt-1">
        <div class="card-body p-2">
            <table id="usersTable" class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th class="text-center">ORD</th>
                        <th class="text-center">GRADO</th>
                        <th class="text-center">APELLIDOS Y NOMBRES</th>
                        <th class="text-center">CORREO</th>
                        <th class="text-center">FUNCIÓN</th>
                        <th class="text-center">ESTADO</th>
                        <th class="text-center">ACCIONES</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $i => $dato)
                        <tr>
                            <td class="text-center align-middle">
                                {{ $i + 1 }}
                            </td>
                            <td class="text-center align-middle">
                                {{ $dato->grade }}
                            </td>
                            <td class="align-middle text-wrap">
                                {{ $dato->name }}
                            </td>
                            <td class="align-middle text-wrap">
                                {{ $dato->email }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($dato->role == 'admin')
                                    <span class="badge badge-danger">Administrador</span>
                                @elseif ($dato->role == 'user')
                                    <span class="badge badge-success">Usuario</span>
                                @elseif ($dato->role == 'none')
                                    <span class="badge badge-secondary">Restringido</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if ($dato->is_active == 1)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>


                            <td class="text-center align-middle">
                                <form action="{{ route('users.edit', $dato->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm" data-toggle="modal">
                                        <span class="fas fa-user-edit"></span>
                                    </button>
                                </form>
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
            background: #a71111;
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

        $("#usersTable").dataTable({
            "paging": true,
            "ordering": false,
            "language": {
                url: 'https://cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json'
            },
            "searching": true
        });

        //ELIMINAR MENSAJE DE ERROR
        setTimeout(function() {
                $('.alert').alert('close');
            }, 3000);
    </script>
@stop
