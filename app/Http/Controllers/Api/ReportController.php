<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        // Manager export is a JSON payload so it can be saved as CSV/PDF by the frontend.
        $rows = Candidate::with([
            'user',
            'submissions' => fn ($query) => $query->where('test_session_id', $session->id),
            'gradingResults' => fn ($query) => $query->where('test_session_id', $session->id)->where('is_latest', true),
        ])->orderBy('candidate_code')->get()->map(function (Candidate $candidate): array {
            $submission = $candidate->submissions->first();
            $result = $candidate->gradingResults->first();

            return [
                'candidate_code' => $candidate->candidate_code,
                'candidate_name' => $candidate->user?->name ?? $candidate->display_name,
                'region_name' => $candidate->region_name,
                'frontend_url' => $submission?->frontend_url,
                'backend_api_url' => $submission?->backend_api_url,
                'submission_status' => $submission?->status ?? 'not_submitted',
                'score_backend' => (float) ($result?->score_backend ?? 0),
                'score_frontend' => (float) ($result?->score_frontend ?? 0),
                'score_integration' => (float) ($result?->score_integration ?? 0),
                'score_deployment' => (float) ($result?->score_deployment ?? 0),
                'score_code_quality' => (float) ($result?->score_code_quality ?? 0),
                'total_score' => (float) ($result?->total_score ?? 0),
                'pass_status' => $result?->pass_status ?? 'pending',
                'confirmed_at' => $result?->confirmed_at?->toIso8601String(),
            ];
        });

        return $this->success([
            'generated_at' => now()->toIso8601String(),
            'session' => [
                'code' => $session->code,
                'name' => $session->name,
                'status' => $session->status,
            ],
            'rows' => $rows,
        ]);
    }
}
