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

// Obtener contadores para las pestañas
$uId = obtenerUsuarioId();
$db = conectarDB();

// 1. Tareas Internas (Pool)
$n_tareas = (int)$db->query("SELECT COUNT(*) FROM tareas_internas WHERE estado IN ('Pendiente', 'En Proceso') AND (asignado_a IS NULL OR asignado_a = 0)")->fetchColumn();

// 2. Pendientes Técnicos (Pool)
$n_pend_tec = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE tipo IN ('Mantenimiento', 'Reparación', 'Soporte') AND estado IN ('Pendiente', 'En Proceso') AND (asignado_a IS NULL OR asignado_a = 0)")->fetchColumn();

// 3. Pedidos de Insumos (Pendientes)
$n_insumos = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE tipo = 'Pedido Insumo' AND estado = 'Pendiente'")->fetchColumn();

// 4. Logística (En Tránsito) - Tanto pedidos de insumos preparados como técnicos completados
$n_logistica = (int)$db->query("SELECT COUNT(*) FROM pedidos 
    WHERE ((tipo = 'Pedido Insumo' AND estado = 'Preparado') OR (tipo != 'Pedido Insumo' AND estado = 'Completado')) 
    AND estado_entrega NOT IN ('Entregado', 'De Baja')")->fetchColumn();

// 5. Mis Tareas (Asignadas a mi)
$stmtMP = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE asignado_a = ? AND estado IN ('Pendiente', 'En Proceso')");
$stmtMP->execute([$uId]);
$total_mis_pedidos = (int)$stmtMP->fetchColumn();

$stmtMT = $db->prepare("SELECT COUNT(*) FROM tareas_internas WHERE asignado_a = ? AND estado IN ('Pendiente', 'En Proceso')");
$stmtMT->execute([$uId]);
$total_mis_tareas = (int)$stmtMT->fetchColumn();

$total_mis_cosas = $total_mis_pedidos + $total_mis_tareas;
?>

<?php include '../../includes/header.php'; ?>

<style>
.modo-historial .dataTables_filter {
    display: none !important;
}

/* Botón y animación para el colapso de las tablas */
.fa-chevron-down[data-bs-toggle="collapse"] {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    cursor: pointer;
    transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    font-size: 0.8em;
}
.fa-chevron-down[data-bs-toggle="collapse"]:hover {
    background-color: var(--sitia-success, #5cab7d);
    color: #ffffff;
    border-color: var(--sitia-success, #5cab7d) !important;
}

/* --- VERSION MODO OSCURO --- */
[data-theme="dark"] .fa-chevron-down[data-bs-toggle="collapse"] {
    background-color: var(--sitia-surface-2, #60736b);
    border-color: var(--sitia-border, #7a9187);
    color: var(--sitia-text, #f4f7f5);
}
[data-theme="dark"] .fa-chevron-down[data-bs-toggle="collapse"]:hover {
    background-color: var(--sitia-success, #9de2b9);
    color: #1e2522; /* Texto oscuro para legibilidad sobre fondo pastel */
    border-color: var(--sitia-success, #9de2b9) !important;
}

/* Rotar flecha cuando el acordeón está desplegado (NO colapsado) */
.fa-chevron-down[data-bs-toggle="collapse"]:not(.collapsed) {
    transform: rotate(180deg);
}
</style>

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
        <i class="fas fa-tasks me-2"></i>Tareas Internas (<?php echo $n_tareas; ?>)
    </button>
  </li>

  <?php if ($puedeVerPendientes): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="pendientes" data-bs-toggle="tooltip" title="Tareas técnicas sin asignar">
        <i class="fas fa-tools me-2"></i>Pendientes Técnicos (<?php echo $n_pend_tec; ?>)
    </button>
  </li>
  <?php endif; ?>
  
  <?php if (tienePermiso('pedidos', 'ver_todos')): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="pedidos_insumos" title="Solicitudes de insumos pendientes de preparación">
        <i class="fas fa-boxes me-2"></i>Pedidos de Insumos (<?php echo $n_insumos; ?>)
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="transito-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="logistica" title="Pedidos preparados en proceso de entrega">
        <i class="fas fa-shipping-fast me-2"></i>En Tránsito (<?php echo $n_logistica; ?>)
    </button>
  </li>
  <?php endif; ?>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="mis-pedidos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="mis_pedidos" title="Tareas técnicas y tareas internas asignadas a mi usuario">
        <i class="fas fa-user-clock me-2"></i>Mis Tareas (<?php echo $total_mis_cosas; ?>)
    </button>
  </li>

  <?php if (tieneRol([1, 2])): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="todos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="todos" title="Historial completo de pedidos">
        <i class="fas fa-history me-2"></i>Historial
    </button>
  </li>
  <?php endif; ?>
</ul>

<div id="seccionPedidos" class="d-none">

<!-- Filtros -->
<div class="filtros-container mt-4 mb-4" id="filtrosContainer">
    <form id="formFiltros" class="row g-3 align-items-end" novalidate>
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
        
        
        <div class="col-md-3" id="wrapFiltroPrioridad">
            <label class="form-label">Prioridad</label>
            <select class="form-select" id="filtroPrioridad">
                <option value="">Todas</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>
        </div>

        <div class="col-md-3" id="wrapFiltroAsignado" style="display:none;">
            <label class="form-label">Técnico Asignado</label>
            <select class="form-select" id="filtroAsignado">
                <option value="">Todos</option>
            </select>
        </div>

        <div class="col-md-3" id="wrapFiltroBusqueda" style="display:none;">
            <label class="form-label">Buscar</label>
            <input type="text" class="form-control" id="filtroBusqueda" placeholder="Agente, técnico o título...">
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
        
        <div class="col-md-3 d-flex align-items-end ms-auto" id="wrapFiltroAcciones">
            <div class="d-grid gap-1 w-100" id="btnContainerFiltros">
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
    <div class="card-header" id="headerPedidos">
        <h5 class="mb-0 d-flex align-items-center justify-content-between">
            <span class="pe-3"><i class="fas fa-list me-2"></i><span id="tituloSeccionPedidos">Listado de Pedidos</span></span>
            <i class="fas fa-chevron-down" style="display: none;"></i>
        </h5>
    </div>
    <div class="collapse show" id="collapsePedidos">
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
</div>

</div><!-- /seccionPedidos -->

<!-- ══════════════════════════════════════════════════════════ -->
<!-- SECCIÓN TAREAS INTERNAS                                    -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="seccionTareas" class="d-none">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header" id="headerTareas">
            <h5 class="mb-0 d-flex align-items-center justify-content-between">
                <span class="pe-3"><i class="fas fa-tasks me-2"></i><span id="tituloSeccionTareas">Tareas Internas del Área</span></span>
                <i class="fas fa-chevron-down" style="display: none;"></i>
            </h5>
        </div>
        <div class="collapse show" id="collapseTareas">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="tablaTareas" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Título</th>
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
                    <input type="hidden" name="accion" id="tareasAccion" value="crear">
                    <input type="hidden" name="id" id="tareasId" value="">

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

                    <div class="mb-3 form-check form-switch" id="wrapEsColaborativa">
                        <input class="form-check-input" type="checkbox" name="es_colaborativa" id="tareasEsColaborativa" value="1">
                        <label class="form-check-label fw-bold" for="tareasEsColaborativa">Hacer colaborativa (en proceso desde el inicio, sin asignación única)</label>
                    </div>

                    <div class="mb-3" id="wrapAsignadoA">
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
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cerrar</button>
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

// Helper: cierra todos los tooltips abiertos antes de recargar la tabla
// Necesario para evitar que los tooltips queden flotando cuando se destruye el botón
function cerrarTooltips() {
    try {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            var t = bootstrap.Tooltip.getInstance(el);
            if (t) { t.hide(); }
        });
        // Eliminar cualquier tooltip huérfano del DOM directamente
        document.querySelectorAll('.tooltip').forEach(function(el) { el.remove(); });
    } catch(e) {}
}

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
                d.prioridad = $('#filtroPrioridad').val();
                d.logistica = $('#filtroLogistica').val();
                d.asignado_a = $('#filtroAsignado').val();
                if ($('#filtroModo').val() === 'todos') {
                    d.search = { value: $('#filtroBusqueda').val(), regex: false };
                }
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

    function recargarTablas() {
        const modo = $('#filtroModo').val();
        if (modo === 'tareas_internas' || $('#tareas-tab').hasClass('active')) {
            currentTareasModo = $('#filtroVista').val() || 'activas';
            cerrarTooltips(); dtTareas.ajax.reload();
        } else if (modo === 'todos' || modo === 'mis_pedidos') {
            if (dtPedidos) dtPedidos.ajax.reload();
            cerrarTooltips(); dtTareas.ajax.reload();
        } else {
            if (dtPedidos) dtPedidos.ajax.reload();
        }
    }

    // Eventos de Filtros
    $('#filtroEstado, #filtroPrioridad, #filtroLogistica, #filtroVista, #filtroAsignado').on('change', function() {
        recargarTablas();
    });

    $('#btnLimpiar').on('click', function() {
        $('#filtroEstado, #filtroPrioridad, #filtroLogistica, #filtroAsignado, #filtroBusqueda').val('');
        $('#filtroVista').val('activas');
        recargarTablas();
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

        if (modo === 'todos' || modo === 'mis_pedidos') {
            if (modo === 'todos') {
                $('body').addClass('modo-historial');
            } else {
                $('body').removeClass('modo-historial');
            }
            // Habilitar comportamiento de acordeón sólo en los botones de flecha
            $('#headerPedidos .fa-chevron-down').attr({'data-bs-toggle': 'collapse', 'data-bs-target': '#collapsePedidos'});
            $('#headerTareas .fa-chevron-down').attr({'data-bs-toggle': 'collapse', 'data-bs-target': '#collapseTareas'});
            $('#headerPedidos .fa-chevron-down, #headerTareas .fa-chevron-down').show();
        } else {
            $('body').removeClass('modo-historial');
            // Deshabilitar comportamiento de acordeón
            $('#headerPedidos .fa-chevron-down, #headerTareas .fa-chevron-down').removeAttr('data-bs-toggle').removeAttr('data-bs-target');
            $('#headerPedidos .fa-chevron-down, #headerTareas .fa-chevron-down').hide();
            // Asegurar que estén abiertos
            $('#collapsePedidos, #collapseTareas').addClass('show').css('display', '');
        }

        if (section === 'tareas') {
            $('#btnNuevaTarea').removeClass('d-none');
            $('#seccionTareas').removeClass('d-none');
            $('#filtrosContainer').removeClass('d-none');
            iniciarTablaTareas('activas');
            $('#tituloSeccionTareas').text('Tareas Internas del Área');
        } else {
            $('#btnGrupoPedidos').removeClass('d-none');
            $('#seccionPedidos').removeClass('d-none');
            $('#filtrosContainer').removeClass('d-none');
            $('#tituloSeccionPedidos').text('Listado de Pedidos');
            
            if (dtPedidos) {
                // Columnas Visibles según modo
                const colTipo = 2;
                const colPrioridad = 3;
                const colEstado = 4;
                const colAsignado = 6;
                const colLogistica = 7;
                
                // Resetear visibilidad por defecto
                dtPedidos.column(colTipo).visible(true);
                dtPedidos.column(colPrioridad).visible(true);
                dtPedidos.column(colEstado).visible(true);
                dtPedidos.column(colAsignado).visible(true);
                dtPedidos.column(colLogistica).visible(true);

                if (modo === 'pedidos_insumos') {
                    dtPedidos.column(colTipo).visible(false);
                    dtPedidos.column(colAsignado).visible(false);
                    dtPedidos.column(colLogistica).visible(false);
                } else if (['pendientes', 'mis_pedidos'].includes(modo)) {
                    if (modo === 'mis_pedidos') {
                        dtPedidos.column(colEstado).visible(false);
                    }
                    dtPedidos.column(colAsignado).visible(false);
                    dtPedidos.column(colLogistica).visible(false);
                } else if (modo === 'logistica') {
                    dtPedidos.column(colPrioridad).visible(false);
                    dtPedidos.column(colEstado).visible(false);
                    dtPedidos.column(colAsignado).visible(false);
                    dtPedidos.column(colLogistica).visible(true);
                }
                
                dtPedidos.ajax.reload();
            }
            
            // Si el modo es mis_pedidos, mostrar también Tareas Internas pero filtrado a "mis_tareas"
            if (modo === 'mis_pedidos') {
                $('#seccionTareas').removeClass('d-none');
                $('#tituloSeccionTareas').text('Mis Tareas Internas (Asignadas a mi)');
                iniciarTablaTareas('mis_tareas');
            } else if (modo === 'todos') {
                // Historial: Mostrar ambas tablas colapsables
                $('#seccionTareas').removeClass('d-none');
                $('#tituloSeccionPedidos').text('Historial de Pedidos Técnicos');
                $('#tituloSeccionTareas').text('Historial de Tareas Internas');
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
        recargarTablas();
    });

    // Búsqueda en tiempo real con debounce
    var filtroBusquedaTimer;
    $('#filtroBusqueda').on('input', function() {
        clearTimeout(filtroBusquedaTimer);
        filtroBusquedaTimer = setTimeout(function() {
            recargarTablas();
        }, 400);
    });

    // Cargar select de usuarios para el filtro de asignado
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/usuarios_listar_asignables.php',
        type: 'GET', dataType: 'json', xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) {
                r.usuarios.forEach(function(u) {
                    $('#filtroAsignado').append(
                        '<option value="' + u.id_usuario + '">' + u.apellido + ', ' + u.nombre + '</option>'
                    );
                });
            }
        }
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
        error: function(xhr) { 
            let msg = 'Error de conexión';
            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
            showToast(msg, 'error'); 
        }
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
                error: function(xhr) { 
                    let msg = 'Error de conexión';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    showToast(msg, 'error'); 
                }
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
        error: function(xhr) { 
            let msg = 'Error de conexión';
            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
            showToast(msg, 'error'); 
        }
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
            error: function(xhr) { 
                let msg = 'Error de conexión';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                showToast(msg, 'error'); 
            }
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
            error: function(xhr) { 
                $btn.prop('disabled', false).html('Registrar Entrega');
                let msg = 'Error de conexión al subir los datos';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                showToast(msg, 'error'); 
            }
        });
    });
});

function actualizarLogistica(id, estadoActual, metodo) {
    const texto = (metodo === 'Envío') 
        ? '¿Confirmar que el pedido ha salido hacia su destino?' 
        : '¿Confirmar que el pedido ha sido retirado?';
    
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
                error: function(xhr) { 
                    let msg = 'Error de conexión';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    showToast(msg, 'error'); 
                }
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
    const $wrapPrioridad = $('#wrapFiltroPrioridad');
    const $wrapAsignado = $('#wrapFiltroAsignado');
    const $wrapBusqueda = $('#wrapFiltroBusqueda');
    const $wrapLogistica = $('#wrapFiltroLogistica');
    const $wrapVista = $('#wrapFiltroVista');
    
    const $selEstado = $('#filtroEstado');
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

    // Ocultar todo por defecto y mostrar solo lo necesario
    $wrapEstado.hide();
    $wrapPrioridad.hide();
    $wrapAsignado.hide();
    $wrapBusqueda.hide();
    $wrapLogistica.hide();
    $wrapVista.hide();

    $('#filtrosContainer').removeClass('d-none'); // Asegurar que el contenedor de filtros sea visible por defecto en los modos de pedidos

    if (modo === 'tareas_internas') {
        $wrapVista.show();
        $wrapEstado.hide();
        $wrapPrioridad.hide();
        $wrapLogistica.hide();
    } else if (modo === 'todos') {
        // Historial
        $wrapEstado.show();
        $wrapAsignado.show(); // Mostrar asignado en lugar de prioridad
        $wrapBusqueda.show(); // Mostrar buscador unificado
    } else {
        $wrapEstado.show();
        $wrapPrioridad.show();
        $wrapVista.hide();

        if (modo === 'pendientes' || modo === 'mis_pedidos') {
            $selEstado.html(`
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En Proceso">En Proceso</option>
            `);
        } else if (modo === 'pedidos_insumos') {
            $wrapEstado.hide();
            $selEstado.val('Pendiente');
        } else if (modo === 'logistica') {
            $wrapEstado.hide();
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
                data: function(d) { 
                    d.modo = currentTareasModo; 
                    d.asignado_a = $('#filtroAsignado').val();
                    d.estado = $('#filtroEstado').val();
                    if ($('#filtroModo').val() === 'todos') {
                        d.search = { value: $('#filtroBusqueda').val(), regex: false };
                    }
                },
                xhrFields: { withCredentials: true },
                error: function(xhr, err, code) {
                    showToast('Error al cargar tareas: ' + code, 'error');
                }
            },
            order: [[5, 'desc']],
            columns: [
                { data: 0, width: '60px' },
                { data: 1 },
                { data: 2, width: '120px' },
                { data: 3 },
                { data: 4 },
                { data: 5 },
                { data: 6 }, // Fecha finalización (oculta en activas)
                { data: 7, orderable: false, width: '110px' }
            ],
            drawCallback: function() {
                if (typeof inicializarTooltips === 'function') inicializarTooltips();
            }
        });
    } else {
        cerrarTooltips();
        dtTareas.ajax.reload();
    }

    // La visibilidad de las columnas ahora depende del modo
    dtTareas.column(2).visible(currentTareasModo !== 'mis_tareas');
    dtTareas.column(6).visible(currentTareasModo === 'historial' || currentTareasModo === 'completadas');
}

// La función cambiarVistaTareas ha sido reemplazada por la lógica en adaptarFiltros y el evento de cambio de #filtroVista

function abrirModalNuevaTarea() {
    // Resetear formulario
    $('#formNuevaTarea')[0].reset();
    $('#tareasId').val('');
    $('#tareasAccion').val('crear');
    $('#modalNuevaTarea .modal-title').html('<i class="fas fa-plus-circle me-2"></i>Nueva Tarea Interna');
    $('#btnGuardarTarea').html('<i class="fas fa-save me-1"></i>Guardar Tarea');

    $('#tareasEsColaborativa').prop('checked', false).prop('disabled', false);
    $('#wrapAsignadoA').show();

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

function editarTarea(id) {
    // Resetear formulario
    $('#formNuevaTarea')[0].reset();
    $('#tareasId').val(id);
    $('#tareasAccion').val('editar');
    $('#modalNuevaTarea .modal-title').html('<i class="fas fa-edit me-2"></i>Editar Tarea Interna');
    $('#btnGuardarTarea').html('<i class="fas fa-save me-1"></i>Actualizar Tarea');

    // Cargar datos de la tarea
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'GET',
        data: { accion: 'obtener', id: id },
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            if (r.success) {
                var t = r.data.tarea;
                $('#tareasTitulo').val(t.titulo);
                $('#tareasDescripcion').val(t.descripcion);
                
                $('#tareasEsColaborativa').prop('checked', t.es_colaborativa == 1).prop('disabled', true);
                if (t.es_colaborativa == 1) {
                    $('#wrapAsignadoA').hide();
                } else {
                    $('#wrapAsignadoA').show();
                }

                // Cargar usuarios en el select de asignación
                var $sel = $('#tareasAsignadoA').empty()
                    .append('<option value="">Sin asignar — quedará disponible para tomar</option>');
                $.ajax({
                    url: '<?php echo app_base_url(); ?>/ajax/usuarios_listar_asignables.php',
                    type: 'GET',
                    dataType: 'json',
                    xhrFields: { withCredentials: true },
                    success: function(ru) {
                        if (ru.success) {
                            ru.usuarios.forEach(function(u) {
                                let selected = (t.asignado_a == u.id_usuario) ? 'selected' : '';
                                $sel.append('<option value="' + u.id_usuario + '" ' + selected + '>' + u.apellido + ', ' + u.nombre + ' (' + u.username + ')</option>');
                            });
                        }
                    }
                });
                $('#modalNuevaTarea').modal('show');
            } else {
                showToast(r.error || 'Error al obtener datos', 'error');
            }
        }
    });
}

$(document).on('change', '#tareasEsColaborativa', function() {
    if (this.checked) {
        $('#wrapAsignadoA').slideUp();
    } else {
        $('#wrapAsignadoA').slideDown();
    }
});

$(document).on('click', '#btnGuardarTarea', function() {
    var titulo = $('#tareasTitulo').val().trim();
    var desc   = $('#tareasDescripcion').val().trim();
    var accion = $('#tareasAccion').val();
    var label  = (accion === 'editar') ? 'Actualizar Tarea' : 'Guardar Tarea';

    if (!titulo) { showToast('El título es obligatorio', 'warning'); return; }
    if (!desc)   { showToast('La descripción es obligatoria', 'warning'); return; }

    var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
    $.ajax({
        url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
        type: 'POST',
        data: $('#formNuevaTarea').serialize(),
        dataType: 'json',
        xhrFields: { withCredentials: true },
        success: function(r) {
            $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>' + label);
            if (r.success) {
                $('#modalNuevaTarea').modal('hide');
                showToast(r.mensaje || r.data.mensaje || 'Tarea guardada', 'success');
                cerrarTooltips(); dtTareas.ajax.reload();
            } else {
                showToast(r.error || 'Error al guardar tarea', 'error');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>' + label);
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
            var comentarios = r.data.comentarios || [];
            var estadoClass = { 'Pendiente': 'warning text-dark', 'En Proceso': 'primary', 'Completada': 'success' }[t.estado] || 'secondary';
            var asignado = t.es_colaborativa == 1 
                ? '<span class="badge badge-colaborativa"><i class="fas fa-users me-1"></i>Colaborativa</span>'
                : (t.asig_nombre ? (t.asig_nombre + ' ' + t.asig_apellido + ' (' + t.asig_user + ')') : '<em class="text-muted">Sin asignar</em>');
            var fechaFin = t.fecha_finalizacion ? new Date(t.fecha_finalizacion).toLocaleString('es-AR') : '<em class="text-muted">—</em>';
            
            var html = '<div class="row g-3">'
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
                + (t.comentario ? '<div class="col-12 mt-3"><label class="fw-bold text-muted small">COMENTARIO FINAL</label><div class="p-2 bg-light border rounded" style="white-space:pre-wrap">' + $('<span>').text(t.comentario).html() + '</div></div>' : '')
                + (t.adjunto_path ? '<div class="col-12 mt-2"><label class="fw-bold text-muted small">ADJUNTO</label><br><a href="<?php echo app_base_url(); ?>/uploads/tareas/' + t.adjunto_path + '" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="fas fa-paperclip me-1"></i>Ver adjunto</a></div>' : '');

            // Sección de comentarios
            html += '<div class="col-12 mt-4"><hr class="border-2 opacity-25">'
                + '<h6 class="fw-bold mb-3 d-flex align-items-center"><i class="fas fa-comments me-2 text-primary"></i>Historial de Comentarios</h6>';

            function renderComentarioHtml(c, esSuperAdmin) {
                var fechaCom = new Date(c.fecha).toLocaleString('es-AR');
                var isHidden = c.visible == 0;
                
                // Cajas consistentes sin borde izquierdo de color, usando borde simple y fondo según visibilidad
                var boxStyle = isHidden 
                    ? 'border bg-warning bg-opacity-10' 
                    : 'border bg-light shadow-sm';
                
                var hideBadge = isHidden ? '<span class="badge bg-warning text-dark me-2 d-inline-flex align-items-center"><i class="fas fa-eye-slash me-1"></i>Oculto para usuarios</span>' : '';
                
                var toggleButton = '';
                if (esSuperAdmin) {
                    var title = isHidden ? 'Hacer visible para todos' : 'Ocultar para otros usuarios';
                    var icon = isHidden ? 'fa-eye' : 'fa-eye-slash';
                    var btnClass = isHidden ? 'btn-soft-success text-success border-success bg-success bg-opacity-10' : 'btn-soft-warning text-warning border-warning bg-warning bg-opacity-10';
                    toggleButton = '<button class="btn btn-xs ' + btnClass + ' ms-2 px-2 py-1 toggle-vis-comentario" data-id="' + c.id_comentario + '" title="' + title + '" style="border: 1px solid; transition: all 0.2s;">'
                        + '<i class="fas ' + icon + ' me-1"></i>' + (isHidden ? 'Mostrar' : 'Ocultar')
                        + '</button>';
                }

                return '<div class="p-3 rounded mb-3 ' + boxStyle + '" style="transition: all 0.3s;">'
                    + '<div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<div>'
                    + '<span class="fw-bold text-dark dark-text-light"><i class="fas fa-user-circle me-1 text-muted"></i>' + $('<span>').text(c.nombre + ' ' + c.apellido).html() + '</span>'
                    + '<span class="text-muted small ms-2">(' + $('<span>').text(c.username).html() + ')</span>'
                    + '</div>'
                    + '<div class="d-flex align-items-center">'
                    + hideBadge
                    + '<span class="text-muted small"><i class="far fa-clock me-1"></i>' + fechaCom + '</span>'
                    + toggleButton
                    + '</div>'
                    + '</div>'
                    + '<div style="white-space:pre-wrap; line-height: 1.5;" class="text-dark dark-text-light ' + (isHidden ? 'fst-italic' : '') + '">' + $('<span>').text(c.comentario).html() + '</div>'
                    + '</div>';
            }

            // Obtener el flag de si es Super Administrador directamente del rol en la sesión PHP
            var esSuperAdmin = <?php echo (obtenerUsuario()['id_rol'] == 1) ? 'true' : 'false'; ?>;

            if (comentarios.length === 0) {
                html += '<div class="p-4 bg-light border border-dashed rounded-3 text-center">'
                    + '<i class="fas fa-comments fa-2x text-muted mb-2"></i>'
                    + '<p class="text-muted mb-0">No hay comentarios registrados en esta tarea.</p>'
                    + '</div>';
            } else if (comentarios.length === 1) {
                html += '<div class="mb-3" style="max-height: 350px; overflow-y: auto;">';
                html += renderComentarioHtml(comentarios[0], esSuperAdmin);
                html += '</div>';
            } else {
                var ultCom = comentarios[comentarios.length - 1];
                var antComentarios = comentarios.slice(0, comentarios.length - 1);

                html += '<div class="d-grid mb-3">'
                    + '<button class="btn btn-xs btn-outline-secondary d-flex align-items-center justify-content-center py-2" type="button" data-bs-toggle="collapse" data-bs-target="#comentariosAnteriores" aria-expanded="false" id="btnToggleComentarios">'
                    + '<i class="fas fa-history me-2"></i> Mostrar comentarios anteriores (' + antComentarios.length + ')'
                    + '</button>'
                    + '</div>';

                html += '<div class="collapse mb-3" id="comentariosAnteriores" style="max-height: 300px; overflow-y: auto;">';
                antComentarios.forEach(function(c) {
                    html += renderComentarioHtml(c, esSuperAdmin);
                });
                html += '</div>';

                html += '<div class="mb-3">'
                    + '<div class="fw-bold small text-muted mb-2"><i class="fas fa-comment-dots me-1 text-primary"></i>Último comentario:</div>'
                    + renderComentarioHtml(ultCom, esSuperAdmin)
                    + '</div>';
            }

            // Formulario para agregar comentarios si la tarea NO está completada
            if (t.estado !== 'Completada') {
                html += '<form id="formAgregarComentarioTarea" class="mt-3">'
                    + '<input type="hidden" name="accion" value="agregar_comentario">'
                    + '<input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">'
                    + '<input type="hidden" name="id_tarea" value="' + t.id_tarea + '">'
                    + '<div class="row g-2">'
                    + '<div class="col">'
                    + '<textarea class="form-control" name="comentario" id="comentarioTareaTxt" rows="2" placeholder="Escribe un comentario..." required></textarea>'
                    + '</div>'
                    + '<div class="col-auto d-flex align-items-stretch">'
                    + '<button class="btn btn-primary px-3 d-flex align-items-center" type="submit" id="btnEnviarComentarioTarea"><i class="fas fa-paper-plane"></i></button>'
                    + '</div>'
                    + '</div>'
                    + '</form>';
            }

            html += '</div>'; // fin col-12
            html += '</div>'; // fin row

            $('#verTareaContenido').html(html);

            // Listeners para cambiar texto del botón
            $(document).off('show.bs.collapse', '#comentariosAnteriores').on('show.bs.collapse', '#comentariosAnteriores', function() {
                $('#btnToggleComentarios').html('<i class="fas fa-chevron-up me-1"></i> Ocultar comentarios anteriores');
            });
            $(document).off('hide.bs.collapse', '#comentariosAnteriores').on('hide.bs.collapse', '#comentariosAnteriores', function() {
                var count = comentarios.length - 1;
                $('#btnToggleComentarios').html('<i class="fas fa-history me-1"></i> Mostrar comentarios anteriores (' + count + ')');
            });

            // Handler para toggle de visibilidad del comentario
            $('.toggle-vis-comentario').off('click').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var idComentario = $btn.data('id');
                $btn.prop('disabled', true);
                $.ajax({
                    url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                    type: 'POST',
                    data: {
                        accion: 'toggle_visibilidad_comentario',
                        id_comentario: idComentario,
                        _csrf: '<?php echo csrf_token(); ?>'
                    },
                    dataType: 'json',
                    xhrFields: { withCredentials: true },
                    success: function(res) {
                        if (res.success) {
                            var msg = res.data && res.data.mensaje ? res.data.mensaje : (res.mensaje || 'Visibilidad cambiada');
                            showToast(msg, 'success');
                            verTarea(t.id_tarea);
                        } else {
                            $btn.prop('disabled', false);
                            showToast(res.error || 'Error al cambiar visibilidad', 'error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        var errMsg = 'Error de conexión';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errMsg = xhr.responseJSON.error;
                        }
                        showToast(errMsg, 'error');
                    }
                });
            });

            // Handler para enviar comentario
            $('#formAgregarComentarioTarea').on('submit', function(e) {
                e.preventDefault();
                var text = $('#comentarioTareaTxt').val().trim();
                if (!text) return;
                var $btn = $('#btnEnviarComentarioTarea').prop('disabled', true);
                $.ajax({
                    url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    xhrFields: { withCredentials: true },
                    success: function(cr) {
                        $btn.prop('disabled', false);
                        if (cr.success) {
                            showToast(cr.mensaje || 'Comentario agregado', 'success');
                            verTarea(t.id_tarea);
                        } else {
                            showToast(cr.error || 'Error al agregar comentario', 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        showToast('Error de conexión', 'error');
                    }
                });
            });
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
            if (r.success) { showToast(r.data.mensaje, 'success'); cerrarTooltips(); dtTareas.ajax.reload(); }
            else showToast(r.error || 'Error al tomar tarea', 'error');
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
}

function completarTarea(id) {
    // Hacer el modal más ancho temporalmente para comodidad del comentario
    const $m = $('#modalConfirmacionSITIA');
    $m.find('.modal-dialog').addClass('modal-lg');
    $m.one('hidden.bs.modal', function() { $(this).find('.modal-dialog').removeClass('modal-lg'); });

    showConfirm({
        titulo: 'Completar Tarea',
        mensaje: '<p class="mb-2">¿Marcar esta tarea como completada?</p>' +
                 '<div class="text-start">' +
                 '<label class="form-label small fw-bold mb-1">Comentario final:</label>' +
                 '<textarea id="comentario_tarea_final" class="form-control mb-2" rows="5" placeholder="Escriba aquí los detalles del trabajo finalizado..."></textarea>' +
                 '<label class="form-label small fw-bold mb-1">Adjunto (opcional):</label>' +
                 '<input type="file" id="adjunto_tarea_final" class="form-control">' +
                 '</div>',
        icono: 'fa-check-circle text-success',
        claseBoton: 'btn-success',
        textoAceptar: 'Completar',
        onConfirm: function() {
            let formData = new FormData();
            formData.append('accion', 'completar');
            formData.append('id', id);
            formData.append('comentario', $('#comentario_tarea_final').val());
            formData.append('_csrf', '<?php echo csrf_token(); ?>');
            
            let fileInput = document.getElementById('adjunto_tarea_final');
            if (fileInput.files.length > 0) {
                formData.append('adjunto', fileInput.files[0]);
            }

            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r) {
                    if (r.success) { 
                        showToast(r.data.mensaje, 'success'); 
                        cerrarTooltips(); dtTareas.ajax.reload(); 
                    } else { 
                        showToast(r.error || 'Error', 'error'); 
                    }
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
                    if (r.success) { showToast(r.data.mensaje, 'success'); cerrarTooltips(); dtTareas.ajax.reload(); }
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
                    if (r.success) { showToast(r.data.mensaje, 'success'); cerrarTooltips(); dtTareas.ajax.reload(); }
                    else showToast(r.error || 'Error', 'error');
                },
                error: function() { showToast('Error de conexión', 'error'); }
            });
        }
    });
}

function cambiarTipoTarea(id, esColaborativaActual) {
    var esColab = parseInt(esColaborativaActual) === 1;
    var titulo, mensaje, icono;

    if (esColab) {
        // Colaborativa → Asignable
        titulo   = 'Convertir a Tarea Asignable';
        mensaje  = '¿Convertir esta tarea a <strong>Asignable</strong>? Se liberará del modo colaborativo, quedará en estado <em>Pendiente</em> y podrá ser tomada o asignada a un técnico.';
        icono    = 'fa-user-slash text-secondary';
    } else {
        // Asignable → Colaborativa
        titulo   = 'Convertir a Tarea Colaborativa';
        mensaje  = '¿Convertir esta tarea a <strong>Colaborativa</strong>? Se eliminará la asignación individual y cualquier técnico podrá completarla.';
        icono    = 'fa-users text-secondary';
    }

    showConfirm({
        titulo: titulo,
        mensaje: mensaje,
        icono: icono,
        claseBoton: 'btn-secondary',
        textoAceptar: 'Confirmar',
        onConfirm: function() {
            $.ajax({
                url: '<?php echo app_base_url(); ?>/ajax/tareas_acciones.php',
                type: 'POST',
                data: { accion: 'cambiar_tipo', id: id, _csrf: '<?php echo csrf_token(); ?>' },
                dataType: 'json',
                xhrFields: { withCredentials: true },
                success: function(r) {
                    if (r.success) {
                        showToast(r.data.mensaje, 'success');
                        cerrarTooltips();
                        dtTareas.ajax.reload();
                    } else {
                        showToast(r.error || 'Error al convertir tarea', 'error');
                    }
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
                cerrarTooltips(); dtTareas.ajax.reload();
            } else {
                showToast(r.error || 'Error al asignar', 'error');
            }
        },
        error: function() { showToast('Error de conexión', 'error'); }
    });
});

</script>
