<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        return $this->success([
            'session_id' => $session->id,
            'tasks' => TaskResource::collection($session->tasks()->get())->resolve($request),
        ]);
    }
}
