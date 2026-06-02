<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TestSession extends Model
{
    protected $fillable = [
        'code',
        'name',
        'status',
        'is_current',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'opened_at',
        'closed_at',
        'opened_by_user_id',
        'closed_by_user_id',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function acceptsSubmissions(): bool
    {
        // Candidates may submit only while the session is open and inside time.
        return $this->isOpen()
            && $this->starts_at instanceof Carbon
            && $this->ends_at instanceof Carbon
            && now()->betweenIncluded($this->starts_at, $this->ends_at);
    }

    public function secondsRemaining(): int
    {
        if (! $this->ends_at instanceof Carbon) {
            return 0;
        }

        return max(now()->diffInSeconds($this->ends_at, false), 0);
    }
}
