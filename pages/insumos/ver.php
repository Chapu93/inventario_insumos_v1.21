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
");_insumo = ?
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
    SELECT r.numero_remito, r.fecha_asignacion, r.fecha_devolucion, r.estado, s.nombre_sede, a.nombre_area
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

include '../../includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-eye me-2"></i>Detalles del Insumo</h1>
            <div>
                <a href="listar.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
                <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Editar</a>
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
                                <?p                        <p><strong>Fecha de Adquisición:</strong> <?php echo $insumo['fecha_adquisicion'] ? date('d/m/Y', strtotime($insumo['fecha_adquisicion'])) : '-'; ?></p>
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
                        <?php endif; ?>strong> <?php echo $insumo['nombre_punto'] ?: 'Sin asignar'; ?></p>
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
                        <p><strong>Almacenamiento:</strong> <?php echo htmlspecialchars($esp['almacenamiento_gb'] ?? ''); ?> GB</p>
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
                        <p><strong>Conexión:</strong> <?php echo htmlspecialchars($esp['conexion'] ?? ''); ?></p>
                    <?php elseif ($insumo['tipo_insumo'] === 'Escaner'): ?>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($esp['marca'] ?? ''); ?></p>
                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($esp['modelo'] ?? ''); ?></p>
                    <?php endif; ?>
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
                // Buscar asignación/remito activo para este insumo (incluye ubicación)
                $stmtAct = $db->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado,
                                                s.nombre_sede, l.nombre_localidad, z.nombre_zona, ar.nombre_area
                                         FROM remitos_detalle d
                                         JOIN remitos r ON r.id_remito = d.id_remito
                                         JOIN sedes s ON r.id_sede = s.id_sede
                                         JOIN localidades l ON s.id_localidad = l.id_localidad
                                         JOIN zonas z ON l.id_zona = z.id_zona
                                         JOIN areas ar ON r.id_area = ar.id_area
                                         WHERE d.id_insumo = ? AND r.estado = 'Activa'
                                         ORDER BY r.fecha_asignacion DESC LIMIT 1");
                $stmtAct->execute([$id]);
                $remAct = $stmtAct->fetch();
                if ($remAct): ?>
                    <hr>
                    <p class="mb-1"><strong>Asignación activa:</strong></p>
                    <p class="mb-1">
                        <span class="badge bg-warning">Activa</span>
                        <strong>ID:</strong> <?php echo (int)$remAct['id_remito']; ?>
                    </p>
                    <p class="mb-1"><strong>Remito:</strong> <?php echo htmlspecialchars($remAct['numero_remito']); ?></p>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($remAct['numero_remito']); ?>">
                        Ver remito
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($remAct) && $insumo['tipo_insumo'] !== 'Varios'): ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación Actual</h5></div>
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
                                <small class="text-muted">Remito: <?php echo htmlspecialchars($h['numero_remito']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
