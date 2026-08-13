<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScoringCriterion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ScoringCriterion API Controller
 * 
 * Handles CRUD operations for scoring criteria via REST API.
 */
class ScoringCriterionController extends Controller
{
    /**
     * Display a listing of the user's scoring criteria.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $criteria = $request->user()->scoringCriteria()->orderByDesc('points')->get();

        return response()->json([
            'data' => $criteria
        ]);
    }

    /**
     * Store a newly created scoring criterion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('scoring_criteria', 'name')->where('user_id', $request->user()->id),
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

    /**
     * Display the specified scoring criterion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ScoringCriterion  $scoringCriterion
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        return response()->json([
            'data' => $scoringCriterion
        ]);
    }

    /**
     * Update the specified scoring criterion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ScoringCriterion  $scoringCriterion
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('scoring_criteria', 'name')->where('user_id', $request->user()->id)->ignore($scoringCriterion->id),
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

    /**
     * Remove the specified scoring criterion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ScoringCriterion  $scoringCriterion
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, ScoringCriterion $scoringCriterion)
    {
        abort_if($scoringCriterion->user_id !== $request->user()->id, 403, 'Unauthorized action.');

        $scoringCriterion->delete();

        return response()->json([
            'message' => 'Criterion deleted successfully'
        ], 200);
    }
}
