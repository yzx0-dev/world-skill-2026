<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\GradingResult;
use App\Models\Submission;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerStatisticsController extends Controller
{
    use RespondsWithApiJson;

    public function summary(): JsonResponse
    {
        $session = $this->currentSessionOrFail();
        if ($session instanceof JsonResponse) {
            return $session;
        }

        $latestResults = GradingResult::where('test_session_id', $session->id)
            ->where('is_latest', true);

        return $this->success([
            'test_session_id' => $session->id,
            'session_status' => $session->status,
            'candidate_count' => Candidate::count(),
            'submission_count' => Submission::where('test_session_id', $session->id)->count(),
            'graded_count' => (clone $latestResults)->count(),
            'average_score' => round((float) (clone $latestResults)->avg('total_score'), 2),
            'highest_score' => round((float) (clone $latestResults)->max('total_score'), 2),
            'lowest_score' => round((float) (clone $latestResults)->min('total_score'), 2),
            'seconds_remaining' => $session->secondsRemaining(),
        ]);
    }

    public function ranking(Request $request): JsonResponse
    {
        $session = $this->currentSessionOrFail();
        if ($session instanceof JsonResponse) {
            return $session;
        }

        $rows = Candidate::with([
            'user',
            'submissions' => fn ($query) => $query->where('test_session_id', $session->id),
            'gradingResults' => fn ($query) => $query->where('test_session_id', $session->id)->where('is_latest', true),
        ])->get()->map(function (Candidate $candidate): array {
            $submission = $candidate->submissions->first();
            $result = $candidate->gradingResults->first();

            return [
                'candidate_id' => $candidate->id,
                'candidate_code' => $candidate->candidate_code,
                'candidate_name' => $candidate->user?->name ?? $candidate->display_name,
                'region_name' => $candidate->region_name,
                'submission_status' => $submission?->status ?? 'not_submitted',
                'frontend_url' => $submission?->frontend_url,
                'backend_api_url' => $submission?->backend_api_url,
                'total_score' => (float) ($result?->total_score ?? 0),
                'pass_status' => $result?->pass_status ?? 'pending',
                'confirmed_at' => $result?->confirmed_at?->toIso8601String(),
            ];
        })->sortByDesc('total_score')->values();

        return $this->success($rows->all());
    }

    public function status(): JsonResponse
    {
        $session = $this->currentSessionOrFail();
        if ($session instanceof JsonResponse) {
            return $session;
        }

        $counts = ['pass' => 0, 'fail' => 0, 'pending' => 0];

        Candidate::with([
            'gradingResults' => fn ($query) => $query->where('test_session_id', $session->id)->where('is_latest', true),
        ])->get()->each(function (Candidate $candidate) use (&$counts): void {
            $status = $candidate->gradingResults->first()?->pass_status ?? 'pending';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        });

        return $this->success([
            ['status' => 'pass', 'count' => $counts['pass']],
            ['status' => 'fail', 'count' => $counts['fail']],
            ['status' => 'pending', 'count' => $counts['pending']],
        ]);
    }

    private function currentSessionOrFail(): TestSession|JsonResponse
    {
        $session = TestSession::current()->first();

        return $session ?: $this->error('No current test session has been configured.', 404);
    }
}
