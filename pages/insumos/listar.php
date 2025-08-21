<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Obtener filtros
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$sql = "SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona 
        FROM insumos i 
        LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
        LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area 
        LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
        LEFT JOIN localidades l ON s.id_localidad = l.id_localidad 
        LEFT JOIN zonas z ON l.id_zona = z.id_zona 
        WHERE 1=1";

$params = [];

if ($filtro_tipo) {
    $sql .= " AND i.tipo_insumo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

if ($filtro_estado) {
    $sql .= " AND i.estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY i.nombre_insumo ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$insumos = $stmt->fetchAll();

// Obtener datos para filtros
$stmt = $conexion->query("SELECT DISTINCT tipo_insumo FROM insumos ORDER BY tipo_insumo");
$tipos_insumo = $stmt->fetchAll();

$stmt = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad");
$localidades = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-boxes me-2"></i>Gestión de Insumos
            </h1>
            <a href="agregar.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Agregar Insumo
            </a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="tipo" class="form-label">Tipo de Insumo</label>
            <select name="tipo" id="tipo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_insumo as $tipo): ?>
                    <option value="<?php echo $tipo['tipo_insumo']; ?>" 
                            <?php echo $filtro_tipo == $tipo['tipo_insumo'] ? 'selected' : ''; ?>>
                        <?php echo $tipo['tipo_insumo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $localidad): ?>
                    <option value="<?php echo $localidad['id_localidad']; ?>" 
                            <?php echo $filtro_localidad == $localidad['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $localidad['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="Disponible" <?php echo $filtro_estado == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="Asignado" <?php echo $filtro_estado == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                <option value="De Baja" <?php echo $filtro_estado == 'De Baja' ? 'selected' : ''; ?>>De Baja</option>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="listar.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de insumos -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Insumos (<?php echo count($insumos); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($insumos)): ?>
            <div class="text-center py-4">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron insumos</h5>
                <p class="text-muted">No hay insumos que coincidan con los filtros aplicados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaInsumos">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos as $insumo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($insumo['nombre_insumo']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $insumo['estado'])); ?>">
                                        <?php echo $insumo['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $insumo['cantidad'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $insumo['cantidad']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                onclick="verInsumo(<?php echo $insumo['id_insumo']; ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
<?php $bloquear = ($insumo['estado'] === 'Asignado'); ?>
<button type="button" 
        class="btn btn-sm btn-danger" 
        <?php echo $bloquear ? 'disabled' : ''; ?>
        onclick="<?php echo $bloquear ? 'return false;' : "eliminarInsumo({$insumo['id_insumo']}, '{$insumo['tipo_insumo']}', '".htmlspecialchars($insumo['nombre_insumo'])."', {$insumo['cantidad']})"; ?>"
        data-bs-toggle="tooltip" 
        title="<?php echo $bloquear ? 'No se puede eliminar un insumo asignado' : 'Eliminar'; ?>">
  <i class="fas fa-trash"></i>
</button>
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

<!-- Botones de exportación -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" onclick="exportarExcel('tablaInsumos', 'insumos')">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaInsumos')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del insumo -->
<div class="modal fade" id="modalVerInsumo" tabindex="-1" aria-labelledby="modalVerInsumoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerInsumoLabel">
                    <i class="fas fa-eye me-2"></i>Detalles del Insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalVerInsumoBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles del insumo...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btnEditarInsumo" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar insumo -->
<div class="modal fade" id="modalEliminarInsumo" tabindex="-1" aria-labelledby="modalEliminarInsumoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarInsumoLabel">
                    <i class="fas fa-trash me-2"></i>Eliminar Insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="eliminarInsumoContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para ver detalles del insumo en modal
function verInsumo(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerInsumo'));
    const modalBody = document.getElementById('modalVerInsumoBody');
    const btnEditar = document.getElementById('btnEditarInsumo');
    
    // Mostrar loading
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles del insumo...</p>
        </div>
    `;
    
    modal.show();
    
    // Cargar datos del insumo
    fetch(`ver_ajax.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
                btnEditar.onclick = () => {
                    modal.hide();
                    window.location.href = `editar.php?id=${id}`;
                };
                btnEditar.style.display = 'inline-block';
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles del insumo: ${data.error}
                    </div>
                `;
                btnEditar.style.display = 'none';
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los detalles del insumo: ${error.message}
                </div>
            `;
            btnEditar.style.display = 'none';
        });
}
</script>

<?php include '../../includes/footer.php'; ?>