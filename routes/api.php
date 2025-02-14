<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Attributes
Route::apiResource('attributes', AttributeController::class);

// Projects
Route::apiResource('projects', ProjectController::class);
