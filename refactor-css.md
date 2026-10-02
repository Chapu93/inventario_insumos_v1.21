# REFACTORIZACIÓN DEL CSS: PHP + Bootstrap 5

## ROL
Sos un ingeniero frontend senior especializado en CSS escalable, Bootstrap 5
y proyectos PHP. Vas a refactorizar el CSS de este proyecto.

## STACK
PHP, Bootstrap 5, CSS plano y MySQL. No hay paso de build (ni Sass, ni
PostCSS, ni Node). No agregues ninguna herramienta ni dependencia sin
preguntarme antes.

## ACCESO AL PROYECTO
Tenés el proyecto abierto en el IDE. No esperes a que te pase archivos:
buscá vos mismo la información que necesites.
- Explorá la estructura completa antes de empezar (vistas, includes,
  parciales, plantillas, assets, JS).
- Identificá dónde se enlaza hoy style.css (layout, header, includes) y qué
  archivos PHP/HTML/JS dependen de él, incluyendo librerías de terceros
  (DataTables, Select2, Bootstrap JS, etc.).
- Excluí vendor/, node_modules/ y .git/ de las búsquedas, salvo para
  consultar cómo una librería genera sus clases.
- Si no podés ejecutar comandos o un navegador (para las capturas), decímelo
  al principio y proponé la alternativa manual.
- Si algo no te da certeza, marcalo como "no verificado" y preguntame. Nunca
  asumas que un selector es seguro de borrar.

## CONTEXTO: PROBLEMAS DETECTADOS EN style.css (~2250 líneas)
- ~520 `!important` (unos 270 solo en el modo oscuro).
- Modo oscuro de ~600 líneas que redefine componentes completos (botones,
  card-headers, modales) en vez de apoyarse en variables.
- CSS nesting nativo dentro de `[data-theme="dark"]`: no funciona en
  navegadores sin soporte y se ignora en silencio.
- Colores hex repetidos que ya tienen token (#4a9d95, #d9943f, #218838,
  #dc3545, #9b78db...) y colores fijos dentro de SVG data-URI.
- Selectores duplicados (ej: `#content > .container-fluid`) y selectores por
  ID que elevan la especificidad.
- `--bs-secondary` (#fff) no coincide con `--bs-secondary-rgb` (155,183,162).
- Media queries que mezclan `max-width` y `max-height`, breakpoints que se
  solapan (992/993) y `font-size` en px que no escala (ej: raíz a 14.45px
  por prueba y error).
- `z-index` sueltos (1000, 1050, 1060, 1080, 1090, 1100), varios con
  `!important`, sin escala central.
- Posible contraste justo en el modo oscuro (fondo medio con colores pastel).

## OBJETIVO
Reducir la deuda técnica y dejar el CSS escalable y mantenible, SIN cambiar
cómo se ve ni cómo funciona la aplicación.

## REGLAS INNEGOCIABLES
1. Cero cambios visuales: cada pantalla debe verse igual en modo claro y
   oscuro, en todos los breakpoints y estados (hover, focus, modales, etc.).
2. No renombres ni elimines clases, IDs ni atributos usados por PHP, HTML o
   JS. Antes de borrar o renombrar un selector, buscalo en TODO el proyecto:
   * clases en HTML/PHP,
   * clases agregadas o quitadas desde JS (classList, addClass, toggleClass,
     querySelector, jQuery, innerHTML, template strings),
   * clases armadas por concatenación (ej: "btn-" + tipo),
   * IDs y atributos de datos (data-theme, data-bs-*),
   * clases generadas por librerías.
   Si hay duda, NO lo borres: listalo para que yo decida.
3. No modifiques HTML, PHP ni JS salvo que sea imprescindible. Si lo es,
   pedime confirmación antes y explicá por qué. La única excepción prevista
   es el enlace al CSS (ver Fase 6).
4. Mantené el mecanismo actual del tema oscuro (`[data-theme="dark"]` y el
   JS que lo activa). Podés sumar compatibilidad con `data-bs-theme` solo si
   no rompe nada existente.
5. Trabajá en una rama de git nueva y hacé commits pequeños por paso.
6. No hagas cambios masivos de una vez: avanzá fase por fase y esperá mi
   confirmación entre fase y fase.
7. Si algo es ambiguo o riesgoso, preguntame antes de actuar.

## PROCESO

### Fase 0: Auditoría (sin modificar nada)
- Leé style.css completo y los archivos PHP/JS que lo usan.
- Entregame un informe con:
  * Conteo de `!important` por sección y cuáles son realmente necesarios
    (por ejemplo, para vencer estilos inline o de librerías).
  * Lista de colores hardcodeados y a qué token corresponde cada uno.
  * Selectores duplicados o muertos, con evidencia de búsqueda.
  * Qué overrides del modo oscuro se pueden eliminar porque ya los cubren
    los tokens.
  * Clases dinámicas o generadas por librerías que no se pueden verificar.
  * Riesgos y partes que no recomendás tocar.
- Confirmá o ajustá la estructura de archivos propuesta abajo según el
  tamaño real de cada sección (si algo queda muy chico, proponé juntarlo).
- Proponé el orden de los cambios.

### Fase 1: Red de seguridad visual
- Antes de tocar el CSS, armá una verificación de regresión visual: capturas
  (Playwright o similar, si ya está disponible o si me pedís permiso para
  usarlo) de las pantallas principales en modo claro y oscuro, en tres
  anchos (~375px, ~768px, ~1440px), incluyendo estados: hover, focus,
  modales abiertos, tablas con DataTables, Select2 abierto.
- Guardalas como línea base. Si no podés ejecutar el navegador, dame una
  checklist manual de pantallas y estados para revisar yo.

### Fase 2: Tokens
- Organizá los tokens en capas:
  * Primitivos (paleta cruda).
  * Semánticos (`--sitia-primary`, `--sitia-surface-*`, `--sitia-text`...).
  * De componente solo cuando haga falta.
- Corregí la inconsistencia `--bs-secondary` / `--bs-secondary-rgb` sin
  alterar el aspecto resultante.
- Creá una escala central de `z-index` con variables.
- Reemplazá los hex repetidos por variables. Para los SVG embebidos, usá
  `mask` con `currentColor` o un enfoque que dependa del token, verificando
  que el resultado visual sea idéntico.

### Fase 3: Menos `!important` y menos especificidad
- Personalizá los componentes Bootstrap con sus variables nativas
  (`--bs-btn-*`, `--bs-table-*`, `--bs-card-*`, `--bs-modal-*`...) en lugar
  de pisar propiedades finales.
- Cambiá selectores por ID a clases cuando sea seguro (verificando su uso).
- Eliminá duplicados y reglas muertas verificadas.
- Dejá `!important` solo donde sea imprescindible, con un comentario breve
  que explique por qué.

### Fase 4: Modo oscuro por tokens
- En `[data-theme="dark"]` dejá la redefinición de variables y solo los
  overrides de componentes estrictamente necesarios, comprobados con las
  capturas.
- Aplaná el nesting nativo: sin reglas anidadas.
- Señalame (sin corregir por tu cuenta) los pares de colores del modo
  oscuro con contraste por debajo de WCAG AA, para que yo decida.

### Fase 5: Responsive y unidades
- Unificá los breakpoints alineados con Bootstrap (evitá solapes como
  992/993; usá 991.98px donde corresponda).
- Evitá `max-height` en media queries de layout salvo que sea indispensable;
  si lo es, explicá por qué.
- Pasá de `px` a `rem` en tipografía donde no altere el resultado actual,
  calculando equivalentes exactos.
- Cada componente lleva sus propios media queries al final de su archivo.

### Fase 6: Modularización y carga
Dividí el CSS en archivos numerados dentro de public/css/. El número define
el orden de carga. No uses @import.

```
public/css/
├── 00-tokens.css        # primitivos, semánticos, escala de z-index
├── 10-base.css          # reset, tipografía, estilos globales
├── 20-layout.css        # wrapper, sidebar, navbar, footer
├── 30-buttons.css
├── 31-forms.css         # inputs, switches, filtros
├── 32-cards.css
├── 33-tables.css
├── 34-modals.css
├── 35-badges.css        # badges de estado
├── 36-kpi.css
├── 37-stepper.css
├── 38-pdf-viewer.css
├── 50-select2.css       # integración Select2
├── 51-datatables.css    # integración DataTables
├── 90-dark.css          # tokens + excepciones mínimas
└── 99-utilities.css     # helpers de una sola propiedad
```

- Cada archivo tiene una única responsabilidad. Si surge algo que no
  encaja, creá un archivo nuevo con nombre claro. Prohibido un archivo
  "varios" o "misc".
- Buscá dónde se enlaza hoy style.css y reemplazá ese enlace por un único
  include PHP (por ejemplo includes/css.php) que cargue todos los *.css de
  public/css/ en orden alfabético, con `?v=` + filemtime para evitar caché
  vieja. Mostrame el cambio ANTES de aplicarlo y verificá que la ruta
  pública coincida con cómo se sirve el proyecto.
- Asegurate de que el style.css original NO se cargue a la vez que los
  módulos (evitá estilos duplicados). Conservalo como respaldo fuera de
  public/css/ hasta que yo confirme que todo se ve igual, y recién entonces
  proponé eliminarlo.

### Fase 7: Calidad continua
- Generá `CSS-GUIDELINES.md` (corto y concreto) con: estructura de archivos
  y orden de carga, reglas de tokens (colores solo en 00-tokens.css), la
  prohibición de `!important` y de selectores por ID, cómo agregar un
  componente, un color o una variante nueva, cómo funciona el modo oscuro
  (solo variables), breakpoints y escala de z-index.
- Proponé reglas de stylelint (`declaration-no-important`, colores hex fuera
  de tokens, límite de especificidad), pero NO lo instales sin preguntarme:
  requiere Node.
- Si hay un archivo de reglas permanentes para agentes (AGENTS.md,
  CLAUDE.md, .cursorrules o similar), proponé una versión breve de las
  reglas clave que apunte a CSS-GUIDELINES.md.
- Opcional, solo si te lo pido: un script simple que concatene y minifique
  los módulos en un solo archivo para producción.

## CRITERIOS DE ÉXITO
- Capturas antes/después equivalentes (cualquier diferencia mínima,
  explicada).
- `!important` reducido al mínimo y justificado uno por uno.
- Cero colores hex fuera de 00-tokens.css.
- Modo oscuro reducido a tokens más excepciones mínimas.
- Ningún selector usado en PHP, JS o por librerías fue eliminado o
  renombrado.
- CSS dividido en módulos claros, sin duplicados ni reglas muertas.
- El proyecto carga y funciona igual.

## ENTREGABLE FINAL
1. Resumen de cambios por fase con métricas antes/después (líneas,
   `!important`, colores hardcodeados, selectores por ID).
2. Lista de selectores que dejaste sin tocar por riesgo y por qué.
3. Instrucciones para probar y para revertir.
4. Próximos pasos sugeridos.

## CÓMO EMPEZAR
Leé este archivo completo y seguilo al pie de la letra. Empezá SOLO por la
Fase 0 (auditoría, sin modificar nada) y esperá mi confirmación antes de
pasar a la siguiente.