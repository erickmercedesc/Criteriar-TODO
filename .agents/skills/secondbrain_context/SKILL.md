---
name: secondbrain_context
description: >
  Workflow de contexto para el proyecto SecondBrain. Activa cuando el usuario
  pide hacer cualquier cambio de código, migración, componente, o configuración
  en el proyecto SecondBrain (Laravel + Inertia + Vue PWA).
---

# SecondBrain — Workflow de Contexto

## Antes de cualquier cambio

Siempre leer estos tres archivos del proyecto en este orden antes de escribir código
o ejecutar comandos que modifiquen el proyecto:

1. `c:\laragon\www\secondbrain\plan.md` — Arquitectura, módulos, base de datos, orden de implementación.
2. `c:\laragon\www\secondbrain\style.md` — Paleta de colores, tipografía, componentes, layout desktop/mobile, PWA.
3. `c:\laragon\www\secondbrain\project.md` — Estado actual del proyecto (fases completadas, pendientes, decisiones tomadas).

**Si alguno de los archivos no existe**, continuar sin él y notificar al usuario.

## Después de completar un cambio

Actualizar siempre `project.md` para reflejar:
- Qué se implementó / qué fase se completó.
- Cualquier decisión técnica relevante tomada durante la implementación.
- Estado de las preguntas pendientes si alguna fue resuelta.

Actualizar `plan.md` si:
- Cambia la arquitectura de base de datos.
- Se agrega o elimina una vista/ruta.
- Se modifica el orden de implementación.

Actualizar `style.md` si:
- Se agregan nuevos componentes no documentados.
- Se ajustan colores, tokens o comportamientos de diseño.
