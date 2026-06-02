<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\SessionConfigResource;
use App\Models\AuditLog;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    use RespondsWithApiJson;

    public function start(Request $request): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            $session = TestSession::create([
                'code' => 'RSC2026-WEB-REGIONAL',
                'name' => 'Regional Web Technologies Test Session',
                'status' => 'draft',
                'is_current' => true,
                'duration_minutes' => 360,
            ]);
        }

        if ($session->status === 'closed') {
            return $this->error('The testing session has already been closed.', 409);
        }

        DB::transaction(function () use ($request, $session): void {
            // Starting the session defines the countdown used by candidate dashboards.
            $session->update([
                'status' => 'open',
                'is_current' => true,
                'starts_at' => now(),
                'ends_at' => now()->addMinutes($session->duration_minutes),
                'opened_at' => now(),
                'opened_by_user_id' => $request->user()->id,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'session.opened',
                'entity_type' => 'test_session',
                'entity_id' => $session->id,
                'payload_json' => ['status' => 'open'],
                'ip_address' => $request->ip(),
            ]);
        });

        return $this->success((new SessionConfigResource($session->refresh()))->resolve($request));
    }

    public function close(Request $request): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session) {
            return $this->error('No current test session has been configured.', 404);
        }

        DB::transaction(function () use ($request, $session): void {
            // Closing the session blocks all candidate create/update operations.
            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_user_id' => $request->user()->id,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'session.closed',
                'entity_type' => 'test_session',
                'entity_id' => $session->id,
                'payload_json' => ['status' => 'closed'],
                'ip_address' => $request->ip(),
            ]);
        });

        return $this->success((new SessionConfigResource($session->refresh()))->resolve($request));
    }
}
