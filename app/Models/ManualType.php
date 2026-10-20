<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualType extends Model
{
    protected $fillable = ['code', 'type_name', 'doctrinal_map_id'];

    public function doctrinalMap()
    {
        return $this->belongsTo(DoctrinalMap::class);
    }

    public function manuals()
    {
        return $this->hasMany(Manual::class, 'manual_types_id');
    }
}
