<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseState extends Model
{
    public const VALIDATION_BOUNDARIES = ['research' => 'research.12', 'experimentation' => 'experimentation.12'];

    protected $fillable = ['manual_id', 'manual_phase_id', 'started_on', 'substate', 'validation_started_on', 'catalog_version'];

    protected $casts = ['started_on' => 'date', 'validation_started_on' => 'date', 'catalog_version' => 'integer'];
}
