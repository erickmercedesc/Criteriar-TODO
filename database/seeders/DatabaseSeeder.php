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

        // 2. Seed Scoring Criteria
        $criteriaData = [
            'money' => [
                'name' => 'Genera Dinero',
                'points' => 30,
                'color' => '#22C55E',
                'is_complex_marker' => false,
            ],
            'urgent' => [
                'name' => 'Trabajo Urgente',
                'points' => 25,
                'color' => '#EF4444',
                'is_complex_marker' => false,
            ],
            'complex' => [
                'name' => 'Alta Complejidad (Anti-Burnout)',
                'points' => 20,
                'color' => '#F59E0B',
                'is_complex_marker' => true,
            ],
            'health' => [
                'name' => 'Salud & Bienestar',
                'points' => 15,
                'color' => '#38BDF8',
                'is_complex_marker' => false,
            ],
            'growth' => [
                'name' => 'Aprendizaje & Crecimiento',
                'points' => 10,
                'color' => '#6C63FF',
                'is_complex_marker' => false,
            ],
            'penalty' => [
                'name' => 'Procrastinación',
                'points' => -10,
                'color' => '#A78BFA',
                'is_complex_marker' => false,
            ],
        ];

        $criteria = [];
        foreach ($criteriaData as $key => $data) {
            $criteria[$key] = $user->scoringCriteria()->create($data);
        }

        // 3. Seed Projects & attach Project Criteria
        $projectsData = [
            'secondbrain' => [
                'name' => 'SecondBrain Core',
                'color' => '#6C63FF',
                'criteria' => [$criteria['money']->id, $criteria['urgent']->id],
            ],
            'freelance' => [
                'name' => 'Cliente Freelance (App Móvil)',
                'color' => '#F59E0B',
                'criteria' => [$criteria['money']->id, $criteria['complex']->id],
            ],
            'fitness' => [
                'name' => 'Fitness & Salud',
                'color' => '#22C55E',
                'criteria' => [$criteria['health']->id],
            ],
            'studies' => [
                'name' => 'Certificación AWS & DevOps',
                'color' => '#38BDF8',
                'criteria' => [$criteria['growth']->id, $criteria['complex']->id],
            ],
        ];

        $projects = [];
        foreach ($projectsData as $key => $data) {
            $project = $user->projects()->create([
                'name' => $data['name'],
                'color' => $data['color'],
            ]);

            if (!empty($data['criteria'])) {
                $project->criteria()->sync($data['criteria']);
            }

            $projects[$key] = $project;
        }

        // 4. Seed Tasks
        $tasksData = [
            [
                'title' => 'Implementar webhook y checkout de Stripe',
                'notes' => "Integrar Stripe Checkout Session con webhooks seguros.\nValidar firmas de eventos y actualizar suscripciones en segundo plano.",
                'project_id' => $projects['freelance']->id,
                'criteria' => [$criteria['money']->id, $criteria['complex']->id, $criteria['urgent']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Refactorizar arquitectura de estado y selector de proyectos',
                'notes' => "Unificar uso de LocalStorage reactivo con composables.\nSincronizar entre Dashboard, Pomodoro y Tareas.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$criteria['urgent']->id, $criteria['growth']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Entrenamiento de fuerza y movilidad 45 min',
                'notes' => "Rutina de torso/pierna enfocado en hipertrofia y estiramientos para espalda.",
                'project_id' => $projects['fitness']->id,
                'criteria' => [$criteria['health']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Práctica de laboratorio: Arquitecturas VPC y subredes en AWS',
                'notes' => "Configurar NAT Gateway, Internet Gateway y tablas de ruteo seguras.",
                'project_id' => $projects['studies']->id,
                'criteria' => [$criteria['growth']->id, $criteria['complex']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Enviar propuesta comercial de desarrollo a nuevo prospecto',
                'notes' => "Revisar alcance, estimación en horas y cotización en USD.",
                'project_id' => $projects['freelance']->id,
                'criteria' => [$criteria['money']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Revisar pull request de notificaciones Web Push',
                'notes' => "Comprobar permisos VAPID y compatibilidad en iOS/Android PWA.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$criteria['urgent']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Lectura de 30 páginas de hábitos de alto rendimiento',
                'notes' => null,
                'project_id' => null, // Global task
                'criteria' => [$criteria['growth']->id],
                'is_completed' => false,
            ],
            [
                'title' => 'Configuración inicial del repositorio y entorno Docker',
                'notes' => "Setup de Laravel 12 con PHP 8.2 y MariaDB.",
                'project_id' => $projects['secondbrain']->id,
                'criteria' => [$criteria['urgent']->id, $criteria['growth']->id],
                'is_completed' => true,
                'completed_at' => now()->subHours(4),
            ],
            [
                'title' => 'Comprar suplementos de creatina y electrolitos',
                'notes' => null,
                'project_id' => $projects['fitness']->id,
                'criteria' => [$criteria['health']->id],
                'is_completed' => true,
                'completed_at' => now()->subHours(2),
            ],
        ];

        foreach ($tasksData as $data) {
            $taskCriteria = $data['criteria'] ?? [];
            unset($data['criteria']);

            $task = $user->tasks()->create($data);

            if (!empty($taskCriteria)) {
                $task->criteria()->sync($taskCriteria);
            }
        }

        // 5. Seed sample Daily Statistics
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $user->dailyStatistics()->create([
                'date' => $date,
                'pomodoro_seconds' => rand(1500, 7200), // 25 to 120 mins
                'tasks_completed' => rand(1, 5),
                'points_earned' => rand(20, 80),
            ]);
        }
    }
}
