@extends('layouts.legacy')

@section('title', 'ADMIN MIEMBROS')

@section('content_header')
    <div class="artemis-page-title">
        <span class="artemis-page-kicker">ARTEMIS COMMAND NEXUS · ADMINISTRACIÓN</span>
        <h1>Miembros de comités</h1>
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
    <div class="card artemis-data-list mt-1">
        <div class="card-body">
            <div class="artemis-list-toolbar">
                <p class="artemis-list-summary">Consulta los integrantes registrados y su participación en manuales.</p>
                <a href="{{ route('members.newMember') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Crear miembro
                </a>
            </div>
            <div class="table-responsive">
            <table id="usersTable" class="table table-bordered table-hover table-sm artemis-data-table">
                <caption class="sr-only">Miembros de comités registrados, grado, datos de contacto y acciones disponibles.</caption>
                <thead>
                    <tr>
                        <th scope="col" class="text-center">Ord</th>
                        <th scope="col" class="text-center">Grado</th>
                        <th scope="col">Apellidos y nombres</th>
                        <th scope="col" class="text-center">Cédula de identidad</th>
                        <th scope="col" class="text-center">Celular</th>
                        <th scope="col" class="text-center">Cantidad de manuales</th>
                        <th scope="col" class="text-center">Acciones</th>

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
                                {{ $dato->cellphone ?? 'Sin registrar' }}
                            </td>
                            <td class="align-middle text-center">
                                {{ $dato->manuals }}
                            </td>
                            <td class="text-center align-middle">
                                <form action="{{ route('members.edit', $dato->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-outline-secondary btn-sm" aria-label="Editar a {{ $dato->full_name }}">
                                        <span class="fas fa-user-edit" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

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
