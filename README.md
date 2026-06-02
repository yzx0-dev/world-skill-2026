# world-skill-2026

Laravel API backend for the WorldSkills Thailand 2026 Regional Web Technologies
test project. The app implements the Test Submission Management System from the
PDF brief: login, role dashboards, session open/close, candidate submissions,
judge re-check/confirmation, manager statistics, and report export.

## What is included

- `routes/api.php` - all REST API endpoints from the brief.
- `app/Http/Controllers/Api` - API logic and business rules.
- `app/Http/Requests` - validation for login, LAN URLs, and score confirmation.
- `app/Http/Middleware/RoleMiddleware.php` - candidate/judge/manager access control.
- `app/Models` - Eloquent models for users, candidates, sessions, submissions, checks, and results.
- `database/migrations` - Laravel schema creation.
- `database/seeders/DatabaseSeeder.php` - seed accounts and sample competition data.
- `database/world_skill_2026.sql` - MySQL/MariaDB dump for phpMyAdmin import.
- `tests/Feature/CompetitionApiTest.php` - core API/business-rule tests.
- `docs/database-design.md` - interpretation notes and endpoint-to-table mapping.

## Setup

Install Composer first if it is not available on your machine. This project is
targeted at Laravel 12 because the bundled XAMPP PHP is 8.2.x.

```powershell
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8080
```

With XAMPP PHP when `php` is not in PATH:

```powershell
& "C:\xampp\php\php.exe" artisan key:generate
& "C:\xampp\php\php.exe" artisan migrate --seed
& "C:\xampp\php\php.exe" artisan serve --host=0.0.0.0 --port=8080
```

## Database

Use either Laravel migrations or phpMyAdmin import:

1. Open phpMyAdmin.
2. Import `database/world_skill_2026.sql`.
3. The dump creates and uses database `world_skill_2026`.

If you import the SQL dump, do not also run `php artisan migrate --seed` on the
same database unless you intentionally want to rebuild the schema.

For quick local API testing when XAMPP MySQL is not running, you can use SQLite:

```powershell
copy .env.sqlite.example .env
New-Item -ItemType File -Force database/database.sqlite
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8080
```

`.env` database example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=world_skill_2026
DB_USERNAME=root
DB_PASSWORD=
```

## Seed accounts

All seeded accounts use password `password`.

- `judge01`
- `manager01`
- `candidate01` to `candidate06`

## Quick API test

Login:

```powershell
curl -X POST http://127.0.0.1:8080/api/login `
  -H "Content-Type: application/json" `
  -d "{\"username\":\"judge01\",\"password\":\"password\"}"
```

Use the returned token:

```powershell
curl http://127.0.0.1:8080/api/statistics/summary `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -H "Accept: application/json"
```

## API endpoints

- `POST /api/login`
- `POST /api/logout`
- `GET /api/config`
- `GET /api/tasks`
- `GET /api/my-submission`
- `POST /api/my-submission`
- `PUT /api/my-submission`
- `GET /api/my-result`
- `GET /api/candidates`
- `GET /api/submissions`
- `PUT /api/session/start`
- `PUT /api/session/close`
- `POST /api/submissions/{id}/recheck`
- `PUT /api/results/{candidate_id}/confirm`
- `GET /api/statistics/summary`
- `GET /api/statistics/ranking`
- `GET /api/statistics/status`
- `GET /api/report`

## Tests

```powershell
php artisan test
```

The included tests cover login, candidate one-submission rule, session close
blocking submissions, and manager read-only access.
