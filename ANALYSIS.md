# ANALYSIS: Sistema de Gestión de Insumos

Fecha: 2025-10-13
Repositorio: /opt/lampp/htdocs/inventario_app

Resumen ejecutivo
-----------------
Aplicación PHP (procedural + PDO) para gestión de insumos, asignaciones (remitos) y devoluciones. Usa Composer (librerías `setasign/fpdf` y `setasign/fpdi`) para generar PDFs a partir de una plantilla `membretada.pdf`. El frontend usa Bootstrap 5, jQuery, DataTables, Select2 y Chart.js. La base de datos es MySQL/MariaDB (dump proporcionado en `inventario_insumos_v1.sql`).

Objetivos del análisis
----------------------
- Mapear rutas y endpoints importantes.
- Listar endpoints AJAX y su contract (inputs/outputs esperados).
- Resumir esquema de base de datos (tablas clave y columnas).
- Identificar flujos principales (crear remito, devolver insumos, agregar insumo).
- Señalar riesgos y recomendaciones priorizadas.

Mapa de rutas / páginas principales
----------------------------------
(URLs construidas con `app_base_url()`)
- `/index.php` — entrada principal (no forzada en el análisis, suele incluir header o redirección)
- `/pages/dashboard.php` — dashboard con contadores
- `/pages/insumos/listar.php` — lista de insumos
- `/pages/insumos/agregar.php` — formulario para agregar insumos
- `/pages/insumos/editar.php` — editar insumo
- `/pages/insumos/ver.php` — ver insumo
- `/pages/asignaciones/listar.php` — listar asignaciones/remitos (panel con filtros y modales)
- `/pages/asignaciones/nueva_pasos.php` — flujo paso a paso para nueva asignación
- `/pages/asignaciones/nueva_simple.php` — versión simple
- `/pages/reportes/remito.php` — vista del remito y link a PDF
- `/pages/reportes/remito_pdf.php?remito=NUM` — genera PDF del remito (usa FPDI/FPDF con `membretada.pdf`)
- `/pages/admin/...` — panel de administración (sedes, areas, telecom, migraciones)

Endpoints AJAX (contract breve)
-------------------------------
Todos devuelven JSON con `success: true|false` y `data` o `error`.

- `/ajax/cargar_sedes.php` (GET) — params: `localidad_id`
  - Response.data.sedes: [{id, nombre}]
- `/ajax/cargar_areas.php` (GET) — params: `sede_id`
  - Response.data.areas: [{id_area, nombre_area}]
- `/ajax/cargar_insumos.php` (GET) — params: `sede_id` (opcional)
  - Response.data.insumos: [{id_insumo, nombre_insumo, tipo_insumo, numero_serie, id_fisico, cantidad}]
- `/ajax/insumos_disponibles.php` (GET) — lista insumos disponibles (uso interno)
- `/ajax/remito_items.php` (GET) — params: `remito` (numero)
  - Response.data.items: [{id_insumo,...,cantidad,cantidad_devuelta,tipo_insumo,...}]
- `/ajax/remito_detalle.php` (GET) — params: `remito` (numero)
  - Response: `cab` (cabecera remito) y `items` (detalle)
- `/ajax/devolver_insumos.php` (POST JSON) — body: { remito: string, items: [{id_insumo, cantidad}, ...] }
  - Verifica CSRF via header `X-CSRF-Token` o campo `_csrf`.
- `/ajax/contadores_dashboard.php` (GET) — devuelve contadores para dashboard
- `/ajax/insumo_baja.php` (POST JSON) — dar de baja insumo(s) con motivo; requiere CSRF

Esquema de base de datos (resumen, tablas clave)
------------------------------------------------
(Extraído de `inventario_insumos_v1.sql`)

- `insumos` — almacena insumos, columnas relevantes:
  - `id_insumo, nombre_insumo, tipo_insumo, subcategoria_varios, numero_serie, id_fisico, id_patrimonio, cantidad, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual`
- `remitos` — remitos/assignaciones:
  - `id_remito, numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, estado, fecha_devolucion, observaciones`
- `remitos_detalle` — items de remito:
  - `id_detalle, id_remito, id_insumo, cantidad, cantidad_devuelta`
- `remito_secuencia` — control de secuencia por año para `generarNumeroRemito` (insert/for update used)
- `sedes, localidades, zonas, areas` — entidades geográficas/organizativas
- tablas específicas para subtipos: `pcs_completas, notebooks, impresoras, monitores, escaneres`
- `puntos_stock` — nombres de puntos de almacenamiento

Flujos principales
------------------
1) Crear asignación (remito)
   - UI: `pages/asignaciones/nueva_pasos.php` o `nueva.php` (la última redirige a `nueva_pasos.php`).
   - Se seleccionan Localidad→Sede→Área.
   - Usuario selecciona insumos (unitarios o 'Varios' con cantidad).
   - En backend (`nueva.php`): se abre transacción, se obtiene `numero_remito` usando `generarNumeroRemito($conexion)`, se inserta `remitos`, luego `remitos_detalle`, y se actualiza `insumos` (cantidad/estado/punto_stock). Commit y redirect a `reportes/remito.php`.

2) Generación de PDF del remito
   - `pages/reportes/remito_pdf.php` usa FPDI para cargar `membretada.pdf` si existe; posiciona texto y tabla con insumos y finalmente `Output('I', ...)` para enviar PDF al navegador.

3) Devoluciones
   - Desde `asignaciones/listar.php` se abre modal, carga items via `ajax/remito_items.php` y POST a `ajax/devolver_insumos.php` con X-CSRF-Token header. Backend actualiza `insumos` y `remitos_detalle.cantidad_devuelta`.

Seguridad y validaciones
------------------------
- PDO con atributos ERRMODE_EXCEPTION — buena práctica.
- CSRF: token en `includes/config.php` (meta tag en `header.php`) y JS configura `X-CSRF-Token` para AJAX. Algunas formas POST aún no incluyen el hidden `_csrf` input — recomendado añadir.
- Validaciones frontend: HTML5 + validación en JS (`needs-validation`, `checkValidity()`), y backend hace validaciones y `die` o redirecciones con mensajes en `$_SESSION`.

Riesgos y problemas detectados (priorizados)
--------------------------------------------
1. URL base: `app_base_url()` intenta deducir la ruta por `SCRIPT_NAME` si no hay `APP_BASE_URL`; esto puede fallar en distintos entornos. Recomendación: fijar `APP_BASE_URL` en `.env` y pegarlo en `includes/config.php`.
2. `remitoExiste()` crea nueva conexión DB en vez de recibir PDO — ineficiente; cambiar a inyección de PDO opcional para reusar conexión.
3. Algunos endpoints intentan modificar esquema en runtime (p. ej. agregar columna `cantidad_devuelta`) — esto es peligroso; mover a migraciones.
4. No todas las formas POST incluyen el campo `_csrf` (aunque AJAX lo maneja). Recomendación: helper `csrf_input()` y añadirlo en los formularios server-rendered.
5. Salidas antes de `header()` en algunas páginas se controlan con checks, pero hay logs y echoes en code paths; revisar para evitar "headers already sent".

Cambios recomendados (lista priorizada y estimada)
-------------------------------------------------
Prioridad alta (cambios bajos/seguros)
- R1: Asegurar `APP_BASE_URL` configurable y `app_base_url()` fallback. (30-60 min)
- R2: Añadir helper `csrf_input()` y usar en formularios POST (30-60 min)
- R3: Modificar `remitoExiste()` para aceptar PDO opcional y actualizar llamadas críticas (`generarNumeroRemito`, `nueva.php`). (30-60 min)

Prioridad media
- R4: Extraer/convertir cualquier DDL dinámico en migraciones (`pages/admin/migracion_00X.php`) y dejar los endpoints sin manipular esquema en runtime. (60-120 min)
- R5: Ejecutar `php -l` y arreglar errores de sintaxis o advertencias que aparezcan. (30-60 min)

Prioridad baja
- R6: Unificar uso de `BASE_URL` y `app_base_url()` para evitar discrepancias (refactor). (60-120 min)
- R7: Añadir tests de integración mínimos o script de smoke tests que use la SQL de ejemplo. (120-240 min, depende de acceso a DB)

Propuesta inmediata
------------------
Me pides aplicar cambios concretos después de que lea todo; ya terminé la lectura importante. Si quieres que aplique cambios mínimos y seguros ahora, los más recomendados son R1, R2 y R3. Puedo aplicarlos en un único commit, validar `php -l` y reportar resultados.

Archivos leídos y secciones claves
----------------------------------
- `includes/config.php` — helpers: `conectarDB()`, `generarNumeroRemito()`, CSRF helpers, `app_base_url()` y carga simple de `.env`.
- `includes/header.php` / `includes/footer.php` — meta CSRF, assets, sidebar/menu, DataTables defaults.
- `pages/asignaciones/nueva.php` — flujo de inserción de remito, uso de `generarNumeroRemito` y manejo de transacciones.
- `pages/asignaciones/listar.php` — filtros, modales para devolución, uso de AJAX.
- `pages/insumos/*` — listas, agregar, ver, baja; validaciones server-side.
- `pages/reportes/remito_pdf.php` — generación de PDF (FPDI) con plantilla.
- `public/js/main.js` — utilidades JS, carga AJAX y CSRF en headers.
- `public/css/style.css` — estilos.
- `inventario_insumos_v1.sql` — esquema y datos de ejemplo.

Siguientes pasos
----------------
- Confirmame si querés que aplique ahora los cambios R1, R2 y R3 (bajo riesgo). Yo aplicaré los parches, ejecutaré `php -l` y reportaré resultados.
- Si preferís otro conjunto o primero revisar el `ANALYSIS.md` generado, dime y avanzo.

---
Archivo generado: `ANALYSIS.md` en la raíz del proyecto. Revísalo y dime qué cambio quieres que haga primero.