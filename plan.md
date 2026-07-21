# SecondBrain — Plan del Proyecto

## Descripción General

Aplicación web personal de productividad construida con **Laravel 12 + Inertia.js + Vue.js**.
Sin registro público. Un solo usuario. Sin IA por ahora.

Dos módulos principales:
1. **TODO con sistema de scoring configurable**
2. **Gestión de Deudas Pendientes con historial de abonos y recurrencia**

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

## Módulo 2 — Deudas Pendientes con Recurrencia

### Concepto
Registrar deudas sin rastrear flujo de caja. El foco es: **¿a quién le debo?** y **¿cuánto me falta pagar?**

Soporte para deudas recurrentes: si un mes no se pagó, la deuda sigue activa
y aparece acumulada en el siguiente período.

### Base de Datos

#### `debts` — Deudas
| Campo              | Tipo          | Descripción                                            |
| ------------------ | ------------- | ------------------------------------------------------ |
| `id`               | bigint PK     | —                                                      |
| `creditor`         | string        | A quién se le debe (ej: "Banco Popular", "Claro")      |
| `type`             | enum          | `credit_card`, `loan`, `service`, `other`              |
| `total_amount`     | decimal(12,2) | Monto total original de la deuda                       |
| `is_recurring`     | boolean       | Si se genera cargo mensual automático                  |
| `recurring_amount` | decimal(12,2) | Monto del cargo mensual (si es recurrente)             |
| `due_day`          | integer       | Día del mes en que vence (ej: 15)                      |
| `status`           | enum          | `active`, `paid`, `overdue`                            |
| `notes`            | text nullable | Notas opcionales                                       |
| `created_at`       | timestamp     | —                                                      |
| `updated_at`       | timestamp     | —                                                      |

#### `debt_payments` — Historial de Abonos
| Campo          | Tipo          | Descripción                               |
| -------------- | ------------- | ----------------------------------------- |
| `id`           | bigint PK     | —                                         |
| `debt_id`      | bigint FK     | —                                         |
| `amount`       | decimal(12,2) | Monto del abono                           |
| `payment_date` | date          | Fecha del pago                            |
| `period_month` | integer       | Mes al que corresponde el pago (1-12)     |
| `period_year`  | integer       | Año al que corresponde el pago            |
| `notes`        | string nullable | Referencia o nota del pago              |
| `created_at`   | timestamp     | —                                         |

### Lógica de Recurrencia
- Deudas con `is_recurring = true` generan una obligación mensual.
- Si el mes ya pasó y no hay pago registrado para ese `period_month/period_year`,
  la deuda aparece en estado **"En mora"** para ese período.
- El sistema muestra el mes actual + cualquier mes anterior impago.
- Al registrar un abono, el usuario indica a qué período corresponde.

### Páginas / Vistas
- `/debts` — Lista de todas las deudas con estado visual. Resumen: total adeudado, total pagado, balance pendiente.
- `/debts/create` — Crear nueva deuda.
- `/debts/{id}` — Detalle de la deuda + historial de pagos + registrar abono.

---

## Estructura de Archivos

```
app/
├── Models/
│   ├── User.php
│   ├── Task.php
│   ├── ScoringCriterion.php
│   ├── Debt.php
│   └── DebtPayment.php
├── Http/
│   └── Controllers/
│       ├── TaskController.php
│       ├── ScoringCriterionController.php
│       ├── DebtController.php
│       └── DebtPaymentController.php
└── Actions/
    └── Tasks/
        └── CalculateTaskScore.php

resources/js/
├── Pages/
│   ├── Tasks/
│   │   ├── Index.vue
│   │   └── Create.vue
│   ├── ScoringCriteria/
│   │   └── Index.vue
│   └── Debts/
│       ├── Index.vue
│       ├── Create.vue
│       └── Show.vue
└── Components/
    ├── TaskCard.vue
    ├── DebtCard.vue
    └── PaymentHistoryItem.vue
```

---

## Orden de Implementación

- [ ] **Fase 1 — Setup base**: Desactivar registro público en Jetstream (solo login).
- [ ] **Fase 2 — Migraciones**: Crear tablas `scoring_criteria`, `tasks`, `task_scoring_criteria`, `debts`, `debt_payments`.
- [ ] **Fase 3 — Criterios de Scoring**: CRUD completo + UI en `/scoring-criteria`.
- [ ] **Fase 4 — Módulo TODO**: CRUD de tareas + cálculo de puntaje + ordenamiento + UI.
- [ ] **Fase 5 — Módulo Deudas**: CRUD de deudas + historial de abonos + lógica de recurrencia + UI.
- [ ] **Fase 6 — Polish**: Diseño coherente, navegación, responsivo.

---

## Preguntas Pendientes

- **Moneda**: ¿Solo DOP (Pesos Dominicanos) o multi-moneda?
- **Autenticación**: ¿Mantener el login de Jetstream o algo más simple?
- **Tarjetas de crédito**: El monto mensual puede variar — ¿se ingresa el abono manualmente cada mes sin asumir un monto fijo?
