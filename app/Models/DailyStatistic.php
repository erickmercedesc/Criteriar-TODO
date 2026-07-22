<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'pomodoro_seconds',
        'tasks_completed',
        'points_earned'
    ];

    public static function adjustStat($userId, $date, $column, $amount)
    {
        $stat = self::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['pomodoro_seconds' => 0, 'tasks_completed' => 0, 'points_earned' => 0]
        );

        $stat->increment($column, $amount);
    }
}
