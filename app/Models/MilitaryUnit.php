<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilitaryUnit extends Model
{
    use HasFactory;
    protected $fillable = ['unit_name', 'unit_acronym'];

    public function manualMilitaryUnits()
    {
        return $this->hasMany(ManualMilitaryUnit::class, 'military_units_id');
    }
}
