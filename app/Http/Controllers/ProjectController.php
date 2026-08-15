<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $projects = $request->user()->projects()->with('criteria')->withCount(['tasks' => function ($query) {
            $query->where('is_completed', false);
        }])->orderBy('name')->get();

        $allCriteria = $request->user()->scoringCriteria()->orderBy('name')->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'criteria' => $allCriteria,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        abort_if($project->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'exists:scoring_criteria,id',
        ]);

        $project->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        $project->criteria()->sync($validated['criteria_ids'] ?? []);

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        abort_if($project->user_id !== auth()->id(), 403);

        $project->delete();

        return redirect()->back()->with('success', 'Project deleted successfully.');
    }
}
