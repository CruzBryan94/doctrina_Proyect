 @extends('adminlte::page')

 @section('title', 'GENERACIÓN DE DOCTRINA')

 @section('content_header')
     <div class="container mt-0">
         <div class="row justify-content-center">
             <div class="col-md-10">
                 <div class="text-center p-2 bg-info text-white rounded shadow">
                     <h2 class="display-10">SISTEMA DE DOCTRINA - CEDMFT</h2>
                     <h3 class="mt-1">Bienvenido al sistema de control de avance de manuales Doctrinarios</h3>
                 </div>
             </div>
         </div>
     </div>
 @stop


 @section('content')
     <div class="container mt-0">
         <div class="row justify-content-center">
             <!-- Tarjeta de acceso denegado -->
             @php
             $user = auth()->user(); // Obteniendo el usuario autenticado
             $rol = $user->getRoleNames()->first();

             @endphp
             @if ($rol == 'none')
                 <div class="col-md-8">
                     <div class="card border-danger mb-4">
                         <div class="card-header bg-danger text-white text-center">
                             <h4>Acceso Denegado</h4>
                         </div>
                         <div class="card-body">
                             <p class="text-center">
                                 Usted actualmente no tiene permiso para acceder a este sitio. Por favor, solicite los
                                 permisos
                                 al administrador.
                             </p>
                         </div>
                     </div>
                 </div>
             @endif

             <!-- Tarjeta de descripción del sistema -->
             <div class="col-md-8">
                 <div class="card border-info mb-4">
                     <div class="card-header bg-primary text-white text-center">
                         <h4>Acerca del Sistema</h4>
                     </div>
                     <div class="card-body">
                         <p>
                             Este sistema está diseñado para automatizar el control del avance en la actualización o
                             generación de nuevos proyectos doctrinarios.
                             Permite una gestión eficiente de las fases de investigación, experimentación, edición y
                             publicación.
                         </p>
                     </div>
                 </div>
             </div>

             <!-- Tarjeta con imagen -->
             <div class="col-md-8">
                 <div class="card">
                     <div class="card-body text-center">
                         <img src="{{ asset('vendor/adminlte/dist/img/fondo CEDMT.jpg') }}" alt="Logo CEDMT"
                             class="img-fluid rounded shadow">
                     </div>
                 </div>
             </div>
         </div>
     </div>

 @stop

 @section('css')
     {{-- Add here extra stylesheets --}}
     {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
 @stop

 @section('js')
     <script>
         console.log("Hi, I'm using the Laravel-AdminLTE package!");
     </script>
 @stop
