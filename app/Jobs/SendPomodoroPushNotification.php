<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\PomodoroPhaseEndedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SendPomodoroPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $expectedEndsAt;
    protected $phase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $expectedEndsAt, $phase)
    {
        $this->userId = $userId;
        $this->expectedEndsAt = $expectedEndsAt;
        $this->phase = $phase;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cacheKey = "pomodoro_state_{$this->userId}";
        
        // Use default empty state structure if not found
        $state = Cache::get($cacheKey, [
            'phase' => 'focus',
            'status' => 'waiting',
            'ends_at' => null,
            'remaining_seconds' => 0,
            'focus_cycles' => 0,
        ]);

        // Tolerance window of 2 seconds to avoid precision issues
        $tolerance = 2;
        
        // If the current ends_at in cache doesn't match the one we expected, 
        // it means the user paused, stopped, or restarted the timer.
        // We cancel the notification silently.
        if (
            $state['status'] !== 'running' || 
            !$state['ends_at'] ||
            abs($state['ends_at'] - $this->expectedEndsAt) > $tolerance
        ) {
            return;
        }

        // If it matches, we send the push notification.
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new PomodoroPhaseEndedNotification($this->phase));
        }
    }
}
