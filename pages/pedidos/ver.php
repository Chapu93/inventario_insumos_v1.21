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

            <!-- Información de Entrega (Para Pedidos de Insumos) -->
            <div id="entregaContainer" class="card mb-4 shadow-sm" style="display:none;">
                <div class="card-header" style="background-color: #cfe2ff; color: #084298;">
                    <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Información de Entrega</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Método:</strong> <span id="metodoEntrega" class="fw-bold"></span></div>
                        <div class="col-md-6 mb-2"><strong>Estado Entrega:</strong> <span id="badgeEstadoEntrega" class="badge fs-6"></span></div>
                        <div class="col-md-6 mb-3" id="fechaEntregaRow" style="display:none;">
                            <strong>Fecha de Entrega:</strong> <span id="fechaEntrega"></span>
                        </div>
                        <div class="col-md-6 mb-3" id="receptorEntregaRow" style="display:none;">
                            <strong>Recibido por:</strong> <span id="receptorEntrega" class="fw-bold"></span>
                        </div>
                        <div class="col-md-6 mb-2" id="remitoPedidoRow" style="display:none;">
                            <strong>Remito Asociado:</strong> <span id="remitoPedido"></span>
                        </div>
                        <div class="col-md-6 mb-2" id="fechaEstimadaRow" style="display:none;">
                            <strong>Fecha Estimada:</strong> <span id="fechaEstimada"></span>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top" id="controlesEntrega" style="display:none;">
                        <h6 class="mb-3">Cambiar Estado de Entrega:</h6>
                        <div class="row g-2" id="botonesEstadoEntrega">
                            <!-- Los botones se generan dinámicamente según el estado actual -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Notas e Historial -->
        <div class="col-md-4">
            
            <!-- Notas -->
            <!-- Adjuntar Archivos -->



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
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Diagnóstico Inicial <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="diagnostico" rows="2" required placeholder="Describa el problema detectado"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Trabajo Realizado <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="trabajo" rows="3" required placeholder="Describa las tareas realizadas"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Resultado Final</label>
                            <select class="form-select" name="resultado">
                                <option value="Solucionado">Solucionado</option>
                                <option value="Sin Solución">Sin Solución</option>
                                <option value="Requiere Repuestos">Requiere Repuestos</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <div id="seccionLogistica" class="bg-light p-3 rounded">
                        <h6 class="text-primary mb-3"><i class="fas fa-shipping-fast me-2"></i>Datos de Logística (para devolución o entrega)</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Método de Entrega/Retiro</label>
                                <select class="form-select" name="metodo_entrega">
                                    <option value="Envío">Envío por Logística</option>
                                    <option value="Retiro">Retiro en Oficina</option>
                                    <option value="No aplica">No aplica / En el lugar</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Estimada</label>
                                <input type="date" class="form-control" name="fecha_estimada_entrega" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-12 mb-0">
                                <label class="form-label fw-bold">Notas de Entrega (opcional)</label>
                                <textarea class="form-control" name="notas_entrega" rows="2" placeholder="Observaciones para el transporte o retiro..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="seccionBaja" class="bg-light p-3 rounded border-danger border mt-3" style="display: none;">
                        <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Resultado no satisfactorio</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="checkBaja" name="dar_de_baja" value="1">
                            <label class="form-check-label fw-bold text-danger" for="checkBaja">¿Desea dar de baja el insumo relacionado?</label>
                        </div>
                        <p class="small text-muted mb-0">Al marcar esta opción, el equipo pasará a estado 'Baja' y el informe servirá como justificativo técnico.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="$('#formInforme').submit()">
                    <i class="fas fa-save me-2"></i>Guardar y Finalizar
                </button>
            </div>
        </div>
    </div>
</div>




<!-- Modal Entregar Pedido -->
<div class="modal fade" id="modalEntregar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-double me-2"></i>Finalizar Entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Recibido por: (Nombre y Apellido) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="receptorNombre" placeholder="Ej: Juan Pérez">
                </div>
                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle me-1"></i> Al marcar como entregado, el estado de la entrega pasará a ser definitivo.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarEntrega" class="btn btn-success">Confirmar Entrega</button>
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
            showAlert('Error al cargar pedido: ' + resp.error, 'error');
            window.location.href = 'listar.php';
            return;
        }
        
        const pedido = resp.pedido || resp.data.pedido;
        const historial = resp.historial || resp.data.historial;
        const informe = resp.informe || resp.data.informe;
        const remitoItems = resp.remito_items || [];

        if (!pedido) { 
            console.error('No pedido data in response:', resp);
            showAlert('Estructura de respuesta inválida', 'error'); 
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
            renderRemitoItems(remitoItems, pedido);
            console.log('renderRemitoItems OK');
        } catch(e) {
            console.error('renderRemitoItems error:', e);
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
        showAlert('Error de conexión al cargar pedido', 'error');
    });
}

function renderPedido(p) {
    $('#tituloPedido').text('Pedido #' + p.id_pedido);
    $('#descripcionPedido').text(p.descripcion);
    $('#tipoPedido').html(`<span class="badge bg-dark">${p.tipo}</span>`);
    $('#prioridadPedido').html(`<span class="badge bg-${p.prioridad==='Alta'?'danger':(p.prioridad==='Baja'?'success':'warning text-dark')}">${p.prioridad}</span>`);
    
    // Insumo Relacionado: si hay items del remito se renderiza por renderRemitoItems
    // Si está pendiente (sin remito), mostrar el texto descriptivo si existe
    if (p.tipo !== 'Pedido Insumo' && p.insumo_relacionado) {
        $('#insumoRelacionadoContainer').show();
        $('#insumoRelacionado').text(p.insumo_relacionado);
    } else if (p.tipo !== 'Pedido Insumo') {
        $('#insumoRelacionadoContainer').hide();
    }
    // Para Pedido Insumo: renderRemitoItems se encarga de mostrar los items

    $('#sedePedido').text(p.nombre_sede);
    
    // Solicitante Externo
    let solText = p.solicitante_nombre + (p.solicitante_apellido ? ' ' + p.solicitante_apellido : '');
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
    
    if (p.tipo === 'Pedido Insumo') {
        $('#asignadoPedido').parent().hide(); // Ocultar fila de asignado para Pedido Insumo
    } else if (p.asignado_a && p.asignado_a != 0) {
        $('#asignadoPedido').parent().show();
        $('#asignadoPedido').removeClass('bg-secondary').addClass('bg-info text-dark').text(p.asig_nom + ' ' + p.asig_ape);
    } else {
        $('#asignadoPedido').parent().show();
        $('#asignadoPedido').addClass('bg-secondary').removeClass('bg-info text-dark').text('Sin Asignar');
    }

    // Información de Entrega (Si es Pedido Insumo)
    if (p.tipo === 'Pedido Insumo') {
        $('#entregaContainer').show();

        // Método de entrega
        const metodoText = (!p.metodo_entrega || p.metodo_entrega === 'No aplica') ? 'No definido' : p.metodo_entrega;
        $('#metodoEntrega').text(metodoText);

        // Remito Link (solo mostrar si realmente existe)
        if (p.numero_remito) {
            $('#remitoPedidoRow').show();
            $('#remitoPedido').html(`<a href="${BASE}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(p.numero_remito)}" target="_blank" class="btn btn-sm btn-outline-primary py-0"><i class="fas fa-file-invoice me-1"></i>${p.numero_remito}</a>`);
        } else {
            $('#remitoPedidoRow').hide();
        }

        // Fecha Estimada
        if (p.fecha_estimada_entrega) {
            $('#fechaEstimadaRow').show();
            const dateParts = p.fecha_estimada_entrega.split('-');
            $('#fechaEstimada').text(`${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`);
        } else {
            $('#fechaEstimadaRow').hide();
        }

        // Estado de entrega: derivar del estado general si no está definido
        const entregaCls = {
            'Pendiente':  'bg-secondary',
            'Preparado':  'bg-info text-dark',
            'Enviado':    'bg-primary',
            'Entregado':  'bg-success'
        };
        // Si estado_entrega es null/vacío, derivar del estado del pedido
        let estadoEntrega = p.estado_entrega;
        if (!estadoEntrega) {
            if (p.estado === 'Completado') estadoEntrega = 'Entregado';
            else if (p.estado === 'Preparado') estadoEntrega = 'Preparado';
            else estadoEntrega = 'Pendiente';
        }
        $('#badgeEstadoEntrega')
            .attr('class', 'badge fs-6 ' + (entregaCls[estadoEntrega] || 'bg-secondary'))
            .text(estadoEntrega);

        if (estadoEntrega === 'Entregado') {
            if (p.fecha_entrega) {
                $('#fechaEntregaRow').show();
                $('#fechaEntrega').text(new Date(p.fecha_entrega).toLocaleString());
            }
            if (p.receptor_nombre) {
                $('#receptorEntregaRow').show();
                $('#receptorEntrega').text(p.receptor_nombre);
            }
            $('#controlesEntrega').hide();
        } else {
            $('#fechaEntregaRow').hide();
            $('#receptorEntregaRow').hide();
            if (PERMISOS.gestionar && p.insumo_relacionado && p.insumo_relacionado !== '') {
                $('#controlesEntrega').show();
                // Construir botones según estado_entrega actual
                const $btns = $('#botonesEstadoEntrega').empty();
                // "Marcar Preparado" solo si aún está Pendiente
                if (estadoEntrega === 'Pendiente') {
                    $btns.append(`
                        <div class="col">
                            <button type="button" class="btn btn-outline-primary w-100 btn-sm" onclick="actualizarEstadoEntrega('Preparado')">
                                <i class="fas fa-box me-1"></i>Marcar Preparado
                            </button>
                        </div>`);
                }
                // "Enviado" solo si es Envío por Logística (para Retiro es redundante)
                if ((estadoEntrega === 'Preparado' || estadoEntrega === 'Pendiente') && p.metodo_entrega === 'Envío') {
                    $btns.append(`
                        <div class="col">
                            <button type="button" class="btn btn-warning text-dark w-100 btn-sm" onclick="actualizarEstadoEntrega('Enviado')">
                                <i class="fas fa-truck me-1"></i>Marcar Enviado
                            </button>
                        </div>`);
                }
                // "Confirmar Entrega" siempre disponible
                $btns.append(`
                    <div class="col">
                        <button type="button" class="btn btn-success w-100 btn-sm" onclick="abrirModalEntregar()">
                            <i class="fas fa-check-double me-1"></i>Confirmar Entrega
                        </button>
                    </div>`);
            } else {
                $('#controlesEntrega').hide();
            }
        }
    } else {
        $('#entregaContainer').hide();
    }

}


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
    
    if (p.remito_firmado) {
        $c.append(`<a href="<?php echo app_base_url(); ?>/uploads/pedidos/${p.remito_firmado}" target="_blank" class="btn btn-success me-2 text-white" data-bs-toggle="tooltip" title="Ver Constancia Firmada de Entrega"><i class="fas fa-file-signature me-2"></i>Ver Constancia Firmada</a>`);
    }

    // Botón Descargar Todo (Merge PDF)
    $c.append(`<a href="descargar_todo.php?id=${p.id_pedido}" target="_blank" class="btn btn-dark me-2" title="Descargar toda la documentación en un solo PDF"><i class="fas fa-file-archive me-2"></i>Descargar Todo</a>`);

    // Imprimir Remito (Solo Insumos con remito)
    if (p.tipo === 'Pedido Insumo' && p.numero_remito) {
        $c.append(`<button class="btn btn-primary me-2" onclick="imprimirRemito('${p.numero_remito}')"><i class="fas fa-print me-2"></i>Imprimir Remito</button>`);
    }

    // Ver Constancia (Solo para Tareas Técnicas)
    if (p.tipo !== 'Pedido Insumo') {
        $c.append(`<a href="constancia_pdf.php?id=${p.id_pedido}" target="_blank" class="btn btn-outline-dark me-2" data-bs-toggle="tooltip" title="Imprimir constancia de visita técnica"><i class="fas fa-print me-2"></i>Constancia</a>`);
    }
    

    // Acciones de Gestión
    if (PERMISOS.gestionar && !isCompleted) {
        let urlEditar = 'editar.php';
        if (p.tipo === 'Pedido Insumo') {
            urlEditar = 'editar_pedido_insumo.php';
        } else if (p.tipo === 'Tarea Interna') {
            urlEditar = 'editar_tarea_interna.php';
        }
        $c.append(`<a href="${urlEditar}?id=${p.id_pedido}" class="btn btn-warning me-2"><i class="fas fa-edit me-2"></i>Editar</a>`);
        // Tomar si no tiene asignado O si tiene asignado 0/null
        if (!p.asignado_a || p.asignado_a == 0) {
            // Solo se permite "Tomar" si es un pedido técnico. 
            // Los pedidos de insumo los "Prepara" cualquier gestor desde el detalle.
            if (p.tipo !== 'Pedido Insumo') {
                $c.append(`<button class="btn btn-primary me-2" onclick="accionTomar()"><i class="fas fa-hand-paper me-2"></i>Tomar Pedido</button>`);
            }
        } else if (p.asignado_a == USER_ID) {
            // Solo pedidos técnicos llevan informe
            if (p.tipo !== 'Pedido Insumo') {
                $c.append(`<button class="btn btn-success me-2" onclick="$('#modalInforme').modal('show')"><i class="fas fa-check me-2"></i>Completar con Informe</button>`);
            }
        }
        
        // Boton Asignar (Solo Admin/SuperAdmin y si no tiene asignado) - NO para Pedidos de Insumo
        if (p.tipo !== 'Pedido Insumo' && (!p.asignado_a || p.asignado_a == 0) && (CURRENT_USER_ROL_ID == 1 || CURRENT_USER_ROL_ID == 2)) {
             $c.append(`<button class="btn btn-outline-primary me-2" onclick="accionAsignar()"><i class="fas fa-user-plus me-2"></i>Asignar a...</button>`);
        }
        // Boton Rechazar - Solo para el usuario asignado
        if (p.asignado_a == USER_ID) {
             $c.append(`<button class="btn btn-danger me-2" onclick="accionRechazar()"><i class="fas fa-times me-2"></i>Rechazar</button>`);
        }

        // Nuevo Botón para Preparar (Solo Insumos en estado Pendiente) - Redirige a la página de preparación interactiva
        if (p.tipo === 'Pedido Insumo' && p.estado === 'Pendiente') {
            const urlPreparar = '<?php echo app_base_url(); ?>/pages/pedidos/preparar.php?id=' + p.id_pedido;
            $c.append(`<a href="${urlPreparar}" class="btn btn-success me-2"><i class="fas fa-box-open me-2"></i>PREPARAR PEDIDO</a>`);
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
    showConfirm({
        titulo: 'Tomar Pedido',
        mensaje: '¿Confirmar tomar este pedido?',
        icono: 'fa-hand-paper text-primary',
        btnAceptar: 'Tomar',
        onConfirm: () => {
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
        }
    });
};

window.accionPreparar = function() {
    showConfirm({
        titulo: 'Preparar Pedido',
        mensaje: '¿Deseas marcar este pedido como PREPARADO? <br><br>Esto lo moverá automáticamente a la <strong>Gestión de Envíos</strong>.',
        icono: 'fa-box-open text-success',
        btnAceptar: 'Confirmar Preparado',
        onConfirm: () => {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
                type: 'POST',
                data: { accion: 'preparar', id: PEDIDO_ID, _csrf: CSRF_TOKEN },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r){
                    if(r.success) {
                        showToast('Pedido marcado como preparado', 'success');
                        cargarPedido();
                    }
                    else showToast(r.error || 'Error al preparar pedido', 'error');
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
};

window.imprimirRemito = function(num) {
    if(!num) return;
    const url = '<?php echo app_base_url(); ?>/pages/reportes/remito_pdf.php?remito=' + encodeURIComponent(num);
    window.open(url, 'remitoPrint');
};

function renderRemitoItems(items, pedido) {
    if (pedido.tipo !== 'Pedido Insumo') return;

    const $container = $('#insumoRelacionadoContainer');
    const $content   = $('#insumoRelacionado');

    if (items && items.length > 0) {
        $container.show();
        let html = `<table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Insumo</th>
                    <th class="text-center">Cant.</th>
                    <th>N° Serie / ID</th>
                </tr>
            </thead><tbody>`;
        items.forEach(item => {
            const link = `<a href="${BASE}/pages/insumos/ver.php?id=${item.id_insumo}" target="_blank" class="text-decoration-none">
                <i class="fas fa-external-link-alt fa-xs me-1"></i>${item.nombre_insumo}
                <small class="text-muted">(${item.tipo_insumo})</small></a>`;
            const ref = item.numero_serie
                ? `<small class="text-muted">S/N: ${item.numero_serie}</small>`
                : (item.id_fisico ? `<small class="text-muted">ID: ${item.id_fisico}</small>` : '-');
            html += `<tr><td>${link}</td><td class="text-center">${item.cantidad}</td><td>${ref}</td></tr>`;
        });
        html += '</tbody></table>';
        $content.html(html);
    } else if (pedido.tipo === 'Pedido Insumo' && !pedido.id_remito) {
        // Pedido aún no preparado
        $container.show();
        $content.html('<span class="text-muted fst-italic"><i class="fas fa-clock me-1"></i>Pendiente de preparación — los insumos aún no fueron seleccionados.</span>');
    } else {
        $container.hide();
    }
}

window.accionEliminar = function() {
    showConfirm({
        titulo: 'Eliminar Pedido',
        mensaje: '¿Desea eliminar este pedido de forma irreversible?',
        icono: 'fa-trash-alt text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => {
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
        }
    });
};

window.accionRechazar = function() {
    $('#modalRechazar').modal('show');
};

// ===== DOCUMENT READY =====

$(function(){
    cargarPedido();
    
    // Manejo de visibilidad condicional en el Informe Técnico
    $('select[name="resultado"]').on('change', function() {
        const val = $(this).val();
        if (val === 'Sin Solución' || val === 'Requiere Repuestos') {
            $('#seccionLogistica').hide();
            $('#seccionBaja').slideDown();
        } else {
            $('#seccionLogistica').show();
            $('#seccionBaja').slideUp();
            $('#checkBaja').prop('checked', false);
        }
    });

    // Auto-abrir modal si viene en URL
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('accion') === 'completar') {
        // Esperar a cargar para verificar estado
    }
    

    $('#formInforme').on('submit', function(e){
        e.preventDefault();
        const form = this;
        showConfirm({
            titulo: 'Finalizar Pedido',
            mensaje: '¿Confirma que el trabajo está terminado y desea cerrar el pedido?<br><br><small class="text-muted">Se generará el informe PDF automáticamente.</small>',
            icono: 'fa-check-circle text-success',
            claseBoton: 'btn-success',
            textoAceptar: 'Finalizar y Cerrar',
            onConfirm: () => {
                $.ajax({
                    url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
                    type: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',
                    xhrFields: { withCredentials: true },
                    success: function(resp){
                        if(resp.success) {
                            $('#modalInforme').modal('hide');
                            showToast('Pedido completado correctamente', 'success');
                            
                            // Abrir informe PDF automáticamente
                            window.open('informe_pdf.php?id=' + PEDIDO_ID, '_blank');
                            
                            cargarPedido();
                        } else {
                            showToast(resp.error || 'Error al completar', 'error');
                        }
                    },
                    error: function() { showToast('Error de conexión', 'error'); }
                });
            }
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
    
    // Funciones de Gestión de Entrega
    window.actualizarEstadoEntrega = function(nuevoEstado) {
        if (nuevoEstado === 'Enviado' || nuevoEstado === 'Preparado') {
            const textoConfirm = nuevoEstado === 'Enviado' ? 
                (window.currentPedido.metodo_entrega === 'Retiro' ? '¿Confirmar que está listo para retiro?' : '¿Confirmar que el paquete fue enviado?') : 
                '¿Confirmar que el pedido está preparado?';
            
            showConfirm({
                titulo: 'Actualizar Entrega',
                mensaje: textoConfirm,
                icono: 'fa-shipping-fast text-primary',
                onConfirm: () => {
                    ejecutarActualizarEntrega(nuevoEstado);
                }
            });
        }
    };

    window.abrirModalEntregar = function() {
        $('#modalEntregar').modal('show');
    };

    function ejecutarActualizarEntrega(estado, receptor = '') {
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: { 
                accion: 'actualizar_entrega', 
                id: PEDIDO_ID, 
                estado_entrega: estado,
                receptor_nombre: receptor,
                _csrf: CSRF_TOKEN 
            },
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function(r) {
                if (r.success) {
                    showToast('Entrega actualizada correctamente', 'success');
                    if (estado === 'Entregado') $('#modalEntregar').modal('hide');
                    cargarPedido();
                } else {
                    showToast(r.error || 'Error al actualizar entrega', 'error');
                }
            },
            error: function() { showToast('Error de conexión', 'error'); }
        });
    }

    $('#btnConfirmarEntrega').click(function() {
        const receptor = $('#receptorNombre').val().trim();
        if (!receptor) {
            showToast('Debe ingresar el nombre de quien recibe', 'warning');
            return;
        }
        ejecutarActualizarEntrega('Entregado', receptor);
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

<script>
</script>
