<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardCardController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskUpdateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth / profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);       // admin only
    Route::put('/users/{user}', [UserController::class, 'update']); // admin only

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Calendar — user activity logs + project due dates for a month
    Route::get('/calendar', [CalendarController::class, 'index']);

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);

    // Project members
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Task update logs
    Route::get('/tasks/{task}/updates', [TaskUpdateController::class, 'index']);
    Route::post('/tasks/{task}/updates', [TaskUpdateController::class, 'store']);

    // Files
    Route::get('/files', [FileController::class, 'index']);
    Route::get('/files/{attachment}/download', [FileController::class, 'download']);
    Route::delete('/files/{attachment}', [FileController::class, 'destroy']);

    // Task It boards (kanban)
    Route::get('/boards', [BoardController::class, 'index']);
    Route::post('/boards', [BoardController::class, 'store']);
    Route::get('/boards/{board}', [BoardController::class, 'show']);
    Route::delete('/boards/{board}', [BoardController::class, 'destroy']);
    Route::post('/boards/{board}/cards', [BoardCardController::class, 'store']);
    Route::put('/boards/{board}/cards/reorder', [BoardCardController::class, 'reorder']);
    Route::delete('/boards/{board}/cards/{card}', [BoardCardController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead']);
});
