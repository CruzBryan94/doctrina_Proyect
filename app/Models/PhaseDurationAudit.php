<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhaseDurationAudit extends Model
{
    protected $fillable = ['manual_phase_id', 'user_id', 'before_months', 'after_months'];
}
