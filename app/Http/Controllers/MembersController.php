<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommitteeMember;
use App\Models\Grade;

use Illuminate\Support\Facades\DB;


class MembersController extends Controller
{
    public function index()
    {
        $committeeMembers = CommitteeMember::join('grades as G', 'committee_members.grades_id', '=', 'G.code')
            ->leftJoin('manual_committee_members as MCM', 'committee_members.id', '=', 'MCM.committee_members_id')
            ->select(
                'committee_members.id',
                'committee_members.full_name',
                'committee_members.identification',
                'committee_members.cellphone',
                'G.code as code',
                'G.grade_name as grade',
                DB::raw('COUNT(MCM.id) as manuals') // Corrección aquí
            )
            ->groupBy(
                'committee_members.id',
                'committee_members.full_name',
                'committee_members.identification',
                'committee_members.cellphone',
                'G.code',
                'G.grade_name'
            )->orderBy('G.code', 'desc')
            ->orderBy('committee_members.full_name')
            ->get();

        return view('committees.members.index', compact('committeeMembers'));
    }

    public function edit($id)
    {
        $committeeMember = CommitteeMember::find($id);
        $grades = Grade::all();

        return view('committees.members.edit', compact('committeeMember', 'grades'));

    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'cellphone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]*$/'],
        ], [
            'cellphone.regex' => 'El celular debe contener números y puede incluir + al inicio, espacios, guiones o paréntesis.',
            'cellphone.max' => 'El celular no debe superar los 20 caracteres.',
        ]);

        try {
            $id = $request->id;
            $committeeMember = CommitteeMember::find($id);
            $committeeMember->fill($request->only(['grades_id', 'full_name', 'identification']));
            $committeeMember->fill($validated);
            $committeeMember->save();


            return redirect()->route('members.index')->with('success', 'Miembro actualizado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('members.index')->with('error', 'Error al actualizar el miembro');
        }
    }

    public function newMember()
    {
        $grades = Grade::all();
        return view('committees.members.newMember', compact('grades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cellphone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]*$/'],
        ], [
            'cellphone.regex' => 'El celular debe contener números y puede incluir + al inicio, espacios, guiones o paréntesis.',
            'cellphone.max' => 'El celular no debe superar los 20 caracteres.',
        ]);

        try{

        $committeeMember = new CommitteeMember();
        $committeeMember->fill($request->only(['grades_id', 'full_name', 'identification']));
        $committeeMember->fill($validated);
        $committeeMember->save();

        return redirect()->route('members.index')->with('success', 'Miembro creado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('members.index')->with('error', 'Error al crear el miembro');
        }
    }
}
