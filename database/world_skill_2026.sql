-- WorldSkills Thailand 2026 Regional - Web Technologies
-- Test Submission Management System database dump
-- Target: MySQL 8.x / MariaDB 10.x, importable with phpMyAdmin.
--
-- If you already have a Laravel database selected in phpMyAdmin,
-- change or remove the CREATE DATABASE and USE statements below.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `world_skill_2026`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `world_skill_2026`;

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS `v_report_export`;
DROP VIEW IF EXISTS `v_pass_fail_status`;
DROP VIEW IF EXISTS `v_statistics_summary`;
DROP VIEW IF EXISTS `v_manager_ranking`;
DROP VIEW IF EXISTS `v_current_session_config`;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `grading_results`;
DROP TABLE IF EXISTS `check_runs`;
DROP TABLE IF EXISTS `submissions`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `test_sessions`;
DROP TABLE IF EXISTS `candidates`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('candidate', 'judge', 'manager') NOT NULL DEFAULT 'candidate',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `candidates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `candidate_code` VARCHAR(30) NOT NULL,
  `display_name` VARCHAR(255) NOT NULL,
  `region_name` VARCHAR(120) NULL DEFAULT NULL,
  `seat_number` VARCHAR(30) NULL DEFAULT NULL,
  `workstation_ip` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `candidates_user_id_unique` (`user_id`),
  UNIQUE KEY `candidates_candidate_code_unique` (`candidate_code`),
  KEY `candidates_region_name_index` (`region_name`),
  CONSTRAINT `candidates_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `test_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `status` ENUM('draft', 'open', 'closed') NOT NULL DEFAULT 'draft',
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 360,
  `starts_at` TIMESTAMP NULL DEFAULT NULL,
  `ends_at` TIMESTAMP NULL DEFAULT NULL,
  `opened_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `opened_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `closed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_sessions_code_unique` (`code`),
  KEY `test_sessions_status_current_index` (`status`, `is_current`),
  KEY `test_sessions_opened_by_user_id_foreign` (`opened_by_user_id`),
  KEY `test_sessions_closed_by_user_id_foreign` (`closed_by_user_id`),
  CONSTRAINT `test_sessions_opened_by_user_id_foreign`
    FOREIGN KEY (`opened_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `test_sessions_closed_by_user_id_foreign`
    FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` LONGTEXT NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_session_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `description` LONGTEXT NULL,
  `frontend_requirements` LONGTEXT NULL,
  `backend_requirements` LONGTEXT NULL,
  `business_rules` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tasks_test_session_id_foreign` (`test_session_id`),
  CONSTRAINT `tasks_test_session_id_foreign`
    FOREIGN KEY (`test_session_id`) REFERENCES `test_sessions` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_session_id` BIGINT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `frontend_url` VARCHAR(2048) NOT NULL,
  `backend_api_url` VARCHAR(2048) NOT NULL,
  `status` ENUM('submitted', 'checking', 'checked', 'confirmed', 'rejected') NOT NULL DEFAULT 'submitted',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `recheck_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submissions_session_candidate_unique` (`test_session_id`, `candidate_id`),
  KEY `submissions_candidate_id_foreign` (`candidate_id`),
  KEY `submissions_status_index` (`status`),
  CONSTRAINT `submissions_test_session_id_foreign`
    FOREIGN KEY (`test_session_id`) REFERENCES `test_sessions` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `submissions_candidate_id_foreign`
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `check_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` BIGINT UNSIGNED NOT NULL,
  `requested_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `status` ENUM('queued', 'running', 'passed', 'failed', 'error') NOT NULL DEFAULT 'queued',
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `finished_at` TIMESTAMP NULL DEFAULT NULL,
  `http_status_code` INT UNSIGNED NULL DEFAULT NULL,
  `newman_report_path` VARCHAR(500) NULL DEFAULT NULL,
  `playwright_report_path` VARCHAR(500) NULL DEFAULT NULL,
  `summary_json` LONGTEXT NULL,
  `logs` MEDIUMTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `check_runs_submission_id_foreign` (`submission_id`),
  KEY `check_runs_requested_by_user_id_foreign` (`requested_by_user_id`),
  KEY `check_runs_status_index` (`status`),
  CONSTRAINT `check_runs_submission_id_foreign`
    FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `check_runs_requested_by_user_id_foreign`
    FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grading_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_session_id` BIGINT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `submission_id` BIGINT UNSIGNED NOT NULL,
  `check_run_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `score_backend` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `score_frontend` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `score_integration` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `score_deployment` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `score_code_quality` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `pass_status` ENUM('pending', 'pass', 'fail') NOT NULL DEFAULT 'pending',
  `is_latest` TINYINT(1) NOT NULL DEFAULT 1,
  `confirmed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `judge_notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grading_results_check_run_unique` (`check_run_id`),
  KEY `grading_results_session_candidate_index` (`test_session_id`, `candidate_id`),
  KEY `grading_results_submission_id_foreign` (`submission_id`),
  KEY `grading_results_confirmed_by_user_id_foreign` (`confirmed_by_user_id`),
  KEY `grading_results_latest_index` (`is_latest`, `pass_status`),
  CONSTRAINT `grading_results_test_session_id_foreign`
    FOREIGN KEY (`test_session_id`) REFERENCES `test_sessions` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `grading_results_candidate_id_foreign`
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `grading_results_submission_id_foreign`
    FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `grading_results_check_run_id_foreign`
    FOREIGN KEY (`check_run_id`) REFERENCES `check_runs` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `grading_results_confirmed_by_user_id_foreign`
    FOREIGN KEY (`confirmed_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `entity_type` VARCHAR(120) NULL DEFAULT NULL,
  `entity_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `payload_json` LONGTEXT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  CONSTRAINT `audit_logs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE VIEW `v_current_session_config` AS
SELECT
  `test_sessions`.`id`,
  `test_sessions`.`code`,
  `test_sessions`.`name`,
  `test_sessions`.`status`,
  `test_sessions`.`duration_minutes`,
  `test_sessions`.`starts_at`,
  `test_sessions`.`ends_at`,
  GREATEST(TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), `test_sessions`.`ends_at`), 0) AS `seconds_remaining`
FROM `test_sessions`
WHERE `test_sessions`.`is_current` = 1;

CREATE VIEW `v_manager_ranking` AS
SELECT
  `candidates`.`id` AS `candidate_id`,
  `candidates`.`candidate_code`,
  `users`.`name` AS `candidate_name`,
  `candidates`.`region_name`,
  COALESCE(`submissions`.`status`, 'not_submitted') AS `submission_status`,
  `submissions`.`frontend_url`,
  `submissions`.`backend_api_url`,
  COALESCE(`grading_results`.`total_score`, 0.00) AS `total_score`,
  COALESCE(`grading_results`.`pass_status`, 'pending') AS `pass_status`,
  `grading_results`.`confirmed_at`
FROM `candidates`
INNER JOIN `users` ON `users`.`id` = `candidates`.`user_id`
LEFT JOIN `test_sessions` ON `test_sessions`.`is_current` = 1
LEFT JOIN `submissions`
  ON `submissions`.`candidate_id` = `candidates`.`id`
  AND `submissions`.`test_session_id` = `test_sessions`.`id`
LEFT JOIN `grading_results`
  ON `grading_results`.`candidate_id` = `candidates`.`id`
  AND `grading_results`.`test_session_id` = `test_sessions`.`id`
  AND `grading_results`.`is_latest` = 1;

CREATE VIEW `v_statistics_summary` AS
SELECT
  `test_sessions`.`id` AS `test_session_id`,
  `test_sessions`.`code`,
  `test_sessions`.`status`,
  COUNT(DISTINCT `candidates`.`id`) AS `candidate_count`,
  COUNT(DISTINCT `submissions`.`id`) AS `submission_count`,
  COUNT(DISTINCT `grading_results`.`id`) AS `graded_count`,
  COALESCE(ROUND(AVG(`grading_results`.`total_score`), 2), 0.00) AS `average_score`,
  COALESCE(MAX(`grading_results`.`total_score`), 0.00) AS `highest_score`,
  COALESCE(MIN(`grading_results`.`total_score`), 0.00) AS `lowest_score`,
  SUM(CASE WHEN `grading_results`.`pass_status` = 'pass' THEN 1 ELSE 0 END) AS `pass_count`,
  SUM(CASE WHEN `grading_results`.`pass_status` = 'fail' THEN 1 ELSE 0 END) AS `fail_count`,
  SUM(CASE WHEN `candidates`.`id` IS NULL THEN 0 WHEN `grading_results`.`id` IS NULL OR `grading_results`.`pass_status` = 'pending' THEN 1 ELSE 0 END) AS `pending_count`
FROM `test_sessions`
LEFT JOIN `candidates` ON 1 = 1
LEFT JOIN `submissions`
  ON `submissions`.`test_session_id` = `test_sessions`.`id`
  AND `submissions`.`candidate_id` = `candidates`.`id`
LEFT JOIN `grading_results`
  ON `grading_results`.`test_session_id` = `test_sessions`.`id`
  AND `grading_results`.`candidate_id` = `candidates`.`id`
  AND `grading_results`.`is_latest` = 1
WHERE `test_sessions`.`is_current` = 1
GROUP BY `test_sessions`.`id`, `test_sessions`.`code`, `test_sessions`.`status`;

CREATE VIEW `v_pass_fail_status` AS
SELECT
  COALESCE(`grading_results`.`pass_status`, 'pending') AS `pass_status`,
  COUNT(DISTINCT `candidates`.`id`) AS `candidate_count`
FROM `candidates`
LEFT JOIN `test_sessions` ON `test_sessions`.`is_current` = 1
LEFT JOIN `grading_results`
  ON `grading_results`.`test_session_id` = `test_sessions`.`id`
  AND `grading_results`.`candidate_id` = `candidates`.`id`
  AND `grading_results`.`is_latest` = 1
GROUP BY COALESCE(`grading_results`.`pass_status`, 'pending');

CREATE VIEW `v_report_export` AS
SELECT
  `test_sessions`.`code` AS `session_code`,
  `test_sessions`.`name` AS `session_name`,
  `candidates`.`candidate_code`,
  `users`.`name` AS `candidate_name`,
  `candidates`.`region_name`,
  `submissions`.`frontend_url`,
  `submissions`.`backend_api_url`,
  `submissions`.`status` AS `submission_status`,
  `grading_results`.`score_backend`,
  `grading_results`.`score_frontend`,
  `grading_results`.`score_integration`,
  `grading_results`.`score_deployment`,
  `grading_results`.`score_code_quality`,
  `grading_results`.`total_score`,
  `grading_results`.`pass_status`,
  `grading_results`.`confirmed_at`
FROM `test_sessions`
LEFT JOIN `candidates` ON 1 = 1
LEFT JOIN `users` ON `users`.`id` = `candidates`.`user_id`
LEFT JOIN `submissions`
  ON `submissions`.`test_session_id` = `test_sessions`.`id`
  AND `submissions`.`candidate_id` = `candidates`.`id`
LEFT JOIN `grading_results`
  ON `grading_results`.`test_session_id` = `test_sessions`.`id`
  AND `grading_results`.`candidate_id` = `candidates`.`id`
  AND `grading_results`.`is_latest` = 1
WHERE `test_sessions`.`is_current` = 1;

SET @default_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/b7yJ7bduHDX7xqUG';

INSERT INTO `users`
  (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `is_active`, `remember_token`, `created_at`, `updated_at`)
VALUES
  (1, 'Judge User', 'judge01', 'judge01@example.test', NOW(), @default_password_hash, 'judge', 1, NULL, NOW(), NOW()),
  (2, 'Manager User', 'manager01', 'manager01@example.test', NOW(), @default_password_hash, 'manager', 1, NULL, NOW(), NOW()),
  (3, 'Candidate 01', 'candidate01', 'candidate01@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW()),
  (4, 'Candidate 02', 'candidate02', 'candidate02@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW()),
  (5, 'Candidate 03', 'candidate03', 'candidate03@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW()),
  (6, 'Candidate 04', 'candidate04', 'candidate04@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW()),
  (7, 'Candidate 05', 'candidate05', 'candidate05@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW()),
  (8, 'Candidate 06', 'candidate06', 'candidate06@example.test', NOW(), @default_password_hash, 'candidate', 1, NULL, NOW(), NOW());

INSERT INTO `candidates`
  (`id`, `user_id`, `candidate_code`, `display_name`, `region_name`, `seat_number`, `workstation_ip`, `created_at`, `updated_at`)
VALUES
  (1, 3, 'CAND-001', 'Candidate 01', 'Central', 'A01', '10.10.0.101', NOW(), NOW()),
  (2, 4, 'CAND-002', 'Candidate 02', 'North', 'A02', '10.10.0.102', NOW(), NOW()),
  (3, 5, 'CAND-003', 'Candidate 03', 'Northeast', 'A03', '10.10.0.103', NOW(), NOW()),
  (4, 6, 'CAND-004', 'Candidate 04', 'South', 'A04', '10.10.0.104', NOW(), NOW()),
  (5, 7, 'CAND-005', 'Candidate 05', 'East', 'A05', '10.10.0.105', NOW(), NOW()),
  (6, 8, 'CAND-006', 'Candidate 06', 'West', 'A06', '10.10.0.106', NOW(), NOW());

INSERT INTO `app_settings`
  (`setting_key`, `setting_value`, `description`, `created_at`, `updated_at`)
VALUES
  ('api_base_example', 'http://10.10.0.xxx:8080/api', 'Backend API URL format expected in the LAN', NOW(), NOW()),
  ('frontend_url_example', 'http://10.10.0.xxx:3000', 'Frontend URL format expected in the LAN', NOW(), NOW()),
  ('url_lan_pattern', '^https?://(10\\.|192\\.168\\.|172\\.(1[6-9]|2[0-9]|3[0-1])\\.|localhost|127\\.)', 'Application-level validation pattern for internal URLs', NOW(), NOW()),
  ('passing_score', '70', 'Suggested pass mark for dashboard status', NOW(), NOW()),
  ('default_duration_minutes', '360', 'Competition duration from the technical description', NOW(), NOW());

INSERT INTO `test_sessions`
  (`id`, `code`, `name`, `status`, `is_current`, `duration_minutes`, `starts_at`, `ends_at`, `opened_at`, `closed_at`, `opened_by_user_id`, `closed_by_user_id`, `created_at`, `updated_at`)
VALUES
  (1, 'RSC2026-WEB-REGIONAL', 'Regional Web Technologies Test Session', 'open', 1, 360, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 6 HOUR), UTC_TIMESTAMP(), NULL, 1, NULL, NOW(), NOW());

INSERT INTO `tasks`
  (`id`, `test_session_id`, `title`, `summary`, `description`, `frontend_requirements`, `backend_requirements`, `business_rules`, `created_at`, `updated_at`)
VALUES
  (
    1,
    1,
    'Test Submission Management System',
    'Build a full-stack application for candidates to submit frontend and backend URLs, judges to control the session and confirm results, and managers to view statistics and rankings.',
    'The system runs in an isolated LAN without external APIs, CDN assets, external fonts, or cloud hosting. Frontend clients must use the backend API only and must not connect directly to the database.',
    '["Login with validation and role-based redirect","Candidate dashboard with task summary, countdown timer, URL form, submission status, and latest result","Judge dashboard with candidate list, session controls, submissions, re-check action, and confirm result action","Manager dashboard with summary cards, ranking table, pass/fail counts, and export action","Usable at 1366px desktop width and 375px mobile width"]',
    '["REST API with JSON success and error formats","Authentication and role-based authorization","Persistence for users, candidates, session status, submissions, and results","Validation and business rules on relevant endpoints","Statistics and report endpoints for managers"]',
    '["A candidate may have only one active submission for the current session","Candidates cannot create or update a submission before the session starts or after it closes","Only judges may start or close the session","Managers are read-only users","Candidates can access only their own submission and result data","Submitted URLs must be valid internal LAN URLs and reachable by the checking system","Protected operations after session close should return an appropriate error such as HTTP 403","Seed data must continue to work if the schema is extended"]',
    NOW(),
    NOW()
  );

INSERT INTO `submissions`
  (`id`, `test_session_id`, `candidate_id`, `frontend_url`, `backend_api_url`, `status`, `is_active`, `version`, `submitted_at`, `recheck_requested_at`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1, 'http://10.10.0.101:3000', 'http://10.10.0.101:8080/api', 'confirmed', 1, 2, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 90 MINUTE), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 60 MINUTE), NOW(), NOW()),
  (2, 1, 2, 'http://10.10.0.102:3000', 'http://10.10.0.102:8080/api', 'checked', 1, 1, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 70 MINUTE), NULL, NOW(), NOW()),
  (3, 1, 3, 'http://10.10.0.103:3000', 'http://10.10.0.103:8080/api', 'submitted', 1, 1, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 45 MINUTE), NULL, NOW(), NOW()),
  (4, 1, 5, 'http://10.10.0.105:3000', 'http://10.10.0.105:8080/api', 'checked', 1, 1, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE), NULL, NOW(), NOW());

INSERT INTO `check_runs`
  (`id`, `submission_id`, `requested_by_user_id`, `status`, `started_at`, `finished_at`, `http_status_code`, `newman_report_path`, `playwright_report_path`, `summary_json`, `logs`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1, 'passed', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 58 MINUTE), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 55 MINUTE), 200, 'reports/newman/cand-001.json', 'reports/playwright/cand-001', '{"api": "passed", "ui": "passed", "reachability": "passed"}', 'Initial check completed successfully.', NOW(), NOW()),
  (2, 2, 1, 'failed', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 40 MINUTE), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 MINUTE), 200, 'reports/newman/cand-002.json', 'reports/playwright/cand-002', '{"api": "partial", "ui": "failed", "reachability": "passed"}', 'UI route guard failed one test.', NOW(), NOW()),
  (3, 4, 1, 'passed', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 20 MINUTE), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 17 MINUTE), 200, 'reports/newman/cand-005.json', 'reports/playwright/cand-005', '{"api": "passed", "ui": "partial", "reachability": "passed"}', 'Candidate passed minimum checks with minor frontend issues.', NOW(), NOW());

INSERT INTO `grading_results`
  (`id`, `test_session_id`, `candidate_id`, `submission_id`, `check_run_id`, `score_backend`, `score_frontend`, `score_integration`, `score_deployment`, `score_code_quality`, `total_score`, `pass_status`, `is_latest`, `confirmed_by_user_id`, `confirmed_at`, `judge_notes`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1, 1, 1, 34.00, 21.00, 17.00, 8.00, 4.00, 84.00, 'pass', 1, 1, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 50 MINUTE), 'Confirmed after successful re-check.', NOW(), NOW()),
  (2, 1, 2, 2, 2, 24.00, 13.00, 12.00, 8.00, 3.00, 60.00, 'fail', 1, NULL, NULL, 'Needs judge review before final confirmation.', NOW(), NOW()),
  (3, 1, 5, 4, 3, 31.00, 18.00, 15.00, 9.00, 4.00, 77.00, 'pass', 1, NULL, NULL, 'Auto-check finished. Awaiting confirmation.', NOW(), NOW());

INSERT INTO `audit_logs`
  (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `payload_json`, `ip_address`, `created_at`)
VALUES
  (1, 1, 'session.opened', 'test_session', 1, '{"status": "open"}', '10.10.0.10', UTC_TIMESTAMP()),
  (2, 3, 'submission.created', 'submission', 1, '{"candidate_code": "CAND-001"}', '10.10.0.101', UTC_TIMESTAMP()),
  (3, 1, 'result.confirmed', 'grading_result', 1, '{"candidate_code": "CAND-001", "total_score": 84}', '10.10.0.10', UTC_TIMESTAMP());

-- Seed login accounts use password: password
-- judge01 / password
-- manager01 / password
-- candidate01..candidate06 / password
