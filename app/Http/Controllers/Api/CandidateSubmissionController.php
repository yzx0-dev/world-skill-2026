<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Requests\UpdateSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Submission;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateSubmissionController extends Controller
{
    use RespondsWithApiJson;

    public function show(Request $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $session = $this->currentSession();

        if (! $candidate || ! $session) {
            return $this->success(null);
        }

        $submission = Submission::with('latestResult')
            ->where('candidate_id', $candidate->id)
            ->where('test_session_id', $session->id)
            ->first();

        return $this->success($submission
            ? (new SubmissionResource($submission))->resolve($request)
            : null);
    }

    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $session = $this->currentSession();

        if (! $candidate || ! $session) {
            return $this->error('Candidate or session was not found.', 404);
        }

        if (! $session->acceptsSubmissions()) {
            return $this->error('The testing session is not accepting submissions.', 403);
        }

        $existing = Submission::where('candidate_id', $candidate->id)
            ->where('test_session_id', $session->id)
            ->where('is_active', true)
            ->first();

        // Business rule: a candidate may have only one active submission per session.
        if ($existing) {
            return $this->error('An active submission already exists. Use PUT /api/my-submission to update it.', 409);
        }

        $submission = DB::transaction(function () use ($request, $candidate, $session): Submission {
            $submission = Submission::create([
                'test_session_id' => $session->id,
                'candidate_id' => $candidate->id,
                'frontend_url' => $request->validated('frontend_url'),
                'backend_api_url' => $request->validated('backend_api_url'),
                'status' => 'submitted',
                'is_active' => true,
                'version' => 1,
                'submitted_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'submission.created',
                'entity_type' => 'submission',
                'entity_id' => $submission->id,
                'payload_json' => ['candidate_code' => $candidate->candidate_code],
                'ip_address' => $request->ip(),
            ]);

            return $submission;
        });

        return $this->success((new SubmissionResource($submission))->resolve($request), [], 201);
    }

    public function update(UpdateSubmissionRequest $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $session = $this->currentSession();

        if (! $candidate || ! $session) {
            return $this->error('Candidate or session was not found.', 404);
        }

        if (! $session->acceptsSubmissions()) {
            return $this->error('The testing session is not accepting submission updates.', 403);
        }

        $submission = Submission::where('candidate_id', $candidate->id)
            ->where('test_session_id', $session->id)
            ->where('is_active', true)
            ->first();

        if (! $submission) {
            return $this->error('No active submission exists yet.', 404);
        }

        DB::transaction(function () use ($request, $submission, $candidate): void {
            $submission->update([
                'frontend_url' => $request->validated('frontend_url'),
                'backend_api_url' => $request->validated('backend_api_url'),
                'status' => 'submitted',
                'version' => $submission->version + 1,
                'submitted_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'submission.updated',
                'entity_type' => 'submission',
                'entity_id' => $submission->id,
                'payload_json' => ['candidate_code' => $candidate->candidate_code],
                'ip_address' => $request->ip(),
            ]);
        });

        return $this->success((new SubmissionResource($submission->refresh()))->resolve($request));
    }

    private function candidate(Request $request): ?Candidate
    {
        return $request->user()?->candidate;
    }

    private function currentSession(): ?TestSession
    {
        return TestSession::current()->first();
    }
}
