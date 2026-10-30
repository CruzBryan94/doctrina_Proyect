<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ARTEMIS · Acceso al sistema')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/artemis.css') }}?v={{ filemtime(public_path('css/artemis.css')) }}">
</head>
<body class="artemis-ui auth-page">
    <div class="auth-shell">
        <section class="auth-intro" aria-labelledby="auth-title">
            <div class="auth-brand-surface">
                <a class="auth-brand" href="{{ route('login') }}" aria-label="ARTEMIS, inicio de sesión">
                    <img src="{{ asset('imagenes/Logo_pequeño.png') }}" alt="ARTEMIS">
                </a>
            </div>
            <div class="auth-intro-copy">
                <p class="auth-eyebrow">Sistema de control de generación de doctrina</p>
                <h1 id="auth-title">Seguimiento institucional de manuales y publicaciones militares</h1>
                <p class="auth-description">Planifica, coordina y consulta el avance de cada fase del proceso doctrinario.</p>
                <img class="auth-cover" src="{{ asset('imagenes/Logo de portada.png') }}" alt="Identidad institucional ARTEMIS con mapa y escena militar">
                <ul class="auth-phases" aria-label="Fases del proceso">
                    <li>Planificación</li><li>Investigación</li><li>Experimentación</li><li>Edición</li><li>CGFT</li>
                </ul>
            </div>
        </section>
        <main class="auth-content">
            <div class="auth-form-wrap">@yield('content')</div>
        </main>
    </div>
    @include('partials.alert-modal')
</body>
</html>
