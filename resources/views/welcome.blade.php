 {{-- @extends('adminlte::page', ['iFrameEnabled' => true]) --}}
 @extends('adminlte::page')

 @section('title', 'GENERACIÓN DE DOCTRINA')

 @section('content_header')
     <h1>Dashboard REPOSITORIO MANUALES CEDFT</h1>
 @stop

 @section('content')
     <p>Aquí se visualiza los reportes de los manuales.</p>


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
