<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = ['grades_id','full_name','identification'];

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grades_id');
    }

    public function manualCommitteeMembers()
    {
        return $this->hasMany(ManualCommitteeMember::class, 'committee_members_id');
    }
}
