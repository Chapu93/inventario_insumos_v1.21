# ACLARACIÓN: PLANOS E INGRESOS EN EL SISTEMA DE PERMISOS

## ✅ CONFIRMACIÓN: SÍ ESTÁN CONTEMPLADOS

### 📋 INGRESOS
**Ubicación**: `/pages/insumos/ingresos_listar.php`  
**Módulo de permisos**: `insumos`

Los ingresos utilizan los permisos del módulo **insumos**:
- `insumos.ver` → Ver listado de ingresos
- `insumos.crear` → Crear nuevo ingreso
- `insumos.editar` → Editar ingreso existente
- `insumos.eliminar` → Eliminar ingreso

**Código de verificación** (líneas 5, 12, 124-127):
```php
verificarPermiso('insumos', 'ver');
<?php if (tienePermiso('insumos', 'crear')): ?>
<?php if (tienePermiso('insumos', 'editar')): ?>
<?php if (tienePermiso('insumos', 'eliminar')): ?>
```

---

### 📋 PLANOS
**Ubicación**: `/pages/admin/telecom_planos.php`  
**Módulo de permisos**: `telecom`

Los planos utilizan los permisos del módulo **telecomunicaciones**:
- `telecom.ver` → Ver listado de planos
- `telecom.crear` → Subir nuevo plano
- `telecom.editar` → Editar información del plano
- `telecom.eliminar` → Eliminar plano

---

## 📊 MATRIZ DE PERMISOS PARA INGRESOS Y PLANOS

### Ingresos (módulo: insumos)

| Acción | Superadmin | Admin | Operador | Consultor |
|--------|------------|-------|----------|-----------|
| Ver ingresos | ✓ | ✓ | ✓ | ✓ |
| Crear ingreso | ✓ | ✓ | ✓ | ✗ |
| Editar ingreso | ✓ | ✓ | ✓ | ✗ |
| **Eliminar ingreso** | **✓** | **✓** | **✗** | **✗** |

### Planos (módulo: telecom)

| Acción | Superadmin | Admin | Operador | Consultor |
|--------|------------|-------|----------|-----------|
| Ver planos | ✓ | ✓ | ✓ | ✓ |
| Subir plano | ✓ | ✓ | ✓ | ✗ |
| Editar plano | ✓ | ✓ | ✗ | ✗ |
| **Eliminar plano** | **✓** | **✓** | **✗** | **✗** |

---

## ✅ RESUMEN

**NO SE REQUIERE NINGÚN CAMBIO ADICIONAL**

Los permisos ya están correctamente configurados en el script SQL:

1. **Ingresos** → Hereda permisos de `insumos`
   - Superadmin y Admin pueden eliminar
   - Operador puede ver, crear y editar
   - Consultor solo puede ver

2. **Planos** → Hereda permisos de `telecom`
   - Superadmin y Admin pueden eliminar
   - Operador puede ver y crear
   - Consultor solo puede ver

---

## 🎯 PERMISOS ACTUALES EN EL SCRIPT SQL

### ROL 1: Superadministrador
```json
{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "telecom": ["ver", "crear", "editar", "eliminar"]
}
```
✅ **Puede eliminar ingresos y planos**

### ROL 2: Administrador
```json
{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "telecom": ["ver", "crear", "editar", "eliminar"]
}
```
✅ **Puede eliminar ingresos y planos**

### ROL 3: Operador
```json
{
  "insumos": ["ver", "crear", "editar", "baja"],
  "telecom": ["ver", "crear"]
}
```
✅ **Puede editar ingresos pero NO eliminar**  
✅ **Puede crear planos pero NO editar ni eliminar**

### ROL 4: Consultor
```json
{
  "insumos": ["ver"],
  "telecom": ["ver"]
}
```
✅ **Solo puede ver ingresos y planos**

---

## ✅ CONCLUSIÓN

**Los permisos para Planos e Ingresos YA ESTÁN CORRECTAMENTE CONTEMPLADOS** en la configuración actual del script SQL.

No se requiere ninguna modificación adicional.
