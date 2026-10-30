<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogSubphase extends Model
{
    protected $fillable = ['suphase_name', 'manual_phases_id', 'activity_key', 'position', 'advance_number'];

    protected $casts = ['advance_number' => 'integer', 'position' => 'integer'];

    public function manualPhase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phases_id');
    }

    public function manualPhaseSuphases()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'catalog_subphases_id');
    }
}
