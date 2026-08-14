<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ScoringCriterion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $showCompleted = $request->boolean('completed', false);
        $projectId = $request->query('project_id');

        $tasks = $request->user()->tasks()->with('criteria', 'project')
            ->withSum('criteria', 'points')
            ->when($showCompleted, function ($query) {
                return $query->where('is_completed', true);
            }, function ($query) {
                return $query->where('is_completed', false);
            })
            ->when($projectId, function ($query, $projectId) {
                return $query->where('project_id', $projectId);
            })
            // Orders by the sum calculated by withSum
            ->orderByDesc('criteria_sum_points')
            // Fallback order by newest
            ->orderBy('created_at')
            ->get();

        $allCriteria = $request->user()->scoringCriteria()->orderBy('name')->get();
        $projects = $request->user()->projects()->orderBy('name')->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'criteria' => $allCriteria,
            'projects' => $projects,
            'filters' => [
                'completed' => $showCompleted,
                'project_id' => $projectId,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'project_id' => $validated['project_id'] ?? null,
            'is_completed' => false,
        ]);

        if (!empty($validated['criteria_ids'])) {
            $task->criteria()->sync($validated['criteria_ids']);
        }

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task->update([
            'title' => $validated['title'],
            'project_id' => $validated['project_id'] ?? null,
        ]);

        // Always sync, if empty it removes all
        $task->criteria()->sync($validated['criteria_ids'] ?? []);

        return redirect()->back();
    }

    /**
     * Toggle the completed status of the specified task.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleComplete(Task $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);

        $isCompleted = !$task->is_completed;
        
        $task->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        $userId = auth()->id();
        $points = $task->criteria()->sum('points');
        $multiplier = $isCompleted ? 1 : -1;

        \App\Models\DailyStatistic::adjustStat($userId, now()->toDateString(), 'tasks_completed', 1 * $multiplier);
        \App\Models\DailyStatistic::adjustStat($userId, now()->toDateString(), 'points_earned', $points * $multiplier);

        if ($isCompleted) {
            \Illuminate\Support\Facades\Cache::forget('skipped_tasks');
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Task $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);

        $task->delete();

        return redirect()->back();
    }
}
