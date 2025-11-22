<?php

use App\Http\Controllers\API\V1\AuditLogController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\CourseController;
use App\Http\Controllers\API\V1\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Students - Manager and Technical only
    Route::middleware('role:manager,technical')->group(function () {
        Route::apiResource('students', StudentController::class)->parameters([
            'students' => 'student',
        ]);
    });

    // Courses - Manager, Technical, and Trainer
    Route::middleware('role:manager,technical,trainer')->group(function () {
        Route::apiResource('courses', CourseController::class)->parameters([
            'courses' => 'course',
        ]);
    });

    // Audit Logs - Manager only
    Route::middleware('role:manager')->prefix('audit-logs')->group(function () {
        Route::get('/', [AuditLogController::class, 'index']);
        Route::get('/statistics', [AuditLogController::class, 'statistics']);
        Route::get('/user/{userId}', [AuditLogController::class, 'userActivity']);
        Route::get('/model-history', [AuditLogController::class, 'modelHistory']);
        Route::get('/{id}', [AuditLogController::class, 'show']);
    });
});
