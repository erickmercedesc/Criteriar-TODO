<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;

class PomodoroController extends Controller
{
    private $cacheKey = 'pomodoro_state'; // Simple key for single user

    public function index()
    {
        $skippedIds = Cache::get('skipped_tasks', []);

        // Get Top Task
        $topTask = Task::with('criteria')
            ->where('is_completed', false)
            ->whereNotIn('id', $skippedIds)
            ->withSum('criteria', 'points')
            ->orderByDesc('criteria_sum_points')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('Pomodoro/Index', [
            'topTask' => $topTask,
            'initialState' => $this->getState(),
        ]);
    }

    public function state()
    {
        return response()->json($this->getState());
    }

    public function start(Request $request)
    {
        $phase = $request->input('phase', 'focus'); // 'focus' or 'break'
        $durationMinutes = $phase === 'focus' ? 25 : 5;
        $durationSeconds = $durationMinutes * 60;

        $state = [
            'status' => $phase,
            'ends_at' => now()->addSeconds($durationSeconds)->timestamp,
            'remaining_seconds' => $durationSeconds,
            'is_paused' => false,
        ];

        Cache::put($this->cacheKey, $state);

        return response()->json($state);
    }

    public function pause()
    {
        $state = $this->getState();

        if ($state['status'] !== 'idle' && !$state['is_paused']) {
            // Calculate remaining seconds
            $remaining = max(0, $state['ends_at'] - now()->timestamp);
            
            $state['is_paused'] = true;
            $state['remaining_seconds'] = $remaining;
            
            Cache::put($this->cacheKey, $state);
        }

        return response()->json($state);
    }

    public function resume()
    {
        $state = $this->getState();

        if ($state['status'] !== 'idle' && $state['is_paused']) {
            $state['is_paused'] = false;
            $state['ends_at'] = now()->addSeconds($state['remaining_seconds'])->timestamp;
            
            Cache::put($this->cacheKey, $state);
        }

        return response()->json($state);
    }

    public function stop()
    {
        $state = $this->getEmptyState();
        Cache::put($this->cacheKey, $state);

        return response()->json($state);
    }

    private function getState()
    {
        return Cache::get($this->cacheKey, $this->getEmptyState());
    }

    private function getEmptyState()
    {
        return [
            'status' => 'idle',
            'ends_at' => null,
            'remaining_seconds' => 0,
            'is_paused' => false,
        ];
    }
}
