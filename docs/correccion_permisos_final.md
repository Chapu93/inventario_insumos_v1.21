# CORRECCIÓN DE PERMISOS - 4 ROLES EXISTENTES

## 📋 RESUMEN DE CAMBIOS

### ✅ Cambios Aplicados:
1. ✓ Agregada acción "crear" al módulo telecomunicaciones
2. ✓ Script SQL actualizado para 4 roles únicamente
3. ✓ Permisos definidos claramente para cada rol

---

## 🎯 CONFIGURACIÓN DE PERMISOS POR ROL

### 🔴 ROL 1: ADMINISTRADOR
**Descripción**: Acceso total al sistema

| Módulo | Permisos |
|--------|----------|
| Insumos | ver, crear, editar, eliminar, baja |
| Asignaciones | ver, crear, editar, anular, devolver |
| Sedes | ver, crear, editar, eliminar |
| **Telecomunicaciones** | **ver, crear, editar, eliminar** |
| Reportes | ver, exportar |
| Usuarios | ver, crear, editar, eliminar, cambiar_rol, reset_password |
| Auditoría | ver_todo |
| Sistema | backup |
| Áreas | ver, crear, editar, eliminar |

**Puede hacer**: TODO en el sistema

---

### 🟠 ROL 2: GESTOR
**Descripción**: Gestión operativa sin administración de usuarios

| Módulo | Permisos |
|--------|----------|
| Insumos | ver, crear, editar, baja |
| Asignaciones | ver, crear, editar, devolver |
| Sedes | ver, crear, editar |
| **Telecomunicaciones** | **ver, crear, editar** |
| Reportes | ver, exportar |
| Usuarios | ver (solo consulta) |
| Áreas | ver, crear, editar |

**Puede hacer**: Gestionar operaciones diarias, NO puede eliminar ni administrar usuarios

---

### 🟡 ROL 3: OPERADOR
**Descripción**: Operaciones diarias y asignaciones

| Módulo | Permisos |
|--------|----------|
| Insumos | ver, crear, editar |
| Asignaciones | ver, crear, editar, devolver |
| Sedes | ver (solo consulta) |
| **Telecomunicaciones** | **ver, crear** |
| Reportes | ver, exportar |
| Áreas | ver (solo consulta) |

**Puede hacer**: Gestionar asignaciones y crear elementos, NO puede eliminar

---

### 🔵 ROL 4: USUARIO/CONSULTOR
**Descripción**: Consulta y asignaciones básicas

| Módulo | Permisos |
|--------|----------|
| Insumos | ver (solo consulta) |
| Asignaciones | ver, crear |
| Sedes | ver (solo consulta) |
| Telecomunicaciones | ver (solo consulta) |
| Reportes | ver (solo consulta) |
| Áreas | ver (solo consulta) |

**Puede hacer**: Ver información y crear asignaciones, NO puede modificar ni eliminar

---

## 🔧 INSTRUCCIONES DE APLICACIÓN

### Paso 1: Ejecutar el script SQL
```bash
# Opción A: Desde línea de comandos
mysql -u root -p inventario_insumos < scripts/corregir_permisos.sql

# Opción B: Desde phpMyAdmin
# 1. Abrir phpMyAdmin
# 2. Seleccionar base de datos 'inventario_insumos'
# 3. Ir a pestaña SQL
# 4. Copiar y pegar el contenido de scripts/corregir_permisos.sql
# 5. Ejecutar
```

### Paso 2: Verificar cambios
1. Acceder a: `http://localhost/inventario_app/test_permisos.php`
2. Revisar la matriz de permisos
3. Confirmar que todos los roles tienen permisos definidos

### Paso 3: Probar con diferentes usuarios
1. Cerrar sesión actual
2. Iniciar sesión con usuario de cada rol
3. Verificar que los botones se muestren/oculten correctamente:
   - **Administrador**: Ve TODOS los botones
   - **Gestor**: Ve crear/editar, NO ve eliminar en usuarios/sistema
   - **Operador**: Ve crear/editar en asignaciones, NO ve eliminar
   - **Usuario**: Solo ve botones de consulta y crear asignaciones

---

## 📊 MATRIZ DE BOTONES ESPERADOS

### Página: Insumos
| Botón | Admin | Gestor | Operador | Usuario |
|-------|-------|--------|----------|---------|
| Ver | ✓ | ✓ | ✓ | ✓ |
| Crear | ✓ | ✓ | ✓ | ✗ |
| Editar | ✓ | ✓ | ✓ | ✗ |
| Eliminar | ✓ | ✗ | ✗ | ✗ |
| Dar de Baja | ✓ | ✓ | ✗ | ✗ |

### Página: Asignaciones
| Botón | Admin | Gestor | Operador | Usuario |
|-------|-------|--------|----------|---------|
| Ver | ✓ | ✓ | ✓ | ✓ |
| Crear | ✓ | ✓ | ✓ | ✓ |
| Editar | ✓ | ✓ | ✓ | ✗ |
| Anular | ✓ | ✓ | ✗ | ✗ |
| Devolver | ✓ | ✓ | ✓ | ✗ |

### Página: Telecomunicaciones
| Botón | Admin | Gestor | Operador | Usuario |
|-------|-------|--------|----------|---------|
| Ver | ✓ | ✓ | ✓ | ✓ |
| **Crear** | **✓** | **✓** | **✓** | **✗** |
| Editar | ✓ | ✓ | ✗ | ✗ |
| Eliminar | ✓ | ✗ | ✗ | ✗ |

### Página: Sedes
| Botón | Admin | Gestor | Operador | Usuario |
|-------|-------|--------|----------|---------|
| Ver | ✓ | ✓ | ✓ | ✓ |
| Crear | ✓ | ✓ | ✗ | ✗ |
| Editar | ✓ | ✓ | ✗ | ✗ |
| Eliminar | ✓ | ✗ | ✗ | ✗ |

### Página: Usuarios
| Botón | Admin | Gestor | Operador | Usuario |
|-------|-------|--------|----------|---------|
| Ver | ✓ | ✓ | ✗ | ✗ |
| Crear | ✓ | ✗ | ✗ | ✗ |
| Editar | ✓ | ✗ | ✗ | ✗ |
| Eliminar | ✓ | ✗ | ✗ | ✗ |
| Reset Password | ✓ | ✗ | ✗ | ✗ |

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de aplicar los cambios, verificar:

- [ ] Script SQL ejecutado sin errores
- [ ] Todos los roles tienen permisos en formato JSON válido
- [ ] La página test_permisos.php muestra la matriz correctamente
- [ ] Botón "Crear" aparece en telecomunicaciones para Admin/Gestor/Operador
- [ ] Botones de eliminar solo aparecen para Administrador
- [ ] Usuario puede crear asignaciones pero no modificar insumos
- [ ] Gestor puede editar pero no eliminar
- [ ] Operador puede crear asignaciones y elementos de telecom

---

## 🚨 PROBLEMAS CONOCIDOS Y SOLUCIONES

### Problema: Los botones siguen apareciendo para todos
**Solución**: Verificar que los archivos PHP usen correctamente:
```php
<?php if (tienePermiso('modulo', 'accion')): ?>
    <button>Acción</button>
<?php endif; ?>
```

### Problema: Permisos no se actualizan
**Solución**: 
1. Cerrar sesión completamente
2. Limpiar caché del navegador (Ctrl+Shift+Del)
3. Volver a iniciar sesión

### Problema: Error al ejecutar SQL
**Solución**: Verificar que los nombres de roles coincidan exactamente con la base de datos

---

## 📞 SOPORTE

Si después de aplicar estos cambios los permisos siguen sin funcionar:
1. Revisar logs de PHP en `/opt/lampp/logs/php_error_log`
2. Verificar que la función `tienePermiso()` esté siendo llamada
3. Comprobar que no haya permisos personalizados sobrescribiendo los del rol

---

**Última actualización**: 2025-12-09  
**Versión**: 1.0 - Corrección para 4 roles con telecomunicaciones
