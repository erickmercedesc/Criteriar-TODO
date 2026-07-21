<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/skip/{task}', [\App\Http\Controllers\DashboardController::class, 'skipTask'])->name('dashboard.skip');
    Route::post('/dashboard/reset-skipped', [\App\Http\Controllers\DashboardController::class, 'resetSkipped'])->name('dashboard.reset');

    Route::resource('scoring-criteria', \App\Http\Controllers\ScoringCriterionController::class)->except(['create', 'show', 'edit']);
    
    Route::resource('tasks', \App\Http\Controllers\TaskController::class)->except(['create', 'show', 'edit']);
    Route::patch('tasks/{task}/toggle', [\App\Http\Controllers\TaskController::class, 'toggleComplete'])->name('tasks.toggle');
});
