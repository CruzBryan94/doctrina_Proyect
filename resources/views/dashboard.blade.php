@extends('adminlte::page')

 @section('title', 'GENERACIÓN DE DOCTRINA')

 @section('content_header')
     <h1>Dashboard REPOSITORIO MANUALES CEDFT</h1>
 @stop

 @section('content')

<div class="row">
    <div class="small-box bg-info">
        <div class="inner">
          <h3>150</h3>
          <p>New Orders</p>
        </div>
        <div class="icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <a href="#" class="small-box-footer">
          More info <i class="fas fa-arrow-circle-right"></i>
        </a>
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
