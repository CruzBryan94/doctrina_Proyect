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
                'G.grade_name as grade',
                DB::raw('COUNT(MCM.id) as manuals') // Corrección aquí
            )
            ->groupBy(
                'committee_members.id',
                'committee_members.full_name',
                'committee_members.identification',
                'G.grade_name'
            ) // Agrupamos para evitar problemas con COUNT
            ->get();

        return view('committees.members.index', compact('committeeMembers'));
    }

    public function edit($id)
    {
        $committeeMember = CommitteeMember::find($id);
        $grades = Grade::all();

        return view('committees.members.edit', compact('committeeMember', 'grades'));

    }
}
