<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResultResource;
use App\Models\GradingResult;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateResultController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request): JsonResponse
    {
        $candidate = $request->user()?->candidate;
        $session = TestSession::current()->first();

        if (! $candidate || ! $session) {
            return $this->success(null);
        }

        $result = GradingResult::where('candidate_id', $candidate->id)
            ->where('test_session_id', $session->id)
            ->where('is_latest', true)
            ->first();

        return $this->success($result
            ? (new ResultResource($result))->resolve($request)
            : null);
    }
}
