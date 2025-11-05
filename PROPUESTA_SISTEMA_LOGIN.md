# Propuesta: Sistema de Login con Roles y Auditoría

## 📋 Estructura de Base de Datos

### 1. Tabla `usuarios`
```sql
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  id_rol INT NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso DATETIME NULL,
  INDEX idx_username (username),
  INDEX idx_email (email),
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);
```

### 2. Tabla `roles`
```sql
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol VARCHAR(50) UNIQUE NOT NULL,
  descripcion TEXT,
  permisos JSON NOT NULL COMMENT 'Permisos del rol en formato JSON',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Tabla `sesiones`
```sql
CREATE TABLE sesiones (
  id_sesion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  token_sesion VARCHAR(255) UNIQUE NOT NULL,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_ultimo_acceso DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fecha_cierre DATETIME NULL,
  activa TINYINT(1) DEFAULT 1,
  INDEX idx_token (token_sesion),
  INDEX idx_usuario (id_usuario),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);
```

### 4. Tabla `auditoria_acciones`
```sql
CREATE TABLE auditoria_acciones (
  id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_sesion INT NULL,
  accion VARCHAR(100) NOT NULL COMMENT 'crear_insumo, editar_insumo, eliminar_insumo, crear_asignacion, etc.',
  modulo VARCHAR(50) NOT NULL COMMENT 'insumos, asignaciones, reportes, usuarios, etc.',
  descripcion TEXT NOT NULL,
  entidad_tipo VARCHAR(50) NULL COMMENT 'insumo, asignacion, usuario, etc.',
  entidad_id INT NULL COMMENT 'ID de la entidad afectada',
  datos_antes JSON NULL COMMENT 'Estado anterior (para ediciones/eliminaciones)',
  datos_despues JSON NULL COMMENT 'Estado posterior (para creaciones/ediciones)',
  ip_address VARCHAR(45),
  resultado ENUM('exito', 'error') DEFAULT 'exito',
  mensaje_error TEXT NULL,
  fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuario (id_usuario),
  INDEX idx_sesion (id_sesion),
  INDEX idx_accion (accion),
  INDEX idx_modulo (modulo),
  INDEX idx_fecha (fecha_accion),
  INDEX idx_entidad (entidad_tipo, entidad_id),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_sesion) REFERENCES sesiones(id_sesion) ON DELETE SET NULL
);
```

## 👥 Roles Propuestos

### 1. **Super Administrador** (id_rol: 1)
- Acceso total al sistema
- Gestión de usuarios y roles
- Acceso a auditoría completa
- Configuración del sistema

**Permisos:**
```json
{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "anular", "devolver"],
  "reportes": ["ver", "exportar"],
  "usuarios": ["ver", "crear", "editar", "eliminar", "cambiar_rol"],
  "auditoria": ["ver_todo"],
  "telecom": ["ver", "editar"],
  "sedes": ["ver", "crear", "editar"],
  "areas": ["ver", "crear", "editar"]
}
```

### 2. **Administrador** (id_rol: 2)
- Gestión completa de inventario
- Creación de asignaciones
- Acceso a reportes
- NO puede gestionar usuarios

**Permisos:**
```json
{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "anular", "devolver"],
  "reportes": ["ver", "exportar"],
  "telecom": ["ver", "editar"],
  "sedes": ["ver"],
  "areas": ["ver"]
}
```

### 3. **Operador** (id_rol: 3)
- Gestión de inventario (sin eliminar)
- Creación de asignaciones
- Acceso a reportes básicos

**Permisos:**
```json
{
  "insumos": ["ver", "crear", "editar", "baja"],
  "asignaciones": ["ver", "crear", "devolver"],
  "reportes": ["ver"],
  "telecom": ["ver"],
  "sedes": ["ver"],
  "areas": ["ver"]
}
```

### 4. **Consultor** (id_rol: 4)
- Solo lectura
- Acceso a reportes

**Permisos:**
```json
{
  "insumos": ["ver"],
  "asignaciones": ["ver"],
  "reportes": ["ver", "exportar"],
  "telecom": ["ver"],
  "sedes": ["ver"],
  "areas": ["ver"]
}
```

## 🔍 Acciones a Auditar

### Módulo: Insumos
- `crear_insumo`: Guardar datos completos del insumo creado
- `editar_insumo`: Guardar estado antes/después
- `eliminar_insumo`: Guardar datos del insumo eliminado
- `baja_insumo`: Registrar motivo y cantidad
- `reponer_stock`: Cantidad movida de depósito a oficina
- `ingreso_insumo`: Registro de nuevos ingresos

### Módulo: Asignaciones
- `crear_asignacion`: Guardar remito completo
- `editar_asignacion`: Cambios en items del remito
- `anular_asignacion`: Motivo de anulación
- `devolver_insumos`: Items y cantidades devueltas
- `generar_remito_pdf`: Registrar generación de PDF

### Módulo: Usuarios
- `login`: Inicio de sesión (éxito/fallo)
- `logout`: Cierre de sesión
- `crear_usuario`: Datos del nuevo usuario
- `editar_usuario`: Cambios realizados
- `cambiar_rol`: Rol anterior y nuevo
- `desactivar_usuario`: Usuario desactivado

### Módulo: Reportes
- `exportar_excel`: Tipo de reporte exportado
- `imprimir_reporte`: Tipo de reporte impreso
- `ver_dashboard`: Acceso al dashboard

### Módulo: Telecom
- `editar_internet`: Cambios en instancias de internet
- `editar_telefonia`: Cambios en líneas telefónicas
- `editar_vigilancia`: Cambios en equipos de vigilancia

## 🔐 Seguridad Implementada

1. **Contraseñas**: Hash con `password_hash()` (bcrypt)
2. **Sesiones**: Token único por sesión
3. **CSRF**: Tokens en formularios (ya implementado)
4. **SQL Injection**: PDO con prepared statements (ya implementado)
5. **Timeouts**: Cierre automático de sesión por inactividad (30 minutos)
6. **IP Tracking**: Registro de IP en cada acción

## 📊 Funcionalidades a Crear

### 1. Sistema de Login
- `login.php`: Formulario de inicio de sesión
- `logout.php`: Cierre de sesión
- `auth_middleware.php`: Verificación de sesión en cada página

### 2. Gestión de Usuarios (Solo Admin/Super Admin)
- `pages/admin/usuarios/listar.php`: Lista de usuarios con DataTables
- `pages/admin/usuarios/crear.php`: Formulario para crear usuario
- `pages/admin/usuarios/editar.php`: Editar usuario existente
- `pages/admin/usuarios/cambiar_password.php`: Cambiar contraseña

### 3. Auditoría (Solo Super Admin)
- `pages/admin/auditoria/listar.php`: Historial completo de acciones
- Filtros: Usuario, Acción, Módulo, Fecha
- Vista detallada de cada acción con antes/después

### 4. Mi Perfil (Todos los usuarios)
- `pages/perfil.php`: Ver y editar datos propios
- `pages/cambiar_password.php`: Cambiar propia contraseña
- `pages/mis_sesiones.php`: Ver sesiones activas

## 🎯 Usuario Inicial

Script para crear el primer Super Administrador:
```sql
INSERT INTO usuarios (username, email, password_hash, nombre, apellido, id_rol, activo) 
VALUES (
  'admin',
  'admin@inventario.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "admin123"
  'Administrador',
  'Sistema',
  1,
  1
);
```

## 📝 Modificaciones en Código Existente

1. **includes/config.php**: Agregar funciones de autenticación
2. **includes/header.php**: Mostrar usuario logueado, botón logout
3. **Todos los archivos PHP**: Agregar verificación de sesión al inicio
4. **Todos los endpoints AJAX**: Registrar acciones en auditoría

## ⚙️ Configuración Adicional

### includes/auth.php (Nuevo)
```php
<?php
function verificarSesion() { /* ... */ }
function verificarPermiso($modulo, $accion) { /* ... */ }
function registrarAuditoria($accion, $modulo, $descripcion, $datos = []) { /* ... */ }
function cerrarSesionesInactivas() { /* ... */ }
```

---

## 🚀 Orden de Implementación

1. ✅ Crear script SQL de migración
2. ✅ Implementar sistema de autenticación básico (login/logout)
3. ✅ Crear middleware de verificación de sesiones
4. ✅ Implementar sistema de auditoría
5. ✅ Crear panel de gestión de usuarios
6. ✅ Integrar auditoría en todas las acciones existentes
7. ✅ Crear panel de visualización de auditoría
8. ✅ Testing completo del sistema

---

## 💭 Preguntas para Definir

1. **¿Quieres estos 4 roles o prefieres otros?**
2. **¿Necesitas algún permiso específico adicional?**
3. **¿Quieres bloqueo de cuenta después de X intentos fallidos?**
4. **¿Requieres doble factor de autenticación (2FA)?**
5. **¿Tiempo de expiración de sesión? (sugerido: 30 minutos)**
6. **¿Quieres que se cierren automáticamente otras sesiones al hacer login?**

---

¿Te parece bien esta propuesta o quieres ajustar algo antes de que comience la implementación?
