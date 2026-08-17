<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\ScoringCriterion;
use App\Models\Task;
use App\Models\DailyStatistic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create or retrieve test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Clean existing data for this user to ensure idempotent seeding
        $user->tasks()->delete();
        $user->projects()->delete();
        $user->scoringCriteria()->delete();
        $user->dailyStatistics()->delete();

        // 2. Seed Global Scoring Criteria (project_id = null)
        $globalCriteriaData = [
            'urgent' => [
                'name' => 'Trabajo Urgente',
                'points' => 25,
                'color' => '#EF4444',
                'is_complex_marker' => false,
                'project_id' => null,
            ],
            'complex' => [
                'name' => 'Alta Complejidad (Anti-Burnout)',
                'points' => 20,
                'color' => '#F59E0B',
                'is_complex_marker' => true,
                'project_id' => null,
            ],
            'growth' => [
                'name' => 'Aprendizaje & Crecimiento',
                'points' => 15,
                'color' => '#6C63FF',
                'is_complex_marker' => false,
                'project_id' => null,
            ],
            'penalty' => [
                'name' => 'Procrastinación',
                'points' => -10,
                'color' => '#A78BFA',
                'is_complex_marker' => false,
                'project_id' => null,
            ],
        ];

        $globalCriteria = [];
        foreach ($globalCriteriaData as $key => $data) {
            $globalCriteria[$key] = $user->scoringCriteria()->create($data);
        }

        // 3. Seed Projects with Base Score
        $projectsData = [
            'secondbrain' => [
                'name' => 'SecondBrain Core',
                'color' => '#6C63FF',
                'base_score' => 40,
            ],
            'freelance' => [
                'name' => 'Cliente Freelance (App Móvil)',
                'color' => '#F59E0B',
                'base_score' => 50,
            ],
            'fitness' => [
                'name' => 'Fitness & Salud',
                'color' => '#22C55E',
                'base_score' => 20,
            ],
            'studies' => [
                'name' => 'Certificación AWS & DevOps',
                'color' => '#38BDF8',
                'base_score' => 30,
            ],
        ];

        $projects = [];
        foreach ($projectsData as $key => $data) {
            $projects[$key] = $user->projects()->create($data);
        }

        // 4. Seed Project-Specific Criteria
        $projectCriteriaData = [
            'stripe' => [
                'project_id' => $projects['freelance']->id,
                'name' => 'Integración Stripe & Checkout',
                'points' => 35,
                'color' => '#22C55E',
                'is_complex_marker' => false,
            ],
            'client_call' => [
                'project_id' => $projects['freelance']->id,
                'name' => 'Reunión con Stakeholders',
                'points' => 15,
                'color' => '#38BDF8',
                'is_complex_marker' => false,
            ],
            'core_refactor' => [
                'project_id' => $projects['secondbrain']->id,
                'name' => 'Refactor Arquitectura & DRY',
                'points' => 30,
                'color' => '#6C63FF',
                'is_complex_marker' => false,
            ],
            'db_optimization' => [
                'project_id' => $projects['secondbrain']->id,
                'name' => 'Optimización de Consultas SQL',
                'points' => 20,
                'color' => '#38BDF8',
                'is_complex_marker' => false,
            ],
            'hiit' => [
                'project_id' => $projects['fitness']->id,
                'name' => 'Entrenamiento de Alta Intensidad (HIIT)',
                'points' => 15,
                'color' => '#22C55E',
                'is_complex_marker' => false,
            ],
            'aws_hands_on' => [
                'project_id' => $projects['studies']->id,
                'name' => 'Hands-on Labs en AWS Console',
                'points' => 25,
                'color' => '#38BDF8',
                'is_complex_marker' => true,
            ],
        ];

        $projCriteria = [];
        foreach ($projectCriteriaData as $key => $data) {
            $projCriteria[$key] = $user->scoringCriteria()->create($data);
        }

        // 5. Seed Tasks (with notes and combined criteria)
        $tasksData = [
            [
                'title' => 'Implementar webhook y checkout de Stripe',
                'notes' => "Integrar Stripe Checkout Session con webhooks seguros.\nValidar firmas de eventos y actualizar suscripciones en segundo plano.",
                'project_id' => $projects['freelance']->id,
                'criteria' => [$projCriteria['stripe']->id, $globalCriteria['complex']->id, $globalCriteria['urgent']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Refactorizar arquitectura de estado y selector de proyectos',
                'notes' => "Unificar uso de LocalStorage reactivo con composables.\nSincronizar entre Dashboard, Pomodoro y Tareas.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$projCriteria['core_refactor']->id, $globalCriteria['urgent']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Práctica de laboratorio: Arquitecturas VPC y subredes en AWS',
                'notes' => "Configurar NAT Gateway, Internet Gateway y tablas de ruteo seguras.",
                'project_id' => $projects['studies']->id,
                'criteria' => [$projCriteria['aws_hands_on']->id, $globalCriteria['growth']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Enviar propuesta comercial y demo a nuevo cliente',
                'notes' => "Revisar alcance, estimación en horas y cotización en USD.",
                'project_id' => $projects['freelance']->id,
                'criteria' => [$projCriteria['client_call']->id, $globalCriteria['urgent']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Entrenamiento de fuerza y movilidad 45 min',
                'notes' => "Rutina de torso/pierna enfocado en hipertrofia y estiramientos para espalda.",
                'project_id' => $projects['fitness']->id,
                'criteria' => [$projCriteria['hiit']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Lectura de 30 páginas de hábitos de alto rendimiento',
                'notes' => "Resumir ideas principales en el cuaderno de notas personal.",
                'project_id' => null, // Global Task
                'criteria' => [$globalCriteria['growth']->id],
                'is_completed' => false,
            ],
            // Completed tasks
            [
                'title' => 'Configuración inicial del repositorio y entorno Docker',
                'notes' => "Configuración de PHP 8.4, MariaDB y scripts de npm.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$projCriteria['core_refactor']->id],
                'is_completed' => true,
                'completed_at' => now()->subDay(),
            ],
            [
                'title' => 'Comprar suplementos de creatina y electrolitos',
                'notes' => "Adquirir producto certificado para recuperación muscular.",
                'project_id' => $projects['fitness']->id,
                'criteria' => [$projCriteria['hiit']->id],
                'is_completed' => true,
                'completed_at' => now()->subHours(5),
            ],
            [
                'title' => 'Revisar pull request de notificaciones Web Push',
                'notes' => "Validar compatibilidad con Safari iOS y Chrome Android.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$projCriteria['core_refactor']->id],
                'is_completed' => true,
                'completed_at' => now()->subHours(2),
            ],
        ];

        foreach ($tasksData as $data) {
            $criteriaIds = $data['criteria'] ?? [];
            unset($data['criteria']);

            $task = $user->tasks()->create($data);

            if (!empty($criteriaIds)) {
                $task->criteria()->sync($criteriaIds);
            }
        }

        // 6. Seed Daily Statistics (past 7 days)
        $pastDays = [
            ['date' => now()->subDays(6)->toDateString(), 'pomodoro_seconds' => 5400, 'tasks_completed' => 3, 'points_earned' => 210],
            ['date' => now()->subDays(5)->toDateString(), 'pomodoro_seconds' => 7200, 'tasks_completed' => 4, 'points_earned' => 280],
            ['date' => now()->subDays(4)->toDateString(), 'pomodoro_seconds' => 3600, 'tasks_completed' => 2, 'points_earned' => 140],
            ['date' => now()->subDays(3)->toDateString(), 'pomodoro_seconds' => 9000, 'tasks_completed' => 5, 'points_earned' => 360],
            ['date' => now()->subDays(2)->toDateString(), 'pomodoro_seconds' => 6000, 'tasks_completed' => 3, 'points_earned' => 220],
            ['date' => now()->subDays(1)->toDateString(), 'pomodoro_seconds' => 7500, 'tasks_completed' => 4, 'points_earned' => 310],
            ['date' => now()->toDateString(), 'pomodoro_seconds' => 3000, 'tasks_completed' => 2, 'points_earned' => 170],
        ];

        foreach ($pastDays as $stat) {
            $user->dailyStatistics()->create($stat);
        }
    }
}
