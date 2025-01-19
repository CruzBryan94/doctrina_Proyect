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
                'G.code as code',
                'G.grade_name as grade',
                DB::raw('COUNT(MCM.id) as manuals') // Corrección aquí
            )
            ->groupBy(
                'committee_members.id',
                'committee_members.full_name',
                'committee_members.identification',
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
        try {
            $id = $request->id;
            $committeeMember = CommitteeMember::find($id);
            $committeeMember->fill($request->all());
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
        try{

        $committeeMember = new CommitteeMember();
        $committeeMember->fill($request->all());
        $committeeMember->save();

        return redirect()->route('members.index')->with('success', 'Miembro creado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('members.index')->with('error', 'Error al crear el miembro');
        }
    }
}
