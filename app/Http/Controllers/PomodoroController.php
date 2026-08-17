<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;
use App\Models\Setting;
use App\Jobs\SendPomodoroPushNotification;

class PomodoroController extends Controller
{
    private function getCacheKey()
    {
        $userId = auth()->id();
        return "pomodoro_state_{$userId}";
    }

    public function index(Request $request)
    {
        $skippedIds = Cache::get('skipped_tasks', []);
        $projectId = $request->query('project_id');

        // Get Top Task
        $topTask = $request->user()->tasks()->with('criteria', 'project')
            ->where('is_completed', false)
            ->whereNotIn('id', $skippedIds)
            ->when($projectId, function ($query, $projectId) {
                return $query->where('project_id', $projectId);
            })
            ->withSum('criteria', 'points')
            ->addSelect([
                'project_score' => \App\Models\Project::select('base_score')
                    ->whereColumn('projects.id', 'tasks.project_id')
                    ->limit(1)
            ])
            ->orderByRaw('(COALESCE(project_score, 0) + COALESCE(criteria_sum_points, 0)) DESC')
            ->orderBy('created_at')
            ->first();

        $projects = $request->user()->projects()->with('criteria')->orderBy('name')->get();

        return Inertia::render('Pomodoro/Index', [
            'topTask' => $topTask,
            'initialState' => $this->getState(),
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
            ],
        ]);
    }

    public function state()
    {
        return response()->json($this->getState());
    }

    public function start(Request $request)
    {
        $userId = auth()->id();
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

        SendPomodoroPushNotification::dispatch($userId, $state['ends_at'], $state['phase'])
            ->delay(now()->addSeconds($durationSeconds));

        return response()->json($state);
    }

    public function skip()
    {
        $userId = auth()->id();
        $state = $this->getState();

        $this->advancePhase($state);
        
        // If we skip, we just wait for the user to start the new phase
        $state['status'] = 'waiting';
        $state['ends_at'] = null;
        $state['remaining_seconds'] = 0;

        Cache::put($this->getCacheKey(), $state);
        return response()->json($state);
    }

    public function skipTask(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
        ]);

        $skippedIds = Cache::get('skipped_tasks', []);
        if (!in_array($validated['task_id'], $skippedIds)) {
            $skippedIds[] = $validated['task_id'];
            Cache::put('skipped_tasks', $skippedIds, now()->addDay());
        }

        // Return current state so frontend can update topTask implicitly (since topTask is fetched on load)
        // Wait, Pomodoro/Index.vue fetches topTask on initial load, but the API `/pomodoro/state` just returns state.
        // Actually, if we skip a task, we need the frontend to get the new topTask. Let's return the state, but 
        // the frontend can just reload the page using Inertia.
        return response()->json($this->getState());
    }

    public function nextPhase()
    {
        // Deprecated: State transitions happen automatically in getState()
        return response()->json($this->getState());
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

            SendPomodoroPushNotification::dispatch(auth()->id(), $state['ends_at'], $state['phase'])
                ->delay(now()->addSeconds($state['remaining_seconds']));
        }

        return response()->json($state);
    }

    public function stop()
    {
        $state = $this->getEmptyState();
        Cache::put($this->getCacheKey(), $state);

        return response()->json($state);
    }

    public function getStateForUser($userId)
    {
        $cacheKey = "pomodoro_state_{$userId}";
        $state = Cache::get($cacheKey, $this->getEmptyState());
        
        if ($state['status'] === 'running' && $state['ends_at'] <= time()) {
            $this->processCompletion($state, $userId);
            Cache::put($cacheKey, $state);
        }

        return $state;
    }

    private function getState()
    {
        $userId = auth()->id();
        return $this->getStateForUser($userId);
    }

    private function processCompletion(&$state, $userId)
    {
        $webhookUrl = Setting::getForUser($userId, 'pomodoro_webhook', null);
        
        if ($webhookUrl) {
            try {
                \Illuminate\Support\Facades\Http::timeout(3)->post($webhookUrl, [
                    'event' => 'pomodoro_finished',
                    'phase' => $state['phase'],
                    'user_id' => $userId,
                    'completed_at' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                // Silently ignore webhook errors to avoid breaking the timer state
            }
        }
        
        if ($state['phase'] === 'focus') {
            $durationMinutes = (int) Setting::getForUser($userId, 'pomo_time', 25);
            $durationSeconds = $durationMinutes * 60;
            \App\Models\DailyStatistic::adjustStat($userId, now()->toDateString(), 'pomodoro_seconds', $durationSeconds);
        }

        $this->advancePhase($state);
        $state['status'] = 'waiting';
        $state['ends_at'] = null;
        $state['remaining_seconds'] = 0;
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
