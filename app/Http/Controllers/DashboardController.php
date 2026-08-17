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
    public function index(Request $request)
    {
        $skippedIds = Cache::get('skipped_tasks', []);

        $projectId = $request->query('project_id');

        // 1. All Pending Tasks ordered by score (excluding skipped)
        $pendingTasks = $request->user()->tasks()->with('criteria', 'project')
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
            ->orderByRaw('COALESCE(project_score, 0) DESC, COALESCE(criteria_sum_points, 0) DESC')
            ->orderBy('created_at')
            ->get();

        // 2. Pending Tasks Count (Total pending, including skipped, so the user knows they exist)
        $pendingCount = $request->user()->tasks()->where('is_completed', false)->count();
        $skippedCount = count($skippedIds);

        // 3. Completed Today Count
        $completedTodayCount = $request->user()->tasks()->where('is_completed', true)
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // 4. Criteria (for creating new tasks from dashboard)
        $criteria = $request->user()->scoringCriteria()->orderBy('name')->get();

        // 5. Projects
        $projects = $request->user()->projects()->with('criteria')->orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'pendingTasks' => $pendingTasks,
            'stats' => [
                'pending' => $pendingCount,
                'completedToday' => $completedTodayCount,
                'skipped' => $skippedCount,
            ],
            'criteria' => $criteria,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
            ],
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
