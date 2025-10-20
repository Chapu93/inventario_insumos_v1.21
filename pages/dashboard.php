<?php
require_once '../includes/config.php';

// Obtener estadísticas del dashboard
$conexion = conectarDB();

// Total de insumos
$total_insumos = $conexion->query("SELECT COUNT(*) as total FROM insumos")->fetch()['total'];

// Insumos disponibles
$insumos_disponibles = $conexion->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Disponible'")->fetch()['total'];

// Insumos asignados
$insumos_asignados = $conexion->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Asignado'")->fetch()['total'];

// Total de asignaciones (remitos)
$total_asignaciones = $conexion->query("SELECT COUNT(*) as total FROM remitos")->fetch()['total'];

// Asignaciones activas (suma de cantidades de detalle con insumos 'Asignado')
$asignaciones_activas = $conexion->query("SELECT COALESCE(SUM(d.cantidad),0) as total
                                          FROM remitos r
                                          JOIN remitos_detalle d ON d.id_remito = r.id_remito
                                          JOIN insumos i ON i.id_insumo = d.id_insumo
                                          WHERE i.estado = 'Asignado'")->fetch()['total'];

// Asignaciones recientes (remitos)
$stmt = $conexion->query("SELECT r.fecha_asignacion,
                                 r.nombre_persona_asignada,
                                 r.apellido_persona_asignada,
                                 l.nombre_localidad
                          FROM remitos r
                          JOIN sedes s ON r.id_sede = s.id_sede
                          JOIN localidades l ON s.id_localidad = l.id_localidad
                          ORDER BY r.fecha_asignacion DESC
                          LIMIT 5");
$asignaciones_recientes = $stmt->fetchAll();

// Cantidades disponibles por subtipos de 'Varios'
$varios_subtipos = [
    'Hardware' => 0,
    'Periféricos' => 0,
    'Red' => 0,
];
$stmtVarios = $conexion->query("SELECT subcategoria_varios, COALESCE(SUM(cantidad),0) AS total
                                FROM insumos
                                WHERE TRIM(tipo_insumo) = 'Varios' AND cantidad > 0
                                GROUP BY subcategoria_varios");
$rowsV = $stmtVarios->fetchAll();
foreach ($rowsV as $row) {
    $sub = $row['subcategoria_varios'];
    if ($sub === null || $sub === '') { continue; }
    $varios_subtipos[$sub] = (int)$row['total'];
}

// (Se eliminó 'Insumos por Sede' del dashboard)
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </h1>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card dashboard-card--primary">
            <div class="d-flex justify-content-between">
                <div>
                    <h3 id="total-insumos"><?php echo $total_insumos; ?></h3>
                    <p><i class="fas fa-boxes me-2"></i>Total Insumos</p>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-boxes fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card dashboard-card--success">
            <div class="d-flex justify-content-between">
                <div>
                    <h3 id="insumos-disponibles"><?php echo $insumos_disponibles; ?></h3>
                    <p><i class="fas fa-check-circle me-2"></i>Disponibles</p>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card dashboard-card--info">
            <div class="d-flex justify-content-between">
                <div>
                    <h3 id="insumos-asignados"><?php echo $insumos_asignados; ?></h3>
                    <p><i class="fas fa-clipboard-list me-2"></i>Asignados</p>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card dashboard-card--warning">
            <div class="d-flex justify-content-between">
                <div>
                    <h3 id="total-asignaciones"><?php echo $total_asignaciones; ?></h3>
                    <p><i class="fas fa-file-alt me-2"></i>Total Asignaciones</p>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-file-alt fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Asignaciones recientes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Asignaciones Recientes
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($asignaciones_recientes)): ?>
                    <p class="text-muted">No hay asignaciones recientes</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($asignaciones_recientes as $asignacion): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($asignacion['nombre_persona_asignada'] . ' ' . $asignacion['apellido_persona_asignada']); ?></strong>
                                <div class="text-muted small"><?php echo htmlspecialchars($asignacion['nombre_localidad']); ?></div>
                            </div>
                            <div class="text-nowrap"><?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tabla: Varios por subtipos (cantidad disponible) -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-layer-group me-2"></i>Cantidades disponibles
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-flat" id="tablaVariosDashboard">
                        <tbody>
                            <tr>
                                <td><strong>Periféricos</strong></td>
                                <td class="text-end"><span class="badge bg-primary"><?php echo (int)$varios_subtipos['Periféricos']; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Hardware</strong></td>
                                <td class="text-end"><span class="badge bg-primary"><?php echo (int)$varios_subtipos['Hardware']; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Red</strong></td>
                                <td class="text-end"><span class="badge bg-primary"><?php echo (int)$varios_subtipos['Red']; ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Se eliminó la sección 'Insumos por Sede' -->

<!-- Acciones rápidas -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="insumos/agregar.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Agregar Insumo
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="asignaciones/nueva_pasos.php" class="btn btn-success w-100">
                            <i class="fas fa-clipboard-check me-2"></i>Nueva Asignación
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="insumos/listar.php" class="btn btn-info w-100">
                            <i class="fas fa-list me-2"></i>Ver Insumos
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="asignaciones/listar.php" class="btn btn-warning w-100">
                            <i class="fas fa-clipboard-list me-2"></i>Ver Asignaciones
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// (Sin gráfico de 'Insumos por Sede')
</script>

<?php include '../includes/footer.php'; ?> 