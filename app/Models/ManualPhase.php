<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPhase extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'phase_name'];

    public function manuals()
    {
        return $this->hasMany(Manual::class, 'manual_phases_id');
    }

    public function catalogSubphases()
    {
        return $this->hasMany(CatalogSubphase::class, 'manual_phases_id');
    }

    public function manualPhaseSuphases()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'manual_phases_id');
    }
}
