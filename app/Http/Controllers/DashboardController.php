<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Task;
use App\Models\ScoringCriterion;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the focused dashboard.
     */
    public function index()
    {
        // 1. All Pending Tasks ordered by score
        $pendingTasks = Task::with('criteria')
            ->where('is_completed', false)
            ->withSum('criteria', 'points')
            ->orderByDesc('criteria_sum_points')
            ->orderByDesc('created_at')
            ->get();

        // 2. Pending Tasks Count
        $pendingCount = $pendingTasks->count();

        // 3. Completed Today Count
        $completedTodayCount = Task::where('is_completed', true)
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // 4. Criteria (for creating new tasks from dashboard)
        $criteria = ScoringCriterion::orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'pendingTasks' => $pendingTasks,
            'stats' => [
                'pending' => $pendingCount,
                'completedToday' => $completedTodayCount,
            ],
            'criteria' => $criteria,
        ]);
    }
}
