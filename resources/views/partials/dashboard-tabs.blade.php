<nav class="phase-tabs dashboard-tabs" aria-label="Secciones del dashboard">
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) class="active" aria-current="page" @endif><i class="fas fa-chart-pie" aria-hidden="true"></i> Situación actual</a>
    <a href="{{ route('dashboard.history') }}" @if(request()->routeIs('dashboard.history')) class="active" aria-current="page" @endif><i class="fas fa-chart-line" aria-hidden="true"></i> Productividad</a>
    <a href="{{ route('dashboard.schedule') }}" @if(request()->routeIs('dashboard.schedule')) class="active" aria-current="page" @endif><i class="fas fa-calendar-alt" aria-hidden="true"></i> Proyección</a>
    <a href="{{ route('references.index') }}" @if(request()->routeIs('references.*')) class="active" aria-current="page" @endif><i class="fas fa-balance-scale" aria-hidden="true"></i> Consistencia</a>
</nav>
