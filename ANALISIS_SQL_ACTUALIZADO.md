# Análisis de Inconsistencias y Redundancias
## Archivo: `inventario_insumos_v1.sql`
## Fecha de análisis: 2025-11-03

---

## 🔴 REDUNDANCIAS ENCONTRADAS

### 1. **Tabla `insumos` - Índices Duplicados** (Líneas 801-812)

#### Índices sobre `id_sede_actual`:
- ❌ Línea 803: `id_sede_actual` (índice automático de FK)
- ❌ Línea 806: `idx_insumos_sede` sobre `id_sede_actual`
- ❌ Línea 810: `idx_i_sede_actual` sobre `id_sede_actual`
**Solución:** Eliminar `idx_insumos_sede` y `idx_i_sede_actual`, mantener solo el índice de FK.

#### Índices sobre `estado`:
- ❌ Línea 804: `idx_insumos_estado` sobre `estado`
- ❌ Línea 807: `idx_i_estado` sobre `estado`
**Solución:** Eliminar `idx_i_estado`, mantener solo `idx_insumos_estado`.

#### Índices sobre `tipo_insumo`:
- ❌ Línea 805: `idx_insumos_tipo` sobre `tipo_insumo`
- ❌ Línea 808: `idx_i_tipo` sobre `tipo_insumo`
**Solución:** Eliminar `idx_i_tipo`, mantener solo `idx_insumos_tipo`.

#### Índices sobre `id_punto_stock_actual`:
- ❌ Línea 801: `id_punto_stock_actual` (índice automático de FK)
- ❌ Línea 809: `idx_i_punto` sobre `id_punto_stock_actual`
**Solución:** Eliminar `idx_i_punto`, mantener solo el índice de FK.

#### Índices sobre `id_area_asignacion_actual`:
- ❌ Línea 802: `id_area_asignacion_actual` (índice automático de FK)
- ❌ Línea 811: `idx_i_area_actual` sobre `id_area_asignacion_actual`
**Solución:** Eliminar `idx_i_area_actual`, mantener solo el índice de FK.

---

### 2. **Tabla `remitos` - Índices Duplicados** (Líneas 860-871)

#### UNIQUE KEY duplicado sobre `numero_remito`:
- ❌ Línea 862: `UNIQUE KEY numero_remito` sobre `numero_remito`
- ❌ Línea 863: `UNIQUE KEY uniq_numero_remito` sobre `numero_remito`
**Solución:** Eliminar `uniq_numero_remito`, mantener solo `numero_remito`.

#### Índices sobre `fecha_asignacion`:
- ❌ Línea 864: `idx_remitos_fecha` sobre `fecha_asignacion`
- ❌ Línea 870: `idx_r_fecha` sobre `fecha_asignacion`
**Solución:** Eliminar `idx_r_fecha`, mantener solo `idx_remitos_fecha`.

#### Índices sobre `estado`:
- ❌ Línea 865: `idx_remitos_estado` sobre `estado`
- ❌ Línea 871: `idx_estado` sobre `estado`
**Solución:** Eliminar `idx_estado`, mantener solo `idx_remitos_estado`.

#### Índices sobre `id_sede` (FK duplicado):
- ❌ Línea 866: `fk_remitos_sede` (índice de FK) sobre `id_sede`
- ❌ Línea 868: `idx_r_sede` sobre `id_sede`
**Solución:** Eliminar `idx_r_sede`, el índice de FK ya cubre esto.

#### Índices sobre `id_area` (FK duplicado):
- ❌ Línea 867: `fk_remitos_area` (índice de FK) sobre `id_area`
- ❌ Línea 869: `idx_r_area` sobre `id_area`
**Solución:** Eliminar `idx_r_area`, el índice de FK ya cubre esto.

---

### 3. **Tabla `sedes_internet` - Índice Duplicado** (Líneas 897-907)

#### Índices sobre `estado_servicio`:
- ❌ Línea 900: `idx_si_estado` sobre `estado_servicio`
- ❌ Línea 902: `idx_estado_servicio` sobre `estado_servicio`
**Solución:** Eliminar `idx_estado_servicio`, mantener solo `idx_si_estado`.

---

### 4. **Tabla `insumos` - Foreign Keys Duplicadas** (Líneas 1118-1126)

#### FK sobre `id_area_asignacion_actual`:
- ✅ Línea 1119: `fk_i_area_actual` con `ON DELETE SET NULL`
- ❌ Línea 1122: `fk_insumos_area_actual` sin `ON DELETE SET NULL`
**Solución:** Eliminar `fk_insumos_area_actual`, mantener `fk_i_area_actual` (tiene mejor manejo de borrado).

#### FK sobre `id_ingreso`:
- ✅ Línea 1123: `fk_insumos_ingreso` con `ON DELETE SET NULL ON UPDATE CASCADE`
- ❌ Línea 1124: `fk_insumos_licitacion` con solo `ON DELETE SET NULL`
**Solución:** Eliminar `fk_insumos_licitacion`, mantener `fk_insumos_ingreso` (tiene mejor manejo de actualización).

#### FK sobre `id_punto_stock_actual`:
- ✅ Línea 1120: `fk_i_punto` con `ON DELETE SET NULL`
- ❌ Línea 1125: `fk_insumos_punto_stock` sin `ON DELETE SET NULL`
**Solución:** Eliminar `fk_insumos_punto_stock`, mantener `fk_i_punto` (tiene mejor manejo de borrado).

#### FK sobre `id_sede_actual`:
- ✅ Línea 1121: `fk_i_sede_actual` con `ON DELETE SET NULL`
- ❌ Línea 1126: `fk_insumos_sede_actual` sin `ON DELETE SET NULL`
**Solución:** Eliminar `fk_insumos_sede_actual`, mantener `fk_i_sede_actual` (tiene mejor manejo de borrado).

---

### 5. **Tabla `remitos` - Foreign Keys Duplicadas** (Líneas 1161-1165)

#### FK sobre `id_area`:
- ✅ Línea 1162: `fk_r_area` sobre `id_area`
- ❌ Línea 1164: `fk_remitos_area` sobre `id_area`
**Solución:** Eliminar `fk_remitos_area`, mantener `fk_r_area`.

#### FK sobre `id_sede`:
- ✅ Línea 1163: `fk_r_sede` sobre `id_sede`
- ❌ Línea 1165: `fk_remitos_sede` sobre `id_sede`
**Solución:** Eliminar `fk_remitos_sede`, mantener `fk_r_sede`.

---

## ⚠️ INCONSISTENCIAS ENCONTRADAS

### 1. **Tabla `ingresos`** (Línea 105)
- **Problema:** `nro_referencia` está como `DEFAULT NULL`, pero según el código PHP debe ser `NOT NULL`
- **Problema:** El índice único `cod_expediente` (línea 793) solo cubre `nro_referencia`, pero debería ser compuesto `(tipo_ingreso, nro_referencia)` según el código PHP que valida que no haya duplicados por tipo
- **Solución:** Cambiar `nro_referencia` a `NOT NULL` y modificar el índice único a compuesto

### 2. **Tabla `monitores`** (Línea 267)
- **Problema:** El ENUM `conexion` solo tiene `'VGA','HDMI'`, pero el código PHP usa también `'DisplayPort'` y `'DVI'`
- **Solución:** Agregar `'DisplayPort'` y `'DVI'` al ENUM

### 3. **Tabla `pcs_completas`** (Línea 324)
- **Problema:** `sist_op` está definido como `text`, pero el código PHP lo usa como `VARCHAR(100)` en las consultas
- **Solución:** Cambiar de `TEXT` a `VARCHAR(100)` (más eficiente y consistente con el uso)

---

## 📊 RESUMEN

### Redundancias a eliminar:
- **Índices duplicados:** 12
- **Foreign Keys duplicadas:** 7
- **Total redundancias:** 19

### Inconsistencias a corregir:
- **Campos con definición incorrecta:** 3
- **Total inconsistencias:** 3

---

## ✅ RECOMENDACIONES

1. **Prioridad Alta:** Eliminar todas las redundancias (índices y FKs duplicadas) - mejoran rendimiento y evitan confusión
2. **Prioridad Media:** Corregir `ingresos.nro_referencia` y su índice único - importante para integridad de datos
3. **Prioridad Media:** Agregar valores al ENUM de `monitores.conexion` - evita errores en producción
4. **Prioridad Baja:** Cambiar `pcs_completas.sist_op` de TEXT a VARCHAR - optimización menor

---

## ⚠️ ADVERTENCIAS

- **Backup obligatorio** antes de aplicar cambios
- **Probar en ambiente de desarrollo** primero
- **Verificar que el código PHP funcione** después de los cambios
- Algunas FK tienen mejor manejo de borrados (`ON DELETE SET NULL`), mantener esas y eliminar las duplicadas sin manejo
