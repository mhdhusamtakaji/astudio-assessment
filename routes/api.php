<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -------------------------------------
// 1. fetch authenticated user route 
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// -------------------------------------
// 2. Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// -------------------------------------
// 3. Protected routes for authentication
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// -------------------------------------
// 4. Protected REST resources
Route::apiResource('projects', ProjectController::class)->middleware('auth:api');
Route::apiResource('timesheets', TimesheetController::class)->middleware('auth:api');
Route::apiResource('attributes', AttributeController::class)->middleware('auth:api');
