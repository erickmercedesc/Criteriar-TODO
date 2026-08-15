<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Project API Controller
 * 
 * Handles CRUD operations for projects via REST API.
 */
class ProjectController extends Controller
{
    #[OA\Get(
        path: '/api/projects',
        summary: 'Listar Proyectos',
        security: [['apiAuth' => []]],
        tags: ['Projects'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de proyectos del usuario', content: new OA\JsonContent())
        ]
    )]
    public function index(Request $request)
    {
        $projects = $request->user()->projects()->with('criteria')->orderBy('name')->get();

        return response()->json([
            'data' => $projects
        ]);
    }

    #[OA\Post(
        path: '/api/projects',
        summary: 'Crear Proyecto',
        security: [['apiAuth' => []]],
        tags: ['Projects'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mi Nuevo Proyecto'),
                    new OA\Property(property: 'color', type: 'string', example: '#ff0000')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Proyecto creado', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $project = $request->user()->projects()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        if (!empty($validated['criteria_ids'])) {
            $project->criteria()->sync($validated['criteria_ids']);
        }
        
        $project->load('criteria');

        return response()->json([
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    #[OA\Get(
        path: '/api/projects/{project}',
        summary: 'Ver Proyecto',
        security: [['apiAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, description: 'ID del proyecto', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del proyecto', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(Request $request, Project $project)
    {
        abort_if($project->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $project->load('criteria');

        return response()->json([
            'data' => $project
        ]);
    }

    #[OA\Put(
        path: '/api/projects/{project}',
        summary: 'Actualizar Proyecto',
        security: [['apiAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, description: 'ID del proyecto', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Proyecto actualizado'),
                    new OA\Property(property: 'color', type: 'string', example: '#00ff00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Proyecto actualizado', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function update(Request $request, Project $project)
    {
        abort_if($project->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'nullable|string|max:7',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        if (array_key_exists('name', $validated) || array_key_exists('color', $validated)) {
            $project->update([
                'name' => $validated['name'] ?? $project->name,
                'color' => array_key_exists('color', $validated) ? $validated['color'] : $project->color,
            ]);
        }

        if (array_key_exists('criteria_ids', $validated)) {
            $project->criteria()->sync($validated['criteria_ids'] ?? []);
        }

        $project->load('criteria');

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $project
        ]);
    }

    #[OA\Delete(
        path: '/api/projects/{project}',
        summary: 'Eliminar Proyecto',
        security: [['apiAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, description: 'ID del proyecto', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Proyecto eliminado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(Request $request, Project $project)
    {
        abort_if($project->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }
}
