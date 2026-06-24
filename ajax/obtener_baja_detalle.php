<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autorizado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('Sin permiso para esta acción', 403);
}

if (!isset($_GET['id'])) {
    json_error('ID de baja no especificado', 400);
}

$idBaja = (int)$_GET['id'];

try {
    $db = conectarDB();
    
    // Obtener datos de la baja y del insumo relacionado
    $sql = "SELECT b.id_baja, b.id_insumo, b.fecha_baja, b.observacion, b.cantidad AS cantidad_baja,
                   i.nombre_insumo, i.tipo_insumo, i.subcategoria_varios, i.es_nuevo, i.numero_serie, 
                   i.id_fisico, i.id_patrimonio, i.descripcion_general
            FROM insumos_bajas b
            JOIN insumos i ON i.id_insumo = b.id_insumo
            WHERE b.id_baja = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$idBaja]);
    $baja = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$baja) {
        json_error('Detalle de baja no encontrado', 404);
    }
    
    $idInsumo = (int)$baja['id_insumo'];
    
    // Buscar si existe un informe técnico asociado a la baja
    $idPedidoAsociado = null;
    if (preg_match('/\(Informe\s+#([A-Za-z0-9_]+)\)/', $baja['observacion'], $matches)) {
        $nroInforme = $matches[1];
        $stmt_ped = $db->prepare("SELECT id_pedido FROM pedidos_informes WHERE numero_informe = ?");
        $stmt_ped->execute([$nroInforme]);
        $ped = $stmt_ped->fetch();
        if ($ped) {
            $idPedidoAsociado = (int)$ped['id_pedido'];
        }
    }
    
    // Fallback: buscar último pedido con informe para este insumo dado de baja
    if (!$idPedidoAsociado) {
        $stmt_ped_fallback = $db->prepare("
            SELECT pi.id_pedido 
            FROM pedidos_informes pi 
            JOIN pedidos p ON pi.id_pedido = p.id_pedido 
            WHERE p.id_insumo_relacionado = ? 
            ORDER BY pi.fecha_informe DESC LIMIT 1
        ");
        $stmt_ped_fallback->execute([$idInsumo]);
        $ped_fallback = $stmt_ped_fallback->fetch();
        if ($ped_fallback) {
            $idPedidoAsociado = (int)$ped_fallback['id_pedido'];
        }
    }
    
    // Obtener datos específicos según el tipo de insumo (similar a ver_ajax.php)
    $datos_especificos = [];
    if ($baja['tipo_insumo'] !== 'Varios') {
        switch ($baja['tipo_insumo']) {
            case 'PC Completa':
            case 'PC Escritorio':
                $stmt = $db->prepare("SELECT * FROM pcs_completas WHERE id_insumo = ?");
                $stmt->execute([$idInsumo]);
                $datos_especificos = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'Notebook':
                $stmt = $db->prepare("SELECT * FROM notebooks WHERE id_insumo = ?");
                $stmt->execute([$idInsumo]);
                $datos_especificos = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'Impresora':
                $stmt = $db->prepare("SELECT * FROM impresoras WHERE id_insumo = ?");
                $stmt->execute([$idInsumo]);
                $datos_especificos = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'Monitor':
                $stmt = $db->prepare("SELECT * FROM monitores WHERE id_insumo = ?");
                $stmt->execute([$idInsumo]);
                $datos_especificos = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'Escaner':
                $stmt = $db->prepare("SELECT * FROM escaneres WHERE id_insumo = ?");
                $stmt->execute([$idInsumo]);
                $datos_especificos = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
        }
    }

    // Intentar buscar el usuario que realizó la acción en la tabla de auditoría
    $usuario_baja = 'No registrado';
    try {
        $stmt_aud = $db->prepare("
            SELECT u.nombre, u.apellido 
            FROM auditoria_acciones a
            JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE a.accion = 'baja_insumo' AND a.id_objeto_afectado = ?
            ORDER BY a.fecha DESC LIMIT 1
        ");
        $stmt_aud->execute([$idInsumo]);
        $aud = $stmt_aud->fetch(PDO::FETCH_ASSOC);
        if ($aud) {
            $usuario_baja = htmlspecialchars($aud['nombre'] . ' ' . $aud['apellido']);
        }
    } catch (Exception $e) {
        // Ignorar si falla la consulta de auditoría
    }

    // Generar el HTML para el modal
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-7">
            <!-- Información de la Baja -->
            <div class="card mb-3 border-danger-subtle">
                <div class="card-header d-flex align-items-center" style="background-color: #fdf2f2; color: #9b1c1c; border-bottom: 1px solid #f8d7da;">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i>Detalles del Movimiento de Baja
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <p class="mb-1"><strong>Fecha y Hora:</strong></p>
                            <p class="text-muted"><i class="far fa-calendar-alt me-1"></i><?php echo date('d/m/Y H:i:s', strtotime($baja['fecha_baja'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="mb-1"><strong>Cantidad de Baja:</strong></p>
                            <p><span class="badge bg-danger fs-6" style="background-color: #e53e3e !important;"><?php echo (int)$baja['cantidad_baja']; ?></span></p>
                        </div>
                        <div class="col-12 mb-2">
                            <p class="mb-1"><strong>Usuario Responsable:</strong></p>
                            <p class="text-muted"><i class="fas fa-user me-1"></i><?php echo $usuario_baja; ?></p>
                        </div>
                        <div class="col-12">
                            <p class="mb-1"><strong>Observación / Motivo:</strong></p>
                            <div class="p-3 bg-light rounded text-dark" style="border-left: 4px solid #ea868f; min-height: 60px;">
                                <?php echo nl2br(htmlspecialchars($baja['observacion'])); ?>
                            </div>
                            <?php if ($idPedidoAsociado): ?>
                                <div class="mt-3">
                                    <a href="<?php echo app_base_url(); ?>/pages/pedidos/informe_pdf.php?id=<?php echo $idPedidoAsociado; ?>" target="_blank" class="btn btn-outline-danger btn-sm w-100 py-2 fw-bold">
                                        <i class="fas fa-file-pdf me-2"></i>Descargar Informe Técnico
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información General del Insumo -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información del Insumo Relacionado
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($baja['nombre_insumo']); ?></p>
                            <p><strong>Tipo:</strong> 
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($baja['tipo_insumo'] === 'Varios' && !empty($baja['subcategoria_varios']) ? $baja['subcategoria_varios'] : $baja['tipo_insumo']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Condición:</strong> 
                                <span class="badge <?php echo ($baja['es_nuevo'] ?? 1) ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo ($baja['es_nuevo'] ?? 1) ? 'Nuevo' : 'Usado'; ?>
                                </span>
                            </p>
                            <p><strong>ID Insumo:</strong> <span class="badge bg-secondary">#<?php echo $idInsumo; ?></span></p>
                        </div>
                    </div>

                    <?php if ($baja['tipo_insumo'] !== 'Varios'): ?>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Número de Serie:</strong><br>
                                    <span class="text-muted"><?php echo !empty($baja['numero_serie']) ? htmlspecialchars($baja['numero_serie']) : 'No tiene'; ?></span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>ID Físico:</strong><br>
                                    <span class="text-muted"><?php echo !empty($baja['id_fisico']) ? htmlspecialchars($baja['id_fisico']) : 'No tiene'; ?></span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>ID Patrimonio:</strong><br>
                                    <span class="text-muted"><?php echo !empty($baja['id_patrimonio']) ? htmlspecialchars($baja['id_patrimonio']) : 'No tiene'; ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($baja['descripcion_general'])): ?>
                        <hr>
                        <p><strong>Descripción General:</strong></p>
                        <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($baja['descripcion_general'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <!-- Especificaciones Técnicas (si aplican) -->
            <?php if ($baja['tipo_insumo'] !== 'Varios' && $datos_especificos): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Especificaciones Técnicas
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($baja['tipo_insumo'] === 'PC Escritorio' || $baja['tipo_insumo'] === 'PC Completa'): ?>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <strong>Procesador:</strong> <?php echo htmlspecialchars($datos_especificos['procesador'] ?: 'No especificado'); ?>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb'] ? $datos_especificos['ram_gb'] . ' GB' : 'No especificada'); ?>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>Almacenamiento:</strong> <?php echo htmlspecialchars($datos_especificos['almacenamiento_gb'] ? $datos_especificos['almacenamiento_gb'] . ' GB' : 'No especificado'); ?>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>Sistema Operativo:</strong> <?php echo htmlspecialchars($datos_especificos['sist_op'] ?: 'No especificado'); ?>
                                </div>
                                <div class="col-12">
                                    <strong>Motherboard:</strong> <?php echo htmlspecialchars($datos_especificos['mother'] ?: 'No especificado'); ?>
                                </div>
                            </div>
                        <?php elseif ($baja['tipo_insumo'] === 'Notebook'): ?>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <strong>Marca / Modelo:</strong> <?php echo htmlspecialchars(($datos_especificos['marca'] ?? '') . ' ' . ($datos_especificos['modelo'] ?? '')) ?: 'No especificado'; ?>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>Procesador:</strong> <?php echo htmlspecialchars($datos_especificos['procesador'] ?: 'No especificado'); ?>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>RAM:</strong> <?php echo htmlspecialchars($datos_especificos['ram_gb'] ? $datos_especificos['ram_gb'] . ' GB' : 'No especificada'); ?>
                                </div>
                            </div>
                        <?php elseif ($baja['tipo_insumo'] === 'Impresora' || $baja['tipo_insumo'] === 'Monitor' || $baja['tipo_insumo'] === 'Escaner'): ?>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <strong>Marca / Modelo:</strong> <?php echo htmlspecialchars(($datos_especificos['marca'] ?? '') . ' ' . ($datos_especificos['modelo'] ?? '')) ?: 'No especificado'; ?>
                                </div>
                                <?php if ($baja['tipo_insumo'] === 'Monitor'): ?>
                                    <div class="col-12 mb-2">
                                        <strong>Pulgadas:</strong> <?php echo htmlspecialchars($datos_especificos['pulgadas'] ?: 'No especificado'); ?>
                                    </div>
                                    <div class="col-12">
                                        <strong>Conexión:</strong> <?php echo htmlspecialchars($datos_especificos['conexion'] ?: 'No especificado'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-boxes me-2"></i>Tipo de Insumo
                        </h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-tag fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Insumo genérico o "Varios"</h6>
                        <p class="text-muted small mb-0">No contiene especificaciones técnicas de hardware dedicadas.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    json_success(['html' => $html]);

} catch (Exception $e) {
    Logger::error('Error al obtener detalle de baja', [
        'id_baja' => $idBaja,
        'error' => $e->getMessage()
    ]);
    json_error('Error al procesar la solicitud: ' . $e->getMessage(), 500);
}
?>
