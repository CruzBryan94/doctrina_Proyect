@extends('adminlte::page')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/artemis.css') }}?v={{ filemtime(public_path('css/artemis.css')) }}">
@endpush
@push('js')
    @include('partials.alert-modal')
@endpush
