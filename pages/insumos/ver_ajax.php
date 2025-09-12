<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de insumo no especificado']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $conexion = conectarDB();
    
    // Obtener información completa del insumo
    $sql = "SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona 
            FROM insumos i 
            LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
            LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area 
            LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
            LEFT JOIN localidades l ON s.id_localidad = l.id_localidad 
            LEFT JOIN zonas z ON l.id_zona = z.id_zona 
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
               ar.nombre_area, s.nombre_sede
        FROM remitos_detalle d
        JOIN remitos r ON r.id_remito = d.id_remito
        LEFT JOIN areas ar ON r.id_area = ar.id_area
        LEFT JOIN sedes s ON r.id_sede = s.id_sede
        WHERE d.id_insumo = ?
        ORDER BY r.fecha_asignacion DESC
        LIMIT 5";
    $stmt_asignaciones = $conexion->prepare($sql_asignaciones);
    $stmt_asignaciones->execute([$id]);
    $asignaciones = $stmt_asignaciones->fetchAll();
    
    // Buscar remito activo (asignación actual) si existe
    $stmt_act = $conexion->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado,
                                           s.nombre_sede, l.nombre_localidad, z.nombre_zona, ar.nombre_area
                                    FROM remitos_detalle d
                                    JOIN remitos r ON r.id_remito = d.id_remito
                                    JOIN sedes s ON r.id_sede = s.id_sede
                                    JOIN localidades l ON s.id_localidad = l.id_localidad
                                    JOIN zonas z ON l.id_zona = z.id_zona
                                    JOIN areas ar ON r.id_area = ar.id_area
                                    WHERE d.id_insumo = ? AND r.estado = 'Activa'
                                    ORDER BY r.fecha_asignacion DESC LIMIT 1");
    $stmt_act->execute([$id]);
    $remito_activo = $stmt_act->fetch();

    // Última baja (si existe la tabla)
    $ultima_baja = null;
    try {
        $stmt_b = $conexion->prepare("SELECT fecha_baja, observacion FROM insumos_bajas WHERE id_insumo = ? ORDER BY fecha_baja DESC LIMIT 1");
        $stmt_b->execute([$id]);
        $ultima_baja = $stmt_b->fetch();
    } catch (Exception $e) {
        $ultima_baja = null;
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
                            <p><strong>ID:</strong> <?php echo $insumo['id_insumo']; ?></p>
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($insumo['nombre_insumo']); ?></p>
                            <p><strong>Tipo:</strong> 
                                <span class="badge bg-info"><?php echo $insumo['tipo_insumo']; ?></span>
                                <?php if ($insumo['subcategoria_varios']): ?>
                                    <br><small class="text-muted"><?php echo $insumo['subcategoria_varios']; ?></small>
                                <?php endif; ?>
                            </p>
                            <p><strong>Estado:</strong> 
                                <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $insumo['estado'])); ?>">
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
                            <p><strong>Fecha de Adquisición:</strong> 
                                <?php echo $insumo['fecha_adquisicion'] ? date('d/m/Y', strtotime($insumo['fecha_adquisicion'])) : '-'; ?>
                            </p>
                            <?php if ($insumo['estado'] !== 'Asignado'): ?>
                                <p><strong>Punto de Almacenamiento:</strong> <?php echo $insumo['nombre_punto'] ?: 'Sin asignar'; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($insumo['numero_serie'] || $insumo['id_fisico']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <?php if ($insumo['numero_serie']): ?>
                                <p><strong>Número de Serie:</strong> <?php echo htmlspecialchars($insumo['numero_serie']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($insumo['id_fisico']): ?>
                                <p><strong>ID Físico:</strong> <?php echo htmlspecialchars($insumo['id_fisico']); ?></p>
                            <?php endif; ?>
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
                    <?php if ($insumo['tipo_insumo'] === 'PC Completa'): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <?php if ($datos_especificos['procesador']): ?>
                                    <p><strong>Procesador:</strong> <?php echo htmlspecialchars($datos_especificos['procesador']); ?></p>
                                <?php endif; ?>
                                <?php if ($datos_especificos['ram_gb']): ?>
                                    <p><strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb']); ?> GB</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($datos_especificos['almacenamiento_gb']): ?>
                                    <p><strong>Almacenamiento:</strong> <?php echo htmlspecialchars($datos_especificos['almacenamiento_gb']); ?> GB</p>
                                <?php endif; ?>
                                <?php if ($datos_especificos['mother']): ?>
                                    <p><strong>Motherboard:</strong> <?php echo htmlspecialchars($datos_especificos['mother']); ?></p>
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
                                    <p><strong>Procesador:</strong> <?php echo htmlspecialchars($datos_especificos['procesador']); ?></p>
                                <?php endif; ?>
                                <?php if ($datos_especificos['ram_gb']): ?>
                                    <p><strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb']); ?> GB</p>
                                <?php endif; ?>
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
                                    <p><strong>Pulgadas:</strong> <?php echo htmlspecialchars($datos_especificos['pulgadas']); ?></p>
                                <?php endif; ?>
                                <?php if ($datos_especificos['conexion']): ?>
                                    <p><strong>Conexión:</strong> <?php echo htmlspecialchars($datos_especificos['conexion']); ?></p>
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
            <!-- Ubicación Actual -->
            <?php if ($remito_activo && $insumo['tipo_insumo'] !== 'Varios'): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Ubicación Actual
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($remito_activo['nombre_sede']); ?></p>
                    <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($remito_activo['nombre_localidad']); ?></p>
                    <p class="mb-1"><strong>Zona:</strong> <?php echo htmlspecialchars($remito_activo['nombre_zona']); ?></p>
                    <?php if (!empty($remito_activo['nombre_area'])): ?>
                        <p class="mb-1"><strong>Área:</strong> <?php echo htmlspecialchars($remito_activo['nombre_area']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($remito_activo): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-link me-2"></i>Asignación activa
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <span class="badge bg-warning">Activa</span>
                        <strong>ID:</strong> <?php echo (int)$remito_activo['id_remito']; ?>
                    </p>
                    <p class="mb-1"><strong>Remito:</strong> <?php echo htmlspecialchars($remito_activo['numero_remito']); ?></p>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($remito_activo['numero_remito']); ?>">
                        Ver remito
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Última Baja -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-ban me-2"></i>Última Baja
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($ultima_baja): ?>
                        <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($ultima_baja['fecha_baja'])); ?></p>
                        <p class="mb-0"><strong>Observación:</strong> <span class="text-muted"><?php echo htmlspecialchars($ultima_baja['observacion']); ?></span></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Sin registros de baja</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de Asignaciones -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Últimas Asignaciones
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($asignaciones)): ?>
                        <p class="text-muted">No hay asignaciones registradas</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($asignaciones as $asignacion): ?>
                                <div class="timeline-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($asignacion['fecha_asignacion'])); ?>
                                        </small>
                                        <?php 
                                            $estadoEvento = !empty($asignacion['fecha_devolucion']) ? 'Devuelta' : ($asignacion['estado'] ?: 'Activa');
                                            $badge = 'estado-' . strtolower($estadoEvento);
                                        ?>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo $estadoEvento; ?>
                                        </span>
                                    </div>
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars($asignacion['nombre_sede']); ?></strong>
                                        <?php if ($asignacion['nombre_area']): ?>
                                            - <?php echo htmlspecialchars($asignacion['nombre_area']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        Asignado a: <?php echo htmlspecialchars($asignacion['nombre_persona_asignada'] . ' ' . $asignacion['apellido_persona_asignada']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cargar los datos del insumo: ' . $e->getMessage()]);
}
?> 