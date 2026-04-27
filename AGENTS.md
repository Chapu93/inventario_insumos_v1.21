# AGENTS.md - Guía para Agentes de Desarrollo

## ⚠️ REGLA FUNDAMENTAL
**NUNCA hacer modificaciones sin confirmación del usuario.** Siempre consultar antes de realizar cualquier cambio en el código, base de datos o configuración.

---

## 📋 Sobre el Proyecto

**Nombre:** SITIA (Sistema de Inventario de Telecomunicaciones, Insumos y Administración)  
**Tipo:** Aplicación web PHP  
**Base de datos:** MariaDB/MySQL  

---

## 🛠️ Stack Tecnológico

### Backend
- **PHP 8.x** - Lenguaje principal
- **PDO** - Conexión a base de datos
- **TCPDF** - Generación de documentos PDF

### Frontend
- **Bootstrap 5** - Framework CSS
- **jQuery** - JavaScript
- **DataTables** - Tablas interactivas con paginación
- **Select2** - Selectores mejorados
- **Chart.js** - Gráficos
- **Font Awesome** - Iconos

### Servidor
- **XAMPP** (Apache + MariaDB)

---

## 📁 Estructura del Proyecto

```
inventario_app/
├── ajax/                    # Endpoints AJAX (JSON responses)
├── includes/                # Archivos de configuración y funciones
│   ├── config.php          # Configuración principal (DB, constantes)
│   ├── auth.php            # Sistema de autenticación
│   ├── header.php          # Header HTML común
│   ├── footer.php          # Footer HTML común + scripts JS
│   ├── Logger.php          # Sistema de logging
│   └── validar_archivo.php # Validación centralizada de uploads
├── pages/                   # Páginas del sistema organizadas por módulo
│   ├── admin/              # Administración (usuarios, telecom, auditoría)
│   ├── asignaciones/       # Gestión de asignaciones y remitos
│   ├── insumos/            # Gestión de inventario
│   ├── pedidos/            # Gestión de pedidos y pendientes
│   └── reportes/           # Generación de reportes PDF
├── public/                  # Assets públicos (CSS, JS, imágenes)
├── uploads/                 # Archivos subidos (documentos, PDFs)
└── setup/                   # Scripts de migración SQL
```

---

## 🗃️ Módulos Principales

| Módulo | Carpeta | Descripción |
|--------|---------|-------------|
| **Insumos** | `pages/insumos/` | Inventario de equipos informáticos |
| **Asignaciones** | `pages/asignaciones/` | Remitos y asignación de insumos |
| **Pedidos** | `pages/pedidos/` | Solicitudes de soporte técnico y mantenimiento |
| **Tareas** | `pages/pedidos/` | Tareas internas del área técnica (`tareas_internas`) |
| **Administración** | `pages/admin/` | Usuarios, roles, telecomunicaciones, auditoría |
| **Reportes** | `pages/reportes/` | Generación de PDFs |

---

## 🗄️ Tablas Principales de Base de Datos

### Inventario
- `insumos` - Registro principal de equipos
- `pcs_completas`, `notebooks`, `impresoras`, `monitores`, `escaneres` - Datos específicos por tipo
- `ingresos`, `ingresos_documentos` - Licitaciones y documentación

### Asignaciones
- `remitos` - Cabecera de asignaciones
- `remitos_detalle` - Detalle de insumos asignados

### Pedidos y Tareas
- `pedidos` - Solicitudes de soporte
- `pedidos_historial`, `pedidos_adjuntos`, `pedidos_informes`, `pedidos_informes_secuencia`
- `tareas_internas`, `tipos_tarea_interna` - Tareas exclusivas del área técnica

### Telecomunicaciones
- `sedes_internet`, `sedes_telefonia_lineas`, `sedes_vigilancia`, `sedes_planos`

### Usuarios
- `usuarios`, `roles`, `sesiones`, `auditoria_acciones`

### Ubicaciones
- `localidades`, `sedes`, `areas`, `zonas`

---

## ✅ Patrones de Código a Seguir

### Antes de crear algo nuevo, verificar si ya existe:
```php
// Buscar funciones existentes en:
// - includes/config.php
// - includes/auth.php
// - includes/validar_archivo.php
```

### Autenticación y Permisos
```php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('modulo', 'accion');
// o
if (!tienePermiso('modulo', 'accion')) { ... }
```

### Validación de Archivos (SIEMPRE usar función centralizada)
```php
// Para documentos generales
$validacion = validarArchivoDocumento($archivo);

// Solo PDF
$validacion = validarArchivoPdf($archivo, $maxSize);

// Para planos (sin SVG)
$validacion = validarArchivoPlano($archivo);

if (!$validacion['valido']) {
    json_error($validacion['error'], 400);
}
```

### Respuestas AJAX
```php
// Éxito
json_success(['data' => $resultado]);

// Error
json_error('Mensaje de error', 400);
```

### Logging (solo en desarrollo)
```php
Logger::debug("Mensaje", ['contexto' => $variable]);
Logger::info("Acción completada", [...]);
Logger::error("Error ocurrido", ['error' => $e->getMessage()]);
```

### Protección CSRF
```php
// En formularios HTML
<?php echo csrf_input(); ?>

// Verificar en backend
if (!verify_csrf()) {
    json_error('Token CSRF inválido', 403);
}
```

### DataTables (idioma español)
- Ya configurado globalmente en `footer.php`
- Usar clase `.datatable` para inicialización automática
- Para server-side: usar `data-ssp="1"`

### Empty States en tablas
```php
<?php if (empty($datos)): ?>
    <div class="text-center py-4">
        <i class="fas fa-[icono] fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No hay datos disponibles</h5>
        <p class="text-muted">Mensaje descriptivo</p>
    </div>
<?php endif; ?>
```

### Notificaciones Toast
```javascript
showToast('Mensaje', 'success'); // o 'error', 'warning', 'info'
```

---

## 🔒 Archivos de Configuración Importantes

| Archivo | Descripción | ⚠️ Precaución |
|---------|-------------|---------------|
| `includes/config.php` | Conexión DB, constantes, funciones globales | MUY SENSIBLE |
| `includes/auth.php` | Sistema de autenticación y permisos | CRÍTICO |
| `includes/header.php` | Menú de navegación, meta tags | Afecta todo el sistema |
| `includes/footer.php` | Scripts JS globales, DataTables config | Afecta todo el sistema |

---

## 📝 Convenciones de Código

### Idioma
- **Todo en español**: variables, comentarios, mensajes, nombres de archivos

### Nombres de archivos
- Minúsculas con guiones bajos: `ingresos_listar.php`, `pedidos_acciones.php`
- AJAX endpoints en carpeta `ajax/`
- Páginas en subcarpetas de `pages/`

### Variables PHP
```php
$nombreVariable = 'camelCase para variables';
$id_usuario = 'snake_case también aceptado para IDs';
```

### Consultas SQL
```php
// SIEMPRE usar prepared statements
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = ?");
$stmt->execute([$id]);
```

---

## 🚫 Lo que NO debe hacer un agente

1. **NO modificar sin confirmación** - Siempre preguntar antes
2. **NO eliminar código funcional** - Solo modificar lo mínimo necesario
3. **NO crear funciones duplicadas** - Revisar si ya existe antes de crear
4. **NO modificar estructura de BD** sin scripts de migración
5. **NO hardcodear credenciales** - Usar variables de entorno o config.php
6. **NO ignorar la validación de archivos** - Siempre usar `validarArchivo()`
7. **NO ignorar permisos** - Siempre verificar con `tienePermiso()`
8. **NO guardar uploads fuera de `/uploads/`** - Usar siempre la constante `UPLOAD_BASE_DIR` y la estructura organizada por módulo

---

## 🔄 Flujo de Desarrollo Recomendado

1. **Entender el requerimiento** - Preguntar si algo no está claro
2. **Buscar código existente** - Revisar si hay funciones/patrones reutilizables
3. **Proponer solución** - Describir cambios antes de implementar
4. **Esperar confirmación** - No proceder sin aprobación
5. **Implementar cambios mínimos** - Solo lo necesario
6. **Verificar sintaxis** - `php -l archivo.php`
7. **Documentar cambios** - Comentar código cuando sea necesario

---

## 📚 Archivos de Migración SQL

Ubicación: `setup/`

- `inventario_insumos_v1.sql` - Esquema completo de la base de datos
- `migracion_pedidos.sql` - Módulo de pedidos
- `migracion_tareas_internas.sql` - Tabla para tareas internas
- `migracion_tipo_tarea_interna.sql` - Tipos categorizados de tareas
- `migracion_remito_firmado.sql` - Adjuntar remitos escaneados
- `migracion_uploads.sql` - Adaptación de base para paths de uploads
- `migracion_informes_numeracion.sql` - Secuencia anual para informes
- `migracion_nota_solicitud.sql` - Nota PDF adjunta a remitos

Para nuevas migraciones, crear archivo con formato: `migracion_[modulo].sql`

---

## 🆘 Funciones Útiles Existentes

### En `config.php`
- `conectarDB()` - Conexión PDO a la base de datos
- `app_base_url()` - URL base de la aplicación
- `csrf_token()`, `csrf_input()`, `verify_csrf()` - Protección CSRF
- `procesarArchivoAdjunto()` - Guardar archivos de ingresos
- `generarNumeroRemito()` / `generarNumeroInforme()` - Secuencias por año
- `getFromCache()`, `setToCache()`, `invalidarCache()` - Caché simple (ej. Dashboard)

### En `auth.php`
- `estaAutenticado()` - Verificar si hay sesión activa
- `requerirAutenticacion()` - Redirigir si no está logueado
- `tienePermiso($modulo, $accion)` - Verificar permiso específico
- `verificarPermiso($modulo, $accion)` - Redirigir si no tiene permiso
- `obtenerUsuarioId()` - ID del usuario actual
- `tieneRol($nombreRol)` - Verificar rol del usuario
- `verificarRateLimitLogin()`, `registrarIntentoLogin()` - Bloqueos contra fuerza bruta

### En `validar_archivo.php`
- `validarArchivo($archivo, $opciones)` - Validación completa
- `validarArchivoDocumento($archivo)` - Para documentos generales
- `validarArchivoPdf($archivo)` - Solo PDFs
- `validarArchivoPlano($archivo)` - Para planos (sin SVG)
- `validarArchivoImagen($archivo)` - Solo imágenes

---

## 🔐 Credenciales de Desarrollo

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `admin` | `admin123` | Super Administrador |

---

## 🔗 Dependencias entre Módulos

```
┌─────────────────────────────────────────────────────────────────┐
│                        ESTRUCTURA GEOGRÁFICA                     │
│  Zonas → Localidades → Sedes → Áreas                            │
│  (Río Negro organizado en zonas geográficas)                    │
└─────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│   INSUMOS     │    │   PEDIDOS     │    │   TELECOM     │
│               │    │               │    │               │
│ - Equipos     │◄───│ - Soporte     │    │ - Internet    │
│ - Stock       │    │ - Manten.     │    │ - Telefonía   │
│ - Ingresos    │    │ - Reparación  │    │ - Vigilancia  │
└───────┬───────┘    └───────────────┘    │ - Planos      │
        │                                  └───────────────┘
        ▼
┌───────────────┐
│ ASIGNACIONES  │
│               │
│ - Remitos     │
│ - Devolución  │
└───────────────┘
```

### Relaciones Clave:

| Origen | Destino | Relación |
|--------|---------|----------|
| **Insumos** → **Asignaciones** | Un insumo se asigna mediante un remito a una sede/área/persona |
| **Insumos** → **Pedidos** | Un pedido puede estar relacionado a un insumo específico (`id_insumo_relacionado`) |
| **Ingresos** → **Insumos** | Los insumos provienen de ingresos (licitaciones, compras) |
| **Sedes** → **Todo** | Casi todas las entidades están vinculadas a una sede |
| **Usuarios** → **Todo** | Auditoría, historial, asignados, etc. |

### Flujos Importantes:

1. **Ingreso de Insumo:**
   ```
   Crear Ingreso (licitación) → Agregar Insumos al Ingreso → Insumo queda "Disponible"
   ```

2. **Asignación de Insumo:**
   ```
   Insumo "Disponible" → Crear Remito → Insumo pasa a "Asignado"
   ```

3. **Devolución:**
   ```
   Insumo "Asignado" → Devolver desde Remito → Insumo vuelve a "Disponible"
   ```

4. **Pedido de Soporte / Insumos:**
   ```
   Crear Pedido → Relacionar con Insumo → Asignar Técnico → Completar con Informe (Soporte) o Preparar Remito (Insumo)
   ```

5. **Tareas Internas:**
   ```
   Crear Tarea Interna → Disponible → Técnico "Toma" la tarea → En Proceso → Se marca Completada
   ```

---

## 👥 Sistema de Permisos

### Jerarquía de Roles

```
┌────────────────────────────────────────┐
│         SUPER ADMINISTRADOR            │  ← Acceso total + gestión usuarios
│              (id_rol: 1)               │
├────────────────────────────────────────┤
│           ADMINISTRADOR                │  ← Acceso total sin gestión usuarios
│              (id_rol: 2)               │
├────────────────────────────────────────┤
│            OPERADOR                    │  ← Operaciones diarias
│              (id_rol: 3)               │
├────────────────────────────────────────┤
│            CONSULTOR                   │  ← Solo lectura + crear asignaciones
│              (id_rol: 4)               │
└────────────────────────────────────────┘
```

### Permisos por Módulo

#### Insumos (`insumos`)
| Permiso | Super Admin | Admin | Operador | Consultor |
|---------|:-----------:|:-----:|:--------:|:---------:|
| `ver` | ✅ | ✅ | ✅ | ✅ |
| `crear` | ✅ | ✅ | ✅ | ❌ |
| `editar` | ✅ | ✅ | ✅ | ❌ |
| `eliminar` | ✅ | ✅ | ❌ | ❌ |
| `baja` | ✅ | ✅ | ✅ | ❌ |

#### Asignaciones (`asignaciones`)
| Permiso | Super Admin | Admin | Operador | Consultor |
|---------|:-----------:|:-----:|:--------:|:---------:|
| `ver` | ✅ | ✅ | ✅ | ✅ |
| `crear` | ✅ | ✅ | ✅ | ✅ |
| `editar` | ✅ | ✅ | ✅ | ❌ |
| `anular` | ✅ | ✅ | ❌ | ❌ |
| `devolver` | ✅ | ✅ | ✅ | ❌ |

#### Pedidos (`pedidos`)
| Permiso | Super Admin | Admin | Operador | Consultor |
|---------|:-----------:|:-----:|:--------:|:---------:|
| `ver_propios` | ✅ | ✅ | ✅ | ✅ |
| `ver_todos` | ✅ | ✅ | ✅ | ✅ |
| `crear` | ✅ | ✅ | ✅ | ❌ |
| `gestionar` | ✅ | ✅ | ✅ | ❌ |
| `asignar` | ✅ | ✅ | ❌ | ❌ |
| `informe` | ✅ | ✅ | ✅ | ❌ |
| `eliminar` | ✅ | ✅ | ❌ | ❌ |

#### Telecomunicaciones (`telecom`)
| Permiso | Super Admin | Admin | Operador | Consultor |
|---------|:-----------:|:-----:|:--------:|:---------:|
| `ver` | ✅ | ✅ | ✅ | ✅ |
| `crear` | ✅ | ✅ | ✅ | ❌ |
| `editar` | ✅ | ✅ | ❌ | ❌ |
| `eliminar` | ✅ | ✅ | ❌ | ❌ |

#### Usuarios (`usuarios`)
| Permiso | Super Admin | Admin | Operador | Consultor |
|---------|:-----------:|:-----:|:--------:|:---------:|
| `ver` | ✅ | ✅ | ❌ | ❌ |
| `crear` | ✅ | ❌ | ❌ | ❌ |
| `editar` | ✅ | ❌ | ❌ | ❌ |
| `eliminar` | ✅ | ❌ | ❌ | ❌ |
| `cambiar_rol` | ✅ | ❌ | ❌ | ❌ |

---

## 📝 Ejemplos de Código

### Ejemplo 1: Crear un Endpoint AJAX

```php
<?php
// Archivo: ajax/mi_accion.php

require_once '../includes/config.php';

// Verificar autenticación
if (!estaAutenticado()) {
    json_error('No autorizado', 401);
}

// Verificar permiso
if (!tienePermiso('modulo', 'accion')) {
    json_error('Sin permiso para esta acción', 403);
}

// Verificar CSRF para operaciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        json_error('Token CSRF inválido', 403);
    }
}

try {
    $db = conectarDB();
    
    // Obtener parámetros
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    
    if ($id <= 0) {
        json_error('ID inválido', 400);
    }
    
    // Realizar operación
    $stmt = $db->prepare("SELECT * FROM tabla WHERE id = ?");
    $stmt->execute([$id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultado) {
        json_error('Registro no encontrado', 404);
    }
    
    // Respuesta exitosa
    json_success(['data' => $resultado]);
    
} catch (Exception $e) {
    Logger::error('Error en mi_accion', ['error' => $e->getMessage()]);
    json_error('Error interno del servidor', 500);
}
```

### Ejemplo 2: Crear una Página con DataTable

```php
<?php
// Archivo: pages/modulo/listar.php

require_once '../../includes/config.php';
requerirAutenticacion();

// Verificar permiso
if (!tienePermiso('modulo', 'ver')) {
    $_SESSION['mensaje'] = 'No tienes permiso para acceder a este módulo';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

$db = conectarDB();

// Obtener datos
$stmt = $db->query("SELECT * FROM tabla ORDER BY fecha DESC");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-list me-2"></i>Listado</h1>
    <?php if (tienePermiso('modulo', 'crear')): ?>
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($registros)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay registros</h5>
                <p class="text-muted">Agregue un nuevo registro para comenzar</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $reg): ?>
                        <tr>
                            <td><?php echo $reg['id']; ?></td>
                            <td><?php echo htmlspecialchars($reg['nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($reg['fecha'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="ver.php?id=<?php echo $reg['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (tienePermiso('modulo', 'editar')): ?>
                                    <a href="editar.php?id=<?php echo $reg['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
```

### Ejemplo 3: Subir Archivo con Validación

```php
<?php
// En un endpoint AJAX o página de procesamiento

if (!empty($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
    
    // Usar validación centralizada
    $validacion = validarArchivoDocumento($_FILES['documento']);
    
    if (!$validacion['valido']) {
        json_error($validacion['error'], 400);
    }
    
    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../uploads/documentos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único
    $extension = $validacion['extension'];
    $nombreArchivo = 'doc_' . time() . '_' . uniqid() . '.' . $extension;
    $rutaCompleta = $uploadDir . $nombreArchivo;
    
    // Guardar archivo
    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $rutaCompleta)) {
        json_error('Error al guardar el archivo', 500);
    }
    
    // Guardar en BD
    $stmt = $db->prepare("INSERT INTO documentos (nombre, ruta, fecha) VALUES (?, ?, NOW())");
    $stmt->execute([
        basename($_FILES['documento']['name']),
        $nombreArchivo
    ]);
    
    json_success(['mensaje' => 'Archivo subido correctamente']);
}
```

### Ejemplo 4: Llamada AJAX desde JavaScript

```javascript
// Obtener datos (GET)
$.ajax({
    url: BASE + '/ajax/obtener_datos.php',
    method: 'GET',
    data: { id: idRegistro },
    dataType: 'json',
    success: function(resp) {
        if (resp.success) {
            console.log(resp.data);
        } else {
            showToast(resp.error || 'Error al cargar datos', 'error');
        }
    },
    error: function(xhr) {
        showToast('Error de conexión', 'error');
    }
});

// Enviar datos (POST con CSRF)
$.ajax({
    url: BASE + '/ajax/guardar_datos.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        _csrf: document.querySelector('meta[name="csrf-token"]').content,
        nombre: $('#nombre').val(),
        descripcion: $('#descripcion').val()
    }),
    success: function(resp) {
        if (resp.success) {
            showToast('Guardado correctamente', 'success');
            location.reload();
        } else {
            showToast(resp.error || 'Error al guardar', 'error');
        }
    },
    error: function(xhr) {
        showToast('Error de conexión', 'error');
    }
});
```
