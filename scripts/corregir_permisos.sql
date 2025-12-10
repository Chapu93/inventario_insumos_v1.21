-- Script de corrección de permisos - 4 ROLES
-- Sistema de Inventario de Insumos
-- Actualizado: 2025-12-09

-- ============================================
-- DIAGNÓSTICO: Ver estado actual
-- ============================================
SELECT '=== ROLES ACTUALES ===' as info;
SELECT id_rol, nombre_rol, permisos FROM roles ORDER BY id_rol;

-- ============================================
-- CORRECCIÓN DE PERMISOS - 4 ROLES
-- ============================================

-- ROL 1: Superadministrador (acceso total al sistema)
UPDATE roles SET 
    nombre_rol = 'Superadministrador',
    permisos = '{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "anular", "devolver"],
  "sedes": ["ver", "crear", "editar", "eliminar"],
  "telecom": ["ver", "crear", "editar", "eliminar"],
  "reportes": ["ver", "exportar"],
  "usuarios": ["ver", "crear", "editar", "eliminar", "cambiar_rol", "reset_password"],
  "auditoria": ["ver_todo"],
  "sistema": ["backup"],
  "areas": ["ver", "crear", "editar", "eliminar"]
}'
WHERE id_rol = 1;

-- ROL 2: Administrador (gestión completa con eliminación)
UPDATE roles SET 
    nombre_rol = 'Administrador',
    permisos = '{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "anular", "devolver"],
  "sedes": ["ver", "crear", "editar", "eliminar"],
  "telecom": ["ver", "crear", "editar", "eliminar"],
  "reportes": ["ver", "exportar"],
  "usuarios": ["ver"],
  "areas": ["ver", "crear", "editar", "eliminar"]
}'
WHERE id_rol = 2;

-- ROL 3: Operador (operaciones diarias)
UPDATE roles SET 
    nombre_rol = 'Operador',
    permisos = '{
  "insumos": ["ver", "crear", "editar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "devolver"],
  "sedes": ["ver", "crear"],
  "telecom": ["ver", "crear"],
  "reportes": ["ver", "exportar"],
  "areas": ["ver", "crear"]
}'
WHERE id_rol = 3;

-- ROL 4: Consultor (solo lectura y asignaciones básicas)
UPDATE roles SET 
    nombre_rol = 'Consultor',
    permisos = '{
  "insumos": ["ver"],
  "asignaciones": ["ver", "crear"],
  "sedes": ["ver"],
  "telecom": ["ver"],
  "reportes": ["ver"],
  "areas": ["ver"]
}'
WHERE id_rol = 4;

-- ============================================
-- VERIFICACIÓN: Confirmar cambios
-- ============================================
SELECT '=== PERMISOS ACTUALIZADOS ===' as info;
SELECT id_rol, nombre_rol, permisos FROM roles ORDER BY id_rol;

-- ============================================
-- RESUMEN DE PERMISOS POR ROL
-- ============================================
/*
ROL 1 - SUPERADMINISTRADOR:
  ✓ Acceso total a todos los módulos
  ✓ Puede crear, editar, ELIMINAR en todos los módulos
  ✓ Puede ELIMINAR insumos, sedes, telecomunicaciones y áreas
  ✓ Puede ANULAR asignaciones
  ✓ Acceso completo a usuarios, auditoría y sistema (backup)

ROL 2 - ADMINISTRADOR:
  ✓ Gestión completa de insumos, asignaciones, sedes, telecom y áreas
  ✓ Puede ELIMINAR insumos, sedes, telecomunicaciones y áreas
  ✓ Puede ANULAR asignaciones
  ✓ Puede dar de baja insumos
  ✓ Puede ver usuarios (solo consulta)
  ✗ NO puede administrar usuarios, auditoría ni sistema

ROL 3 - OPERADOR:
  ✓ Puede crear, editar y DAR DE BAJA insumos
  ✓ Gestión completa de asignaciones (sin anular)
  ✓ Puede CREAR sedes y áreas
  ✓ Puede crear elementos en telecomunicaciones
  ✓ Ver y exportar reportes
  ✗ NO puede eliminar ni anular

ROL 4 - CONSULTOR:
  ✓ Solo lectura en la mayoría de módulos
  ✓ Puede crear asignaciones
  ✓ Puede ver telecomunicaciones, sedes y áreas
  ✓ Ver reportes
  ✗ NO puede modificar, eliminar ni dar de baja
*/

-- ============================================
-- INSTRUCCIONES DE USO:
-- ============================================
-- 1. Ejecutar este script completo
-- 2. Verificar en test_permisos.php que los cambios se aplicaron
-- 3. Probar con usuarios de cada rol
-- 4. Confirmar que los botones se muestren correctamente
