<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manual extends Model
{
    use HasFactory;
    protected $fillable = ['manual_types_id', 'manual_name', 'manual_phases_id', 'code', 'observations', 'publication_year', 'is_published'];

    public function manualType()
    {
        return $this->belongsTo(ManualType::class, 'manual_types_id');
    }

    public function manualPhase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phases_id');
    }

    public function manualCommitteeMembers()
    {
        return $this->hasMany(ManualCommitteeMember::class, 'manuals_id');
    }

    public function manualMilitaryUnits()
    {
        return $this->hasMany(ManualMilitaryUnit::class, 'manuals_id');
    }

    public function manualPhaseSuphases()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'manuals_id');
    }
}
