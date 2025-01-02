<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'grade_name'];

    public function committeeMembers()
    {
        return $this->hasMany(CommitteeMember::class, 'grades_id');
    }
}
