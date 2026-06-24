<?php
require_once '../../includes/config.php';

requerirAutenticacion();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de insumo no válido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

$id = (int)$_GET['id'];
$db = conectarDB();

// Datos generales del insumo con ubicación
$stmt = $db->prepare("
    SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona,
           ing.nro_referencia AS ingreso_referencia, ing.tipo_ingreso
    FROM insumos i
    LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock
    LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area
    LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
    LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
    LEFT JOIN zonas z ON l.id_zona = z.id_zona
    LEFT JOIN ingresos ing ON i.id_ingreso = ing.id_ingreso
    WHERE i.id_insumo = ?
");
$stmt->execute([$id]);
$insumo = $stmt->fetch();

if (!$insumo) {
    $_SESSION['mensaje'] = 'El insumo no existe.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

// Especificaciones según tipo
$esp = [];
switch ($insumo['tipo_insumo']) {
    case 'PC Completa':
    case 'PC Escritorio':
        $q = $db->prepare("SELECT * FROM pcs_completas WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Notebook':
        $q = $db->prepare("SELECT * FROM notebooks WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Impresora':
        $q = $db->prepare("SELECT * FROM impresoras WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Monitor':
        $q = $db->prepare("SELECT * FROM monitores WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Escaner':
        $q = $db->prepare("SELECT * FROM escaneres WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
}

// Historial de asignaciones desde remitos/remitos_detalle
$sqlHist = "
    SELECT r.numero_remito, r.fecha_asignacion, r.fecha_devolucion, r.estado, s.nombre_sede, a.nombre_area,
           d.cantidad, d.cantidad_devuelta
    FROM remitos_detalle d
    JOIN remitos r ON r.id_remito = d.id_remito
    LEFT JOIN sedes s ON r.id_sede = s.id_sede
    LEFT JOIN areas a ON r.id_area = a.id_area
    WHERE d.id_insumo = ?
    ORDER BY r.fecha_asignacion DESC
";
$st = $db->prepare($sqlHist);
$st->execute([$id]);
$historial = $st->fetchAll();

// Historial de intervenciones (pedidos de mantenimiento, reparación, soporte)
$sqlIntervenciones = "
    SELECT p.id_pedido, p.tipo, p.descripcion, p.estado, p.prioridad,
           p.solicitante_nombre, p.fecha_creacion, p.fecha_actualizacion,
           u.nombre as tecnico_nombre, u.apellido as tecnico_apellido,
           s.nombre_sede
    FROM pedidos p
    LEFT JOIN usuarios u ON p.asignado_a = u.id_usuario
    LEFT JOIN sedes s ON p.id_sede = s.id_sede
    WHERE p.id_insumo_relacionado = ?
    ORDER BY p.fecha_creacion DESC
";
$stInt = $db->prepare($sqlIntervenciones);
$stInt->execute([$id]);
$intervenciones = $stInt->fetchAll();

include '../../includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-eye me-2"></i>Detalles del Insumo</h1>
            <div>
                <a href="listar.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
                <?php if (tienePermiso('insumos', 'editar')): ?>
                <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Editar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?php echo $insumo['id_insumo']; ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($insumo['nombre_insumo']); ?></p>
                        <p><strong>Tipo:</strong> <span class="badge bg-info"><?php 
                            if ($insumo['tipo_insumo'] === 'Varios' && !empty($insumo['subcategoria_varios'])) {
                                echo htmlspecialchars($insumo['subcategoria_varios']);
                            } else {
                                echo htmlspecialchars($insumo['tipo_insumo']);
                            }
                        ?></span></p>
                        <p><strong>Estado:</strong>
                            <span class="badge estado-<?php echo strtolower(str_replace(' ','-',$insumo['estado'])); ?>">
                                <?php echo $insumo['estado']; ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Cantidad:</strong>
                            <span class="badge <?php echo $insumo['cantidad'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $insumo['cantidad']; ?>
                            </span>
                        </p>
                        <p><strong>Fecha de Adquisición:</strong> <?php echo $insumo['fecha_adquisicion'] ? date('d/m/Y', strtotime($insumo['fecha_adquisicion'])) : '-'; ?></p>
                        <?php if ($insumo['estado'] !== 'Asignado'): ?>
                            <p><strong>Punto de Stock:</strong> <?php echo $insumo['nombre_punto'] ?: 'Sin asignar'; ?></p>
                        <?php endif; ?>
                        <p><strong>Condición:</strong> 
                            <span class="badge <?php echo ($insumo['es_nuevo'] ?? 1) ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ($insumo['es_nuevo'] ?? 1) ? 'Nuevo' : 'Usado'; ?>
                            </span>
                        </p>
                        <?php if (!empty($insumo['ingreso_referencia'])): ?>
                            <p><strong>
                                <?php 
                                $tipoIngreso = $insumo['tipo_ingreso'] ?? 'otros';
                                $labelIngreso = match($tipoIngreso) {
                                    'fondos' => 'Nro. Nota (Fondos)',
                                    'licitacion' => 'Nro. Expediente (Licitación)',
                                    'compra_directa' => 'Nro. Expediente (Compra Directa)',
                                    'otros' => 'Nro. Referencia',
                                    default => 'Nro. Referencia'
                                };
                                echo $labelIngreso;
                                ?>:</strong> 
                                <span class="badge bg-primary"><?php echo htmlspecialchars($insumo['ingreso_referencia']); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($insumo['tipo_insumo'] !== 'Varios'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Número de Serie:</strong> 
                                <?php echo !empty($insumo['numero_serie']) ? htmlspecialchars($insumo['numero_serie']) : '<span class="text-muted">No tiene</span>'; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>ID Físico:</strong> 
                                <?php echo !empty($insumo['id_fisico']) ? htmlspecialchars($insumo['id_fisico']) : '<span class="text-muted">No tiene</span>'; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>ID Patrimonio:</strong> 
                                <?php echo !empty($insumo['id_patrimonio']) ? htmlspecialchars($insumo['id_patrimonio']) : '<span class="text-muted">No tiene</span>'; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($insumo['descripcion_general']): ?>
                    <hr>
                    <p><strong>Descripción:</strong></p>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($insumo['descripcion_general'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($insumo['tipo_insumo'] !== 'Varios'): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Especificaciones Técnicas</h5></div>
                <div class="card-body">
                    <?php if ($insumo['tipo_insumo'] === 'PC Escritorio' || $insumo['tipo_insumo'] === 'PC Completa'): ?>
                        <p><strong>Procesador:</strong> <?php echo htmlspecialchars($esp['procesador'] ?? ''); ?></p>
                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?> GB</p>
                        <p><strong>Almacenamiento:</strong> <?php echo htmlspecialchars($esp['almacenamiento_gb'] ?? ''); ?> GB <?php if (!empty($esp['ssd_o_superior'])): ?><span class="badge bg-success ms-1"><i class="fas fa-microchip me-1"></i>SSD o superior</span><?php endif; ?></p>
                        <p><strong>Mother:</strong> <?php echo htmlspecialchars($esp['mother'] ?? ''); ?></p>
                        <p><strong>Sistema Operativo:</strong> <?php echo htmlspecialchars($esp['sist_op'] ?? ''); ?></p>
                    <?php elseif ($insumo['tipo_insumo'] === 'Notebook'): ?>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($esp['marca'] ?? ''); ?></p>
                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($esp['modelo'] ?? ''); ?></p>
                        <p><strong>Procesador:</strong> <?php echo htmlspecialchars($esp['procesador'] ?? ''); ?></p>
                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?> GB</p>
                        <p><strong>Almacenamiento:</strong> <?php echo htmlspecialchars($esp['almacenamiento_gb'] ?? ''); ?> GB</p>
                    <?php elseif ($insumo['tipo_insumo'] === 'Impresora'): ?>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($esp['marca'] ?? ''); ?></p>
                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($esp['modelo'] ?? ''); ?></p>
                    <?php elseif ($insumo['tipo_insumo'] === 'Monitor'): ?>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($esp['marca'] ?? ''); ?></p>
                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($esp['modelo'] ?? ''); ?></p>
                        <p><strong>Pulgadas:</strong> <?php echo htmlspecialchars($esp['pulgadas'] ?? ''); ?></p>
                        <p><strong>Conexión:</strong> <?php 
                            $cx = $esp['conexion'] ?? ''; 
                            echo htmlspecialchars($cx . ($cx === 'Ambas' ? ' (HDMI-VGA)' : '')); 
                        ?></p>
                    <?php elseif ($insumo['tipo_insumo'] === 'Escaner'): ?>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($esp['marca'] ?? ''); ?></p>
                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($esp['modelo'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Historial de Intervenciones -->
        <?php if (!empty($intervenciones)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Historial de Intervenciones (<?php echo count($intervenciones); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Descripción</th>
                                <th>Técnico</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($intervenciones as $int): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($int['fecha_creacion'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $badgeClass = match($int['tipo']) {
                                        'Mantenimiento' => 'bg-warning text-dark',
                                        'Reparación' => 'bg-danger',
                                        'Soporte' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $int['tipo']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $estadoClass = match($int['estado']) {
                                        'Pendiente' => 'bg-secondary',
                                        'En Proceso' => 'bg-primary',
                                        'Completado' => 'bg-success',
                                        'Rechazado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $estadoClass; ?>"><?php echo $int['estado']; ?></span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($int['descripcion'], 0, 50)); ?><?php echo strlen($int['descripcion']) > 50 ? '...' : ''; ?></small>
                                </td>
                                <td>
                                    <?php if ($int['tecnico_nombre']): ?>
                                        <small><?php echo htmlspecialchars($int['tecnico_nombre'] . ' ' . $int['tecnico_apellido']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Sin asignar</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <a href="../pedidos/listar.php?insumo=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt me-1"></i>Ver todos los pedidos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación Actual</h5></div>
            <div class="card-body">
                <?php if ($insumo['nombre_sede']): ?>
                    <p><strong>Sede:</strong> <?php echo htmlspecialchars($insumo['nombre_sede']); ?></p>
                    <p><strong>Localidad:</strong> <?php echo htmlspecialchars($insumo['nombre_localidad']); ?></p>
                    <p><strong>Zona:</strong> <?php echo htmlspecialchars($insumo['nombre_zona']); ?></p>
                    <?php if ($insumo['nombre_area']): ?><p><strong>Área:</strong> <?php echo htmlspecialchars($insumo['nombre_area']); ?></p><?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Sin ubicación asignada</p>
                <?php endif; ?>
                <?php
                // Buscar asignaciones activas para este insumo (incluye ubicación y cantidad)
                $stmtAct = $db->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado,
                                                s.nombre_sede, l.nombre_localidad, z.nombre_zona, ar.nombre_area,
                                                d.cantidad, d.cantidad_devuelta
                                         FROM remitos_detalle d
                                         JOIN remitos r ON r.id_remito = d.id_remito
                                         JOIN sedes s ON r.id_sede = s.id_sede
                                         JOIN localidades l ON s.id_localidad = l.id_localidad
                                         JOIN zonas z ON l.id_zona = z.id_zona
                                         LEFT JOIN areas ar ON r.id_area = ar.id_area
                                         WHERE d.id_insumo = ? AND r.estado = 'Activa' AND d.cantidad_devuelta < d.cantidad
                                         ORDER BY r.fecha_asignacion DESC");
                $stmtAct->execute([$id]);
                $remActivas = $stmtAct->fetchAll();
                if (!empty($remActivas)): ?>
                    <hr>
                    <p class="mb-2"><strong>Asignaciones activas:</strong></p>
                    <?php foreach ($remActivas as $remActItem): ?>
                        <div class="mb-2 p-2 bg-light rounded border text-dark">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge bg-warning">Activa</span>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($remActItem['fecha_asignacion'])); ?></small>
                            </div>
                            <p class="mb-1 small"><strong>Remito:</strong> <?php echo htmlspecialchars($remActItem['numero_remito']); ?></p>
                            <p class="mb-1 small"><strong>Sede:</strong> <?php echo htmlspecialchars($remActItem['nombre_sede']); ?></p>
                            <?php if ($remActItem['nombre_area']): ?>
                                <p class="mb-1 small"><strong>Área:</strong> <?php echo htmlspecialchars($remActItem['nombre_area']); ?></p>
                            <?php endif; ?>
                            <?php if ($insumo['tipo_insumo'] === 'Varios'): ?>
                                <p class="mb-1 small"><strong>Cantidad asignada:</strong> <span class="badge bg-primary"><?php echo (int)($remActItem['cantidad'] - $remActItem['cantidad_devuelta']); ?></span></p>
                            <?php endif; ?>
                            <a class="btn btn-xs btn-outline-primary mt-1 py-0 px-2" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($remActItem['numero_remito']); ?>">
                                <i class="fas fa-file-alt me-1"></i>Ver remito
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        $remAct = !empty($remActivas) ? $remActivas[0] : null;
        if (!empty($remAct) && $insumo['tipo_insumo'] !== 'Varios'): ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación Actual (Seguimiento)</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($remAct['nombre_sede'] ?? ''); ?></p>
                <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($remAct['nombre_localidad'] ?? ''); ?></p>
                <p class="mb-1"><strong>Zona:</strong> <?php echo htmlspecialchars($remAct['nombre_zona'] ?? ''); ?></p>
                <?php if (!empty($remAct['nombre_area'])): ?><p class="mb-1"><strong>Área:</strong> <?php echo htmlspecialchars($remAct['nombre_area']); ?></p><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Asignaciones</h5></div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                    <p class="text-muted">No hay asignaciones registradas</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($historial as $h): ?>
                            <?php
                                $estado_evento = !empty($h['fecha_devolucion']) ? 'Devuelta' : ($h['estado'] ?: 'Activa');
                                $badge_class = 'estado-' . strtolower($estado_evento);
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($h['fecha_asignacion'])); ?></small>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $estado_evento; ?></span>
                                </div>
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($h['nombre_sede'] ?? ''); ?></strong>
                                    <?php if (!empty($h['nombre_area'])): ?> - <?php echo htmlspecialchars($h['nombre_area']); ?><?php endif; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Remito: <?php echo htmlspecialchars($h['numero_remito']); ?></small>
                                    <?php if ($insumo['tipo_insumo'] === 'Varios'): ?>
                                        <small class="text-muted">Cantidad: <strong><?php echo (int)$h['cantidad']; ?></strong></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>