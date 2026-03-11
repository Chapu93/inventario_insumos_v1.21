<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'editar');

$db = conectarDB();

// Detectar si es edición
$esEdicion = false;
$idIngreso = null;
$ingresoData = null;
$insumosExistentes = [];

if (isset($_GET['id']) && $_GET['id']) {
    $idIngreso = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM ingresos WHERE id_ingreso=?');
    $stmt->execute([$idIngreso]);
    $ingresoData = $stmt->fetch();
    
    if ($ingresoData) {
        $esEdicion = true;
        $ins = $db->prepare('SELECT id_insumo FROM insumos WHERE id_ingreso=?');
        $ins->execute([$idIngreso]);
        $insumosExistentes = array_column($ins->fetchAll(), 'id_insumo');
    }
}

$tipoIngreso = $ingresoData['tipo_ingreso'] ?? ($_GET['tipo'] ?? 'otros');
$tipoIngreso = is_string($tipoIngreso) ? strtolower($tipoIngreso) : 'otros';

$uiDefaults = [
    'ref_label' => 'Nro. Exp./Notas/Referencia',
    'ref_placeholder' => 'Ej: REF-2025-001',
    'ref_help' => 'Ingrese el número correspondiente',
    'invalid_msg' => 'El número es obligatorio',
    'desc_title' => 'Descripción del ingreso',
    'desc_placeholder' => 'Descripción detallada del ingreso...',
    'summary_label' => 'Nro. Exp./Notas/Referencia',
    'toast_msg' => 'Complete el número correspondiente'
];

$uiByTipo = [
    'licitacion' => [
        'ref_label' => 'Nro. de Expediente',
        'ref_placeholder' => 'Ej: EXP-2025-001',
        'ref_help' => 'Ingrese el número de expediente de licitación',
        'invalid_msg' => 'El número de expediente es obligatorio',
        'desc_title' => 'Descripción de la licitación',
        'desc_placeholder' => 'Descripción detallada de la licitación...',
        'summary_label' => 'Nro. de Expediente',
        'toast_msg' => 'Complete el número de expediente'
    ],
    'compra_directa' => [
        'ref_label' => 'Nro. de Expediente',
        'ref_placeholder' => 'Ej: EXP-CD-2025-015',
        'ref_help' => 'Ingrese el número de expediente de la compra directa',
        'invalid_msg' => 'El número de expediente es obligatorio',
        'desc_title' => 'Descripción de la compra directa',
        'desc_placeholder' => 'Detalle de la compra directa...',
        'summary_label' => 'Nro. de Expediente',
        'toast_msg' => 'Complete el número de expediente'
    ],
    'fondos' => [
        'ref_label' => 'Nro. de Nota',
        'ref_placeholder' => 'Ej: NOTA-2025-015',
        'ref_help' => 'Ingrese el número de nota de fondos',
        'invalid_msg' => 'El número de nota es obligatorio',
        'desc_title' => 'Descripción de la nota',
        'desc_placeholder' => 'Detalle de la nota de fondos...',
        'summary_label' => 'Nro. de Nota',
        'toast_msg' => 'Complete el número de nota'
    ],
    'otros' => [
        'ref_label' => 'Nro. de Referencia',
        'ref_placeholder' => 'Ej: REF-2025-XYZ',
        'ref_help' => 'Ingrese el número de referencia',
        'invalid_msg' => 'El número de referencia es obligatorio',
        'desc_title' => 'Descripción del ingreso',
        'desc_placeholder' => 'Descripción detallada del ingreso...',
        'summary_label' => 'Nro. de Referencia',
        'toast_msg' => 'Complete el número de referencia'
    ]
];

$uiConfig = $uiByTipo[$tipoIngreso] ?? $uiDefaults;

// Obtener todos los insumos disponibles (sin licitación asignada) y los de esta licitación si estamos editando
$sqlInsumos = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.subcategoria_varios, i.numero_serie, i.id_fisico, i.cantidad, 
               ps.nombre_punto AS punto_stock,
               pc.sist_op AS sistema_operativo
               FROM insumos i
               LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock
               LEFT JOIN pcs_completas pc ON i.id_insumo = pc.id_insumo
               WHERE (i.id_ingreso IS NULL" . ($esEdicion ? " OR i.id_ingreso = ?" : "") . ")
               AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
               ORDER BY i.nombre_insumo";

if ($esEdicion) {
    $stmt = $db->prepare($sqlInsumos);
    $stmt->execute([$idIngreso]);
} else {
    $stmt = $db->query($sqlInsumos);
}
$insumos = $stmt->fetchAll();

// POST para crear/actualizar la licitación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        
        $db->beginTransaction();
        
        $nroReferencia = trim($_POST['nro_referencia'] ?? '');
        $fechaFin = !empty($_POST['fecha_finalizacion']) ? $_POST['fecha_finalizacion'] : null;
        $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
        
        Logger::debug("Guardar ingreso desde editar", ['fecha_recibida' => $fechaFin ?? 'NULL']);
        $insumos = isset($_POST['id_insumo']) && is_array($_POST['id_insumo']) ? array_map('intval', $_POST['id_insumo']) : [];
        $cantidades = isset($_POST['cantidad_varios']) && is_array($_POST['cantidad_varios']) ? $_POST['cantidad_varios'] : [];
        
        if ($nroReferencia === '') {
            throw new Exception('El número de referencia es obligatorio');
        }
        
        if (empty($insumos)) {
            throw new Exception('Debe seleccionar al menos un insumo');
        }
        
        if ($esEdicion && $idIngreso) {
            // Actualizar ingreso existente
            $stmt = $db->prepare('UPDATE ingresos SET nro_referencia=?, descripcion=?, fecha_finalizacion=? WHERE id_ingreso=?');
            $stmt->execute([$nroReferencia, $descripcion, $fechaFin, $idIngreso]);
            $id = $idIngreso;
        } else {
            // Crear nuevo ingreso
            $stmt = $db->prepare('INSERT INTO ingresos (nro_referencia, descripcion, fecha_finalizacion) VALUES (?,?,?)');
            $stmt->execute([$nroReferencia, $descripcion, $fechaFin]);
            $id = (int)$db->lastInsertId();
        }
        
        // Desasignar insumos actuales de esta licitación
        $db->prepare('UPDATE insumos SET id_ingreso=NULL WHERE id_ingreso=?')->execute([$id]);
        
        // Asignar los nuevos insumos seleccionados
        if (!empty($insumos)) {
            $in = implode(',', array_fill(0, count($insumos), '?'));
            $params = array_merge([$id], $insumos, [$id]);
            $db->prepare("UPDATE insumos SET id_ingreso=? WHERE id_insumo IN ($in) AND (id_ingreso IS NULL OR id_ingreso=?)")
               ->execute($params);
        }
        
        // Procesar archivos adjuntos
        $usuarioId = obtenerUsuarioId();
        $uploadDir = UPLOAD_BASE_DIR . 'ingresos/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Procesar remito
        if (!empty($_FILES['archivo_remito']['name'])) {
            $resultado = procesarArchivoAdjunto($_FILES['archivo_remito'], $id, 'remito', $uploadDir, $db, $usuarioId);
            if (!$resultado['success']) {
                Logger::warning("Error al cargar remito", ['error' => $resultado['error']]);
            }
        }
        
        // Procesar documentación
        if (!empty($_FILES['archivo_documentacion']['name'])) {
            $resultado = procesarArchivoAdjunto($_FILES['archivo_documentacion'], $id, 'documentacion', $uploadDir, $db, $usuarioId);
            if (!$resultado['success']) {
                Logger::warning("Error al cargar documentación", ['error' => $resultado['error']]);
            }
        }
        
        $db->commit();
        
        // Limpiar localStorage
        echo '<script>try { localStorage.removeItem("ingreso_paso1"); localStorage.removeItem("ingreso_seleccionados"); } catch(e) {}</script>';
        
        $_SESSION['mensaje'] = $esEdicion ? 'Ingreso actualizada correctamente' : 'Ingreso creada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/insumos/ingresos_listar.php');
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-file-signature me-2"></i>
                <?php echo $esEdicion ? 'Editar Ingreso' : 'Nueva Ingreso'; ?>
            </h4>
            <a href="ingresos_listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
</div>

<!-- Stepper visual (3 pasos) -->
<div class="stepper">
    <div class="step step-1 active"><span class="circle">1</span><span>Datos</span></div>
    <div class="divider"></div>
    <div class="step step-2"><span class="circle">2</span><span>Insumos</span></div>
    <div class="divider"></div>
    <div class="step step-3"><span class="circle">3</span><span>Confirmación</span></div>
</div>

<div id="ingreso-pasos">
    <div class="card">
        <div class="card-body">
            <form method="POST" id="formPasos" class="needs-validation" novalidate enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <?php if ($esEdicion && $idIngreso): ?>
                    <input type="hidden" name="id_ingreso" value="<?php echo $idIngreso; ?>">
                <?php endif; ?>

                <!-- Paso 1: Datos de la Ingreso -->
                <div id="paso1">
                    <div class="row justify-content-center">
                        <div class="col-lg-10 col-xl-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3 section-title">Información de la Ingreso</h6>
                                    <div class="mb-3">
                                        <label for="nro_referencia" class="form-label" id="label_nro_referencia">
                                            <?php echo htmlspecialchars($uiConfig['ref_label']); ?> <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="nro_referencia" 
                                            name="nro_referencia" 
                                            required
                                            value="<?php echo $esEdicion && $ingresoData ? htmlspecialchars($ingresoData['nro_referencia']) : ''; ?>"
                                            placeholder="<?php echo htmlspecialchars($uiConfig['ref_placeholder']); ?>"
                                        >
                                        <small class="text-muted" id="help_nro_referencia"><?php echo htmlspecialchars($uiConfig['ref_help']); ?></small>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($uiConfig['invalid_msg']); ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_finalizacion" class="form-label">
                                            Fecha de Finalización
                                        </label>
                                        <input 
                                            type="date" 
                                            class="form-control" 
                                            id="fecha_finalizacion" 
                                            name="fecha_finalizacion"
                                            value="<?php echo $esEdicion && $ingresoData && $ingresoData['fecha_finalizacion'] ? $ingresoData['fecha_finalizacion'] : ''; ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-3 section-title" id="titulo_descripcion"><?php echo htmlspecialchars($uiConfig['desc_title']); ?></h6>
                                    <textarea 
                                        class="form-control" 
                                        id="descripcion" 
                                        name="descripcion" 
                                        rows="6"
                                        placeholder="<?php echo htmlspecialchars($uiConfig['desc_placeholder']); ?>"
                                    ><?php echo $esEdicion && $ingresoData && $ingresoData['descripcion'] ? htmlspecialchars($ingresoData['descripcion']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Documentos Adjuntos -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="mb-3 section-title">Documentos Adjuntos</h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editar_archivo_remito" class="form-label">
                                            <i class="fas fa-file-pdf me-1"></i>Remito
                                        </label>
                                        <input type="file" class="form-control" id="editar_archivo_remito" name="archivo_remito" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt">
                                        <small class="text-muted">PDF, imágenes o documentos (máx. 10MB)</small>
                                        <div id="documentos_remito_existentes" class="mt-2" style="display:none;">
                                            <small class="text-muted d-block mb-2">Documento actual:</small>
                                            <div id="lista_remito" class="list-group list-group-sm"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editar_archivo_documentacion" class="form-label">
                                            <i class="fas fa-file-alt me-1"></i>Documentación
                                        </label>
                                        <input type="file" class="form-control" id="editar_archivo_documentacion" name="archivo_documentacion" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.xls,.zip,.odt,.ods">
                                        <small class="text-muted">PDF, imágenes, documentos o archivos (máx. 10MB)</small>
                                        <div id="documentos_documentacion_existentes" class="mt-2" style="display:none;">
                                            <small class="text-muted d-block mb-2">Documentos actuales:</small>
                                            <div id="lista_documentacion" class="list-group list-group-sm"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2 justify-content-center">
                        <div class="col-lg-10 col-xl-8">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if ($esEdicion): ?>
                                <button type="button" class="btn btn-secondary" id="btnGuardarPDF">
                                    <i class="fas fa-file-pdf me-2"></i>Guardar PDF
                                </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-primary" id="btnSiguiente">
                                    <i class="fas fa-arrow-right me-2"></i>Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Selección de Insumos -->
                <div id="paso2" style="display:none;">
                    <div class="row justify-content-center">
                        <div class="col-lg-11">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Buscar</label>
                                            <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID...">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tipo de Insumo</label>
                                            <select class="form-select form-select-sm" id="filtro_tipo" style="min-width: 280px;">
                                                <option value="">Todos los tipos</option>
                                                <option value="Varios">Varios</option>
                                                <option value="PC Escritorio">PC Escritorio</option>
                                                <option value="Notebook">Notebook</option>
                                                <option value="Impresora">Impresora</option>
                                                <option value="Monitor">Monitor</option>
                                                <option value="Escaner">Escaner</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros" title="Limpiar filtros" aria-label="Limpiar filtros">
                                                <i class="fas fa-eraser" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn btn-success" id="btnNuevoInsumo" onclick="irACrearInsumo()">
                                                <i class="fas fa-plus me-1"></i>Nuevo Insumo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary" id="contadorSeleccion">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarFiltrados()" title="Seleccionar filtrados" aria-label="Seleccionar filtrados">
                                            <i class="fas fa-check-double" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deseleccionarTodos()" title="Deseleccionar todo" aria-label="Deseleccionar todo">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-flat table-hover" id="tablaInsumos">
                                            <tbody>
                                                <?php foreach ($insumos as $ins): 
                                                    $yaSeleccionado = in_array($ins['id_insumo'], $insumosExistentes);
                                                ?>
                                                <tr class="fila-insumo <?php echo $yaSeleccionado ? 'fila-seleccionada' : ''; ?>" 
                                                    data-id="<?php echo $ins['id_insumo']; ?>"
                                                    data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" 
                                                    data-nombre="<?php echo htmlspecialchars($ins['nombre_insumo']); ?>"
                                                    data-texto="<?php echo strtolower(htmlspecialchars($ins['nombre_insumo'] . ' ' . ($ins['numero_serie'] ?: '') . ' ' . ($ins['id_fisico'] ?: ''))); ?>">
                                                    <td>
                                                        <div>
                                                            <?php if ($ins['tipo_insumo'] === 'PC Escritorio' && $ins['sistema_operativo']): ?>
                                                                <strong><?php echo htmlspecialchars($ins['sistema_operativo']); ?></strong>
                                                            <?php else: ?>
                                                                <strong><?php echo htmlspecialchars($ins['nombre_insumo']); ?></strong>
                                                                <?php if ($ins['numero_serie']): ?>
                                                                    <div class="text-muted small">S/N: <?php echo htmlspecialchars($ins['numero_serie']); ?></div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <?php if ($ins['id_fisico']): ?>
                                                                <div class="text-muted small">ID: <?php echo htmlspecialchars($ins['id_fisico']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php 
                                                            if ($ins['tipo_insumo'] === 'Varios' && !empty($ins['subcategoria_varios'])) {
                                                                echo htmlspecialchars($ins['subcategoria_varios']);
                                                            } else {
                                                                echo htmlspecialchars($ins['tipo_insumo']);
                                                            }
                                                        ?></span>
                                                    </td>
                                                    <td><?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?></td>
                                                    <td><?php echo $ins['punto_stock'] ? htmlspecialchars($ins['punto_stock']) : '<span class="text-muted">Sin punto</span>'; ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                                                            <input type="hidden" name="id_insumo[]" value="<?php echo $ins['id_insumo']; ?>" class="hidden-insumo-input" data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" data-max="<?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?>" <?php echo $yaSeleccionado ? '' : 'disabled'; ?> style="display:none;">
                                                            <?php if ($ins['tipo_insumo'] === 'Varios' && $ins['cantidad'] > 1): ?>
                                                            <div class="cantidad-input" style="<?php echo $yaSeleccionado ? '' : 'display:none;'; ?>">
                                                                <?php $cid = 'cantidad_varios_' . (int)$ins['id_insumo']; ?>
                                                                <label for="<?php echo $cid; ?>" class="small text-muted mb-0">Cant.</label>
                                                                <input id="<?php echo $cid; ?>" type="number" class="form-control form-control-sm" name="cantidad_varios[<?php echo $ins['id_insumo']; ?>]" min="1" max="<?php echo (int)$ins['cantidad']; ?>" value="1" style="width:84px;">
                                                            </div>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm <?php echo $yaSeleccionado ? 'btn-primary' : 'btn-outline-primary'; ?> btn-seleccionar" data-insumo-id="<?php echo $ins['id_insumo']; ?>" onclick="toggleSeleccionInsumo(<?php echo $ins['id_insumo']; ?>, undefined)" title="<?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?>" aria-label="<?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?> insumo <?php echo htmlspecialchars($ins['nombre_insumo']); ?>">
                                                                <i class="fas <?php echo $yaSeleccionado ? 'fa-minus' : 'fa-plus'; ?>" aria-hidden="true"></i>
                                                                <span class="d-none d-sm-inline"> <?php echo $yaSeleccionado ? 'Deseleccionar' : 'Seleccionar'; ?></span>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-secondary" id="btnVolver">
                                            <i class="fas fa-arrow-left me-1"></i>Volver
                                        </button>
                                        <button type="button" class="btn btn-primary" id="btnSiguiente3">
                                            <i class="fas fa-arrow-right me-1"></i>Siguiente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Confirmación -->
                <div id="paso3" style="display:none;">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle me-2"></i>Confirmar Ingreso
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-2"><i class="fas fa-file-signature me-2"></i>Datos de la Ingreso</h6>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($uiConfig['summary_label']); ?>:</strong> <span id="m_codigo"></span></p>
                                            <p class="mb-1"><strong>Fecha Finalización:</strong> <span id="m_fecha"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-2"><i class="fas fa-comment me-2"></i><?php echo htmlspecialchars($uiConfig['desc_title']); ?></h6>
                                            <div class="alert alert-light mb-0" id="m_descripcion" style="white-space: pre-wrap; max-height: 100px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6 class="text-primary mb-2"><i class="fas fa-boxes me-2"></i>Insumos Seleccionados</h6>
                                    <div id="m_insumos" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3 justify-content-center">
                        <div class="col-lg-10">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" id="btnVolver3">
                                    <i class="fas fa-arrow-left me-1"></i>Volver
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i>Confirmar Ingreso
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
const BASE = '<?php echo app_base_url(); ?>';
const ES_EDICION = <?php echo $esEdicion ? 'true' : 'false'; ?>;
const ES_ADMIN = <?php echo tieneRol([1, 2]) ? 'true' : 'false'; ?>; // Solo Admin/Superadmin pueden eliminar documentos
const TOAST_MSG_REFERENCIA = <?php echo json_encode($uiConfig['toast_msg'], JSON_UNESCAPED_UNICODE); ?>;

let pasoActual = 1;

// Guardar datos del paso 1 y seleccionados
function guardarEstado() {
    try {
        console.log('=== Guardando estado ===');
        
        // Guardar datos del Paso 1
        const datos = {
            nro_referencia: document.getElementById('nro_referencia').value,
            fecha_finalizacion: document.getElementById('fecha_finalizacion').value,
            descripcion: document.getElementById('descripcion').value
        };
        localStorage.setItem('ingreso_paso1', JSON.stringify(datos));
        console.log('Paso 1 guardado:', datos);
        
        // Guardar seleccionados con cantidades
        const seleccionados = {};
        const insumosSeleccionados = $('.hidden-insumo-input:not([disabled])');
        console.log('Insumos seleccionados encontrados:', insumosSeleccionados.length);
        
        insumosSeleccionados.each(function() {
            const id = $(this).val();
            const $fila = $(this).closest('tr');
            const inputCantidad = $fila.find('input[type="number"]');
            
            if (inputCantidad.length) {
                const cantidad = parseInt(inputCantidad.val()) || 1;
                seleccionados[id] = cantidad;
                console.log(`  - Insumo ${id} (Varios): cantidad ${cantidad}`);
            } else {
                seleccionados[id] = 1;
                console.log(`  - Insumo ${id} (Normal): cantidad 1`);
            }
        });
        
        localStorage.setItem('ingreso_seleccionados', JSON.stringify(seleccionados));
        console.log('Seleccionados guardados:', seleccionados);
        console.log('Total insumos guardados:', Object.keys(seleccionados).length);
        
    } catch (e) {
        console.error('Error guardando estado:', e);
        alert('Error al guardar el estado. Por favor, intente nuevamente.');
    }
}

// Restaurar datos del paso 1
function restaurarDatosPaso1() {
    try {
        const datos = localStorage.getItem('ingreso_paso1');
        if (datos) {
            const obj = JSON.parse(datos);
            if (obj.nro_referencia) document.getElementById('nro_referencia').value = obj.nro_referencia;
            if (obj.fecha_finalizacion) document.getElementById('fecha_finalizacion').value = obj.fecha_finalizacion;
            if (obj.descripcion) document.getElementById('descripcion').value = obj.descripcion;
        }
    } catch (e) {
        console.error('Error restaurando datos:', e);
    }
}

// Restaurar seleccionados
function restaurarSeleccionados(nuevoId) {
    try {
        console.log('Iniciando restauración de seleccionados...');
        const datos = localStorage.getItem('ingreso_seleccionados');
        console.log('Datos en localStorage:', datos);
        
        if (datos) {
            const seleccionados = JSON.parse(datos);
            console.log('Seleccionados a restaurar:', seleccionados);
            
            Object.keys(seleccionados).forEach(id => {
                const fila = $(`.fila-insumo[data-id="${id}"]`);
                console.log(`Buscando fila con id ${id}:`, fila.length > 0 ? 'Encontrada' : 'NO encontrada');
                
                if (fila.length) {
                    const input = fila.find('.hidden-insumo-input');
                    const btn = fila.find('.btn-seleccionar');
                    const cantInput = fila.find('.cantidad-input');
                    const inputNumero = cantInput.find('input[type="number"]');
                    const cantidadGuardada = parseInt(seleccionados[id]) || 1;
                    
                    // Seleccionar
                    input.prop('disabled', false);
                    btn.removeClass('btn-outline-primary').addClass('btn-primary');
                    btn.find('i').removeClass('fa-plus').addClass('fa-minus');
                    btn.find('span').text(' Deseleccionar');
                    btn.attr('title', 'Deseleccionar');
                    fila.addClass('fila-seleccionada');
                    
                    // Restaurar cantidad si es tipo "Varios"
                    if (cantInput.length && inputNumero.length) {
                        cantInput.show();
                        inputNumero.val(cantidadGuardada);
                        console.log(`✓ Insumo ${id} restaurado con cantidad: ${cantidadGuardada}`);
                    } else {
                        console.log(`✓ Insumo ${id} restaurado (normal)`);
                    }
                }
            });
        } else {
            console.log('No hay datos de seleccionados en localStorage');
        }
        
        // Seleccionar el nuevo insumo si existe
        if (nuevoId) {
            console.log('Intentando seleccionar nuevo insumo:', nuevoId);
            const fila = $(`.fila-insumo[data-id="${nuevoId}"]`);
            console.log('Fila del nuevo insumo:', fila.length > 0 ? 'Encontrada' : 'NO encontrada');
            
            if (fila.length) {
                const input = fila.find('.hidden-insumo-input');
                if (input.prop('disabled')) {
                    console.log('Seleccionando nuevo insumo con cantidad máxima...');
                    // Pasar null como cantidad para que use el máximo del stock
                    toggleSeleccionInsumo(parseInt(nuevoId), null);
                } else {
                    console.log('El nuevo insumo ya estaba seleccionado');
                }
            } else {
                console.error('¡PROBLEMA! El nuevo insumo no se encuentra en la tabla. ID:', nuevoId);
                console.log('IDs disponibles en la tabla:', $('.fila-insumo').map(function() { return $(this).data('id'); }).get());
            }
        }
        
        actualizarContador();
        
        // Asegurar que las filas seleccionadas estén arriba
        setTimeout(function() {
            ordenarFilas();
            console.log('Restauración y ordenamiento completados');
        }, 50);
    } catch (e) {
        console.error('Error restaurando seleccionados:', e);
    }
}

// Función para ir a crear nuevo insumo
function irACrearInsumo() {
    console.log('Guardando estado antes de crear insumo...');
    guardarEstado();
    
    // Verificar que se guardó
    const verificar = localStorage.getItem('ingreso_seleccionados');
    console.log('Seleccionados guardados:', verificar);
    
    // Obtener ID del ingreso actual si estamos editando
    const urlParams = new URLSearchParams(window.location.search);
    const idIngreso = urlParams.get('id');
    
    // Redirigir incluyendo el ID del ingreso si existe
    let url = BASE + '/pages/insumos/agregar.php?from=ingreso';
    if (idIngreso) {
        url += '&return_to_id=' + idIngreso;
    }
    window.location.href = url;
}

// Al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOMContentLoaded ===');
    const urlParams = new URLSearchParams(window.location.search);
    const nuevoId = urlParams.get('added_id');
    const from = urlParams.get('from');
    
    console.log('URL params:', { from, nuevoId });
    
    if (from === 'agregar') {
        console.log('Volviendo de crear insumo...');
        
        // Restaurar datos del Paso 1
        restaurarDatosPaso1();
        
        // Delay para asegurar que el DOM está listo
        setTimeout(function() {
            // Restaurar seleccionados
            restaurarSeleccionados(nuevoId);
            
            // IR DIRECTAMENTE AL PASO 2 con delay adicional
            setTimeout(function() {
                console.log('Navegando al Paso 2...');
                irAPaso(2);
            }, 150); // Después de que restaurarSeleccionados termine
        }, 100);
    } else {
        // Actualizar contador inicial
        actualizarContador();
        ordenarFilas();
    }
});

// Ir a un paso específico
function irAPaso(paso) {
    // Validar paso actual antes de avanzar
    if (paso > pasoActual) {
        if (pasoActual === 1 && !validarPaso1()) {
            return;
        }
        if (pasoActual === 2 && !validarPaso2()) {
            return;
        }
    }
    
    // Ocultar todos los pasos
    $('#paso1, #paso2, #paso3').hide();
    
    // Actualizar stepper
    $('.stepper .step').removeClass('active');
    $(`.stepper .step-${paso}`).addClass('active');
    
    // Mostrar paso
    $(`#paso${paso}`).show();
    
    // Acciones específicas
    if (paso === 2) {
        ordenarFilas();
        filtrarInsumos();
    } else if (paso === 3) {
        actualizarResumenPaso3();
    }
    
    pasoActual = paso;
}

// Validar paso 1
function validarPaso1() {
    const nroRef = document.getElementById('nro_referencia');
    if (!nroRef || !nroRef.value.trim()) {
        nroRef && nroRef.classList.add('is-invalid');
        showToast(TOAST_MSG_REFERENCIA, 'warning');
        return false;
    }
    nroRef.classList.remove('is-invalid');
    return true;
}

// Validar paso 2
function validarPaso2() {
    const seleccionados = $('.hidden-insumo-input:not([disabled])');
    if (seleccionados.length === 0) {
        alert('Debe seleccionar al menos un insumo');
        return false;
    }
    return true;
}

// Actualizar resumen paso 3
function actualizarResumenPaso3() {
    $('#m_codigo').text($('#nro_referencia').val() || '-');
    const fecha = $('#fecha_finalizacion').val();
    $('#m_fecha').text(fecha ? new Date(fecha + 'T00:00:00').toLocaleDateString('es-AR') : 'No especificada');
    $('#m_descripcion').text($('#descripcion').val() || 'Sin descripción');
    
    // Agrupar insumos por tipo
    const porTipo = {};
    $('.hidden-insumo-input:not([disabled])').each(function() {
        const $input = $(this);
        const $fila = $input.closest('tr');
        const tipo = $input.data('tipo');
        const nombre = $fila.data('nombre');
        const cantidad = $fila.find('input[type="number"]').val() || 1;
        
        if (!porTipo[tipo]) {
            porTipo[tipo] = [];
        }
        
        porTipo[tipo].push({ nombre, cantidad: parseInt(cantidad) });
    });
    
    // Renderizar tabla
    let html = '<table class="table table-sm table-striped"><tbody>';
    Object.keys(porTipo).sort().forEach(tipo => {
        html += `<tr><td colspan="2" class="fw-bold bg-light">${tipo} (${porTipo[tipo].length})</td></tr>`;
        porTipo[tipo].forEach(ins => {
            html += `<tr><td class="ps-4">${ins.nombre}</td><td class="text-end">`;
            if (tipo === 'Varios' && ins.cantidad > 1) {
                html += `<span class="badge bg-primary">Cant: ${ins.cantidad}</span>`;
            }
            html += '</td></tr>';
        });
    });
    html += '</tbody></table>';
    
    $('#m_insumos').html(html);
}

// Navegación entre pasos
$('#btnSiguiente').on('click', function() {
    guardarEstado();
    irAPaso(2);
});

$('#btnVolver').on('click', function() {
    irAPaso(1);
});

$('#btnSiguiente3').on('click', function() {
    if (validarPaso2()) {
        guardarEstado();
        irAPaso(3);
    }
});

$('#btnVolver3').on('click', function() {
    irAPaso(2);
});

// Toggle selección de insumo
function toggleSeleccionInsumo(id, cantidadInicial) {
    const fila = $(`.fila-insumo[data-id="${id}"]`);
    const btn = fila.find('.btn-seleccionar');
    const input = fila.find('.hidden-insumo-input');
    const cantInput = fila.find('.cantidad-input');
    const inputNumero = cantInput.find('input[type="number"]');
    
    if (input.prop('disabled')) {
        // Seleccionar
        input.prop('disabled', false);
        btn.removeClass('btn-outline-primary').addClass('btn-primary');
        btn.find('i').removeClass('fa-plus').addClass('fa-minus');
        btn.find('span').text(' Deseleccionar');
        btn.attr('title', 'Deseleccionar');
        fila.addClass('fila-seleccionada');
        
        if (cantInput.length && inputNumero.length) {
            cantInput.show();
            
            // Si es cantidad inicial (restaurando), usar ese valor
            // Si no, usar el máximo del stock para tipo "Varios"
            if (cantidadInicial !== undefined && cantidadInicial !== null) {
                inputNumero.val(cantidadInicial);
                console.log(`Cantidad inicial establecida: ${cantidadInicial}`);
            } else {
                const max = parseInt(input.data('max')) || 1;
                inputNumero.val(max);
                console.log(`Cantidad máxima establecida: ${max}`);
            }
        }
    } else {
        // Deseleccionar
        input.prop('disabled', true);
        btn.removeClass('btn-primary').addClass('btn-outline-primary');
        btn.find('i').removeClass('fa-minus').addClass('fa-plus');
        btn.find('span').text(' Seleccionar');
        btn.attr('title', 'Seleccionar');
        fila.removeClass('fila-seleccionada');
        
        if (cantInput.length) {
            cantInput.hide();
        }
    }
    
    actualizarContador();
    guardarEstado();
    ordenarFilas();
}

// Actualizar contador
function actualizarContador() {
    const count = $('.hidden-insumo-input:not([disabled])').length;
    $('#contadorSeleccion').text(count);
}

// Ordenar filas - SELECCIONADOS SIEMPRE ARRIBA
function ordenarFilas() {
    const tbody = $('#tablaInsumos tbody');
    const filas = tbody.find('tr.fila-insumo').toArray();
    
    filas.sort(function(a, b) {
        const aSeleccionada = $(a).hasClass('fila-seleccionada');
        const bSeleccionada = $(b).hasClass('fila-seleccionada');
        
        // Seleccionados siempre arriba
        if (aSeleccionada && !bSeleccionada) return -1;
        if (!aSeleccionada && bSeleccionada) return 1;
        
        // Entre seleccionados o no seleccionados, mantener orden alfabético
        const aNombre = $(a).data('nombre') || '';
        const bNombre = $(b).data('nombre') || '';
        return aNombre.localeCompare(bNombre);
    });
    
    tbody.empty();
    $.each(filas, function(idx, fila) {
        tbody.append(fila);
    });
}

// Filtrar insumos - MANTENER ORDEN (seleccionados arriba)
function filtrarInsumos() {
    const busqueda = $('#filtro_busqueda').val().toLowerCase();
    const tipo = $('#filtro_tipo').val();
    
    $('tr.fila-insumo').each(function() {
        const $fila = $(this);
        const texto = $fila.data('texto') || '';
        const tipoFila = $fila.data('tipo') || '';
        
        let mostrar = true;
        
        if (tipo && tipoFila !== tipo) {
            mostrar = false;
        }
        
        if (busqueda && texto.indexOf(busqueda) === -1) {
            mostrar = false;
        }
        
        $fila.toggle(mostrar);
    });
    
    // Re-ordenar después de filtrar para asegurar que seleccionados estén arriba
    ordenarFilas();
}

$('#filtro_busqueda').on('input', filtrarInsumos);
$('#filtro_tipo').on('change', filtrarInsumos);

$('#btnLimpiarFiltros').on('click', function() {
    $('#filtro_busqueda').val('');
    $('#filtro_tipo').val('');
    filtrarInsumos();
});

// Seleccionar todos los filtrados
function seleccionarFiltrados() {
    $('tr.fila-insumo:visible').each(function() {
        const $fila = $(this);
        const input = $fila.find('.hidden-insumo-input');
        if (input.prop('disabled')) {
            const id = $fila.data('id');
            // Pasar undefined para que use el máximo
            toggleSeleccionInsumo(id, undefined);
        }
    });
}

// Deseleccionar todos
function deseleccionarTodos() {
    $('tr.fila-insumo').each(function() {
        const $fila = $(this);
        const input = $fila.find('.hidden-insumo-input');
        if (!input.prop('disabled')) {
            const id = $fila.data('id');
            toggleSeleccionInsumo(id, undefined);
        }
    });
}

// Limpiar localStorage al enviar formulario
$('#formPasos').on('submit', function() {
    try {
        localStorage.removeItem('ingreso_paso1');
        localStorage.removeItem('ingreso_seleccionados');
    } catch(e) {}
});

// Botón Guardar PDF
$('#btnGuardarPDF').on('click', function() {
    if (!ES_EDICION) {
        showToast('Esta acción solo está disponible al editar un ingreso existente', 'warning');
        return;
    }
    
    const idIngresoEdit = <?php echo $idIngreso; ?>;
    const tieneArchivoRemito = $('#editar_archivo_remito')[0].files.length > 0;
    const tieneArchivoDocumentacion = $('#editar_archivo_documentacion')[0].files.length > 0;
    
    if (!tieneArchivoRemito && !tieneArchivoDocumentacion) {
        showToast('Selecciona al menos un archivo para guardar', 'warning');
        return;
    }
    
    // Crear FormData para enviar archivos
    const formData = new FormData();
    
    if (tieneArchivoRemito) {
        formData.append('archivo_remito', $('#editar_archivo_remito')[0].files[0]);
    }
    
    if (tieneArchivoDocumentacion) {
        formData.append('archivo_documentacion', $('#editar_archivo_documentacion')[0].files[0]);
    }
    
    // Deshabilitar botón mientras se carga
    const btn = $(this);
    const btnOriginalHTML = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...');
    
    $.ajax({
        url: BASE + '/ajax/ingresos_guardar_documentos.php?id=' + idIngresoEdit,
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(resp) {
            if (resp && resp.success) {
                showToast('Documentos guardados correctamente', 'success');
                
                // Limpiar inputs de archivo
                $('#editar_archivo_remito').val('');
                $('#editar_archivo_documentacion').val('');
                
                // Recargar página para mostrar nuevos documentos
                setTimeout(function() {
                    window.location.href = BASE + '/pages/insumos/ingresos_listar.php';
                }, 1500);
            } else {
                showToast(resp.error || 'Error al guardar documentos', 'error');
                btn.prop('disabled', false).html(btnOriginalHTML);
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr);
            showToast('Error al guardar documentos. Por favor intente nuevamente', 'error');
            btn.prop('disabled', false).html(btnOriginalHTML);
        }
    });
});

// Cargar documentos existentes si estamos editando
if (ES_EDICION) {
    const idIngresoEdit = <?php echo $idIngreso; ?>;
    
    $.ajax({
        url: BASE + '/ajax/ingresos_documentos_listar.php',
        data: { id: idIngresoEdit },
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.success && resp.data && resp.data.documentos) {
                const docs = resp.data.documentos;
                
                // Separar por tipo
                const remitos = docs.filter(d => d.tipo_documento === 'remito');
                const documentaciones = docs.filter(d => d.tipo_documento === 'documentacion');
                
                // Mostrar remitos
                if (remitos.length > 0) {
                    let html = '';
                    remitos.forEach(doc => {
                        let btnEliminar = '';
                        if (ES_ADMIN) {
                            btnEliminar = `
                                <button class="btn btn-sm btn-danger" 
                                        onclick="eliminarDocumentoIngreso(${doc.id_documento}, ${idIngresoEdit})" 
                                        title="Eliminar documento">
                                    <i class="fas fa-trash"></i>
                                </button>`;
                        }
                        html += `
                            <div class="list-group-item" id="doc-item-${doc.id_documento}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-pdf me-1"></i>
                                        <small>${$('<div>').text(doc.nombre_archivo).html()}</small>
                                        <br>
                                        <small class="text-muted">${doc.fecha_carga}</small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="${BASE}/ajax/ingresos_descargar_documento.php?id=${doc.id_documento}" 
                                           class="btn btn-sm btn-info" 
                                           download
                                           title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        ${btnEliminar}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#lista_remito').html(html);
                    $('#documentos_remito_existentes').show();
                }
                
                // Mostrar documentación
                if (documentaciones.length > 0) {
                    let html = '';
                    documentaciones.forEach(doc => {
                        let btnEliminar = '';
                        if (ES_ADMIN) {
                            btnEliminar = `
                                <button class="btn btn-sm btn-danger" 
                                        onclick="eliminarDocumentoIngreso(${doc.id_documento}, ${idIngresoEdit})" 
                                        title="Eliminar documento">
                                    <i class="fas fa-trash"></i>
                                </button>`;
                        }
                        html += `
                            <div class="list-group-item" id="doc-item-${doc.id_documento}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-alt me-1"></i>
                                        <small>${$('<div>').text(doc.nombre_archivo).html()}</small>
                                        <br>
                                        <small class="text-muted">${doc.fecha_carga}</small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="${BASE}/ajax/ingresos_descargar_documento.php?id=${doc.id_documento}" 
                                           class="btn btn-sm btn-info" 
                                           download
                                           title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        ${btnEliminar}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#lista_documentacion').html(html);
                    $('#documentos_documentacion_existentes').show();
                }
            }
        },
        error: function() {
            console.error('Error al cargar documentos existentes');
        }
    });
}

// Eliminar documento adjunto (Solo Admin/Superadmin)
function eliminarDocumentoIngreso(idDocumento, idIngreso) {
    if (!confirm('¿Está seguro de eliminar este documento?\n\nEsta acción no se puede deshacer.')) return;
    
    $.ajax({
        url: BASE + '/ajax/ingresos_eliminar_documento.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ 
            _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '', 
            id: idDocumento 
        }),
        success: function(r){ 
            if (!r.success) { 
                showToast(r.error||'Error al eliminar documento', 'error'); 
                return; 
            }
            showToast('Documento eliminado correctamente', 'success');
            // Remover el elemento del DOM
            $('#doc-item-' + idDocumento).fadeOut(300, function() {
                $(this).remove();
                // Si no quedan documentos, ocultar el contenedor
                if ($('#lista_remito .list-group-item').length === 0) {
                    $('#documentos_remito_existentes').hide();
                }
                if ($('#lista_documentacion .list-group-item').length === 0) {
                    $('#documentos_documentacion_existentes').hide();
                }
            });
        },
        error: function(xhr){ 
            try {
                const response = JSON.parse(xhr.responseText);
                showToast(response.error || 'Error al eliminar documento', 'error');
            } catch(e) {
                showToast('Error al eliminar documento', 'error');
            }
        }
    });
}
</script>
