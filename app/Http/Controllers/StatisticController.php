<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DailyStatistic;
use Carbon\Carbon;

class StatisticController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id() ?? 1;

        // Fetch last 30 days of stats
        $stats = DailyStatistic::where('user_id', $userId)
            ->where('date', '>=', Carbon::now()->subDays(30)->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        // Ensure we have today's record even if empty for the summary cards
        $today = $stats->firstWhere('date', Carbon::now()->toDateString());
        if (!$today) {
            $today = [
                'pomodoro_seconds' => 0,
                'tasks_completed' => 0,
                'points_earned' => 0,
            ];
        }

        $yesterday = $stats->firstWhere('date', Carbon::yesterday()->toDateString());
        if (!$yesterday) {
            $yesterday = [
                'pomodoro_seconds' => 0,
                'tasks_completed' => 0,
                'points_earned' => 0,
            ];
        }

        return Inertia::render('Statistics/Index', [
            'stats' => $stats,
            'today' => $today,
            'yesterday' => $yesterday,
        ]);
    }
}
