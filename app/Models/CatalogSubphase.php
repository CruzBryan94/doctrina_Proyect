<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogSubphase extends Model
{
    use HasFactory;
    protected $fillable = ['suphase_name', 'manual_phases_id'];

    public function manualPhase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phases_id');
    }

    public function manualPhaseSuphases()
    {
        return $this->hasMany(ManualPhaseSuphase::class, 'catalog_subphases_id');
    }
}
