<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MilitaryUnit;

use Illuminate\Support\Facades\DB;

class MilitaryUnitController extends Controller
{
    public function index()
    {
        $militaryUnits = MilitaryUnit::leftJoin('manual_military_units as MU','military_units.id', '=', 'MU.military_units_id')
        ->select(
            'military_units.id',
            'military_units.unit_name',
            'military_units.unit_acronym',
            DB::raw('COUNT(MU.id) as manuals')
        )
        ->groupBy(
            'military_units.id',
            'military_units.unit_name',
            'military_units.unit_acronym',
        )->orderBy('military_units.unit_name')
        ->get();

        return view('committees.military_units.index', compact('militaryUnits'));
    }

    public function newMilitaryUnit()
    {
        return view('committees.military_units.create');
    }

    public function store(Request $request)
    {
        try{
            $militaryUnit = new MilitaryUnit();
            $militaryUnit->fill($request->all());
            $militaryUnit->save();

            return redirect()->route('militaryUnits.index')->with('success', 'Unidad militar creada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('militaryUnits.index')->with('error', 'Error al crear la unidad militar');
        }
    }

    public function edit( $id)
    {
        $militaryUnit = MilitaryUnit::find($id);
        return view('committees.military_units.edit', compact('militaryUnit'));
    }

    public function update(Request $request)
    {
        try {
            $military_unit = MilitaryUnit::find($request->id);
            $military_unit->fill($request->all());
            $military_unit->save();

            return redirect()->route('militaryUnits.index')->with('success', 'Unidad militar actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('militaryUnits.index')->with('error', 'Error al actualizar la unidad militar');
        }
    }

    public function destroy(MilitaryUnit $military_unit)
    {
        $military_unit->delete();

        return redirect()->route('military_units.index');
    }
}
