@extends('adminlte::page')

@section('title', 'ADMIN MIEMBROS')

@section('content_header')
    <div class="title-container mb-0">
        <h1 class="dashboard-title">ADMINISTRACIÓN DE MIEMBROS DE COMITÉS</h1>
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
    <!-- Botón para crear un nuevo manual -->
    <div class="d-flex justify-content-center mt-0 mb-3">
        <a href="{{ route('members.newMember') }}" class="btn btn-info btn-sm shadow-sm px-3 py-1">
            <i class="fas fa-plus-circle"></i> Crear Nuevo Miembro
        </a>
    </div>
    <div class="card mt-1">
        <div class="card-body p-2">
            <table id="usersTable" class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th class="text-center">ORD</th>
                        <th class="text-center">GRADO</th>
                        <th class="text-center">APELLIDOS Y NOMBRES</th>
                        <th class="text-center">CÉDULA DE IDENTIDAD</th>
                        <th class="text-center">CANTIDAD DE MANUALES</th>>
                        <th class="text-center">ACCIONES</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($committeeMembers as $i => $dato)
                        <tr>
                            <td class="text-center align-middle">
                                {{ $i + 1 }}
                            </td>
                            <td class="text-center align-middle">
                                {{ $dato->grade }}
                            </td>
                            <td class="align-middle text-wrap">
                                {{ $dato->full_name }}
                            </td>
                            <td class="align-middle text-center">
                                {{ $dato->identification }}
                            </td>
                            <td class="align-middle text-center">
                                {{ $dato->manuals }}
                            <td class="text-center align-middle">
                                <form action="{{ route('members.edit', $dato->id) }}" method="POST">
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
