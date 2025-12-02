<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('areas', 'ver');

$conexion = conectarDB();

// Procesar formulario de agregar/editar área
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['accion'])) {
            if ($_POST['accion'] == 'agregar') {
                verificarPermiso('areas', 'crear');
                $sql = "INSERT INTO areas (nombre_area, descripcion) VALUES (?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([$_POST['nombre_area'], $_POST['descripcion'] ?: null]);
                
                $_SESSION['mensaje'] = "Área agregada correctamente";
                $_SESSION['tipo_mensaje'] = "success";
                
            } elseif ($_POST['accion'] == 'editar') {
                verificarPermiso('areas', 'editar');
                $sql = "UPDATE areas SET nombre_area = ?, descripcion = ? WHERE id_area = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([$_POST['nombre_area'], $_POST['descripcion'] ?: null, $_POST['id_area']]);
                
                $_SESSION['mensaje'] = "Área actualizada correctamente";
                $_SESSION['tipo_mensaje'] = "success";
            }
        }
        
        header("Location: areas.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
}

// Obtener áreas con información de uso
$sql = "SELECT a.*, 
               COUNT(DISTINCT i.id_insumo) as insumos_asignados,
               COUNT(DISTINCT r.id_remito) as asignaciones_activas
        FROM areas a 
        LEFT JOIN insumos i ON a.id_area = i.id_area_asignacion_actual
        LEFT JOIN remitos r ON a.id_area = r.id_area AND r.estado = 'Activa'
        GROUP BY a.id_area 
        ORDER BY a.nombre_area";
$stmt = $conexion->query($sql);
$areas = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-sitemap me-2"></i>Administración de Áreas
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalArea">
                <i class="fas fa-plus me-2"></i>Agregar Área
            </button>
        </div>
    </div>
</div>

<!-- Tabla de áreas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Áreas (<?php echo count($areas); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($areas)): ?>
            <div class="text-center py-4">
                <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay áreas registradas</h5>
                <p class="text-muted">Agregue la primera área para comenzar</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaAreas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Insumos Asignados</th>
                            <th>Asignaciones Activas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($areas as $area): ?>
                            <tr>
                                <td><?php echo $area['id_area']; ?></td>
                                <td><strong><?php echo htmlspecialchars($area['nombre_area']); ?></strong></td>
                                <td>
                                    <?php if ($area['descripcion']): ?>
                                        <?php echo htmlspecialchars($area['descripcion']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin descripción</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $area['insumos_asignados']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $area['asignaciones_activas']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-warning" 
                                                onclick="editarArea(<?php echo htmlspecialchars(json_encode($area)); ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($area['insumos_asignados'] == 0 && $area['asignaciones_activas'] == 0): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="eliminarItem(<?php echo $area['id_area']; ?>, 'área')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-secondary" 
                                                    disabled
                                                    data-bs-toggle="tooltip" 
                                                    title="No se puede eliminar - tiene insumos o asignaciones">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal para agregar/editar área -->
<div class="modal fade" id="modalArea" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAreaTitle">Agregar Área</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formArea">
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accion" value="agregar">
                    <input type="hidden" name="id_area" id="id_area">
                    
                    <div class="mb-3">
                        <label for="nombre_area" class="form-label">Nombre del Área *</label>
                        <input type="text" class="form-control" id="nombre_area" name="nombre_area" required>
                        <div class="invalid-feedback">El nombre del área es obligatorio</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarArea(area) {
    $('#modalAreaTitle').text('Editar Área');
    $('#accion').val('editar');
    $('#id_area').val(area.id_area);
    $('#nombre_area').val(area.nombre_area);
    $('#descripcion').val(area.descripcion);
    $('#modalArea').modal('show');
}

// Resetear modal al cerrar
$('#modalArea').on('hidden.bs.modal', function () {
    $('#modalAreaTitle').text('Agregar Área');
    $('#accion').val('agregar');
    $('#id_area').val('');
    $('#formArea')[0].reset();
    $('#formArea').removeClass('was-validated');
});

// Validación del formulario
$('#formArea').on('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    $(this).addClass('was-validated');
});
</script>

<?php include '../../includes/footer.php'; ?> 