<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Manual;
use App\Models\ManualPhaseSuphase;

class DashboardController extends Controller
{
    public function index()
    {
        // SEGMENTO PARA CONTAR LA CANTDAD DE MANUALES
        $investigacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'INVESTIGACIÓN');
        })->count();

        $experimentacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EXPERIMENTACIÓN');
        })->count();

        $publishedCount = Manual::all()->where('is_published', true)->count();

        $edicionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EDICIÓN');
        })->count() - $publishedCount;

        $totalCount = Manual::count();
        // FIN DE SEGMENTO PARA CONTAR LOS MANUALES

        //INICIO DE SEGMENTO PARA OBTENER MANUALES

        $manuals = Manual::with(['manualType', 'manualPhase'])
            ->orderBy('manual_phases_id')
            ->orderBy('manual_name')
            ->where('is_active', true)
            ->get();

        // Formateamos los datos para la vista
        $manualData = $manuals->map(function ($manual) {
            $progress = $this->calculateProgress($manual->id, $manual->manual_phases_id);
            $currentActivity = $this->calculateActivityProgress($manual->id);

            return [
                'type_code' => $manual->manualType->code,
                'manual_name' => $manual->manual_name,
                'phase_name' => $manual->manualPhase->phase_name,
                'progress' => $progress, // Porcentaje de progreso
                'current_activity' => $currentActivity, // Actividad actual
                'id' => $manual->id,
            ];
        });
        // FIN DE SEGMENTO PARA OBTENER MANUALES


        // Formatear los datos para la vista
        $data = [
            ['title' => 'INVESTIGACIÓN', 'count' => $investigacionCount],
            ['title' => 'EXPERIMENTACIÓN', 'count' => $experimentacionCount],
            ['title' => 'EDICIÓN', 'count' => $edicionCount],
            ['title' => 'PUBLICADOS', 'count' => $publishedCount],
            ['title' => 'TOTAL', 'count' => $totalCount],
        ];

        return view('dashboard', compact('data','manualData'));
    }

    function calculateProgress($manualId, $idPhase)
    {
        $subphases = ManualPhaseSuphase::where('manuals_id', $manualId)
            ->where('manual_phases_id', $idPhase)
            ->get();

        $countSubphases = $subphases->count();

        if ($countSubphases === 0) {
            return 0; //
        }

        $subphasesCompleted = $subphases->where('is_completed', 1)->count();

        $totalProgress = (($subphasesCompleted / $countSubphases) * 100);

        return number_format($totalProgress, 2);
    }

    function calculateActivityProgress($manualId)
    {
        $subphase = ManualPhaseSuphase::where('manuals_id', $manualId)
            ->where('is_Completed', 1)
            ->with('catalogSubphase')
            ->get()->last();

        if (!$subphase) {
            return 'No se ha realizado ninguna actividad';
        }

        return $subphase->catalogSubphase->suphase_name;
    }
}
