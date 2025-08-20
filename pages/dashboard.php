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

// Insumos por tipo (cantidad disponible) y subtipos de 'Varios'
$insumos_por_tipo = [];
// Tipos unitarios (disponibles)
$stmtTipos = $conexion->query("SELECT tipo_insumo AS label, COUNT(*) AS total
                               FROM insumos
                               WHERE tipo_insumo <> 'Varios' AND estado = 'Disponible'
                               GROUP BY tipo_insumo");
$insumos_por_tipo = $stmtTipos->fetchAll();
// Subtipos de Varios (sumatoria de cantidades > 0)
$stmtVarios = $conexion->query("SELECT 
                                  CASE subcategoria_varios
                                    WHEN 'Hardware' THEN 'Varios - Hardware'
                                    WHEN 'Periféricos' THEN 'Varios - Periféricos'
                                    WHEN 'Red' THEN 'Varios - Red'
                                    ELSE 'Varios - (Sin subcategoría)'
                                  END AS label,
                                  COALESCE(SUM(cantidad),0) AS total
                                FROM insumos
                                WHERE tipo_insumo = 'Varios' AND cantidad > 0
                                GROUP BY subcategoria_varios");
$varios = $stmtVarios->fetchAll();
$insumos_por_tipo = array_merge($insumos_por_tipo, $varios);

// Insumos por sede
$insumos_por_sede = $conexion->query("
    SELECT s.nombre_sede, COUNT(*) as total 
    FROM insumos i 
    JOIN sedes s ON i.id_sede_actual = s.id_sede 
    WHERE i.id_sede_actual IS NOT NULL 
    GROUP BY s.id_sede 
    ORDER BY total DESC 
    LIMIT 10
")->fetchAll();
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
        <div class="dashboard-card">
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
        <div class="dashboard-card" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
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
        <div class="dashboard-card" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
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
        <div class="dashboard-card" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);">
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
    
    <!-- Gráfico de insumos por tipo -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Cantidad disponible (por tipo)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="chartInsumosPorTipo" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Gráfico de insumos por sede -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Insumos por Sede
                </h5>
            </div>
            <div class="card-body">
                <canvas id="chartInsumosPorSede" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

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
                        <a href="asignaciones/nueva.php" class="btn btn-success w-100">
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
// Datos para los gráficos
const datosInsumosPorTipo = <?php echo json_encode($insumos_por_tipo); ?>;
const datosInsumosPorSede = <?php echo json_encode($insumos_por_sede); ?>;

// Gráfico de insumos por tipo
const ctx1 = document.getElementById('chartInsumosPorTipo').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: datosInsumosPorTipo.map(item => item.label || item.tipo_insumo),
        datasets: [{
            data: datosInsumosPorTipo.map(item => parseInt(item.total, 10)),
            backgroundColor: [
                '#0d6efd',
                '#198754',
                '#ffc107',
                '#dc3545',
                '#0dcaf0',
                '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de insumos por sede
const ctx2 = document.getElementById('chartInsumosPorSede').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: datosInsumosPorSede.map(item => item.nombre_sede),
        datasets: [{
            label: 'Cantidad de Insumos',
            data: datosInsumosPorSede.map(item => item.total),
            backgroundColor: '#0d6efd',
            borderColor: '#0d6efd',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?> 