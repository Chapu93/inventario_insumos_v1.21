<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'ver');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de insumo no especificado']);
    exit;
}

$id = (int) $_GET['id'];

try {
    $conexion = conectarDB();

    // Obtener información completa del insumo
    $sql = "SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona,
                   ing.nro_referencia AS ingreso_referencia, ing.tipo_ingreso
            FROM insumos i 
            LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
            LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area 
            LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
            LEFT JOIN localidades l ON s.id_localidad = l.id_localidad 
            LEFT JOIN zonas z ON l.id_zona = z.id_zona 
            LEFT JOIN ingresos ing ON i.id_ingreso = ing.id_ingreso
            WHERE i.id_insumo = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        echo json_encode(['success' => false, 'error' => 'El insumo no existe']);
        exit;
    }

    // Obtener datos específicos según el tipo
    $datos_especificos = [];
    if ($insumo['tipo_insumo'] !== 'Varios') {
        switch ($insumo['tipo_insumo']) {
            case 'PC Completa':
            case 'PC Escritorio':
                $stmt = $conexion->prepare("SELECT * FROM pcs_completas WHERE id_insumo = ?");
                $stmt->execute([$id]);
                $datos_especificos = $stmt->fetch();
                break;
            case 'Notebook':
                $stmt = $conexion->prepare("SELECT * FROM notebooks WHERE id_insumo = ?");
                $stmt->execute([$id]);
                $datos_especificos = $stmt->fetch();
                break;
            case 'Impresora':
                $stmt = $conexion->prepare("SELECT * FROM impresoras WHERE id_insumo = ?");
                $stmt->execute([$id]);
                $datos_especificos = $stmt->fetch();
                break;
            case 'Monitor':
                $stmt = $conexion->prepare("SELECT * FROM monitores WHERE id_insumo = ?");
                $stmt->execute([$id]);
                $datos_especificos = $stmt->fetch();
                break;
            case 'Escaner':
                $stmt = $conexion->prepare("SELECT * FROM escaneres WHERE id_insumo = ?");
                $stmt->execute([$id]);
                $datos_especificos = $stmt->fetch();
                break;
        }
    }

    // Obtener historial de asignaciones basado en remitos/remitos_detalle
    $sql_asignaciones = "
        SELECT r.numero_remito, r.fecha_asignacion, r.fecha_devolucion, r.estado, r.nombre_persona_asignada, r.apellido_persona_asignada,
               ar.nombre_area, s.nombre_sede" . ($insumo['tipo_insumo'] === 'Varios' ? ", d.cantidad" : "") . "
        FROM remitos_detalle d
        JOIN remitos r ON r.id_remito = d.id_remito
        LEFT JOIN areas ar ON r.id_area = ar.id_area
        LEFT JOIN sedes s ON r.id_sede = s.id_sede
        WHERE d.id_insumo = ?" . ($insumo['tipo_insumo'] === 'Varios' ? " AND r.estado != 'Activa'" : "") . "
        ORDER BY r.fecha_asignacion DESC
        LIMIT 5";
    $stmt_asignaciones = $conexion->prepare($sql_asignaciones);
    $stmt_asignaciones->execute([$id]);
    $asignaciones = $stmt_asignaciones->fetchAll();

    // Buscar remito activo (asignación actual) si existe
    $filtroRemito = isset($_GET['remito']) ? trim($_GET['remito']) : '';
    $whereRemito = "d.id_insumo = ? AND r.estado = 'Activa'";
    $paramsRemito = [$id];

    if ($filtroRemito !== '' && $insumo['tipo_insumo'] !== 'Varios') {
        $whereRemito .= " AND r.numero_remito = ?";
        $paramsRemito[] = $filtroRemito;
    }

    $stmt_act = $conexion->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado,
                                           r.nombre_persona_asignada, r.apellido_persona_asignada, r.declaracion_jurada,
                                           s.nombre_sede, l.nombre_localidad, z.nombre_zona, ar.nombre_area,
                                           d.cantidad AS cantidad_asignada, d.cantidad_devuelta
                                    FROM remitos_detalle d
                                    JOIN remitos r ON r.id_remito = d.id_remito
                                    JOIN sedes s ON r.id_sede = s.id_sede
                                    JOIN localidades l ON s.id_localidad = l.id_localidad
                                    JOIN zonas z ON l.id_zona = z.id_zona
                                    LEFT JOIN areas ar ON r.id_area = ar.id_area
                                    WHERE $whereRemito
                                    ORDER BY r.fecha_asignacion DESC");
    $stmt_act->execute($paramsRemito);
    $remitos_activos = $stmt_act->fetchAll();
    
    $remito_activo = !empty($remitos_activos) ? $remitos_activos[0] : null;

    // No sobreescribir la cantidad del insumo con la del remito para mantener la visualización del stock real disponible (oficina + depósito)
    $cantidad_remito_relacionado = 0;
    if ($filtroRemito !== '' && $remito_activo && $insumo['tipo_insumo'] === 'Varios') {
        $cantidad_remito_relacionado = max(0, $remito_activo['cantidad_asignada'] - $remito_activo['cantidad_devuelta']);
    }

    // Última baja (si existe la tabla)
    $ultima_baja = null;
    $bajas_hist = [];
    try {
        $stmt_b = $conexion->prepare("SELECT fecha_baja, observacion FROM insumos_bajas WHERE id_insumo = ? ORDER BY fecha_baja DESC LIMIT 1");
        $stmt_b->execute([$id]);
        $ultima_baja = $stmt_b->fetch();
        $stmt_b2 = $conexion->prepare("SELECT fecha_baja, observacion FROM insumos_bajas WHERE id_insumo = ? ORDER BY fecha_baja DESC");
        $stmt_b2->execute([$id]);
        $bajas_hist = $stmt_b2->fetchAll();
    } catch (Exception $e) {
        $ultima_baja = null;
        $bajas_hist = [];
    }

    // Generar HTML para el modal
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <!-- Información Principal -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información General
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><?php echo ($insumo['tipo_insumo'] !== 'Varios') ? 'Descripción' : 'Nombre'; ?>:</strong>
                                <?php echo htmlspecialchars($insumo['nombre_insumo']); ?></p>
                            <p><strong>Tipo:</strong>
                                <span class="badge bg-info"><?php
                                if ($insumo['tipo_insumo'] === 'Varios' && !empty($insumo['subcategoria_varios'])) {
                                    echo htmlspecialchars($insumo['subcategoria_varios']);
                                } else {
                                    echo htmlspecialchars($insumo['tipo_insumo']);
                                }
                                ?></span>
                            </p>
                            <p><strong>Estado:</strong>
                                <span
                                    class="badge estado-<?php echo strtolower(str_replace(' ', '-', $insumo['estado'])); ?>">
                                    <?php echo $insumo['estado']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php 
                            $cantidadStock = ($insumo['tipo_insumo'] === 'Varios') 
                                ? ((int)$insumo['cantidad_oficina'] + (int)$insumo['cantidad_deposito']) 
                                : (int)$insumo['cantidad'];
                            ?>
                            <p><strong>Cantidad:</strong>
                                <span class="badge <?php echo $cantidadStock > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $cantidadStock; ?>
                                </span>
                                <?php if ($insumo['tipo_insumo'] === 'Varios'): ?>
                                    <br><small class="text-muted">(Oficina: <?php echo (int)$insumo['cantidad_oficina']; ?> | Depósito: <?php echo (int)$insumo['cantidad_deposito']; ?>)</small>
                                <?php endif; ?>
                            </p>
                            <p><strong>Fecha de Adquisición:</strong>
                                <?php echo $insumo['fecha_adquisicion'] ? date('d/m/Y', strtotime($insumo['fecha_adquisicion'])) : '-'; ?>
                            </p>
                            <?php if ($insumo['estado'] !== 'Asignado'): ?>
                                <p><strong>Punto de Almacenamiento:</strong>
                                    <?php echo $insumo['nombre_punto'] ?: 'Sin asignar'; ?></p>
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
                                        $labelIngreso = match ($tipoIngreso) {
                                            'fondos' => 'Nro. Nota (Fondos)',
                                            'licitacion' => 'Nro. Expediente (Licitación)',
                                            'compra_directa' => 'Nro. Expediente (Compra Directa)',
                                            'otros' => 'Nro. Referencia',
                                            default => 'Nro. Referencia'
                                        };
                                        echo $labelIngreso;
                                        ?>:</strong>
                                    <span
                                        class="badge bg-primary"><?php echo htmlspecialchars($insumo['ingreso_referencia']); ?></span>
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
                        <p><strong>Descripción General:</strong></p>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($insumo['descripcion_general'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información Específica según Tipo -->
            <?php if ($insumo['tipo_insumo'] !== 'Varios' && $datos_especificos): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Especificaciones Técnicas
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($insumo['tipo_insumo'] === 'PC Escritorio' || $insumo['tipo_insumo'] === 'PC Completa'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['procesador']): ?>
                                        <p><strong>Procesador:</strong>
                                            <?php echo htmlspecialchars($datos_especificos['procesador']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['ram_gb']): ?>
                                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb']); ?> GB</p>
                                    <?php endif; ?>
                                    <?php if (!empty($datos_especificos['sist_op'])): ?>
                                        <p><strong>Sistema Operativo:</strong>
                                            <?php echo htmlspecialchars($datos_especificos['sist_op']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['almacenamiento_gb']): ?>
                                        <p><strong>Almacenamiento:</strong>
                                            <?php echo htmlspecialchars($datos_especificos['almacenamiento_gb']); ?> GB</p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['mother']): ?>
                                        <p><strong>Motherboard:</strong> <?php echo htmlspecialchars($datos_especificos['mother']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($insumo['tipo_insumo'] === 'Notebook'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['marca']): ?>
                                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($datos_especificos['marca']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['modelo']): ?>
                                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($datos_especificos['modelo']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['procesador']): ?>
                                        <p><strong>Procesador:</strong>
                                            <?php echo htmlspecialchars($datos_especificos['procesador']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['ram_gb']): ?>
                                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb']); ?> GB</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr>
                            <?php
                            $cargador = !empty($datos_especificos['cargador']);
                            $funda = !empty($datos_especificos['funda']);
                            $microSd = !empty($datos_especificos['micro_sd']);
                            $microSdGb = isset($datos_especificos['micro_sd_gb']) && $datos_especificos['micro_sd_gb'] !== '' ? (int) $datos_especificos['micro_sd_gb'] : null;
                            $caja = !empty($datos_especificos['caja']);
                            $adaptadorRed = !empty($datos_especificos['adaptador_red']);
                            ?>
                            <div class="row g-2">
                                <div class="col-sm-6"><span class="text-muted"><i class="fas fa-plug me-1"></i>Cargador:</span>
                                    <span
                                        class="badge <?php echo $cargador ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $cargador ? 'Sí' : 'No'; ?></span>
                                </div>
                                <div class="col-sm-6"><span class="text-muted"><i class="fas fa-suitcase me-1"></i>Funda:</span>
                                    <span
                                        class="badge <?php echo $funda ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $funda ? 'Sí' : 'No'; ?></span>
                                </div>
                                <div class="col-sm-6"><span class="text-muted"><i class="fas fa-sd-card me-1"></i>Micro SD:</span>
                                    <span
                                        class="badge <?php echo $microSd ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $microSd ? 'Sí' : 'No'; ?></span>
                                    <?php if ($microSd && $microSdGb !== null): ?><small
                                            class="text-muted ms-1"><?php echo $microSdGb; ?> GB</small><?php endif; ?>
                                </div>
                                <div class="col-sm-6"><span class="text-muted"><i class="fas fa-box me-1"></i>Caja:</span> <span
                                        class="badge <?php echo $caja ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $caja ? 'Sí' : 'No'; ?></span>
                                </div>
                                <div class="col-sm-6"><span class="text-muted"><i class="fas fa-network-wired me-1"></i>Adaptador de
                                        red:</span> <span
                                        class="badge <?php echo $adaptadorRed ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $adaptadorRed ? 'Sí' : 'No'; ?></span>
                                </div>
                            </div>
                        <?php elseif ($insumo['tipo_insumo'] === 'Impresora'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['marca']): ?>
                                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($datos_especificos['marca']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['modelo']): ?>
                                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($datos_especificos['modelo']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($insumo['tipo_insumo'] === 'Monitor'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['marca']): ?>
                                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($datos_especificos['marca']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['modelo']): ?>
                                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($datos_especificos['modelo']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['pulgadas']): ?>
                                        <p><strong>Pulgadas:</strong> <?php echo htmlspecialchars($datos_especificos['pulgadas']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['conexion']): ?>
                                        <p><strong>Conexión:</strong> <?php 
                                            $cx = $datos_especificos['conexion'];
                                            echo htmlspecialchars($cx . ($cx === 'Ambas' ? ' (HDMI-VGA)' : '')); 
                                        ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($insumo['tipo_insumo'] === 'Escaner'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($datos_especificos['marca']): ?>
                                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($datos_especificos['marca']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($datos_especificos['modelo']): ?>
                                        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($datos_especificos['modelo']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <?php if ($remito_activo && $insumo['tipo_insumo'] !== 'Varios'): 
                $rem = $remito_activo;
            ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning-subtle text-warning-emphasis py-3 px-3">
                        <h6 class="mb-0 small fw-bold">Asignación activa</h6>
                    </div>
                    <div class="card-body py-3">
                        <p class="mb-2"><span class="badge bg-warning">Activa</span></p>
                        <p class="mb-2 small"><strong>Remito:</strong> <?php echo htmlspecialchars($rem['numero_remito']); ?></p>
                        <p class="mb-2 small"><strong>Persona:</strong> <?php echo htmlspecialchars(($rem['nombre_persona_asignada'] ?? '') . ' ' . ($rem['apellido_persona_asignada'] ?? '')); ?></p>
                        <p class="mb-2 small"><strong>Localidad:</strong> <?php echo htmlspecialchars($rem['nombre_localidad']); ?></p>
                        <p class="mb-2 small"><strong>Sede:</strong> <?php echo htmlspecialchars($rem['nombre_sede']); ?></p>
                        <a class="btn btn-sm btn-outline-primary py-1 px-3 mt-2" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($rem['numero_remito']); ?>">Ver remito</a>
                        <?php if (!empty($rem['declaracion_jurada'])): ?>
                            <a class="btn btn-sm btn-outline-success py-1 px-3 mt-2 ms-1" href="<?php echo app_base_url(); ?>/uploads/documentos/<?php echo htmlspecialchars($rem['declaracion_jurada']); ?>" target="_blank"><i class="fas fa-file-pdf me-1"></i>Ver DDJJ</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (!empty($remitos_activos) && $insumo['tipo_insumo'] === 'Varios'): 
                $totalAsig = count($remitos_activos);
                $limiteMostrar = 5;
                $mostrarBotonDesglose = $totalAsig > $limiteMostrar;
                $remitosRender = $mostrarBotonDesglose ? array_slice($remitos_activos, 0, $limiteMostrar) : $remitos_activos;
            ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning-subtle text-warning-emphasis py-3 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 small fw-bold">Asignaciones activas (<?php echo $totalAsig; ?>)</h6>
                        <?php if ($mostrarBotonDesglose): ?>
                            <button type="button" class="btn btn-xs btn-primary py-1 px-3 fw-bold" style="font-size: 0.75rem;" data-bs-toggle="collapse" data-bs-target="#desgloseAsignacionesCollapse" aria-expanded="false" aria-controls="desgloseAsignacionesCollapse">
                                <i class="fas fa-expand me-1"></i>Ver todas
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body py-2" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($remitosRender as $rem): ?>
                            <div class="p-3 mb-2 bg-light rounded border small text-dark">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-warning">Activa</span>
                                    <span class="badge bg-primary">Cant: <?php echo (int)($rem['cantidad_asignada'] - $rem['cantidad_devuelta']); ?></span>
                                </div>
                                <p class="mb-1"><strong>Remito:</strong> <?php echo htmlspecialchars($rem['numero_remito']); ?></p>
                                <p class="mb-1"><strong>Persona:</strong> <?php echo htmlspecialchars(($rem['nombre_persona_asignada'] ?? '') . ' ' . ($rem['apellido_persona_asignada'] ?? '')); ?></p>
                                <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($rem['nombre_localidad']); ?></p>
                                <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($rem['nombre_sede']); ?></p>
                                <a class="btn btn-xs btn-outline-primary py-1 px-3 mt-2" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($rem['numero_remito']); ?>">Ver remito</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-ban me-2"></i>Última Baja</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($ultima_baja): ?>
                            <p class="mb-1"><strong>Fecha:</strong>
                                <?php echo date('d/m/Y H:i', strtotime($ultima_baja['fecha_baja'])); ?></p>
                            <p class="mb-0"><strong>Observación:</strong> <span
                                    class="text-muted"><?php echo htmlspecialchars($ultima_baja['observacion']); ?></span></p>
                        <?php else: ?>
                            <p class="text-muted mb-0">Sin registros de baja</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

                <!-- Historial de asignaciones -->
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Asignaciones</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($asignaciones && count($asignaciones) > 0): ?>
                                <ul class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                    <?php foreach ($asignaciones as $asig): ?>
                                        <li class="list-group-item px-0 py-2 small text-dark">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>Remito:</strong> <?php echo htmlspecialchars($asig['numero_remito']); ?>
                                                <?php if ($insumo['tipo_insumo'] === 'Varios'): ?>
                                                    <span class="badge bg-secondary">Cant: <?php echo (int)$asig['cantidad']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <strong>Persona:</strong> <?php echo htmlspecialchars(($asig['nombre_persona_asignada'] ?? '') . ' ' . ($asig['apellido_persona_asignada'] ?? '')); ?>
                                            <br><strong>Sede:</strong> <?php echo htmlspecialchars($asig['nombre_sede']); ?>
                                            <?php if ($asig['nombre_area']): ?>
                                                <br><strong>Área:</strong> <?php echo htmlspecialchars($asig['nombre_area']); ?>
                                            <?php endif; ?>
                                            <br><strong>Fecha Asignación:</strong> <?php echo date('d/m/Y', strtotime($asig['fecha_asignacion'])); ?>
                                            <?php if ($asig['fecha_devolucion']): ?>
                                                <br><strong>Fecha Devolución:</strong> <?php echo date('d/m/Y', strtotime($asig['fecha_devolucion'])); ?>
                                            <?php endif; ?>
                                            <br><span class="badge bg-secondary mt-1"><?php echo $asig['estado']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">Sin historial de asignaciones</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <!-- Fila colapsable para Desglose Completo (solo si corresponde) -->
    <?php if (isset($mostrarBotonDesglose) && $mostrarBotonDesglose): ?>
        <div class="row">
            <div class="col-12 mt-2 collapse" id="desgloseAsignacionesCollapse">
                <div class="card border-primary shadow-sm mb-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 small"><i class="fas fa-list me-2"></i>Desglose Completo de Asignaciones Activas</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#desgloseAsignacionesCollapse" aria-label="Cerrar"></button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm text-dark align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2">Remito</th>
                                        <th class="py-2">Sede</th>
                                        <th class="py-2">Localidad</th>
                                        <th class="py-2">Área</th>
                                        <th class="py-2">Persona Asignada</th>
                                        <th class="py-2 text-center">Cant. Activa</th>
                                        <th class="pe-3 py-2 text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($remitos_activos as $rem): ?>
                                        <tr>
                                            <td class="ps-3 py-2 fw-bold text-primary"><?php echo htmlspecialchars($rem['numero_remito']); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars($rem['nombre_sede']); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars($rem['nombre_localidad']); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars($rem['nombre_area'] ?: '-'); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars(trim(($rem['nombre_persona_asignada'] ?? '') . ' ' . ($rem['apellido_persona_asignada'] ?? '')) ?: '-'); ?></td>
                                            <td class="py-2 text-center fw-bold"><span class="badge bg-primary"><?php echo (int)($rem['cantidad_asignada'] - $rem['cantidad_devuelta']); ?></span></td>
                                            <td class="pe-3 py-2 text-end">
                                                <a class="btn btn-xs btn-outline-primary py-0 px-2" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($rem['numero_remito']); ?>" target="_blank">
                                                    <i class="fas fa-eye me-1"></i>Ver Remito
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $html = ob_get_clean();
    $remitoNum = $remito_activo ? (string) $remito_activo['numero_remito'] : null;
    $personaAsignada = $remito_activo ? trim(($remito_activo['nombre_persona_asignada'] ?? '') . ' ' . ($remito_activo['apellido_persona_asignada'] ?? '')) : null;
    $tipoInsumo = isset($insumo['tipo_insumo']) ? (string) $insumo['tipo_insumo'] : null;
    $cantInsumo = ($tipoInsumo === 'Varios') 
        ? ((int)$insumo['cantidad_oficina'] + (int)$insumo['cantidad_deposito']) 
        : (isset($insumo['cantidad']) ? (int) $insumo['cantidad'] : null);
    echo json_encode([
        'success' => true,
        'html' => $html,
        'remito_activo_numero' => $remitoNum,
        'persona_asignada' => $personaAsignada,
        'insumo_tipo' => $tipoInsumo,
        'insumo_cantidad' => $cantInsumo
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cargar los datos del insumo: ' . $e->getMessage()]);
}
?>