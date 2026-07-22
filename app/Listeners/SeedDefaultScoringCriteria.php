<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SeedDefaultScoringCriteria
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        $defaultCriteria = [
            ['name' => 'Trabajo', 'points' => 20, 'color' => '#38BDF8'],
            ['name' => 'Salud', 'points' => 15, 'color' => '#22C55E'],
            ['name' => 'Aprendizaje', 'points' => 10, 'color' => '#6C63FF'],
            ['name' => 'Procrastinación', 'points' => -5, 'color' => '#EF4444'],
        ];

        foreach ($defaultCriteria as $criterion) {
            $user->scoringCriteria()->create($criterion);
        }
    }
}
