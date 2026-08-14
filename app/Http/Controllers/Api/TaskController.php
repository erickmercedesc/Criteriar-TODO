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
            ),
            new OA\Parameter(
                name: 'project_id',
                in: 'query',
                required: false,
                description: 'Filtrar por ID de proyecto',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'criteria_ids',
                in: 'query',
                required: false,
                description: 'Filtrar por IDs de criterios (separados por comas, ej: 1,2,3)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'min_score',
                in: 'query',
                required: false,
                description: 'Filtrar por puntaje mínimo',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'max_score',
                in: 'query',
                required: false,
                description: 'Filtrar por puntaje máximo',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de tareas del usuario', content: new OA\JsonContent())
        ]
    )]
    public function index(Request $request)
    {
        $showCompleted = $request->boolean('completed', false);
        $projectId = $request->query('project_id');
        $criteriaIds = $request->query('criteria_ids');
        $minScore = $request->query('min_score');
        $maxScore = $request->query('max_score');

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
            ->when($criteriaIds, function ($query, $criteriaIds) {
                $ids = is_string($criteriaIds) ? explode(',', $criteriaIds) : $criteriaIds;
                return $query->whereHas('criteria', function ($q) use ($ids) {
                    $q->whereIn('scoring_criteria.id', $ids);
                });
            })
            ->when($minScore !== null, function ($query) use ($minScore) {
                return $query->having('criteria_sum_points', '>=', (int) $minScore);
            })
            ->when($maxScore !== null, function ($query) use ($maxScore) {
                return $query->having('criteria_sum_points', '<=', (int) $maxScore);
            })
            ->orderByDesc('criteria_sum_points')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $tasks
        ]);
    }

    #[OA\Get(
        path: '/api/tasks/top',
        summary: 'Top 3 Tareas',
        description: 'Obtiene las 3 tareas pendientes con mayor puntuación para el día.',
        security: [['apiAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'project_id',
                in: 'query',
                required: false,
                description: 'Filtrar por ID de proyecto',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Top 3 tareas pendientes ordenadas por puntaje', content: new OA\JsonContent())
        ]
    )]
    public function top(Request $request)
    {
        $projectId = $request->query('project_id');

        $tasks = $request->user()->tasks()->with('criteria', 'project')
            ->where('is_completed', false)
            ->when($projectId, function ($query, $projectId) {
                return $query->where('project_id', $projectId);
            })
            ->withSum('criteria', 'points')
            ->orderByDesc('criteria_sum_points')
            ->orderBy('created_at')
            ->take(3)
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
                    new OA\Property(property: 'notes', type: 'string', example: 'Contexto de la tarea...', nullable: true),
                    new OA\Property(property: 'project_id', type: 'integer', example: 1, nullable: true),
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
            'notes' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
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
                    new OA\Property(property: 'notes', type: 'string', example: 'Contexto de la tarea...', nullable: true),
                    new OA\Property(property: 'is_completed', type: 'boolean', example: true),
                    new OA\Property(property: 'project_id', type: 'integer', example: 1, nullable: true),
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
            'notes' => 'sometimes|nullable|string',
            'is_completed' => 'sometimes|boolean',
            'project_id' => 'nullable|exists:projects,id',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $oldIsCompleted = $task->is_completed;

        if (isset($validated['title'])) {
            $task->title = $validated['title'];
        }

        if (array_key_exists('notes', $validated)) {
            $task->notes = $validated['notes'];
        }

        if (array_key_exists('project_id', $validated)) {
            $task->project_id = $validated['project_id'];
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
