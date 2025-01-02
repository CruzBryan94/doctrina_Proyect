<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualCommitteeMember extends Model
{
    use HasFactory;
    protected $fillable = ['committee_members_id', 'manuals_id', 'committee_type_id'];

    public function committeeMember()
    {
        return $this->belongsTo(CommitteeMember::class, 'committee_members_id');
    }

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id');
    }

    public function committeeType()
    {
        return $this->belongsTo(CommitteeType::class, 'committee_type_id');
    }
}
