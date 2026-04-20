<?php
require_once '../../includes/config.php';
requerirAutenticacion();

// Verificar permiso
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    $_SESSION['mensaje'] = 'No tienes permiso para acceder al módulo de pedidos';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

$puedeVerPendientes = tienePermiso('pedidos', 'ver_todos') || tienePermiso('pedidos', 'gestionar');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clipboard-list me-2"></i>Gestión de Pedidos</h1>
    <?php if (tienePermiso('pedidos', 'crear')): ?>
    <div id="btnGrupoPedidos" class="btn-group">
        <a href="<?php echo app_base_url(); ?>/pages/pedidos/crear.php" class="btn btn-primary" data-bs-toggle="tooltip" title="Crear nueva tarea técnica (Mantenimiento, Soporte, etc)">
            <i class="fas fa-plus me-2"></i>Nuevo Pendiente
        </a>
        <a href="<?php echo app_base_url(); ?>/pages/pedidos/nuevo_pedido.php" class="btn btn-success" data-bs-toggle="tooltip" title="Nueva solicitud de equipos o insumos">
            <i class="fas fa-box me-2"></i>Nuevo Pedido
        </a>
    </div>
    <button type="button" class="btn btn-primary d-none shadow-sm" id="btnNuevaTarea" onclick="abrirModalNuevaTarea()" title="Registrar nueva tarea interna del área">
        <i class="fas fa-plus me-2"></i>Nueva Tarea
    </button>
<?php endif; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="pedidosTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="tareas-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="tareas_internas" data-section="tareas" title="Tareas internas del área (depósito, racks, limpieza, etc.)">
        <i class="fas fa-tasks me-2"></i>Tareas Internas
    </button>
  </li>

  <?php if ($puedeVerPendientes): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="pendientes" data-bs-toggle="tooltip" title="Tareas técnicas sin asignar">
        <i class="fas fa-tools me-2"></i>Pendientes Técnicos
    </button>
  </li>
  <?php endif; ?>
  
  <?php if (tienePermiso('pedidos', 'ver_todos')): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="pedidos_insumos" title="Solicitudes de insumos pendientes de preparación">
        <i class="fas fa-boxes me-2"></i>Pedidos de Insumos
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="transito-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="logistica" title="Pedidos preparados en proceso de entrega">
        <i class="fas fa-shipping-fast me-2"></i>En Tránsito
    </button>
  </li>
  <?php endif; ?>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="mis-pedidos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="mis_pedidos" title="Tareas técnicas y tareas internas asignadas a mi usuario">
        <i class="fas fa-user-clock me-2"></i>Mis Tareas
    </button>
  </li>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="todos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="todos" title="Historial completo de pedidos">
        <i class="fas fa-history me-2"></i>Historial
    </button>
  </li>
</ul>

<div id="seccionPedidos" class="d-none">

<!-- Filtros -->
<div class="filtros-container mb-4" id="filtrosContainer">
    <form id="formFiltros" class="row g-3 align-items-end">
        <input type="hidden" id="filtroModo" name="modo" value="tareas_internas">
        
        <div class="col-md-3" id="wrapFiltroEstado">
            <label class="form-label">Estado</label>
            <select class="form-select" id="filtroEstado">
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En Proceso">En Proceso</option>
                <option value="Preparado">Preparado</option>
                <option value="Completado">Completado</option>
                <option value="Rechazado">Rechazado</option>
            </select>
        </div>
        
        <div class="col-md-3" id="wrapFiltroTipo">
            <label class="form-label">Tipo</label>
            <select class="form-select" id="filtroTipo">
                <option value="">Todos</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Reparación">Reparación</option>
                <option value="Soporte">Soporte</option>
                <option value="Pedido Insumo">Pedido Insumo</option>
            </select>
        </div>
        
        <div class="col-md-3" id="wrapFiltroPrioridad">
            <label class="form-label">Prioridad</label>
            <select class="form-select" id="filtroPrioridad">
                <option value="">Todas</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>
        </div>
        
        <div class="col-md-3" id="wrapFiltroLogistica" style="display:none;">
            <label class="form-label">Acción a realizar</label>
            <select class="form-select" id="filtroLogistica">
                <option value="">Todos</option>
                <option value="Preparado">Listo para Enviar / Retirar</option>
                <option value="Enviado">Enviado (en camino)</option>
            </select>
        </div>

        <div class="col-md-3" id="wrapFiltroVista" style="display:none;">
            <label class="form-label">Vista</label>
            <select class="form-select" id="filtroVista">
                <option value="activas">Disponibles (Sin asignar)</option>
                <option value="historial">Historial completo</option>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end ms-auto">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm shadow-sm" id="btnFiltrar">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <button type="button" class="btn btn-secondary btn-sm shadow-sm" id="btnLimpiar">
                    <i class="fas fa-times me-1"></i>Limpiar
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Pedidos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tablaPedidos" style="width:100%">
                <thead>
                    <tr>
                        <th>Nro de Pedido</th>
                        <th>Solicitante / Sede</th>
                        <th>Tipo</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Asignado A</th>
                        <th>Logística</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /seccionPedidos -->

<!-- ══════════════════════════════════════════════════════════ -->
<!-- SECCIÓN TAREAS INTERNAS                                    -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="seccionTareas" class="d-none">
    <div class="card shadow-sm border-0">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Tareas Internas del Área</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="tablaTareas" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th style="width:120px">Estado</th>
                            <th>Creado por</th>
                            <th>Asignado a</th>
                            <th>Creación</th>
                            <th>Finalización</th>
                            <th style="width:110px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div><!-- /seccionTareas -->

<!-- Modal Asignar Pedido -->
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus me-2"></i>Asignar Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="formAsignar">
                    <input type="hidden" name="accion" value="asignar">
                    <input type="hidden" name="id" id="idPedidoAsignar">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asignar a Técnico: <span class="text-danger">*</span></label>
                        <select class="form-select border-primary-subtle" name="asignado_a" id="selectUsuarioAsignar" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                        <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i>Solo se listan administradores y operadores activos.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarAsignar" class="btn btn-primary px-4 shadow-sm">Confirmar Asignación</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Entregar (Logística) -->
<div class="modal fade" id="modalEntregarLista" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius: 15px;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-handshake me-2"></i>Confirmar Entrega Final</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="formEntregarLista" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="entregar_lista_id">
                    <input type="hidden" name="accion" value="actualizar_entrega">
                    <input type="hidden" name="estado_entrega" value="Entregado">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Indique quién recibe el/los insumo(s) junto con el documento firmado para cerrar el ciclo de logística.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Receptor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="receptor_nombre" id="receptor_nombre_lista" required placeholder="Nombre y Apellido">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remito Firmado (Constancia) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="remito_firmado" id="remito_firmado_lista" accept=".pdf, .jpg, .jpeg, .png" required>
                        <div class="form-text mt-1 text-muted">Adjuntar archivo escaneado o foto (PDF, JPG, PNG). Máx: 10MB.</div>
                    </div>
                </form>

            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 shadow-sm" id="btnConfirmarEntregaLista">Registrar Entrega</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════ -->
<!-- Modal: Nueva Tarea Interna     -->
<!-- ══════════════════════════════ -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius:15px">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nueva Tarea Interna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="formNuevaTarea">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="titulo" id="tareasTitulo"
                               maxlength="200" placeholder="Ej: Limpiar depósito, Ordenar cables de rack..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="descripcion" id="tareasDescripcion"
                                  rows="4" placeholder="Detalle de la tarea a realizar..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Asignar a (opcional)</label>
                        <select class="form-select" name="asignado_a" id="tareasAsignadoA">
                            <option value="">Sin asignar — quedará disponible para tomar</option>
                        </select>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Si no se asigna, cualquier operador puede tomarla.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarTarea" class="btn btn-success px-4 shadow-sm">
                    <i class="fas fa-save me-1"></i>Guardar Tarea
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════ -->
<!-- Modal: Ver Tarea Interna       -->
<!-- ══════════════════════════════ -->
<div class="modal fade" id="modalVerTarea" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius:15px">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalle de Tarea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" id="verTareaContenido">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-modal="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════ -->
<!-- Modal: Asignar Tarea           -->
<!-- ══════════════════════════════ -->
<div class="modal fade" id="modalAsignarTarea" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius:15px">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Asignar Tarea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="formAsignarTarea">
                    <input type="hidden" name="accion" value="asignar">
                    <input type="hidden" name="id" id="idTareaAsignar">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asignar a: <span class="text-danger">*</span></label>
                        <select class="form-select" name="asignado_a" id="selectUsuarioAsignarTarea" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarAsignarTarea" class="btn btn-primary px-4">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
var dtPedidos;

$(function() {
    // Inicializar DataTable
    dtPedidos = $('#tablaPedidos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_list_ssp.php',
            data: function(d) {
                d.modo = $('#filtroModo').val();
                d.estado = $('#filtroEstado').val();
                d.tipo = $('#filtroTipo').val();
                d.prioridad = $('#filtroPrioridad').val();
                d.logistica = $('#filtroLogistica').val();
            },
            xhrFields: { withCredentials: true },
            error: function(xhr, error, code) {
                console.error('DataTables error:', xhr, error, code);
                showAlert('Error al cargar la tabla: ' + code, 'error');
            }
        },
        order: [[5, 'desc']], 
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, orderable: false }
        ],
        drawCallback: function() {
            // Usar la función global centralizada para tooltips
            if (typeof inicializarTooltips === 'function') {
                inicializarTooltips();
            }
        }
    });

    // Eventos de Filtros
    $('#filtroEstado, #filtroTipo, #filtroPrioridad, #filtroLogistica, #filtroVista').on('change', function() {
        if ($('#filtroModo').val() === 'tareas_internas' || $('#tareas-tab').hasClass('active')) {
            currentTareasModo = $('#filtroVista').val();
            if (dtTareas) dtTareas.ajax.reload();
        } else {
            dtPedidos.ajax.reload();
        }
    });

    $('#btnLimpiar').on('click', function() {
        $('#filtroEstado, #filtroTipo, #filtroPrioridad, #filtroLogistica').val('');
        $('#filtroVista').val('activas');
        if ($('#filtroModo').val() === 'tareas_internas') {
            currentTareasModo = 'activas';
            if (dtTareas) dtTareas.ajax.reload();
        } else {
            dtPedidos.ajax.reload();
        }
    });

    // Eventos de Tabs
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
        var $tab   = $(event.target);
        var modo   = $tab.data('modo');
        var section = $tab.data('section') || 'pedidos';

        // Resetear visibilidad inicial
        $('#seccionPedidos').addClass('d-none');
        $('#seccionTareas').addClass('d-none');
        $('#btnGrupoPedidos').addClass('d-none');
        $('#btnNuevaTarea').addClass('d-none');
        
        $('#filtroModo').val(modo);
        adaptarFiltros(modo);

        if (section === 'tareas') {
            $('#btnNuevaTarea').removeClass('d-none');
            $('#seccionTareas').removeClass('d-none');
            $('#filtrosContainer').removeClass('d-none'); // Asegurar que el contenedor de filtros sea visible para Tareas
            iniciarTablaTareas('activas');
            $('#seccionTareas .card-header h5').html('<i class="fas fa-tasks me-2"></i>Tareas Internas del Área');
        } else {
            $('#btnGrupoPedidos').removeClass('d-none');
            $('#seccionPedidos').removeClass('d-none');
            $('#filtrosContainer').removeClass('d-none'); // Asegurar que el contenedor de filtros sea visible para Pedidos
            
            if (dtPedidos) {
                // Columnas Visibles según modo
                const colAsignado = 6;
                const colLogistica = 7;
                
                dtPedidos.column(colAsignado).visible(true);
                dtPedidos.column(colLogistica).visible(true);

                if (['pedidos_insumos', 'pendientes', 'mis_pedidos'].includes(modo)) {
                    dtPedidos.column(colAsignado).visible(false);
                    dtPedidos.column(colLogistica).visible(false);
                } else if (modo === 'logistica') {
                    dtPedidos.column(colAsignado).visible(false);
                    dtPedidos.column(colLogistica).visible(true);
                }
                
                dtPedidos.ajax.reload();
            }
            
            // Si el modo es mis_pedidos, mostrar también Tareas Internas pero filtrado a "mis_tareas"
            if (modo === 'mis_pedidos') {
                $('#seccionTareas').removeClass('d-none');
                $('#seccionTareas .card-header h5').html('<i class="fas fa-tasks me-2"></i>Mis Tareas Internas (Asignadas a mi)');
                iniciarTablaTareas('mis_tareas');
            } else if (modo === 'todos') {
                $('#seccionTareas').removeClass('d-none');
                $('#seccionTareas .card-header h5').html('<i class="fas fa-history me-2"></i>Historial de Tareas Internas');
                iniciarTablaTareas('historial');
            }
        }
    });

    // Activar la pestaña activa por defecto para disparar la carga inicial
    const $activeTab = $('#pedidosTabs .nav-link.active');
    if ($activeTab.length > 0) {
        $activeTab.trigger('shown.bs.tab');
    } else {
        adaptarFiltros($('#filtroModo').val());
    }

    // Manejar envío del formulario de filtros
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        dtPedidos.ajax.reload();
    });

    // Abrir PDF si viene imprimir=<remito> desde preparar.php (Igual que en asignaciones)
    try {
        const url = new URL(window.location.href);
        const imp = url.searchParams.get('imprimir');
        if (imp && (typeof imp === 'string') && imp.trim() !== '') {
            const base = (typeof getAppBase === 'function') ? getAppBase() : '';
            const printUrl = `${base}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(imp)}`;
            window.open(printUrl, 'remitoPrint');
            url.searchParams.delete('imprimir');
            window.history.replaceState({}, document.title, url.toString());
        }
    } catch (e) { }

});

// Funciones Globales para botones de acción
function verPedido(id) {
    const url = '<?php echo app_base_url(); ?>/pages/pedidos/ver.php?id=' + id;
    console.log('Navigating to:', url);
    window.location.href = url;
}

function prepararPedido(id) {
    window.location.href = '<?php echo app_base_url(); ?>/pages/pedidos/preparar.php?id=' + id;
}

function completarPedido(id) {
    const url = '<?php echo app_base_url(); ?>/pages/pedidos/ver.php?id=' + id + '&accion=completar';
    console.log('Navigating to:', url);
    window.location.href = url;
}

function tomarPedido(id) {
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
        type: 'POST',
        data: {
            accion: 'tomar',
            id: id,
            _csrf: '<?php echo csrf_token(); ?>'
        },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(resp) {
            if (resp.success) {
                showToast('Pedido tomado correctamente', 'success');
                dtPedidos.ajax.reload();
            } else {
                showToast(resp.error || 'Error al tomar pedido', 'error');
            }
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
}

function eliminarPedido(id) {
    showConfirm({
        titulo: 'Eliminar Pedido',
        mensaje: '¿Realmente deseas eliminar este pedido? Esta acción no se puede deshacer.',
        icono: 'fa-trash-alt text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
                type: 'POST',
                data: {
                    accion: 'eliminar',
                    id: id,
                    _csrf: '<?php echo csrf_token(); ?>'
                },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(resp) {
                    if (resp.success) {
                        showToast('Pedido eliminado correctamente', 'success');
                        dtPedidos.ajax.reload();
                    } else {
                        showToast(resp.error || 'Error al eliminar', 'error');
                    }
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function abrirModalAsignar(id) {
    $('#idPedidoAsignar').val(id);
    
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
}

function imprimirRemito(num) {
    if(!num) return;
    const url = '<?php echo app_base_url(); ?>/pages/reportes/remito_pdf.php?remito=' + encodeURIComponent(num);
    window.open(url, 'remitoPrint');
}

// Handler para botón de asignación
$(document).ready(function() {
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
                    dtPedidos.ajax.reload(); // Recargar tabla
                } else {
                    showToast(r.error || 'Error al asignar', 'error');
                }
            },
            error: function() { showToast('Error de conexión', 'error'); }
        });
    });

    // Handler para confirmar entrega desde logística
    $('#btnConfirmarEntregaLista').click(function() {
        const id = $('#entregar_lista_id').val();
        const receptor = $('#receptor_nombre_lista').val().trim();
        const remito = $('#remito_firmado_lista').val();
        
        if (!receptor) {
            showToast('Debe ingresar el nombre del receptor', 'warning');
            return;
        }
        
        if (!remito) {
            showToast('Debe adjuntar el remito firmado o constancia de entrega', 'warning');
            return;
        }
        
        const formData = new FormData($('#formEntregarLista')[0]);
        
        // Bloquear botón repetitivo
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            xhrFields: { withCredentials: true },
            success: function(resp) {
                $btn.prop('disabled', false).html('Registrar Entrega');
                if (resp.success) {
                    showToast('Pedido entregado y cerrado con éxito', 'success');
                    $('#modalEntregarLista').modal('hide');
                    dtPedidos.ajax.reload();
                } else {
                    showToast(resp.error || 'Error', 'error');
                }
            },
            error: function() { 
                $btn.prop('disabled', false).html('Registrar Entrega');
                showToast('Error de conexión al subir los datos', 'error'); 
            }
        });
    });
});

function actualizarLogistica(id, estadoActual, metodo) {
    const texto = (metodo === 'Envío') 
        ? '¿Confirmar que el pedido ha salido hacia su destino?' 
        : '¿Confirmar que el pedido está listo para ser retirado?';
    
    showConfirm({
        titulo: 'Actualizar Logística',
        mensaje: texto,
        icono: 'fa-shipping-fast text-primary',
        onConfirm: () => {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
                type: 'POST',
                data: {
                    accion: 'actualizar_entrega',
                    id: id,
                    estado_entrega: 'Enviado',
                    _csrf: '<?php echo csrf_token(); ?>'
                },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(resp) {
                    if (resp.success) {
                        showToast('Estado actualizado', 'success');
                        dtPedidos.ajax.reload();
                    } else {
                        showToast(resp.error || 'Error', 'error');
                    }
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function abrirModalEntregarLista(id) {
    $('#entregar_lista_id').val(id);
    $('#receptor_nombre_lista').val('');
    $('#remito_firmado_lista').val('');
    $('#modalEntregarLista').modal('show');
}

// Adapta los filtros visuales según la pestaña activa
function adaptarFiltros(modo) {
    const $wrapEstado = $('#wrapFiltroEstado');
    const $wrapTipo = $('#wrapFiltroTipo');
    const $wrapPrioridad = $('#wrapFiltroPrioridad');
    const $wrapLogistica = $('#wrapFiltroLogistica');
    const $wrapVista = $('#wrapFiltroVista');
    
    const $selEstado = $('#filtroEstado');
    const $selTipo = $('#filtroTipo');
    const $selVista = $('#filtroVista');
    
    // Primero resetear opciones a full para pedidos
    $selEstado.html(`
        <option value="">Todos</option>
        <option value="Pendiente">Pendiente</option>
        <option value="En Proceso">En Proceso</option>
        <option value="Preparado">Preparado</option>
        <option value="Completado">Completado</option>
        <option value="Rechazado">Rechazado</option>
    `);
    
    $selTipo.html(`
        <option value="">Todos</option>
        <option value="Mantenimiento">Mantenimiento</option>
        <option value="Reparación">Reparación</option>
        <option value="Soporte">Soporte</option>
        <option value="Pedido Insumo">Pedido Insumo</option>
    `);

    // Ocultar todo por defecto y mostrar solo lo necesario
    $wrapEstado.hide();
    $wrapTipo.hide();
    $wrapPrioridad.hide();
    $wrapLogistica.hide();
    $wrapVista.hide();

    $('#filtrosContainer').removeClass('d-none'); // Asegurar que el contenedor de filtros sea visible por defecto en los modos de pedidos

    if (modo === 'tareas_internas') {
        $wrapVista.show();
        $wrapEstado.hide();
        $wrapTipo.hide();
        $wrapPrioridad.hide();
        $wrapLogistica.hide();
    } else {
        $wrapEstado.show();
        $wrapTipo.show();
        $wrapPrioridad.show();
        $wrapVista.hide();

        if (modo === 'pendientes' || modo === 'mis_pedidos') {
            $selEstado.html(`
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En Proceso">En Proceso</option>
            `);
            $selTipo.html(`
                <option value="">Todos</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Reparación">Reparación</option>
                <option value="Soporte">Soporte</option>
            `);
        } else if (modo === 'pedidos_insumos') {
            $wrapEstado.hide();
            $wrapTipo.hide();
            $selEstado.val('Pendiente');
            $selTipo.val('Pedido Insumo');
        } else if (modo === 'logistica') {
            $wrapEstado.hide();
            $wrapTipo.hide();
            $wrapPrioridad.hide();
            $wrapLogistica.show();
        }
    }
}

// ══════════════════════════════════════════════════
// MÓDULO TAREAS INTERNAS
// ══════════════════════════════════════════════════
var dtTareas;
var currentTareasModo = 'activas';

function iniciarTablaTareas(overrideModo) {
    if (overrideModo) {
        currentTareasModo = overrideModo;
    } else {
        currentTareasModo = $('#filtroVista').val() || 'activas';
    }

    if (!dtTareas) {
        dtTareas = $('#tablaTareas').DataTable({
            processing:  true,
            serverSide:  true,
            ajax: {
                url: '<?php echo app_base_url(); ?>/ajax/tareas_list_ssp.php',
                data: function(d) { d.modo = currentTareasModo; },
                xhrFields: { withCredentials: true },
                error: function(xhr, err, code) {
                    showToast('Error al cargar tareas: ' + code, 'error');
                }
            },
            order: [[6, 'desc']],
            columns: [
                { data: 0, width: '60px' },
                { data: 1 },
                { data: 2 },
                { data: 3, width: '120px' },
                { data: 4 },
                { data: 5 },
                { data: 6 },
                { data: 7 }, // Fecha finalización (oculta en activas)
                { data: 8, orderable: false, width: '110px' }
            ],
            drawCallback: function() {
                if (typeof inicializarTooltips === 'function') inicializarTooltips();
            }
        });
    } else {
        dtTareas.ajax.reload();
    }

    // La visibilidad de la columna 7 (Fecha Finalización) ahora depende del modo
    dtTareas.column(7).visible(currentTareasModo === 'historial' || currentTareasModo === 'completadas');
}

// La función cambiarVistaTareas ha sido reemplazada por la lógica en adaptarFiltros y el evento de cambio de #filtroVista

function abrirModalNuevaTarea() {
    // Resetear formulario
    $('#formNuevaTarea')[0].reset();
    // Cargar usuarios en el select de asignación
    var $sel = $('#tareasAsignadoA').empty()
        .append('<option value="">Sin asignar — quedará disponible para tomar</option>');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/usuarios_listar_asignables.php',
        type: 'GET',
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) {
                r.usuarios.forEach(function(u) {
                    $sel.append('<option value="' + u.id_usuario + '">' + u.apellido + ', ' + u.nombre + ' (' + u.username + ')</option>');
                });
            }
        }
    });
    $('#modalNuevaTarea').modal('show');
}

$(document).on('click', '#btnGuardarTarea', function() {
    var titulo = $('#tareasTitulo').val().trim();
    var desc   = $('#tareasDescripcion').val().trim();
    if (!titulo) { showToast('El título es obligatorio', 'warning'); return; }
    if (!desc)   { showToast('La descripción es obligatoria', 'warning'); return; }

    var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'POST',
        data: $('#formNuevaTarea').serialize() + '&accion=crear',
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar Tarea');
            if (r.success) {
                $('#modalNuevaTarea').modal('hide');
                showToast(r.data.mensaje || 'Tarea creada', 'success');
                if (dtTareas) dtTareas.ajax.reload();
            } else {
                showToast(r.error || 'Error al crear tarea', 'error');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar Tarea');
            showToast('Error de conexión', 'error');
        }
    });
});

function verTarea(id) {
    $('#verTareaContenido').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#modalVerTarea').modal('show');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'GET',
        data: { accion: 'obtener', id: id },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (!r.success) { $('#verTareaContenido').html('<div class="alert alert-danger">Error al cargar la tarea</div>'); return; }
            var t = r.data.tarea;
            var estadoClass = { 'Pendiente': 'warning text-dark', 'En Proceso': 'primary', 'Completada': 'success' }[t.estado] || 'secondary';
            var asignado = t.asig_nombre ? (t.asig_nombre + ' ' + t.asig_apellido + ' (' + t.asig_user + ')') : '<em class="text-muted">Sin asignar</em>';
            var fechaFin = t.fecha_finalizacion ? new Date(t.fecha_finalizacion).toLocaleString('es-AR') : '<em class="text-muted">—</em>';
            $('#verTareaContenido').html(
                '<div class="row g-3">'
                + '<div class="col-12"><h5 class="fw-bold mb-1">' + $('<span>').text(t.titulo).html() + '</h5>' +
                  '<span class="badge bg-' + estadoClass + ' fs-6">' + t.estado + '</span></div>'
                + '<div class="col-12"><label class="fw-bold text-muted small">DESCRIPCIÓN</label>' +
                  '<p class="mb-0" style="white-space:pre-wrap">' + $('<span>').text(t.descripcion).html() + '</p></div>'
                + '<div class="col-md-6"><label class="fw-bold text-muted small">CREADO POR</label>' +
                  '<p class="mb-0">' + $('<span>').text(t.creador_nombre + ' ' + t.creador_apellido).html() + '</p></div>'
                + '<div class="col-md-6"><label class="fw-bold text-muted small">ASIGNADO A</label>' +
                  '<p class="mb-0">' + asignado + '</p></div>'
                + '<div class="col-md-6"><label class="fw-bold text-muted small">FECHA CREACIÓN</label>' +
                  '<p class="mb-0">' + new Date(t.fecha_creacion).toLocaleString('es-AR') + '</p></div>'
                + '<div class="col-md-6"><label class="fw-bold text-muted small">FECHA FINALIZACIÓN</label>' +
                  '<p class="mb-0">' + fechaFin + '</p></div>'
                + '</div>'
            );
        },
        error: function() { $('#verTareaContenido').html('<div class="alert alert-danger">Error de conexión</div>'); }
    });
}

function tomarTarea(id) {
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'POST',
        data: { accion: 'tomar', id: id, _csrf: '<?php echo csrf_token(); ?>' },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) { showToast(r.data.mensaje, 'success'); if (dtTareas) dtTareas.ajax.reload(); }
            else showToast(r.error || 'Error al tomar tarea', 'error');
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
}

function completarTarea(id) {
    showConfirm({
        titulo: 'Completar Tarea',
        mensaje: '¿Marcar esta tarea como completada?',
        icono: 'fa-check-circle text-success',
        claseBoton: 'btn-success',
        textoAceptar: 'Completar',
        onConfirm: function() {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                type: 'POST',
                data: { accion: 'completar', id: id, _csrf: '<?php echo csrf_token(); ?>' },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r) {
                    if (r.success) { showToast(r.data.mensaje, 'success'); if (dtTareas) dtTareas.ajax.reload(); }
                    else showToast(r.error || 'Error', 'error');
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function liberarTarea(id) {
    showConfirm({
        titulo: 'Liberar Tarea',
        mensaje: '¿Devolver esta tarea al pool de pendientes?',
        icono: 'fa-undo text-warning',
        claseBoton: 'btn-warning',
        textoAceptar: 'Liberar',
        onConfirm: function() {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                type: 'POST',
                data: { accion: 'liberar', id: id, _csrf: '<?php echo csrf_token(); ?>' },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r) {
                    if (r.success) { showToast(r.data.mensaje, 'success'); if (dtTareas) dtTareas.ajax.reload(); }
                    else showToast(r.error || 'Error', 'error');
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function eliminarTarea(id) {
    showConfirm({
        titulo: 'Eliminar Tarea',
        mensaje: '¿Eliminar esta tarea definitivamente?',
        icono: 'fa-trash-alt text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: function() {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                type: 'POST',
                data: { accion: 'eliminar', id: id, _csrf: '<?php echo csrf_token(); ?>' },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r) {
                    if (r.success) { showToast(r.data.mensaje, 'success'); if (dtTareas) dtTareas.ajax.reload(); }
                    else showToast(r.error || 'Error', 'error');
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function abrirModalAsignarTarea(id) {
    $('#idTareaAsignar').val(id);
    var $sel = $('#selectUsuarioAsignarTarea').empty().append('<option value="">Seleccione usuario...</option>');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/usuarios_listar_asignables.php',
        type: 'GET',
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) {
                r.usuarios.forEach(function(u) {
                    $sel.append('<option value="' + u.id_usuario + '">' + u.apellido + ', ' + u.nombre + ' (' + u.username + ')</option>');
                });
                $('#modalAsignarTarea').modal('show');
            } else {
                showToast('Error al cargar usuarios', 'error');
            }
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
}

$(document).on('click', '#btnConfirmarAsignarTarea', function() {
    var userId = $('#selectUsuarioAsignarTarea').val();
    if (!userId) { showToast('Seleccione un usuario', 'warning'); return; }
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'POST',
        data: $('#formAsignarTarea').serialize(),
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) {
                $('#modalAsignarTarea').modal('hide');
                showToast(r.data.mensaje, 'success');
                if (dtTareas) dtTareas.ajax.reload();
            } else {
                showToast(r.error || 'Error al asignar', 'error');
            }
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
});

</script>
