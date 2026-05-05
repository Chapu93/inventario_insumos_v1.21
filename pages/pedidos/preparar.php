<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('pedidos', 'gestionar');

$db = conectarDB();

$idPedido = (int)($_GET['id'] ?? 0);
if (!$idPedido) {
    $_SESSION['mensaje'] = 'ID de pedido no válido';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

// Obtener datos del pedido
$stmt = $db->prepare("SELECT p.*, s.nombre_sede, a.nombre_area, l.nombre_localidad 
                      FROM pedidos p 
                      JOIN sedes s ON p.id_sede = s.id_sede 
                      JOIN localidades l ON s.id_localidad = l.id_localidad
                      LEFT JOIN areas a ON p.id_area = a.id_area 
                      WHERE p.id_pedido = ? AND p.tipo = 'Pedido Insumo'");
$stmt->execute([$idPedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    $_SESSION['mensaje'] = 'Pedido no encontrado';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

if ($pedido['estado'] !== 'Pendiente') {
    $_SESSION['mensaje'] = 'Este pedido ya ha sido preparado o está en otro estado.';
    $_SESSION['tipo_mensaje'] = 'warning';
    header('Location: listar.php');
    exit;
}

// Insumos disponibles - Misma lógica de búsqueda de nueva_pasos.php
$stmt = $db->query("SELECT i.id_insumo,
                           i.nombre_insumo,
                           i.tipo_insumo,
                           i.numero_serie,
                           i.id_fisico,
                           i.cantidad,
                           ps.nombre_punto AS punto_stock,
                           pc.sist_op AS pc_sist_op,
                           nb.marca AS nb_marca,
                           nb.modelo AS nb_modelo,
                           imp.marca AS imp_marca,
                           imp.modelo AS imp_modelo,
                           mon.marca AS mon_marca,
                           mon.modelo AS mon_modelo,
                           esc.marca AS esc_marca,
                           esc.modelo AS esc_modelo
                    FROM insumos i
                    LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock
                    LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
                    LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
                    LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
                    LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
                    LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
                    WHERE i.estado = 'Disponible' AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
                    ORDER BY i.nombre_insumo");
$insumos = $stmt->fetchAll();

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) throw new Exception('CSRF inválido');
        
        $idsInsumos = isset($_POST['id_insumo']) ? (array) $_POST['id_insumo'] : [];
        if (empty($idsInsumos)) throw new Exception('Debe seleccionar al menos un insumo para el pedido');

        $metodoEntrega = $_POST['metodo_entrega'] ?? 'Envío';
        $fechaEstimada = $_POST['fecha_estimada_entrega'] ?: null;
        $notasLogistica = $_POST['notas_entrega'] ?? '';

        // Verificar si hay alguna Notebook seleccionada para exigir DJ
        $hayNotebook = false;
        if (!empty($idsInsumos)) {
            $placeholders = implode(',', array_fill(0, count($idsInsumos), '?'));
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM insumos WHERE id_insumo IN ($placeholders) AND tipo_insumo = 'Notebook'");
            $stmtCheck->execute($idsInsumos);
            $hayNotebook = (int)$stmtCheck->fetchColumn() > 0;
        }

        $nombreArchivoDj = null;
        if ($hayNotebook) {
            if (empty($_FILES['declaracion_jurada']['name'])) {
                throw new Exception('Debe adjuntar la Declaración Jurada para preparar una Notebook.');
            }
            
            require_once '../../includes/validar_archivo.php';
            $validacion = validarArchivoDocumento($_FILES['declaracion_jurada']);
            if (!$validacion['valido']) {
                throw new Exception('DJ Inválida: ' . $validacion['error']);
            }
            
            $uploadDir = __DIR__ . '/../../uploads/documentos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $nombreArchivoDj = 'dj_' . time() . '_' . uniqid() . '.' . $validacion['extension'];
            if (!move_uploaded_file($_FILES['declaracion_jurada']['tmp_name'], $uploadDir . $nombreArchivoDj)) {
                throw new Exception('Error al guardar la declaración jurada.');
            }
        }

        $db->beginTransaction();

        $numeroRemito = generarNumeroRemito($db);
        
        // 1. Crear Remito (Alineado con esquema real y nueva_pasos.php)
        $stmtR = $db->prepare("INSERT INTO remitos (
            numero_remito, 
            id_sede, 
            id_area, 
            nombre_persona_asignada, 
            apellido_persona_asignada, 
            fecha_asignacion, 
            observaciones,
            nota_solicitud,
            declaracion_jurada
        ) VALUES (?,?,?,?,?,NOW(),?,?,?)");
        
        $stmtR->execute([
            $numeroRemito, 
            $pedido['id_sede'], 
            $pedido['id_area'], 
            $pedido['solicitante_nombre'], 
            $pedido['solicitante_apellido'], 
            $notasLogistica ?: null,
            ($pedido['pdf_nota'] ? 'pedidos/'.$pedido['pdf_nota'] : null),
            $nombreArchivoDj
        ]);
        $idRemito = (int) $db->lastInsertId();

        // 2. Detalle de remito e Insumos
        $stmtDet = $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)");
        $cantVarios = isset($_POST['cantidad_varios']) && is_array($_POST['cantidad_varios']) ? $_POST['cantidad_varios'] : [];
        $resumenInsumos = [];

        foreach ($idsInsumos as $idIns) {
            $row = $db->prepare("SELECT tipo_insumo, nombre_insumo, numero_serie, cantidad FROM insumos WHERE id_insumo=? FOR UPDATE");
            $row->execute([$idIns]);
            $ins = $row->fetch();
            if (!$ins) throw new Exception('Insumo no encontrado');

            $reps = 1;
            if ($ins['tipo_insumo'] === 'Varios') {
                $reps = isset($cantVarios[$idIns]) ? max(1, (int) $cantVarios[$idIns]) : 1;
            }
            $stmtDet->execute([$idRemito, $idIns, $reps]);
            $resumenInsumos[] = $ins['nombre_insumo'] . ($ins['numero_serie'] ? " (S/N: {$ins['numero_serie']})" : "");

            // Lógica de Stock
            if ($ins['tipo_insumo'] === 'Varios') {
                $stockOficina = (int) ($ins['cantidad_oficina'] ?? $ins['cantidad']);
                $stockDeposito = (int) ($ins['cantidad_deposito'] ?? 0);

                $descontarOficina = min($reps, $stockOficina); // Lo que se puede descontar de oficina
                $descontarDeposito = $reps - $descontarOficina; // El resto del depósito

                $nuevoOficina = $stockOficina - $descontarOficina;
                $nuevoDeposito = $stockDeposito - $descontarDeposito;
                $cantidadTotal = $nuevoOficina + $nuevoDeposito;

                // Si hay stock remanente (en oficina o depósito), mantener disponible; si no, asignado
                $estado = ($cantidadTotal > 0) ? 'Disponible' : 'Asignado';

                if ($cantidadTotal > 0) {
                    $db->prepare("UPDATE insumos SET cantidad=?, cantidad_oficina=?, cantidad_deposito=?, estado=?, id_sede_actual=?, id_area_asignacion_actual=?, id_punto_stock_actual=id_punto_stock_actual WHERE id_insumo=?")
                       ->execute([$cantidadTotal, $nuevoOficina, $nuevoDeposito, $estado, $pedido['id_sede'], $pedido['id_area'], $idIns]);
                } else {
                    // Si se agotó todo, limpiar punto de stock
                    $db->prepare("UPDATE insumos SET cantidad=?, cantidad_oficina=?, cantidad_deposito=?, estado=?, id_sede_actual=?, id_area_asignacion_actual=?, id_punto_stock_actual=NULL WHERE id_insumo=?")
                       ->execute([$cantidadTotal, $nuevoOficina, $nuevoDeposito, $estado, $pedido['id_sede'], $pedido['id_area'], $idIns]);
                }
            } else {
                $db->prepare("UPDATE insumos SET estado='Asignado', id_sede_actual=?, id_area_asignacion_actual=?, id_punto_stock_actual=NULL WHERE id_insumo=?")
                   ->execute([$pedido['id_sede'], $pedido['id_area'], $idIns]);
            }
        }

        // 3. Actualizar Pedido
        $insRel = implode(' | ', $resumenInsumos);
        $stmtP = $db->prepare("UPDATE pedidos SET 
            estado = 'Preparado', 
            id_remito = ?, 
            insumo_relacionado = ?, 
            metodo_entrega = ?, 
            fecha_estimada_entrega = ?, 
            notas_entrega = ?, 
            estado_entrega = 'Preparado'
            WHERE id_pedido = ?");
        
        $stmtP->execute([
            $idRemito, 
            $insRel, 
            $metodoEntrega, 
            $fechaEstimada, 
            $notasLogistica, 
            $idPedido
        ]);

        $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Preparación', 'Pedido preparado, se generó Remito #$numeroRemito')")
           ->execute([$idPedido, obtenerUsuarioId()]);

        $db->commit();
        $_SESSION['mensaje'] = "Pedido preparado correctamente. Remito #$numeroRemito generado.";
        $_SESSION['tipo_mensaje'] = 'success';
        
        if (!empty($_POST['imprimir_remito'])) {
            header('Location: ' . app_base_url() . '/pages/pedidos/listar.php?imprimir=' . urlencode($numeroRemito));
        } else {
            header('Location: listar.php');
        }
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-box-open me-2"></i>Preparar Pedido de Insumos</h4>
            <a href="listar.php" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-2"></i>Volver</a>
        </div>
    </div>
</div>

<!-- Stepper visual (Estilo exacto de nueva_pasos.php) -->
<div class="stepper mb-4">
    <div class="step step-1 active"><span class="circle">1</span><span>Selección de Insumos</span></div>
    <div class="divider"></div>
    <div class="step step-2 text-muted"><span class="circle">2</span><span>Logística y Confirmación</span></div>
</div>

<div id="preparar-pasos">
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <form method="POST" id="formPreparar" class="needs-validation" novalidate enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                
                <!-- Paso 1: Selección de Insumos -->
                <div id="section-insumos">
                    <div class="row justify-content-center pt-4 px-3 px-md-0">
                        <div class="col-lg-11 col-xl-10">
                            
                            <!-- Información del Pedido (Banner Premium) -->
                            <div class="card border mb-4 shadow-none">
                                <div class="card-body p-3 px-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; background-color: var(--primary-color);">
                                                    <i class="fas fa-info-circle" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold text-dark">Detalles de la Solicitud #<?php echo $idPedido; ?></h6>
                                            </div>
                                            <?php 
                                                $prioridadColor = 'bg-secondary';
                                                if($pedido['prioridad'] == 'Media') $prioridadColor = 'bg-success opacity-75';
                                                elseif($pedido['prioridad'] == 'Alta') $prioridadColor = 'bg-danger';
                                            ?>
                                            <p class="mb-1 small"><strong>Solicitante:</strong> <?php echo htmlspecialchars($pedido['solicitante_nombre'] . ' ' . $pedido['solicitante_apellido']); ?> <span class="badge <?php echo $prioridadColor; ?> ms-1">Prioridad <?php echo $pedido['prioridad']; ?></span></p>
                                            <p class="mb-0 small"><strong>Destino:</strong> <?php echo htmlspecialchars($pedido['nombre_sede'] . ' - ' . $pedido['nombre_localidad']); ?><?php echo $pedido['nombre_area'] ? ' (' . htmlspecialchars($pedido['nombre_area']) . ')' : ''; ?></p>
                                        </div>
                                        <div class="col-md-5 border-start ps-4">
                                            <label class="small fw-bold text-muted text-uppercase mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Necesidad / Motivo</label>
                                            <div class="small text-dark lh-sm" style="font-style: italic;">
                                                "<?php echo nl2br(htmlspecialchars($pedido['descripcion'])); ?>"
                                            </div>
                                            <?php if ($pedido['pdf_nota']): ?>
                                                <div class="mt-2 text-end">
                                                    <a href="<?php echo app_base_url(); ?>/uploads/pedidos/<?php echo $pedido['pdf_nota']; ?>" target="_blank" class="btn btn-sm btn-outline-danger border-opacity-25 shadow-none ms-2">
                                                        <i class="fas fa-file-pdf me-1"></i>DESCARGAR NOTA
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros estilo nueva_pasos.php -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label mb-1">Buscar</label>
                                            <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID físico...">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-1">Tipo de Insumo</label>
                                            <select class="form-select" id="filtro_tipo">
                                                <option value="">Todos los tipos</option>
                                                <option value="Varios">Varios</option>
                                                <option value="PC Escritorio">PC Escritorio</option>
                                                <option value="Notebook">Notebook</option>
                                                <option value="Impresora">Impresora</option>
                                                <option value="Monitor">Monitor</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()" title="Limpiar filtros"><i class="fas fa-eraser"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold px-1"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary" id="contadorSeleccion">0 seleccionados</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarFiltrados()" title="Seleccionar filtrados"><i class="fas fa-check-double"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deseleccionarTodos()" title="Deseleccionar todo"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <!-- Según feedback: "la tabla no lleva header" -->
                                        <table class="table table-flat table-hover align-middle mb-0" id="tablaInsumos">
                                            <tbody>
                                                <?php foreach ($insumos as $ins): 
                                                    $tipo = (string)$ins['tipo_insumo'];
                                                    $name = (string)$ins['nombre_insumo'];
                                                    
                                                    // Determinar nombre descriptivo según tipo
                                                    if ($tipo === 'Notebook') {
                                                        $marcaModelo = trim(($ins['nb_marca'] ?? '') . ' ' . ($ins['nb_modelo'] ?? ''));
                                                        if (!empty($marcaModelo)) $name = $marcaModelo;
                                                    } elseif ($tipo === 'Impresora') {
                                                        $marcaModelo = trim(($ins['imp_marca'] ?? '') . ' ' . ($ins['imp_modelo'] ?? ''));
                                                        if (!empty($marcaModelo)) $name = $marcaModelo;
                                                    } elseif ($tipo === 'Monitor') {
                                                        $marcaModelo = trim(($ins['mon_marca'] ?? '') . ' ' . ($ins['mon_modelo'] ?? ''));
                                                        if (!empty($marcaModelo)) $name = $marcaModelo;
                                                    } elseif ($tipo === 'Escaner') {
                                                        $marcaModelo = trim(($ins['esc_marca'] ?? '') . ' ' . ($ins['esc_modelo'] ?? ''));
                                                        if (!empty($marcaModelo)) $name = $marcaModelo;
                                                    } elseif ($tipo === 'PC Escritorio' || $tipo === 'PC Completa') {
                                                        // Para PCs, preferimos el nombre de insumo, pero podemos añadir el SO si existe
                                                        if (!empty($ins['pc_sist_op'])) $name .= ' (' . $ins['pc_sist_op'] . ')';
                                                    }
                                                    
                                                    $filterText = mb_strtolower($name.' '.$tipo.' '.$ins['numero_serie'].' '.$ins['id_fisico']);
                                                ?>
                                                <tr class="fila-insumo" data-tipo="<?php echo htmlspecialchars($tipo); ?>" data-texto="<?php echo htmlspecialchars($filterText); ?>">
                                                    <td class="ps-3 border-0">
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($name); ?></div>
                                                        <div class="text-muted small">
                                                            <?php if($ins['id_fisico']) echo "ID: ".htmlspecialchars($ins['id_fisico'])." | "; ?>
                                                            <?php if($ins['numero_serie']) echo "S/N: ".htmlspecialchars($ins['numero_serie']); ?>
                                                        </div>
                                                    </td>
                                                    <td class="border-0"><span class="badge bg-info"><?php echo $tipo; ?></span></td>
                                                    <td class="text-center fw-bold border-0"><?php echo ($tipo === 'Varios') ? (int)$ins['cantidad'] : 1; ?></td>
                                                    <td class="border-0"><?php echo $ins['punto_stock'] ? htmlspecialchars($ins['punto_stock']) : '<span class="text-muted">Sin punto</span>'; ?></td>
                                                    <td class="text-end pe-3 border-0">
                                                        <div class="d-flex gap-2 justify-content-end align-items-center">
                                                            <input type="hidden" name="id_insumo[]" value="<?php echo $ins['id_insumo']; ?>" class="hidden-insumo-input" data-tipo="<?php echo $tipo; ?>" data-max="<?php echo ($tipo === 'Varios') ? (int)$ins['cantidad'] : 1; ?>" disabled>
                                                            
                                                            <?php if ($tipo === 'Varios' && $ins['cantidad'] > 1): ?>
                                                                <div class="cantidad-input d-flex align-items-center gap-2 opacity-50" style="visibility: hidden;">
                                                                    <label class="small text-muted mb-0">Cant.</label>
                                                                    <input type="number" class="form-control form-control-sm text-center" name="cantidad_varios[<?php echo $ins['id_insumo']; ?>]" min="1" max="<?php echo (int)$ins['cantidad']; ?>" value="1" style="width:70px;" disabled>
                                                                </div>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar px-3" onclick="toggleSeleccionInsumo(<?php echo $ins['id_insumo']; ?>)" data-insumo-id="<?php echo $ins['id_insumo']; ?>">
                                                                <i class="fas fa-plus me-1"></i> Seleccionar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Paginación -->
                            <ul class="pagination pagination-sm justify-content-center mt-3" id="paginationInsumos"></ul>

                            <!-- Declaración Jurada (Movido al Paso 1) -->
                            <div id="div_declaracion_jurada" style="display: none;" class="mt-4">
                                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
                                    <i class="fas fa-file-contract fa-2x me-3 opacity-75"></i>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-bold mb-1">Declaración Jurada de Notebook *</label>
                                        <p class="small mb-2">Se ha seleccionado una Notebook. Debe adjuntar la Declaración Jurada firmada.</p>
                                        <input type="file" class="form-control" name="declaracion_jurada" id="declaracion_jurada" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <div class="invalid-feedback">Debe adjuntar la Declaración Jurada.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end border-top py-4 mt-2">
                                <button type="button" class="btn btn-primary shadow-sm px-4" id="next-to-logistica">
                                    Siguiente paso <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Logística -->
                <div id="section-logistica" class="d-none">
                    <div class="row justify-content-center pt-4 px-3 px-md-0">
                        <div class="col-lg-10 col-xl-8">

                            <h6 class="section-title mb-4">Datos de Logística</h6>
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Método de Entrega *</label>
                                    <select class="form-select" name="metodo_entrega">
                                        <option value="Envío">Envío por Logística</option>
                                        <option value="Retiro">Retiro en Oficina</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Fecha Estimada *</label>
                                    <input type="date" class="form-control" name="fecha_estimada_entrega" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Notas de Entrega</label>
                                    <textarea class="form-control" name="notas_entrega" rows="4" placeholder="Observaciones para el transporte..."></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 border-top py-4">
                                <button type="button" class="btn btn-secondary px-3" id="back-to-insumos">
                                    <i class="fas fa-arrow-left me-2"></i> Volver
                                </button>
                                <button type="button" class="btn btn-primary px-4 shadow-sm" onclick="mostrarModalConfirmacion()">
                                    <i class="fas fa-check-circle me-2"></i>Revisar y Confirmar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Confirmar Preparación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>Detalles del Pedido</h6>
                        <p class="mb-1"><strong>Solicitante:</strong> <?php echo htmlspecialchars($pedido['solicitante_nombre'].' '.$pedido['solicitante_apellido']); ?></p>
                        <p class="mb-1"><strong>Destino:</strong> <span><?php echo htmlspecialchars($pedido['nombre_sede'] . ' - ' . $pedido['nombre_localidad']); ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="fas fa-truck me-2"></i>Logística</h6>
                        <p class="mb-1"><strong>Método:</strong> <span id="m_metodo"></span></p>
                        <p class="mb-1"><strong>Fecha:</strong> <span id="m_fecha"></span></p>
                    </div>
                </div>
                <hr>
                <h6 class="text-primary mb-2"><i class="fas fa-boxes me-2"></i>Equipos Seleccionados</h6>
                <div id="m_resumen_insumos" class="table-responsive"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-3" onclick="confirmarPreparacion()"><i class="fas fa-check me-1"></i>Confirmar</button>
                <button type="button" class="btn btn-primary px-3" onclick="confirmarPreparacion(true)"><i class="fas fa-print me-1"></i>Confirmar e Imprimir</button>
            </div>
        </div>
    </div>
</div>

<style>
.bg-light { background-color: #f8f9fa !important; }
.btn-xs { padding: 0.1rem 0.4rem; font-size: 0.7rem; }
.fila-insumo.selected { background-color: rgba(90, 147, 103, 0.05); }
.d-none-filter { display: none !important; }
#tablaInsumos tbody tr td { border-bottom: 1px solid #f0f0f0; }
</style>

<script>
$(function() {
    let currentPage = 1;
    const rowsPerPage = 12;

    // Inicializar
    filtrarInsumos();

    // Navegación
    $('#next-to-logistica').on('click', function() {
        if ($('.hidden-insumo-input:not(:disabled)').length === 0) {
            showToast('Debe seleccionar al menos un insumo', 'warning');
            return;
        }

        // Validación de Declaración Jurada para Notebooks
        if (verificarNotebookSeleccionada() && !$('#declaracion_jurada').val()) {
            showToast('Debe adjuntar la Declaración Jurada para continuar con la Notebook', 'warning');
            $('#declaracion_jurada').addClass('is-invalid').focus();
            return;
        } else {
            $('#declaracion_jurada').removeClass('is-invalid');
        }

        showSection('logistica');
    });

    $('#back-to-insumos').on('click', function() { showSection('insumos'); });

    function showSection(s) {
        if(s === 'insumos') {
            $('#section-logistica').addClass('d-none');
            $('#section-insumos').removeClass('d-none');
            $('.stepper .step-1').addClass('active').removeClass('done');
            $('.stepper .step-2').removeClass('active').addClass('text-muted');
        } else {
            $('#section-insumos').addClass('d-none');
            $('#section-logistica').removeClass('d-none');
            $('.stepper .step-2').addClass('active').removeClass('text-muted');
            $('.stepper .step-1').addClass('done').removeClass('active');
        }
        window.scrollTo(0, 0);
    }

    // Modal
    window.mostrarModalConfirmacion = function() {
        $('#m_metodo').text($('select[name="metodo_entrega"] option:selected').text());
        const fecha = $('input[name="fecha_estimada_entrega"]').val();
        $('#m_fecha').text(fecha ? fecha.split('-').reverse().join('/') : '-');
        
        let html = '<table class="table table-sm table-bordered mb-0"><thead><tr class="table-light"><th>Item</th><th class="text-center">Cant.</th></tr></thead><tbody>';
        $('.hidden-insumo-input:not(:disabled)').each(function() {
            const $row = $(this).closest('tr');
            const nombre = $row.find('td:first-child .fw-bold').text();
            const tipo = $(this).data('tipo');
            let cant = 1;
            if (tipo === 'Varios') cant = $row.find('.cantidad-input input').val() || 1;
            html += `<tr><td>${nombre} <small class="text-muted">(${tipo})</small></td><td class="text-center fw-bold">${cant}</td></tr>`;
        });
        html += '</tbody></table>';
        
        $('#m_resumen_insumos').html(html);
        
        // Mostrar aviso de DJ en el modal si corresponde
        if (verificarNotebookSeleccionada()) {
            if (!$('#declaracion_jurada').val()) {
                showToast('Debe adjuntar la Declaración Jurada para la Notebook', 'error');
                return;
            }
        }

        $('#modalConfirmacion').modal('show');
    };

    window.confirmarPreparacion = function(imprimir = false) {
        const btn = $('#modalConfirmacion .btn-success, #modalConfirmacion .btn-primary');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');
        
        if (imprimir) {
            // TRICK: Abrir ventana nombrada ANTES del submit para saltar el bloqueador de popups
            try { window.open('', 'remitoPrint'); } catch (e) { }
            $('<input>').attr({type: 'hidden', name: 'imprimir_remito', value: '1'}).appendTo('#formPreparar');
        }
        
        $('#formPreparar').submit();
    }
;

    // Lógica Filtros y Paginación idéntica a nueva_pasos.php
    $('#filtro_busqueda, #filtro_tipo').on('input change', function() {
        currentPage = 1;
        filtrarInsumos();
    });

    function filtrarInsumos() {
        const q = $('#filtro_busqueda').val().toLowerCase();
        const tipo = $('#filtro_tipo').val().toLowerCase();
        
        $('.fila-insumo').each(function() {
            const $f = $(this);
            const text = $f.data('texto');
            const t = $f.data('tipo').toLowerCase();
            const selected = !$f.find('.hidden-insumo-input').prop('disabled');
            let matches = true;
            if (!selected) {
                if(q && !text.includes(q)) matches = false;
                if(tipo && t !== tipo) matches = false;
            }
            $f.toggleClass('d-none-filter', !matches);
        });
        reorderSelectedFirst();
    }

    function reorderSelectedFirst() {
        const $tbody = $('#tablaInsumos').find('tbody');
        const $rows = $tbody.find('tr.fila-insumo');
        
        // Obtener seleccionados y no seleccionados
        const $selected = $rows.filter(function () { 
            return !$(this).find('.hidden-insumo-input').prop('disabled'); 
        });
        const $others = $rows.not($selected);
        
        // Re-insertar en orden: primero los seleccionados (manteniendo su orden) y luego el resto
        $tbody.empty().append($selected).append($others);
        
        renderPaginacion();
    }

    function renderPaginacion() {
        const $allValidRows = $('.fila-insumo:not(.d-none-filter)');
        const $tbody = $('#tablaInsumos tbody');
        $tbody.find('tr.no-results').remove();
        
        if ($allValidRows.length === 0) {
            $tbody.append('<tr class="no-results"><td colspan="5" class="text-center text-muted py-4">Sin resultados</td></tr>');
            $('#paginationInsumos').empty();
            return;
        }

        const $selectedRows = $allValidRows.filter(function() { return !$(this).find('.hidden-insumo-input').prop('disabled'); });
        const $unselectedRows = $allValidRows.not($selectedRows);

        $selectedRows.show();

        const totalPages = Math.ceil($unselectedRows.length / rowsPerPage);
        $unselectedRows.each(function(index) {
            if (index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage) $(this).show();
            else $(this).hide();
        });

        let pageHtml = '';
        if (totalPages > 1) {
            pageHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1}); return false;">&laquo;</a></li>`;
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    pageHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${i}); return false;">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    pageHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            pageHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1}); return false;">&raquo;</a></li>`;
        }
        $('#paginationInsumos').html(pageHtml);
    }

    window.cambiarPagina = function(p) { currentPage = p; renderPaginacion(); };

    window.toggleSeleccionInsumo = function(id) {
        const $input = $(`.hidden-insumo-input[value="${id}"]`);
        const $btn = $(`.btn-seleccionar[data-insumo-id="${id}"]`);
        const $row = $btn.closest('tr');
        const $cant = $row.find('.cantidad-input');
        const isSelected = !$input.prop('disabled');
        
        if (isSelected) {
            $input.prop('disabled', true);
            $btn.removeClass('btn-primary').addClass('btn-outline-primary').html('<i class="fas fa-plus me-1"></i> Seleccionar');
            $row.removeClass('selected');
            if($cant.length) { 
                $cant.css('visibility', 'hidden').removeClass('opacity-100').addClass('opacity-50');
                $cant.find('input').prop('disabled', true); 
            }
        } else {
            $input.prop('disabled', false);
            $btn.removeClass('btn-outline-primary').addClass('btn-primary').html('<i class="fas fa-minus me-1"></i> Deseleccionar');
            $row.addClass('selected');
            if($cant.length) { 
                $cant.css('visibility', 'visible').removeClass('opacity-50').addClass('opacity-100');
                $cant.find('input').prop('disabled', false); 
            }
        }
        
        actualizarContador();
        
        // Diferir el reordenamiento para evitar conflictos con el evento click (fix 2-clicks)
        setTimeout(() => {
            reorderSelectedFirst();
        }, 50);
    };

    window.seleccionarFiltrados = function() {
        $('.fila-insumo:visible').each(function() {
            const id = $(this).find('.btn-seleccionar').data('insumo-id');
            if ($(this).find('.hidden-insumo-input').prop('disabled')) toggleSeleccionInsumo(id);
        });
    }

    window.deseleccionarTodos = function() {
        $('.fila-insumo.selected').each(function() {
            const id = $(this).find('.btn-seleccionar').data('insumo-id');
            toggleSeleccionInsumo(id);
        });
    }

    function actualizarContador() {
        const cant = $('.hidden-insumo-input:not(:disabled)').length;
        $('#contadorSeleccion').text(`${cant} seleccionados`);
        actualizarDJVisibility();
    }

    function verificarNotebookSeleccionada() {
        let hayNotebook = false;
        $('.hidden-insumo-input:not(:disabled)').each(function() {
            if ($(this).data('tipo') === 'Notebook') hayNotebook = true;
        });
        return hayNotebook;
    }

    function actualizarDJVisibility() {
        if (verificarNotebookSeleccionada()) {
            $('#div_declaracion_jurada').fadeIn();
            $('#declaracion_jurada').prop('required', true);
        } else {
            $('#div_declaracion_jurada').fadeOut();
            $('#declaracion_jurada').prop('required', false).val('');
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
