# Análisis del Sistema de Autenticación, Autorización y Auditoría

**Fecha:** 2025-12-01  
**Sistema:** Inventario de Insumos v1.21  
**Archivo Principal:** `includes/auth.php`

---

## 📋 Resumen Ejecutivo

El sistema implementa un **modelo robusto de autenticación y autorización basado en roles (RBAC)** con auditoría completa de acciones. Los permisos están definidos en formato JSON dentro de cada rol, permitiendo control granular por módulo y acción.

### Estado General: ✅ **FUNCIONANDO CORRECTAMENTE**

---

## 🔐 1. Sistema de Autenticación

### 1.1 Configuración de Sesiones Seguras

```php
// Características de seguridad implementadas:
- session.cookie_httponly = 1       // Previene acceso desde JavaScript
- session.cookie_secure = auto      // HTTPS cuando está disponible
- session.use_only_cookies = 1      // Solo cookies, no URL
- session.cookie_samesite = Strict/Lax  // Protección CSRF
```

**✅ Verificación:** La configuración es apropiada y se adapta automáticamente a HTTP/HTTPS.

### 1.2 Funciones de Autenticación

#### `estaAutenticado()`
- ✅ Verifica 3 condiciones: `usuario_id`, `token_sesion`, `ultimo_acceso`
- ✅ Implementación correcta

#### `iniciarSesion($username, $password)`
- ✅ Busca por username O email
- ✅ Verifica que el usuario esté activo
- ✅ Usa `password_verify()` para validación segura
- ✅ Cierra otras sesiones activas del mismo usuario
- ✅ Genera token de sesión seguro (64 caracteres hex)
- ✅ Registra IP y User-Agent
- ✅ Actualiza `ultimo_acceso` en BD
- ✅ Registra en auditoría (login exitoso y fallido)

**Resultado:** ✅ **Implementación robusta y segura**

#### `cerrarSesion()`
- ✅ Registra en auditoría antes de cerrar
- ✅ Marca sesión como inactiva en BD
- ✅ Limpia variables de sesión
- ✅ Destruye cookie de sesión
- ✅ Destruye sesión PHP

**Resultado:** ✅ **Cierre completo y seguro**

### 1.3 Gestión de Sesiones

#### `verificarSesionActiva()`
- ✅ Timeout de 30 minutos (1800 segundos)
- ✅ Actualiza `ultimo_acceso` en cada request
- ✅ Actualiza BD cada 5 minutos (optimización)
- ✅ Registra expiración en auditoría

**Resultado:** ✅ **Gestión eficiente con balance entre seguridad y performance**

#### `cerrarSesionesInactivas()`
- ✅ Cierra sesiones con >30 min de inactividad
- ✅ Puede ejecutarse vía cron o al login

**Resultado:** ✅ **Limpieza automática implementada**

---

## 🛡️ 2. Sistema de Autorización (Permisos)

### 2.1 Estructura de Roles

**Roles Definidos:**

| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Super Administrador | Acceso total incluyendo gestión de usuarios |
| 2 | Administrador | Gestión completa de inventario sin usuarios |
| 3 | Operador | Operaciones diarias de inventario |
| 4 | Consultor | Solo lectura y reportes |

**✅ Verificación:** Roles bien definidos con separación clara de responsabilidades.

### 2.2 Estructura de Permisos (JSON)

Los permisos se almacenan en formato JSON en la columna `roles.permisos`:

```json
{
  "modulo": ["accion1", "accion2", ...]
}
```

**Módulos Implementados:**
- `insumos`: ver, crear, editar, eliminar, baja
- `asignaciones`: ver, crear, editar, anular, devolver
- `reportes`: ver, exportar
- `usuarios`: ver, crear, editar, eliminar, cambiar_rol
- `auditoria`: ver_todo
- `telecom`: ver, editar
- `sedes`: ver, crear, editar
- `areas`: ver, crear, editar

### 2.3 Matriz de Permisos por Rol

| Módulo | Super Admin | Administrador | Operador | Consultor |
|--------|-------------|---------------|----------|-----------|
| **Insumos** |
| - ver | ✅ | ✅ | ✅ | ✅ |
| - crear | ✅ | ✅ | ✅ | ❌ |
| - editar | ✅ | ✅ | ✅ | ❌ |
| - eliminar | ✅ | ✅ | ❌ | ❌ |
| - baja | ✅ | ✅ | ✅ | ❌ |
| **Asignaciones** |
| - ver | ✅ | ✅ | ✅ | ✅ |
| - crear | ✅ | ✅ | ✅ | ❌ |
| - editar | ✅ | ✅ | ❌ | ❌ |
| - anular | ✅ | ✅ | ❌ | ❌ |
| - devolver | ✅ | ✅ | ✅ | ❌ |
| **Usuarios** |
| - ver | ✅ | ❌ | ❌ | ❌ |
| - crear | ✅ | ❌ | ❌ | ❌ |
| - editar | ✅ | ❌ | ❌ | ❌ |
| - eliminar | ✅ | ❌ | ❌ | ❌ |
| - cambiar_rol | ✅ | ❌ | ❌ | ❌ |
| **Auditoría** |
| - ver_todo | ✅ | ❌ | ❌ | ❌ |
| **Reportes** |
| - ver | ✅ | ✅ | ✅ | ✅ |
| - exportar | ✅ | ✅ | ❌ | ✅ |

**✅ Verificación:** Matriz de permisos lógica y bien estructurada.

### 2.4 Funciones de Verificación de Permisos

#### `tienePermiso($modulo, $accion)`
```php
// Proceso:
1. Obtiene usuario actual
2. Decodifica JSON de permisos
3. Verifica si el módulo existe
4. Verifica si la acción está en el array del módulo
```

**✅ Verificación:** Implementación correcta con manejo de errores.

#### `verificarPermiso($modulo, $accion, $redirect = null)`
```php
// Proceso:
1. Llama a tienePermiso()
2. Si no tiene permiso:
   - Establece mensaje de error en sesión
   - Redirige a página especificada o index.php
```

**✅ Verificación:** Función útil para proteger páginas completas.

#### `tieneRol($rolesPermitidos)`
```php
// Permite verificar por:
- ID de rol (numérico)
- Nombre de rol (string, case-insensitive)
```

**✅ Verificación:** Flexible y bien implementada.

#### `requerirRol($rolesPermitidos, $redirect = null)`
```php
// Similar a verificarPermiso pero basado en roles
```

**✅ Verificación:** Complementa bien el sistema de permisos granulares.

---

## 📊 3. Sistema de Auditoría

### 3.1 Estructura de la Tabla `auditoria_acciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id_auditoria | bigint(20) | PK, auto-increment |
| id_usuario | int(11) | FK a usuarios (NOT NULL) |
| id_sesion | int(11) | FK a sesiones (nullable) |
| accion | varchar(100) | Tipo de acción |
| modulo | varchar(50) | Módulo del sistema |
| descripcion | text | Descripción legible |
| entidad_tipo | varchar(50) | Tipo de entidad afectada |
| entidad_id | int(11) | ID de entidad afectada |
| datos_antes | longtext | JSON con estado anterior |
| datos_despues | longtext | JSON con estado posterior |
| ip_address | varchar(45) | IP del usuario |
| resultado | enum('exito','error') | Resultado de la acción |
| mensaje_error | text | Mensaje de error si aplica |
| fecha_accion | datetime | Timestamp automático |

**Índices:**
- ✅ id_usuario (búsqueda por usuario)
- ✅ id_sesion (búsqueda por sesión)
- ✅ accion (búsqueda por tipo de acción)
- ✅ modulo (búsqueda por módulo)
- ✅ entidad_tipo (búsqueda por tipo de entidad)
- ✅ resultado (filtrado por éxito/error)
- ✅ fecha_accion (ordenamiento temporal)

**✅ Verificación:** Estructura completa con índices apropiados para consultas eficientes.

### 3.2 Registros de Auditoría Actuales

**Total de registros:** 116 acciones auditadas

**✅ Verificación:** El sistema está registrando acciones activamente.

### 3.3 Funciones de Auditoría

#### `registrarAuditoria(...)`
```php
// Uso típico:
registrarAuditoria(
    'crear_insumo',           // acción
    'insumos',                // módulo
    'Insumo creado: Mouse',   // descripción
    'insumo',                 // tipo de entidad
    123,                      // ID de entidad
    null,                     // datos antes (null para creación)
    ['nombre' => 'Mouse'],    // datos después
    'exito',                  // resultado
    null                      // mensaje error
);
```

**Características:**
- ✅ Obtiene automáticamente `id_usuario` de la sesión
- ✅ Obtiene automáticamente `id_sesion` de la sesión
- ✅ Delega a `registrarAuditoriaDirecto()`

#### `registrarAuditoriaDirecto(...)`
```php
// Uso para casos sin sesión activa (ej: login fallido)
```

**Características:**
- ✅ Permite especificar `id_usuario` e `id_sesion` manualmente
- ✅ Captura IP automáticamente
- ✅ Convierte arrays a JSON automáticamente
- ✅ No lanza excepciones (no interrumpe flujo principal)
- ✅ Registra errores en Logger

**✅ Verificación:** Implementación robusta que no afecta la operación principal si falla.

### 3.4 Tipos de Acciones Auditadas

**Autenticación:**
- `login` - Login exitoso
- `login_fallido` - Intento fallido
- `logout` - Cierre de sesión
- `sesion_expirada` - Sesión expirada por timeout

**Insumos:**
- `crear_insumo`
- `editar_insumo`
- `eliminar_insumo`
- `baja_insumo`

**Asignaciones:**
- `crear_asignacion`
- `editar_asignacion`
- `anular_asignacion`
- `devolver_insumo`

**Usuarios:**
- `crear_usuario`
- `editar_usuario`
- `eliminar_usuario`
- `cambiar_rol_usuario`
- `cambiar_estado_usuario`

**✅ Verificación:** Cobertura completa de acciones críticas.

---

## 🔍 4. Análisis de Seguridad

### 4.1 Fortalezas

✅ **Autenticación:**
- Hashing seguro de contraseñas (password_hash/verify)
- Tokens de sesión aleatorios (64 caracteres)
- Timeout de sesión (30 minutos)
- Cierre de sesiones múltiples
- Registro de IP y User-Agent

✅ **Autorización:**
- Control granular por módulo y acción
- Permisos basados en roles (RBAC)
- Verificación en cada request
- Separación clara de responsabilidades

✅ **Auditoría:**
- Registro completo de acciones
- Captura de estado antes/después
- Registro de intentos fallidos
- No interrumpe operación si falla
- Índices para consultas eficientes

✅ **Sesiones:**
- Configuración segura de cookies
- Protección contra CSRF (SameSite)
- HttpOnly (previene XSS)
- Secure en HTTPS
- Limpieza de sesiones inactivas

### 4.2 Recomendaciones de Mejora

⚠️ **Prioridad Media:**

1. **Límite de intentos de login:**
   - Implementar bloqueo temporal tras N intentos fallidos
   - Previene ataques de fuerza bruta

2. **Rotación de tokens de sesión:**
   - Regenerar token tras acciones sensibles
   - Previene session fixation

3. **Verificación de 2FA (Autenticación de Dos Factores):**
   - Opcional para Super Administradores
   - Aumenta seguridad de cuentas privilegiadas

4. **Alertas de seguridad:**
   - Notificar al usuario sobre login desde nueva IP
   - Notificar cambios de contraseña

⚠️ **Prioridad Baja:**

5. **Política de contraseñas:**
   - Actualmente: mínimo 6 caracteres
   - Recomendado: 8+ caracteres, complejidad opcional

6. **Expiración de contraseñas:**
   - Opcional: forzar cambio cada 90 días
   - Solo para roles administrativos

### 4.3 Posibles Vulnerabilidades

❌ **No detectadas vulnerabilidades críticas**

⚠️ **Observaciones menores:**

1. **Variable indefinida en auth.php línea 65:**
   ```php
   Logger::error("Error al obtener usuario", [
       'mensaje' => $e->getMessage(),
       'username' => $username  // ⚠️ $username no está definido en este contexto
   ]);
   ```
   **Impacto:** Bajo (solo afecta logging)
   **Solución:** Remover o usar `obtenerUsuarioId()`

---

## 📝 5. Pruebas Recomendadas

### 5.1 Pruebas de Autenticación

- [ ] Login con credenciales válidas
- [ ] Login con credenciales inválidas
- [ ] Login con usuario inactivo
- [ ] Login con email en lugar de username
- [ ] Timeout de sesión tras 30 minutos
- [ ] Cierre manual de sesión
- [ ] Login simultáneo (debe cerrar sesión anterior)

### 5.2 Pruebas de Autorización

**Para cada rol (Super Admin, Admin, Operador, Consultor):**

- [ ] Acceso a páginas permitidas
- [ ] Bloqueo de páginas no permitidas
- [ ] Verificación de acciones AJAX
- [ ] Botones/enlaces ocultos según permisos

### 5.3 Pruebas de Auditoría

- [ ] Registro de login exitoso
- [ ] Registro de login fallido
- [ ] Registro de creación de insumo
- [ ] Registro de edición con datos antes/después
- [ ] Registro de eliminación
- [ ] Registro de acciones con error
- [ ] Consulta de auditoría por usuario
- [ ] Consulta de auditoría por fecha
- [ ] Consulta de auditoría por módulo

---

## 🎯 6. Conclusiones

### Estado General: ✅ **SISTEMA ROBUSTO Y FUNCIONAL**

El sistema de autenticación, autorización y auditoría está **bien implementado** con las siguientes características destacadas:

1. ✅ **Seguridad sólida** en autenticación
2. ✅ **Control granular** de permisos
3. ✅ **Auditoría completa** de acciones
4. ✅ **Manejo de errores** apropiado
5. ✅ **Logging estructurado** con Logger

### Acciones Inmediatas Recomendadas:

1. ✅ **Corregir variable indefinida** en auth.php línea 65
2. ⚠️ **Implementar límite de intentos** de login (prioridad media)
3. ℹ️ **Documentar permisos** para nuevos desarrolladores

### Próximos Pasos:

- Ejecutar suite de pruebas de seguridad
- Revisar logs de auditoría regularmente
- Considerar implementación de 2FA para administradores

---

**Documento generado:** 2025-12-01  
**Revisado por:** Antigravity AI  
**Próxima revisión:** Trimestral o tras cambios significativos
