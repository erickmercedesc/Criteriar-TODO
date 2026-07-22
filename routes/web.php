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

    // Pomodoro
    Route::get('/pomodoro', [\App\Http\Controllers\PomodoroController::class, 'index'])->name('pomodoro.index');
    Route::get('/api/pomodoro/state', [\App\Http\Controllers\PomodoroController::class, 'state'])->name('pomodoro.state');
    Route::post('/api/pomodoro/start', [\App\Http\Controllers\PomodoroController::class, 'start'])->name('pomodoro.start');
    Route::post('/api/pomodoro/pause', [\App\Http\Controllers\PomodoroController::class, 'pause'])->name('pomodoro.pause');
    Route::post('/api/pomodoro/resume', [\App\Http\Controllers\PomodoroController::class, 'resume'])->name('pomodoro.resume');
    Route::post('/api/pomodoro/stop', [\App\Http\Controllers\PomodoroController::class, 'stop'])->name('pomodoro.stop');
    Route::post('/api/pomodoro/skip', [\App\Http\Controllers\PomodoroController::class, 'skip'])->name('pomodoro.skip');
    Route::post('/api/pomodoro/next-phase', [\App\Http\Controllers\PomodoroController::class, 'nextPhase'])->name('pomodoro.nextPhase');

    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    Route::resource('scoring-criteria', \App\Http\Controllers\ScoringCriterionController::class)->except(['create', 'show', 'edit']);
    
    Route::resource('tasks', \App\Http\Controllers\TaskController::class)->except(['create', 'show', 'edit']);
    Route::patch('tasks/{task}/toggle', [\App\Http\Controllers\TaskController::class, 'toggleComplete'])->name('tasks.toggle');
});
