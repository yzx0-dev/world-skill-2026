<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    protected $fillable = [
        'test_session_id',
        'candidate_id',
        'frontend_url',
        'backend_api_url',
        'status',
        'is_active',
        'version',
        'submitted_at',
        'recheck_requested_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
        'submitted_at' => 'datetime',
        'recheck_requested_at' => 'datetime',
    ];

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function checkRuns(): HasMany
    {
        return $this->hasMany(CheckRun::class);
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(GradingResult::class)->where('is_latest', true);
    }
}
