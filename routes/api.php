<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->name('api.')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('tasks', \App\Http\Controllers\Api\TaskController::class);
    Route::apiResource('scoring-criteria', \App\Http\Controllers\Api\ScoringCriterionController::class);
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class);
});
