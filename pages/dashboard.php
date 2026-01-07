<?php
require_once '../includes/config.php';

requerirAutenticacion();

// Obtener estadísticas del dashboard
$conexion = conectarDB();

// Query optimizada: obtener todos los contadores en una sola consulta
$stats = $conexion->query("
    SELECT 
        (SELECT COUNT(*) FROM insumos) as total_insumos,
        (SELECT COUNT(*) FROM insumos WHERE estado = 'Disponible') as insumos_disponibles,
        (SELECT COUNT(*) FROM insumos WHERE estado = 'Asignado') as insumos_asignados,
        (SELECT COUNT(*) FROM remitos) as total_asignaciones,
        (SELECT COALESCE(SUM(d.cantidad), 0)
         FROM remitos r
         JOIN remitos_detalle d ON d.id_remito = r.id_remito
         JOIN insumos i ON i.id_insumo = d.id_insumo
         WHERE i.estado = 'Asignado') as asignaciones_activas
")->fetch();

// Asignar variables
$total_insumos = (int)$stats['total_insumos'];
$insumos_disponibles = (int)$stats['insumos_disponibles'];
$insumos_asignados = (int)$stats['insumos_asignados'];
$total_asignaciones = (int)$stats['total_asignaciones'];
$asignaciones_activas = (int)$stats['asignaciones_activas'];

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

// Cantidades disponibles por subtipos de 'Varios' con stock dual
$varios_subtipos = [
    'Hardware' => ['oficina' => 0, 'deposito' => 0, 'total' => 0],
    'Periféricos' => ['oficina' => 0, 'deposito' => 0, 'total' => 0],
    'Red' => ['oficina' => 0, 'deposito' => 0, 'total' => 0],
];
$stmtVarios = $conexion->query("SELECT subcategoria_varios, 
                                       COALESCE(SUM(cantidad_oficina), SUM(cantidad)) AS oficina,
                                       COALESCE(SUM(cantidad_deposito), 0) AS deposito,
                                       COALESCE(SUM(cantidad),0) AS total
                                FROM insumos
                                WHERE TRIM(tipo_insumo) = 'Varios' AND cantidad > 0
                                GROUP BY subcategoria_varios");
$rowsV = $stmtVarios->fetchAll();
foreach ($rowsV as $row) {
    $sub = $row['subcategoria_varios'];
    if ($sub === null || $sub === '') { continue; }
    $varios_subtipos[$sub] = [
        'oficina' => (int)$row['oficina'],
        'deposito' => (int)$row['deposito'],
        'total' => (int)$row['total']
    ];
}

// Query optimizada para totales de stock (una sola consulta)
$stockTotales = $conexion->query("
    SELECT 
        COALESCE(SUM(cantidad_oficina), SUM(cantidad)) AS oficina,
        COALESCE(SUM(cantidad_deposito), 0) AS deposito
    FROM insumos 
    WHERE tipo_insumo = 'Varios'
")->fetch();

$totalStockOficina = (int)($stockTotales['oficina'] ?? 0);
$totalStockDeposito = (int)($stockTotales['deposito'] ?? 0);
$totalStockSistema = $totalStockOficina + $totalStockDeposito;

// Contar Pedidos Pendientes
$sqlPedidos = "SELECT COUNT(*) FROM pedidos WHERE estado IN ('Pendiente', 'En Proceso')";
$paramsPedidos = [];
if (!tienePermiso('pedidos', 'ver_todos')) {
    $uId = obtenerUsuarioId();
    $sqlPedidos .= " AND (id_usuario_solicitante = ? OR asignado_a = ?)";
    $paramsPedidos = [$uId, $uId];
}
$stmtP = $conexion->prepare($sqlPedidos);
$stmtP->execute($paramsPedidos);
$pendientes_count = $stmtP->fetchColumn();
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
    <div class="col-md">
        <a href="<?php echo app_base_url(); ?>/pages/pedidos/listar.php?modo=pendientes" style="text-decoration: none; color: inherit;">
            <div class="dashboard-card dashboard-card--primary" style="background: linear-gradient(45deg, #FF512F, #DD2476); border-left-color: #DD2476; cursor: pointer;">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 id="pedidos-pendientes"><?php echo $pendientes_count; ?></h3>
                        <p><i class="fas fa-tasks me-2"></i>Pendientes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md">
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
    
    <div class="col-md">
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
    
    <div class="col-md">
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
    
    <div class="col-md">
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

<div class="row mb-4">
    <!-- Asignaciones recientes -->
    <div class="col-md-6">
        <div class="card h-100">
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
    
    <!-- Tabla: Stock Total Varios (Oficina + Depósito) -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-warehouse me-2"></i>Stock Varios
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <i class="fas fa-building text-success fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo (int)$totalStockOficina; ?></h4>
                            <small class="text-muted">Oficina</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <i class="fas fa-warehouse text-success fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo (int)$totalStockDeposito; ?></h4>
                            <small class="text-muted">Depósito</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <i class="fas fa-boxes text-info fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo (int)$totalStockSistema; ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Categoría</th>
                                <th class="text-center" style="width: 80px;"><i class="fas fa-building"></i></th>
                                <th class="text-center" style="width: 80px;"><i class="fas fa-warehouse"></i></th>
                                <th class="text-center" style="width: 80px;"><i class="fas fa-boxes"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Periféricos</strong></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Periféricos']['oficina']; ?></span></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Periféricos']['deposito']; ?></span></td>
                                <td class="text-center"><span class="badge bg-info"><?php echo $varios_subtipos['Periféricos']['total']; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Hardware</strong></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Hardware']['oficina']; ?></span></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Hardware']['deposito']; ?></span></td>
                                <td class="text-center"><span class="badge bg-info"><?php echo $varios_subtipos['Hardware']['total']; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Red</strong></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Red']['oficina']; ?></span></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $varios_subtipos['Red']['deposito']; ?></span></td>
                                <td class="text-center"><span class="badge bg-info"><?php echo $varios_subtipos['Red']['total']; ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (tienePermiso('insumos', 'crear')): ?>
                    <a href="<?php echo app_base_url(); ?>/pages/insumos/agregar.php" class="btn text-white flex-grow-1 py-2" style="background-color: #5a7c59; border-color: #5a7c59;">
                        <i class="fas fa-plus me-2"></i>Agregar Insumo
                    </a>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('asignaciones', 'crear')): ?>
                    <a href="<?php echo app_base_url(); ?>/pages/asignaciones/nueva_pasos.php" class="btn text-white flex-grow-1 py-2" style="background-color: #66a86a; border-color: #66a86a;">
                        <i class="fas fa-file-alt me-2"></i>Nueva Asignación
                    </a>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('insumos', 'ver')): ?>
                    <a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php" class="btn text-white flex-grow-1 py-2" style="background-color: #4a9d95; border-color: #4a9d95;">
                        <i class="fas fa-list me-2"></i>Ver Insumos
                    </a>
                    <?php endif; ?>

                    <?php if (tienePermiso('asignaciones', 'ver')): ?>
                    <a href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php" class="btn text-white flex-grow-1 py-2" style="background-color: #d9943f; border-color: #d9943f;">
                        <i class="fas fa-clipboard-list me-2"></i>Ver Asignaciones
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>