<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'test_session_id',
        'title',
        'summary',
        'description',
        'frontend_requirements',
        'backend_requirements',
        'business_rules',
    ];

    protected $casts = [
        'frontend_requirements' => 'array',
        'backend_requirements' => 'array',
        'business_rules' => 'array',
    ];

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
