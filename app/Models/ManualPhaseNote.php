<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseNote extends Model
{
    protected $fillable = ['manual_id', 'manual_phase_id', 'note'];
}
