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
        $phase = $request->input('phase', 'focus'); // 'focus' or 'break'
        $state = $this->getState();

        if ($phase === 'focus') {
            $durationMinutes = (int) Setting::getForUser($userId, 'pomo_time', 25);
            $newStatus = 'focus';
        } else {
            // It's a break
            $state['focus_cycles']++;
            
            if ($state['focus_cycles'] % 4 === 0) {
                $durationMinutes = (int) Setting::getForUser($userId, 'long_break_time', 15);
                $newStatus = 'long_break';
            } else {
                $durationMinutes = (int) Setting::getForUser($userId, 'short_break_time', 5);
                $newStatus = 'short_break';
            }
        }

        $durationSeconds = $durationMinutes * 60;

        $state['status'] = $newStatus;
        $state['ends_at'] = now()->addSeconds($durationSeconds)->timestamp;
        $state['remaining_seconds'] = $durationSeconds;
        $state['is_paused'] = false;

        Cache::put($this->getCacheKey(), $state);

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
            
            Cache::put($this->getCacheKey(), $state);
        }

        return response()->json($state);
    }

    public function resume()
    {
        $state = $this->getState();

        if ($state['status'] !== 'idle' && $state['is_paused']) {
            $state['is_paused'] = false;
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
            'status' => 'idle',
            'ends_at' => null,
            'remaining_seconds' => 0,
            'is_paused' => false,
            'focus_cycles' => 0,
        ];
    }
}
