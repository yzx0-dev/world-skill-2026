<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'submission_id' => $this->submission_id,
            'score_backend' => (float) $this->score_backend,
            'score_frontend' => (float) $this->score_frontend,
            'score_integration' => (float) $this->score_integration,
            'score_deployment' => (float) $this->score_deployment,
            'score_code_quality' => (float) $this->score_code_quality,
            'total_score' => (float) $this->total_score,
            'pass_status' => $this->pass_status,
            'is_latest' => (bool) $this->is_latest,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'judge_notes' => $this->judge_notes,
        ];
    }
}
