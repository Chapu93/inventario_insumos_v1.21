# Solución: Visibilidad de Botones Basada en Permisos

## Problema Reportado
Los iconos/botones de las acciones que no tiene permisos los usuarios **no se ocultaban automáticamente**. 

Los usuarios veían botones para realizar acciones que después resultaban en errores de permiso (403) al hacer clic.

## Solución Implementada
Se agregó lógica de **verificación de permisos en los AJAX endpoints** que generan los botones de acciones.

Ahora, antes de generar cada botón, se verifica si el usuario tiene permiso para ejecutar esa acción específica usando la función `tienePermiso($modulo, $accion)`.

### Cambios Realizados

#### 1. `ajax/insumos_list_ssp.php`
**Botones verificados:**
- ✓ Ver (ojo) → `tienePermiso('insumos', 'ver')`
- ✓ Editar (lápiz) → `tienePermiso('insumos', 'editar')`
- ✓ Dar de Baja (flecha abajo) → `tienePermiso('insumos', 'baja')`
- ✓ Eliminar (basura) → `tienePermiso('insumos', 'eliminar')`
- ✓ Reponer Stock → `tienePermiso('insumos', 'editar')`

**Antes:**
```php
$acciones = '<div class="btn-group" role="group">' . $btnReponer
          . '<button type="button" class="btn btn-sm btn-info"... <!-- Ver -->
          . ' <a href="editar.php?id=' . (int)$r['id_insumo'] . '"... <!-- Editar -->
          . ' <button type="button"... <!-- Baja -->
          . ' <button type="button" class="btn btn-sm btn-danger"... <!-- Eliminar -->
          . '</div>';
```

**Después:**
```php
$puedeVer = tienePermiso('insumos', 'ver');
$puedeEditar = tienePermiso('insumos', 'editar');
$puedeBaja = tienePermiso('insumos', 'baja');
$puedeEliminar = tienePermiso('insumos', 'eliminar');

$botones = [];
if ($puedeVer) $botones[] = '<!-- Botón Ver -->';
if ($puedeEditar) $botones[] = '<!-- Botón Editar -->';
if ($puedeBaja) $botones[] = '<!-- Botón Baja -->';
if ($puedeEliminar) $botones[] = '<!-- Botón Eliminar -->';

$acciones = '<div class="btn-group" role="group">' . implode(' ', $botones) . '</div>';
```

#### 2. `ajax/asignaciones_list_ssp.php`
**Botones verificados:**
- ✓ Ver (ojo) → `tienePermiso('asignaciones', 'ver')`
- ✓ Imprimir (impresora) → `tienePermiso('asignaciones', 'ver')`
- ✓ Devolver (deshacer) → `tienePermiso('asignaciones', 'devolver')`
- ✓ Eliminar (basura) → `tienePermiso('asignaciones', 'eliminar')`

#### 3. `ajax/remitos_list_ssp.php`
**Botones verificados:**
- ✓ Imprimir (impresora) → `tienePermiso('asignaciones', 'ver')`
- ✓ Ver Detalles (ojo) → `tienePermiso('asignaciones', 'ver')`

#### 4. `ajax/remitos_anulados_ssp.php`
**Botones verificados:**
- ✓ Ver (ojo) → `tienePermiso('asignaciones', 'ver')`

## Archivos NO Modificados
Estos archivos NO requieren cambios porque:

- **`ajax/auditoria_list_ssp.php`**: Ya verifica `tienePermiso('auditoria', 'ver_todo')` al inicio del archivo. El botón de ver detalles es consistente.
- **`ajax/historial_*.php`**: Verifican permisos a nivel de página. Los botones que muestran son informativos, no acciones peligrosas.

## Impacto

### Matriz de Permisos
Cada módulo tiene sus propias acciones que se pueden controlar independientemente:

| Módulo | Acciones |
|--------|----------|
| insumos | ver, editar, baja, eliminar |
| asignaciones | ver, devolver, eliminar |
| reportes | ver |
| auditoria | ver_todo |

### Comportamiento por Rol

**Administrador (Todos los permisos):**
- ✓ Ve todos los botones
- ✓ Puede realizar todas las acciones

**Usuario Limitado (Solo "ver"):**
- ✓ Ve botón de VER
- ✗ No ve botones de EDITAR, ELIMINAR, BAJA, etc.
- ✗ No puede hacer clic en acciones no permitidas

**Usuario sin permisos:**
- ✗ No ve ningún botón de acción
- ✗ Solo puede ver la tabla

## Beneficios

1. **Mejor UX**: Los usuarios no ven botones que no pueden usar
2. **Seguridad**: Refuerza la validación de permisos a nivel frontend
3. **Consistencia**: Frontend ahora refleja exactamente lo que el backend permite
4. **Mantenibilidad**: Cambios en permisos se reflejan inmediatamente en UI

## Testing

Para verificar que los permisos están funcionando correctamente:

1. Abrir el archivo `test_permisos_ui.php` (si estás autenticado)
2. Verá una lista de todos los permisos de su usuario
3. Verá qué botones se mostrarán en cada módulo

## Commit
- **Commit**: 125f660
- **Mensaje**: feat: Implementar visibilidad de botones basada en permisos de usuario

## Notas Técnicas

La función `tienePermiso($modulo, $accion)` (en `includes/auth.php`):
1. Primero verifica si el usuario tiene permisos personalizados
2. Si no, utiliza los permisos del rol
3. Retorna true/false según corresponda

Esto se ejecuta **en tiempo de renderizado del HTML**, antes de enviar la respuesta JSON al frontend.

No hay lógica JavaScript necesaria; los botones simplemente no se incluyen en el HTML si el usuario no tiene permisos.
