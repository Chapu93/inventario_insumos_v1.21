<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Detectar si es edición
$esEdicion = false;
$idLicitacion = null;
$licitacionData = null;
$insumosExistentes = [];

if (isset($_GET['id']) && $_GET['id']) {
    $idLicitacion = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM licitaciones WHERE id_licitacion=?');
    $stmt->execute([$idLicitacion]);
    $licitacionData = $stmt->fetch();
    
    if ($licitacionData) {
        $esEdicion = true;
        $ins = $db->prepare('SELECT id_insumo FROM insumos WHERE id_licitacion=?');
        $ins->execute([$idLicitacion]);
        $insumosExistentes = array_column($ins->fetchAll(), 'id_insumo');
    }
}

// Obtener todos los insumos disponibles (sin licitación asignada) y los de esta licitación si estamos editando
$sqlInsumos = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, i.cantidad, 
               ps.nombre_punto AS punto_stock
               FROM insumos i
               LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock
               WHERE (i.id_licitacion IS NULL" . ($esEdicion ? " OR i.id_licitacion = ?" : "") . ")
               AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
               ORDER BY i.nombre_insumo";

if ($esEdicion) {
    $stmt = $db->prepare($sqlInsumos);
    $stmt->execute([$idLicitacion]);
} else {
    $stmt = $db->query($sqlInsumos);
}
$insumos = $stmt->fetchAll();

// POST para crear/actualizar la licitación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        
        $db->beginTransaction();
        
        $codExpediente = trim($_POST['cod_expediente'] ?? '');
        $fechaFin = $_POST['fecha_finalizacion'] ?: null;
        $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
        $insumos = isset($_POST['id_insumo']) && is_array($_POST['id_insumo']) ? array_map('intval', $_POST['id_insumo']) : [];
        $cantidades = isset($_POST['cantidad_varios']) && is_array($_POST['cantidad_varios']) ? $_POST['cantidad_varios'] : [];
        
        if ($codExpediente === '') {
            throw new Exception('El código de expediente es obligatorio');
        }
        
        if (empty($insumos)) {
            throw new Exception('Debe seleccionar al menos un insumo');
        }
        
        if ($esEdicion && $idLicitacion) {
            // Actualizar licitación existente
            $stmt = $db->prepare('UPDATE licitaciones SET cod_expediente=?, descripcion=?, fecha_finalizacion=? WHERE id_licitacion=?');
            $stmt->execute([$codExpediente, $descripcion, $fechaFin, $idLicitacion]);
            $id = $idLicitacion;
        } else {
            // Crear nueva licitación
            $stmt = $db->prepare('INSERT INTO licitaciones (cod_expediente, descripcion, fecha_finalizacion) VALUES (?,?,?)');
            $stmt->execute([$codExpediente, $descripcion, $fechaFin]);
            $id = (int)$db->lastInsertId();
        }
        
        // Desasignar insumos actuales de esta licitación
        $db->prepare('UPDATE insumos SET id_licitacion=NULL WHERE id_licitacion=?')->execute([$id]);
        
        // Asignar los nuevos insumos seleccionados
        if (!empty($insumos)) {
            $in = implode(',', array_fill(0, count($insumos), '?'));
            $params = array_merge([$id], $insumos, [$id]);
            $db->prepare("UPDATE insumos SET id_licitacion=? WHERE id_insumo IN ($in) AND (id_licitacion IS NULL OR id_licitacion=?)")
               ->execute($params);
        }
        
        $db->commit();
        $_SESSION['mensaje'] = $esEdicion ? 'Licitación actualizada correctamente' : 'Licitación creada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/insumos/licitaciones_listar.php');
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-file-signature me-2"></i>
                <?php echo $esEdicion ? 'Editar Licitación' : 'Nueva Licitación'; ?>
            </h4>
            <a href="licitaciones_listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
</div>

<!-- Stepper visual (mismo estilo que asignaciones) -->
<div class="stepper">
    <div class="step step-1 active"><span class="circle">1</span><span>Datos de Licitación</span></div>
    <div class="divider"></div>
    <div class="step step-2"><span class="circle">2</span><span>Selección de Insumos</span></div>
</div>

<div id="licitacion-pasos">
    <div class="card">
        <div class="card-body">
            <form method="POST" id="formPasos" class="needs-validation" novalidate>
                <?php echo csrf_input(); ?>
                <?php if ($esEdicion && $idLicitacion): ?>
                    <input type="hidden" name="id_licitacion" value="<?php echo $idLicitacion; ?>">
                <?php endif; ?>

                <!-- Paso 1: Datos de la Licitación -->
                <div id="paso1">
                    <div class="row justify-content-center">
                        <div class="col-lg-10 col-xl-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3 section-title">Información de la Licitación</h6>
                                    <div class="mb-3">
                                        <label for="cod_expediente" class="form-label">
                                            Código de Expediente <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="cod_expediente" 
                                            name="cod_expediente" 
                                            required
                                            value="<?php echo $esEdicion && $licitacionData ? htmlspecialchars($licitacionData['cod_expediente']) : ''; ?>"
                                            placeholder="Ej: EXP-2025-001"
                                        >
                                        <div class="invalid-feedback">El código de expediente es obligatorio</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_finalizacion" class="form-label">
                                            Fecha de Finalización
                                        </label>
                                        <input 
                                            type="date" 
                                            class="form-control" 
                                            id="fecha_finalizacion" 
                                            name="fecha_finalizacion"
                                            value="<?php echo $esEdicion && $licitacionData && $licitacionData['fecha_finalizacion'] ? $licitacionData['fecha_finalizacion'] : ''; ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-3 section-title">Descripción</h6>
                                    <textarea 
                                        class="form-control" 
                                        id="descripcion" 
                                        name="descripcion" 
                                        rows="6"
                                        placeholder="Descripción detallada de la licitación..."
                                    ><?php echo $esEdicion && $licitacionData && $licitacionData['descripcion'] ? htmlspecialchars($licitacionData['descripcion']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2 justify-content-center">
                        <div class="col-lg-10 col-xl-8">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" id="btnSiguiente">
                                    <i class="fas fa-arrow-right me-2"></i>Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Selección de Insumos -->
                <div id="paso2" style="display:none;">
                    <div class="row justify-content-center">
                        <div class="col-lg-11">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Buscar</label>
                                            <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID...">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tipo de Insumo</label>
                                            <select class="form-select form-select-sm" id="filtro_tipo" style="min-width: 280px;">
                                                <option value="">Todos los tipos</option>
                                                <option value="Varios">Varios</option>
                                                <option value="PC Completa">PC Completa</option>
                                                <option value="Notebook">Notebook</option>
                                                <option value="Impresora">Impresora</option>
                                                <option value="Monitor">Monitor</option>
                                                <option value="Escaner">Escaner</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros" title="Limpiar filtros" aria-label="Limpiar filtros">
                                                <i class="fas fa-eraser" aria-hidden="true"></i>
                                            </button>
                                            <a href="<?php echo app_base_url(); ?>/pages/insumos/agregar.php?from=licitacion" id="btnNuevoInsumo" class="btn btn-success btn-sm" title="Nuevo Insumo">
                                                <i class="fas fa-plus" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary" id="contadorSeleccion">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarFiltrados()" title="Seleccionar filtrados" aria-label="Seleccionar filtrados">
                                            <i class="fas fa-check-double" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deseleccionarTodos()" title="Deseleccionar todo" aria-label="Deseleccionar todo">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-flat table-hover" id="tablaInsumos">
                                            <tbody>
                                                <?php foreach ($insumos as $ins): 
                                                    $yaSeleccionado = in_array($ins['id_insumo'], $insumosExistentes);
                                                ?>
                                                <tr class="fila-insumo <?php echo $yaSeleccionado ? 'fila-seleccionada' : ''; ?>" 
                                                    data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" 
                                                    data-texto="<?php echo strtolower(htmlspecialchars($ins['nombre_insumo'] . ' ' . ($ins['numero_serie'] ?: '') . ' ' . ($ins['id_fisico'] ?: ''))); ?>">
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($ins['nombre_insumo']); ?></strong>
                                                            <?php if ($ins['numero_serie']): ?>
                                                                <div class="text-muted small">S/N: <?php echo htmlspecialchars($ins['numero_serie']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($ins['id_fisico']): ?>
                                                                <div class="text-muted small">ID: <?php echo htmlspecialchars($ins['id_fisico']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($ins['tipo_insumo']); ?></span>
                                                    </td>
                                                    <td><?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?></td>
                                                    <td><?php echo $ins['punto_stock'] ? htmlspecialchars($ins['punto_stock']) : '<span class="text-muted">Sin punto</span>'; ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                                                            <input type="hidden" name="id_insumo[]" value="<?php echo $ins['id_insumo']; ?>" class="hidden-insumo-input" data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" data-max="<?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?>" <?php echo $yaSeleccionado ? '' : 'disabled'; ?> style="display:none;">
                                                            <?php if ($ins['tipo_insumo'] === 'Varios' && $ins['cantidad'] > 1): ?>
                                                            <div class="cantidad-input" style="<?php echo $yaSeleccionado ? '' : 'display:none;'; ?>">
                                                                <?php $cid = 'cantidad_varios_' . (int)$ins['id_insumo']; ?>
                                                                <label for="<?php echo $cid; ?>" class="small text-muted mb-0">Cant.</label>
                                                                <input id="<?php echo $cid; ?>" type="number" class="form-control form-control-sm" name="cantidad_varios[<?php echo $ins['id_insumo']; ?>]" min="1" max="<?php echo (int)$ins['cantidad']; ?>" value="1" style="width:84px;">
                                                            </div>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm <?php echo $yaSeleccionado ? 'btn-primary' : 'btn-outline-primary'; ?> btn-seleccionar" data-insumo-id="<?php echo $ins['id_insumo']; ?>" onclick="toggleSeleccionInsumo(<?php echo $ins['id_insumo']; ?>)" title="<?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?>" aria-label="<?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?> insumo <?php echo htmlspecialchars($ins['nombre_insumo']); ?>">
                                                                <i class="fas <?php echo $yaSeleccionado ? 'fa-minus' : 'fa-plus'; ?>" aria-hidden="true"></i>
                                                                <span class="d-none d-sm-inline"> <?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?></span>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-secondary" id="btnVolver">
                                            <i class="fas fa-arrow-left me-1"></i>Volver
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="mostrarModalConfirmacion()">
                                            <i class="fas fa-eye me-1"></i>Revisar y Confirmar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalConfirmacionLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirmar Licitación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="fas fa-file-signature me-2"></i>Datos de la Licitación</h6>
                        <p class="mb-1"><strong>Código Expediente:</strong> <span id="m_codigo"></span></p>
                        <p class="mb-1"><strong>Fecha Finalización:</strong> <span id="m_fecha"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="fas fa-comment me-2"></i>Descripción</h6>
                        <div class="alert alert-light mb-0" id="m_descripcion" style="white-space: pre-wrap; max-height: 100px; overflow-y: auto;"></div>
                    </div>
                </div>
                <hr>
                <h6 class="text-primary mb-2"><i class="fas fa-boxes me-2"></i>Insumos Seleccionados</h6>
                <div id="m_insumos" class="table-responsive"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" onclick="confirmarLicitacion()">
                    <i class="fas fa-check me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
const BASE = '<?php echo app_base_url(); ?>';
const ES_EDICION = <?php echo $esEdicion ? 'true' : 'false'; ?>;

// Restaurar datos del paso 1 si volvemos de crear un insumo
function restaurarDatosPaso1() {
    try {
        const datos = localStorage.getItem('licitacion_paso1');
        if (datos) {
            const obj = JSON.parse(datos);
            if (obj.cod_expediente) document.getElementById('cod_expediente').value = obj.cod_expediente;
            if (obj.fecha_finalizacion) document.getElementById('fecha_finalizacion').value = obj.fecha_finalizacion;
            if (obj.descripcion) document.getElementById('descripcion').value = obj.descripcion;
        }
    } catch (e) {
        console.error('Error restaurando datos:', e);
    }
}

// Guardar datos del paso 1 antes de ir a crear insumo
function guardarDatosPaso1() {
    try {
        const datos = {
            cod_expediente: document.getElementById('cod_expediente').value,
            fecha_finalizacion: document.getElementById('fecha_finalizacion').value,
            descripcion: document.getElementById('descripcion').value
        };
        localStorage.setItem('licitacion_paso1', JSON.stringify(datos));
    } catch (e) {
        console.error('Error guardando datos:', e);
    }
}

// Interceptar click en botón de nuevo insumo
document.getElementById('btnNuevoInsumo')?.addEventListener('click', function(e) {
    guardarDatosPaso1();
});

// Al cargar la página, restaurar datos si volvemos de crear insumo
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from') === 'agregar') {
        restaurarDatosPaso1();
        // Limpiar el localStorage después de restaurar
        localStorage.removeItem('licitacion_paso1');
    }
    
    // Actualizar contador inicial
    actualizarContador();
});

// Paso 1 -> Paso 2
$('#btnSiguiente').on('click', function() {
    const form = document.getElementById('formPasos');
    const paso1 = document.getElementById('paso1');
    
    // Validar solo código de expediente (obligatorio)
    let valido = true;
    const codExp = document.getElementById('cod_expediente');
    if (!codExp || !codExp.value.trim()) {
        valido = false;
        codExp && codExp.classList.add('is-invalid');
    } else {
        codExp.classList.remove('is-invalid');
    }
    
    if (!valido) {
        paso1.classList.add('was-validated');
        return;
    }
    
    // Guardar datos por si acaso
    guardarDatosPaso1();
    
    // Limpiar validación
    paso1.classList.remove('was-validated');
    form.classList.remove('was-validated');
    
    $('#paso1').hide();
    $('#paso2').show();
    
    // Stepper activo
    $('.stepper .step').removeClass('active');
    $('.stepper .step-2').addClass('active');
    
    // Ordenar filas (seleccionados arriba)
    ordenarFilas();
});

// Paso 2 -> Paso 1
$('#btnVolver').on('click', function() {
    $('#paso2').hide();
    $('#paso1').show();
    $('.stepper .step').removeClass('active');
    $('.stepper .step-1').addClass('active');
});

// Toggle selección de insumo
function toggleSeleccionInsumo(id) {
    const fila = $(`tr.fila-insumo:has(.btn-seleccionar[data-insumo-id="${id}"])`);
    const btn = fila.find('.btn-seleccionar');
    const input = fila.find('.hidden-insumo-input');
    const cantInput = fila.find('.cantidad-input');
    
    if (input.prop('disabled')) {
        // Seleccionar
        input.prop('disabled', false);
        btn.removeClass('btn-outline-primary').addClass('btn-primary');
        btn.find('i').removeClass('fa-plus').addClass('fa-minus');
        btn.find('span').text(' Deseleccionar');
        btn.attr('title', 'Deseleccionar');
        fila.addClass('fila-seleccionada');
        
        if (cantInput.length) {
            cantInput.show();
        }
    } else {
        // Deseleccionar
        input.prop('disabled', true);
        btn.removeClass('btn-primary').addClass('btn-outline-primary');
        btn.find('i').removeClass('fa-minus').addClass('fa-plus');
        btn.find('span').text(' Seleccionar');
        btn.attr('title', 'Seleccionar');
        fila.removeClass('fila-seleccionada');
        
        if (cantInput.length) {
            cantInput.hide();
        }
    }
    
    actualizarContador();
    ordenarFilas();
}

// Actualizar contador
function actualizarContador() {
    const count = $('.hidden-insumo-input:not([disabled])').length;
    $('#contadorSeleccion').text(count);
}

// Ordenar filas (seleccionados arriba)
function ordenarFilas() {
    const tbody = $('#tablaInsumos tbody');
    const filas = tbody.find('tr.fila-insumo').toArray();
    
    filas.sort(function(a, b) {
        const aSeleccionada = $(a).hasClass('fila-seleccionada');
        const bSeleccionada = $(b).hasClass('fila-seleccionada');
        
        if (aSeleccionada && !bSeleccionada) return -1;
        if (!aSeleccionada && bSeleccionada) return 1;
        return 0;
    });
    
    tbody.empty();
    $.each(filas, function(idx, fila) {
        tbody.append(fila);
    });
}

// Filtrar insumos
function filtrarInsumos() {
    const busqueda = $('#filtro_busqueda').val().toLowerCase();
    const tipo = $('#filtro_tipo').val();
    
    $('tr.fila-insumo').each(function() {
        const $fila = $(this);
        const texto = $fila.data('texto') || '';
        const tipoFila = $fila.data('tipo') || '';
        
        let mostrar = true;
        
        if (tipo && tipoFila !== tipo) {
            mostrar = false;
        }
        
        if (busqueda && texto.indexOf(busqueda) === -1) {
            mostrar = false;
        }
        
        $fila.toggle(mostrar);
    });
}

$('#filtro_busqueda').on('input', filtrarInsumos);
$('#filtro_tipo').on('change', filtrarInsumos);

$('#btnLimpiarFiltros').on('click', function() {
    $('#filtro_busqueda').val('');
    $('#filtro_tipo').val('');
    filtrarInsumos();
});

// Seleccionar todos los filtrados
function seleccionarFiltrados() {
    $('tr.fila-insumo:visible').each(function() {
        const $fila = $(this);
        const input = $fila.find('.hidden-insumo-input');
        if (input.prop('disabled')) {
            const id = $fila.find('.btn-seleccionar').data('insumo-id');
            toggleSeleccionInsumo(id);
        }
    });
}

// Deseleccionar todos
function deseleccionarTodos() {
    $('tr.fila-insumo').each(function() {
        const $fila = $(this);
        const input = $fila.find('.hidden-insumo-input');
        if (!input.prop('disabled')) {
            const id = $fila.find('.btn-seleccionar').data('insumo-id');
            toggleSeleccionInsumo(id);
        }
    });
}

// Mostrar modal de confirmación
function mostrarModalConfirmacion() {
    const seleccionados = $('.hidden-insumo-input:not([disabled])');
    
    if (seleccionados.length === 0) {
        alert('Debe seleccionar al menos un insumo');
        return;
    }
    
    // Llenar datos de la licitación
    $('#m_codigo').text($('#cod_expediente').val() || '-');
    const fecha = $('#fecha_finalizacion').val();
    $('#m_fecha').text(fecha ? new Date(fecha + 'T00:00:00').toLocaleDateString('es-AR') : 'No especificada');
    $('#m_descripcion').text($('#descripcion').val() || 'Sin descripción');
    
    // Agrupar insumos por tipo
    const porTipo = {};
    seleccionados.each(function() {
        const $input = $(this);
        const $fila = $input.closest('tr');
        const tipo = $input.data('tipo');
        const nombre = $fila.find('td:first strong').text();
        const cantidad = $fila.find('input[type="number"]').val() || 1;
        
        if (!porTipo[tipo]) {
            porTipo[tipo] = [];
        }
        
        porTipo[tipo].push({ nombre, cantidad: parseInt(cantidad) });
    });
    
    // Renderizar tabla de insumos
    let html = '<table class="table table-sm table-striped"><tbody>';
    Object.keys(porTipo).sort().forEach(tipo => {
        html += `<tr><td colspan="2" class="fw-bold bg-light">${tipo} (${porTipo[tipo].length})</td></tr>`;
        porTipo[tipo].forEach(ins => {
            html += `<tr><td class="ps-4">${ins.nombre}</td><td class="text-end">`;
            if (tipo === 'Varios' && ins.cantidad > 1) {
                html += `<span class="badge bg-primary">Cant: ${ins.cantidad}</span>`;
            }
            html += '</td></tr>';
        });
    });
    html += '</tbody></table>';
    
    $('#m_insumos').html(html);
    
    new bootstrap.Modal(document.getElementById('modalConfirmacion')).show();
}

// Confirmar licitación
function confirmarLicitacion() {
    const seleccionados = $('.hidden-insumo-input:not([disabled])');
    
    if (seleccionados.length === 0) {
        alert('Debe seleccionar al menos un insumo');
        return;
    }
    
    document.getElementById('formPasos').submit();
}
</script>
