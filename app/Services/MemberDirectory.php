<?php

namespace App\Services;

use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Builder;

class MemberDirectory
{
    public function search(Builder $query, string $search): Builder
    {
        foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function (Builder $query) use ($term) {
                $query->where('committee_members.full_name', 'like', '%'.$term.'%')
                    ->orWhere('committee_members.identification', 'like', ctype_digit($term) ? $term.'%' : '%'.$term.'%');
            });
        }

        return $query;
    }

    public function listing(string $search = ''): Builder
    {
        return $this->search(CommitteeMember::query(), $search)
            ->join('grades', 'committee_members.grades_id', '=', 'grades.id')
            ->select('committee_members.*', 'grades.grade_name as grade', 'grades.code as grade_code')
            ->selectSub(function ($query) {
                $query->from('manual_committee_members')->selectRaw('COUNT(DISTINCT manuals_id)')
                    ->whereColumn('committee_members_id', 'committee_members.id');
            }, 'manuals')
            ->orderByDesc('grades.code')->orderBy('committee_members.full_name')->orderBy('committee_members.id');
    }
}
