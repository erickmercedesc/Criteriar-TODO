# SecondBrain — Guía del Agente de IA

## 1. Contexto del Proyecto

SecondBrain es una aplicación web personal de productividad (TODO + Puntuación + Pomodoro) construida con **Laravel 12 + Inertia.js + Vue.js**.

**Stack:** PHP 8.2, Laravel 12, Jetstream (sin Teams), Inertia.js v2, Vue 3, Tailwind CSS.

**Directorio raíz:** `C:\laragon\www\todo`

**Arquitectura:** Multiusuario. Cada usuario tiene sus propias tareas, criterios y proyectos.

---

## 2. Antes de cualquier cambio

Siempre leer estos tres archivos del proyecto en este orden:

1. **`C:\laragon\www\todo\plan.md`** — Arquitectura, módulos, base de datos, orden de implementación.
2. **`C:\laragon\www\todo\style.md`** — Paleta de colores, tipografía, componentes, layout desktop/mobile, PWA.
3. **`C:\laragon\www\todo\project.md`** — Estado actual del proyecto (fases completadas, pendientes, decisiones tomadas).

Si alguno de los archivos no existe, continuar sin él y notificar al usuario.

---

## 3. Principios de Arquitectura Limpia (DRY y SOLID)

Al escribir, refactorizar o diseñar código, adherirte rigurosamente a:

1. **DRY (Don't Repeat Yourself)**:
   - Evita la duplicación de código.
   - Si un mismo fragmento de UI (Vue), lógica de controladores o consultas se repite, **extráelo a una pieza reutilizable** (componente compartido, composable de Vue, servicio de Laravel, Trait, etc.).

2. **SOLID**:
   - **Single Responsibility (SRP)**: Cada componente Vue, clase PHP o método debe tener un solo propósito.
   - **Open/Closed (OCP)**: Código abierto a extensión pero cerrado a modificación. Favorece inyección de dependencias y slots/composables.
   - **Liskov Substitution (LSP)**: Implementaciones derivadas deben poder ser reemplazadas sin romper el sistema.
   - **Interface Segregation (ISP)**: No obligues a componentes/clases a depender de métodos que no usan.
   - **Dependency Inversion (DIP)**: Depende de abstracciones, no de implementaciones concretas.

**Mandato Activo:** Si notas que el código existente viola estos principios, estás autorizado a refactorizar primero antes de agregar nuevas funcionalidades.

---

## 4. Filosofía del Sistema (SecondBrain)

SecondBrain no es una todo list pasiva; es un **motor de decisiones activo** diseñado para eliminar la parálisis por análisis y el burnout mediante matemáticas y gamificación:

1. **Prioridad Jerárquica (La Regla Matemática):**
   - Las tareas se priorizan primero por la puntuación base del Proyecto (`base_score DESC`), y luego por la suma de puntos de sus criterios (`criteria_sum_points DESC`).
   - Nunca adivines ni sobrescribas la prioridad manualmente; deja que el motor de scoring la determine.

2. **Especificaciones Técnicas en `notes`:**
   - El campo `notes` de una tarea está reservado para **Especificaciones Técnicas**, criterios de aceptación, dependencias, links de contexto y notas arquitectónicas.

3. **Anti-Burnout y Complejidad (`is_complex_marker`):**
   - Los criterios marcados con `is_complex_marker: true` señalan trabajo mentalmente demandante. En sesiones Pomodoro, finalizar tareas complejas activa sugerencias de descanso y opciones de "saltar" para evitar rabbit holes.

4. **Impacto de Gamificación Permanente:**
   - Completar una tarea acredita permanentemente todos los puntos acumulados a las `daily_statistics` e incrementa `tasks_completed`.

---

## 5. Después de completar un cambio

Actualizar **siempre** la documentación correspondiente:

- **`project.md`**:
  - Qué se implementó / qué fase se completó.
  - Decisiones técnicas relevantes tomadas.
  - Estado de preguntas pendientes si alguna fue resuelta.

- **`plan.md`** si:
  - Cambia la arquitectura de base de datos.
  - Se agrega o elimina una vista/ruta.
  - Se modifica el orden de implementación.

- **`style.md`** si:
  - Se agregan nuevos componentes no documentados.
  - Se ajustan colores, tokens o comportamientos de diseño.

- **`public/llm.txt`** si:
  - Se modifican los endpoints de la API (rutas, parámetros, body request/response).
  - Se añaden nuevas reglas de negocio que deba conocer un bot o sistema de IA.

### Control de Versiones (Git)

Después de finalizar cualquier tarea y haber actualizado la documentación:

1. Ejecutar `git add .`
2. Ejecutar `git commit -m "tipo(alcance): mensaje"`
3. Usar SIEMPRE la **Angular Commit Convention**: `feat:`, `fix:`, `docs:`, `refactor:`, `style:`, `chore:`.
4. **NUNCA ejecutar `git push`**. Respetar la regla global de no hacer push.

---

## 6. Herramientas MCP Disponibles

SecondBrain expone un servidor MCP (`mcp-server/`) y endpoint `/api/mcp/sse` con estas 12 herramientas:

| Tool | Propósito |
| :--- | :--- |
| `secondbrain_get_top_tasks` | Obtener la(s) tarea(s) con mayor prioridad ahora. |
| `secondbrain_list_tasks` | Buscar/filtrar tareas por estado, score, proyecto o criterios. |
| `secondbrain_get_task` | Ver specs técnicos, criterios y detalles de una tarea. |
| `secondbrain_create_task` | Crear tarea con specs técnicos, proyecto y criterios. |
| `secondbrain_update_task` | Modificar specs, título, proyecto o criterios. |
| `secondbrain_complete_task` | Marcar tarea como completada (otorga puntos). |
| `secondbrain_delete_task` | Eliminar una tarea permanentemente. |
| `secondbrain_list_projects` | Listar proyectos con `base_score` y criterios. |
| `secondbrain_create_project` | Crear proyecto con color y puntos base. |
| `secondbrain_list_scoring_criteria` | Listar criterios globales o específicos de proyecto. |
| `secondbrain_create_scoring_criterion` | Crear criterio con valor de puntos y marcador complejo. |
| `secondbrain_get_productivity_stats` | Ver estadísticas diarias de focus, puntos y tendencias. |

---

## 7. Reglas de Negocio y Guardrails

- **No adivinar IDs:** Siempre recuperar IDs de proyectos y criterios con `secondbrain_list_projects` y `secondbrain_list_scoring_criteria` antes de asignarlos.
- **No sobrescribir prioridad manualmente:** Las prioridades se calculan dinámicamente en la base de datos. Para aumentarla, asigna criterios de mayor valor o aumenta el `base_score` del proyecto.
- **Preservar integridad de `notes`:** Al actualizar una tarea, apéndice o refina los specs existentes sin borrar contexto valioso a menos que se solicite explícitamente.
- **Criterios negativos:** Los criterios con puntos negativos (ej. `Procrastinación (-10)`) se usan para despriorizar tareas de distracción sin eliminarlas.
- **Nunca crear tarea con 0 criterios:** Caerá al fondo con 0 puntos de prioridad.

---

## 8. Procedimientos Operativos Estándar (Runbooks)

### Runbook A: "¿Qué debo hacer ahora?" (Flujo de Ejecución)
1. Llamar `secondbrain_get_top_tasks` (opcional `project_id`).
2. La primera tarea devuelta es la acción de mayor prioridad.
3. Leer el campo `notes` para extraer los **Technical Specs**.
4. Ejecutar el trabajo requerido por la tarea.
5. Verificar y finalizar con `secondbrain_complete_task({ task_id: ID, is_completed: true })`.

### Runbook B: "Crear una tarea / Planificar un feature" (Flujo de Creación)
1. **Descubrir Contexto:**
   - `secondbrain_list_projects` para encontrar el proyecto apropiado.
   - `secondbrain_list_scoring_criteria` para ver criterios disponibles.
2. **Formular Technical Specs:** Escribir especificaciones claras y accionables en `notes`.
3. **Asignar Criterios:**
   - Nunca crear tarea con 0 criterios.
   - Seleccionar 1-3 criterios relevantes (ej. `Trabajo Urgente (+25)`, `Genera Dinero (+20)`).
   - Si la tarea es mentalmente exigente, incluir un criterio con `is_complex_marker: true`.
4. Llamar `secondbrain_create_task` con `title`, `project_id`, `criteria_ids`, `notes`.

### Runbook C: "Organizar / Estructurar un Nuevo Proyecto"
1. `secondbrain_create_project` con color identificador y `base_score` sensato (30-50 para proyectos de alto impacto, 15-20 para mantenimiento).
2. `secondbrain_create_scoring_criterion` pasando `project_id` para crear tags específicos.
3. Descomponer el proyecto en tareas discretas y crearlas con `secondbrain_create_task`.

### Runbook D: "Revisión de Productividad y Daily Standup"
1. `secondbrain_get_productivity_stats` para el focus de hoy, tareas completadas y puntos.
2. `secondbrain_list_tasks({ completed: true })` para resumir entregables completados.
3. `secondbrain_get_top_tasks` para anunciar próximos objetivos del día.
