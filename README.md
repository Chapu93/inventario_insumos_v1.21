# Sistema de Gestión de Insumos Informáticos

## Descripción
Sistema web para la gestión de insumos informáticos dentro de un organismo administrativo. Permite control de inventario, asignación de insumos a personas y generación de remitos en PDF.

## Novedades recientes
- Flujo de asignaciones Localidad → Sede → Área → Insumo (carga dinámica desde BD)
- Remitos con numeración única anual `NNNN_YYYY`
- Generación de PDF robusta usando `FPDI` y plantilla `membretada.pdf` (autodetección de tamaño/orientación y fallback a A4 si la importación falla)
- Dashboard con contadores y gráficos (Chart.js) y actualización por AJAX
- Devoluciones parciales con historial (`remitos_detalle.cantidad_devuelta`) vía endpoint `ajax/devolver_insumos.php`
- Configuración portable vía variables de entorno (`APP_BASE_URL`, `DB_HOST`, etc.) y deducción automática de base pública

## Requisitos del Sistema
- Servidor Web: Apache/Nginx
- PHP: 7.4 o superior
- MySQL/MariaDB: 5.7+ / 10.x+
- Extensiones PHP: PDO, JSON, mbstring
- Composer (para instalar `setasign/fpdf` y `setasign/fpdi`)

## Instalación
1. Clonar el repositorio y cambiar a la rama de trabajo
   ```bash
   git clone <repo>
   cd inventario_insumos
   git checkout remito-dashboard
   ```
2. Instalar dependencias PHP (PDFs)
   ```bash
   composer install
   ```
3. Importar la base de datos
   ```bash
   mysql -u <usuario> -p < inventario_insumos_v1.sql
   ```
4. Configurar variables de entorno (vhost, .env o entorno del sistema)
   - `APP_BASE_URL`: ruta base pública de la app (ej: `/inventario_app`)
   - `DB_HOST`: host (ej: `localhost`)
   - `DB_PORT`: puerto (por defecto `3306`)
   - `DB_NAME`: nombre de BD (ej: `inventario_insumos_v1`)
   - `DB_USER`: usuario
   - `DB_PASS`: contraseña
   - `DB_SOCKET` (opcional): socket Unix si aplica

   Nota: si no defines `APP_BASE_URL`, la app intenta deducirla automáticamente; algunas redirecciones usan el valor por defecto `/inventario_app`.

5. Configuración de Apache (recomendado)
   - Habilitar `.htaccess` (Rewrite/AllowOverride según corresponda)
   - El directorio `includes/` está protegido contra acceso directo

## Estructura del Proyecto
```
inventario_app/
├── includes/                 # Configuración y helpers
│   ├── config.php            # Conexión PDO, APP_BASE_URL/BASE_URL, generación de remitos
│   ├── header.php            # Layout y navegación (usa app_base_url())
│   └── footer.php            # Scripts comunes
├── pages/
│   ├── dashboard.php
│   ├── insumos/
│   │   ├── agregar.php
│   │   ├── editar.php
│   │   ├── eliminar.php
│   │   ├── listar.php
│   │   ├── ver.php
│   │   └── ver_ajax.php
│   ├── asignaciones/
│   │   ├── listar.php
│   │   ├── nueva.php         # Flujo Localidad→Sede→Área→Insumo
│   │   ├── nueva_simple.php
│   │   └── cambiar_estado.php
│   └── reportes/
│       ├── remito.php        # Vista HTML de remitos
│       └── remito_pdf.php    # PDF con FPDI y plantilla membretada
├── ajax/
│   ├── cargar_sedes.php
│   ├── cargar_areas.php
│   ├── cargar_insumos.php
│   ├── insumos_disponibles.php
│   ├── localidades_list.php
│   ├── sedes_por_localidad.php
│   ├── areas_por_sede.php
│   ├── remito_items.php
│   ├── remito_detalle.php
│   ├── devolver_insumos.php
│   └── contadores_dashboard.php
├── public/
│   ├── css/style.css
│   └── js/main.js            # Utilidades, AJAX y gráficos
├── membretada.pdf            # Plantilla de fondo para remitos
├── inventario_insumos_v1.sql # Esquema y datos de ejemplo
├── composer.json             # Dependencias FPDI/FPDF
└── index.php                 # Redirección usando BASE_URL
```

## Base de Datos (resumen)
Tablas principales: `insumos`, `remitos`, `remitos_detalle`, `sedes`, `areas`, `localidades`, `zonas`, `puntos_stock`.

Relaciones relevantes:
- `sedes.id_localidad` → `localidades.id_localidad`
- `localidades.id_zona` → `zonas.id_zona`
- `sede_areas` relaciona sedes con áreas disponibles (catálogo)
- `remitos_detalle.id_insumo` → `insumos.id_insumo`
- `remitos.id_area` → `areas.id_area`

## Uso del Sistema
### Asignaciones
1. Asignaciones → Nueva Asignación
2. Seleccionar `Localidad` → se cargan `Sedes`
3. Seleccionar `Sede` → se cargan `Áreas` e `Insumos disponibles`
   - “Varios” solo aparece si `cantidad > 0` (múltiples unidades)
4. Completar datos de persona, fecha y observaciones (opcional)
5. Guardar: se genera un remito único `REMITO_YYYY_NNNN` y redirige a su vista

### Remitos
- Numeración: correlativo anual con padding de 4 dígitos (formato `NNNN_YYYY`)
- PDF: botón “Imprimir remito” abre `reportes/remito_pdf.php`
  - Carga la plantilla `membretada.pdf` (autodetección de tamaño/orientación). Si falla, usa A4 en blanco

### Devoluciones
- Desde Asignaciones → Listar: botón “Devolver insumos” permite devoluciones parciales
- Se actualiza `remitos_detalle.cantidad_devuelta` y estados/stock de `insumos`

## Variables de Entorno
- `APP_BASE_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SOCKET`

## Desarrollo
- Rama de trabajo: `remito-dashboard`
- Flujo recomendado:
  1. Crear rama desde `remito-dashboard` o trabajar sobre ella
  2. Commit y push
  3. Abrir PR hacia `main` para revisión

## Seguridad
- Prepared statements en consultas
- Directorio `includes/` bloqueado por `.htaccess`
- Sanitización de salida en vistas (`htmlspecialchars`)

## Mantenimiento
- Backups regulares de BD
- Revisión de logs del servidor web
- Actualización de dependencias controlada (`composer update`)

## Solución de problemas
- CSS/JS no cargan: revisar `APP_BASE_URL` y rutas deducidas
- PDF no se genera:
  - Ejecutar `composer install`
  - Verificar permisos/lectura de `membretada.pdf`
  - Asegurar que `membretada.pdf` sea un PDF válido (no corrupto)
- Insumos no aparecen al asignar: confirmar estado “Disponible”, sede correcta y `cantidad > 0` para “Varios”

## Soporte
Para consultas técnicas, contactar al administrador del sistema.