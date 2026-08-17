<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScoringCriterion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectScoringCriterionController extends Controller
{
    /**
     * Display a listing of the scoring criteria for a specific project.
     */
    public function index(Request $request, Project $project)
    {
        abort_if($project->user_id !== auth()->id(), 403);

        $criteria = $project->criteria()->orderBy('name')->get();

        return Inertia::render('Projects/ScoringCriteria/Index', [
            'project' => $project,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Store a newly created scoring criterion for the project.
     */
    public function store(Request $request, Project $project)
    {
        abort_if($project->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|between:-100,100',
            'color' => 'required|string|max:7',
            'is_complex_marker' => 'boolean',
        ]);

        $project->criteria()->create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'points' => $validated['points'],
            'color' => $validated['color'],
            'is_complex_marker' => $validated['is_complex_marker'] ?? false,
        ]);

        return redirect()->back();
    }

    /**
     * Update the specified scoring criterion for the project.
     */
    public function update(Request $request, Project $project, ScoringCriterion $scoringCriterion)
    {
        abort_if($project->user_id !== auth()->id() || $scoringCriterion->project_id !== $project->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|between:-100,100',
            'color' => 'required|string|max:7',
            'is_complex_marker' => 'boolean',
        ]);

        $scoringCriterion->update([
            'name' => $validated['name'],
            'points' => $validated['points'],
            'color' => $validated['color'],
            'is_complex_marker' => $validated['is_complex_marker'] ?? false,
        ]);

        return redirect()->back();
    }

    /**
     * Remove the specified scoring criterion from the project.
     */
    public function destroy(Request $request, Project $project, ScoringCriterion $scoringCriterion)
    {
        abort_if($project->user_id !== auth()->id() || $scoringCriterion->project_id !== $project->id, 403);

        $scoringCriterion->delete();

        return redirect()->back();
    }
}
