# Database Design Notes

Source documents:

- `RSC2026_TD_Web_Technologies .pdf`
- `Web_Technologies_TP v1.0_public.pdf`

The SQL file in `database/world_skill_2026.sql` interprets the test project as a
Test Submission Management System for a LAN-only competition room.

## Main interpretation

The database supports three roles:

- `candidate`: logs in, reads the task, sees the countdown, submits frontend and backend URLs, and views only their own latest result.
- `judge`: opens or closes the test session, reviews candidates and submissions, requests re-checks, and confirms scores.
- `manager`: read-only dashboard user for summary, ranking, pass/fail status, and report export.

## Tables

- `users`: Laravel-friendly user table with username, email, password hash, role, and remember token.
- `candidates`: competitor profile linked one-to-one with a `candidate` user.
- `test_sessions`: current competition session, open/closed lifecycle, timing, and judge ownership.
- `tasks`: task overview and requirement lists for `/api/tasks`.
- `submissions`: one active submission per candidate per session, enforced by a unique key on `test_session_id` and `candidate_id`.
- `check_runs`: automatic checking attempts from Newman, Playwright, and reachability checks.
- `grading_results`: measured scores, pass/fail state, latest-result flag, and judge confirmation data.
- `app_settings`: configurable values such as LAN URL pattern, examples, duration, and passing score.
- `audit_logs`: simple action history for session, submission, re-check, and confirmation events.
- `sessions`, `password_reset_tokens`, `personal_access_tokens`: common Laravel-compatible support tables.

## Views

- `v_current_session_config`: useful for `/api/config` and countdown data.
- `v_manager_ranking`: useful for `/api/statistics/ranking`.
- `v_statistics_summary`: useful for `/api/statistics/summary`.
- `v_pass_fail_status`: useful for `/api/statistics/status`.
- `v_report_export`: useful for `/api/report`.

## API mapping

- `POST /api/login`: `users`, optionally `personal_access_tokens` or `sessions`
- `POST /api/logout`: `personal_access_tokens` or `sessions`
- `GET /api/config`: `v_current_session_config`
- `GET /api/tasks`: `tasks`
- `GET /api/my-submission`: `submissions` filtered by authenticated candidate
- `POST /api/my-submission`: `submissions`
- `PUT /api/my-submission`: `submissions`
- `GET /api/my-result`: `grading_results` filtered by authenticated candidate and `is_latest = 1`
- `GET /api/candidates`: `candidates` joined to `users`
- `GET /api/submissions`: `submissions` joined to `candidates`
- `PUT /api/session/start`: `test_sessions`, `audit_logs`
- `PUT /api/session/close`: `test_sessions`, `audit_logs`
- `POST /api/submissions/{id}/recheck`: `check_runs`, `submissions`, `audit_logs`
- `PUT /api/results/{candidate_id}/confirm`: `grading_results`, `audit_logs`
- `GET /api/statistics/summary`: `v_statistics_summary`
- `GET /api/statistics/ranking`: `v_manager_ranking`
- `GET /api/statistics/status`: `v_pass_fail_status`
- `GET /api/report`: `v_report_export`

## Seed data

The dump includes:

- 1 judge: `judge01`
- 1 manager: `manager01`
- 6 candidates: `candidate01` to `candidate06`
- Password for all seeded users: `password`
- 1 current open session with a 6-hour countdown from import time
- Sample submissions, check runs, grading results, and audit logs

The current session is seeded as `open` so a Laravel frontend/API can be tested
immediately after import. If you want judges to open the session manually during
a formal run, update `test_sessions.status` to `draft` and clear `starts_at`,
`ends_at`, and `opened_at`.
