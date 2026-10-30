<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseNoteAudit extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = 'created_at';

    protected $fillable = ['manual_id', 'manual_phase_id', 'user_id', 'before_note', 'after_note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
