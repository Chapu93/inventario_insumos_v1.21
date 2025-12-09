<?php
require_once '../../includes/config.php';

// Requerir autenticación y permiso de auditoría
requerirAutenticacion();
verificarPermiso('auditoria', 'ver_todo');
?>
<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Auditoría del Sistema</h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="filtroUsuario" class="form-label"><i class="fas fa-user me-1"></i>Usuario</label>
                <select class="form-select" id="filtroUsuario">
                    <option value="">Todos los usuarios</option>
                    <?php
                    try {
                        $db = conectarDB();
                        $stmt = $db->query("SELECT id_usuario, username, nombre, apellido FROM usuarios ORDER BY username");
                        while ($user = $stmt->fetch()) {
                            echo '<option value="' . $user['id_usuario'] . '">' . 
                                 htmlspecialchars($user['username'] . ' - ' . $user['nombre'] . ' ' . $user['apellido']) . 
                                 '</option>';
                        }
                    } catch (Exception $e) {
                        Logger::error('Error cargando usuarios para filtro', ['mensaje' => $e->getMessage()]);
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="filtroModulo" class="form-label"><i class="fas fa-th-large me-1"></i>Módulo</label>
                <select class="form-select" id="filtroModulo">
                    <option value="">Todos los módulos</option>
                    <option value="usuarios">Usuarios</option>
                    <option value="insumos">Insumos</option>
                    <option value="asignaciones">Asignaciones</option>
                    <option value="reportes">Reportes</option>
                    <option value="telecom">Telecomunicaciones</option>
                    <option value="sedes">Sedes</option>
                    <option value="areas">Áreas</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="filtroResultado" class="form-label"><i class="fas fa-check-circle me-1"></i>Resultado</label>
                <select class="form-select" id="filtroResultado">
                    <option value="">Todos</option>
                    <option value="exito">Éxito</option>
                    <option value="error">Error</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="filtroFechaDesde" class="form-label"><i class="fas fa-calendar me-1"></i>Desde</label>
                <input type="date" class="form-control" id="filtroFechaDesde">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" id="btnFiltrar">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle" id="tablaAuditoria">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Resultado</th>
                        <th>IP</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalles de Auditoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallesContent">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let table = $('#tablaAuditoria').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: getAppBase() + '/ajax/auditoria_list_ssp.php',
            type: 'GET',
            data: function(d) {
                d.filtro_usuario = $('#filtroUsuario').val();
                d.filtro_modulo = $('#filtroModulo').val();
                d.filtro_resultado = $('#filtroResultado').val();
                d.filtro_fecha_desde = $('#filtroFechaDesde').val();
            }
        },
        order: [[0, 'desc']], // Ordenar por fecha DESC
        pageLength: 25,
        columns: [
            { data: 0 },  // Fecha
            { data: 1 },  // Usuario
            { data: 2 },  // Módulo
            { data: 3 },  // Acción
            { data: 4 },  // Descripción
            { data: 5 },  // Resultado
            { data: 6 },  // IP
            { data: 7, orderable: false, searchable: false }  // Botón detalles
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        drawCallback: function() {
            inicializarTooltips();
        }
    });
    
    // Aplicar filtros
    $('#btnFiltrar').on('click', function() {
        table.ajax.reload();
    });
    
    // Enter en filtros
    $('#filtroUsuario, #filtroModulo, #filtroResultado, #filtroFechaDesde').on('keypress', function(e) {
        if (e.which === 13) {
            table.ajax.reload();
        }
    });
    
    // Ver detalles
    $(document).on('click', '.btn-ver-detalles', function() {
        const id = $(this).data('id');
        $('#detallesContent').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
        $('#modalDetalles').modal('show');
        
        $.ajax({
            url: getAppBase() + '/ajax/auditoria_detalles.php',
            method: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const a = response.data;
                    let html = '<div class="row">';
                    
                    // Información básica
                    html += '<div class="col-12 mb-3">';
                    html += '<h6><i class="fas fa-info-circle me-2"></i>Información General</h6>';
                    html += '<table class="table table-sm table-bordered">';
                    html += '<tr><th style="width:30%">Fecha/Hora</th><td>' + a.fecha_accion + '</td></tr>';
                    html += '<tr><th>Usuario</th><td>' + a.usuario + '</td></tr>';
                    html += '<tr><th>Módulo</th><td><span class="badge bg-info">' + a.modulo + '</span></td></tr>';
                    html += '<tr><th>Acción</th><td><span class="badge bg-primary">' + a.accion + '</span></td></tr>';
                    html += '<tr><th>Descripción</th><td>' + a.descripcion + '</td></tr>';
                    html += '<tr><th>Resultado</th><td>';
                    if (a.resultado === 'exito') {
                        html += '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Éxito</span>';
                    } else {
                        html += '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span>';
                    }
                    html += '</td></tr>';
                    html += '<tr><th>IP</th><td>' + (a.ip_address || 'N/A') + '</td></tr>';
                    if (a.mensaje_error) {
                        html += '<tr><th>Mensaje Error</th><td class="text-danger">' + a.mensaje_error + '</td></tr>';
                    }
                    html += '</table>';
                    html += '</div>';
                    
                    // Datos antes
                    if (a.datos_antes) {
                        html += '<div class="col-md-6 mb-3">';
                        html += '<h6><i class="fas fa-history me-2"></i>Estado Anterior</h6>';
                        html += '<pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">';
                        html += JSON.stringify(a.datos_antes, null, 2);
                        html += '</pre>';
                        html += '</div>';
                    }
                    
                    // Datos después
                    if (a.datos_despues) {
                        html += '<div class="col-md-6 mb-3">';
                        html += '<h6><i class="fas fa-save me-2"></i>Estado Posterior</h6>';
                        html += '<pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">';
                        html += JSON.stringify(a.datos_despues, null, 2);
                        html += '</pre>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    $('#detallesContent').html(html);
                } else {
                    $('#detallesContent').html('<div class="alert alert-danger">Error al cargar detalles</div>');
                }
            },
            error: function() {
                $('#detallesContent').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });
});
</script>
