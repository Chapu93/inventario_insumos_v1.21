# Sistema de Gestión de Insumos Informáticos

## Descripción
Sistema web para la gestión de insumos informáticos dentro de un organismo administrativo. Permite el control de inventario, asignación de insumos a personas y generación de remitos en PDF.

## Novedades recientes
- Asignaciones con flujo Localidad → Sede → Área → Insumo (carga dinámica desde BD)
- Remitos con numeración única anual en formato: `REMITO_YYYY_NNNN`
- Generación de PDF de remitos usando plantilla `membretada.pdf`
- Configuración portable vía variables de entorno (`APP_BASE_URL`, `DB_HOST`, etc.)

## Requisitos del Sistema
- Servidor Web: Apache/Nginx
- PHP: 7.4 o superior
- MySQL/MariaDB: 5.7+ / 10.x+
- Extensiones PHP: PDO, JSON, mbstring
- Composer (para instalar FPDI/FPDF)

## Instalación
1. Clonar el repositorio y cambiar a la rama de trabajo
   ```bash
   git clone <repo>
   cd inventario_insumos
   git checkout feat/optimizaciones-iniciales-20250811
   ```
2. Instalar dependencias PHP (para PDFs)
   ```bash
   composer install
   ```
3. Importar la base de datos
   ```bash
   mysql -u <usuario> -p < inventario_insumos_v1.sql
   ```
4. Configurar variables de entorno (vhost, .env o entorno del sistema)
   - `APP_BASE_URL`: ruta base donde vive la app (ej: `/inventario_app`)
   - `DB_HOST`: host de BD (ej: `localhost`)
   - `DB_PORT`: puerto (por defecto `3306`)
   - `DB_NAME`: nombre de BD (ej: `inventario_insumos_v1`)
   - `DB_USER`: usuario
   - `DB_PASS`: contraseña
   - `DB_SOCKET` (opcional): socket Unix si aplica

   Nota: Si no defines `APP_BASE_URL`, por defecto se usa `/inventario_app`.

5. Configuración de Apache (recomendado)
   - Asegúrate de que `.htaccess` esté habilitado
   - El directorio `includes/` ya está protegido contra acceso directo

## Estructura del Proyecto
```
inventario_app/
├── includes/                 # Configuración y helpers
│   ├── config.php            # Conexión PDO, APP_BASE_URL, generación de remitos
│   ├── header.php            # Header/navegación (usa BASE_URL)
│   └── footer.php            # JS/CSS y plugins (usa BASE_URL)
├── pages/                    # Páginas principales
│   ├── dashboard.php
│   ├── insumos/              # Gestión de insumos
│   ├── asignaciones/         # Gestión de asignaciones
│   │   └── nueva.php         # Flujo Localidad→Sede→Área→Insumo
│   └── reportes/
│       ├── remito.php        # Vista HTML de remitos
│       └── remito_pdf.php    # Generación de PDF con FPDI y membretada.pdf
├── ajax/                     # Endpoints AJAX
│   ├── cargar_sedes.php      # NUEVO: sedes por localidad
│   ├── cargar_areas.php      # áreas por sede
│   ├── cargar_insumos.php    # insumos disponibles por sede
│   └── contadores_dashboard.php
├── public/
│   ├── css/style.css
│   └── js/main.js            # Funciones JS (BASE_URL y cargas dinámicas)
├── membretada.pdf            # Plantilla de fondo para remitos
├── inventario_insumos_v1.sql # Base de datos
├── composer.json             # Dependencias FPDI/FPDF
└── index.php                 # Redirección usando BASE_URL
```

## Configuración de la Base de Datos (resumen)
Tablas principales: `insumos`, `remitos`, `remitos_detalle`, `sedes`, `areas`, `localidades`, `zonas`, `puntos_stock`.

Relaciones relevantes:
- `sedes.id_localidad` → `localidades.id_localidad`
- `localidades.id_zona` → `zonas.id_zona`
- `sede_areas` relaciona sedes con áreas disponibles
- `remitos_detalle.id_insumo` → `insumos.id_insumo`
- `remitos.id_area` → `areas.id_area`

## Uso del Sistema
### Asignaciones
1. Navegar a: Asignaciones → Nueva Asignación
2. Seleccionar `Localidad` → se cargan `Sedes` de esa localidad
3. Seleccionar `Sede` → se cargan `Áreas` de esa sede y `Insumos disponibles`
   - Insumos tipo "Varios" solo aparecen si `cantidad > 0`
4. Completar `Nombre` y `Apellido` (manual), `Fecha` (por defecto hoy) y opcional `Observaciones`
5. Guardar: se genera un número de remito único en formato `REMITO_YYYY_NNNN` y se redirige a la vista de remito

### Remitos
- Formato confirmado: `REMITO_YYYY_NNNN`
  - Correlativo anual con padding de 4 dígitos (0001, 0002, ...)
  - Inmutable una vez generado y guardado en `remitos.numero_remito`
- PDF: botón “Imprimir remito” abre `remito_pdf.php` que usa `membretada.pdf` como fondo

## Variables de Entorno
- `APP_BASE_URL`: base pública de la app (ej: `/inventario_app`)
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SOCKET`

## Desarrollo
- Rama de trabajo: `feat/optimizaciones-iniciales-20250811`
- Flujo recomendado:
  1. Hacer cambios y commits en la rama
  2. Push al remoto
  3. Abrir PR hacia `main` para revisión cuando esté listo (no se fusiona sin aprobación)

## Seguridad
- Consultas con prepared statements
- Directorio `includes/` bloqueado por `.htaccess`
- Sanitización de salida con `htmlspecialchars` en vistas

## Mantenimiento
- Backups regulares de BD
- Revisión de logs del servidor web
- Actualización de dependencias (`composer update`) bajo control

## Solución de problemas
- No carga CSS/JS: revisar `APP_BASE_URL`
- No genera PDF: ejecutar `composer install` y verificar permisos de `membretada.pdf`
- Insumos no aparecen al asignar: confirmar estado “Disponible”, sede correcta y cantidad > 0 para “Varios”

## Soporte
Para consultas técnicas, contactar al administrador del sistema. 