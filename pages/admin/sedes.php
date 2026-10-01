<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('sedes', 'ver');

$conexion = conectarDB();

// Asegurar columnas de responsable (segundo delegado)
try {
    $conexion->query("SELECT responsable_nombre, responsable_apellido, responsable_telefono FROM sedes LIMIT 1");
} catch (Exception $e) {
    try {
        $conexion->exec("ALTER TABLE sedes ADD COLUMN responsable_nombre VARCHAR(100) NULL AFTER delegado_telefono");
    } catch (Exception $e2) {}
    try {
        $conexion->exec("ALTER TABLE sedes ADD COLUMN responsable_apellido VARCHAR(100) NULL AFTER responsable_nombre");
    } catch (Exception $e3) {}
    try {
        $conexion->exec("ALTER TABLE sedes ADD COLUMN responsable_telefono VARCHAR(50) NULL AFTER responsable_apellido");
    } catch (Exception $e4) {}
}

// Procesar formulario de agregar/editar sede
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
                if (isset($_POST['accion'])) {
            if ($_POST['accion'] == 'agregar') {
                verificarPermiso('sedes', 'crear');
                // Añadimos el campo observaciones (puede ser NULL)
                $sql = "INSERT INTO sedes (nombre_sede, id_localidad, direccion, observaciones, delegado_nombre, delegado_apellido, delegado_telefono, responsable_nombre, responsable_apellido, responsable_telefono) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_sede'],
                    $_POST['id_localidad'],
                    ($_POST['direccion'] ?? null) ?: null,
                    ($_POST['observaciones'] ?? null) ?: null,
                    ($_POST['delegado_nombre'] ?? null) ?: null,
                    ($_POST['delegado_apellido'] ?? null) ?: null,
                    ($_POST['delegado_telefono'] ?? null) ?: null,
                    ($_POST['responsable_nombre'] ?? null) ?: null,
                    ($_POST['responsable_apellido'] ?? null) ?: null,
                    ($_POST['responsable_telefono'] ?? null) ?: null
                ]);
                
                $_SESSION['mensaje'] = "Sede agregada correctamente";
                $_SESSION['tipo_mensaje'] = "success";
                
            } elseif ($_POST['accion'] == 'editar') {
                verificarPermiso('sedes', 'editar');
                $id_sede = (int)$_POST['id_sede'];
                $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

                // Si se intenta desactivar, verificar que no tenga insumos asignados o pendientes de devolución
                if ($activo === 0) {
                    $stmtIns = $conexion->prepare("SELECT COUNT(*) FROM insumos WHERE id_sede_actual = ? AND estado != 'De Baja'");
                    $stmtIns->execute([$id_sede]);
                    $cantInsumos = (int)$stmtIns->fetchColumn();

                    $stmtRem = $conexion->prepare("
                        SELECT COALESCE(SUM(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0)), 0) 
                        FROM remitos_detalle rd 
                        JOIN remitos r ON rd.id_remito = r.id_remito 
                        WHERE r.id_sede = ?
                    ");
                    $stmtRem->execute([$id_sede]);
                    $cantRem = (int)$stmtRem->fetchColumn();

                    $pendientes = max($cantInsumos, $cantRem);
                    if ($pendientes > 0) {
                        $_SESSION['mensaje'] = "No se puede desactivar la sede porque aún cuenta con {$pendientes} insumo(s) asignado(s) o pendientes de devolución. Debe devolver la totalidad de los insumos antes de desactivarla.";
                        $_SESSION['tipo_mensaje'] = "danger";
                        header("Location: sedes.php");
                        exit;
                    }
                }

                // Actualizar incluyendo observaciones y estado activo
                $sql = "UPDATE sedes SET nombre_sede = ?, id_localidad = ?, direccion = ?, observaciones = ?, activo = ?, delegado_nombre = ?, delegado_apellido = ?, delegado_telefono = ?, responsable_nombre = ?, responsable_apellido = ?, responsable_telefono = ? WHERE id_sede = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_sede'],
                    $_POST['id_localidad'],
                    ($_POST['direccion'] ?? null) ?: null,
                    ($_POST['observaciones'] ?? null) ?: null,
                    $activo,
                    ($_POST['delegado_nombre'] ?? null) ?: null,
                    ($_POST['delegado_apellido'] ?? null) ?: null,
                    ($_POST['delegado_telefono'] ?? null) ?: null,
                    ($_POST['responsable_nombre'] ?? null) ?: null,
                    ($_POST['responsable_apellido'] ?? null) ?: null,
                    ($_POST['responsable_telefono'] ?? null) ?: null,
                    $id_sede
                ]);
                
                $_SESSION['mensaje'] = "Sede actualizada correctamente";
                $_SESSION['tipo_mensaje'] = "success";
            }
        }
        
        header("Location: sedes.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
}

// Obtener sedes con información de localidad, zona e insumos asignados
$sql = "SELECT s.*, l.nombre_localidad, z.nombre_zona,
        (SELECT COUNT(*) FROM insumos i WHERE i.id_sede_actual = s.id_sede AND i.estado != 'De Baja') AS insumos_actuales,
        (SELECT COALESCE(SUM(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0)), 0) 
         FROM remitos_detalle rd 
         JOIN remitos r ON rd.id_remito = r.id_remito 
         WHERE r.id_sede = s.id_sede) AS remitos_pendientes
        FROM sedes s 
        JOIN localidades l ON s.id_localidad = l.id_localidad 
        JOIN zonas z ON l.id_zona = z.id_zona 
        ORDER BY z.nombre_zona, l.nombre_localidad, s.nombre_sede";
$stmt = $conexion->query($sql);
$sedes = $stmt->fetchAll();

// Obtener localidades para el formulario
$stmt = $conexion->query("SELECT l.*, z.nombre_zona 
                          FROM localidades l 
                          JOIN zonas z ON l.id_zona = z.id_zona 
                          ORDER BY z.nombre_zona, l.nombre_localidad");
$localidades = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-building me-2"></i>Gestión de Sedes</h1>
        <?php if (tienePermiso('sedes', 'crear')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSede" onclick="$('#modalSedeTitle').text('Agregar Sede'); $('#accion').val('agregar'); $('#formSede')[0].reset();">
            <i class="fas fa-plus me-2"></i>Agregar Sede
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Sedes (<?php echo count($sedes); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($sedes)): ?>
            <div class="text-center py-4">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay sedes registradas</h5>
                <p class="text-muted">Agregue la primera sede para comenzar</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaSedes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Localidad</th>
                            <th>Delegado</th>
                            <th>Teléfono</th>
                            <th>Responsable</th>
                            <th>Teléfono Resp.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sedes as $sede): 
                            $isActivo = !empty($sede['activo']);
                            $insumosPendientes = max((int)($sede['insumos_actuales'] ?? 0), (int)($sede['remitos_pendientes'] ?? 0));
                            $puedeDesactivar = ($insumosPendientes === 0);
                        ?>
                            <tr id="row_sede_<?php echo $sede['id_sede']; ?>" class="<?php echo !$isActivo ? 'opacity-50' : ''; ?>">
                                <td><?php echo $sede['id_sede']; ?></td>
                                <td><strong><?php echo htmlspecialchars($sede['nombre_sede']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sede['nombre_localidad']); ?></td>
                                <td><?php echo htmlspecialchars(trim(($sede['delegado_nombre'] ?? '').' '.($sede['delegado_apellido'] ?? '')) ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($sede['delegado_telefono'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars(trim(($sede['responsable_nombre'] ?? '').' '.($sede['responsable_apellido'] ?? '')) ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($sede['responsable_telefono'] ?? '-'); ?></td>
                                <td id="status_sede_<?php echo $sede['id_sede']; ?>">
                                    <?php if ($isActivo): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (tienePermiso('sedes', 'editar')): ?>
                                        <button type="button" 
                                                class="btn btn-warning text-dark" 
                                                onclick="editarSede(<?php echo htmlspecialchars(json_encode($sede)); ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Editar sede" aria-label="Editar sede">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                        <?php if ($isActivo): ?>
                                            <?php if ($puedeDesactivar): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    onclick="toggleEstadoSede(<?php echo (int)$sede['id_sede']; ?>, '<?php echo htmlspecialchars(addslashes($sede['nombre_sede'])); ?>', 1)"
                                                    data-bs-toggle="tooltip" 
                                                    title="Desactivar sede">
                                                <i class="fas fa-toggle-on text-success"></i>
                                            </button>
                                            <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    disabled
                                                    data-bs-toggle="tooltip" 
                                                    title="No se puede desactivar: cuenta con <?php echo $insumosPendientes; ?> insumo(s) asignado(s) o pendientes de devolución">
                                                <i class="fas fa-toggle-on text-muted"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    onclick="toggleEstadoSede(<?php echo (int)$sede['id_sede']; ?>, '<?php echo htmlspecialchars(addslashes($sede['nombre_sede'])); ?>', 0)"
                                                    data-bs-toggle="tooltip" 
                                                    title="Activar sede">
                                                <i class="fas fa-toggle-off text-muted"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if (tienePermiso('sedes', 'ver')): ?>
                                        <a class="btn btn-info text-white" href="<?php echo app_base_url(); ?>/pages/admin/sede_detalle.php?id_localidad=<?php echo (int)$sede['id_localidad']; ?>&id_sede=<?php echo (int)$sede['id_sede']; ?>" data-bs-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles de la sede">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (tienePermiso('sedes', 'eliminar')): ?>
                                        <button type="button" 
                                                class="btn btn-danger" 
                                                onclick="eliminarItem(<?php echo $sede['id_sede']; ?>, 'sede')"
                                                data-bs-toggle="tooltip" 
                                                title="Eliminar sede" aria-label="Eliminar sede">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para agregar/editar sede -->
<div class="modal fade" id="modalSede" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-building me-2"></i><span id="modalSedeTitle">Agregar Sede</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formSede" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="accion" id="accion" value="agregar">
                    <input type="hidden" name="id_sede" id="id_sede">
                    
                    <div class="mb-3">
                        <label for="nombre_sede" class="form-label">Nombre de la Sede *</label>
                        <input type="text" class="form-control" id="nombre_sede" name="nombre_sede" required>
                        <div class="invalid-feedback">El nombre de la sede es obligatorio</div>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_localidad" class="form-label">Localidad *</label>
                        <select class="form-select" id="id_localidad" name="id_localidad" required>
                            <option value="">Seleccione una localidad</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una localidad</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Nombre Delegado</label>
                            <input type="text" class="form-control" id="delegado_nombre" name="delegado_nombre">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido Delegado</label>
                            <input type="text" class="form-control" id="delegado_apellido" name="delegado_apellido">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono Delegado</label>
                            <input type="text" class="form-control" id="delegado_telefono" name="delegado_telefono">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Nombre Responsable</label>
                            <input type="text" class="form-control" id="responsable_nombre" name="responsable_nombre">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido Responsable</label>
                            <input type="text" class="form-control" id="responsable_apellido" name="responsable_apellido">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono Responsable</label>
                            <input type="text" class="form-control" id="responsable_telefono" name="responsable_telefono">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2" id="grupo_estado_sede">
                        <div class="col-md-6">
                            <label for="sede_activo" class="form-label">Estado de la Sede</label>
                            <select class="form-select" id="sede_activo" name="activo">
                                <option value="1">Activa (operativa y visible en listados)</option>
                                <option value="0">Inactiva (desactivada, no seleccionable)</option>
                            </select>
                            <small id="help_sede_activo" class="text-danger d-none mt-1">
                                <i class="fas fa-exclamation-triangle me-1"></i><span id="help_sede_activo_text"></span>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarSede(sede) {
    $('#modalSedeTitle').text('Editar Sede');
    $('#accion').val('editar');
    $('#id_sede').val(sede.id_sede);
    $('#nombre_sede').val(sede.nombre_sede);
    $('#direccion').val(sede.direccion || '');
    $('#observaciones').val(sede.observaciones || '');
    $('#id_localidad').val(sede.id_localidad);
    $('#delegado_nombre').val(sede.delegado_nombre || '');
    $('#delegado_apellido').val(sede.delegado_apellido || '');
    $('#delegado_telefono').val(sede.delegado_telefono || '');
    $('#responsable_nombre').val(sede.responsable_nombre || '');
    $('#responsable_apellido').val(sede.responsable_apellido || '');
    $('#responsable_telefono').val(sede.responsable_telefono || '');
    $('#sede_activo').val(sede.activo !== undefined ? sede.activo : 1);

    var insumosPend = Math.max(parseInt(sede.insumos_actuales || 0), parseInt(sede.remitos_pendientes || 0));
    if (insumosPend > 0) {
        $('#sede_activo option[value="0"]').prop('disabled', true);
        $('#help_sede_activo_text').text('No se puede desactivar: cuenta con ' + insumosPend + ' insumo(s) asignado(s) o pendientes de devolución.');
        $('#help_sede_activo').removeClass('d-none');
    } else {
        $('#sede_activo option[value="0"]').prop('disabled', false);
        $('#help_sede_activo').addClass('d-none');
    }

    $('#modalSede').modal('show');
}

function eliminarItem(id, tipo) {
    showConfirm({
        titulo: 'Eliminar Sede',
        mensaje: '¿Está seguro de que desea eliminar esta sede?',
        icono: 'fa-trash-alt text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo app_base_url(); ?>/pages/admin/eliminar.php';
            
            var inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;
            
            var inputTipo = document.createElement('input');
            inputTipo.type = 'hidden';
            inputTipo.name = 'tipo';
            inputTipo.value = tipo;
            
            var inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = '_csrf';
            inputCsrf.value = '<?php echo csrf_token(); ?>';
            
            form.appendChild(inputId);
            form.appendChild(inputTipo);
            form.appendChild(inputCsrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Resetear modal al cerrar
$('#modalSede').on('hidden.bs.modal', function () {
    $('#modalSedeTitle').text('Agregar Sede');
    $('#accion').val('agregar');
    $('#id_sede').val('');
    $('#sede_activo').val('1');
    $('#sede_activo option[value="0"]').prop('disabled', false);
    $('#help_sede_activo').addClass('d-none');
    $('#formSede')[0].reset();
    $('#formSede').removeClass('was-validated');
});

// Toggle para activar/desactivar sede con diálogo de confirmación estándar
function toggleEstadoSede(idSede, nombreSede, esActiva) {
    var accion = esActiva ? 'desactivar' : 'activar';
    showConfirm({
        titulo: (esActiva ? 'Desactivar' : 'Activar') + ' Sede',
        mensaje: '¿Está seguro de que desea ' + accion + ' la sede "<strong>' + nombreSede + '</strong>"?',
        icono: esActiva ? 'fa-eye-slash text-warning' : 'fa-check-circle text-success',
        claseBoton: esActiva ? 'btn-warning' : 'btn-success',
        textoAceptar: esActiva ? 'Desactivar' : 'Activar',
        onConfirm: () => {
            var csrfToken = $('meta[name="csrf-token"]').attr('content') || '<?php echo csrf_token(); ?>';
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/sedes_toggle_estado.php',
                method: 'POST',
                data: {
                    id_sede: idSede,
                    _csrf: csrfToken
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        showToast(resp.data && resp.data.mensaje ? resp.data.mensaje : 'Estado actualizado', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        showToast((resp && resp.error) ? resp.error : 'Error al cambiar estado', 'error');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error de comunicación';
                    showToast(msg, 'error');
                }
            });
        }
    });
}

// Validación del formulario
$('#formSede').on('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    $(this).addClass('was-validated');
});

const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades(){
  return $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{
    const $loc = $('#id_localidad');
    $loc.html('<option value="">Seleccione una localidad</option>');
    if(r.success){ r.data.forEach(l=> $loc.append(`<option value="${l.id}">${l.nombre}</option>`)); }
  });
}

// Abrir modal con datos si llegan parámetros
document.addEventListener('DOMContentLoaded', function(){
  cargarLocalidades();
  const url = new URL(window.location.href);
  const qId = url.searchParams.get('id_sede');
  const qLoc = url.searchParams.get('id_localidad');
  if (qId) {
    try {
      const filas = <?php echo json_encode($sedes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
      const encontrada = filas.find(s => String(s.id_sede) === String(qId));
      if (encontrada) { editarSede(encontrada); }
      else { new bootstrap.Modal(document.getElementById('modalSede')).show(); if(qLoc){ $('#id_localidad').val(qLoc); } }
    } catch(e) { new bootstrap.Modal(document.getElementById('modalSede')).show(); if(qLoc){ $('#id_localidad').val(qLoc); } }
  }
});
</script>

<?php include '../../includes/footer.php'; ?>