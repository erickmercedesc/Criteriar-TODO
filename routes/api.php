<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->name('api.')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/tasks/top', [\App\Http\Controllers\Api\TaskController::class, 'top']);
    Route::apiResource('tasks', \App\Http\Controllers\Api\TaskController::class);
    Route::apiResource('scoring-criteria', \App\Http\Controllers\Api\ScoringCriterionController::class);
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class);

    Route::get('/statistics', function (Request $request) {
        $stats = \App\Models\DailyStatistic::where('user_id', $request->user()->id)
            ->where('date', '>=', \Carbon\Carbon::now()->subDays(30)->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        $today = $stats->firstWhere('date', \Carbon\Carbon::now()->toDateString()) ?? [
            'date' => \Carbon\Carbon::now()->toDateString(),
            'pomodoro_seconds' => 0,
            'tasks_completed' => 0,
            'points_earned' => 0,
        ];

        return response()->json([
            'data' => [
                'today' => $today,
                'history_30_days' => $stats,
            ]
        ]);
    })->name('statistics');
});
