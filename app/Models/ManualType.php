<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualType extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'type_name'];

    public function manuals()
    {
        return $this->hasMany(Manual::class, 'manual_types_id');
    }
}
