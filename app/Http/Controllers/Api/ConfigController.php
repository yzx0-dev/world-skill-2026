<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\SessionConfigResource;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        return $this->success((new SessionConfigResource($session))->resolve($request));
    }
}
