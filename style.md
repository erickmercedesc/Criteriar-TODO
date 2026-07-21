# SecondBrain — Guía de Diseño (style.md)

> Documento de referencia de diseño. Sin código. Define colores, tipografía, espaciado,
> componentes y comportamiento responsivo para la PWA.

---

## 1. Filosofía de Diseño

- **Minimalista pero expresivo**: interfaces limpias, sin ruido visual, pero con jerarquía clara.
- **Orientado a la acción**: lo importante siempre visible de inmediato (tarea top, deuda urgente).
- **Context-aware**: la misma información se presenta de forma distinta según el dispositivo.
- **Dark mode por defecto**: app de uso personal/productividad → dark mode reduce fatiga visual.

---

## 2. Paleta de Colores

### 2.1 Base (Dark Mode)

| Token                  | Valor       | Uso                                              |
| ---------------------- | ----------- | ------------------------------------------------ |
| `--color-bg`           | `#0F1117`   | Fondo principal de la app                        |
| `--color-surface`      | `#1A1D27`   | Cards, tablas, paneles                           |
| `--color-surface-2`    | `#22263A`   | Hover de filas, surface secundario               |
| `--color-border`       | `#2E3347`   | Bordes sutiles                                   |
| `--color-text-primary` | `#F0F2F8`   | Texto principal                                  |
| `--color-text-muted`   | `#7B82A0`   | Texto secundario, labels, placeholders           |

### 2.2 Colores de Acento

| Token                  | Valor       | Uso                                              |
| ---------------------- | ----------- | ------------------------------------------------ |
| `--color-primary`      | `#6C63FF`   | Acciones primarias, botones CTA, links activos   |
| `--color-primary-soft` | `#6C63FF26` | Fondos suaves de elementos activos               |
| `--color-success`      | `#22C55E`   | Estado pagado, tarea completada                  |
| `--color-warning`      | `#F59E0B`   | Deuda próxima a vencer, estado "por vencer"      |
| `--color-danger`       | `#EF4444`   | Deuda en mora, acciones destructivas             |
| `--color-info`         | `#38BDF8`   | Información neutral, badges de tipo              |

### 2.3 Colores de Estado para Deudas

| Estado       | Color               | Significado                          |
| ------------ | ------------------- | ------------------------------------ |
| `active`     | `--color-info`      | Deuda activa y al día                |
| `overdue`    | `--color-danger`    | En mora — pagos vencidos sin cubrir  |
| `paid`       | `--color-success`   | Deuda saldada completamente          |
| "por vencer" | `--color-warning`   | Vence en los próximos 5 días         |

### 2.4 Score Badge (TODO)

Los badges de puntaje usan un color basado en el valor relativo del score:

| Rango de puntaje | Color                |
| ---------------- | -------------------- |
| Mayor puntaje    | `--color-primary`    |
| Puntaje medio    | `--color-warning`    |
| Puntaje bajo     | `--color-text-muted` |

---

## 3. Tipografía

- **Fuente principal**: `Inter` (Google Fonts)
- **Fuente mono** (para números clave, montos): `JetBrains Mono`

### Escala tipográfica

| Rol              | Tamaño | Peso | Uso                                          |
| ---------------- | ------ | ---- | -------------------------------------------- |
| Display / Hero   | `28px` | 700  | Tarea top destacada, título de módulo        |
| Heading 1        | `22px` | 600  | Títulos de página                            |
| Heading 2        | `18px` | 600  | Secciones dentro de una página              |
| Body             | `15px` | 400  | Texto de contenido general                   |
| Body Small       | `13px` | 400  | Texto secundario, descripciones              |
| Label / Badge    | `11px` | 600  | Badges, tags, labels de estado               |
| Mono / Monto     | `16px` | 500  | Montos en DOP, scores numéricos              |

---

## 4. Espaciado y Bordes

| Token         | Valor  | Uso                                          |
| ------------- | ------ | -------------------------------------------- |
| `--space-xs`  | `4px`  | Gaps internos mínimos                        |
| `--space-sm`  | `8px`  | Padding de badges, separaciones internas     |
| `--space-md`  | `16px` | Padding estándar de cards y celdas           |
| `--space-lg`  | `24px` | Separación entre secciones                   |
| `--space-xl`  | `32px` | Padding de contenedores principales          |
| `--radius-sm` | `6px`  | Badges, inputs                               |
| `--radius-md` | `12px` | Cards, modales                               |
| `--radius-lg` | `20px` | Bottom sheets (esquinas superiores)          |

---

## 5. Iconografía

- **Librería**: [Lucide Icons](https://lucide.dev) (SVG, ligero, tree-shakeable)
- **Tamaño estándar**: `18px` en desktop · `20px` en mobile
- **Color**: heredado del texto en contexto (`currentColor`)

---

## 6. Layout — Desktop (≥ 768px)

### 6.1 Estructura general

```
┌──────────────────────────────────────────────┐
│  Sidebar fija (240px)  │  Contenido principal │
│                        │                      │
│  Logo + nav links      │  Toolbar + Data      │
│                        │                      │
└──────────────────────────────────────────────┘
```

- Sidebar fija a la izquierda, colapsable.
- Contenido principal ocupa el resto del ancho.
- Max-width del área de contenido: `1200px`, centrado.

### 6.2 Visualización de datos

| Módulo              | Componente principal | Notas                                                    |
| ------------------- | -------------------- | -------------------------------------------------------- |
| TODO — tareas       | **Tabla**            | Columnas: Puntaje · Tarea · Criterios · Acciones         |
| TODO — la top task  | **Hero card** encima | Destacada visualmente con borde de acento                |
| Criterios scoring   | **Lista**            | Nombre · Puntos · Color · Acciones inline                |
| Deudas              | **Tabla**            | Acreedor · Tipo · Total · Pendiente · Estado · Acciones  |
| Historial de pagos  | **Lista anidada**    | Dentro del detalle de cada deuda                         |

### 6.3 Formularios en Desktop → Modal

- Los formularios (crear/editar) se abren en un **modal centrado** sobre la pantalla.
- Overlay de fondo: `rgba(0,0,0,0.6)` + `backdrop-filter: blur(4px)` sutil.
- Ancho del modal: `520px` máximo.
- Estructura del modal: encabezado (título + botón cerrar ✕) · cuerpo con formulario · footer con botones (Cancelar / Guardar).
- Cierre: clic en overlay, botón ✕ o tecla `Escape`.

---

## 7. Layout — Mobile (< 768px)

### 7.1 Estructura general

```
┌──────────────────┐
│  Top bar (60px)  │
│  Logo + menú ≡   │
├──────────────────┤
│                  │
│  Contenido       │
│  (scroll)        │
│                  │
├──────────────────┤
│  Bottom nav bar  │
│  (56px)          │
└──────────────────┘
```

- Sin sidebar. Navegación mediante **bottom navigation bar** con íconos + label.
- Top bar con logo y nombre de la vista actual.
- Contenido en scroll vertical con padding inferior para no quedar detrás del bottom nav.

### 7.2 Visualización de datos

| Módulo              | Componente principal | Notas                                                           |
| ------------------- | -------------------- | --------------------------------------------------------------- |
| TODO — top task     | **Hero Card grande** | Primera card, fondo con acento, label "⚡ Haz esto ahora"       |
| TODO — tareas       | **Lista de Cards**   | Una card por tarea con puntaje + criterios como chips           |
| Criterios scoring   | **Lista de Cards**   | Cards compactas con color, nombre y puntos                      |
| Deudas              | **Lista de Cards**   | Acreedor · monto pendiente · estado · día de vencimiento        |
| Historial de pagos  | **Lista simple**     | Dentro de la bottom sheet de detalle de cada deuda              |

### 7.3 Formularios en Mobile → Bottom Sheet

- Los formularios se despliegan como un **bottom modal** (estilo iOS / Android sheet).
- Cubre aproximadamente el **85% de la pantalla** en altura.
- Esquinas superiores redondeadas (`border-radius: 20px 20px 0 0`).
- Handle/pill en la parte superior: `40×4px`, redondeado, color `--color-border`, centrado.
- Se cierra arrastrando hacia abajo o tocando el overlay detrás.
- El formulario tiene **scroll interno** si el contenido excede la altura disponible.
- Fondo: `--color-surface` con overlay oscuro detrás.

---

## 8. Componentes — Definición Visual

### 8.1 Card (Mobile)

- Fondo: `--color-surface`
- Borde: `1px solid --color-border`
- Border-radius: `--radius-md` (12px)
- Padding interno: `--space-md` (16px)
- Sombra: `0 2px 12px rgba(0,0,0,0.3)`
- Separación entre cards: `--space-sm` (8px)
- Estructura interna sugerida:

```
[Badge estado / ícono]  [Título principal]       [Score / Monto]
                        [Subtítulo / info]
                        [Chips de criterios]
```

### 8.2 Tabla (Desktop)

- Header: fondo `--color-surface-2`, texto uppercase `11px`, `--color-text-muted`, `letter-spacing: 0.08em`
- Filas: fondo alternado `--color-surface` / `--color-bg`
- Hover de fila: fondo `--color-surface-2`
- Bordes: solo horizontales (`border-bottom: 1px solid --color-border`), sin bordes verticales
- Acciones de fila: íconos que se revelan al hacer hover sobre la fila (no visibles por defecto)

### 8.3 Modal (Desktop)

- Overlay: `rgba(0,0,0,0.6)` + `backdrop-filter: blur(4px)`
- Fondo: `--color-surface`
- Borde: `1px solid --color-border`
- Border-radius: `--radius-md` (12px)
- Ancho: `520px` fijo
- Animación de entrada: `fade + scale` (de 95% → 100%), duración `200ms`

### 8.4 Bottom Sheet (Mobile)

- Fondo: `--color-surface`
- Border-radius: `20px 20px 0 0`
- Handle/pill: `40×4px`, `--color-border`, centrado en la parte superior
- Animación de entrada: `translateY(100%) → translateY(0)`, duración `280ms`
- Easing: `cubic-bezier(0.32, 0.72, 0, 1)` (sensación natural táctil)

### 8.5 Chip / Badge de Criterio

- Fondo: color del criterio al `20%` de opacidad
- Texto: color del criterio al `100%`
- Border-radius: `--radius-sm` (6px)
- Padding: `2px 8px`
- Font: `11px`, peso `600`
- Sin borde explícito

### 8.6 Score Badge (Puntaje)

- Muestra el número con sufijo `pts`
- Font: `JetBrains Mono`, `14px`, peso `600`
- Border-radius: `--radius-sm`
- Color de fondo según rango (ver sección 2.4)

### 8.7 Botones

| Variante     | Fondo               | Texto             | Uso                          |
| ------------ | ------------------- | ----------------- | ---------------------------- |
| Primary      | `--color-primary`   | Blanco            | Acción principal (Guardar)   |
| Ghost        | Transparente        | `--color-primary` | Cancelar, acción secundaria  |
| Danger       | `--color-danger`    | Blanco            | Eliminar (confirmado)        |
| Danger Ghost | Transparente        | `--color-danger`  | Confirmar eliminación suave  |

- Border-radius: `--radius-sm` (6px)
- Padding: `8px 16px` desktop · `10px 20px` mobile
- Transición: `background 150ms ease`, `opacity 150ms ease`
- Hover: `opacity: 0.85`
- Disabled: `opacity: 0.4`, cursor `not-allowed`

---

## 9. Navegación

### Desktop — Sidebar (240px)

| Sección        | Ícono Lucide  | Ruta                |
| -------------- | ------------- | ------------------- |
| Tareas (TODO)  | `CheckSquare` | `/tasks`            |
| Criterios      | `Sliders`     | `/scoring-criteria` |
| Deudas         | `CreditCard`  | `/debts`            |

- Link **activo**: fondo `--color-primary-soft`, texto `--color-primary`, borde izquierdo `3px solid --color-primary`
- Link **inactivo**: texto `--color-text-muted`, hover con fondo `--color-surface-2`

### Mobile — Bottom Navigation (56px)

| Tab       | Ícono Lucide  | Label     |
| --------- | ------------- | --------- |
| Tareas    | `CheckSquare` | Tareas    |
| Criterios | `Sliders`     | Criterios |
| Deudas    | `CreditCard`  | Deudas    |

- Fondo: `--color-surface` con `border-top: 1px solid --color-border`
- Tab activo: ícono + label en `--color-primary`
- Tab inactivo: ícono + label en `--color-text-muted`
- Respetar `env(safe-area-inset-bottom)` para dispositivos con notch/home bar

---

## 10. Página Especial — "¿Qué hago ahora?" (Top Task)

La tarea con mayor puntaje recibe tratamiento visual especial.

- **Desktop**: Hero card de ancho completo encima de la tabla. Fondo con gradiente sutil usando `--color-primary` al `10%` de opacidad. Label flotante `"⚡ Haz esto ahora"` en la esquina superior izquierda.
- **Mobile**: Primera card de la lista, más grande que las demás. Mismo gradiente de acento.
- Contiene: título de la tarea, puntaje total, chips de criterios, botón `"Marcar como hecha"`.

---

## 11. PWA — Requisitos de Diseño

| Aspecto             | Definición                                                       |
| ------------------- | ---------------------------------------------------------------- |
| **Ícono de app**    | Fondo `#6C63FF`, letras `SB` en blanco, `Inter 700`, esquinas redondeadas |
| **Theme color**     | `#0F1117` (barra de sistema en Android/iOS)                      |
| **Background color**| `#0F1117` (splash screen)                                        |
| **Display mode**    | `standalone` (sin barra del navegador)                           |
| **Orientación**     | `portrait` preferida (no bloqueada en fuerza)                    |
| **Safe areas**      | `env(safe-area-inset-*)` en bottom nav y bottom sheets           |
| **Íconos**          | `192×192px` y `512×512px` en el manifest                        |
| **Offline**         | Service Worker con cache del shell — lectura básica sin internet |

### Splash / Pantalla de carga

- Fondo: `#0F1117`
- Logo centrado (ícono SB + nombre "SecondBrain")
- Texto: `"Cargando..."` en `--color-text-muted`
- Sin animaciones complejas — solo fade-in al montar la app

---

## 12. Micro-interacciones

| Acción                       | Feedback visual                                              |
| ---------------------------- | ------------------------------------------------------------ |
| Marcar tarea como completada | Card/fila hace fade-out suave + tachado del título (strikethrough) |
| Registrar abono              | Monto pendiente se actualiza con animación de contador       |
| Hover sobre fila de tabla    | Revelar íconos de acción + cambio de fondo suave             |
| Abrir modal                  | Fade + scale de entrada (`200ms`)                            |
| Cerrar modal                 | Fade + scale de salida inversa                               |
| Abrir bottom sheet           | Slide-up (`280ms`, easing natural)                           |
| Cerrar bottom sheet          | Slide-down (misma duración)                                  |
| Botón guardar en carga       | Spinner inline + texto "Guardando..." + botón deshabilitado  |
| Chip de criterio hover       | Scale `1.05` suave                                           |

---

## 13. Tono Visual por Módulo

| Módulo        | Acento visual                             | Razón                              |
| ------------- | ----------------------------------------- | ---------------------------------- |
| TODO / Tareas | `--color-primary` (violeta)               | Productividad, enfoque, acción     |
| Criterios     | Color propio de cada criterio             | Personalización visual del usuario |
| Deudas        | `--color-warning` / `--color-danger`      | Urgencia, dinero, atención         |
