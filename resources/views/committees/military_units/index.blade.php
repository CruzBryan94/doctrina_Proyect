@extends('layouts.legacy')

@section('title', 'ADMIN UNIDADES MILITARES')

@section('content_header')
    <div class="artemis-page-title mb-0">
        <span class="artemis-page-kicker">ARTEMIS · ADMINISTRACIÓN</span>
        <h1>Unidades militares</h1>
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
    <div class="d-flex justify-content-end mt-0 mb-3">
        <a href="{{ route('militaryUnits.newMilitaryUnit') }}" class="btn btn-primary btn-sm shadow-sm px-3 py-1">
            <i class="fas fa-plus-circle"></i> Crear Nueva Unidad Militar
        </a>
    </div>
    <div class="card artemis-data-list mt-1">
        <div class="card-body p-2">
            <div class="table-responsive">
            <table id="usersTable" class="table table-bordered table-hover table-sm artemis-data-table">
                <thead>
                    <tr>
                        <th class="text-center">ORD</th>
                        <th class="text-center">NOMBRE DE LA UNIDAD</th>
                        <th class="text-center">ABREVIATURA</th>
                        <th class="text-center">CANTIDAD DE MANUALES</th>
                        <th class="text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($militaryUnits as $i => $dato)
                        <tr>
                            <td class="text-center align-middle">
                                {{ $i + 1 }}
                            </td>
                            <td class="text-center align-middle">
                                {{ $dato->unit_name }}
                            </td>
                            <td class="align-middle text-center">
                                {{ $dato->unit_acronym }}
                            </td>
                            <td class="align-middle text-center">
                                {{ $dato->manuals }}
                            </td>
                            <td class="text-center align-middle">
                                <form action="{{ route('militaryUnits.edit', $dato->id) }}" method="POST">
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
    </div>

@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                paging: true,
                ordering: false,
                searching: true,
                language: {
                    emptyTable: 'No hay datos disponibles en la tabla',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros en total)',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    loadingRecords: 'Cargando...',
                    processing: 'Procesando...',
                    search: 'Buscar:',
                    zeroRecords: 'No se encontraron resultados',
                    paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
                    aria: { sortAscending: ': activar para ordenar la columna de forma ascendente', sortDescending: ': activar para ordenar la columna de forma descendente' }
                }
            });
        });

        //ELIMINAR MENSAJE DE ERROR
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    </script>
@stop
