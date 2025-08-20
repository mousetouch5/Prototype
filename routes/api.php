<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClickUpAccountController;
use App\Http\Controllers\MondayAccountController;
use App\Http\Controllers\SyncConfigurationController;
use App\Http\Controllers\GanttController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ClickUp Account routes
    Route::apiResource('clickup-accounts', ClickUpAccountController::class);
    Route::post('/clickup-accounts/{account}/test', [ClickUpAccountController::class, 'testConnection']);
    Route::get('/clickup-accounts/{account}/workspaces', [ClickUpAccountController::class, 'getWorkspaces']);
    Route::get('/clickup-accounts/{account}/workspaces/{workspaceId}/spaces', [ClickUpAccountController::class, 'getSpaces']);
    Route::get('/clickup-accounts/{account}/lists', [ClickUpAccountController::class, 'getLists']);
    Route::get('/clickup-accounts/{account}/lists/{listId}/tasks', [ClickUpAccountController::class, 'getTasks']);

    // Monday Account routes
    Route::apiResource('monday-accounts', MondayAccountController::class);
    Route::post('/monday-accounts/{account}/test', [MondayAccountController::class, 'testConnection']);
    Route::get('/monday-accounts/{account}/boards', [MondayAccountController::class, 'getBoards']);
    Route::get('/monday-accounts/{account}/boards/{boardId}/groups', [MondayAccountController::class, 'getGroups']);
    Route::get('/monday-accounts/{account}/boards/{boardId}/items', [MondayAccountController::class, 'getItems']);

    // Gantt Chart routes
    Route::post('/gantt/data', [GanttController::class, 'getGanttData']);
    Route::get('/gantt/lists', [GanttController::class, 'getAccountLists']);

    // Sync Configuration routes
    Route::apiResource('sync-configurations', SyncConfigurationController::class);
    Route::post('/sync-configurations/{configuration}/sync', [SyncConfigurationController::class, 'syncNow']);
    Route::get('/sync-configurations/{configuration}/logs', [SyncConfigurationController::class, 'getSyncLogs']);
    Route::post('/sync-configurations/{configuration}/test', [SyncConfigurationController::class, 'testConnection']);
});