# SecondBrain — Estado del Proyecto

## Fases Completadas
- [x] **Fase 3 — Criterios de Scoring**: CRUD completo implementado con validaciones y ResponsiveDialog para soportar modal en desktop y bottom-sheet en mobile. Paleta curada de colores utilizada. (Completado: 2026-07-21)

## Fases Pendientes
- [ ] Fase 1 — Setup base (Desactivar registro público). *(Omitido temporalmente para avanzar con Phase 3)*
- [ ] Fase 2 — Migraciones restantes (`tasks`, `debts`, etc).
- [x] Fase 4 — Módulo TODO (Añadido soporte para Scoring de Proyectos que sobrescribe la prioridad)
- [ ] Fase 6 — Polish

## Decisiones Técnicas y Notas
- Se creó `ResponsiveDialog.vue` para unificar la UI de formularios en escritorio y móviles sin duplicar código.
- Los puntos de los criterios pueden ser negativos para admitir "penalidades" (ej. procrastinación = -10). Rango admitido: -100 a 100.
- La selección de color se hace mediante swatches predefinidos que combinan con la paleta de la aplicación en vez de un input hex libre para asegurar la estética.
- Se agregó el paquete `lucide-vue-next` para iconografía estándar de la app.
- Se rediseñó completamente el `AppLayout.vue` y el UI de Autenticación (`Login.vue`, `AuthenticationCard`, etc.) para soportar el diseño responsivo Dark Mode dictado en `style.md` (Sidebar Desktop, Top/Bottom Navigation en Mobile).
- Se implementó un sistema de Estadísticas Diarias (`daily_statistics`) usando `vue-chartjs` y `chart.js`. Registra tiempo de Pomodoro (Focus) y Tareas Completadas sumando/restando puntos dinámicamente.
- **Transición a Multiusuario:** Se agregaron relaciones `user_id` a `tasks` y `scoring_criteria`. Los datos existentes se asignaron al usuario ID 1. Se implementó el listener `SeedDefaultScoringCriteria` para generar criterios base a los nuevos usuarios. Se actualizó Jetstream para permitir registro público y se aislaron todas las consultas en los controladores.
- **Pomodoro Timer Audio:** Se implementó la reproducción de un archivo de audio físico (`/alarm.wav`) a través de un elemento `Audio` de HTML5 al finalizar el temporizador, garantizando su funcionamiento mediante la precarga (`load()`) en la primera interacción del usuario.
- **Marcador de Tareas Complejas:** Se añadió la propiedad `is_complex_marker` a los criterios de puntuación. Si un Pomodoro finaliza en una tarea con este marcador, el sistema detiene el flujo y lanza un modal permitiendo al usuario continuar con la misma tarea o "saltarla" temporalmente. El salto usa el caché `skipped_tasks` ya existente para excluir la tarea y cargar el próximo `topTask` disponible dinámicamente, evadiendo rabbit holes.
- **Notificaciones Web Push (Background):** Se integró `laravel-notification-channels/webpush`. Los Pomodoros ahora programan un "Delayed Job" en el backend (`SendPomodoroPushNotification`) al iniciar/reanudar. Esto permite enviar una notificación Push real al dispositivo del usuario cuando el temporizador termina, incluso si el celular está bloqueado o el navegador está en segundo plano.
- **API REST & Autenticación por Token:** Se creó una API REST para gestionar Tareas y Criterios. La autenticación se maneja usando un `api_token` único en la tabla `users` mediante el guard de `token` de Laravel. El usuario puede regenerar su token desde la sección de Perfil de Jetstream, advirtiendo previamente que los tokens antiguos pierden acceso de inmediato.
- Se agregó el endpoint `/api/tasks/top` para obtener las 3 tareas con mayor puntaje, diseñado para integraciones o LLMs.
- Se implementó búsqueda avanzada en `/api/tasks` permitiendo filtrar por `criteria_ids`, `min_score`, `max_score` además de proyectos y estado de completado.
- Se reescribió por completo `public/llm.txt` para que funcione como la "Guía Definitiva" (The Ultimate Guide) para agentes de IA y bots de planificación. Ahora documenta detalladamente la filosofía (The Why), los mecanismos anti-burnout (is_complex_marker), el funcionamiento de las entidades principales (Tareas, Criterios, Proyectos, Estadísticas Diarias) y delega los endpoints específicos a `/docs/api-docs.json` (Swagger). También se especificó que el campo `notes` está destinado a los Technical Specs.
- **Persistencia Global de Proyecto Activo (`working-project`):** Se implementó el composable `useWorkingProject.js` para persistir en `LocalStorage` el ID del proyecto de trabajo seleccionado (`working-project`) y sincronizarlo de forma reactiva y en tiempo real (incluso entre pestañas del navegador). El Dashboard, Pomodoro y la Lista de Tareas (`/tasks`) sincronizan automáticamente su contexto con este proyecto, preseleccionándolo en la creación de tareas y filtrando las tareas pendientes y topTask en consecuencia. La navegación directa desde el listado de proyectos (`/projects`) respeta la visualización puntual sin alterar el `working-project` activo.
- **Database Seeder Integral para Pruebas:** Se enriqueció `DatabaseSeeder.php` para sembrar datos representativos del usuario de prueba (`test@example.com` / `password`), incluyendo un set completo de Criterios (con puntos positivos, negativos y marcador complejo), Proyectos con criterios asignados, Tareas pendientes/completadas con criterios asociados y notas técnicas, así como datos de Estadísticas Diarias para visualización de métricas.
- **Modal de Finalización de Pomodoro & Break con Control de Alarma:** Al terminar una sesión de Focus o un Descanso (corto/largo), la alarma de audio se ejecuta en bucle continuo y se despliega automáticamente un modal (`ResponsiveDialog`) con el mensaje y diseño alusivo ("¡Pomodoro Terminado!" o "¡Descanso Terminado!"). El usuario puede detener la alarma con un solo clic, iniciar de inmediato la siguiente fase o tomar decisiones sobre tareas complejas.
- **Herencia y Suma de Puntuación de Proyectos en Tareas:** Las tareas ahora heredan automáticamente los puntos de los criterios asignados a su Proyecto como su puntuación base predeterminada, sumando dinámicamente los criterios particulares que se le agreguen a la tarea. Se unificó el cálculo (`total_score`) en el modelo `Task`, en las consultas backend con ordenamiento global combinado (`(project_score + criteria_sum_points) DESC`), en la acumulación de estadísticas diarias al completar tareas, y en la visualización en el Dashboard, Pomodoro y Lista de Tareas.
- **Separación de Criterios Globales y Criterios por Proyecto:**
  - Se estructuró un modelo híbrido donde los criterios con `project_id = null` son **Globales** (gestionados en `/scoring-criteria` y disponibles para cualquier tarea) y los criterios con `project_id = X` son **Específicos del Proyecto** (gestionados en la ruta dedicada `/projects/{project_id}/scoring-criteria`).
  - Los Proyectos cuentan con un campo `base_score` configurable directamente en `/projects`.
  - Los modales de creación/edición de tareas en el Dashboard y Lista de Tareas computan reactivamente los criterios disponibles según el proyecto seleccionado (Globales + Proyecto) y limpian automáticamente los criterios huérfanos si el usuario cambia de proyecto.




