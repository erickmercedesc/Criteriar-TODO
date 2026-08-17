<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScoringCriterion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * ScoringCriterion API Controller
 * 
 * Handles CRUD operations for scoring criteria via REST API.
 */
class ScoringCriterionController extends Controller
{
    #[OA\Get(
        path: '/api/scoring-criteria',
        summary: 'Listar Criterios',
        security: [['apiAuth' => []]],
        tags: ['Scoring Criteria'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de criterios de puntuación', content: new OA\JsonContent())
        ]
    )]
    public function index(Request $request)
    {
        $projectId = $request->query('project_id');
        $onlyGlobal = $request->boolean('global', false);

        $criteria = $request->user()->scoringCriteria()
            ->when($onlyGlobal, function ($q) {
                return $q->whereNull('project_id');
            })
            ->when($projectId !== null, function ($q) use ($projectId) {
                return $q->where('project_id', $projectId);
            })
            ->orderByDesc('points')
            ->get();

        return response()->json([
            'data' => $criteria
        ]);
    }

    #[OA\Post(
        path: '/api/scoring-criteria',
        summary: 'Crear Criterio',
        security: [['apiAuth' => []]],
        tags: ['Scoring Criteria'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'points', 'color'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Ejercicio'),
                    new OA\Property(property: 'points', type: 'integer', example: 10),
                    new OA\Property(property: 'color', type: 'string', example: '#22C55E'),
                    new OA\Property(property: 'project_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'is_complex_marker', type: 'boolean', example: false)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criterio creado', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('scoring_criteria', 'name')
                    ->where('user_id', $request->user()->id)
                    ->where(function ($q) use ($request) {
                        return $request->filled('project_id')
                            ? $q->where('project_id', $request->input('project_id'))
                            : $q->whereNull('project_id');
                    }),
            ],
            'points' => 'required|integer|min:-100|max:100',
            'color' => 'required|string|size:7|starts_with:#',
            'is_complex_marker' => 'boolean',
        ]);

        $criterion = $request->user()->scoringCriteria()->create($validated);

        return response()->json([
            'message' => 'Criterion created successfully',
            'data' => $criterion
        ], 201);
    }

    #[OA\Get(
        path: '/api/scoring-criteria/{criterion}',
        summary: 'Ver Criterio',
        security: [['apiAuth' => []]],
        tags: ['Scoring Criteria'],
        parameters: [
            new OA\Parameter(name: 'criterion', in: 'path', required: true, description: 'ID del criterio', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del criterio', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        return response()->json([
            'data' => $scoringCriterion
        ]);
    }

    #[OA\Put(
        path: '/api/scoring-criteria/{criterion}',
        summary: 'Actualizar Criterio',
        security: [['apiAuth' => []]],
        tags: ['Scoring Criteria'],
        parameters: [
            new OA\Parameter(name: 'criterion', in: 'path', required: true, description: 'ID del criterio', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Ejercicio intenso'),
                    new OA\Property(property: 'points', type: 'integer', example: 20),
                    new OA\Property(property: 'color', type: 'string', example: '#22C55E'),
                    new OA\Property(property: 'project_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'is_complex_marker', type: 'boolean', example: false)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Criterio actualizado', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function update(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $targetProjectId = $request->has('project_id') ? $request->input('project_id') : $scoringCriterion->project_id;

        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('scoring_criteria', 'name')
                    ->where('user_id', $request->user()->id)
                    ->where(function ($q) use ($targetProjectId) {
                        return $targetProjectId !== null
                            ? $q->where('project_id', $targetProjectId)
                            : $q->whereNull('project_id');
                    })
                    ->ignore($scoringCriterion->id),
            ],
            'points' => 'sometimes|required|integer|min:-100|max:100',
            'color' => 'sometimes|required|string|size:7|starts_with:#',
            'is_complex_marker' => 'sometimes|boolean',
        ]);

        $scoringCriterion->update($validated);

        return response()->json([
            'message' => 'Criterion updated successfully',
            'data' => $scoringCriterion
        ]);
    }

    #[OA\Delete(
        path: '/api/scoring-criteria/{criterion}',
        summary: 'Eliminar Criterio',
        security: [['apiAuth' => []]],
        tags: ['Scoring Criteria'],
        parameters: [
            new OA\Parameter(name: 'criterion', in: 'path', required: true, description: 'ID del criterio', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Criterio eliminado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $scoringCriterion->delete();

        return response()->json([
            'message' => 'Criterion deleted successfully'
        ], 200);
    }
}
