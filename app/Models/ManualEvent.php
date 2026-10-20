<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualEvent extends Model
{
    protected $fillable = ['manual_id', 'user_id', 'action', 'before', 'after', 'reason'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
