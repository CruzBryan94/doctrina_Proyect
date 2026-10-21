<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ARTEMIS') · ARTEMIS</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/artemis.css') }}?v={{ filemtime(public_path('css/artemis.css')) }}">
    @stack('styles')
</head>
<body>
<a class="skip-link" href="#main">Ir al contenido</a>
<aside class="app-sidebar">
    <a class="app-brand" href="{{ route('welcome') }}"><span class="brand-mark">A</span><span>ARTEMIS<small>GESTIÓN DE DOCTRINA</small></span></a>
    <div class="nav-caption">ESPACIO DE TRABAJO</div>
    <nav aria-label="Navegación principal">
        @can('dashboard.view')<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="fas fa-chart-pie" aria-hidden="true"></i> Panel general</a>@endcan
        @can('viewAny', App\Models\Manual::class)<a class="{{ request()->routeIs('manuals.*') ? 'active' : '' }}" href="{{ route('manuals.index') }}"><i class="fas fa-th" aria-hidden="true"></i> Matriz de manuales</a>@endcan
        @can('reports.view')<a href="{{ route('reports.manuals') }}"><i class="fas fa-file-pdf" aria-hidden="true"></i> Descargar reporte</a>@endcan
        @can('users.manage')<div class="nav-caption">ADMINISTRACIÓN</div><a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="fas fa-users" aria-hidden="true"></i> Usuarios y roles</a>@endcan
        @can('catalogs.manage')<a href="{{ route('members.index') }}"><i class="fas fa-user-friends" aria-hidden="true"></i> Miembros de comités</a><a href="{{ route('militaryUnits.index') }}"><i class="fas fa-building" aria-hidden="true"></i> Unidades militares</a>@endcan
    </nav>
    <div class="sidebar-foot"><span class="status-dot"></span> Control del proceso doctrinario<small>Planificar · Investigar · Publicar</small></div>
</aside>
<div class="app-workspace">
    <header class="app-topbar"><span class="topbar-context">CEDMFT <span>/</span> Sistema de doctrina</span><div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}<small>{{ auth()->user()->getRoleNames()->join(' · ') ?: 'Sin acceso' }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" aria-label="Cerrar sesión" title="Cerrar sesión"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></button></form></div></header>
    <main id="main" class="app-main">
        @yield('content')
    </main>
    <footer class="app-footer">ARTEMIS <span>Seguimiento de manuales y publicaciones militares</span></footer>
</div>
@include('partials.alert-modal')
@stack('scripts')
</body>
</html>

