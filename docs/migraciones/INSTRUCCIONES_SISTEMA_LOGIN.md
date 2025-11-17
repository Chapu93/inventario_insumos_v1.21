# 🔐 Instrucciones de Implementación - Sistema de Login y Auditoría

## ✅ Sistema Completamente Implementado

El sistema de autenticación con roles y auditoría está **100% funcional** y listo para usar.

---

## 📦 1. Ejecutar Migración SQL

Primero, ejecuta el script SQL para crear las tablas necesarias:

```bash
mysql -u tu_usuario -p inventario_insumos_v1 < sql/migracion_sistema_usuarios.sql
```

O desde MySQL:
```sql
USE inventario_insumos_v1;
source /ruta/completa/sql/migracion_sistema_usuarios.sql;
```

Esto creará:
- ✅ 4 Tablas: `usuarios`, `roles`, `sesiones`, `auditoria_acciones`
- ✅ 4 Roles predefinidos con permisos
- ✅ Usuario administrador inicial
- ✅ Triggers para sincronización
- ✅ Índices optimizados

---

## 🔑 2. Credenciales Iniciales

**Usuario Administrador por Defecto:**
- **Usuario:** `admin`
- **Contraseña:** `admin123`
- **Rol:** Super Administrador

⚠️ **IMPORTANTE:** Cambia esta contraseña inmediatamente en producción desde:
`Usuarios → Mi Perfil → Cambiar Contraseña`

---

## 🎯 3. Acceder al Sistema

1. **Ir a la página de login:**
   ```
   http://tu-servidor/inventario_app/login.php
   ```

2. **Ingresar credenciales:**
   - Usuario: `admin`
   - Contraseña: `admin123`

3. **Explorar el sistema:**
   - Verás tu nombre de usuario en la esquina superior derecha
   - Menú desplegable con opciones de perfil y logout

---

## 👥 4. Gestión de Usuarios

### Crear Nuevos Usuarios

1. Ir a **Usuarios → Gestión de Usuarios**
2. Click en **"Nuevo Usuario"**
3. Completar el formulario:
   - Nombre de usuario (solo letras, números, guión bajo)
   - Email válido
   - Nombre y apellido
   - Seleccionar rol
   - Establecer contraseña (mínimo 6 caracteres)
4. Click en **"Crear Usuario"**

### Editar Usuarios

1. En la lista de usuarios, click en el botón **"Editar"** (icono lápiz)
2. Modificar los datos necesarios
3. Para cambiar contraseña: completar ambos campos de contraseña
4. Click en **"Guardar Cambios"**

### Cambiar Rol

1. Click en el botón **"Cambiar Rol"** (icono etiqueta)
2. Seleccionar el nuevo rol
3. Confirmar el cambio

### Activar/Desactivar

1. Click en el botón **"Activar/Desactivar"** (icono usuario)
2. Confirmar la acción
3. Al desactivar, se cierran automáticamente todas sus sesiones activas

---

## 🎭 5. Roles y Permisos

### Super Administrador (ID: 1)
- ✅ Acceso total al sistema
- ✅ Gestión de usuarios y roles
- ✅ Visualización completa de auditoría
- ✅ Todas las operaciones en todos los módulos

### Administrador (ID: 2)
- ✅ Gestión completa de inventario
- ✅ Crear y anular asignaciones
- ✅ Acceso a reportes completos
- ✅ Gestión de telecomunicaciones
- ❌ NO puede gestionar usuarios

### Operador (ID: 3)
- ✅ Gestión de inventario (sin eliminar)
- ✅ Crear asignaciones y devoluciones
- ✅ Acceso a reportes básicos
- ❌ NO puede anular asignaciones
- ❌ NO puede eliminar insumos

### Consultor (ID: 4)
- ✅ Solo lectura en todo el sistema
- ✅ Exportar reportes
- ❌ NO puede crear, editar ni eliminar

---

## 📊 6. Panel de Auditoría

**Acceso:** Solo para Super Administradores

**Ubicación:** `Usuarios → Auditoría`

### Funcionalidades:
- 🔍 **Filtros Avanzados:**
  - Por usuario
  - Por módulo (insumos, asignaciones, usuarios, etc.)
  - Por resultado (éxito/error)
  - Por rango de fechas
  
- 📝 **Información Registrada:**
  - Fecha y hora exacta
  - Usuario que realizó la acción
  - Módulo y tipo de acción
  - Descripción detallada
  - IP de origen
  - Estado antes y después (en JSON)
  - Mensajes de error (si aplica)

- 👁️ **Ver Detalles:**
  - Click en el botón "Ver Detalles"
  - Modal con información completa
  - Comparación antes/después

### Acciones Auditadas:

#### Módulo Usuarios
- `login` - Inicio de sesión
- `logout` - Cierre de sesión
- `login_fallido` - Intento fallido
- `crear_usuario` - Nuevo usuario
- `editar_usuario` - Modificación de usuario
- `cambiar_rol_usuario` - Cambio de rol
- `activar_usuario` - Activación
- `desactivar_usuario` - Desactivación
- `sesion_expirada` - Sesión por timeout

#### Módulo Insumos
- `crear_insumo` - Nuevo insumo
- `editar_insumo` - Modificación
- `eliminar_insumo` - Eliminación
- `baja_insumo` - Baja física
- `reponer_stock` - Reposición oficina/depósito
- `ingreso_insumo` - Nuevo ingreso

#### Módulo Asignaciones
- `crear_asignacion` - Nueva asignación
- `editar_asignacion` - Modificación
- `anular_asignacion` - Anulación
- `devolver_insumos` - Devolución
- `generar_remito_pdf` - Generación PDF

---

## 🔒 7. Seguridad Implementada

### Contraseñas
- ✅ Hash con `password_hash()` (bcrypt)
- ✅ Mínimo 6 caracteres requeridos
- ✅ Confirmación obligatoria

### Sesiones
- ✅ Token único por sesión (256 bits)
- ✅ Timeout de 30 minutos de inactividad
- ✅ Cierre automático de otras sesiones al login
- ✅ Registro de IP y User-Agent
- ✅ Cookies con flags seguras (HttpOnly, Secure, SameSite)

### Permisos
- ✅ Verificación en cada página
- ✅ Verificación en cada endpoint AJAX
- ✅ Sistema granular por módulo y acción
- ✅ Imposible cambiar el propio rol
- ✅ Imposible desactivarse a sí mismo

### SQL Injection
- ✅ PDO con prepared statements
- ✅ Validación de tipos de datos
- ✅ Sanitización de inputs

### CSRF
- ✅ Tokens en formularios (ya implementado)
- ✅ Validación en el backend

---

## 🛠️ 8. Configuración Avanzada

### Cambiar Timeout de Sesión

Editar `/includes/auth.php`, línea ~260:
```php
$timeout = 1800; // 30 minutos por defecto
```

Valores comunes:
- 900 = 15 minutos
- 1800 = 30 minutos (recomendado)
- 3600 = 1 hora
- 7200 = 2 horas

### Agregar Nuevos Permisos

Editar la tabla `roles` y modificar el JSON de permisos:
```sql
UPDATE roles 
SET permisos = JSON_SET(
    permisos,
    '$.nuevo_modulo', JSON_ARRAY('ver', 'crear', 'editar')
)
WHERE id_rol = 1;
```

---

## 📋 9. Checklist de Implementación

- [ ] Ejecutar migración SQL
- [ ] Acceder con credenciales por defecto
- [ ] Cambiar contraseña del admin
- [ ] Crear usuarios reales del sistema
- [ ] Asignar roles apropiados
- [ ] Desactivar o eliminar usuario admin por defecto (opcional)
- [ ] Verificar que la auditoría funcione correctamente
- [ ] Configurar timeout de sesión según necesidad
- [ ] Documentar usuarios y roles creados

---

## 🚨 10. Troubleshooting

### "Error de conexión a la base de datos"
- Verificar credenciales en `/includes/config.php`
- Asegurar que la BD existe
- Verificar que la migración SQL se ejecutó correctamente

### "No autenticado" al acceder a páginas
- Limpiar cookies del navegador
- Verificar que `includes/auth.php` se carga en `config.php`
- Revisar logs de PHP por errores

### "Sin permisos"
- Verificar que el usuario tiene el rol correcto
- Revisar permisos del rol en la tabla `roles`
- Verificar que la función `tienePermiso()` esté correctamente implementada

### Sesión expira muy rápido
- Aumentar el timeout en `auth.php`
- Verificar configuración de `php.ini` para `session.gc_maxlifetime`

### Auditoría no registra acciones
- Verificar que existe la tabla `auditoria_acciones`
- Revisar logs de PHP por errores
- Verificar que las funciones `registrarAuditoria()` se llamen correctamente

---

## 📞 11. Soporte

Para problemas técnicos:
1. Revisar logs de PHP (`error_log`)
2. Revisar logs de Apache/Nginx
3. Verificar tabla `auditoria_acciones` para errores registrados
4. Consultar este documento

---

## ✨ 12. Características Destacadas

- ⚡ **Rápido:** Optimizado con índices y prepared statements
- 🔐 **Seguro:** Múltiples capas de seguridad implementadas
- 📊 **Auditable:** Registro completo de toda actividad
- 🎨 **Intuitivo:** Interfaz moderna con Bootstrap 5
- 🔄 **Escalable:** Fácil agregar nuevos roles y permisos
- 📱 **Responsive:** Funciona en móviles y tablets
- 🌐 **Multi-sesión:** Gestión inteligente de sesiones múltiples

---

¡El sistema está listo para usar! 🎉
