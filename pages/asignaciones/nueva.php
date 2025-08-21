<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Obtener datos base para los select
$stmt = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad");
$localidades = $stmt->fetchAll();

// Obtener todas las sedes para mostrar en el select
$stmt = $conexion->query("SELECT id_sede, nombre_sede, id_localidad FROM sedes ORDER BY nombre_sede");
$todas_sedes = $stmt->fetchAll();

// Obtener todas las áreas
$stmt = $conexion->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area");
$todas_areas = $stmt->fetchAll();

// Obtener insumos disponibles para mostrar en la tabla
$stmt = $conexion->query("SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, i.cantidad, ps.nombre_punto AS punto_stock 
                          FROM insumos i 
                          LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
                          WHERE i.estado = 'Disponible' 
                          AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
                          ORDER BY i.nombre_insumo");
$insumos_disponibles = $stmt->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conexion->beginTransaction();

        $idsInsumo = isset($_POST['id_insumo']) ? (array)$_POST['id_insumo'] : [];
        if (empty($idsInsumo)) { throw new Exception('Debe seleccionar al menos un insumo'); }

        $numero_remito = generarNumeroRemito();

        // Cabecera
        $stmtRemito = $conexion->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?,?,?,?,?,?,?)");
        $stmtRemito->execute([
            $numero_remito,
            $_POST['id_sede'],
            $_POST['id_area_asignada'],
            $_POST['nombre_persona_asignada'],
            $_POST['apellido_persona_asignada'],
            $_POST['fecha_asignacion'],
            $_POST['observaciones'] ?: null
        ]);
        $idRemito = (int)$conexion->lastInsertId();
        $stmtInsertDet = $conexion->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)");

        $cantidadesVarios = isset($_POST['cantidad_varios']) && is_array($_POST['cantidad_varios']) ? $_POST['cantidad_varios'] : [];

        foreach ($idsInsumo as $idInsumo) {
            $stmt = $conexion->prepare("SELECT tipo_insumo, cantidad FROM insumos WHERE id_insumo = ? FOR UPDATE");
            $stmt->execute([$idInsumo]);
            $insumo = $stmt->fetch();
            if (!$insumo) { throw new Exception('Insumo no encontrado: ' . (int)$idInsumo); }
            if ($insumo['tipo_insumo'] !== 'Varios' && (int)$insumo['cantidad'] < 1) { throw new Exception('Insumo sin disponibilidad: ' . (int)$idInsumo); }

            $repeticiones = 1;
            if ($insumo['tipo_insumo'] === 'Varios') {
                $solicitada = isset($cantidadesVarios[$idInsumo]) ? (int)$cantidadesVarios[$idInsumo] : 1;
                if ($solicitada < 1) { $solicitada = 1; }
                if ($solicitada > (int)$insumo['cantidad']) {
                    throw new Exception('Cantidad solicitada supera stock disponible en insumo ID ' . (int)$idInsumo);
                }
                $repeticiones = $solicitada;
            }

            $stmtInsertDet->execute([$idRemito, $idInsumo, $repeticiones]);

            if ($insumo['tipo_insumo'] === 'Varios') {
                $nueva_cantidad = max(0, (int)$insumo['cantidad'] - $repeticiones);
                $nuevo_estado = ($nueva_cantidad > 0) ? 'Disponible' : 'Asignado';
                if ($nueva_cantidad > 0) {
                    $conexion->prepare("UPDATE insumos SET cantidad = ?, estado = ?, id_sede_actual = ?, id_area_asignacion_actual = ? WHERE id_insumo = ?")
                             ->execute([$nueva_cantidad, $nuevo_estado, $_POST['id_sede'], $_POST['id_area_asignada'], $idInsumo]);
                } else {
                    // Sin stock remanente: marcar asignado y limpiar punto de stock
                    $conexion->prepare("UPDATE insumos SET cantidad = ?, estado = ?, id_sede_actual = ?, id_area_asignacion_actual = ?, id_punto_stock_actual = NULL WHERE id_insumo = ?")
                             ->execute([$nueva_cantidad, $nuevo_estado, $_POST['id_sede'], $_POST['id_area_asignada'], $idInsumo]);
                }
            } else {
                // Unitarios: al asignar, limpiar punto de stock
                $conexion->prepare("UPDATE insumos SET estado = 'Asignado', id_sede_actual = ?, id_area_asignacion_actual = ?, id_punto_stock_actual = NULL WHERE id_insumo = ?")
                         ->execute([$_POST['id_sede'], $_POST['id_area_asignada'], $idInsumo]);
            }
        }

        $conexion->commit();

        $_SESSION['mensaje'] = "Asignación creada correctamente. Remito: " . $numero_remito;
        $_SESSION['tipo_mensaje'] = "success";
        header("Location: ../reportes/remito.php?remito=" . urlencode($numero_remito));
        exit;

    } catch (Exception $e) {
        if ($conexion->inTransaction()) { $conexion->rollBack(); }
        $_SESSION['mensaje'] = "Error al crear asignación: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-plus me-2"></i>Nueva Asignación
            </h1>
            <a href="listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>Información de la Asignación
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" id="formAsignacion" class="needs-validation" novalidate>
            <div id="form-contenido">
            <div class="row">
                <!-- Selección de localidad, sede y área -->
                <div class="col-md-6">
                    <h6 class="mb-3">Ubicación</h6>

                    <div class="mb-3">
                        <label for="id_localidad" class="form-label">Localidad *</label>
                        <select class="form-select" id="id_localidad" name="id_localidad" required>
                            <option value="">Seleccione una localidad</option>
                            <?php foreach ($localidades as $localidad): ?>
                                <option value="<?php echo $localidad['id_localidad']; ?>">
                                    <?php echo htmlspecialchars($localidad['nombre_localidad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una localidad</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_sede" class="form-label">Sede *</label>
                        <select class="form-select" id="id_sede" name="id_sede" required>
                            <option value="">Seleccione una sede</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una sede</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_area_asignada" class="form-label">Área *</label>
                        <select class="form-select" id="id_area_asignada" name="id_area_asignada" required>
                            <option value="">Seleccione un área</option>
                            <?php foreach ($todas_areas as $area): ?>
                                <option value="<?php echo $area['id_area']; ?>">
                                    <?php echo htmlspecialchars($area['nombre_area']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una área</div>
                    </div>
                </div>
                
                <!-- Información de la persona -->
                <div class="col-md-6">
                    <h6 class="mb-3">Persona Asignada</h6>
                    
                    <div class="mb-3">
                        <label for="nombre_persona_asignada" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre_persona_asignada" 
                               name="nombre_persona_asignada" required>
                        <div class="invalid-feedback">El nombre es obligatorio</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="apellido_persona_asignada" class="form-label">Apellido *</label>
                        <input type="text" class="form-control" id="apellido_persona_asignada" 
                               name="apellido_persona_asignada" required>
                        <div class="invalid-feedback">El apellido es obligatorio</div>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_asignacion" class="form-label">Fecha de Asignación *</label>
                        <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Debe seleccionar una fecha</div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ocultarFormulario()">
                                <i class="fas fa-chevron-up me-1"></i>Ocultar formulario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Observaciones -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Observaciones adicionales sobre la asignación..."></textarea>
                </div>
            </div>
            
            <!-- Línea divisoria -->
            <hr class="my-4 border-2 border-primary">
            </div><!-- /#form-contenido -->
            
            <!-- Selección de insumos -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Selección de Insumos</h6>
                        <button type="button" id="btn-mostrar-formulario" class="btn btn-sm btn-outline-secondary" style="display: none;" onclick="mostrarFormulario()">
                            <i class="fas fa-chevron-down me-1"></i>Mostrar formulario
                        </button>
                    </div>
                    
                    <!-- Filtros para insumos -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filtro_tipo" class="form-label">Filtrar por Tipo</label>
                            <select class="form-select" id="filtro_tipo">
                                <option value="">Todos los tipos</option>
                                <option value="Varios">Varios</option>
                                <option value="PC Completa">PC Completa</option>
                                <option value="Notebook">Notebook</option>
                                <option value="Impresora">Impresora</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Escaner">Escaner</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID...">
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-grid gap-2 w-100">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                                    <i class="fas fa-times me-1"></i>Limpiar Filtros
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deseleccionarTodos()">
                                    <i class="fas fa-times me-1"></i>Deseleccionar Todo
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de insumos disponibles -->
                    <div class="table-responsive">
                        <table class="table table-striped datatable tabla-asignacion" id="tablaInsumos">
                            <thead>
                                <tr>
                                    <th>Nombre Insumo</th>
                                    <th>Cantidad</th>
                                    <th>Punto de Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($insumos_disponibles as $insumo): ?>
                                    <tr class="fila-insumo" 
                                        data-tipo="<?php echo htmlspecialchars($insumo['tipo_insumo']); ?>"
                                        data-texto="<?php echo strtolower(htmlspecialchars($insumo['nombre_insumo'] . ' ' . ($insumo['numero_serie'] ?: '') . ' ' . ($insumo['id_fisico'] ?: ''))); ?>">
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($insumo['nombre_insumo']); ?></strong>
                                                <?php if ($insumo['numero_serie']): ?>
                                                    <br><small class="text-muted">S/N: <?php echo htmlspecialchars($insumo['numero_serie']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($insumo['id_fisico']): ?>
                                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($insumo['id_fisico']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo ($insumo['tipo_insumo'] === 'Varios') ? (int)$insumo['cantidad'] : 1; ?>
                                        </td>
                                        <td>
                                            <?php echo $insumo['punto_stock'] ? htmlspecialchars($insumo['punto_stock']) : '<span class="text-muted">Sin punto</span>'; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="id_insumo[]" value="<?php echo $insumo['id_insumo']; ?>" 
                                                       class="hidden-insumo-input" 
                                                       data-tipo="<?php echo htmlspecialchars($insumo['tipo_insumo']); ?>"
                                                       data-max="<?php echo ($insumo['tipo_insumo'] === 'Varios') ? (int)$insumo['cantidad'] : 1; ?>"
                                                       disabled
                                                       style="display: none;">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" 
                                                        data-insumo-id="<?php echo $insumo['id_insumo']; ?>"
                                                        onclick="toggleSeleccionInsumo(<?php echo $insumo['id_insumo']; ?>)">
                                                    <i class="fas fa-plus"></i><span> Seleccionar</span>
                                                </button>
                                                <?php if ($insumo['tipo_insumo'] === 'Varios' && $insumo['cantidad'] > 1): ?>
                                                    <div class="cantidad-input" style="display: none;">
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="cantidad_varios[<?php echo $insumo['id_insumo']; ?>]" 
                                                               min="1" max="<?php echo $insumo['cantidad']; ?>" value="1" 
                                                               style="width: 80px;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="button" class="btn btn-primary" onclick="mostrarModalConfirmacion()">
                            <i class="fas fa-eye me-2"></i>Revisar y Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalConfirmacionLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirmar Asignación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Revise los datos antes de confirmar la asignación:</strong>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Ubicación
                        </h6>
                        <div class="mb-2">
                            <strong>Localidad:</strong> <span id="modal-localidad"></span>
                        </div>
                        <div class="mb-2">
                            <strong>Sede:</strong> <span id="modal-sede"></span>
                        </div>
                        <div class="mb-2">
                            <strong>Área:</strong> <span id="modal-area"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-user me-2"></i>Persona Asignada
                        </h6>
                        <div class="mb-2">
                            <strong>Nombre:</strong> <span id="modal-nombre"></span>
                        </div>
                        <div class="mb-2">
                            <strong>Apellido:</strong> <span id="modal-apellido"></span>
                        </div>
                        <div class="mb-2">
                            <strong>Fecha:</strong> <span id="modal-fecha"></span>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-boxes me-2"></i>Insumos Seleccionados
                        </h6>
                        <div id="modal-insumos" class="table-responsive">
                            <!-- Los insumos se cargarán dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3" id="modal-observaciones-container" style="display: none;">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-comment me-2"></i>Observaciones
                        </h6>
                        <div class="alert alert-light">
                            <span id="modal-observaciones"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" onclick="confirmarAsignacion()">
                    <i class="fas fa-check me-2"></i>Confirmar Asignación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cargar sedes cuando se selecciona una localidad
    $('#id_localidad').on('change', function() {
        const localidadId = $(this).val();
        $('#id_sede').html('<option value="">Seleccione una sede</option>');
        
        if (localidadId) {
            const sedesFiltradas = <?php echo json_encode($todas_sedes); ?>.filter(sede => sede.id_localidad == localidadId);
            sedesFiltradas.forEach(sede => {
                $('#id_sede').append(`<option value="${sede.id_sede}">${sede.nombre_sede}</option>`);
            });
        }
    });

    // Filtros para la tabla de insumos
    $('#filtro_tipo, #filtro_busqueda').on('input change', function() {
        filtrarInsumos();
        actualizarContadorSeleccionados();
    });

    // Resaltar fila al hacer hover
    $(document).on('mouseenter', '.fila-insumo', function() {
        $(this).addClass('highlight');
    }).on('mouseleave', '.fila-insumo', function() {
        $(this).removeClass('highlight');
    });

    // Validación del formulario
    $('#formAsignacion').on('submit', function(e) {
        const insumosSeleccionados = $('.hidden-insumo-input:not(:disabled)').length;
        if (insumosSeleccionados === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un insumo');
            return false;
        }
        
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Inicializar contador
    actualizarContadorSeleccionados();
});

// Función para filtrar insumos
function filtrarInsumos() {
    const tipo = $('#filtro_tipo').val().toLowerCase();
    const busqueda = $('#filtro_busqueda').val().toLowerCase();

    $('.fila-insumo').each(function() {
        const $fila = $(this);
        const tipoFila = $fila.data('tipo').toLowerCase();
        const textoFila = $fila.data('texto');
        const isSelected = !$fila.find('.hidden-insumo-input').prop('disabled');

        let mostrar = true;

        if (!isSelected) {
            if (tipo && tipoFila !== tipo) mostrar = false;
            if (busqueda && !textoFila.includes(busqueda)) mostrar = false;
        }

        $fila.toggle(mostrar);
    });
}

// Función para alternar la selección de un insumo
function toggleSeleccionInsumo(idInsumo) {
    const $hiddenInput = $(`.hidden-insumo-input[value="${idInsumo}"]`);
    const $button = $(`.btn-seleccionar[data-insumo-id="${idInsumo}"]`);
    const $fila = $button.closest('tr');
    const isSelected = !$hiddenInput.prop('disabled');

    if (isSelected) {
        // Deseleccionar
        $hiddenInput.prop('disabled', true);
        $button.removeClass('btn-primary').addClass('btn-outline-primary');
        $button.html('<i class="fas fa-plus"></i> Seleccionar');
        $fila.removeClass('highlight');
        
        // Ocultar y deshabilitar input de cantidad si existe
        $fila.find('.cantidad-input').hide();
        $fila.find('.cantidad-input input').prop('disabled', true);
    } else {
        // Seleccionar
        $hiddenInput.prop('disabled', false);
        $button.removeClass('btn-outline-primary').addClass('btn-primary');
        $button.html('<i class="fas fa-minus"></i> Deseleccionar');
        
        // Resaltar la fila brevemente
        $fila.addClass('highlight');
        setTimeout(() => $fila.removeClass('highlight'), 1000);
        
        // Mostrar input de cantidad si es tipo "Varios"
        const tipo = $hiddenInput.data('tipo');
        const max = parseInt($hiddenInput.data('max') || 1);
        if (tipo === 'Varios' && max > 1) {
            $fila.find('.cantidad-input').show();
            $fila.find('.cantidad-input input').prop('disabled', false);
        }
    }
    
    actualizarContadorSeleccionados();
}

// Función para actualizar cantidades de insumos tipo "Varios"
function actualizarCantidades() {
    // Ocultar todos los inputs de cantidad primero
    $('.cantidad-input').hide();
    
    // Mostrar inputs de cantidad solo para insumos tipo "Varios" seleccionados
    $('.hidden-insumo-input:not(:disabled)').each(function() {
        const $hiddenInput = $(this);
        const tipo = $hiddenInput.data('tipo');
        const max = parseInt($hiddenInput.data('max') || 1);
        
        if (tipo === 'Varios' && max > 1) {
            const $fila = $hiddenInput.closest('tr');
            const $cantidadInput = $fila.find('.cantidad-input');
            $cantidadInput.show();
            $cantidadInput.find('input').prop('disabled', false);
        }
    });
}

// Función para actualizar contador de insumos seleccionados
function actualizarContadorSeleccionados() {
    // Contador no requerido
    return;
}

// Función para limpiar filtros
function limpiarFiltros() {
    $('#filtro_tipo').val('');
    $('#filtro_busqueda').val('');
    filtrarInsumos();
    actualizarContadorSeleccionados();
}

// Función para deseleccionar todos
function deseleccionarTodos() {
    $('.hidden-insumo-input').prop('disabled', true);
    $('.btn-seleccionar').removeClass('btn-primary').addClass('btn-outline-primary');
    $('.btn-seleccionar').html('<i class="fas fa-plus"></i> Seleccionar');
    $('.cantidad-input').hide();
    $('.cantidad-input input').prop('disabled', true);
    $('.fila-insumo').removeClass('highlight');
    actualizarContadorSeleccionados();
}

// Función para mostrar el modal de confirmación
function mostrarModalConfirmacion() {
    // Validar formulario
    const form = document.getElementById('formAsignacion');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    // Validar que hay insumos seleccionados
    const insumosSeleccionados = $('.hidden-insumo-input:not(:disabled)').length;
    if (insumosSeleccionados === 0) {
        alert('Debe seleccionar al menos un insumo');
        return;
    }
    
    // Recopilar datos del formulario
    const localidad = $('#id_localidad option:selected').text();
    const sede = $('#id_sede option:selected').text();
    const area = $('#id_area_asignada option:selected').text();
    const nombre = $('#nombre_persona_asignada').val();
    const apellido = $('#apellido_persona_asignada').val();
    const fecha = $('#fecha_asignacion').val();
    const observaciones = $('#observaciones').val();
    
    // Poblar datos en el modal
    $('#modal-localidad').text(localidad);
    $('#modal-sede').text(sede);
    $('#modal-area').text(area);
    $('#modal-nombre').text(nombre);
    $('#modal-apellido').text(apellido);
    $('#modal-fecha').text(fecha);
    
    // Mostrar/ocultar observaciones
    if (observaciones.trim()) {
        $('#modal-observaciones').text(observaciones);
        $('#modal-observaciones-container').show();
    } else {
        $('#modal-observaciones-container').hide();
    }
    
    // Generar tabla de insumos seleccionados
    let tablaInsumos = `
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th>Insumo</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Ubicación Actual</th>
                </tr>
            </thead>
            <tbody>`;
    
    $('.hidden-insumo-input:not(:disabled)').each(function() {
        const $hiddenInput = $(this);
        const $fila = $hiddenInput.closest('tr');
        const nombreInsumo = $fila.find('td:nth-child(1) strong').text();
        const tipo = $hiddenInput.data('tipo');
        const ubicacion = $fila.find('td:nth-child(4)').text().trim();
        
        let cantidad = '1';
        if (tipo === 'Varios') {
            const cantidadInput = $fila.find(`input[name="cantidad_varios[${$hiddenInput.val()}]"]`);
            cantidad = cantidadInput.length ? cantidadInput.val() : '1';
        }
        
        tablaInsumos += `
            <tr>
                <td><strong>${nombreInsumo}</strong></td>
                <td><span class="badge bg-info">${tipo}</span></td>
                <td><span class="badge bg-success">${cantidad}</span></td>
                <td><small>${ubicacion || 'Sin ubicación'}</small></td>
            </tr>`;
    });
    
    tablaInsumos += '</tbody></table>';
    $('#modal-insumos').html(tablaInsumos);
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    modal.show();
}

// Función para confirmar la asignación
function confirmarAsignacion() {
    // Cerrar el modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
    modal.hide();
    
    // Enviar el formulario
    $('#formAsignacion').submit();
}

// Ocultar/mostrar formulario
function ocultarFormulario() {
    $('#form-contenido').hide();
    $('#btn-mostrar-formulario').show();
}
function mostrarFormulario() {
    $('#form-contenido').show();
    $('#btn-mostrar-formulario').hide();
}
</script>

<?php include '../../includes/footer.php'; ?> 