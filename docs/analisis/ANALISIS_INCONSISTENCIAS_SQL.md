# Análisis de Inconsistencias y Redundancias en inventario_insumos_v1.sql

## 🔴 REDUNDANCIAS CRÍTICAS

### 1. **Tabla `insumos` - Índices Duplicados**
**Líneas 568-579**

Se definen **múltiples índices redundantes** sobre las mismas columnas:

```sql
ADD KEY `id_punto_stock_actual` (`id_punto_stock_actual`),           -- Línea 569
ADD KEY `id_area_asignacion_actual` (`id_area_asignacion_actual`),  -- Línea 570
ADD KEY `id_sede_actual` (`id_sede_actual`),                        -- Línea 571
ADD KEY `idx_insumos_estado` (`estado`),                             -- Línea 572
ADD KEY `idx_insumos_tipo` (`tipo_insumo`),                          -- Línea 573
ADD KEY `idx_insumos_sede` (`id_sede_actual`),                      -- Línea 574 (❌ REDUNDANTE con 571)
ADD KEY `idx_i_estado` (`estado`),                                   -- Línea 575 (❌ REDUNDANTE con 572)
ADD KEY `idx_i_tipo` (`tipo_insumo`),                                -- Línea 576 (❌ REDUNDANTE con 573)
ADD KEY `idx_i_punto` (`id_punto_stock_actual`),                     -- Línea 577 (❌ REDUNDANTE con 569)
ADD KEY `idx_i_sede_actual` (`id_sede_actual`),                     -- Línea 578 (❌ REDUNDANTE con 571, 574)
ADD KEY `idx_i_area_actual` (`id_area_asignacion_actual`);          -- Línea 579 (❌ REDUNDANTE con 570)
```

**Solución:** Eliminar índices duplicados, mantener solo:
- `id_punto_stock_actual`
- `id_area_asignacion_actual`
- `id_sede_actual`
- `idx_insumos_estado` (o `idx_i_estado`)
- `idx_insumos_tipo` (o `idx_i_tipo`)

---

### 2. **Tabla `remitos` - Índices Duplicados**
**Líneas 629-637**

```sql
ADD UNIQUE KEY `numero_remito` (`numero_remito`),      -- Línea 629
ADD UNIQUE KEY `uniq_numero_remito` (`numero_remito`), -- Línea 630 (❌ REDUNDANTE con 629)
ADD KEY `idx_remitos_fecha` (`fecha_asignacion`),      -- Línea 631
ADD KEY `fk_remitos_sede` (`id_sede`),                 -- Línea 633
ADD KEY `fk_remitos_area` (`id_area`),                 -- Línea 634
ADD KEY `idx_r_sede` (`id_sede`),                      -- Línea 635 (❌ REDUNDANTE con 633)
ADD KEY `idx_r_area` (`id_area`),                      -- Línea 636 (❌ REDUNDANTE con 634)
ADD KEY `idx_r_fecha` (`fecha_asignacion`);            -- Línea 637 (❌ REDUNDANTE con 631)
```

**Solución:** Eliminar duplicados, mantener solo:
- `numero_remito` (UNIQUE)
- `idx_remitos_fecha` (o `idx_r_fecha`)
- `fk_remitos_sede` (o `idx_r_sede`)
- `fk_remitos_area` (o `idx_r_area`)

---

### 3. **Tabla `remitos` - Foreign Keys Duplicadas**
**Líneas 913-916**

```sql
ADD CONSTRAINT `fk_r_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),       -- Línea 913
ADD CONSTRAINT `fk_r_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`),        -- Línea 914
ADD CONSTRAINT `fk_remitos_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`), -- Línea 915 (❌ REDUNDANTE con 913)
ADD CONSTRAINT `fk_remitos_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`); -- Línea 916 (❌ REDUNDANTE con 914)
```

**Solución:** Eliminar duplicados, mantener solo `fk_r_area` y `fk_r_sede`.

---

### 4. **Tabla `insumos` - Foreign Keys Duplicadas**
**Líneas 872-877**

```sql
ADD CONSTRAINT `fk_i_area_actual` FOREIGN KEY (`id_area_asignacion_actual`) REFERENCES `areas` (`id_area`) ON DELETE SET NULL, -- Línea 872
ADD CONSTRAINT `fk_i_punto` FOREIGN KEY (`id_punto_stock_actual`) REFERENCES `puntos_stock` (`id_punto_stock`) ON DELETE SET NULL,  -- Línea 873
ADD CONSTRAINT `fk_i_sede_actual` FOREIGN KEY (`id_sede_actual`) REFERENCES `sedes` (`id_sede`) ON DELETE SET NULL,               -- Línea 874
ADD CONSTRAINT `fk_insumos_area_actual` FOREIGN KEY (`id_area_asignacion_actual`) REFERENCES `areas` (`id_area`),                -- Línea 875 (❌ REDUNDANTE con 872)
ADD CONSTRAINT `fk_insumos_punto_stock` FOREIGN KEY (`id_punto_stock_actual`) REFERENCES `puntos_stock` (`id_punto_stock`),         -- Línea 876 (❌ REDUNDANTE con 873)
ADD CONSTRAINT `fk_insumos_sede_actual` FOREIGN KEY (`id_sede_actual`) REFERENCES `sedes` (`id_sede`);                             -- Línea 877 (❌ REDUNDANTE con 874)
```

**Solución:** Mantener solo las que tienen `ON DELETE SET NULL` (líneas 872-874), eliminar las duplicadas (875-877).

---

## ⚠️ INCONSISTENCIAS CON MIGRACIONES

### 5. **Tabla `remitos` - Estado sin 'Anulado'**
**Línea 247**

El archivo SQL base define:
```sql
`estado` enum('Activa','Devuelta') NOT NULL DEFAULT 'Activa',
```

Pero `sql/migracion_remitos_anulados.sql` agrega el estado 'Anulado' y campos `motivo_anulacion` y `fecha_anulacion`.

**Solución:** Actualizar el SQL base para incluir:
```sql
`estado` enum('Activa','Devuelta','Anulado') NOT NULL DEFAULT 'Activa',
`motivo_anulacion` TEXT DEFAULT NULL,
`fecha_anulacion` DATETIME DEFAULT NULL
```

---

### 6. **Tabla `insumos` - Campo `nombre_insumo` debería permitir NULL**
**Línea 89**

El SQL base define:
```sql
`nombre_insumo` varchar(100) NOT NULL,
```

Pero `sql/permitir_nombre_insumo_null.sql` indica que debe permitir NULL para tipos no "Varios".

**Solución:** Cambiar a:
```sql
`nombre_insumo` varchar(100) DEFAULT NULL,
```

---

### 7. **Tabla `insumos` - Tipo 'PC Completa' debería ser 'PC Escritorio'**
**Línea 90**

El SQL base define:
```sql
`tipo_insumo` enum('Varios','PC Completa','Notebook','Impresora','Monitor','Escaner') NOT NULL,
```

Pero `sql/actualizar_pc_completa_a_escritorio.sql` indica que debe ser 'PC Escritorio'.

**Solución:** Cambiar a:
```sql
`tipo_insumo` enum('Varios','PC Escritorio','Notebook','Impresora','Monitor','Escaner') NOT NULL,
```

---

### 8. **Tabla `insumos` - Falta campo `id_ingreso`**
**Líneas 87-103**

Falta el campo `id_ingreso` que se agrega en `SQL_MIGRACION_INGRESOS.sql`.

**Solución:** Agregar después de `id_area_asignacion_actual`:
```sql
`id_ingreso` int(11) DEFAULT NULL,
```

Y agregar la foreign key:
```sql
ADD CONSTRAINT `fk_insumos_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE,
```

---

### 9. **Tabla `insumos` - Falta campo `es_nuevo`**
El código PHP en `agregar.php` y `agregar_nueva.php` usa `es_nuevo` pero no está en el SQL base.

**Solución:** Agregar:
```sql
`es_nuevo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Nuevo, 0=Usado',
```

---

### 10. **Tabla `pcs_completas` - Falta campo `sist_op`**
**Líneas 203-210**

El código PHP usa `sist_op` pero no está en la definición de la tabla.

**Solución:** Agregar:
```sql
`sist_op` varchar(100) DEFAULT NULL COMMENT 'Sistema Operativo',
```

---

### 11. **Tabla `notebooks` - Faltan campos extras**
**Líneas 187-195**

Faltan campos que se agregan en `sql/notebooks_extra_fields.sql`:
- `cargador`
- `funda`
- `micro_sd`
- `micro_sd_gb`
- `caja`
- `adaptador_red`

**Solución:** Agregar estos campos según el archivo de migración.

---

### 12. **Tabla `monitores` - ENUM de conexión incompleto**
**Línea 178**

El SQL base define:
```sql
`conexion` enum('VGA','HDMI') NOT NULL
```

Pero el código PHP (en `agregar.php` línea 644-646) incluye también 'DisplayPort' y 'DVI'.

**Solución:** Actualizar a:
```sql
`conexion` enum('VGA','HDMI','DisplayPort','DVI') NOT NULL
```

---

### 13. **Tabla `sedes_internet` - Campos faltantes según migraciones**
**Líneas 364-375**

Faltan campos de migraciones:
- `velocidad_mbps` (de `migracion_internet_velocidad_unica.sql`)
- `fecha_instalacion` (de `agregar_fecha_instalacion_internet.sql`)
- `fecha_baja` (de `agregar_fecha_baja_internet.sql`)
- `instancia_pendiente` (de `migracion_internet_instancias_pendientes.sql`)
- `fecha_solicitud_autorizacion`
- `archivo_autorizacion`
- `id_servicio_trasladado_a` (de `agregar_traslados.sql`)
- `id_servicio_trasladado_desde`
- `fecha_traslado`
- Estado 'Baja por Traslado' en enum

**Solución:** Actualizar la definición completa según todas las migraciones.

---

### 14. **Falta tabla `ingresos`**
El código PHP y `SQL_MIGRACION_INGRESOS.sql` hacen referencia a la tabla `ingresos` pero no está en el SQL base.

**Solución:** Agregar definición completa de la tabla `ingresos`.

---

## 📋 RESUMEN DE ACCIONES REQUERIDAS

### Eliminar redundancias:
1. Eliminar índices duplicados en `insumos` (6 índices redundantes)
2. Eliminar índices duplicados en `remitos` (3 índices redundantes)
3. Eliminar foreign keys duplicadas en `remitos` (2 FKs redundantes)
4. Eliminar foreign keys duplicadas en `insumos` (3 FKs redundantes)

### Actualizar definiciones según migraciones:
5. Actualizar `remitos.estado` para incluir 'Anulado'
6. Agregar campos `motivo_anulacion` y `fecha_anulacion` en `remitos`
7. Cambiar `insumos.nombre_insumo` a permitir NULL
8. Cambiar 'PC Completa' a 'PC Escritorio' en enum
9. Agregar campo `id_ingreso` en `insumos`
10. Agregar campo `es_nuevo` en `insumos`
11. Agregar campo `sist_op` en `pcs_completas`
12. Agregar 6 campos extras en `notebooks`
13. Actualizar enum de `monitores.conexion`
14. Actualizar `sedes_internet` con todos los campos de migraciones
15. Agregar definición completa de tabla `ingresos`

---

## ✅ RECOMENDACIONES ADICIONALES

1. **Índice único para número_serie, id_fisico, id_patrimonio**: Considerar índices únicos para prevenir duplicados (aunque el código ya valida esto).

2. **Foreign Keys con ON DELETE CASCADE donde corresponda**: Revisar si algunas relaciones deberían usar CASCADE en lugar de SET NULL.

3. **Índice compuesto en remitos**: Considerar índice compuesto en `(estado, fecha_asignacion)` para queries frecuentes.

4. **Revisar charset/collation**: Asegurar que todas las tablas usen `utf8mb4` y `utf8mb4_unicode_ci` (mejor soporte Unicode).

---

**Fecha de análisis:** 2025-11-03
**Archivo analizado:** `inventario_insumos_v1.sql`
