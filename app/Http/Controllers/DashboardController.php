<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\ManualPhaseSuphase;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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

        $secretaryCount = Manual::whereHas('manualPhaseSuphases', function ($query) {
            $query->whereHas('catalogSubphase', function ($subQuery) {
                $subQuery->where('suphase_name', 'ENVIADO GRUPO ASESOR');
            })->where('is_completed', 1);
        })->where('is_published', 0)->count();

        $edicionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EDICIÓN');
        })->count() - $publishedCount - $secretaryCount;

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
            $timeEdition = $this->calculateTimeEditionManual($manual->id);
            $daysEdition = $this->calculateDaysEditionManual($manual->id);

            return [
                'type_code' => $manual->manualType->code,
                'manual_name' => $manual->manual_name,
                'phase_name' => $manual->manualPhase->phase_name,
                'progress' => $progress, // Porcentaje de progreso
                'current_activity' => $currentActivity, // Actividad actual
                'time_edition' => $timeEdition,
                'days_edition' => $daysEdition,
                'id' => $manual->id,
            ];
        });
        // FIN DE SEGMENTO PARA OBTENER MANUALES

        // Formatear los datos para la vista
        $data = [
            ['title' => 'INVESTIGACIÓN', 'count' => $investigacionCount],
            ['title' => 'EXPERIMENTACIÓN', 'count' => $experimentacionCount],
            ['title' => 'EDICIÓN', 'count' => $edicionCount],
            ['title' => 'GRUPO ASESOR', 'count' => $secretaryCount],
            ['title' => 'PUBLICADOS', 'count' => $publishedCount],
            ['title' => 'TOTAL', 'count' => $totalCount],
        ];

        return view('dashboard', compact('data', 'manualData'));
    }

    //SECCION PARA GENERAR PDF
    public function generatePDFPrubea()
    {
        $pruebaPDF = "<h1>Hola, esta es una prueba q se imprimera en pdg</h1>";
        $pdf = PDF::loadHTML($pruebaPDF);
        return $pdf->download('prueba.pdf');
    }

    public function generatePDFManual()
    {
        // SEGMENTO PARA CONTAR LA CANTDAD DE MANUALES
        $investigacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'INVESTIGACIÓN');
        })->count();

        $experimentacionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EXPERIMENTACIÓN');
        })->count();

        $publishedCount = Manual::all()->where('is_published', true)->count();

        $secretaryCount = Manual::whereHas('manualPhaseSuphases', function ($query) {
            $query->whereHas('catalogSubphase', function ($subQuery) {
                $subQuery->where('suphase_name', 'ENVIADO GRUPO ASESOR');
            })->where('is_completed', 1);
        })->where('is_published', 0)->count();

        $edicionCount = Manual::whereHas('manualPhase', function ($query) {
            $query->where('phase_name', 'EDICIÓN');
        })->count() - $publishedCount - $secretaryCount;

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
            $timeEdition = $this->calculateTimeEditionManual($manual->id);
            $daysEdition = $this->calculateDaysEditionManual($manual->id);


            return [
                'type_code' => $manual->manualType->code,
                'manual_name' => $manual->manual_name,
                'phase_name' => $manual->manualPhase->phase_name,
                'progress' => $progress, // Porcentaje de progreso
                'current_activity' => $currentActivity, // Actividad actual
                'time_edition' => $timeEdition,
                'days_edition' => $daysEdition,
                'id' => $manual->id,
            ];
        });

        // Formatear los datos para la vista
        $data = [
            ['title' => 'INVESTIGACIÓN', 'count' => $investigacionCount],
            ['title' => 'EXPERIMENTACIÓN', 'count' => $experimentacionCount],
            ['title' => 'EDICIÓN', 'count' => $edicionCount],
            ['title' => 'GRUPO ASESOR', 'count' => $secretaryCount],
            ['title' => 'PUBLICADOS', 'count' => $publishedCount],
            ['title' => 'TOTAL', 'count' => $totalCount],
        ];

        //Capturar fecha de hoy, horario guayaquil  -5
        $fechaHoy = Carbon::now()->timezone('America/Guayaquil')->format('dMY-H:i');

        $pdf = PDF::loadView('report.dashboardPDF', compact('data', 'manualData', 'fechaHoy'))->setPaper('a4', 'portrait');
        return $pdf->download('Reporte_Manuales' .$fechaHoy . '.pdf');
    }



    //MÉTODOS FUNCIONALES, NO TOPAR
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

    function calculateTimeEditionManual($manualId)
    {
        // Obtener el manual para verificar su estado de publicación
        $manual = Manual::find($manualId);

        // Validar si el manual está publicado
        if ($manual && $manual->is_published) {
            return 'finalizado';
        }

        // Buscar la subfase específica con catalog_subphases.id = 1
        $subphase = ManualPhaseSuphase::where('manuals_id', $manualId)
            ->where('catalog_subphases_id', 1) // Filtrar por el id de catalog_subphases
            ->where('is_Completed', 1) // Asegurarse de que esté completada
            ->first();

        // Validar si no se encontró ninguna subfase
        if (!$subphase) {
            return 'No se ha realizado ninguna actividad ';
        }

        // Convertir la fecha de completación a un objeto Carbon
        $date = \Carbon\Carbon::parse($subphase->completation_date);
        $now = now();

        // Calcular la diferencia de tiempo
        $diff = $date->diff($now);

        // Formatear el resultado
        return $diff->format('%y años, %m meses y %d días');
    }

    function calculateDaysEditionManual($manualId)
    {
        // Obtener el manual para verificar su estado de publicación
        $manual = Manual::find($manualId);

        // Validar si el manual está publicado
        if ($manual && $manual->is_published) {
            return 0;
        }

        // Calcular la cantidad total de días usando Carbon
        $subphase = ManualPhaseSuphase::where('manuals_id', $manual->id)
            ->where('catalog_subphases_id', 1)
            ->where('is_Completed', 1)
            ->first();

        $days = 0;
        if ($subphase && !$manual->is_published) {
            $date = \Carbon\Carbon::parse($subphase->completation_date);
            $now = now();
            $days = $date->diffInDays($now); // Diferencia total en días
        }

        return $days;
    }

}
