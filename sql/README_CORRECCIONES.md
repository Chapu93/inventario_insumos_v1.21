# Script de Corrección de Inconsistencias y Redundancias

## 📋 Descripción

Este script SQL corrige todas las inconsistencias y redundancias identificadas en el esquema de la base de datos `inventario_insumos_v1`, según el análisis realizado y las migraciones aplicadas.

## ✅ Correcciones Aplicadas

### 1. **Eliminación de Redundancias**

#### Índices Duplicados:
- **Tabla `insumos`**: Elimina `idx_insumos_sede`, `idx_i_estado`, `idx_i_tipo`, `idx_i_punto`, `idx_i_sede_actual`, `idx_i_area_actual`
- **Tabla `remitos`**: Elimina `uniq_numero_remito`, `idx_r_sede`, `idx_r_area`, `idx_r_fecha`

#### Foreign Keys Duplicadas:
- **Tabla `remitos`**: Elimina `fk_remitos_area`, `fk_remitos_sede` (mantiene `fk_r_area`, `fk_r_sede`)
- **Tabla `insumos`**: Elimina `fk_insumos_area_actual`, `fk_insumos_punto_stock`, `fk_insumos_sede_actual` (mantiene versiones con `fk_i_*`)

### 2. **Corrección de Inconsistencias**

#### Tabla `remitos`:
- ✅ Agrega estado `'Anulado'` al ENUM de `estado`
- ✅ Agrega campos `motivo_anulacion` y `fecha_anulacion`
- ✅ Crea índice `idx_estado` para mejorar búsquedas

#### Tabla `insumos`:
- ✅ Permite `NULL` en `nombre_insumo` (según migración)
- ✅ Actualiza ENUM `tipo_insumo`: elimina `'PC Completa'`, mantiene `'PC Escritorio'`
- ✅ Agrega campo `id_ingreso` (FK a tabla `ingresos`)
- ✅ Agrega campo `es_nuevo` (TINYINT, default 1)

#### Tabla `pcs_completas`:
- ✅ Agrega campo `sist_op` (Sistema Operativo)

#### Tabla `notebooks`:
- ✅ Agrega campos: `cargador`, `funda`, `micro_sd`, `micro_sd_gb`, `caja`, `adaptador_red`

#### Tabla `monitores`:
- ✅ Actualiza ENUM `conexion` para incluir `'DisplayPort'` y `'DVI'`

#### Tabla `ingresos`:
- ✅ Crea la tabla completa si no existe (con todos los campos necesarios según código PHP)

#### Tabla `sedes_internet`:
- ✅ Agrega campo `velocidad_mbps` (unifica bajada/subida)
- ✅ Agrega campos: `fecha_instalacion`, `fecha_baja`
- ✅ Agrega ENUM `'Baja por Traslado'` a `estado_servicio`
- ✅ Agrega campos de instancias pendientes: `instancia_pendiente`, `fecha_solicitud_autorizacion`, `archivo_autorizacion`
- ✅ Agrega campos de traslados: `id_servicio_trasladado_a`, `id_servicio_trasladado_desde`, `fecha_traslado`
- ✅ Crea índices para todos los campos nuevos

## 🔒 Seguridad

- ✅ **Transacción**: Todo el script está envuelto en `START TRANSACTION` / `COMMIT`
- ✅ **Verificaciones**: Cada operación verifica la existencia antes de ejecutarse
- ✅ **No destructivo**: Solo elimina elementos redundantes, no datos ni funcionalidad
- ✅ **Idempotente**: Puede ejecutarse múltiples veces sin causar errores (si un elemento ya existe o no existe, se maneja correctamente)

## 📝 Requisitos Previos

1. **Respaldo de la base de datos**:
   ```bash
   mysqldump -u usuario -p inventario_insumos_v1 > backup_antes_correcciones.sql
   ```

2. **Acceso a la base de datos** con permisos suficientes:
   - `ALTER TABLE`
   - `CREATE TABLE`
   - `DROP INDEX`
   - `DROP FOREIGN KEY`

3. **Versión de MySQL/MariaDB**: MySQL 5.7+ o MariaDB 10.x+

## 🚀 Ejecución

### Opción 1: Línea de comandos
```bash
mysql -u usuario -p inventario_insumos_v1 < sql/corregir_inconsistencias_y_redundancias.sql
```

### Opción 2: phpMyAdmin o cliente MySQL
1. Abre phpMyAdmin o tu cliente MySQL preferido
2. Selecciona la base de datos `inventario_insumos_v1`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `corregir_inconsistencias_y_redundancias.sql`
5. Ejecuta el script

### Opción 3: Desde PHP (con verificación previa)
```bash
php test_correcciones_sql.php
```

## ⚠️ Precauciones

1. **NO ejecutes el script sin hacer un respaldo primero**
2. **Revisa los mensajes de error** durante la ejecución
3. **Si hay errores**, el script hará `ROLLBACK` automáticamente si no llegas al `COMMIT`
4. **Verifica la funcionalidad** después de ejecutar:
   - Prueba crear/editar insumos
   - Prueba crear remitos
   - Prueba anular remitos
   - Verifica que los reportes funcionen correctamente

## 🔍 Verificación Post-Ejecución

Después de ejecutar el script, verifica que:

1. **No hay índices duplicados**:
   ```sql
   SELECT INDEX_NAME, COUNT(*) as cnt
   FROM information_schema.STATISTICS 
   WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME IN ('insumos', 'remitos')
   GROUP BY INDEX_NAME
   HAVING cnt > 1;
   ```
   (Debería devolver 0 filas)

2. **Los campos nuevos existen**:
   ```sql
   SHOW COLUMNS FROM insumos LIKE 'id_ingreso';
   SHOW COLUMNS FROM insumos LIKE 'es_nuevo';
   SHOW COLUMNS FROM remitos LIKE 'motivo_anulacion';
   SHOW COLUMNS FROM pcs_completas LIKE 'sist_op';
   ```

3. **Los ENUMs están actualizados**:
   ```sql
   SHOW COLUMNS FROM remitos WHERE Field = 'estado';
   SHOW COLUMNS FROM insumos WHERE Field = 'tipo_insumo';
   SHOW COLUMNS FROM monitores WHERE Field = 'conexion';
   ```

## 📊 Tiempo Estimado

- **Ejecución**: 10-30 segundos (dependiendo del tamaño de la base de datos)
- **Verificación**: 5-10 minutos

## 🐛 Solución de Problemas

### Error: "Duplicate key name"
- **Causa**: El índice ya existe o ya fue eliminado
- **Solución**: El script maneja esto automáticamente con verificaciones previas

### Error: "Unknown column"
- **Causa**: La columna no existe cuando se intenta eliminar un índice sobre ella
- **Solución**: Verifica que la estructura de la tabla sea correcta antes de ejecutar

### Error: "Foreign key constraint fails"
- **Causa**: Hay datos que violan las restricciones de foreign key
- **Solución**: Limpia los datos huérfanos antes de ejecutar el script

## 📞 Soporte

Si encuentras problemas al ejecutar el script:
1. Guarda el mensaje de error completo
2. Verifica que tienes la última versión del script
3. Revisa el análisis completo en `ANALISIS_INCONSISTENCIAS_SQL.md`

## 📄 Archivos Relacionados

- `corregir_inconsistencias_y_redundancias.sql` - Script principal
- `test_correcciones_sql.php` - Script de verificación previa
- `ANALISIS_INCONSISTENCIAS_SQL.md` - Análisis detallado de inconsistencias
