<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        // Laravel hashes plain-text passwords automatically when models are saved.
        'password' => 'hashed',
    ];

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function isCandidate(): bool
    {
        return $this->role === 'candidate';
    }

    public function isJudge(): bool
    {
        return $this->role === 'judge';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
