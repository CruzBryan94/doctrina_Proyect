<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseState extends Model
{
    protected $fillable = ['manual_id', 'manual_phase_id', 'started_on'];

    protected $casts = ['started_on' => 'date'];
}
