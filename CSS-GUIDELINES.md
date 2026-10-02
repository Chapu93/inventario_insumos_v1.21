# Guía de Estilos CSS (CSS-GUIDELINES.md) — SITIA

Esta guía establece las convenciones arquitectónicas, metodológicas y operativas para el mantenimiento y evolución del CSS de **SITIA** (Sistema de Inventario de Telecomunicaciones, Insumos y Administración).

---

## 1. Arquitectura y Orden de Carga

Los estilos residen en `public/css/` divididos en módulos numerados con responsabilidad única. Se cargan de forma dinámica en orden alfabético estricto mediante [includes/css.php](file:///opt/lampp/htdocs/inventario_app/includes/css.php), que aplica cache-busting automático (`?v=filemtime`). **No utilizar `@import`**.

```
public/css/
├── 00-tokens.css        # Primitivos, semánticos, componentes y escala de z-index
├── 10-base.css          # Reset, tipografía global, transiciones de tema y motion reduce
├── 20-layout.css        # Wrapper, sidebar, navbar, footer y responsive layout
├── 30-buttons.css       # Botones institucionales, focos accesibles y botones suaves
├── 31-forms.css         # Controles de formulario, switches, filtros y sección títulos
├── 32-cards.css         # Tarjetas base y variantes temáticas
├── 33-tables.css        # Estilos generales de tablas y densidad de filas
├── 34-modals.css        # Modales de confirmación, backdrops y animación zoom-in
├── 35-badges.css        # Badges de estado de insumos y asignaciones
├── 36-kpi.css           # Tarjetas KPI de estadísticas y panel de control
├── 37-stepper.css       # Asistentes de pasos y stepper
├── 38-pdf-viewer.css    # Visor integrado de documentos PDF
├── 50-select2.css       # Integración visual con librería Select2 y tema Bootstrap 5
├── 51-datatables.css    # Integración visual con librería DataTables
├── 90-dark.css          # Tokens de modo oscuro y excepciones mínimas sin nesting
└── 99-utilities.css     # Clases utilitarias y overlays (tooltips, tabs)
```

> **Regla de Oro:** Cada archivo tiene una única responsabilidad. Está estrictamente prohibido crear archivos genéricos como "varios.css" o "misc.css".

---

## 2. Reglas de Tokens y Colores

1. **Cero colores hexadecimales en componentes**: Ningún archivo fuera de `00-tokens.css` y el bloque raíz de `90-dark.css` debe contener valores hexadecimales (`#hex`) directos.
2. **Jerarquía de capas**:
   - **Primitivos**: Paleta base de la marca (`--sitia-primitive-green-*`, `--sitia-primitive-sage-*`, etc.).
   - **Semánticos**: Roles de la interfaz (`--sitia-primary`, `--sitia-surface-1`, `--sitia-text`, `--sitia-border`).
   - **Componentes**: Variables atadas a elementos específicos (`--sitia-btn-*`, `--sitia-tooltip-bg`).
3. **Uso en componentes**: Consumir siempre variables semánticas:
   ```css
   /* Correcto */
   .mi-tarjeta {
       background-color: var(--sitia-surface-3);
       border: 1px solid var(--sitia-border);
       color: var(--sitia-text);
   }

   /* Prohibido */
   .mi-tarjeta {
       background-color: #ffffff;
       border: 1px solid #dee2e6;
   }
   ```

---

## 3. Especificidad y Prohibiciones

1. **Prohibido `!important` en nuevas reglas**: 
   - `!important` solo se admite en utilidades de una sola propiedad o cuando sea técnicamente indispensable para vencer reglas inline inyectadas por librerías de terceros (DataTables/Select2).
   - Cualquier uso debe documentarse con un comentario explicativo.
2. **Prohibidos selectores por ID para estilos**:
   - Los IDs (`#mi-elemento`) están reservados para ganchos de JavaScript/PHP.
   - Todo estilo debe declararse mediante clases CSS (`.mi-elemento`).
   - Solo se conservan selectores por ID existentes por razones de compatibilidad estricta ya documentada (`#content`, `#modalVisorPDF`).
3. **Variables nativas de Bootstrap 5**:
   - Siempre que se personalice un componente de Bootstrap, usar sus variables nativas (`--bs-card-*`, `--bs-modal-*`, `--bs-btn-*`, `--bs-table-*`) antes de sobrescribir selectores hijos profundos.

---

## 4. Modo Oscuro

1. **Basado en Variables**: El modo oscuro se activa mediante el atributo `[data-theme="dark"]` en el elemento `<html>`.
2. **Sin CSS Nesting Nativo**: No anidar bloques dentro de `[data-theme="dark"] { ... }`.
   - La redefinición de tokens se hace en la raíz:
     ```css
     [data-theme="dark"] {
         --sitia-primary: var(--sitia-primitive-green-300);
         --sitia-surface-3: var(--sitia-primitive-sage-700);
         --sitia-text: var(--sitia-primitive-sage-50);
     }
     ```
   - Si un componente requiere una excepción explícita que una variable no cubre, usar selector de nivel superior con prefijo:
     ```css
     [data-theme="dark"] .mi-componente {
         border-color: var(--sitia-border);
     }
     ```
3. **Contraste Accesible**: Todo nuevo par de texto y fondo en modo oscuro debe cumplir la pauta WCAG AA (ratio mínimo 4.5:1 para texto normal, 3.0:1 para texto grande o elementos de interfaz).

---

## 5. Breakpoints y Responsive

Alineados estrictamente con la cuadrícula de Bootstrap 5 para evitar colisiones:

| Breakpoint | Consulta Media | Uso en SITIA |
|---|---|---|
| **Móviles pequeños** | `@media (max-width: 768px)` | Dispositivos móviles y tablets en formato vertical |
| **Tablets / Lg-down** | `@media (max-width: 991.98px)` | Ocultamiento de sidebar y navegación colapsada |
| **Escritorio / Lg-up** | `@media (min-width: 992px)` | Barra lateral visible y estructura fija de contenido |
| **Laptops / HD Density**| `@media (max-width: 1400px), (max-height: 820px)` | Densidad vertical (padding compactos en tablas, cards y navbar) |

- **Regla de `max-height`**: Solo se permite en consultas de *densidad vertical* para optimizar pantallas laptop HD (1366x768). Prohibido usar `max-height` para alterar anchos de layout horizontal.
- **Ubicación de Media Queries**: Las reglas responsive de un componente se colocan al final de su propio archivo modular.

---

## 6. Escala Centralizada de Z-Index

Nunca escribir valores numéricos arbitrarios de `z-index` (como `9999` o `99999`). Utilizar siempre la escala semántica de `00-tokens.css`:

```css
:root {
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
}
```

---

## 7. Procedimiento para Nuevos Componentes

1. **Crear archivo**: Si el componente representa una entidad nueva, crear `public/css/XX-nombre.css` (donde `XX` representa su posición lógica en la cascada, entre 30 y 49).
2. **Definir tokens**: Si requiere colores o métricas nuevas, agregarlos primero a `00-tokens.css` (modo claro) y `90-dark.css` (modo oscuro).
3. **Estructurar estilos**:
   - Usar nombres de clase semánticos (`.card-mi-modulo`, `.badge-mi-estado`).
   - Consumir tokens `var(--sitia-*)`.
   - Ubicar sus media queries específicas al final de ese archivo.
4. **Verificación visual**: Cargar la página y verificar en tema claro y oscuro que no haya saltos de diseño ni alertas en consola.
