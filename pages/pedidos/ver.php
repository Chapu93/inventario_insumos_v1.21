<?php
require_once '../../includes/config.php';

// Force UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');

requerirAutenticacion();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

// Obtener datos iniciales para validar permisos de vista
$usuario = obtenerUsuario();
$usuarioId = $usuario['id_usuario'];

include '../../includes/header.php';
?>

<meta charset="UTF-8">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div id="loader" class="text-center py-5">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
</div>

<div id="contenidoPedido" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <a href="listar.php" class="btn btn-outline-secondary me-3"><i class="fas fa-arrow-left"></i> Volver</a>
            <h2 class="mb-0 text-truncate" style="max-width: 600px;" id="tituloPedido">Cargando...</h2>
            <span class="badge ms-3 fs-6" id="badgeEstado"></span>
        </div>
        <div id="actionsContainer"></div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Detalles -->
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header" style="background-color: #d1e7dd;">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Detalles de la Solicitud</h5>
                </div>
                <div class="card-body">
                    <p class="card-text fs-5" id="descripcionPedido" style="white-space: pre-wrap;"></p>
                    <hr>
                    <div class="row text-muted">
                        <div class="col-md-6 mb-2"><strong>Tipo:</strong> <span id="tipoPedido"></span></div>
                        <div class="col-md-6 mb-2"><strong>Prioridad:</strong> <span id="prioridadPedido"></span></div>
                        <div class="col-md-12 mb-2" id="insumoRelacionadoContainer" style="display:none;">
                            <strong>Insumo Relacionado:</strong> <span id="insumoRelacionado" class="fw-bold"></span>
                        </div>
                        <div class="col-md-6 mb-2"><strong>Sede:</strong> <span id="sedePedido"></span></div>
                        <div class="col-md-6 mb-2"><strong>Área:</strong> <span id="areaPedido">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Solicitado por:</strong> <span id="solicitantePedido"></span></div>
                        <div class="col-md-6 mb-2"><strong>Fecha:</strong> <span id="fechaPedido"></span></div>
                        <div class="col-md-12 mt-2"><strong>Asignado a:</strong> <span id="asignadoPedido" class="badge bg-secondary">Sin Asignar</span></div>
                    </div>
                </div>
            </div>

            <!-- Informe Técnico (Si existe) -->
            <div id="informeContainer" class="card mb-4 shadow-sm" style="display:none;">
                <div class="card-header" style="background-color: #d1e7dd;">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Informe Técnico</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold">Diagnóstico:</h6>
                        <p id="infDiagnostico" class="bg-light p-2 rounded"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">Trabajo Realizado:</h6>
                        <p id="infTrabajo" class="bg-light p-2 rounded"></p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div><strong>Resultado:</strong> <span id="infResultado" class="badge"></span></div>
                        <a href="#" id="btnDescargarPdf" target="_blank" class="btn btn-outline-success btn-sm"><i class="fas fa-file-pdf me-2"></i>Descargar PDF</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Columna Derecha: Notas e Historial -->
        <div class="col-md-4">
            
            <!-- Notas -->
            <!-- Adjuntar Archivos -->
            <div class="card mb-3 shadow-sm" id="cardAdjuntarNota">
                <div class="card-header" style="background-color: #d1e7dd;">
                    <h5 class="mb-0"><i class="fas fa-paperclip me-2"></i>Adjuntar Nota (.pdf)</h5>
                </div>
                <div class="card-body bg-light">
                    <form id="formSubirNota" class="mb-3" enctype="multipart/form-data">
                         <label class="form-label small fw-bold">Subir Nota de Pedido:</label>
                         <div class="input-group">
                             <input type="file" class="form-control" name="nota_pdf" accept="application/pdf" required>
                             <button class="btn btn-primary" type="submit" title="Subir Nota"><i class="fas fa-upload"></i></button>
                         </div>
                         <div class="form-text small">Solo archivos .pdf. Al subirla, se habilitará la visualización.</div>
                    </form>
                </div>
            </div>



            <!-- Historial -->
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #d1e7dd;">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="listaHistorial" style="max-height: 400px; overflow-y: auto;">
                        <!-- Items historial -->
                    </ul>
                </div>
                <div class="card-footer bg-light">
                   <small class="text-muted">Los cambios de estado se registran automáticamente.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Completar / Informe -->
<div class="modal fade" id="modalInforme" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Completar Pedido e Informe Técnico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formInforme">
                    <input type="hidden" name="accion" value="completar">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Diagnóstico Inicial <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="diagnostico" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trabajo Realizado <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="trabajo" rows="4" required></textarea>
                        <div class="form-text">Detalle las tareas efectuadas para resolver la solicitud.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resultado Final</label>
                        <select class="form-select" name="resultado">
                            <option value="Solucionado">Solucionado</option>
                            <option value="Sin Solución">Sin Solución</option>
                            <option value="Requiere Repuestos">Requiere Repuestos</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="$('#formInforme').submit()">Guardar y Finalizar</button>
            </div>
        </div>
    </div>
</div>



<!-- Modal Editar Pedido -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre Solicitante</label>
                        <input type="text" class="form-control" name="solicitante_nombre" id="editSolicitante" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" id="editTipo" required>
                            <option value="Mantenimiento">Mantenimiento</option>
                            <option value="Reparación">Reparación</option>
                            <option value="Soporte">Soporte</option>
                            <option value="Pedido Insumo">Pedido Insumo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <select class="form-select" name="prioridad" id="editPrioridad">
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Insumo Relacionado</label>
                        <input type="text" class="form-control" name="insumo_manual" id="editInsumo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="editDescripcion" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="$('#formEditar').submit()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Variables Globales para JS -->
<script>
    const PEDIDO_ID = <?php echo $id; ?>;
    const USER_ID = <?php echo $usuarioId; ?>;
    const CURRENT_USER_ROL_ID = <?php echo obtenerUsuario()['id_rol']; ?>;
    const CSRF_TOKEN = '<?php echo csrf_token(); ?>';
    const PERMISOS = {
        gestionar: <?php echo tienePermiso('pedidos', 'gestionar') ? 'true' : 'false'; ?>,
        asignar: <?php echo tienePermiso('pedidos', 'asignar') ? 'true' : 'false'; ?>,
        eliminar: <?php echo tienePermiso('pedidos', 'eliminar') ? 'true' : 'false'; ?>
    };
</script>

<?php include '../../includes/footer.php'; ?>





<script>
const HISTORIAL_HEIGHT = 400;

// ===== FUNCTION DECLARATIONS (must be before $(function)) =====

function cargarPedido() {
    console.log('cargarPedido() called');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
        data: { accion: 'obtener', id: PEDIDO_ID },
        type: 'GET',
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(resp){
        console.log('cargarPedido response:', resp);
        if(!resp.success) {
            console.error('cargarPedido failed:', resp.error);
            alert('Error al cargar pedido: ' + resp.error);
            window.location.href = 'listar.php';
            return;
        }
        
        const pedido = resp.pedido || resp.data.pedido;
        const historial = resp.historial || resp.data.historial;
        const informe = resp.informe || resp.data.informe;

        if (!pedido) { 
            console.error('No pedido data in response:', resp);
            alert('Estructura de respuesta inválida'); 
            return; 
        }
        
        console.log('Rendering pedido:', pedido);
        
        // Store globally for edit modal
        window.currentPedido = pedido;

        try {
            renderPedido(pedido);
            console.log('renderPedido OK');
        } catch(e) {
            console.error('renderPedido error:', e);
        }
        
        try {
            renderHistorial(historial);
            console.log('renderHistorial OK');
        } catch(e) {
            console.error('renderHistorial error:', e);
        }
        
        try {
            renderInforme(informe, pedido);
            console.log('renderInforme OK');
        } catch(e) {
            console.error('renderInforme error:', e);
        }
        
        try {
            renderBotones(pedido);
            console.log('renderBotones OK');
        } catch(e) {
            console.error('renderBotones error:', e);
        }
        
        try {
            mostrarRechazo(pedido, historial);
            console.log('mostrarRechazo OK');
        } catch(e) {
            console.error('mostrarRechazo error:', e);
        }
        
        $('#loader').hide();
        $('#contenidoPedido').fadeIn();
        
        // Auto-modal
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('accion') === 'completar' && pedido.estado !== 'Completado' && pedido.estado !== 'Rechazado') {
             if (pedido.asignado_a == USER_ID || PERMISOS.gestionar) {
                 $('#modalInforme').modal('show');
                 window.history.replaceState({}, document.title, window.location.pathname + '?id=' + PEDIDO_ID);
             }
        }
        
        console.log('cargarPedido completed successfully');
    }}).fail(function(xhr, status, error) {
        console.error('cargarPedido AJAX failed:', xhr, status, error);
        alert('Error de conexion al cargar pedido');
    });
}

function renderPedido(p) {
    $('#tituloPedido').text('Pedido #' + p.id_pedido);
    $('#descripcionPedido').text(p.descripcion);
    $('#tipoPedido').html(`<span class="badge bg-dark">${p.tipo}</span>`);
    $('#prioridadPedido').html(`<span class="badge bg-${p.prioridad==='Alta'?'danger':(p.prioridad==='Baja'?'success':'warning text-dark')}">${p.prioridad}</span>`);
    
    // Insumo Relacionado
    if (p.insumo_relacionado) {
        $('#insumoRelacionadoContainer').show();
        $('#insumoRelacionado').text(p.insumo_relacionado);
    } else {
        $('#insumoRelacionadoContainer').hide();
    }

    $('#sedePedido').text(p.nombre_sede);
    
    // Solicitante Externo
    let solText = p.solicitante_nombre;
    if (p.solicitante_telefono) solText += ` (Tel: ${p.solicitante_telefono})`;
    if (p.solicitante_email) solText += ` <br><small class="text-muted"><i class="fas fa-envelope me-1"></i>${p.solicitante_email}</small>`;
    
    $('#solicitantePedido').html(solText);
    
    $('#fechaPedido').text(new Date(p.fecha_creacion).toLocaleString());
    
    const badgeCls = {
        'Pendiente': 'bg-warning text-dark',
        'En Proceso': 'bg-primary',
        'Completado': 'bg-success',
        'Rechazado': 'bg-danger'
    };
    $('#badgeEstado').attr('class', 'badge ms-3 fs-6 ' + (badgeCls[p.estado] || 'bg-secondary')).text(p.estado);
    
    if (p.asignado_a && p.asignado_a != 0) {
        $('#asignadoPedido').removeClass('bg-secondary').addClass('bg-info text-dark').text(p.asig_nom + ' ' + p.asig_ape);
    } else {
        $('#asignadoPedido').addClass('bg-secondary').removeClass('bg-info text-dark').text('Sin Asignar');
    }

    // Ocultar card de adjuntar si ya tiene nota
    if (p.pdf_nota) {
        $('#cardAdjuntarNota').hide();
    } else {
        $('#cardAdjuntarNota').show();
    }
}

window.abrirModalEditar = function() {
    if(!window.currentPedido) return;
    const p = window.currentPedido;
    
    $('#editId').val(p.id_pedido);
    $('#editSolicitante').val(p.solicitante_nombre);
    $('#editPrioridad').val(p.prioridad);
    $('#editTipo').val(p.tipo);
    $('#editDescripcion').val(p.descripcion);
    $('#editInsumo').val(p.insumo_relacionado || '');
    
    $('#modalEditar').modal('show');
};

function renderHistorial(hist) {
    const $ul = $('#listaHistorial').empty();
    if (!hist || hist.length === 0) {
        $ul.append('<li class="list-group-item text-muted">Sin movimientos registrados.</li>');
        return;
    }
    
    hist.forEach(h => {
        const fecha = new Date(h.fecha).toLocaleString();
        $ul.append(`
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>${h.accion}</strong>
                    <small class="text-muted" style="font-size:0.75rem;">${fecha}</small>
                </div>
                <div class="small text-muted mb-1"><i class="fas fa-user-circle me-1"></i>${h.username}</div>
                <div class="small">${h.detalle || ''}</div>
            </li>
        `);
    });
}

function renderInforme(inf, p) {
    if (inf) {
        $('#informeContainer').show();
        $('#infDiagnostico').text(inf.diagnostico);
        $('#infTrabajo').text(inf.trabajo_realizado);
        $('#infResultado').text(inf.resultado).addClass(inf.resultado === 'Solucionado' ? 'bg-success' : 'bg-warning text-dark');
        $('#btnDescargarPdf').attr('href', 'informe_pdf.php?id=' + p.id_pedido);
    } else {
        $('#informeContainer').hide();
    }
}

function renderBotones(p) {
    const $c = $('#actionsContainer').empty();
    const isCompleted = (p.estado === 'Completado' || p.estado === 'Rechazado');
    const isOwner = (p.id_usuario_solicitante == USER_ID);
    
    // Ver Nota Original (PDF)
    if (p.pdf_nota) {
        $c.append(`<a href="<?php echo app_base_url(); ?>/uploads/pedidos/${p.pdf_nota}" target="_blank" class="btn btn-info text-white me-2"><i class="fas fa-file-pdf me-2"></i>Ver Nota</a>`);
    }

    $c.append(`<a href="constancia_pdf.php?id=${p.id_pedido}" target="_blank" class="btn btn-outline-dark me-2"><i class="fas fa-print me-2"></i>Constancia</a>`);
    
    // Editar
    if ((PERMISOS.gestionar || isOwner) && !isCompleted) {
         $c.append(`<button class="btn btn-warning me-2" onclick="abrirModalEditar()"><i class="fas fa-edit me-2"></i>Editar</button>`);
    }

    // Acciones de Gestión
    if (PERMISOS.gestionar && !isCompleted) {
        // Tomar si no tiene asignado O si tiene asignado 0/null
        if (!p.asignado_a || p.asignado_a == 0) {
            $c.append(`<button class="btn btn-primary me-2" onclick="accionTomar()"><i class="fas fa-hand-paper me-2"></i>Tomar Pedido</button>`);
        } else if (p.asignado_a == USER_ID) {
            $c.append(`<button class="btn btn-success me-2" onclick="$('#modalInforme').modal('show')"><i class="fas fa-check me-2"></i>Completar con Informe</button>`);
        }
        
        // Boton Asignar (Solo Admin/SuperAdmin y si no tiene asignado)
        if ((!p.asignado_a || p.asignado_a == 0) && (CURRENT_USER_ROL_ID == 1 || CURRENT_USER_ROL_ID == 2)) {
             $c.append(`<button class="btn btn-outline-primary me-2" onclick="accionAsignar()"><i class="fas fa-user-plus me-2"></i>Asignar a...</button>`);
        }
        // Boton Rechazar - Solo para el usuario asignado
        if (p.asignado_a == USER_ID) {
             $c.append(`<button class="btn btn-danger me-2" onclick="accionRechazar()"><i class="fas fa-times me-2"></i>Rechazar</button>`);
        }
    }
    
    if (PERMISOS.eliminar) {
        $c.append(`<button class="btn btn-outline-danger" onclick="accionEliminar()" title="Eliminar"><i class="fas fa-trash"></i></button>`);
    }
}
 
function mostrarRechazo(p, h) {
    $('#alertRechazo').remove();
    if (p.estado !== 'Rechazado') return;
    
    // Buscar motivo en historial
    const rechazo = h.find(item => item.accion === 'Rechazado' || item.detalle.startsWith('Motivo:'));
    const motivo = rechazo ? rechazo.detalle : 'No especificado';
    
    $('#contenidoPedido').prepend(`
        <div id="alertRechazo" class="alert alert-danger shadow-sm mb-4">
            <h4 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Pedido Rechazado</h4>
            <p class="mb-0">${motivo}</p>
        </div>
    `);
}

window.accionTomar = function() {
    if(!confirm('Confirmar tomar este pedido?')) return;
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
        type: 'POST',
        data: { accion: 'tomar', id: PEDIDO_ID, _csrf: CSRF_TOKEN },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r){
            if(r.success) {
                showToast('Pedido tomado correctamente', 'success');
                cargarPedido();
            }
            else showToast(r.error || 'Error al tomar pedido', 'error');
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
};

window.accionEliminar = function() {
    if(!confirm('Eliminar pedido irreversiblemente?')) return;
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
        type: 'POST',
        data: { accion: 'eliminar', id: PEDIDO_ID, _csrf: CSRF_TOKEN },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r){
            if(r.success) {
                showToast('Pedido eliminado correctamente', 'success');
                setTimeout(function(){ window.location.href = 'listar.php'; }, 1000);
            }
            else showToast(r.error || 'Error al eliminar', 'error');
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
};

window.accionRechazar = function() {
    $('#modalRechazar').modal('show');
};

// ===== DOCUMENT READY =====

$(function(){
    cargarPedido();
    
    // Auto-abrir modal si viene en URL
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('accion') === 'completar') {
        // Esperar a cargar para verificar estado
    }
    
    // AJAX Upload Adjunto
    // AJAX Upload Nota Pedido
    $('#formSubirNota').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('accion', 'subir_nota');
        formData.append('id', PEDIDO_ID);
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(resp){
                if(resp.success) {
                    $('#formSubirNota')[0].reset();
                    showToast('Nota adjuntada correctamente', 'success');
                    cargarPedido();
                } else {
                    showToast(resp.error || 'Error al subir nota', 'error');
                }
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i>');
            },
            error: function() {
                showToast('Error de conexión', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i>');
            }
        });
    });

    $('#formInforme').on('submit', function(e){
        e.preventDefault();
        if(!confirm('Confirmas que el trabajo esta terminado y deseas cerrar el pedido?')) return;
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(resp){
                if(resp.success) {
                    $('#modalInforme').modal('hide');
                    showToast('Pedido completado correctamente', 'success');
                    cargarPedido();
                } else {
                    showToast(resp.error || 'Error al completar', 'error');
                }
            },
            error: function() { showToast('Error de conexión', 'error'); }
        });
    });

    $('#formEditar').on('submit', function(e){
        e.preventDefault();
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(resp){
                if(resp.success) {
                    $('#modalEditar').modal('hide');
                    showToast('Pedido actualizado correctamente', 'success');
                    cargarPedido();
                } else {
                    showToast(resp.error || 'Error al actualizar', 'error');
                }
            },
            error: function(xhr, error, code) {
                console.error('Edit error:', xhr, error, code);
                showToast('Error de conexión', 'error');
            }
        });
    });

    $('#btnConfirmarRechazo').click(function() {
        var motivo = $('#motivoRechazo').val();
        if (!motivo || motivo.trim() === '') {
            showToast('Debe indicar un motivo', 'warning');
            return;
        }
        
        var dataToSend = { 
            accion: 'rechazar', 
            id: PEDIDO_ID,
            motivo: motivo,
            _csrf: CSRF_TOKEN
        };
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(r) {
                if(r.success) {
                    $('#modalRechazar').modal('hide');
                    showToast('Pedido rechazado, ha vuelto a pendientes', 'success');
                    setTimeout(function(){ window.location.href = '<?php echo app_base_url(); ?>/pages/pedidos/listar.php'; }, 1500);
                } else {
                    showToast(r.error || 'Error al rechazar', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Rechazar error:', error);
                showToast('Error de conexión', 'error');
            }
        });
    });



    
    // Asignar Logica
    window.accionAsignar = function() {
        // Cargar usuarios
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/usuarios_listar_asignables.php',
            type: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(r){
                if(r.success) {
                    const $sel = $('#selectUsuarioAsignar').empty().append('<option value="">Seleccione Usuario...</option>');
                    r.usuarios.forEach(u => {
                        $sel.append(`<option value="${u.id_usuario}">${u.apellido}, ${u.nombre} (${u.username})</option>`);
                    });
                    $('#modalAsignar').modal('show');
                } else {
                    showToast('Error al cargar usuarios: ' + r.error, 'error');
                }
            },
            error: function() { showToast('Error de conexión', 'error'); }
        });
    };
    
    $('#btnConfirmarAsignar').on('click', function(){
        // Validar que se haya seleccionado un usuario
        var userId = $('#selectUsuarioAsignar').val();
        if(!userId) {
            showToast('Por favor seleccione un usuario', 'warning');
            return;
        }
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: $('#formAsignar').serialize(),
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(r){
                if(r.success) {
                    $('#modalAsignar').modal('hide');
                    showToast('Pedido asignado correctamente', 'success');
                    cargarPedido();
                } else {
                    showToast(r.error || 'Error al asignar', 'error');
                }
            },
            error: function() { showToast('Error de conexión', 'error'); }
        });
    });

}); // Fin $(function)
</script>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Rechazar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Motivo de Rechazo <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="motivoRechazo" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarRechazo">Confirmar Rechazo</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Informe Técnico -->

<!-- Modal Asignar Pedido -->
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Asignar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignar">
                    <input type="hidden" name="accion" value="asignar">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Asignar a: <span class="text-danger">*</span></label>
                        <select class="form-select" name="asignado_a" id="selectUsuarioAsignar" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                        <div class="form-text">Solo se listan administradores y operadores activos.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarAsignar" class="btn btn-primary">Confirmar Asignación</button>
            </div>
        </div>
    </div>
</div>
