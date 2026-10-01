# ROL
Sos un ingeniero frontend senior especializado en CSS escalable y Bootstrap 5.
Vas a refactorizar el archivo style.css de un proyecto PHP + Bootstrap 5.

# CONTEXTO
- style.css tiene ~2250 líneas y fue generado de forma incremental con agentes de IA.
- Problemas detectados:
  * ~520 usos de `!important` (270 solo en el modo oscuro).
  * El modo oscuro ocupa ~600 líneas redefiniendo componentes completos
    (botones, card-headers, modales) en vez de apoyarse en variables.
  * Usa CSS nesting nativo dentro de `[data-theme="dark"]`, que no funciona
    en navegadores viejos.
  * Colores hex repetidos que ya tienen token (#4a9d95, #d9943f, #218838,
    #dc3545, #9b78db, etc.) y colores fijos dentro de SVG data-URI.
  * Selectores duplicados (ej: `#content > .container-fluid` aparece dos veces).
  * Selectores por ID que elevan la especificidad.
  * `--bs-secondary` (#fff) no coincide con `--bs-secondary-rgb` (155,183,162).
  * Media queries que mezclan `max-width` y `max-height`, breakpoints que se
    solapan (992 / 993) y `font-size` en px que no escala.
  * `z-index` sueltos (1000, 1050, 1060, 1080, 1090, 1100) sin escala central.
# ACCESO AL PROYECTO
Tenés el proyecto abierto en el IDE. No esperes a que te pase archivos:
buscá vos mismo toda la información que necesites.

- Explorá la estructura completa del proyecto antes de empezar (carpetas de
  vistas, includes, parciales, plantillas, assets, JS, y cualquier otro lugar
  donde se pueda usar una clase o un ID).
- Identificá todos los archivos que cargan o dependen de style.css: vistas
  PHP, HTML, plantillas, archivos JS propios, y librerías de terceros
  (DataTables, Select2, Bootstrap, etc.).
- Antes de eliminar, renombrar o modificar cualquier selector, buscá su uso
  en TODO el proyecto con grep o búsqueda global, incluyendo:
  * clases escritas en HTML/PHP,
  * clases agregadas o quitadas desde JS (classList, addClass, toggleClass,
    querySelector, jQuery, innerHTML, template strings),
  * clases armadas por concatenación o dinámicamente (ej: "btn-" + tipo),
  * IDs y atributos de datos (data-theme, data-bs-*),
  * clases generadas por librerías.
- Excluí de la búsqueda carpetas como vendor/, node_modules/ y .git/, salvo
  para consultar cómo una librería genera sus clases.
- Si no podés acceder a alguna parte del proyecto, o una búsqueda no te da
  certeza (por ejemplo, clases dinámicas), no asumas que el selector es
  seguro de borrar: marcalo como "no verificado" y preguntame.
- Si no podés ejecutar comandos o un navegador (para las capturas), decímelo
  al principio y proponé la alternativa manual.
  

# OBJETIVO
Reducir la deuda técnica y dejar el CSS escalable y mantenible, SIN cambiar
cómo se ve ni cómo funciona la aplicación.

# REGLAS INNEGOCIABLES
1. Cero cambios visuales: cada pantalla debe verse igual, en modo claro y en
   modo oscuro, en todos los breakpoints.
2. No renombres ni elimines clases, IDs ni atributos que usen los archivos
   PHP, HTML o JS. Antes de borrar o renombrar cualquier selector, buscalo
   con grep en TODO el proyecto (vistas PHP, JS, plantillas), incluyendo
   clases armadas dinámicamente o generadas por librerías (DataTables,
   Select2, Bootstrap JS, etc.). Si hay duda, NO lo borres: listalo para
   que yo decida.
3. No modifiques HTML, PHP ni JS salvo que sea imprescindible. Si lo fuera,
   pedime confirmación antes y explicá por qué.
4. Mantené el mecanismo actual del tema oscuro (`[data-theme="dark"]` y la
   clase/atributo que use el JS). Podés agregar compatibilidad con
   `data-bs-theme`, pero sin romper lo existente.
5. No agregues dependencias nuevas sin preguntarme. Si el proyecto no tiene
   paso de build (Sass/PostCSS), el resultado debe poder funcionar con CSS
   plano.
6. Trabajá en una rama de git nueva y hacé commits pequeños por paso, para
   poder revertir.

# PROCESO (por fases, esperá mi OK entre fase y fase)

## Fase 0: Auditoría (sin modificar nada)
- Leé style.css completo y los archivos PHP/JS que lo usan.
- Entregame un informe con:
  * Conteo de `!important` por sección y cuáles son realmente necesarios
    (por ejemplo, para vencer estilos inline o de librerías).
  * Lista de colores hardcodeados y a qué token corresponde cada uno.
  * Selectores duplicados o muertos (con evidencia de grep).
  * Qué overrides del modo oscuro se pueden eliminar porque ya los cubren
    los tokens.
  * Riesgos y partes que no recomendás tocar.
- Proponé la estructura de archivos final y el orden de los cambios.

## Fase 1: Red de seguridad visual
- Armá una verificación de regresión visual ANTES de tocar el CSS:
  capturas (Playwright o similar) de las pantallas principales en modo claro
  y oscuro, y en 3 anchos (mobile ~375px, tablet ~768px, desktop ~1440px).
  También de estados: hover, focus, modales abiertos, tablas con DataTables,
  Select2 abierto.
- Guardá esas capturas como línea base. Si no podés ejecutar el navegador,
  dame una checklist manual de pantallas y estados a revisar.

## Fase 2: Tokens y estructura
- Organizá los tokens en capas:
  * Primitivos (paleta cruda).
  * Semánticos (`--sitia-primary`, `--sitia-surface-*`, `--sitia-text`...).
  * De componente cuando haga falta.
- Corregí la inconsistencia `--bs-secondary` / `--bs-secondary-rgb` sin
  alterar el aspecto resultante.
- Creá una escala central de `z-index` con variables.
- Reemplazá todos los hex repetidos por variables. Para los SVG embebidos,
  usá `mask`/`currentColor` o un enfoque que dependa del token.

## Fase 3: Reducción de `!important` y especificidad
- Personalizá los componentes Bootstrap mediante sus variables nativas
  (`--bs-btn-*`, `--bs-table-*`, `--bs-card-*`, `--bs-modal-*`...) en lugar
  de pisar propiedades finales.
- Cambiá selectores por ID a clases cuando sea seguro (verificando el uso).
- Eliminá duplicados y reglas muertas.
- Dejá `!important` solo donde sea imprescindible, con un comentario
  breve que explique por qué.

## Fase 4: Modo oscuro por tokens
- Dejá en `[data-theme="dark"]` solo la redefinición de variables.
- Eliminá los overrides de componentes que sean redundantes y conservá
  únicamente los que sean realmente necesarios, comprobándolo con las
  capturas.
- Aplanar el nesting nativo (sin anidar reglas dentro del bloque) para que
  funcione en navegadores sin soporte.

## Fase 5: Responsive y unidades
- Unificá los breakpoints en variables o comentarios consistentes,
  alineados con Bootstrap (evitá solapes de 992/993).
- Evitá `max-height` en media queries de layout salvo que sea indispensable.
  Si es así, explicá por qué.
- Cambiá `px` por `rem` en tipografía donde no altere el resultado actual
  (calculando los equivalentes exactos).

## Fase 6: Modularización
- Dividí el CSS en módulos con responsabilidad única, por ejemplo:
  tokens, base/layout, componentes (botones, tarjetas, tablas, modales,
  formularios), integraciones (DataTables, Select2), tema oscuro, utilidades,
  responsive.
- Si no hay build, dejá un `style.css` final que importe o concatene los
  módulos, y explicame cómo cargarlos. Si hay Sass, usá partials.
- Un componente nuevo no debería requerir tocar más de un archivo.

## Fase 7: Calidad continua
- Proponé una configuración de stylelint con reglas como:
  `declaration-no-important`, prohibir colores hex fuera del archivo de
  tokens, límite de especificidad y orden consistente.
- Dejame un `CSS-GUIDELINES.md` corto con las reglas del proyecto (nomenclatura,
  dónde va cada cosa, cómo agregar un componente o un color nuevo,
  prohibiciones) para que cualquier agente o persona futura las siga.

# CRITERIOS DE ÉXITO
- Capturas antes/después equivalentes (diferencias mínimas explicadas).
- `!important` reducido a una cantidad mínima y justificada.
- Cero colores hex fuera del archivo de tokens.
- Modo oscuro reducido a tokens y a los overrides estrictamente necesarios.
- Ningún selector usado en PHP/JS fue eliminado o renombrado.
- El archivo o módulos resultantes son claramente más chicos y legibles.

# ENTREGABLE FINAL
1. Resumen de cambios por fase, con métricas antes/después (líneas,
   `!important`, colores hardcodeados, selectores por ID).
2. Lista de selectores que dejaste sin tocar por riesgo, y por qué.
3. Instrucciones para probar y para revertir.
4. Sugerencias de próximos pasos.

# CÓMO TRABAJAR
Si algo es ambiguo o riesgoso, preguntame antes de actuar. No hagas cambios
masivos de una sola vez: avanzá fase por fase y esperá mi confirmación.