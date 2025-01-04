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
            return [
                'type_code' => $manual->manualType->code,
                'manual_name' => $manual->manual_name,
                'phase_name' => $manual->manualPhase->phase_name,
                'observations' => $manual->observations,
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
            'manual_type_id' => 'required|integer'
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

        return redirect()->route('manuals.index')->with('success', 'Manual creado correctamente.');
    }

    public function editManual($id)
    {

        $manual = Manual::find($id);

        // Obtenemos las subfases para cada fase con sus estados de cumplimiento y fechas
        $researchPhases = CatalogSubphase::where('manual_phases_id', 1)
            ->with(['manualPhaseSuphases' => function ($query) use ($id) {
                $query->where('manuals_id', $id);
            }])
            ->get();

        $experimentationPhases = CatalogSubphase::where('manual_phases_id', 2)
            ->with(['manualPhaseSuphases' => function ($query) use ($id) {
                $query->where('manuals_id', $id);
            }])
            ->get();

        $editionPhases = CatalogSubphase::where('manual_phases_id', 3)
            ->with(['manualPhaseSuphases' => function ($query) use ($id) {
                $query->where('manuals_id', $id);
            }])
            ->get();

        return view('manuals.editManual', compact('manual', 'researchPhases', 'experimentationPhases', 'editionPhases'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'phases.*.is_completed' => 'nullable|boolean',
            'phases.*.completation_date' => 'nullable|date',
        ]);

        $manual = Manual::find($id);

        foreach ($request->phases as $phaseId => $data) {
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


}
