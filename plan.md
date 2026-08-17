# SecondBrain — Plan del Proyecto

## Descripción General

Aplicación web personal de productividad construida con **Laravel 12 + Inertia.js + Vue.js**.
Arquitectura multiusuario. Cada usuario tiene sus propias tareas y criterios. Sin IA por ahora.

Dos módulos principales:
1. **TODO con sistema de scoring configurable**

**Stack:** PHP 8.2, Laravel 12, Jetstream (sin Teams), Inertia.js v2, Vue 3, Tailwind CSS.

---

## Módulo 1 — TODO con Puntuación Dinámica

### Concepto
Cada tarea tiene criterios de evaluación que se le aplican para calcular su puntaje total.
Los criterios son completamente configurables: el usuario puede crear, editar o eliminar criterios
y asignarles un valor de puntos.

**La tarea con mayor puntaje = próxima tarea a hacer.**

### Ejemplo de funcionamiento

| Criterio       | Puntos |
| -------------- | ------ |
| Genera dinero  | 20     |
| Trabajo        | 10     |
| Aprendizaje    | 5      |

Una tarea marcada como "Genera dinero" + "Trabajo" = **30 puntos**.

### Base de Datos

#### `projects` — Proyectos de Trabajo
| Campo        | Tipo      | Descripción                                |
| ------------ | --------- | ------------------------------------------ |
| `id`         | bigint PK | —                                          |
| `user_id`    | bigint FK | Propietario del proyecto                   |
| `name`       | string    | Nombre del proyecto                        |
| `color`      | string    | Color visual (hex)                         |
| `base_score` | integer   | Puntuación base automática (default: 0)    |
| `created_at` | timestamp | —                                          |
| `updated_at` | timestamp | —                                          |

#### `scoring_criteria` — Criterios configurables (Globales y de Proyecto)
| Campo               | Tipo        | Descripción                                                |
| ------------------- | ----------- | ---------------------------------------------------------- |
| `id`                | bigint PK   | —                                                          |
| `user_id`           | bigint FK   | Propietario del criterio                                   |
| `project_id`        | bigint FK?  | Proyecto al que pertenece (NULL = Criterio Global)         |
| `name`              | string      | Ej: "Genera dinero", "Stripe Checkout"                     |
| `points`            | integer     | Puntos que aporta (-100 a 100)                             |
| `color`             | string      | Color visual (hex)                                         |
| `is_complex_marker` | boolean     | Si activa modal de decisión anti-burnout al fin de Pomodoro|
| `created_at`        | timestamp   | —                                                          |
| `updated_at`        | timestamp   | —                                                          |

#### `tasks` — Tareas
| Campo          | Tipo        | Descripción                                                       |
| -------------- | ----------- | ----------------------------------------------------------------- |
| `id`           | bigint PK   | —                                                                 |
| `user_id`      | bigint FK   | Propietario de la tarea                                           |
| `project_id`   | bigint FK?  | Proyecto asignado (hereda `project.base_score`)                   |
| `title`        | string      | Nombre de la tarea                                                |
| `notes`        | text?       | Technical specs / notas contextuales                              |
| `is_completed` | boolean     | Si está completada                                                |
| `completed_at` | timestamp   | Cuándo se completó                                                |
| `created_at`   | timestamp   | —                                                                 |
| `updated_at`   | timestamp   | —                                                                 |

#### `task_scoring_criteria` — Tabla pivote (muchos a muchos)
| Campo                  | Tipo      | Descripción |
| ---------------------- | --------- | ----------- |
| `task_id`              | bigint FK | —           |
| `scoring_criterion_id` | bigint FK | —           |

#### `daily_statistics` — Resumen de actividad por día
| Campo              | Tipo      | Descripción                           |
| ------------------ | --------- | ------------------------------------- |
| `id`               | bigint PK | —                                     |
| `user_id`          | bigint FK | —                                     |
| `date`             | date      | Fecha del registro                    |
| `pomodoro_seconds` | integer   | Segundos en fase focus                |
| `tasks_completed`  | integer   | Tareas marcadas como completadas      |
| `points_earned`    | integer   | Puntos recolectados (base + criterios)|
| `created_at`       | timestamp | —                                     |
| `updated_at`       | timestamp | —                                     |

### Páginas / Vistas
- `/dashboard` — Command Center con Top Task prioritaria, estadísticas del día y selector reactivo de proyecto de trabajo.
- `/pomodoro` — Temporizador Pomodoro sincronizado con Top Task y soporte Web Push en background.
- `/tasks` — Lista de tareas ordenadas por puntaje total (`base_score + criterios`), con filtros y modal de creación responsivo.
- `/projects` — Lista y gestión de proyectos con su `base_score`.
- `/projects/{project_id}/scoring-criteria` — Panel dedicado para administrar criterios específicos de un proyecto.
- `/scoring-criteria` — Panel de gestión de Criterios Globales.
- `/statistics` — Panel de estadísticas diarias e historial (Gráficas).

### Flujo de uso
1. El usuario configura Criterios Globales en `/scoring-criteria`.
2. Crea Proyectos en `/projects` asignando una puntuación base (`base_score`).
3. En `/projects/{id}/scoring-criteria`, define criterios específicos para ese proyecto.
4. Al crear una tarea, selecciona el Proyecto y el formulario ofrece automáticamente los Criterios Globales + los específicos del Proyecto.
5. El sistema calcula `total_score = project.base_score + suma(criterios)`.
6. Las tareas se ordenan jerárquicamente: primero por puntuación del proyecto (`project.base_score DESC`), y luego por la suma de criterios de la tarea (`criteria_sum_points DESC`).
7. Al completar una tarea, el total de puntos se suma a las estadísticas diarias.

---

## Estructura de Archivos

```
app/
├── Models/
│   ├── User.php
│   ├── Task.php
│   └── ScoringCriterion.php
├── Http/
│   └── Controllers/
│       ├── TaskController.php
│       └── ScoringCriterionController.php
└── Actions/
    └── Tasks/
        └── CalculateTaskScore.php

resources/js/
├── Pages/
│   ├── Tasks/
│   │   ├── Index.vue
│   │   └── Create.vue
│   └── ScoringCriteria/
│       └── Index.vue
└── Components/
    └── TaskCard.vue
```

---

## Orden de Implementación

- [ ] **Fase 1 — Setup base**: Desactivar registro público en Jetstream (solo login).
- [ ] **Fase 2 — Migraciones**: Crear tablas `scoring_criteria`, `tasks`, `task_scoring_criteria`.
- [ ] **Fase 3 — Criterios de Scoring**: CRUD completo + UI en `/scoring-criteria`.
- [ ] **Fase 4 — Módulo TODO**: CRUD de tareas + cálculo de puntaje + ordenamiento + UI.
- [ ] **Fase 5 — Polish**: Diseño coherente, navegación, responsivo.

---

## Preguntas Pendientes

- **Autenticación**: Se utiliza Jetstream con registro público para la arquitectura multiusuario.
