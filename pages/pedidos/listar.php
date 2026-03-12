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
    <a href="<?php echo app_base_url(); ?>/pages/pedidos/crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nuevo Pendiente
    </a>
    <?php endif; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="pedidosTabs" role="tablist">
  <?php if ($puedeVerPendientes): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="pendientes">
        <i class="fas fa-tasks me-2"></i>Pendientes
    </button>
  </li>
  <?php endif; ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link<?php echo !$puedeVerPendientes ? ' active' : ''; ?>" id="mis-pedidos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="mis_pedidos">
        <i class="fas fa-user-clock me-2"></i>Mis Pendientes
    </button>
  </li>
  <?php if (tienePermiso('pedidos', 'ver_todos')): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="todos-tab" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab" data-modo="todos">
        <i class="fas fa-list me-2"></i>Historial Global
    </button>
  </li>
  <?php endif; ?>
</ul>

<!-- Filtros -->
<div class="filtros-container mb-4">
    <form id="formFiltros" class="row g-3 align-items-end">
        <input type="hidden" id="filtroModo" name="modo" value="<?php echo $puedeVerPendientes ? 'pendientes' : 'mis_pedidos'; ?>">
        
        <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select class="form-select" id="filtroEstado">
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En Proceso">En Proceso</option>
                <option value="Completado">Completado</option>
                <option value="Rechazado">Rechazado</option>
            </select>
        </div>
        
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select class="form-select" id="filtroTipo">
                <option value="">Todos</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Reparación">Reparación</option>
                <option value="Soporte">Soporte</option>
            </select>
        </div>
        
        <div class="col-md-3">
            <label class="form-label">Prioridad</label>
            <select class="form-select" id="filtroPrioridad">
                <option value="">Todas</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end ms-auto">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm" id="btnFiltrar">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiar">
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

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
                    <input type="hidden" name="id" id="idPedidoAsignar">
                    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Asignar a: <span class="text-danger">*</span></label>
                        <select class="form-select" name="asignado_a" id="selectUsuarioAsignar" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                        <div class="form-text">Solo se listan administradores, operadores y consultores activos.</div>
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
            },
            xhrFields: { withCredentials: true },
            error: function(xhr, error, code) {
                console.error('DataTables error:', xhr, error, code);
                console.log('Response:', xhr.responseText);
                showAlert('Error al cargar la tabla: ' + code + ' - Ver consola para detalles', 'error');
            }
        },
        order: [[5, 'desc']], // Fecha creacion desc (Index 5 is Fecha now)
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false }
        ],
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "<div class=\"text-center py-3\"><i class=\"fas fa-search fa-2x text-muted mb-2\"></i><p class=\"text-muted mb-0\">No se encontraron resultados</p></div>",
            "emptyTable": "<div class=\"text-center py-3\"><i class=\"fas fa-clipboard-list fa-2x text-muted mb-2\"></i><p class=\"text-muted mb-0\">No hay pedidos disponibles</p></div>",
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "infoThousands": ",",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        drawCallback: function() {
            // Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });

    // Eventos de Filtros
    $('#filtroEstado, #filtroTipo, #filtroPrioridad').on('change', function() {
        dtPedidos.ajax.reload();
    });

    $('#btnLimpiar').on('click', function() {
        $('#filtroEstado, #filtroTipo, #filtroPrioridad').val('');
        dtPedidos.ajax.reload();
    });

    // Eventos de Tabs
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
        var modo = $(event.target).data('modo');
        $('#filtroModo').val(modo);
        dtPedidos.ajax.reload();
    });

    // Manejar envío del formulario de filtros
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        dtPedidos.ajax.reload();
    });

});

// Funciones Globales para botones de acción
function verPedido(id) {
    const url = '<?php echo app_base_url(); ?>/pages/pedidos/ver.php?id=' + id;
    console.log('Navigating to:', url);
    window.location.href = url;
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
});


</script>
