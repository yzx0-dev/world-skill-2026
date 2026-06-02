<?php

namespace Tests\Feature;

use App\Models\TestSession;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompetitionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'judge01',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'judge')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'redirect_to']]);
    }

    public function test_candidate_can_create_only_one_active_submission(): void
    {
        $candidateUser = User::where('username', 'candidate04')->firstOrFail();
        Sanctum::actingAs($candidateUser, ['candidate']);

        $payload = [
            'frontend_url' => 'http://10.10.0.104:3000',
            'backend_api_url' => 'http://10.10.0.104:8080/api',
        ];

        $this->postJson('/api/my-submission', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true);

        // Business rule: use PUT for updates after the active row exists.
        $this->postJson('/api/my-submission', $payload)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_candidate_cannot_submit_after_session_close(): void
    {
        $candidateUser = User::where('username', 'candidate04')->firstOrFail();
        Sanctum::actingAs($candidateUser, ['candidate']);

        TestSession::current()->firstOrFail()->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->postJson('/api/my-submission', [
            'frontend_url' => 'http://10.10.0.104:3000',
            'backend_api_url' => 'http://10.10.0.104:8080/api',
        ])->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_manager_is_read_only_and_cannot_close_session(): void
    {
        $manager = User::where('username', 'manager01')->firstOrFail();
        Sanctum::actingAs($manager, ['manager']);

        $this->getJson('/api/statistics/summary')
            ->assertOk()
            ->assertJsonPath('success', true);

        // Business rule: only judges can open or close the test session.
        $this->putJson('/api/session/close')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }
}
