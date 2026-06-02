<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateResultController;
use App\Http\Controllers\Api\CandidateSubmissionController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\JudgeCandidateController;
use App\Http\Controllers\Api\JudgeSubmissionController;
use App\Http\Controllers\Api\ManagerStatisticsController;
use App\Http\Controllers\Api\RecheckController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResultConfirmationController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// The checking system can read timing information without a user token.
Route::get('/config', ConfigController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tasks', TaskController::class);

    Route::middleware('role:candidate')->group(function (): void {
        Route::get('/my-submission', [CandidateSubmissionController::class, 'show']);
        Route::post('/my-submission', [CandidateSubmissionController::class, 'store']);
        Route::put('/my-submission', [CandidateSubmissionController::class, 'update']);
        Route::get('/my-result', CandidateResultController::class);
    });

    Route::middleware('role:judge')->group(function (): void {
        Route::get('/candidates', JudgeCandidateController::class);
        Route::get('/submissions', JudgeSubmissionController::class);
        Route::put('/session/start', [SessionController::class, 'start']);
        Route::put('/session/close', [SessionController::class, 'close']);
        Route::post('/submissions/{submission}/recheck', RecheckController::class);
        Route::put('/results/{candidate}/confirm', ResultConfirmationController::class);
    });

    Route::middleware('role:manager')->group(function (): void {
        Route::get('/statistics/summary', [ManagerStatisticsController::class, 'summary']);
        Route::get('/statistics/ranking', [ManagerStatisticsController::class, 'ranking']);
        Route::get('/statistics/status', [ManagerStatisticsController::class, 'status']);
        Route::get('/report', ReportController::class);
    });
});
