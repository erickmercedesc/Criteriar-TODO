<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\PomodoroController;

class ProcessPomodoros extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pomodoro:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all active Pomodoro timers to check for expiration and trigger webhooks if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new PomodoroController();
        $users = User::all();
        
        foreach ($users as $user) {
            // getStateForUser will automatically check if the timer expired 
            // and trigger the webhook if necessary
            $controller->getStateForUser($user->id);
        }

        $this->info('Processed ' . $users->count() . ' user pomodoros.');
    }
}
