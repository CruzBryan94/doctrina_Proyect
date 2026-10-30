<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionalReference extends Model
{
    protected $fillable = ['doctrinal_map_id', 'expected_total', 'lock_version'];
    protected $casts = ['expected_total' => 'integer', 'lock_version' => 'integer'];
}
