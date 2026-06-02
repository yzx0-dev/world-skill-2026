<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CheckRun;
use App\Models\GradingResult;
use App\Models\Submission;
use App\Models\Task;
use App\Models\TestSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $judge = User::updateOrCreate(
            ['username' => 'judge01'],
            [
                'name' => 'Judge User',
                'email' => 'judge01@example.test',
                'password' => $password,
                'role' => 'judge',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['username' => 'manager01'],
            [
                'name' => 'Manager User',
                'email' => 'manager01@example.test',
                'password' => $password,
                'role' => 'manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $regions = ['Central', 'North', 'Northeast', 'South', 'East', 'West'];

        foreach (range(1, 6) as $index) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['username' => "candidate{$number}"],
                [
                    'name' => "Candidate {$number}",
                    'email' => "candidate{$number}@example.test",
                    'password' => $password,
                    'role' => 'candidate',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            Candidate::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'candidate_code' => 'CAND-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'display_name' => "Candidate {$number}",
                    'region_name' => $regions[$index - 1],
                    'seat_number' => 'A'.$number,
                    'workstation_ip' => "10.10.0.10{$index}",
                ]
            );
        }

        AppSetting::upsert([
            [
                'setting_key' => 'api_base_example',
                'setting_value' => 'http://10.10.0.xxx:8080/api',
                'description' => 'Backend API URL format expected in the LAN',
            ],
            [
                'setting_key' => 'frontend_url_example',
                'setting_value' => 'http://10.10.0.xxx:3000',
                'description' => 'Frontend URL format expected in the LAN',
            ],
            [
                'setting_key' => 'passing_score',
                'setting_value' => '70',
                'description' => 'Suggested pass mark for dashboard status',
            ],
            [
                'setting_key' => 'default_duration_minutes',
                'setting_value' => '360',
                'description' => 'Competition duration from the technical description',
            ],
        ], ['setting_key'], ['setting_value', 'description']);

        $session = TestSession::updateOrCreate(
            ['code' => 'RSC2026-WEB-REGIONAL'],
            [
                'name' => 'Regional Web Technologies Test Session',
                'status' => 'open',
                'is_current' => true,
                'duration_minutes' => 360,
                'starts_at' => now(),
                'ends_at' => now()->addHours(6),
                'opened_at' => now(),
                'opened_by_user_id' => $judge->id,
            ]
        );

        Task::updateOrCreate(
            ['test_session_id' => $session->id, 'title' => 'Test Submission Management System'],
            [
                'summary' => 'Build a full-stack application for candidates to submit frontend and backend URLs, judges to control the session and confirm results, and managers to view statistics and rankings.',
                'description' => 'The system runs in an isolated LAN without external APIs, CDN assets, external fonts, or cloud hosting.',
                'frontend_requirements' => [
                    'Login page with validation and role-based redirect',
                    'Candidate dashboard with countdown timer, URL form, submission status, and latest result',
                    'Judge dashboard with candidate table, session controls, re-check, and confirm actions',
                    'Manager dashboard with summary, ranking, pass/fail counts, and export action',
                ],
                'backend_requirements' => [
                    'REST API with JSON responses',
                    'Authentication and role-based authorization',
                    'Persistence for users, candidates, session status, submissions, and results',
                    'Statistics and report endpoints',
                ],
                'business_rules' => [
                    'A candidate may have only one active submission for the current session',
                    'Candidates cannot create or update a submission before start or after close',
                    'Only judges may start or close the session',
                    'Managers are read-only users',
                    'Submitted URLs must be valid internal LAN URLs',
                ],
            ]
        );

        // Sample activity helps the judge and manager screens show meaningful data immediately.
        $this->seedSampleSubmissions($session, $judge);

        AuditLog::firstOrCreate([
            'action' => 'session.opened',
            'entity_type' => 'test_session',
            'entity_id' => $session->id,
        ], [
            'user_id' => $judge->id,
            'payload_json' => ['status' => 'open'],
            'ip_address' => '10.10.0.10',
            'created_at' => now(),
        ]);
    }

    private function seedSampleSubmissions(TestSession $session, User $judge): void
    {
        $samples = [
            ['candidate_code' => 'CAND-001', 'status' => 'confirmed', 'version' => 2, 'scores' => [34, 21, 17, 8, 4], 'confirmed' => true],
            ['candidate_code' => 'CAND-002', 'status' => 'checked', 'version' => 1, 'scores' => [24, 13, 12, 8, 3], 'confirmed' => false],
            ['candidate_code' => 'CAND-003', 'status' => 'submitted', 'version' => 1, 'scores' => null, 'confirmed' => false],
            ['candidate_code' => 'CAND-005', 'status' => 'checked', 'version' => 1, 'scores' => [31, 18, 15, 9, 4], 'confirmed' => false],
        ];

        foreach ($samples as $sample) {
            $candidate = Candidate::where('candidate_code', $sample['candidate_code'])->firstOrFail();
            $ipLast = str_pad((string) $candidate->id, 3, '0', STR_PAD_LEFT);

            $submission = Submission::updateOrCreate(
                ['test_session_id' => $session->id, 'candidate_id' => $candidate->id],
                [
                    'frontend_url' => "http://10.10.0.{$ipLast}:3000",
                    'backend_api_url' => "http://10.10.0.{$ipLast}:8080/api",
                    'status' => $sample['status'],
                    'is_active' => true,
                    'version' => $sample['version'],
                    'submitted_at' => now()->subMinutes(120 - ($candidate->id * 10)),
                ]
            );

            if (! $sample['scores']) {
                continue;
            }

            $checkRun = CheckRun::updateOrCreate(
                ['submission_id' => $submission->id],
                [
                    'requested_by_user_id' => $judge->id,
                    'status' => array_sum($sample['scores']) >= 70 ? 'passed' : 'failed',
                    'started_at' => now()->subMinutes(30),
                    'finished_at' => now()->subMinutes(25),
                    'http_status_code' => 200,
                    'summary_json' => ['api' => 'checked', 'ui' => 'checked', 'reachability' => 'passed'],
                    'logs' => 'Seeded automatic check result.',
                ]
            );

            [$backend, $frontend, $integration, $deployment, $quality] = $sample['scores'];
            $total = $backend + $frontend + $integration + $deployment + $quality;

            GradingResult::updateOrCreate(
                ['check_run_id' => $checkRun->id],
                [
                    'test_session_id' => $session->id,
                    'candidate_id' => $candidate->id,
                    'submission_id' => $submission->id,
                    'score_backend' => $backend,
                    'score_frontend' => $frontend,
                    'score_integration' => $integration,
                    'score_deployment' => $deployment,
                    'score_code_quality' => $quality,
                    'total_score' => $total,
                    'pass_status' => $total >= 70 ? 'pass' : 'fail',
                    'is_latest' => true,
                    'confirmed_by_user_id' => $sample['confirmed'] ? $judge->id : null,
                    'confirmed_at' => $sample['confirmed'] ? now()->subMinutes(20) : null,
                    'judge_notes' => $sample['confirmed'] ? 'Confirmed seeded result.' : 'Awaiting judge confirmation.',
                ]
            );
        }
    }
}
