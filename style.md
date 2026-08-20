# SecondBrain — Style.me (Fuente de la Verdad del Diseño)

> Este documento reemplaza a `style.md`. Es la referencia única para cualquier cambio de UI/UX en SecondBrain.
> Actualizado: Agosto 2026.

---

## 1. Filosofía de Diseño

- **Dark mode nativo.** La app se usa como herramienta personal diaria; el fondo oscuro reduce fatiga.
- **Enfoque sobre ornamentación.** La acción importante (top task, temporizador) siempre es visible de inmediato.
- **Jerarquía por color.** El morado/accento indica acción, el naranja indica énfasis/urgencia, el verde éxito, el rojo peligro/destrucción.
- **Mobile-first responsivo.** Desktop usa sidebar + tablas; mobile usa bottom nav + cards + bottom sheets.
- **Consistencia sobre creatividad.** No introducir nuevos colores, espaciados o bordes sin actualizar este documento.

---

## 2. Paleta de Colores

Usar siempre los tokens CSS. Nunca colores Tailwind genéricos como `bg-gray-800`, `text-red-500`, etc.

| Token | Valor | Uso |
|-------|-------|-----|
| `--color-bg` | `#0F1117` | Fondo principal de la app |
| `--color-surface` | `#1A1D27` | Cards, sidebar, tablas, modales, bottom sheets |
| `--color-surface-2` | `#22263A` | Hover de filas, fondo secundario, inputs enfocados |
| `--color-border` | `#2E3347` | Bordes sutiles, divisores |
| `--color-text-primary` | `#F0F2F8` | Texto principal, títulos |
| `--color-text-muted` | `#7B82A0` | Labels, placeholders, descripciones, iconos inactivos |
| `--color-primary` | `#6C63FF` | Botones CTA, links activos, acentos principales |
| `--color-primary-soft` | `#6C63FF26` | Fondos suaves de elementos activos |
| `--color-success` | `#22C55E` | Tareas completadas, estados positivos |
| `--color-warning` | `#F59E0B` | Top task destacada, estados de atención |
| `--color-danger` | `#EF4444` | Acciones destructivas, errores |
| `--color-info` | `#38BDF8` | Información neutral, puntos base de proyecto |

### Reglas de uso de color

- Fondos: `bg-[#0F1117]`, `bg-[#1A1D27]`, `bg-[#22263A]`.
- Texto: `text-[#F0F2F8]` para títulos, `text-[#7B82A0]` para labels.
- Bordes: `border-[#2E3347]`.
- Acentos: primario `#6C63FF`, énfasis `#F59E0B`, éxito `#22C55E`, peligro `#EF4444`, info `#38BDF8`.
- Badges de criterios: `backgroundColor: `${color}15`, `color`, `borderColor: `${color}30`.
- Badges de proyecto: `backgroundColor: `${color}20`, `color`, `borderColor: `${color}40`.

---

## 3. Tipografía

- **Principal:** `Inter` (sans-serif).
- **Mono:** `JetBrains Mono` (puntajes, montos, contadores).

| Rol | Tamaño | Peso | Uso |
|-----|--------|------|-----|
| Hero / Top Task | `32px` / `42px` md | 700 | Tarea principal del Dashboard |
| H1 página | `22px` | 600 | Títulos de vista |
| H2 sección | `18px` | 600 | Subtítulos |
| Body | `15px` | 400 | Contenido general |
| Body small | `13px` | 400 | Labels, descripciones |
| Badge / label | `11px` / `12px` | 600 | Chips, estados, puntos |
| Mono / Score | `15px` / `16px` | 700 | Puntajes, contadores |

---

## 4. Espaciado y Bordes

| Token | Valor | Uso |
|-------|-------|-----|
| `--space-xs` | `4px` | Gaps internos mínimos |
| `--space-sm` | `8px` | Padding de badges, separaciones |
| `--space-md` | `16px` | Padding estándar cards/celdas |
| `--space-lg` | `24px` | Separación entre secciones |
| `--space-xl` | `32px` | Padding de contenedores principales |
| `--radius-sm` | `6px` | Inputs, badges, botones |
| `--radius-md` | `12px` | Cards, modales, botones grandes |
| `--radius-lg` | `16px` | Hero cards, estados vacíos |
| `--radius-xl` | `20px` | Bottom sheets (esquinas superiores) |
| `--radius-full` | `9999px` | Pills, badges circulares |

---

## 5. Componentes de UI

### 5.1 Layout

- **Desktop (≥ 768px):**
  - Sidebar fija izquierda de `240px` (`bg-[#1A1D27]`, `border-r border-[#2E3347]`).
  - Contenido principal con `max-w-[800px]` o `max-w-7xl` según vista, centrado.
  - Sin topbar en escritorio.

- **Mobile (< 768px):**
  - Topbar de `60px` cuando `show-mobile-header`.
  - Bottom nav de `56px` con safe-area.
  - Contenido con `pb-24` para no quedar detrás del bottom nav.

### 5.2 Navegación

| Vista | Ícono Lucide | Ruta |
|-------|--------------|------|
| Dashboard | `LayoutDashboard` | `/dashboard` |
| Estadísticas | `BarChart3` | `/statistics` |
| Pomodoro | `Timer` | `/pomodoro` |
| Tareas | `CheckSquare` | `/tasks` |
| Proyectos | `Folder` | `/projects` |
| Criterios | `Sliders` | `/scoring-criteria` |
| Configuración | `Settings` | `/settings` |

- Activo: `bg-[#6C63FF26] text-[#6C63FF] border-l-4 border-[#6C63FF]`.
- Inactivo: `text-[#7B82A0] hover:bg-[#22263A]`.

### 5.3 Botones

| Tipo | Clases esperadas |
|------|-----------------|
| Primary | `bg-[#6C63FF] text-white hover:opacity-85 rounded-[6px]` |
| Primary Grande | `bg-[#6C63FF] text-white rounded-[16px] shadow-[0_4px_12px_rgba(108,99,255,0.2)] hover:shadow-[0_0_20px_rgba(108,99,255,0.4)]` |
| Ghost | `bg-transparent border border-[#2E3347] text-[#7B82A0] hover:text-[#F0F2F8] hover:border-[#7B82A0]` |
| Danger | `bg-[#EF4444] text-white hover:opacity-85` |
| Warning | `bg-[#F59E0B] text-black font-bold` |

- Todos los botones: `inline-flex items-center justify-center gap-2`.
- Disabled: `opacity-25` o `opacity-40` con `cursor-not-allowed`.

### 5.4 Inputs

- Fondo: `bg-[#0F1117]`.
- Borde: `border border-[#2E3347]`.
- Texto: `text-[#F0F2F8]`.
- Focus: `focus:ring-[#6C63FF] focus:border-[#6C63FF]`.
- Radio: `rounded-[6px]` o `rounded-full` según tipo.

### 5.5 Cards

- Fondo: `bg-[#1A1D27]`.
- Borde: `border border-[#2E3347]`.
- Radio: `rounded-[12px]` o `rounded-[24px]` para hero.
- Sombra sutil: `shadow-sm` o `shadow-[0_2px_12px_rgba(0,0,0,0.3)]`.

### 5.6 Modal / Bottom Sheet

Usar siempre `ResponsiveDialog.vue`.

- **Desktop:** modal centrado, `sm:max-w-*`, fondo `bg-[#1A1D27]`, borde `#2E3347`, overlay `bg-black/60 backdrop-blur-sm`.
- **Mobile:** bottom sheet con `rounded-t-[20px]`, handle `#2E3347`, `max-h-[85vh]`.

### 5.7 Badges de Criterios y Proyectos

- Criterio: fondo `color+15`, texto `color`, borde `color+30`, `rounded-[8px]`.
- Proyecto: fondo `color+20`, texto `color`, borde `color+40`, `rounded-full`.
- Puntos: `font-mono font-bold` dentro del badge.

---

## 6. Páginas Específicas

### 6.1 Dashboard

- Hero card para top task: `border-[#F59E0B]`, gradiente superior `from-[#F59E0B] to-[#EF4444]`.
- Botón principal: morado grande con sombra.
- Footer de puntaje: Base Proyecto (info), Criterios (success), Total (texto principal).
- Stats row: 2 cards con contadores grandes.
- Empty states: icono grande en círculo de color, título, descripción, botón secundario.

### 6.2 Tareas / Proyectos / Criterios

- Desktop: tabla con header `bg-[#22263A]`, texto uppercase `11px`, filas alternadas.
- Mobile: lista de cards compactas.
- Acciones de fila: íconos inline o en menú según densidad.

### 6.3 Pomodoro

- Círculo/timer grande centrado.
- Controles: play, pause, skip, reset con iconografía clara.
- Estados: focus, short break, long break con colores distintivos.
- Alarma / modal de fin de sesión usando `ResponsiveDialog`.

---

## 7. Iconografía

- Librería: `lucide-vue-next`.
- Tamaño desktop: `18px` (`w-4.5 h-4.5` o `w-5 h-5`).
- Tamaño mobile: `20px` (`w-5 h-5` o `w-6 h-6`).
- Color: heredado del contexto (`currentColor`) o `text-[#...]` explícito.

---

## 8. PWA

- Theme color: `#0F1117`.
- Background color: `#0F1117`.
- Display: `standalone`.
- Safe areas: respetar `env(safe-area-inset-bottom)` en bottom nav y bottom sheets.

---

## 9. Reglas de Oro

1. **No agregar colores nuevos.** Usar solo los definidos arriba. Si se necesita otro color, se discute y se actualiza este archivo.
2. **No usar colores Tailwind genéricos.** Nada de `bg-gray-700`, `text-red-500`, `border-slate-600`.
3. **Siempre usar `ResponsiveDialog.vue`** para cualquier formulario modal/bottom-sheet.
4. **Mobile y desktop comparten componentes.** No duplicar vistas; se adaptan con clases responsivas.
5. **Los puntajes usan `font-mono`.**
6. **Cualquier cambio visual se refleja en `Style.me` antes de finalizar la tarea.**
