<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;
use App\Models\Setting;

class PomodoroController extends Controller
{
    private function getCacheKey()
    {
        $userId = auth()->id() ?? 1;
        return "pomodoro_state_{$userId}";
    }

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
        $userId = auth()->id() ?? 1;
        $state = $this->getState();
        $phase = $state['phase'];

        if ($phase === 'focus') {
            $durationMinutes = (int) Setting::getForUser($userId, 'pomo_time', 25);
        } else if ($phase === 'long_break') {
            $durationMinutes = (int) Setting::getForUser($userId, 'long_break_time', 15);
        } else {
            $durationMinutes = (int) Setting::getForUser($userId, 'short_break_time', 5);
        }

        $durationSeconds = $durationMinutes * 60;

        $state['status'] = 'running';
        $state['ends_at'] = now()->addSeconds($durationSeconds)->timestamp;
        $state['remaining_seconds'] = $durationSeconds;

        Cache::put($this->getCacheKey(), $state);

        return response()->json($state);
    }

    public function skip()
    {
        $userId = auth()->id() ?? 1;
        $state = $this->getState();

        $this->advancePhase($state);
        
        // If we skip, we just wait for the user to start the new phase
        $state['status'] = 'waiting';
        $state['ends_at'] = null;
        $state['remaining_seconds'] = 0;

        Cache::put($this->getCacheKey(), $state);
        return response()->json($state);
    }

    public function nextPhase()
    {
        // Called when timer reaches 0
        $state = $this->getState();
        if ($state['status'] === 'running') {
            $this->advancePhase($state);
            $state['status'] = 'waiting';
            $state['ends_at'] = null;
            $state['remaining_seconds'] = 0;
            Cache::put($this->getCacheKey(), $state);
        }
        return response()->json($state);
    }

    private function advancePhase(&$state)
    {
        if ($state['phase'] === 'focus') {
            // Finished a focus, so we increment cycle and decide next break
            $state['focus_cycles']++;
            if ($state['focus_cycles'] % 4 === 0) {
                $state['phase'] = 'long_break';
            } else {
                $state['phase'] = 'short_break';
            }
        } else {
            // Finished a break, next is focus
            $state['phase'] = 'focus';
        }
    }

    public function pause()
    {
        $state = $this->getState();

        if ($state['status'] === 'running') {
            $remaining = max(0, $state['ends_at'] - now()->timestamp);
            $state['status'] = 'paused';
            $state['remaining_seconds'] = $remaining;
            Cache::put($this->getCacheKey(), $state);
        }

        return response()->json($state);
    }

    public function resume()
    {
        $state = $this->getState();

        if ($state['status'] === 'paused') {
            $state['status'] = 'running';
            $state['ends_at'] = now()->addSeconds($state['remaining_seconds'])->timestamp;
            Cache::put($this->getCacheKey(), $state);
        }

        return response()->json($state);
    }

    public function stop()
    {
        $state = $this->getEmptyState();
        Cache::put($this->getCacheKey(), $state);

        return response()->json($state);
    }

    private function getState()
    {
        return Cache::get($this->getCacheKey(), $this->getEmptyState());
    }

    private function getEmptyState()
    {
        return [
            'phase' => 'focus', // 'focus', 'short_break', 'long_break'
            'status' => 'waiting', // 'waiting', 'running', 'paused'
            'ends_at' => null,
            'remaining_seconds' => 0,
            'focus_cycles' => 0,
        ];
    }
}
