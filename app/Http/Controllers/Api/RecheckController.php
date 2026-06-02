<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\CheckRunResource;
use App\Models\AuditLog;
use App\Models\CheckRun;
use App\Models\Submission;
use App\Models\TestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecheckController extends Controller
{
    use RespondsWithApiJson;

    public function __invoke(Request $request, Submission $submission): JsonResponse
    {
        $session = TestSession::current()->first();

        if (! $session || $submission->test_session_id !== $session->id) {
            return $this->error('Submission does not belong to the current session.', 404);
        }

        $checkRun = DB::transaction(function () use ($request, $submission): CheckRun {
            // The actual Newman/Playwright worker can pick up queued check runs.
            $checkRun = CheckRun::create([
                'submission_id' => $submission->id,
                'requested_by_user_id' => $request->user()->id,
                'status' => 'queued',
                'summary_json' => [
                    'message' => 'Queued for automatic re-check.',
                ],
            ]);

            $submission->update([
                'status' => 'checking',
                'recheck_requested_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'submission.recheck_requested',
                'entity_type' => 'submission',
                'entity_id' => $submission->id,
                'payload_json' => ['check_run_id' => $checkRun->id],
                'ip_address' => $request->ip(),
            ]);

            return $checkRun;
        });

        return $this->success((new CheckRunResource($checkRun))->resolve($request), [], 202);
    }
}
