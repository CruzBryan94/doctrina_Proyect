<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhase extends Model
{
    public const ROLES = [
        'planning' => 'planificador', 'research' => 'investigador',
        'experimentation' => 'experimentador', 'edition' => 'edición', 'cgft' => 'cgft',
    ];

    protected $fillable = ['code', 'phase_name', 'slug', 'position', 'is_active'];

    public function catalogSubphases()
    {
        return $this->hasMany(CatalogSubphase::class, 'manual_phases_id')->orderBy('position');
    }

    public function manuals()
    {
        return $this->hasMany(Manual::class, 'manual_phases_id');
    }
}
