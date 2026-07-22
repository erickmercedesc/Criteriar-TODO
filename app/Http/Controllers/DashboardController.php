<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Task;
use App\Models\ScoringCriterion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the focused dashboard.
     */
    public function index()
    {
        $skippedIds = Cache::get('skipped_tasks', []);

        // 1. All Pending Tasks ordered by score (excluding skipped)
        $pendingTasks = Task::with('criteria')
            ->where('is_completed', false)
            ->whereNotIn('id', $skippedIds)
            ->withSum('criteria', 'points')
            ->orderByDesc('criteria_sum_points')
            ->orderBy('created_at')
            ->get();

        // 2. Pending Tasks Count (Total pending, including skipped, so the user knows they exist)
        $pendingCount = Task::where('is_completed', false)->count();
        $skippedCount = count($skippedIds);

        // 3. Completed Today Count
        $completedTodayCount = Task::where('is_completed', true)
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // 4. Criteria (for creating new tasks from dashboard)
        $criteria = ScoringCriterion::orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'pendingTasks' => $pendingTasks,
            'stats' => [
                'pending' => $pendingCount,
                'completedToday' => $completedTodayCount,
                'skipped' => $skippedCount,
            ],
            'criteria' => $criteria,
        ]);
    }

    /**
     * Skip a task by adding it to the cache.
     */
    public function skipTask(Task $task)
    {
        $skippedIds = Cache::get('skipped_tasks', []);
        
        if (!in_array($task->id, $skippedIds)) {
            $skippedIds[] = $task->id;
            Cache::put('skipped_tasks', $skippedIds, now()->addDays(7)); // Keep for a week just in case
        }

        return redirect()->back();
    }

    /**
     * Reset the skipped tasks cache.
     */
    public function resetSkipped()
    {
        Cache::forget('skipped_tasks');

        return redirect()->back();
    }
}
