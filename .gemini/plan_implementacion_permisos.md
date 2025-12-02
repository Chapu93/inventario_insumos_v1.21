# Plan de Implementación de Permisos

## Problema Identificado
Los permisos están definidos en la base de datos pero **NO se están aplicando** en las páginas y endpoints AJAX. Solo se verifica autenticación pero no autorización.

## Archivos que NECESITAN verificación de permisos

### 📁 Páginas de Insumos
- [ ] `pages/insumos/listar.php` - Requiere: `insumos`, `ver`
- [ ] `pages/insumos/agregar.php` - Requiere: `insumos`, `crear`
- [ ] `pages/insumos/editar.php` - Requiere: `insumos`, `editar`
- [ ] `pages/insumos/agregar_nueva.php` - Requiere: `insumos`, `crear`
- [ ] `pages/insumos/ingresos_editar.php` - Requiere: `insumos`, `crear`

### 📁 Páginas de Asignaciones
- [ ] `pages/asignaciones/listar.php` - Requiere: `asignaciones`, `ver`
- [ ] `pages/asignaciones/crear.php` - Requiere: `asignaciones`, `crear`
- [ ] `pages/asignaciones/cambiar_estado.php` - Requiere: `asignaciones`, `editar`

### 📁 Páginas de Reportes
- [ ] `pages/reportes/*` - Requiere: `reportes`, `ver`

### 📁 Páginas Admin (Sedes, Areas, Telecom)
- [ ] `pages/admin/sedes.php` - Requiere: `sedes`, `ver`
- [ ] `pages/admin/telecom_*.php` - Requiere: `telecom`, `ver`

### 🔌 Endpoints AJAX Críticos
- [ ] `ajax/insumos_eliminar.php` - Requiere: `insumos`, `eliminar`
- [ ] `ajax/insumo_baja.php` - Requiere: `insumos`, `baja`
- [ ] `ajax/asignacion_eliminar.php` - Requiere: `asignaciones`, `anular`
- [ ] `ajax/devolver_insumos.php` - Requiere: `asignaciones`, `devolver`

## Solución Propuesta

### 1. Aplicar verificaciones de permisos (INMEDIATO)
Agregar `verificarPermiso()` en cada archivo según su función.

### 2. Crear interfaz de gestión de permisos (NUEVO)
- Página: `pages/admin/usuarios/permisos.php`
- Permite al Super Admin editar permisos individuales por usuario
- Interfaz gráfica con checkboxes por módulo/acción

### 3. Modificar estructura de permisos
- Agregar columna `permisos_personalizados` en tabla `usuarios` (JSON, nullable)
- Si es NULL, usar permisos del rol
- Si tiene valor, usar permisos personalizados
- Modificar función `tienePermiso()` para verificar primero permisos personalizados

## Implementación

### Fase 1: Aplicar Verificaciones (30 min)
1. Agregar `verificarPermiso()` en páginas principales
2. Agregar verificación en endpoints AJAX críticos
3. Ocultar botones según permisos en UI

### Fase 2: Sistema de Permisos Personalizados (60 min)
1. Migración de BD: agregar columna `permisos_personalizados`
2. Modificar `tienePermiso()` en `auth.php`
3. Crear interfaz de gestión
4. Crear endpoint AJAX para guardar permisos

### Fase 3: Testing (20 min)
1. Probar con usuario Consultor
2. Probar con usuario Operador
3. Verificar que Super Admin puede gestionar permisos
