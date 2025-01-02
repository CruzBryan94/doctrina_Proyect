<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualMilitaryUnit extends Model
{
    use HasFactory;
    protected $fillable = ['manuals_id', 'committee_type_id', 'military_units_id'];

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id');
    }

    public function committeeType()
    {
        return $this->belongsTo(CommitteeType::class, 'committee_type_id');
    }

    public function militaryUnit()
    {
        return $this->belongsTo(MilitaryUnit::class, 'military_units_id');
    }
}
