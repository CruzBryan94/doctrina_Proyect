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
            'military_units' => 'required|json',
            'observations' => 'nullable|string',
            'manual_type_id' => 'required|integer',
        ]);

        // Crear el manual
        $manual = new Manual();
        $manual->manual_name = $validatedData['manual_name'];
        $manual->observations = $validatedData['observations'] ?? null;
        $manual->manual_phases_id = 1; // ID de la fase inicial
        $manual->manual_types_id = $validatedData['manual_type_id'];
        $manual->is_published = false;
        $manual->is_active = true;
        $manual->save();

        // Capturar el ID del manual recién guardado
        $manualId = $manual->id;

        // Decodificar los campos JSON
        $researchMembers = json_decode($validatedData['committee_research_members'], true);
        $validationMembers = json_decode($validatedData['committee_validation_members'], true);
        $militaryUnits = json_decode($validatedData['military_units'], true);

        // Verificar que los datos decodificados sean arrays válidos
        if (!is_array($researchMembers) || !is_array($validationMembers) || !is_array($militaryUnits)) {
            return back()->withErrors(['error' => 'Los datos de los miembros del comité o unidades no son válidos.']);
        }

        // Guardar en la tabla manual_committee_members los miembros del comité de investigación
        foreach ($researchMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 1, // 1 = Investigación
                'committee_members_id' => $memberId,
            ]);
        }

        // Guardar en la tabla manual_committee_members los miembros del comité de validación
        foreach ($validationMembers as $memberId) {
            ManualCommitteeMember::create([
                'manuals_id' => $manualId,
                'committee_type_id' => 2, // 2 = Validación
                'committee_members_id' => $memberId,
            ]);
        }

        // Guardar las unidades militares asociadas al manual
        foreach ($militaryUnits as $unitId) {
            ManualMilitaryUnit::create([
                'manuals_id' => $manualId,
                'military_units_id' => $unitId,
                'committee_type_id' => 1,
            ]);
        }

        // Obtener todas las subfases del manual_phases_id = 1
        $subphases = CatalogSubphase::where('manual_phases_id', 1)->get();

        foreach ($subphases as $index => $subphase) {
            ManualPhaseSuphase::create([
                'manuals_id' => $manualId,
                'catalog_subphases_id' => $subphase->id,
                'is_completed' => $index === 0 ? 1 : 0, // Solo marcar completada la primera subfase
                'completation_date' => $index === 0 ? now() : null, // Solo agregar fecha a la primera subfase
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

        // Obtener las unidades militares asociadas al manual
        $militaryUnits = ManualMilitaryUnit::where('manuals_id', $id)
            ->where('committee_type_id', 1) // Validar que sea del tipo 1
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
            'militaryUnits',
            'researchProgress',
            'researchActivity',
        ));
    }

    public function update(Request $request, $id)
    {
        $manual = Manual::find($id);

        // Obtener todas las subfases relacionadas con la fase
        $allPhases = CatalogSubphase::where('manual_phases_id', 1)->pluck('id')->toArray();

        foreach ($allPhases as $phaseId) {
            $data = $request->phases[$phaseId] ?? null;

            ManualPhaseSuphase::updateOrCreate(
                ['manuals_id' => $manual->id, 'catalog_subphases_id' => $phaseId],
                [
                    'is_completed' => $data['is_completed'] ?? 0,
                    'completation_date' => $data['completation_date'] ?? null,
                    'manual_phases_id' => 1,
                ]
            );
        }

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
