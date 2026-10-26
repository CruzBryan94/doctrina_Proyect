@extends('layouts.legacy')

@section('title', 'ADMIN MIEMBROS')

@section('content_header')
    <div class="artemis-page-title">
        <span class="artemis-page-kicker">ARTEMIS · ADMINISTRACIÓN</span>
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
    <div class="card artemis-data-list mt-1">
        <div class="card-body">
            <div class="artemis-list-toolbar">
                <p class="artemis-list-summary">Consulta los integrantes registrados y su participación en manuales.</p>
                <a href="{{ route('members.import') }}" class="btn btn-outline-secondary btn-sm">Importar CSV</a>
                <a href="{{ route('members.newMember') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Crear miembro
                </a>
            </div>
            <form method="GET" action="{{ route('members.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-7 mb-0">
                    <label for="member-search">Buscar por cédula, apellidos o nombres</label>
                    <input id="member-search" class="form-control" type="search" name="q" maxlength="100" value="{{ request('q') }}">
                </div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Buscar</button> <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">Limpiar</a></div>
            </form>
            <p role="status">{{ $committeeMembers->total() }} integrantes encontrados. Se muestran hasta 25 por página.</p>
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
                    @forelse ($committeeMembers as $i => $dato)
                        <tr>
                            <td class="text-center align-middle">
                                {{ $committeeMembers->firstItem() + $i }}
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
                    @empty
                        <tr><td colspan="7" class="text-center">No se encontraron integrantes con estos criterios.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            {{ $committeeMembers->links('pagination::bootstrap-4') }}
        </div>
    </div>

@stop
