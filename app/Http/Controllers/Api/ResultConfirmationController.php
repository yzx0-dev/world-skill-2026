<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmResultRequest;
use App\Http\Resources\ResultResource;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\GradingResult;
use App\Models\Submission;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResultConfirmationController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(ConfirmResultRequest $request, Candidate $candidate): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        $submission = Submission::where('test_session_id', $session->id)
            ->where('candidate_id', $candidate->id)
            ->where('is_active', true)
            ->first();

        if (! $submission) {
            return $this->error('This candidate has no active submission for the current session.', 404);
        }

        $hasScoreInput = collect([
            'score_backend',
            'score_frontend',
            'score_integration',
            'score_deployment',
            'score_code_quality',
        ])->contains(fn (string $field): bool => $request->has($field));

        $existing = GradingResult::where('test_session_id', $session->id)
            ->where('candidate_id', $candidate->id)
            ->where('is_latest', true)
            ->first();

        if (! $existing && ! $hasScoreInput) {
            return $this->error('No grading result exists yet. Provide scores or run a check first.', 422);
        }

        $result = DB::transaction(function () use ($request, $session, $candidate, $submission, $existing): GradingResult {
            $scores = [
                'score_backend' => (float) ($request->input('score_backend', $existing?->score_backend ?? 0)),
                'score_frontend' => (float) ($request->input('score_frontend', $existing?->score_frontend ?? 0)),
                'score_integration' => (float) ($request->input('score_integration', $existing?->score_integration ?? 0)),
                'score_deployment' => (float) ($request->input('score_deployment', $existing?->score_deployment ?? 0)),
                'score_code_quality' => (float) ($request->input('score_code_quality', $existing?->score_code_quality ?? 0)),
            ];

            $total = array_sum($scores);
            $passingScore = (float) (AppSetting::find('passing_score')?->setting_value ?? 70);

            // Only one result should be treated as the latest result per candidate/session.
            GradingResult::where('test_session_id', $session->id)
                ->where('candidate_id', $candidate->id)
                ->update(['is_latest' => false]);

            $attributes = array_merge($scores, [
                'test_session_id' => $session->id,
                'candidate_id' => $candidate->id,
                'submission_id' => $submission->id,
                'check_run_id' => $existing?->check_run_id,
                'total_score' => $total,
                'pass_status' => $total >= $passingScore ? 'pass' : 'fail',
                'is_latest' => true,
                'confirmed_by_user_id' => $request->user()->id,
                'confirmed_at' => now(),
                'judge_notes' => $request->input('judge_notes', $existing?->judge_notes),
            ]);

            if ($existing) {
                $existing->update($attributes);
                $result = $existing->refresh();
            } else {
                $result = GradingResult::create($attributes);
            }

            $submission->update(['status' => 'confirmed']);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'result.confirmed',
                'entity_type' => 'grading_result',
                'entity_id' => $result->id,
                'payload_json' => [
                    'candidate_code' => $candidate->candidate_code,
                    'total_score' => $total,
                ],
                'ip_address' => $request->ip(),
            ]);

            return $result;
        });

        return $this->success((new ResultResource($result))->resolve($request));
    }
}
