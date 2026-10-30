<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public function gradeRecord()
    {
        return $this->belongsTo(Grade::class, 'grade');
    }

    public function accessiblePhaseSlugs(): array
    {
        return $this->hasRole('admin') ? array_keys(ManualPhase::ROLES) : array_keys(array_filter(ManualPhase::ROLES, fn ($role) => $this->hasRole($role)));
    }

    public function canConsultManuals(): bool
    {
        return $this->is_active && $this->hasAnyRole(array_merge(['admin', 'supervisor', 'publicados'], array_values(ManualPhase::ROLES)));
    }

    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'is_active',
        'grade',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
