# 🔐 Acceso al Sistema SITIA

## ⚠️ IMPORTANTE: Ejecutar Script de Migración Primero

Antes de poder hacer login, **DEBES ejecutar el script SQL de migración** para crear las tablas de usuarios y roles.

---

## 📋 Paso 1: Ejecutar Migración SQL

### Opción A: Desde línea de comandos

```bash
mysql -u tu_usuario -p inventario_insumos_v1 < sql/migracion_sistema_usuarios.sql
```

### Opción B: Desde MySQL Workbench / phpMyAdmin

1. Abre el archivo `sql/migracion_sistema_usuarios.sql`
2. Copia todo el contenido
3. Pégalo en la ventana de consultas SQL
4. Ejecuta el script completo

### Opción C: Desde consola MySQL

```bash
mysql -u tu_usuario -p
```

Luego dentro de MySQL:
```sql
USE inventario_insumos_v1;
source /ruta/completa/al/proyecto/sql/migracion_sistema_usuarios.sql;
```

---

## 🔍 Paso 2: Verificar Instalación (OPCIONAL)

Puedes ejecutar el script de verificación para asegurarte que todo esté correcto:

```bash
php verificar_y_crear_admin.php
```

Este script:
- ✅ Verifica que existan las tablas
- ✅ Verifica que existan los roles
- ✅ Verifica/crea el usuario admin
- ✅ Actualiza la contraseña del admin

---

## 🔑 Paso 3: Credenciales de Acceso

Una vez ejecutada la migración:

### **Usuario Administrador:**
- **Usuario:** `admin`
- **Contraseña:** `admin123`

---

## 🌐 Paso 4: Acceder al Sistema

1. **Ir a la URL:**
   ```
   http://tu-servidor/inventario_app/login.php
   ```

2. **Ingresar credenciales:**
   - Usuario: `admin`
   - Contraseña: `admin123`

3. **¡Listo!** Serás redirigido al dashboard.

---

## ❓ Solución de Problemas

### Problema: "Usuario o contraseña incorrectos"

**Causas posibles:**
1. ❌ No ejecutaste el script de migración
2. ❌ La tabla `usuarios` no existe
3. ❌ El usuario `admin` no fue creado

**Solución:**
```bash
# Ejecutar script de verificación
php verificar_y_crear_admin.php
```

---

### Problema: "Table 'usuarios' doesn't exist"

**Solución:**
```bash
# Ejecutar migración completa
mysql -u tu_usuario -p inventario_insumos_v1 < sql/migracion_sistema_usuarios.sql
```

---

### Problema: Login funciona pero no muestra permisos

**Causa:** Los roles no se crearon correctamente.

**Solución:**
```sql
-- Verificar roles
SELECT * FROM roles;

-- Si no hay roles, ejecuta la migración completa nuevamente
```

---

## 🔒 Seguridad en Producción

⚠️ **CRÍTICO:** Cambia la contraseña del usuario `admin` inmediatamente en producción:

1. Login con `admin` / `admin123`
2. Ir a **Mi Perfil** (dropdown en la esquina superior derecha)
3. Click en **Editar Perfil**
4. Completar los campos de nueva contraseña
5. Guardar cambios

---

## 📊 Verificar que Todo Funcione

Después del login exitoso deberías ver:

✅ Tu nombre de usuario en la esquina superior derecha
✅ Menú desplegable con:
   - Mi Perfil
   - Gestión de Usuarios
   - Auditoría
   - Cerrar Sesión
✅ Sidebar con todas las opciones del sistema
✅ Dashboard con estadísticas

---

## 🆘 ¿Aún no funciona?

Ejecuta estos comandos para diagnóstico:

```sql
-- Verificar que la tabla existe
USE inventario_insumos_v1;
SHOW TABLES LIKE 'usuarios';

-- Verificar usuario admin
SELECT id_usuario, username, email, activo, id_rol 
FROM usuarios 
WHERE username = 'admin';

-- Verificar roles
SELECT * FROM roles;
```

Si alguna de estas consultas falla, **ejecuta la migración SQL nuevamente**.

---

## ✅ Resumen Rápido

```bash
# 1. Ejecutar migración
mysql -u usuario -p inventario_insumos_v1 < sql/migracion_sistema_usuarios.sql

# 2. (Opcional) Verificar
php verificar_y_crear_admin.php

# 3. Acceder
# URL: http://servidor/inventario_app/login.php
# Usuario: admin
# Password: admin123
```

---

**¡Eso es todo!** Si seguiste estos pasos, el sistema debería funcionar correctamente. 🎉
