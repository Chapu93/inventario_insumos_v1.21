<?php
/**
 * Listado de Insumos Intervenidos
 * Muestra todos los insumos que han tenido pedidos de mantenimiento, reparación o soporte
 */
require_once '../../includes/config.php';
requerirAutenticacion();

$db = conectarDB();

// Obtener filtros
$filtroTipo = $_GET['tipo'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$busqueda = trim($_GET['q'] ?? '');

// Consulta para obtener insumos que tienen pedidos relacionados
$sql = "SELECT DISTINCT
            i.id_insumo,
            i.tipo_insumo,
            i.nombre_insumo,
            i.numero_serie,
            i.id_fisico,
            i.estado,
            COUNT(p.id_pedido) as total_intervenciones,
            MAX(p.fecha_creacion) as ultima_intervencion,
            GROUP_CONCAT(DISTINCT p.tipo ORDER BY p.fecha_creacion DESC SEPARATOR ', ') as tipos_intervencion
        FROM insumos i
        INNER JOIN pedidos p ON p.id_insumo_relacionado = i.id_insumo
        WHERE 1=1";

$params = [];

if ($filtroTipo) {
    $sql .= " AND p.tipo = ?";
    $params[] = $filtroTipo;
}

if ($filtroEstado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtroEstado;
}

if ($busqueda) {
    $sql .= " AND (i.nombre_insumo LIKE ? OR i.tipo_insumo LIKE ? OR i.numero_serie LIKE ? OR i.id_fisico LIKE ?)";
    $like = '%' . $busqueda . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY i.id_insumo ORDER BY ultima_intervencion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales
$statsSQL = "SELECT 
    COUNT(DISTINCT id_insumo_relacionado) as total_insumos,
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN tipo = 'Mantenimiento' THEN 1 ELSE 0 END) as mantenimientos,
    SUM(CASE WHEN tipo = 'Reparación' THEN 1 ELSE 0 END) as reparaciones,
    SUM(CASE WHEN tipo = 'Soporte' THEN 1 ELSE 0 END) as soportes
FROM pedidos WHERE id_insumo_relacionado IS NOT NULL";
$stats = $db->query($statsSQL)->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tools me-2"></i>Insumos Intervenidos</h1>
        <a href="../pedidos/listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-list me-1"></i>Ver Todos los Pedidos
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background-color: #3d7b5f;">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['total_insumos'] ?? 0; ?></h3>
                    <small>Insumos Intervenidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background-color: #d4a03c;">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['mantenimientos'] ?? 0; ?></h3>
                    <small>Mantenimientos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background-color: #c9544a;">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['reparaciones'] ?? 0; ?></h3>
                    <small>Reparaciones</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background-color: #4a9b8c;">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['soportes'] ?? 0; ?></h3>
                    <small>Soportes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar Insumo</label>
                    <input type="text" class="form-control" name="q" placeholder="Nombre, tipo, serie..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Intervención</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="Mantenimiento" <?php echo $filtroTipo === 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                        <option value="Reparación" <?php echo $filtroTipo === 'Reparación' ? 'selected' : ''; ?>>Reparación</option>
                        <option value="Soporte" <?php echo $filtroTipo === 'Soporte' ? 'selected' : ''; ?>>Soporte</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado del Pedido</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="Pendiente" <?php echo $filtroEstado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="En Proceso" <?php echo $filtroEstado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="Completado" <?php echo $filtroEstado === 'Completado' ? 'selected' : ''; ?>>Completado</option>
                        <option value="Rechazado" <?php echo $filtroEstado === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success me-2"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="intervenidos.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de insumos intervenidos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Insumos (<?php echo count($insumos); ?> resultados)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($insumos)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron insumos intervenidos</h5>
                    <p class="text-muted">No hay insumos que coincidan con los filtros seleccionados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaIntervenidos">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>N° Serie</th>
                                <th>Estado Insumo</th>
                                <th>Intervenciones</th>
                                <th>Última</th>
                                <th>Tipos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos as $ins): ?>
                            <tr>
                                <td><?php echo $ins['id_insumo']; ?></td>
                                <td><strong><?php echo htmlspecialchars($ins['tipo_insumo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ins['nombre_insumo'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($ins['numero_serie'] ?: $ins['id_fisico'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($ins['estado']) {
                                            'Disponible' => 'success',
                                            'Asignado' => 'info',
                                            'En Reparación' => 'warning',
                                            'De Baja' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>"><?php echo $ins['estado']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill"><?php echo $ins['total_intervenciones']; ?></span>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($ins['ultima_intervencion'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $tipos = explode(', ', $ins['tipos_intervencion']);
                                    foreach (array_unique($tipos) as $tipo): 
                                        $badgeClass = match($tipo) {
                                            'Mantenimiento' => 'bg-warning text-dark',
                                            'Reparación' => 'bg-danger',
                                            'Soporte' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $tipo; ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <a href="ver.php?id=<?php echo $ins['id_insumo']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="Ver detalles e historial">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary btn-ver-historial" 
                                            data-id="<?php echo $ins['id_insumo']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($ins['tipo_insumo'] . ' - ' . ($ins['nombre_insumo'] ?: 'Sin nombre')); ?>"
                                            title="Ver historial rápido">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para historial rápido -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Historial de Intervenciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="nombreInsumoModal" class="mb-3"></h6>
                <div id="contenidoHistorial">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Cargando historial...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnVerCompleto" class="btn btn-primary">Ver Insumo Completo</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // DataTable
    if ($.fn.DataTable) {
        $('#tablaIntervenidos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[6, 'desc']], // Ordenar por última intervención
            pageLength: 25
        });
    }
    
    // Ver historial rápido
    $('.btn-ver-historial').click(function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        $('#nombreInsumoModal').text(nombre);
        $('#btnVerCompleto').attr('href', 'ver.php?id=' + id);
        $('#contenidoHistorial').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando historial...</p></div>');
        
        var modal = new bootstrap.Modal(document.getElementById('modalHistorial'));
        modal.show();
        
        // Cargar historial via AJAX
        $.getJSON('<?php echo app_base_url(); ?>/ajax/historial_intervenciones.php', { id_insumo: id }, function(resp) {
            if (resp.success && resp.data.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>Fecha</th><th>Tipo</th><th>Estado</th><th>Descripción</th><th>Solicitante</th></tr></thead><tbody>';
                
                resp.data.forEach(function(p) {
                    var badgeClass = {
                        'Mantenimiento': 'bg-warning text-dark',
                        'Reparación': 'bg-danger',
                        'Soporte': 'bg-info'
                    }[p.tipo] || 'bg-secondary';
                    
                    var estadoClass = {
                        'Pendiente': 'bg-secondary',
                        'En Proceso': 'bg-primary',
                        'Completado': 'bg-success',
                        'Rechazado': 'bg-danger'
                    }[p.estado] || 'bg-secondary';
                    
                    html += '<tr>';
                    html += '<td>' + p.fecha + '</td>';
                    html += '<td><span class="badge ' + badgeClass + '">' + p.tipo + '</span></td>';
                    html += '<td><span class="badge ' + estadoClass + '">' + p.estado + '</span></td>';
                    html += '<td>' + (p.descripcion.length > 50 ? p.descripcion.substring(0, 50) + '...' : p.descripcion) + '</td>';
                    html += '<td>' + p.solicitante + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                $('#contenidoHistorial').html(html);
            } else {
                $('#contenidoHistorial').html('<div class="alert alert-info">No hay intervenciones registradas.</div>');
            }
        }).fail(function() {
            $('#contenidoHistorial').html('<div class="alert alert-danger">Error al cargar el historial.</div>');
        });
    });
});
</script>
