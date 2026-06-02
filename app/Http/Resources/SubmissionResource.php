<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'test_session_id' => $this->test_session_id,
            'frontend_url' => $this->frontend_url,
            'backend_api_url' => $this->backend_api_url,
            'status' => $this->status,
            'version' => $this->version,
            'is_active' => (bool) $this->is_active,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'recheck_requested_at' => $this->recheck_requested_at?->toIso8601String(),
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'latest_result' => new ResultResource($this->whenLoaded('latestResult')),
        ];
    }
}
