<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username', 100)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['candidate', 'judge', 'manager'])->default('candidate')->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('candidate_code', 30)->unique();
            $table->string('display_name');
            $table->string('region_name', 120)->nullable()->index();
            $table->string('seat_number', 30)->nullable();
            $table->string('workstation_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('test_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->unsignedInteger('duration_minutes')->default(360);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'is_current']);
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->string('setting_key', 100)->primary();
            $table->longText('setting_value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->longText('description')->nullable();
            // JSON lists keep the task screen flexible without adding extra tables.
            $table->json('frontend_requirements')->nullable();
            $table->json('backend_requirements')->nullable();
            $table->json('business_rules')->nullable();
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('frontend_url', 2048);
            $table->string('backend_api_url', 2048);
            $table->enum('status', ['submitted', 'checking', 'checked', 'confirmed', 'rejected'])->default('submitted')->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('recheck_requested_at')->nullable();
            $table->timestamps();
            // Enforces the one-active-submission rule at database level for the current session.
            $table->unique(['test_session_id', 'candidate_id']);
        });

        Schema::create('check_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['queued', 'running', 'passed', 'failed', 'error'])->default('queued')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('http_status_code')->nullable();
            $table->string('newman_report_path', 500)->nullable();
            $table->string('playwright_report_path', 500)->nullable();
            $table->json('summary_json')->nullable();
            $table->mediumText('logs')->nullable();
            $table->timestamps();
        });

        Schema::create('grading_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('check_run_id')->nullable()->unique()->constrained('check_runs')->nullOnDelete();
            $table->decimal('score_backend', 5, 2)->default(0);
            $table->decimal('score_frontend', 5, 2)->default(0);
            $table->decimal('score_integration', 5, 2)->default(0);
            $table->decimal('score_deployment', 5, 2)->default(0);
            $table->decimal('score_code_quality', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->enum('pass_status', ['pending', 'pass', 'fail'])->default('pending');
            $table->boolean('is_latest')->default(true)->index();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('judge_notes')->nullable();
            $table->timestamps();
            $table->index(['test_session_id', 'candidate_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('grading_results');
        Schema::dropIfExists('check_runs');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('test_sessions');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
