<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JudgeSubmissionController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        $submissions = Submission::with(['candidate.user', 'latestResult'])
            ->where('test_session_id', $session->id)
            ->orderByDesc('submitted_at')
            ->get();

        return $this->success(SubmissionResource::collection($submissions)->resolve($request));
    }
}
