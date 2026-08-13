<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * Task API Controller
 * 
 * Handles CRUD operations for tasks via REST API.
 */
class TaskController extends Controller
{
    /**
     * Display a listing of the user's tasks.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $showCompleted = $request->boolean('completed', false);

        $tasks = $request->user()->tasks()->with('criteria')
            ->withSum('criteria', 'points')
            ->when($showCompleted, function ($query) {
                return $query->where('is_completed', true);
            }, function ($query) {
                return $query->where('is_completed', false);
            })
            ->orderByDesc('criteria_sum_points')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'is_completed' => false,
        ]);

        if (!empty($validated['criteria_ids'])) {
            $task->criteria()->sync($validated['criteria_ids']);
        }

        $task->load('criteria');
        $task->loadSum('criteria', 'points');

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    /**
     * Display the specified task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $task->load('criteria');
        $task->loadSum('criteria', 'points');

        return response()->json([
            'data' => $task
        ]);
    }

    /**
     * Update the specified task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'is_completed' => 'sometimes|boolean',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $oldIsCompleted = $task->is_completed;

        if (isset($validated['title'])) {
            $task->title = $validated['title'];
        }

        if (array_key_exists('criteria_ids', $validated)) {
            $task->criteria()->sync($validated['criteria_ids'] ?? []);
        }
        
        $newIsCompleted = $validated['is_completed'] ?? $task->is_completed;

        if ($oldIsCompleted !== $newIsCompleted) {
            $task->is_completed = $newIsCompleted;
            $task->completed_at = $newIsCompleted ? now() : null;

            $userId = $request->user()->id;
            $points = $task->criteria()->sum('points');
            $multiplier = $newIsCompleted ? 1 : -1;

            \App\Models\DailyStatistic::adjustStat($userId, now()->toDateString(), 'tasks_completed', 1 * $multiplier);
            \App\Models\DailyStatistic::adjustStat($userId, now()->toDateString(), 'points_earned', $points * $multiplier);

            if ($newIsCompleted) {
                \Illuminate\Support\Facades\Cache::forget('skipped_tasks');
            }
        }

        $task->save();

        $task->load('criteria');
        $task->loadSum('criteria', 'points');

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
