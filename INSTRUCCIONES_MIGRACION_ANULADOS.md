# Migración: Sistema de Remitos Anulados

## Fecha: 2025-10-27
## Rama: correciones_en_21

## Resumen de Cambios

Este conjunto de cambios implementa un sistema completo para la anulación de remitos, eliminando la reutilización de números de remito y manteniendo un historial completo de todas las operaciones.

### Cambios Principales

1. **Eliminación de reutilización de números de remito**
   - Los números de remito ahora son consecutivos y nunca se reutilizan
   - Simplificación de la función `generarNumeroRemito()`

2. **Nuevo estado "Anulado" para remitos**
   - Los remitos ya no se eliminan de la base de datos
   - Se marcan como "Anulado" con fecha y motivo
   - Los insumos se revierten a estado disponible

3. **Nueva sección "Anulados" en Historial**
   - Pestaña adicional en el historial para consultar remitos anulados
   - Visualización de motivo y fecha de anulación
   - Filtros y búsqueda de remitos anulados

4. **Interfaz mejorada para anulación**
   - Modal que solicita motivo obligatorio de anulación
   - Confirmación clara de la acción
   - Mensajes informativos sobre el impacto

---

## Instrucciones de Migración

### Paso 1: Ejecutar el script SQL de migración

```bash
mysql -u [usuario] -p [nombre_base_datos] < sql/migracion_remitos_anulados.sql
```

O ejecutar manualmente en phpMyAdmin/MySQL Workbench:

```sql
-- Modificar el ENUM del estado de remitos para incluir 'Anulado'
ALTER TABLE `remitos` 
MODIFY COLUMN `estado` ENUM('Activa','Devuelta','Anulado') NOT NULL DEFAULT 'Activa';

-- Agregar campos para almacenar información de anulación
ALTER TABLE `remitos` 
ADD COLUMN `motivo_anulacion` TEXT DEFAULT NULL AFTER `observaciones`,
ADD COLUMN `fecha_anulacion` DATETIME DEFAULT NULL AFTER `motivo_anulacion`;

-- Crear índice para mejorar búsquedas por estado
ALTER TABLE `remitos` 
ADD INDEX `idx_estado` (`estado`);
```

### Paso 2: Verificar que no hay remitos existentes con problemas

```sql
-- Verificar que todos los remitos tienen un estado válido
SELECT estado, COUNT(*) 
FROM remitos 
GROUP BY estado;

-- Debería mostrar solo 'Activa' y 'Devuelta'
```

### Paso 3: Limpiar caché del navegador

Es importante que los usuarios limpien la caché del navegador o fuercen una recarga (Ctrl+F5) para cargar los nuevos archivos JavaScript y CSS.

---

## Archivos Modificados

### Backend (PHP)

1. **includes/config.php**
   - Simplificación de `generarNumeroRemito()` (eliminada lógica de reutilización)

2. **ajax/asignacion_eliminar.php**
   - Ahora anula en lugar de eliminar
   - Requiere campo `motivo` obligatorio
   - Actualiza estado a 'Anulado' con fecha y motivo

3. **ajax/asignaciones_list_ssp.php**
   - Excluye remitos anulados del listado principal
   - Actualizado contador total

4. **ajax/contadores_dashboard.php**
   - Excluye remitos anulados de todos los contadores
   - Dashboard muestra solo remitos activos/devueltos

5. **ajax/remito_detalle.php**
   - Incluye campos `motivo_anulacion` y `fecha_anulacion` en la respuesta

### Nuevos Archivos

6. **ajax/remitos_anulados_ssp.php** (NUEVO)
   - Endpoint para listar remitos anulados con paginación server-side

7. **sql/migracion_remitos_anulados.sql** (NUEVO)
   - Script de migración de la base de datos

### Frontend (Vistas)

8. **pages/asignaciones/listar.php**
   - SQL actualizado para excluir remitos anulados
   - Nuevo modal para solicitar motivo de anulación
   - JavaScript actualizado para manejar el modal

9. **pages/reportes/historial.php**
   - Nueva pestaña "Anulados"
   - DataTable para remitos anulados
   - Visualización de motivo y fecha de anulación en modal

---

## Funcionalidades Nuevas

### Para el Usuario Final

1. **Anulación de Remitos**
   - Al intentar eliminar una asignación, se abre un modal
   - Debe ingresar un motivo obligatorio
   - El remito se marca como anulado (no se elimina)
   - Los insumos vuelven a estar disponibles

2. **Consulta de Remitos Anulados**
   - Menú: Asignaciones → Historial → Pestaña "Anulados"
   - Lista todos los remitos anulados con su motivo
   - Se puede ver el detalle completo del remito
   - Búsqueda y filtros disponibles

3. **Numeración Consecutiva**
   - Los números de remito ya no se reutilizan
   - Incluso si se anula un remito, su número queda reservado
   - Secuencia limpia y auditoria clara

### Validaciones Implementadas

- No se puede anular un remito ya anulado
- El motivo de anulación es obligatorio (mínimo texto requerido)
- Confirmación clara antes de anular
- Los remitos anulados no aparecen en listados principales
- Los remitos anulados no se cuentan en el dashboard

---

## Comportamiento del Sistema

### Antes de la Migración
- Eliminar asignación → Remito se borra completamente
- Números de remito podían reutilizarse si había "huecos"
- No había historial de asignaciones eliminadas

### Después de la Migración
- Eliminar asignación → Remito se marca como "Anulado"
- Números de remito son consecutivos, nunca se reutilizan
- Historial completo de todas las operaciones
- Motivo registrado para cada anulación

---

## Verificación Post-Migración

### Checklist de Pruebas

1. ✅ **Migración SQL ejecutada correctamente**
   ```sql
   DESCRIBE remitos;
   -- Verificar que existen: motivo_anulacion, fecha_anulacion
   -- Verificar que estado incluye: 'Activa','Devuelta','Anulado'
   ```

2. ✅ **Crear nueva asignación**
   - Verificar que genera número consecutivo
   - No debe reutilizar números

3. ✅ **Anular una asignación**
   - Debe aparecer modal solicitando motivo
   - No debe permitir continuar sin motivo
   - Insumos deben volver a disponible
   - Remito debe quedar en BD con estado 'Anulado'

4. ✅ **Consultar remitos anulados**
   - Ir a Asignaciones → Historial → Anulados
   - Verificar que aparecen remitos anulados
   - Verificar que muestra motivo y fecha
   - Verificar que botón "Ver" muestra detalle completo

5. ✅ **Verificar dashboard**
   - Contadores no deben incluir remitos anulados
   - Listado de asignaciones recientes no debe incluir anulados

6. ✅ **Verificar listado de asignaciones**
   - No deben aparecer remitos anulados
   - Solo deben verse Activas y Devueltas

---

## Rollback (Si es necesario)

Si necesitas revertir los cambios:

```sql
-- Revertir estructura de tabla
ALTER TABLE `remitos` 
DROP COLUMN `fecha_anulacion`,
DROP COLUMN `motivo_anulacion`,
DROP INDEX `idx_estado`,
MODIFY COLUMN `estado` ENUM('Activa','Devuelta') NOT NULL DEFAULT 'Activa';

-- ADVERTENCIA: Esto eliminará todos los remitos marcados como anulados
DELETE FROM remitos WHERE estado = 'Anulado';
```

Luego revertir los cambios de código usando git:
```bash
git checkout HEAD~1 -- includes/config.php
git checkout HEAD~1 -- ajax/asignacion_eliminar.php
# ... etc para cada archivo modificado
```

---

## Notas Importantes

1. **Backup de Base de Datos**: Asegúrate de tener un backup completo antes de ejecutar la migración

2. **Remitos Anulados Existentes**: Si ya tenías remitos que fueron eliminados antes de esta migración, no podrás recuperarlos. Los números de esos remitos no serán reutilizados automáticamente.

3. **Rendimiento**: El índice agregado en el campo `estado` mejora el rendimiento de las consultas que filtran por estado.

4. **Auditoría**: Todos los remitos anulados quedan registrados permanentemente con su fecha y motivo, mejorando la auditoría del sistema.

---

## Soporte

Para problemas con la migración o dudas sobre el funcionamiento del nuevo sistema, contactar al equipo de desarrollo.

**Fecha de implementación**: 2025-10-27  
**Versión**: 1.1  
**Rama**: correciones_en_21
