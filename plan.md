# SecondBrain — Plan del Proyecto

## Descripción General

Aplicación web personal de productividad construida con **Laravel 12 + Inertia.js + Vue.js**.
Sin registro público. Un solo usuario. Sin IA por ahora.

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

#### `scoring_criteria` — Criterios configurables
| Campo        | Tipo      | Descripción                         |
| ------------ | --------- | ----------------------------------- |
| `id`         | bigint PK | —                                   |
| `name`       | string    | Ej: "Genera dinero", "Trabajo"      |
| `points`     | integer   | Puntos que aporta                   |
| `color`      | string    | Color visual (hex)                  |
| `created_at` | timestamp | —                                   |
| `updated_at` | timestamp | —                                   |

#### `tasks` — Tareas
| Campo          | Tipo      | Descripción                          |
| -------------- | --------- | ------------------------------------ |
| `id`           | bigint PK | —                                    |
| `title`        | string    | Nombre de la tarea                   |
| `total_score`  | integer   | Puntaje calculado (suma de criterios)|
| `is_completed` | boolean   | Si está completada                   |
| `completed_at` | timestamp | Cuándo se completó                   |
| `created_at`   | timestamp | —                                    |
| `updated_at`   | timestamp | —                                    |

#### `task_scoring_criteria` — Tabla pivote (muchos a muchos)
| Campo                  | Tipo      | Descripción |
| ---------------------- | --------- | ----------- |
| `task_id`              | bigint FK | —           |
| `scoring_criterion_id` | bigint FK | —           |

### Páginas / Vistas
- `/tasks` — Lista de tareas ordenadas por puntaje (mayor primero). La tarea top aparece destacada como **"¿Qué hago ahora?"**.
- `/tasks/create` — Crear nueva tarea seleccionando criterios.
- `/scoring-criteria` — Panel de gestión de criterios (CRUD completo).

### Flujo de uso
1. El usuario configura sus criterios en `/scoring-criteria`.
2. Crea tareas asignando los criterios que aplican.
3. El sistema calcula automáticamente el puntaje total.
4. Las tareas se muestran ordenadas por puntaje descendente.
5. Al completar una tarea, se marca como hecha y sale de la lista activa.

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

- **Autenticación**: ¿Mantener el login de Jetstream o algo más simple?
