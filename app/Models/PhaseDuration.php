<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhaseDuration extends Model
{
    protected $fillable = ['manual_phase_id', 'duration_months'];
}
