<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctrinalMap extends Model
{
    protected $fillable = ['code', 'name'];

    public function manualTypes()
    {
        return $this->hasMany(ManualType::class);
    }
}
