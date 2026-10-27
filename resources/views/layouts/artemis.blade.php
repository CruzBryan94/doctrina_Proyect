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
<body class="artemis-ui">
<a class="skip-link" href="#main">Ir al contenido</a>
<aside class="app-sidebar" id="app-sidebar" aria-label="Menú principal" aria-hidden="false">
    <a class="app-brand" href="{{ route('welcome') }}"><img class="app-brand-logo" src="{{ asset('imagenes/Logo_pequeño.png') }}" alt="ARTEMIS"></a>
    <div class="nav-caption">ESPACIO DE TRABAJO</div>
    <nav id="app-sidebar-nav" aria-label="Navegación principal">
        @can('dashboard.view')<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-label="Panel general" title="Panel general"><i class="fas fa-chart-pie" aria-hidden="true"></i><span class="app-nav-label">Panel general</span></a>@endcan
        @can('dashboard.view')<a class="{{ request()->routeIs('dashboard.history') ? 'active' : '' }}" href="{{ route('dashboard.history') }}" aria-label="Productividad histórica" title="Productividad histórica"><i class="fas fa-history" aria-hidden="true"></i><span class="app-nav-label">Productividad histórica</span></a>@endcan
        @can('dashboard.view')<a class="{{ request()->routeIs('dashboard.schedule') ? 'active' : '' }}" href="{{ route('dashboard.schedule') }}" aria-label="Cronograma" title="Cronograma"><i class="fas fa-calendar-alt" aria-hidden="true"></i><span class="app-nav-label">Cronograma</span></a>@endcan
        @can('viewAny', App\Models\Manual::class)<a class="{{ request()->routeIs('references.*') ? 'active' : '' }}" href="{{ route('references.index') }}" aria-label="Consistencia por mapa" title="Consistencia por mapa"><i class="fas fa-balance-scale" aria-hidden="true"></i><span class="app-nav-label">Consistencia por mapa</span></a>@endcan
        @can('viewAny', App\Models\Manual::class)<a class="{{ request()->routeIs('manuals.*') ? 'active' : '' }}" href="{{ route('manuals.index') }}" aria-label="Matriz de manuales" title="Matriz de manuales"><i class="fas fa-th" aria-hidden="true"></i><span class="app-nav-label">Matriz de manuales</span></a>@endcan
        @can('reports.view')<a href="{{ route('reports.manuals') }}" aria-label="Descargar reporte" title="Descargar reporte"><i class="fas fa-file-pdf" aria-hidden="true"></i><span class="app-nav-label">Descargar reporte</span></a>@endcan
        @can('users.manage')<div class="nav-caption">ADMINISTRACIÓN</div><a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" aria-label="Usuarios y roles" title="Usuarios y roles"><i class="fas fa-users" aria-hidden="true"></i><span class="app-nav-label">Usuarios y roles</span></a>@endcan
        @if(auth()->user()->is_active && auth()->user()->hasRole('admin'))<a class="{{ request()->routeIs('phase-durations.*') ? 'active' : '' }}" href="{{ route('phase-durations.index') }}" aria-label="Plazos por fase" title="Plazos por fase"><i class="fas fa-clock" aria-hidden="true"></i><span class="app-nav-label">Plazos por fase</span></a>@endif
        @can('catalogs.manage')<a href="{{ route('members.index') }}" aria-label="Miembros de comités" title="Miembros de comités"><i class="fas fa-user-friends" aria-hidden="true"></i><span class="app-nav-label">Miembros de comités</span></a><a href="{{ route('militaryUnits.index') }}" aria-label="Unidades militares" title="Unidades militares"><i class="fas fa-building" aria-hidden="true"></i><span class="app-nav-label">Unidades militares</span></a>@endcan
    </nav>
    <div class="sidebar-foot"><span class="status-dot"></span> Control del proceso doctrinario<small>Planificar · Investigar · Publicar</small></div>
</aside>
<button class="app-sidebar-backdrop" type="button" data-sidebar-backdrop aria-label="Cerrar menú" hidden></button>
<div class="app-workspace">
    <header class="app-topbar"><div class="app-topbar-start"><button class="app-sidebar-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="true" aria-label="Contraer menú" title="Contraer menú"><i class="fas fa-bars" aria-hidden="true"></i></button><span class="topbar-context">CEDMFT <span>/</span> Sistema de doctrina</span></div><div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}<small>{{ auth()->user()->getRoleNames()->join(' · ') ?: 'Sin acceso' }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" aria-label="Cerrar sesión" title="Cerrar sesión"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></button></form></div></header>
    <main id="main" class="app-main">
        @yield('content')
    </main>
    <footer class="app-footer">ARTEMIS <span>Seguimiento de manuales y publicaciones militares</span></footer>
</div>
@include('partials.alert-modal')
<script src="{{ asset('js/artemis-sidebar.js') }}?v={{ filemtime(public_path('js/artemis-sidebar.js')) }}" defer></script>
@stack('scripts')
</body>
</html>

