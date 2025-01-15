<?php

namespace App\Http\Controllers;

use App\Models\CatalogSubphase;
use Illuminate\Http\Request;
use App\Models\Manual;
use App\Models\ManualCommitteeMember;
use App\Models\ManualMilitaryUnit;
use App\Models\ManualPhaseSuphase;
use App\Models\MilitaryUnit;
use App\Models\CommitteeMember;
use App\Models\ManualType;


class ManualController extends Controller
{

    public function index()
    {
        // Obtenemos todos los manuales con sus relaciones necesarias
        $manuals = Manual::with(['manualType', 'manualPhase'])
            ->orderBy('manual_phases_id')
            ->orderBy('manual_name')
            ->where('is_active', true)
            ->get();

        // Formateamos los datos para la vista
        $data = $manuals->map(function ($manual) {
            $progress = $this->calculateProgress($manual->id, $manual->manual_phases_id);
            $currentActivity = $this->calculateActivityProgress($manual->id);

            return [
                'type_code' => $manual->manualType->code,
                'manual_name' => $manual->manual_name,
                'phase_name' => $manual->manualPhase->phase_name,
                'observations' => $manual->observations,
                'progress' => $progress, // Porcentaje de progreso
                'current_activity' => $currentActivity, // Actividad actual
                'id' => $manual->id,
            ];
        });

        return view('manuals.index', compact('data'));
    }


    public function newManual()
    {
        $manualTypes = \App\Models\ManualType::all(['id', 'code', 'type_name']);
        $militaryUnits = \App\Models\MilitaryUnit::all(['id', 'unit_acronym', 'unit_name']);


        $committeeMembers = \App\Models\CommitteeMember::with('grade')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'grade' => $member->grade->grade_name,
                    'full_name' => $member->full_name,
                    'identification' => $member->identification,
                ];
            });

        return view('manuals.newManual', compact('militaryUnits', 'committeeMembers', 'manualTypes'));
    }

    public function store(Request $request)
    {
        // Validar los datos
        $validatedData = $request->validate([
            'manual_name' => 'required|string|max:100',
            'committee_research_members' => 'required|json',
            'committee_validation_members' => 'required|json',
            'committee_experiment_members' => 'required|json',
            'committee_experiment_validation_members' => 'required|json',
            'military_units_investigation' => 'required|json',
            'military_units_experiment' => 'required|json',
            'military_units_experiment_validation' => 'required|json',
            'observations' => 'nullable|string',
            'manual_type_id' => 'required|integer',
        ]);

        // Crear el manual
        $manual = new Manual();
        $manual->manual_name = $validatedData['manual_name'];
        $manual->observations = $validatedData['observations'] ?? null;
        $manual->manual_phases_id = 1; // Fase inicial
        $manual->manual_types_id = $validatedData['manual_type_id'];
        $manual->is_published = false;
        $manual->is_active = true;
        $manual->save();

        // Capturar el ID del manual recién guardado
        $manualId = $manual->id;

        // Decodificar los campos JSON
        $researchMembers = json_decode($validatedData['committee_research_members'], true);
        $validationMembers = json_decode($validatedData['committee_validation_members'], true);
        $experimentMembers = json_decode($validatedData['committee_experiment_members'], true);
        $experimentValidationMembers = json_decode($validatedData['committee_experiment_validation_members'], true);
        $militaryUnitsInvestigation = json_decode($validatedData['military_units_investigation'], true);
        $militaryUnitsExperiment = json_decode($validatedData['military_units_experiment'], true);
        $militaryUnitsExperimentValidation = json_decode($validatedData['military_units_experiment_validation'], true);

        // Verificar que los datos decodificados sean arrays válidos
        if (
            !is_array($researchMembers) ||
            !is_array($validationMembers) ||
            !is_array($experimentMembers) ||
            !is_array($experimentValidationMembers) ||
            !is_array($militaryUnitsInvestigation) ||
            !is_array($militaryUnitsExperiment) ||
            !is_array($militaryUnitsExperimentValidation)
        ) {
            return back()->withErrors(['error' => 'Los datos proporcionados no son válidos.']);
        }

        // Guardar los miembros de cada comité
        foreach ($researchMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 1, // Investigación
                'committee_members_id' => $memberId,
            ]);
        }

        foreach ($validationMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 2, // Validación
                'committee_members_id' => $memberId,
            ]);
        }

        foreach ($experimentMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 3, // Experimentación
                'committee_members_id' => $memberId,
            ]);
        }

        foreach ($experimentValidationMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 4, // Validación de Experimentación
                'committee_members_id' => $memberId,
            ]);
        }

        // Guardar las unidades asociadas a cada comité
        foreach ($militaryUnitsInvestigation as $unitId) {
            ManualMilitaryUnit::create([
                'manuals_id' => $manualId,
                'military_units_id' => $unitId,
                'committee_type_id' => 1,
            ]);
        }

        foreach ($militaryUnitsExperiment as $unitId) {
            ManualMilitaryUnit::create([
                'manuals_id' => $manualId,
                'military_units_id' => $unitId,
                'committee_type_id' => 3,
            ]);
        }

        foreach ($militaryUnitsExperimentValidation as $unitId) {
            ManualMilitaryUnit::create([
                'manuals_id' => $manualId,
                'military_units_id' => $unitId,
                'committee_type_id' => 4,
            ]);
        }

        // Crear las subfases de la fase inicial (manual_phases_id = 1)
        $subphases = CatalogSubphase::where('manual_phases_id', 1)->get();

        foreach ($subphases as $index => $subphase) {
            ManualPhaseSuphase::create([
                'manuals_id' => $manualId,
                'catalog_subphases_id' => $subphase->id,
                'is_completed' => $index === 0 ? 1 : 0, // Completar solo la primera subfase
                'completation_date' => $index === 0 ? now() : null,
                'manual_phases_id' => 1,
            ]);
        }

        return redirect()->route('manuals.index')->with('success', 'Manual creado correctamente.');
    }



    public function editManual($id)
    {
        $manual = Manual::find($id);

        // Obtener los miembros del comité de investigación
        $researchCommitteeMembers = ManualCommitteeMember::where('manuals_id', $id)
            ->where('committee_type_id', 1)
            ->with('committeeMember.grade') // Relación con la tabla de miembros y grados
            ->get();

        // Obtener los miembros del comité de validación
        $validationCommitteeMembers = ManualCommitteeMember::where('manuals_id', $id)
            ->where('committee_type_id', 2)
            ->with('committeeMember.grade') // Relación con la tabla de miembros y grados
            ->get();

        // Obtener los miembros del comité de experimentación
        $experimentationCommitteeMembers = ManualCommitteeMember::where('manuals_id', $id)
            ->where('committee_type_id', 3)
            ->with('committeeMember.grade') // Relación con la tabla de miembros y grados
            ->get();

        //Obtener los miembros del comité de validación de la experimentación
        $expValidCommitteeMembers = ManualCommitteeMember::where('manuals_id', $id)
            ->where('committee_type_id', 4)
            ->with('committeeMember.grade') // Relación con la tabla de miembros y grados
            ->get();


        // Obtener las unidades militares asociadas al manual
        $militaryUnits = ManualMilitaryUnit::where('manuals_id', $id)
            ->where('committee_type_id', 1) // Validar que sea del tipo 1
            ->with('militaryUnit') // Relación con la tabla MilitaryUnit
            ->get();

        // Obtener las unidades militares del comité de experimentación
        $militaryUnitsExp = ManualMilitaryUnit::where('manuals_id', $id)
            ->where('committee_type_id', 3) // Validar que sea del tipo 1
            ->with('militaryUnit') // Relación con la tabla MilitaryUnit
            ->get();

            // Obtener las unidades militares del comité de validación a la experimentación
        $militaryUnitsExpVal = ManualMilitaryUnit::where('manuals_id', $id)
        ->where('committee_type_id', 4) // Validar que sea del tipo 1
        ->with('militaryUnit') // Relación con la tabla MilitaryUnit
        ->get();

        // Obtenemos las subfases para cada fase con sus estados de cumplimiento y fechas
        $researchPhases = CatalogSubphase::where('manual_phases_id', 1)
            ->with([
                'manualPhaseSuphases' => function ($query) use ($id) {
                    $query->where('manuals_id', $id);
                }
            ])
            ->get();

        $experimentationPhases = CatalogSubphase::where('manual_phases_id', 2)
            ->with([
                'manualPhaseSuphases' => function ($query) use ($id) {
                    $query->where('manuals_id', $id);
                }
            ])
            ->get();

        $editionPhases = CatalogSubphase::where('manual_phases_id', 3)
            ->with([
                'manualPhaseSuphases' => function ($query) use ($id) {
                    $query->where('manuals_id', $id);
                }
            ])
            ->get();

        $researchProgress = $this->calculateProgress($id, 1);
        $researchActivity = $this->calculateActivityProgress($id, 1);

        return view('manuals.editManual', compact(
            'manual',
            'researchPhases',
            'experimentationPhases',
            'editionPhases',
            'researchCommitteeMembers',
            'validationCommitteeMembers',
            'expValidCommitteeMembers',
            'experimentationCommitteeMembers',
            'militaryUnits',
            'militaryUnitsExp',
            'militaryUnitsExpVal',
            'researchProgress',
            'researchActivity',
        ));
    }

    public function update(Request $request, $id)
    {
        $manual = Manual::find($id);
        $phasesId = $manual->manual_phases_id;

        // Obtener todas las subfases relacionadas con la fase actual
        $allPhases = CatalogSubphase::where('manual_phases_id', $phasesId)->pluck('id')->toArray();

        $allCompleted = true; // Variable para verificar si todas las subfases están completadas
        $anyIncomplete = false; // Variable para verificar si hay subfases incompletas

        foreach ($allPhases as $phaseId) {
            // Obtén los datos de la fase actual, o usa un array vacío si no existen
            $data = $request->phases[$phaseId] ?? [];

            $isCompleted = $data['is_completed'] ?? 0;
            $completionDate = $isCompleted ? ($data['completation_date'] ?? null) : null;

            ManualPhaseSuphase::updateOrCreate(
                ['manuals_id' => $manual->id, 'catalog_subphases_id' => $phaseId],
                [
                    'is_completed' => $isCompleted,
                    'completation_date' => $completionDate,
                    'manual_phases_id' => $phasesId,
                ]
            );

            // Actualizamos las variables de control
            if ($isCompleted) {
                $anyIncomplete = true; // Hay al menos una subfase completada
            } else {
                $allCompleted = false; // No todas las subfases están completadas
            }
        }

        // Lógica para avanzar, retroceder de fase o publicar
        if ($phasesId === 3) {
            // Verificar si todas las subfases de la fase 3 están completadas
            if ($allCompleted) {
                $manual->is_published = true; // Publicar manual
            } else {
                $manual->is_published = false; // Despublicar manual
            }
        } elseif ($allCompleted && $phasesId < 3) {
            // Avanzar de fase si todas las subfases están completadas y no es la última fase
            $manual->manual_phases_id = $phasesId + 1;
        } elseif (!$anyIncomplete && $phasesId > 1) {
            // Retroceder de fase si todas las subfases de la fase actual están incompletas
            $manual->manual_phases_id = $phasesId - 1;
        }

        $manual->save();

        return redirect()->route('manuals.index')->with('success', 'Subfases actualizadas correctamente.');
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
