<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JudgeCandidateController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request): JsonResponse
    {
        $candidates = Candidate::with('user')
            ->orderBy('candidate_code')
            ->get();

        return $this->success(CandidateResource::collection($candidates)->resolve($request));
    }
}
