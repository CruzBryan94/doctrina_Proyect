<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionalReferenceAudit extends Model
{
    protected $fillable = ['doctrinal_map_id', 'user_id', 'before_total', 'after_total'];
    protected $casts = ['before_total' => 'integer', 'after_total' => 'integer'];

    public function map()
    {
        return $this->belongsTo(DoctrinalMap::class, 'doctrinal_map_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
