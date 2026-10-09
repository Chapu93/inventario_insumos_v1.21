# Guía de Estilos CSS (CSS-GUIDELINES.md) — SITIA

Esta guía establece las convenciones arquitectónicas, metodológicas y operativas para el mantenimiento y evolución del CSS de **SITIA** (Sistema de Inventario de Telecomunicaciones, Insumos y Administración).

---

## 1. Arquitectura y Orden de Carga

Los estilos residen en `public/css/` divididos en módulos numerados con responsabilidad única. Se cargan de forma dinámica en orden alfabético estricto mediante [includes/css.php](file:///opt/lampp/htdocs/inventario_app/includes/css.php), que aplica cache-busting automático (`?v=filemtime`). **Prohibido `@import`** y archivos genéricos (`misc.css`, `varios.css`).

```
public/css/
├── 00-tokens.css        # Primitivos, semánticos, on-*, componentes y escala z-index
├── 10-base.css          # Reset, tipografía global, transiciones de tema y motion reduce
├── 20-layout.css        # Wrapper, sidebar, navbar, footer y layout responsive
├── 30-buttons.css       # Botones institucionales, focos accesibles y variantes soft
├── 31-forms.css         # Controles de formulario, switches, filtros y títulos de sección
├── 32-cards.css         # Tarjetas base y contenedores visuales
├── 33-tables.css        # Tablas institucionales y densidad de filas
├── 34-modals.css        # Modales de confirmación, backdrops y animación zoom-in
├── 35-badges.css        # Badges de estado de insumos, tareas y asignaciones
├── 36-kpi.css           # Tarjetas KPI de estadísticas y métricas del dashboard
├── 37-stepper.css       # Asistentes por pasos (stepper)
├── 38-pdf-viewer.css    # Visor integrado de documentos y remitos PDF
├── 39-alerts.css        # Alertas institucionales y variables nativas de Bootstrap
├── 50-select2.css       # Integración con librería Select2 y tema Bootstrap 5
├── 51-datatables.css    # Integración con librería DataTables
├── 90-dark.css          # Redefinición de tokens de modo oscuro y excepciones mínimas
└── 99-utilities.css     # Clases utilitarias institucionales y overlays
```

---

## 2. Tokens en Capas

El sistema de tokens de `00-tokens.css` se organiza en 4 niveles jerárquicos estrictos:

1. **Primitivos (`--sitia-primitive-*`)**: Colores y valores brutos de marca (p. ej. `--sitia-primitive-green-500`, `--sitia-primitive-sage-700`). Prohibido consumirlos directamente en vistas o componentes.
2. **Semánticos (`--sitia-*`)**: Roles funcionales de interfaz (`--sitia-primary`, `--sitia-surface-1/2/3`, `--sitia-text`, `--sitia-border`).
3. **Tokens de Contenido (`--sitia-on-*`)**: Contrastes accesibles garantizados para texto/icono sobre cada superficie o variante (`--sitia-on-primary`, `--sitia-on-secondary`, `--sitia-on-success`, `--sitia-on-warning`, `--sitia-on-danger`, `--sitia-on-info`).
4. **De Componente (`--sitia-[componente]-*`)**: Tokens específicos atados a propiedades de un elemento (`--sitia-btn-*-bg`, `--sitia-card-*`, `--sitia-tooltip-bg`). Consumen siempre tokens semánticos u `on-*`.

---

## 3. Reglas de Calidad y Prohibiciones Estrictas

1. **Cero colores `#hex` fuera de tokens**: Ningún archivo fuera de `00-tokens.css` y el bloque `[data-theme="dark"]` raíz de `90-dark.css` puede contener valores `#hex` directos.
2. **Prohibido `!important` en nuevas reglas**: 
   - Todo uso de `!important` debe estar debidamente justificado con un comentario explicativo.
   - En componentes Bootstrap, alimentar las variables nativas (`--bs-btn-*`, `--bs-card-*`) en lugar de forzar con `!important`.
3. **Prohibidos selectores por ID (`#id`) para estilos**:
   - Los IDs están reservados exclusivamente para hooks de JavaScript y PHP.
   - Todo estilo de componente debe aplicarse mediante clases semánticas (`.mi-componente`).

---

## 4. Modo Oscuro: Solo Redefinir Tokens

1. **Sin reglas duplicadas por componente**: Los componentes en `30-buttons.css`, `32-cards.css`, etc., se estilizan consumiendo variables (`var(--sitia-*)`). No deben existir bloques `.mi-componente` repetidos dentro de `90-dark.css`.
2. **Redefinición en la raíz**: `90-dark.css` solo debe redefinir el valor de los tokens en el bloque raíz `[data-theme="dark"] { ... }`.
3. **Sin CSS Nesting Nativo**: Prohibido anidar selectores dentro de `[data-theme="dark"] { ... }`.
4. **Contraste WCAG AA**: Todo par de texto y fondo en modo oscuro debe cumplir la pauta WCAG AA (ratio mínimo 4.5:1 para texto normal, 3.0:1 para elementos de interfaz y texto grande).

---

## 5. Excepciones Documentadas

Existen dos categorías exclusivas de excepciones admitidas en la arquitectura:

1. **Excepciones de prevalencia sobre utilidades de Bootstrap** (en `90-dark.css`):
   - 4 declaraciones de color con `!important` (`.btn-secondary`, `.btn-success`, `.btn-light`, `.btn-warning:active/.active`) requeridas para reproducir el comportamiento de la línea base frente a utilidades como `.text-white` y `.text-dark` en dark mode, manteniendo el modo claro intacto.
2. **Selectores de estilo inline `[style*="#..."]`**:
   - Utilizados puntualmente en `33-tables.css` y `20-layout.css` para neutralizar colores inline inyectados dinámicamente por librerías externas o vistas legadas cuando opera el modo oscuro.

---

## 6. Procedimiento para Agregar Componente, Color o Variante de Botón

1. **Tokens**: Definir las variables necesarias en `00-tokens.css` (modo claro) y redefinirlas en `90-dark.css` (modo oscuro).
2. **Implementación modular**:
   - Para un nuevo botón, definir la variante en `30-buttons.css` mapeando las variables de Bootstrap (`--bs-btn-bg`, `--bs-btn-color`, `--bs-btn-border-color`, `--bs-btn-hover-*`, `--bs-btn-active-*`).
   - Para un nuevo módulo, crear `public/css/XX-nombre.css` y colocar sus media queries al final del archivo.
3. **Galería**: Añadir el elemento con sus 5 estados (`normal`, `hover`, `active`, `disabled`, `focus`) en [tools/visual-tests/component-gallery.php](file:///opt/lampp/htdocs/inventario_app/tools/visual-tests/component-gallery.php).
4. **Matriz CDP**: Registrar el selector en el arreglo `BUTTON_VARIANTS` de [tools/visual-tests/capture.js](file:///opt/lampp/htdocs/inventario_app/tools/visual-tests/capture.js).

---

## 7. Breakpoints y Escala de Z-Index

### Breakpoints Responsive (Bootstrap 5)
| Breakpoint | Media Query | Propósito en SITIA |
|---|---|---|
| **Mobile / Tablet vertical** | `@media (max-width: 768px)` | Dispositivos móviles y tablets verticales (768px inclusive, footer compacto y filtros) |
| **Tablet / Lg-down** | `@media (max-width: 991.98px)` | Ocultamiento de sidebar y barra de navegación colapsada |
| **Desktop / Lg-up** | `@media (min-width: 992px)` | Sidebar expandida y layout fijo de escritorio |
| **Laptop HD Density (xxl-down)** | `@media (max-width: 1399.98px), (max-height: 820px)` | Densidad vertical compacta para laptops (1366x768) o monitores con escala de Windows (altura < 820px) |

*Justificación técnica de `max-height: 820px`: En monitores 1366x768 o 1920x1080 escalados al 125%/150% de Windows, el viewport útil vertical es <800px. La densidad compacta previene scroll vertical excesivo en cabeceras de tablas y KPIs. Prohibido usar `max-height` para alterar anchos de layout horizontal.*

### Escala Semántica de Z-Index (`00-tokens.css`)
Nunca usar números mágicos arbitrarios (`999`, `9999`). Utilizar la escala:
```css
--sitia-z-dropdown: 1000;
--sitia-z-sticky: 1020;
--sitia-z-fixed: 1030;
--sitia-z-modal-backdrop: 1050;
--sitia-z-modal: 1055;
--sitia-z-modal-confirm: 1060;
--sitia-z-modal-pdf: 1070;
--sitia-z-popover: 1080;
--sitia-z-tooltip: 1090;
--sitia-z-toast: 1100;
```

---

## 8. Flujo Obligatorio de Verificación

Antes de confirmar cualquier cambio de CSS, ejecutar:
1. **Métricas de calidad**:
   ```bash
   bash tools/css-metrics.sh
   ```
   Verificar que no haya `!important` en comentarios, que el total se mantenga bajo control y que **Tokens SITIA sin referencia `var()` sea 0**.
2. **Suite Visual A/B y Matriz CDP**:
   ```bash
   node tools/visual-tests/capture.js --mode ab
   node tools/visual-tests/compare.js --mode ab
   ```
   El resultado debe ser estrictamente **0 diferencias** (150 capturas en 5 viewports y 7 matrices completas idénticas al píxel).

