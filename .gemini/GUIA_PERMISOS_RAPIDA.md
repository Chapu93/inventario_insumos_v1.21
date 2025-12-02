# 🔐 Guía de Referencia Rápida - Permisos Implementados

## ⚡ Uso Rápido en Endpoints AJAX

### Patrón básico:
```php
<?php
require_once '../includes/config.php';

// Verificar autenticación
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// Verificar permisos específicos
if (!tienePermiso('modulo', 'accion')) {
    json_error('No tienes permisos para realizar esta acción', 403);
}

// Tu código aquí...
json_success(['data' => $resultado]);
?>
```

## ⚡ Uso en Páginas PHP

### Patrón básico:
```php
<?php
require_once '../../includes/config.php';

// Requerir autenticación
requerirAutenticacion();

// Verificar permisos específicos
verificarPermiso('modulo', 'accion');

// Si no tiene permisos, redirige automáticamente
// Tu código aquí...
?>
```

---

## 📚 Módulos y Acciones Disponibles

| Módulo | Acciones | Endpoints |
|--------|----------|-----------|
| **insumos** | ver, crear, editar, eliminar, baja | 11 AJAX + pages |
| **asignaciones** | ver, crear, editar, anular, devolver | 11 AJAX + pages |
| **reportes** | ver, exportar | 3 AJAX |
| **usuarios** | ver, crear, editar, eliminar, cambiar_rol | pages + 1 AJAX |
| **auditoria** | ver_todo | 2 AJAX |
| **telecomunicaciones** | ver, editar | 2 AJAX + 1 page |
| **sedes** | ver, crear, editar | pages + 1 AJAX |
| **areas** | ver, crear, editar | pages |

---

## 🔑 Funciones Principales

### `estaAutenticado()`
Verifica si hay una sesión activa.
```php
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}
```

### `tienePermiso($modulo, $accion)`
Verifica si el usuario tiene un permiso específico.
```php
if (!tienePermiso('insumos', 'ver')) {
    json_error('Sin permisos', 403);
}
```

### `verificarPermiso($modulo, $accion, $redirect = null)`
Verifica permiso y redirige si no lo tiene.
```php
verificarPermiso('insumos', 'ver');
// Si no tiene permiso: redirige a /index.php
```

### `obtenerUsuario()`
Retorna datos del usuario actual (incluyendo permisos).
```php
$usuario = obtenerUsuario();
echo $usuario['nombre_rol'];
```

### `tieneRol($nombre_rol)`
Verifica si el usuario tiene un rol específico.
```php
if (tieneRol('Super Administrador')) {
    // Solo para super admins
}
```

---

## 📍 Archivos Críticos

- **Auth:** `includes/auth.php` - Funciones de autenticación
- **Config:** `includes/config.php` - Configuración global
- **Header:** `includes/header.php` - Menú y validaciones de vista
- **Logger:** `includes/Logger.php` - Sistema de auditoría

---

## 🎯 Flujo de Autenticación

```
Usuario accede a endpoint
    ↓
¿Hay sesión activa? → NO → Error 401
    ↓ SI
¿Tiene permiso? → NO → Error 403
    ↓ SI
Ejecutar código
    ↓
Registrar en auditoría
```

---

## 🚨 Códigos de Error

| Código | Significado | Acción |
|--------|------------|--------|
| **401** | No autenticado | Ir a login |
| **403** | Sin permisos | Mostrar error de acceso |
| **500** | Error del servidor | Contactar soporte |

---

## 💡 Ejemplos Prácticos

### Endpoint para listar insumos:
```php
<?php
require_once '../includes/config.php';

if (!estaAutenticado()) json_error('No auth', 401);
if (!tienePermiso('insumos', 'ver')) json_error('No perms', 403);

$db = conectarDB();
$insumos = $db->query("SELECT * FROM insumos")->fetchAll();
json_success(['insumos' => $insumos]);
?>
```

### Página para crear asignación:
```php
<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('asignaciones', 'crear');

// Procesar formulario...
?>
```

---

## 🔄 Sistema de Permisos Personalizados

Cada usuario puede tener **permisos personalizados** que sobrescriben los del rol:

```php
// La función tienePermiso() automáticamente:
1. Revisa permisos_personalizados del usuario
2. Si existen, usa esos
3. Si no, usa los del rol
```

Esto permite:
- ✅ Roles restringidos (sin editar el rol)
- ✅ Permisos específicos por usuario
- ✅ Mayor flexibilidad

---

## 📊 Matriz de Permisos de Roles

Los roles estándar incluyen:

### Super Administrador
Todos los permisos en todos los módulos.

### Administrador
Gestión completa excepto auditoría y cambio de roles.

### Jefe de Área
Gestión de insumos y asignaciones de su área.

### Usuario Operativo
Solo lectura en reportes y datos necesarios.

---

## 🛠️ Troubleshooting

**Problema:** Recibir error 403 sin razón
```
Solución: 
1. Verificar que el usuario tiene el permiso en su rol
2. Verificar que no haya permisos_personalizados conflictivos
3. Revisar tabla 'roles' - columna 'permisos' (JSON)
```

**Problema:** Sesión expira frecuentemente
```
Solución:
1. Revisar config de sesión en includes/config.php
2. Aumentar SESSION_TIMEOUT si es necesario
3. Revisar logs en includes/logs/
```

**Problema:** Usuario no puede acceder aunque tiene rol correcto
```
Solución:
1. Revisar tabla usuarios - columna activo = 1
2. Verificar que el rol existe y tiene permisos
3. Ejecutar: SELECT * FROM usuarios WHERE id_usuario = X;
```

---

## 📞 Soporte

Para preguntas sobre permisos:
1. Revisar `.gemini/PERMISOS_APLICADOS_FINAL.md`
2. Consultar `includes/auth.php` - líneas 70-115
3. Ver logs en `logs/app.log`

---

**Última actualización:** 1 de Diciembre de 2025  
**Versión:** 1.0
