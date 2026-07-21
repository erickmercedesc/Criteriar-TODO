# SecondBrain — Estado del Proyecto

## Fases Completadas
- [x] **Fase 3 — Criterios de Scoring**: CRUD completo implementado con validaciones y ResponsiveDialog para soportar modal en desktop y bottom-sheet en mobile. Paleta curada de colores utilizada. (Completado: 2026-07-21)

## Fases Pendientes
- [ ] Fase 1 — Setup base (Desactivar registro público). *(Omitido temporalmente para avanzar con Phase 3)*
- [ ] Fase 2 — Migraciones restantes (`tasks`, `debts`, etc).
- [x] Fase 4 — Módulo TODO
- [ ] Fase 6 — Polish

## Decisiones Técnicas y Notas
- Se creó `ResponsiveDialog.vue` para unificar la UI de formularios en escritorio y móviles sin duplicar código.
- Los puntos de los criterios pueden ser negativos para admitir "penalidades" (ej. procrastinación = -10). Rango admitido: -100 a 100.
- La selección de color se hace mediante swatches predefinidos que combinan con la paleta de la aplicación en vez de un input hex libre para asegurar la estética.
- Se agregó el paquete `lucide-vue-next` para iconografía estándar de la app.
- Se rediseñó completamente el `AppLayout.vue` y el UI de Autenticación (`Login.vue`, `AuthenticationCard`, etc.) para soportar el diseño responsivo Dark Mode dictado en `style.md` (Sidebar Desktop, Top/Bottom Navigation en Mobile).
