<?php

namespace App\Http\Controllers;

use App\Models\ScoringCriterion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ScoringCriterionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('ScoringCriteria/Index', [
            'criteria' => ScoringCriterion::orderByDesc('points')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:scoring_criteria,name',
            'points' => 'required|integer|min:-100|max:100',
            'color' => 'required|string|size:7|starts_with:#',
        ]);

        ScoringCriterion::create($validated);

        return redirect()->back()->with('success', 'Criterion created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScoringCriterion $scoringCriterion)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('scoring_criteria', 'name')->ignore($scoringCriterion->id),
            ],
            'points' => 'required|integer|min:-100|max:100',
            'color' => 'required|string|size:7|starts_with:#',
        ]);

        $scoringCriterion->update($validated);

        return redirect()->back()->with('success', 'Criterion updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScoringCriterion $scoringCriterion)
    {
        $scoringCriterion->delete();

        return redirect()->back()->with('success', 'Criterion deleted successfully.');
    }
}
