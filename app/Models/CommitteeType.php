<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommitteeType extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name'];

    public function manualCommitteeMembers()
    {
        return $this->hasMany(ManualCommitteeMember::class, 'committee_type_id');
    }

    public function manualMilitaryUnits()
    {
        return $this->hasMany(ManualMilitaryUnit::class, 'committee_type_id');
    }
}
