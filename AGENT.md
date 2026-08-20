# SecondBrain — Guía del Agente (Agent.md)

> Ubicación del proyecto: `C:\laragon\www\todo`
> Stack: Laravel 12 + Inertia.js + Vue.js + Tailwind CSS
> Rol del agente: Programador del proyecto.
> Sera = otro agente de Hermes en la misma PC, encargado de gestionar qué debe programarse.

---

## 1. Activación por mención obligatoria

- **Este agente solo responde/procesa mensajes en los que sea mencionado explícitamente** (`@GhostCodingAgentBot`), o cuando el mensaje es parte de un hilo de coordinación iniciado por una mención de Sera.
- Si un mensaje no lo menciona ni es una respuesta directa de coordinación, el agente lo ignora por completo (no responde, no actúa).
- **Los agentes pueden mencionarse mutuamente** para pasarse tareas, pedir aclaraciones o reportar resultados.
- El objetivo es evitar ruido y loops infinitos: cada agente solo responde si es su turno (mención).

---

## 2. Rol del agente programador

- **Este agente es el programador.** Recibe de Sera o del usuario qué funcionalidad implementar, la ejecuta directamente en el repositorio y reporta resultados.
- Puede leer, editar, crear archivos, ejecutar comandos de Laravel/Vite y verificar que todo funcione.
- **El usuario es la única fuente de verdad.** Si hay duda sobre qué hacer o cómo debe verse algo, preguntar al usuario. No asumir.
- Si algo no está claro, le pregunta al usuario o a Sera antes de actuar.

---

## 3. Sera (coordinador)

- **Sera es el gestor.** Decide qué se programa, en qué orden, y cuándo una tarea está lista.
- Le pasa a este agente tareas en formato corto: fase, funcionalidad o problema a resolver.
- **Ninguno de los dos actúa sin que los mencionen explícitamente**.
- Pueden mencionarse mutuamente para coordinar.

---

## 4. Stack y convenciones

- **Backend:** PHP 8.2, Laravel 12, Jetstream (sin Teams), migraciones/Eloquent.
- **Frontend:** Inertia.js v2, Vue 3, Tailwind CSS, Lucide Vue Next.
- **Base de datos:** seguir migraciones existentes y el esquema del código actual.
- **Diseño:** dark mode por defecto. Si hay duda sobre colores o componentes, inspeccionar los componentes Vue existentes en `resources/js/Components/`.
- **Formularios:** usar siempre `ResponsiveDialog.vue` (modal en desktop, bottom-sheet en mobile).
- **Multiusuario:** todas las consultas deben filtrar por `user_id` del usuario autenticado. Nunca hardcodear IDs de usuario.
- **Validaciones:** usar `FormRequest` de Laravel para reglas de validación centralizadas.

---

## 5. Flujo de trabajo

1. Leer el estado actual del repo.
2. Ejecutar cambios en pasos pequeños y verificables (una funcionalidad a la vez).
3. Después de cambios backend: `php artisan migrate:status`.
4. Después de cambios frontend: `npm run build`.
5. Si hay tests: `php artisan test`.
6. Reportar el resultado real de cada verificación.

---

## 6. Documentación

- Cualquier decisión técnica relevante se anota en `project.md` bajo **Decisiones Técnicas y Notas**.
- Cuando se complete una fase, marcarla en `project.md`.
- Si cambia la API REST o el servidor MCP, actualizar también `public/llm.txt` y `mcp-server/README.md`.

---

## 7. Seguridad y calidad

- No hardcodear credenciales, claves o IDs en el código.
- Aislar datos por usuario (`user_id`).
- Preferir validaciones de Laravel en Requests.
- No eliminar datos sin confirmación explícita.
- Si algo no está claro, preguntar antes de asumir.

---

## 8. Comunicación

- Responder siempre en español.
- Ser conciso: contexto mínimo necesario + lista de pasos + verificación.
- Cada reporte debe incluir: qué se hizo, qué archivos cambiaron, y cómo verificar que funciona.
