<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Task API Controller
 * 
 * Handles CRUD operations for tasks via REST API.
 */
class TaskController extends Controller
{
    #[OA\Get(
        path: '/api/tasks',
        summary: 'Listar Tareas',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'completed',
                in: 'query',
                required: false,
                description: 'Filtrar por completadas (1 o true) o pendientes (0 o false)',
                schema: new OA\Schema(type: 'boolean')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de tareas del usuario', content: new OA\JsonContent())
        ]
    )]
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

    #[OA\Post(
        path: '/api/tasks',
        summary: 'Crear Tarea',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Nueva tarea importante'),
                    new OA\Property(property: 'criteria_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tarea creada', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
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

    #[OA\Get(
        path: '/api/tasks/{task}',
        summary: 'Ver Tarea',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'ID de la tarea', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la tarea', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $task->load('criteria');
        $task->loadSum('criteria', 'points');

        return response()->json([
            'data' => $task
        ]);
    }

    #[OA\Put(
        path: '/api/tasks/{task}',
        summary: 'Actualizar Tarea',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'ID de la tarea', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Tarea actualizada'),
                    new OA\Property(property: 'is_completed', type: 'boolean', example: true),
                    new OA\Property(property: 'criteria_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tarea actualizada', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
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

    #[OA\Delete(
        path: '/api/tasks/{task}',
        summary: 'Eliminar Tarea',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'ID de la tarea', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tarea eliminada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function destroy(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
