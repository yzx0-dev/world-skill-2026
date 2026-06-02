<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingResult extends Model
{
    protected $fillable = [
        'test_session_id',
        'candidate_id',
        'submission_id',
        'check_run_id',
        'score_backend',
        'score_frontend',
        'score_integration',
        'score_deployment',
        'score_code_quality',
        'total_score',
        'pass_status',
        'is_latest',
        'confirmed_by_user_id',
        'confirmed_at',
        'judge_notes',
    ];

    protected $casts = [
        'score_backend' => 'decimal:2',
        'score_frontend' => 'decimal:2',
        'score_integration' => 'decimal:2',
        'score_deployment' => 'decimal:2',
        'score_code_quality' => 'decimal:2',
        'total_score' => 'decimal:2',
        'is_latest' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function checkRun(): BelongsTo
    {
        return $this->belongsTo(CheckRun::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
