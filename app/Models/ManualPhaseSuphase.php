<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPhaseSuphase extends Model
{
    use HasFactory;
    protected $fillable = ['manuals_id', 'catalog_subphases_id', 'manual_phases_id', 'is_completed', 'completation_date'];

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id');
    }

    public function catalogSubphase()
    {
        return $this->belongsTo(CatalogSubphase::class, 'catalog_subphases_id');
    }

    public function manualPhase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phases_id');
    }
}
