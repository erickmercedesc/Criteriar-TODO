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

        $tasks = Task::with('criteria')
            ->withSum('criteria', 'points')
            ->when($showCompleted, function ($query) {
                return $query->where('is_completed', true);
            }, function ($query) {
                return $query->where('is_completed', false);
            })
            // Orders by the sum calculated by withSum
            ->orderByDesc('criteria_sum_points')
            // Fallback order by newest
            ->orderBy('created_at')
            ->get();

        $allCriteria = ScoringCriterion::orderBy('name')->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'criteria' => $allCriteria,
            'filters' => [
                'completed' => $showCompleted,
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
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task = Task::create([
            'title' => $validated['title'],
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task->update([
            'title' => $validated['title'],
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
        $isCompleted = !$task->is_completed;
        
        $task->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        $userId = auth()->id() ?? 1;
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
        $task->delete();

        return redirect()->back();
    }
}
