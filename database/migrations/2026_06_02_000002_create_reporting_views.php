<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Views are optional helpers for phpMyAdmin/reporting and match the SQL dump.
        DB::statement($this->dropView('v_current_session_config'));
        DB::statement(<<<'SQL'
CREATE VIEW v_current_session_config AS
SELECT
  test_sessions.id,
  test_sessions.code,
  test_sessions.name,
  test_sessions.status,
  test_sessions.duration_minutes,
  test_sessions.starts_at,
  test_sessions.ends_at,
  GREATEST(TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), test_sessions.ends_at), 0) AS seconds_remaining
FROM test_sessions
WHERE test_sessions.is_current = 1
SQL);

        DB::statement($this->dropView('v_manager_ranking'));
        DB::statement(<<<'SQL'
CREATE VIEW v_manager_ranking AS
SELECT
  candidates.id AS candidate_id,
  candidates.candidate_code,
  users.name AS candidate_name,
  candidates.region_name,
  COALESCE(submissions.status, 'not_submitted') AS submission_status,
  submissions.frontend_url,
  submissions.backend_api_url,
  COALESCE(grading_results.total_score, 0.00) AS total_score,
  COALESCE(grading_results.pass_status, 'pending') AS pass_status,
  grading_results.confirmed_at
FROM candidates
INNER JOIN users ON users.id = candidates.user_id
LEFT JOIN test_sessions ON test_sessions.is_current = 1
LEFT JOIN submissions
  ON submissions.candidate_id = candidates.id
  AND submissions.test_session_id = test_sessions.id
LEFT JOIN grading_results
  ON grading_results.candidate_id = candidates.id
  AND grading_results.test_session_id = test_sessions.id
  AND grading_results.is_latest = 1
SQL);

        DB::statement($this->dropView('v_statistics_summary'));
        DB::statement(<<<'SQL'
CREATE VIEW v_statistics_summary AS
SELECT
  test_sessions.id AS test_session_id,
  test_sessions.code,
  test_sessions.status,
  COUNT(DISTINCT candidates.id) AS candidate_count,
  COUNT(DISTINCT submissions.id) AS submission_count,
  COUNT(DISTINCT grading_results.id) AS graded_count,
  COALESCE(ROUND(AVG(grading_results.total_score), 2), 0.00) AS average_score,
  COALESCE(MAX(grading_results.total_score), 0.00) AS highest_score,
  COALESCE(MIN(grading_results.total_score), 0.00) AS lowest_score,
  SUM(CASE WHEN grading_results.pass_status = 'pass' THEN 1 ELSE 0 END) AS pass_count,
  SUM(CASE WHEN grading_results.pass_status = 'fail' THEN 1 ELSE 0 END) AS fail_count,
  SUM(CASE WHEN candidates.id IS NULL THEN 0 WHEN grading_results.id IS NULL OR grading_results.pass_status = 'pending' THEN 1 ELSE 0 END) AS pending_count
FROM test_sessions
LEFT JOIN candidates ON 1 = 1
LEFT JOIN submissions
  ON submissions.test_session_id = test_sessions.id
  AND submissions.candidate_id = candidates.id
LEFT JOIN grading_results
  ON grading_results.test_session_id = test_sessions.id
  AND grading_results.candidate_id = candidates.id
  AND grading_results.is_latest = 1
WHERE test_sessions.is_current = 1
GROUP BY test_sessions.id, test_sessions.code, test_sessions.status
SQL);

        DB::statement($this->dropView('v_pass_fail_status'));
        DB::statement(<<<'SQL'
CREATE VIEW v_pass_fail_status AS
SELECT
  COALESCE(grading_results.pass_status, 'pending') AS pass_status,
  COUNT(DISTINCT candidates.id) AS candidate_count
FROM candidates
LEFT JOIN test_sessions ON test_sessions.is_current = 1
LEFT JOIN grading_results
  ON grading_results.test_session_id = test_sessions.id
  AND grading_results.candidate_id = candidates.id
  AND grading_results.is_latest = 1
GROUP BY COALESCE(grading_results.pass_status, 'pending')
SQL);

        DB::statement($this->dropView('v_report_export'));
        DB::statement(<<<'SQL'
CREATE VIEW v_report_export AS
SELECT
  test_sessions.code AS session_code,
  test_sessions.name AS session_name,
  candidates.candidate_code,
  users.name AS candidate_name,
  candidates.region_name,
  submissions.frontend_url,
  submissions.backend_api_url,
  submissions.status AS submission_status,
  grading_results.score_backend,
  grading_results.score_frontend,
  grading_results.score_integration,
  grading_results.score_deployment,
  grading_results.score_code_quality,
  grading_results.total_score,
  grading_results.pass_status,
  grading_results.confirmed_at
FROM test_sessions
LEFT JOIN candidates ON 1 = 1
LEFT JOIN users ON users.id = candidates.user_id
LEFT JOIN submissions
  ON submissions.test_session_id = test_sessions.id
  AND submissions.candidate_id = candidates.id
LEFT JOIN grading_results
  ON grading_results.test_session_id = test_sessions.id
  AND grading_results.candidate_id = candidates.id
  AND grading_results.is_latest = 1
WHERE test_sessions.is_current = 1
SQL);
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement($this->dropView('v_report_export'));
        DB::statement($this->dropView('v_pass_fail_status'));
        DB::statement($this->dropView('v_statistics_summary'));
        DB::statement($this->dropView('v_manager_ranking'));
        DB::statement($this->dropView('v_current_session_config'));
    }

    private function dropView(string $name): string
    {
        return "DROP VIEW IF EXISTS {$name}";
    }
};
