<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\NotificationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    // Course API endpoints
    Route::apiResource('courses', CourseController::class);
    Route::get('/courses/{course}/students', [CourseController::class, 'students']);
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll']);

    // Chat API endpoints
    Route::get('/conversations', [ChatController::class, 'conversations']);
    Route::get('/conversations/{user}', [ChatController::class, 'messages']);
    Route::post('/conversations/{user}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/messages/{message}/read', [ChatController::class, 'markAsRead']);

    // Grades API endpoints
    Route::get('/grades', [GradeController::class, 'index']);
    Route::get('/grades/course/{course}', [GradeController::class, 'courseGrades']);
    Route::post('/grades', [GradeController::class, 'store']);
    Route::put('/grades/{grade}', [GradeController::class, 'update']);

    // Games API endpoints
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::post('/games/{game}/sessions', [GameController::class, 'startSession']);
    Route::post('/game-sessions/{session}/results', [GameController::class, 'saveResult']);
    Route::get('/game-results', [GameController::class, 'userResults']);

    // Notifications API endpoints
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Real-time endpoints for WebSocket connections
    Route::get('/realtime/auth', function (Request $request) {
        return response()->json([
            'auth' => $request->user()->createToken('realtime')->plainTextToken
        ]);
    });
});