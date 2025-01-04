<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Manual;

class DashboardController extends Controller
{
    public function index()
    {
        // Contar la cantidad de manuales en cada fase
        $investigacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'INVESTIGACIÓN');
        })->count();

        $experimentacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EXPERIMENTACIÓN');
        })->count();

        //POR AHORA ES ESTÁTICO, LOS MANUALES PUBLICADOS
        $publishedCount = Manual::all()->where('is_published', true)->count();


        $edicionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EDICIÓN');
        })->count()-$publishedCount;

        // Calcular el total de todos los manuales
        $totalCount = Manual::count();



        // Formatear los datos para la vista
        $data = [
            ['title' => 'INVESTIGACIÓN', 'count' => $investigacionCount],
            ['title' => 'EXPERIMENTACIÓN', 'count' => $experimentacionCount],
            ['title' => 'EDICIÓN', 'count' => $edicionCount],
            ['title' => 'PUBLICADOS', 'count' => $publishedCount],
            ['title' => 'TOTAL', 'count' => $totalCount],
        ];

        return view('dashboard', compact('data'));
    }
}
