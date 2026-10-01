<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'crear');

$conexion = conectarDB();

// Obtener datos para los select iniciales
$localidades = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll(PDO::FETCH_ASSOC);
$puntos_stock = $conexion->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll(PDO::FETCH_ASSOC);

// Obtener ingresos agrupados por tipo para el select de Información Común
$ingresos = $conexion->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$tiposIngresos = ['fondos' => 'Fondos', 'compra_directa' => 'Compra Directa', 'licitacion' => 'Licitación', 'otros' => 'Otros'];

// Catálogo de capacidades estándar de almacenamiento
$opcionesAlmacenamiento = obtenerOpcionesAlmacenamiento($conexion);

// Catálogos oficiales de hardware y permisos
$puedeGestionarCatalogo = tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador']);
$catalogoMothers = obtenerCatalogoMotherboards($conexion);
$catalogoCpusPc = obtenerCatalogoProcesadores('pc', $conexion);
$catalogoCpusNb = obtenerCatalogoProcesadores('notebook', $conexion);

class RelevamientoValidationException extends Exception {
    public int $itemIndex;
    public string $campo;
    public function __construct(string $message, int $itemIndex = -1, string $campo = '') {
        parent::__construct($message);
        $this->itemIndex = $itemIndex;
        $this->campo = $campo;
    }
}

// Procesar formulario POST (tradicional o AJAX JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isJson = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) 
           || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    $postData = [];
    if ($isJson) {
        $rawBody = file_get_contents('php://input');
        $postData = json_decode($rawBody, true) ?: [];
    } else {
        $postData = $_POST;
    }

    $csrfToken = $postData['_csrf'] ?? null;
    if (!verify_csrf($csrfToken)) {
        if ($isJson) {
            json_error('Token CSRF inválido o sesión expirada', 403);
        }
        $_SESSION['mensaje'] = 'Error: Token CSRF inválido';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: relevamiento_cargar.php');
        exit;
    }

    // 1. Validar campos de asignación (Paso 1)
    $idSede = (int)($postData['id_sede'] ?? 0);
    $idArea = !empty($postData['id_area_asignada']) ? (int)$postData['id_area_asignada'] : null;
    $nombre = trim($postData['nombre_persona_asignada'] ?? '');
    $apellido = trim($postData['apellido_persona_asignada'] ?? '');
    $fechaAsig = !empty($postData['fecha_asignacion']) ? $postData['fecha_asignacion'] : date('Y-m-d');
    $obs = trim($postData['observaciones'] ?? '');

    if ($idSede <= 0 || empty($nombre) || empty($apellido)) {
        $errMsg = "Complete todos los datos obligatorios de asignación y ubicación (Nombre, Apellido y Sede).";
        if ($isJson) {
            json_error($errMsg, 400, ['paso' => 1]);
        }
        $_SESSION['mensaje'] = "Error: " . $errMsg;
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: relevamiento_cargar.php");
        exit;
    }

    // 2. Validar que vengan insumos (Paso 2)
    $insumosPost = $postData['insumos'] ?? [];
    if (!is_array($insumosPost) || empty($insumosPost)) {
        $errMsg = "Debe cargar al menos un insumo en el relevamiento.";
        if ($isJson) {
            json_error($errMsg, 400, ['paso' => 2]);
        }
        $_SESSION['mensaje'] = "Error: " . $errMsg;
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: relevamiento_cargar.php");
        exit;
    }

    try {
        $conexion->beginTransaction();

        $insumosCreados = [];
        $seriesVistas = [];
        $idsFisicosVistos = [];
        $patrimoniosVistos = [];

        // Validar e insertar cada insumo
        foreach ($insumosPost as $index => $ins) {
            $tipoInsumo = trim($ins['tipo_insumo'] ?? '');
            if (empty($tipoInsumo)) {
                throw new RelevamientoValidationException("Debe seleccionar un tipo de insumo.", (int)$index, 'tipo_insumo');
            }

            // Identificadores
            $numeroSerie = ($tipoInsumo !== 'Varios' && !empty($ins['numero_serie'])) ? trim($ins['numero_serie']) : null;
            $idFisicoRaw = ($tipoInsumo !== 'Varios' && !empty($ins['id_fisico'])) ? trim($ins['id_fisico']) : null;
            $idFisico = $idFisicoRaw ? strtoupper(str_replace(['-', ' '], '', $idFisicoRaw)) : null;
            $idPatrimonio = ($tipoInsumo !== 'Varios' && !empty($ins['id_patrimonio'])) ? trim($ins['id_patrimonio']) : null;

            // Validación cruzada interna en el formulario
            if ($numeroSerie) {
                $serieLower = strtolower($numeroSerie);
                if (isset($seriesVistas[$serieLower])) {
                    throw new RelevamientoValidationException("El Número de Serie '{$numeroSerie}' está repetido en el formulario.", (int)$index, 'numero_serie');
                }
                $seriesVistas[$serieLower] = true;
            }
            if ($idFisico) {
                if (isset($idsFisicosVistos[$idFisico])) {
                    throw new RelevamientoValidationException("El ID Físico '{$idFisicoRaw}' está repetido en el formulario.", (int)$index, 'id_fisico');
                }
                $idsFisicosVistos[$idFisico] = true;
            }
            if ($idPatrimonio) {
                $patrLower = strtolower($idPatrimonio);
                if (isset($patrimoniosVistos[$patrLower])) {
                    throw new RelevamientoValidationException("El ID Patrimonio '{$idPatrimonio}' está repetido en el formulario.", (int)$index, 'id_patrimonio');
                }
                $patrimoniosVistos[$patrLower] = true;
            }

            // Validación contra base de datos
            $validacion = validarInsumoUnico($numeroSerie, $idFisico, $idPatrimonio, null, $conexion);
            if (!$validacion['valido']) {
                $campoConflicto = 'numero_serie';
                $errTexto = implode('. ', $validacion['errores']);
                if (stripos($errTexto, 'físico') !== false) {
                    $campoConflicto = 'id_fisico';
                } elseif (stripos($errTexto, 'patrimonio') !== false) {
                    $campoConflicto = 'id_patrimonio';
                }
                throw new RelevamientoValidationException($errTexto, (int)$index, $campoConflicto);
            }

            // Cantidades
            if ($tipoInsumo === 'Varios') {
                $cantidad = isset($ins['cantidad_varios']) ? max(1, (int)$ins['cantidad_varios']) : (isset($ins['cantidad_oficina']) ? max(1, (int)$ins['cantidad_oficina'] + (int)($ins['cantidad_deposito'] ?? 0)) : 1);
                $cantOficina = 0;
                $cantDeposito = 0;
            } else {
                $cantidad = 1;
                $cantOficina = null;
                $cantDeposito = null;
            }

            // Nombre del insumo: solo se guarda si fue ingresado manualmente (para Varios es obligatorio)
            $nombreInsumo = !empty($ins['nombre_insumo']) ? trim($ins['nombre_insumo']) : null;
            if ($tipoInsumo === 'Varios' && empty($nombreInsumo)) {
                throw new RelevamientoValidationException("Debe ingresar un nombre para el insumo de tipo Varios.", (int)$index, 'nombre_insumo');
            }

            $subcatVarios = ($tipoInsumo === 'Varios') ? (!empty($ins['subcategoria_varios']) ? $ins['subcategoria_varios'] : 'Periféricos') : null;
            $descGeneral = !empty($ins['descripcion_general']) ? mb_substr(trim($ins['descripcion_general']), 0, 255) : null;
            $nombreInsumo = $nombreInsumo !== null ? mb_substr($nombreInsumo, 0, 100) : null;
            $numeroSerie = $numeroSerie !== null ? mb_substr($numeroSerie, 0, 50) : null;
            $idFisico = $idFisico !== null ? mb_substr($idFisico, 0, 50) : null;
            $idPatrimonio = $idPatrimonio !== null ? mb_substr($idPatrimonio, 0, 50) : null;
            $esNuevo = isset($ins['es_nuevo']) && $ins['es_nuevo'] == '1' ? 1 : 0;
            $fechaAdq = !empty($ins['fecha_adquisicion']) ? $ins['fecha_adquisicion'] : $fechaAsig;
            $idIngresoAsociado = !empty($ins['id_ingreso']) ? (int)$ins['id_ingreso'] : null;

            // Inserción en tabla insumos (con estado Asignado a la Sede y Área)
            $sqlInsumo = "INSERT INTO insumos (
                            nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general,
                            numero_serie, id_fisico, id_patrimonio, cantidad, cantidad_oficina, cantidad_deposito,
                            fecha_adquisicion, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual,
                            id_ingreso, es_nuevo
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Asignado', NULL, ?, ?, ?, ?)";
            
            $stmtIns = $conexion->prepare($sqlInsumo);
            $stmtIns->execute([
                $nombreInsumo,
                $tipoInsumo,
                $subcatVarios,
                $descGeneral,
                $numeroSerie,
                $idFisico,
                $idPatrimonio,
                $cantidad,
                $cantOficina,
                $cantDeposito,
                $fechaAdq,
                $idSede,
                $idArea,
                $idIngresoAsociado,
                $esNuevo
            ]);

            $idInsumoGenerado = (int)$conexion->lastInsertId();

            // Inserción en tablas de detalle según tipo
            switch ($tipoInsumo) {
                case 'PC Escritorio':
                case 'PC Completa':
                    $sqlPc = "INSERT INTO pcs_completas (
                                id_insumo, procesador, ram_gb, almacenamiento_gb,
                                almacenamiento_secundario_gb, ssd_secundario,
                                mother, sist_op, ssd_o_superior
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtPc = $conexion->prepare($sqlPc);
                    $stmtPc->execute([
                        $idInsumoGenerado,
                        !empty($ins['procesador']) ? mb_substr(trim($ins['procesador']), 0, 100) : null,
                        !empty($ins['ram_gb']) ? (int)$ins['ram_gb'] : null,
                        !empty($ins['almacenamiento_gb']) ? (int)$ins['almacenamiento_gb'] : null,
                        !empty($ins['almacenamiento_secundario_gb']) ? (int)$ins['almacenamiento_secundario_gb'] : null,
                        isset($ins['ssd_secundario']) && $ins['ssd_secundario'] == '1' ? 1 : 0,
                        !empty($ins['mother']) ? mb_substr(trim($ins['mother']), 0, 100) : null,
                        !empty($ins['sist_op']) ? mb_substr(trim($ins['sist_op']), 0, 100) : null,
                        isset($ins['ssd_o_superior']) && $ins['ssd_o_superior'] == '1' ? 1 : 0
                    ]);
                    break;

                case 'Notebook':
                    $sqlNb = "INSERT INTO notebooks (
                                id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb,
                                cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red, ssd_o_superior
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtNb = $conexion->prepare($sqlNb);
                    $microSd = isset($ins['micro_sd']) && $ins['micro_sd'] == '1' ? 1 : 0;
                    $microSdGb = ($microSd && !empty($ins['micro_sd_gb'])) ? (int)$ins['micro_sd_gb'] : null;

                    $stmtNb->execute([
                        $idInsumoGenerado,
                        !empty($ins['marca_notebook']) ? mb_substr(trim($ins['marca_notebook']), 0, 100) : 'S/M',
                        !empty($ins['modelo_notebook']) ? mb_substr(trim($ins['modelo_notebook']), 0, 100) : 'S/M',
                        !empty($ins['procesador_notebook']) ? mb_substr(trim($ins['procesador_notebook']), 0, 100) : null,
                        !empty($ins['ram_gb_notebook']) ? (int)$ins['ram_gb_notebook'] : null,
                        !empty($ins['almacenamiento_gb_notebook']) ? (int)$ins['almacenamiento_gb_notebook'] : null,
                        isset($ins['cargador']) && $ins['cargador'] == '1' ? 1 : 0,
                        isset($ins['funda']) && $ins['funda'] == '1' ? 1 : 0,
                        $microSd,
                        $microSdGb,
                        isset($ins['caja']) && $ins['caja'] == '1' ? 1 : 0,
                        isset($ins['adaptador_red']) && $ins['adaptador_red'] == '1' ? 1 : 0,
                        isset($ins['ssd_o_superior_notebook']) && $ins['ssd_o_superior_notebook'] == '1' ? 1 : 0
                    ]);
                    break;

                case 'Monitor':
                    $sqlMon = "INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?, ?, ?, ?, ?)";
                    $stmtMon = $conexion->prepare($sqlMon);
                    $stmtMon->execute([
                        $idInsumoGenerado,
                        !empty($ins['marca_monitor']) ? mb_substr(trim($ins['marca_monitor']), 0, 100) : 'S/M',
                        !empty($ins['modelo_monitor']) ? mb_substr(trim($ins['modelo_monitor']), 0, 100) : 'S/M',
                        !empty($ins['pulgadas']) ? (float)$ins['pulgadas'] : null,
                        !empty($ins['conexion_monitor']) && in_array($ins['conexion_monitor'], ['VGA', 'HDMI', 'Ambas']) ? $ins['conexion_monitor'] : 'HDMI'
                    ]);
                    break;

                case 'Impresora':
                    $sqlImp = "INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                    $stmtImp = $conexion->prepare($sqlImp);
                    $stmtImp->execute([
                        $idInsumoGenerado,
                        !empty($ins['marca_impresora']) ? mb_substr(trim($ins['marca_impresora']), 0, 100) : 'S/M',
                        !empty($ins['modelo_impresora']) ? mb_substr(trim($ins['modelo_impresora']), 0, 100) : 'S/M'
                    ]);
                    break;

                case 'Escaner':
                    $sqlEsc = "INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                    $stmtEsc = $conexion->prepare($sqlEsc);
                    $stmtEsc->execute([
                        $idInsumoGenerado,
                        !empty($ins['marca_escaner']) ? mb_substr(trim($ins['marca_escaner']), 0, 100) : 'S/M',
                        !empty($ins['modelo_escaner']) ? mb_substr(trim($ins['modelo_escaner']), 0, 100) : 'S/M'
                    ]);
                    break;
            }

            $insumosCreados[] = [
                'id_insumo' => $idInsumoGenerado,
                'cantidad' => $cantidad
            ];
        }

        // 3. Generar Remito Histórico ({secuencia}_{anio}_hist)
        $anio = date('Y', strtotime($fechaAsig));
        $sufijo = '_' . $anio . '_hist';

        $stmtSeq = $conexion->prepare("INSERT IGNORE INTO remitos_historicos_secuencia (anio, ultimo_numero) VALUES (?, 0)");
        $stmtSeq->execute([$anio]);

        $stmtSeq = $conexion->prepare("UPDATE remitos_historicos_secuencia SET ultimo_numero = ultimo_numero + 1 WHERE anio = ?");
        $stmtSeq->execute([$anio]);

        $stmtSeq = $conexion->prepare("SELECT ultimo_numero FROM remitos_historicos_secuencia WHERE anio = ?");
        $stmtSeq->execute([$anio]);
        $secuencia = (int)$stmtSeq->fetchColumn();

        $numeroRemito = $secuencia . $sufijo;

        // Comprobar colisión por si el número ya existía previamente en remitos
        $stmtExiste = $conexion->prepare("SELECT COUNT(*) FROM remitos WHERE numero_remito = ?");
        $stmtExiste->execute([$numeroRemito]);
        if ((int)$stmtExiste->fetchColumn() > 0) {
            $stmtMax = $conexion->prepare("SELECT numero_remito FROM remitos WHERE numero_remito LIKE ?");
            $stmtMax->execute(["%_{$anio}_hist"]);
            $remitosHist = $stmtMax->fetchAll(PDO::FETCH_COLUMN);
            $maxNum = $secuencia;
            foreach ($remitosHist as $r) {
                $p = explode('_', $r);
                if (isset($p[0]) && is_numeric($p[0])) {
                    $maxNum = max($maxNum, (int)$p[0]);
                }
            }
            $secuencia = $maxNum + 1;
            $stmtUpdSeq = $conexion->prepare("UPDATE remitos_historicos_secuencia SET ultimo_numero = ? WHERE anio = ?");
            $stmtUpdSeq->execute([$secuencia, $anio]);
            $numeroRemito = $secuencia . $sufijo;
        }

        $obsRemito = !empty($obs) ? mb_substr($obs . " [Carga de Relevamiento]", 0, 255) : "Relevamiento de Sede / Carga Masiva";

        $sqlRemito = "INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones, declaracion_jurada) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
        $stmtRemito = $conexion->prepare($sqlRemito);
        $stmtRemito->execute([$numeroRemito, $idSede, $idArea, mb_substr($nombre, 0, 100), mb_substr($apellido, 0, 100), $fechaAsig, $obsRemito]);
        $idRemito = (int)$conexion->lastInsertId();

        // 4. Insertar detalle en remitos_detalle
        $sqlDetalle = "INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?, ?, ?)";
        $stmtDetalle = $conexion->prepare($sqlDetalle);
        foreach ($insumosCreados as $item) {
            $stmtDetalle->execute([$idRemito, $item['id_insumo'], $item['cantidad']]);
        }

        // 5. Auditoría
        registrarAuditoria(
            'relevamiento_carga_masiva',
            'insumos',
            "Relevamiento cargado con " . count($insumosCreados) . " insumos para {$nombre} {$apellido} bajo Remito Histórico {$numeroRemito}",
            'remitos',
            $idRemito
        );

        $conexion->commit();

        $mensajeExito = "¡Relevamiento cargado exitosamente! Se generó el Remito Histórico {$numeroRemito} con " . count($insumosCreados) . " insumos asignados.";
        $_SESSION['mensaje'] = $mensajeExito;
        $_SESSION['tipo_mensaje'] = "success";

        if ($isJson) {
            json_success([
                'mensaje' => $mensajeExito,
                'numero_remito' => $numeroRemito,
                'id_remito' => $idRemito,
                'redirect' => app_base_url() . '/pages/reportes/remito.php?remito=' . urlencode($numeroRemito)
            ]);
        }

        header("Location: " . app_base_url() . "/pages/reportes/remito.php?remito=" . urlencode($numeroRemito));
        exit;

    } catch (RelevamientoValidationException $e) {
        if ($conexion->inTransaction()) { $conexion->rollBack(); }
        $numItem = $e->itemIndex + 1;
        $msg = "Error en el ítem #{$numItem}: " . $e->getMessage();
        if ($isJson) {
            json_error($msg, 400, [
                'item_index' => $e->itemIndex,
                'item_numero' => $numItem,
                'campo' => $e->campo
            ]);
        }
        $_SESSION['mensaje'] = $msg;
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: relevamiento_cargar.php");
        exit;
    } catch (Exception $e) {
        if ($conexion->inTransaction()) { $conexion->rollBack(); }
        Logger::error('Error en carga de relevamiento', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        $msg = "Error al procesar el relevamiento: " . $e->getMessage();
        if ($isJson) {
            json_error($msg, 500);
        }
        $_SESSION['mensaje'] = $msg;
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: relevamiento_cargar.php");
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>Carga de Relevamiento de Insumos
                <span class="badge bg-secondary ms-2 fs-6">Remito Histórico</span>
            </h4>
            <a href="listar.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

<!-- Alerta de Borrador LocalStorage -->
<div id="alertaBorrador" class="mb-4" style="display: none;">
    <div class="alert alert-info alert-permanent border-info d-flex justify-content-between align-items-center shadow-sm mb-0">
        <div class="d-flex align-items-center">
            <i class="fas fa-history me-3 text-info fs-4"></i>
            <div>
                <strong>Borrador recuperado:</strong> Se encontró una sesión previa con <span id="borradorCant" class="badge bg-primary">0</span> insumo(s) (<span id="borradorFecha">-</span>).
                <div class="small text-muted">¿Deseas restaurar los insumos y datos que estabas cargando?</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" id="btnRestaurarBorrador">
                <i class="fas fa-undo me-1"></i>Restaurar Borrador
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDescartarBorrador">
                <i class="fas fa-trash me-1"></i>Descartar
            </button>
        </div>
    </div>
</div>

<!-- Stepper visual nativo de SITIA -->
<div class="stepper mb-4">
    <div class="step step-1 active" id="stepper-step-1">
        <span class="circle">1</span>
        <span>Datos de Asignación</span>
    </div>
    <div class="divider"></div>
    <div class="step step-2" id="stepper-step-2">
        <span class="circle">2</span>
        <span>Alta de Insumos Relevados</span>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" action="relevamiento_cargar.php" id="formRelevamiento" class="needs-validation" novalidate>
            <?php echo csrf_input(); ?>

            <!-- ================= PASO 1: DATOS DE ASIGNACIÓN ================= -->
            <div id="paso1">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3 section-title">Agente Relevado</h6>
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre_persona_asignada" name="nombre_persona_asignada" required>
                                    <div class="invalid-feedback">El nombre es obligatorio</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" id="apellido_persona_asignada" name="apellido_persona_asignada" required>
                                    <div class="invalid-feedback">El apellido es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Fecha de Asignación / Relevamiento *</label>
                                    <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">La fecha es obligatoria</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-3 section-title">Ubicación</h6>
                                <div class="mb-3">
                                    <label class="form-label">Localidad *</label>
                                    <select class="form-select" id="id_localidad" required>
                                        <option value="">Seleccione una localidad</option>
                                        <?php foreach ($localidades as $loc): ?>
                                            <option value="<?php echo $loc['id_localidad']; ?>">
                                                <?php echo htmlspecialchars($loc['nombre_localidad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Seleccione una localidad</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sede *</label>
                                    <select class="form-select" id="id_sede" name="id_sede" required disabled>
                                        <option value="">Primero elija una localidad</option>
                                    </select>
                                    <div class="invalid-feedback">Seleccione una sede</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Área</label>
                                    <select class="form-select" id="id_area_asignada" name="id_area_asignada" disabled>
                                        <option value="">Sin área específica / Opcional</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2 justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Notas adicionales del relevamiento..."></textarea>
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-primary mt-3 mt-md-0" id="btnIrPaso2">
                                    <i class="fas fa-arrow-right me-2"></i>Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 2: INSUMOS RELEVADOS ================= -->
            <div id="paso2" style="display: none;">
                <!-- Resumen de Destinatario y Ubicación -->
                <div class="alert alert-info py-2 px-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <i class="fas fa-user-check me-2"></i><strong>Asignando a:</strong> 
                        <span id="resumen-persona" class="fw-bold"></span> 
                        <span class="mx-2 opacity-50">|</span>
                        <i class="fas fa-building me-1"></i><span id="resumen-ubicacion"></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditarAsignacion">
                        <i class="fas fa-edit me-1"></i>Modificar Ubicación/Agente
                    </button>
                </div>

                <!-- Barra de herramientas superior -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 section-title border-0 pb-0">
                            <i class="fas fa-boxes me-2 text-primary"></i>Insumos Relevados (<span id="badge-contador-insumos">0</span>)
                        </h6>
                    </div>
                </div>

                <!-- Alerta de duplicados / errores en vivo -->
                <div id="contenedor-alerta-duplicados" class="mb-3" style="display: none;"></div>

                <!-- Contenedor dinámico de tarjetas de insumos -->
                <div id="contenedor-insumos">
                    <!-- Empty state inicial -->
                    <div id="empty-state-insumos" class="text-center py-5 border rounded bg-light">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay insumos agregados aún</h5>
                        <p class="text-muted mb-3">Seleccione una opción en el menú superior <strong>"+ Agregar Insumo"</strong> para comenzar a cargar los equipos relevados.</p>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-plus me-1"></i>+ Agregar Insumo
                            </button>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="PC Escritorio"><i class="fas fa-desktop me-2 text-primary"></i>PC Escritorio</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Notebook"><i class="fas fa-laptop me-2 text-info"></i>Notebook</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Monitor"><i class="fas fa-tv me-2 text-warning"></i>Monitor</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Impresora"><i class="fas fa-print me-2 text-secondary"></i>Impresora</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Escaner"><i class="fas fa-copy me-2 text-dark"></i>Escáner</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Varios"><i class="fas fa-boxes me-2 text-success"></i>Varios / Periférico</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción Final -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="btnVolverPaso1">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <div class="dropdown" id="dropdownAgregarInsumoInferior" style="display: none;">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="btnDropdownInferior" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-plus me-1"></i>Agregar Insumo
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="btnDropdownInferior">
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="PC Escritorio"><i class="fas fa-desktop me-2 text-primary"></i>PC Escritorio</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Notebook"><i class="fas fa-laptop me-2 text-info"></i>Notebook</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Monitor"><i class="fas fa-tv me-2 text-warning"></i>Monitor</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Impresora"><i class="fas fa-print me-2 text-secondary"></i>Impresora</a></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Escaner"><i class="fas fa-copy me-2 text-dark"></i>Escáner</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item btn-add-tipo py-2" href="#" data-tipo="Varios"><i class="fas fa-boxes me-2 text-success"></i>Varios / Periférico</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-primary" id="btnGuardarRelevamiento" disabled>
                            <i class="fas fa-save me-1"></i>Guardar y Asignar Relevamiento
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL DE CONFIRMACIÓN DE RELEVAMIENTO ================= -->
<div class="modal fade" id="modalConfirmarRelevamiento" tabindex="-1" aria-labelledby="modalConfirmarRelevamientoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title" id="modalConfirmarRelevamientoLabel">
                    <i class="fas fa-clipboard-check me-2"></i>Confirmar Carga de Relevamiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">
                    Estás a punto de registrar y asignar los insumos bajo un nuevo <strong>Remito Histórico</strong>. Por favor revise el resumen:
                </p>
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="mb-2">
                            <i class="fas fa-user text-primary me-2"></i><strong>Asignado a:</strong> <span id="modalResumenPersona" class="fw-bold">-</span>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i><strong>Ubicación:</strong> <span id="modalResumenUbicacion">-</span>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-calendar-alt text-secondary me-2"></i><strong>Fecha Asignación:</strong> <span id="modalResumenFecha">-</span>
                        </div>
                        <div>
                            <i class="fas fa-boxes text-success me-2"></i><strong>Total de Insumos:</strong> <span id="modalResumenTotal" class="badge bg-primary fs-6">0</span>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted fw-bold">Desglose por categoría:</label>
                    <div id="modalResumenDesglose" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btnCancelarModalConfirmar">
                    <i class="fas fa-times me-1"></i>Revisar Formulario
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarGuardarFinal">
                    <i class="fas fa-check me-1"></i>Confirmar y Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= TEMPLATE DE TARJETA DE INSUMO (3 COLUMNAS IDÉNTICAS A AGREGAR.PHP) ================= -->
<template id="template-insumo-card">
    <div class="card mb-3 border insumo-card" data-index="__INDEX__">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center" style="cursor: pointer;">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary item-badge-numero">#__NUMERO__</span>
                <strong class="item-titulo-resumen">Nuevo Insumo</strong>
                <span class="badge bg-secondary item-badge-tipo">Sin tipo</span>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-colapsar-item" title="Colapsar / Expandir">
                    <i class="fas fa-chevron-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-item" title="Eliminar este insumo">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-3 item-card-body">
            <!-- Formulario idéntico en 3 columnas a agregar.php -->
            <div class="row g-2">
                <!-- COLUMNA 1: Información Básica -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Información Básica
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label form-label-sm label-nombre-insumo">Descripción</label>
                                <input type="text" class="form-control form-control-sm w-100 input-nombre-insumo" name="insumos[__INDEX__][nombre_insumo]">
                                <small class="form-text text-muted help-nombre-insumo" style="display: none;">Insumo + Marca + Modelo + Conexión</small>
                                <div class="invalid-feedback">El nombre del insumo es obligatorio</div>
                            </div>

                            <input type="hidden" class="input-tipo-insumo" name="insumos[__INDEX__][tipo_insumo]" value="">

                            <!-- Campos específicos para tipo "Varios" -->
                            <div class="campos-varios" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Subcategoría</label>
                                    <select class="form-select form-select-sm w-100" name="insumos[__INDEX__][subcategoria_varios]">
                                        <option value="">Seleccione subcategoría</option>
                                        <option value="Hardware">Hardware</option>
                                        <option value="Periféricos" selected>Periféricos</option>
                                        <option value="Red">Red</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm mb-1">
                                        <i class="fas fa-boxes text-primary me-1"></i>Cantidad Relevada *
                                    </label>
                                    <input type="number" class="form-control form-control-sm input-cant-varios" name="insumos[__INDEX__][cantidad_varios]" value="1" min="1" required>
                                    <small class="form-text text-muted d-block">Cantidad de unidades encontradas en la sede</small>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Descripción General</label>
                                    <textarea class="form-control form-control-sm w-100" name="insumos[__INDEX__][descripcion_general]" rows="2"></textarea>
                                </div>
                            </div>

                            <!-- Campos específicos para otros tipos (Identificadores) -->
                            <div class="campos-especificos-identificadores">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Número de Serie</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-numero-serie input-valida-duplicado" name="insumos[__INDEX__][numero_serie]">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">ID Físico</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-id-fisico input-valida-duplicado" name="insumos[__INDEX__][id_fisico]" placeholder="Ej: PC0012">
                                    <div class="invalid-feedback">El ID físico es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">ID Patrimonio</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-id-patrimonio input-valida-duplicado" name="insumos[__INDEX__][id_patrimonio]">
                                    <div class="invalid-feedback">El ID patrimonio es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Cantidad</label>
                                    <input type="number" class="form-control form-control-sm w-100" value="1" min="1" readonly>
                                    <small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1 (carga unitaria)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA 2: Información Común -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-cog me-2 text-primary"></i>Información Común
                            </h6>
                        </div>
                        <div class="card-body px-3 pt-3 pb-2">
                            <div class="mb-2">
                                <label class="form-label form-label-sm">Fecha de Adquisición</label>
                                <input type="date" class="form-control form-control-sm w-100" name="insumos[__INDEX__][fecha_adquisicion]" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="es_nuevo___INDEX__" name="insumos[__INDEX__][es_nuevo]" value="1">
                                    <label class="form-check-label form-label-sm" for="es_nuevo___INDEX__">
                                        <strong>Insumo Nuevo</strong> <small class="text-muted">(marcar si es nuevo)</small>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-2 campo-ingreso-asociado">
                                <label class="form-label form-label-sm">Tipo de Ingreso</label>
                                <select class="form-select form-select-sm w-100 select-ingreso" name="insumos[__INDEX__][id_ingreso]">
                                    <option value="">Sin ingreso asociado</option>
                                    <?php foreach ($tiposIngresos as $tipoKey => $tipoLabel): 
                                        $ingresosTipo = array_filter($ingresos, fn($ing) => $ing['tipo_ingreso'] === $tipoKey);
                                        if (count($ingresosTipo) > 0): ?>
                                            <optgroup label="<?php echo $tipoLabel; ?>">
                                                <?php foreach ($ingresosTipo as $ing): ?>
                                                    <option value="<?php echo $ing['id_ingreso']; ?>">
                                                        <?php echo htmlspecialchars($ing['nro_referencia']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                    <?php endif; endforeach; ?>
                                </select>
                                <small class="text-muted">Opcional: Asociar insumo a un ingreso (Fondos, Licitación, etc.)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Extras Notebook: tarjeta independiente debajo de Información Común -->
                    <div class="card border-0 shadow-sm mt-2 extras-notebook" style="display:none;">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-laptop me-2 text-primary"></i>Accesorios Notebook</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="insumos[__INDEX__][cargador]" value="1" checked>
                                <label class="form-check-label form-label-sm">Cargador</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="insumos[__INDEX__][funda]" value="1">
                                <label class="form-check-label form-label-sm">Funda</label>
                            </div>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-micro-sd" type="checkbox" name="insumos[__INDEX__][micro_sd]" value="1">
                                        <label class="form-check-label form-label-sm">Micro SD</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="number" min="0" step="1" class="form-control form-control-sm input-micro-sd-gb" name="insumos[__INDEX__][micro_sd_gb]" placeholder="Tamaño (GB)" disabled>
                                </div>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="insumos[__INDEX__][caja]" value="1">
                                <label class="form-check-label form-label-sm">Caja</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="insumos[__INDEX__][adaptador_red]" value="1">
                                <label class="form-check-label form-label-sm">Adaptador de red</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA 3: Especificaciones -->
                <div class="col-md-4 columna-especificaciones">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-microchip me-2 text-primary"></i>Especificaciones
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Campos para PC Escritorio -->
                            <div class="campos-especificos campos-pc" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Procesador *</label>
                                    <?php if ($puedeGestionarCatalogo): ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm select2-hardware select-cpu-catalog w-100" name="insumos[__INDEX__][procesador]">
                                                <option value="">Seleccione procesador...</option>
                                                <?php foreach ($catalogoCpusPc as $c): 
                                                    $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                ?>
                                                    <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>">
                                                        <?php echo $valC; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="procesador" data-tipo-equipo="pc" title="Agregar nuevo modelo de Procesador">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm select2-hardware select-cpu-catalog w-100" name="insumos[__INDEX__][procesador]">
                                            <option value="">Seleccione procesador...</option>
                                            <?php foreach ($catalogoCpusPc as $c): 
                                                $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                            ?>
                                                <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>">
                                                    <?php echo $valC; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][ram_gb]" min="1">
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Motherboard *</label>
                                    <?php if ($puedeGestionarCatalogo): ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm select2-hardware select-mother-catalog w-100" name="insumos[__INDEX__][mother]">
                                                <option value="">Seleccione placa madre...</option>
                                                <?php foreach ($catalogoMothers as $m): 
                                                    $valM = htmlspecialchars(trim($m['marca'] . ' ' . $m['modelo']));
                                                ?>
                                                    <option value="<?php echo $valM; ?>" data-ram="<?php echo htmlspecialchars($m['tipo_ram']); ?>">
                                                        <?php echo $valM; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="motherboard" title="Agregar nuevo modelo de Motherboard">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm select2-hardware select-mother-catalog w-100" name="insumos[__INDEX__][mother]">
                                            <option value="">Seleccione placa madre...</option>
                                            <?php foreach ($catalogoMothers as $m): 
                                                $valM = htmlspecialchars(trim($m['marca'] . ' ' . $m['modelo']));
                                            ?>
                                                <option value="<?php echo $valM; ?>" data-ram="<?php echo htmlspecialchars($m['tipo_ram']); ?>">
                                                    <?php echo $valM; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <div class="invalid-feedback">La motherboard es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Sistema Operativo</label>
                                    <input type="text" class="form-control form-control-sm w-100" name="insumos[__INDEX__][sist_op]" placeholder="Ej: Windows 11 Pro">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Almacenamiento Principal *</label>
                                    <select class="form-select form-select-sm w-100" name="insumos[__INDEX__][almacenamiento_gb]">
                                        <option value="">Seleccione capacidad...</option>
                                        <?php foreach ($opcionesAlmacenamiento as $opc): ?>
                                            <option value="<?php echo (int)$opc['capacidad_gb']; ?>">
                                                <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                                <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input switch-disco" type="checkbox" id="ssd_o_superior___INDEX__" name="insumos[__INDEX__][ssd_o_superior]" value="1" data-target-badge="badge_disco_pc___INDEX__">
                                        <label class="form-check-label mb-0" for="ssd_o_superior___INDEX__">
                                            <span id="badge_disco_pc___INDEX__" class="badge bg-secondary"><i class="fas fa-hdd me-1"></i>HDD</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Almacenamiento Secundario <span class="text-muted fw-normal">(Opcional)</span></label>
                                    <select class="form-select form-select-sm w-100" name="insumos[__INDEX__][almacenamiento_secundario_gb]">
                                        <option value="">Sin almacenamiento secundario</option>
                                        <?php foreach ($opcionesAlmacenamiento as $opc): ?>
                                            <option value="<?php echo (int)$opc['capacidad_gb']; ?>">
                                                <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input switch-disco" type="checkbox" id="ssd_secundario___INDEX__" name="insumos[__INDEX__][ssd_secundario]" value="1" data-target-badge="badge_disco_pc_sec___INDEX__">
                                        <label class="form-check-label mb-0" for="ssd_secundario___INDEX__">
                                            <span id="badge_disco_pc_sec___INDEX__" class="badge bg-secondary"><i class="fas fa-hdd me-1"></i>HDD</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para Notebook -->
                            <div class="campos-especificos campos-notebook" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][marca_notebook]">
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][modelo_notebook]">
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Procesador *</label>
                                    <?php if ($puedeGestionarCatalogo): ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm select2-hardware select-cpu-catalog w-100" name="insumos[__INDEX__][procesador_notebook]">
                                                <option value="">Seleccione procesador...</option>
                                                <?php foreach ($catalogoCpusNb as $c): 
                                                    $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                ?>
                                                    <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>">
                                                        <?php echo $valC; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="procesador" data-tipo-equipo="notebook" title="Agregar nuevo modelo de Procesador">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm select2-hardware select-cpu-catalog w-100" name="insumos[__INDEX__][procesador_notebook]">
                                            <option value="">Seleccione procesador...</option>
                                            <?php foreach ($catalogoCpusNb as $c): 
                                                $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                            ?>
                                                <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>">
                                                    <?php echo $valC; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" name="insumos[__INDEX__][ram_gb_notebook]" min="1">
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Almacenamiento *</label>
                                    <select class="form-select form-select-sm w-100" name="insumos[__INDEX__][almacenamiento_gb_notebook]">
                                        <option value="">Seleccione capacidad...</option>
                                        <?php foreach ($opcionesAlmacenamiento as $opc): ?>
                                            <option value="<?php echo (int)$opc['capacidad_gb']; ?>">
                                                <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                                <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input switch-disco" type="checkbox" id="ssd_o_superior_nb___INDEX__" name="insumos[__INDEX__][ssd_o_superior_notebook]" value="1" data-target-badge="badge_disco_nb___INDEX__">
                                        <label class="form-check-label mb-0" for="ssd_o_superior_nb___INDEX__">
                                            <span id="badge_disco_nb___INDEX__" class="badge bg-secondary"><i class="fas fa-hdd me-1"></i>HDD</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para Impresora -->
                            <div class="campos-especificos campos-impresora" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][marca_impresora]">
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][modelo_impresora]">
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>

                            <!-- Campos para Monitor -->
                            <div class="campos-especificos campos-monitor" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][marca_monitor]">
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][modelo_monitor]">
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Pulgadas *</label>
                                    <input type="number" class="form-control form-control-sm w-100" name="insumos[__INDEX__][pulgadas]" step="0.1" min="1">
                                    <div class="invalid-feedback">Las pulgadas son obligatorias</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Conexión *</label>
                                    <select class="form-select form-select-sm w-100" name="insumos[__INDEX__][conexion_monitor]">
                                        <option value="">Seleccione conexión</option>
                                        <option value="VGA">VGA</option>
                                        <option value="HDMI" selected>HDMI</option>
                                        <option value="Ambas">Ambas</option>
                                    </select>
                                    <div class="invalid-feedback">La conexión es obligatoria</div>
                                </div>
                            </div>

                            <!-- Campos para Escáner -->
                            <div class="campos-especificos campos-escaner" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][marca_escaner]">
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100 input-autoname" name="insumos[__INDEX__][modelo_escaner]">
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>

                            <!-- Para Varios / No seleccionado -->
                            <div class="campos-especificos campos-none text-muted py-4 text-center">
                                <i class="fas fa-boxes fa-2x text-muted mb-2"></i>
                                <p class="small mb-0">Insumo genérico o "Varios". No requiere especificaciones técnicas de hardware.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
$(document).ready(function() {
    let contadorItems = 0;
    let timeoutDuplicados = null;

    function getBaseUrl() {
        return window.APP_BASE_URL || (window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/pages')));
    }

    // 1. Selector en cascada: Localidad -> Sede -> Área
    $('#id_localidad').on('change', function () {
        const idLoc = $(this).val();
        const $sede = $('#id_sede');
        const $area = $('#id_area_asignada');
        
        $sede.html('<option value="">Cargando sedes...</option>').prop('disabled', true);
        $area.html('<option value="">Primero elija una sede</option>').prop('disabled', true);

        if (!idLoc) {
            $sede.html('<option value="">Primero elija una localidad</option>');
            return;
        }

        $.getJSON(`${getBaseUrl()}/ajax/cargar_sedes.php`, { localidad_id: idLoc })
            .done(function (r) {
                const data = r && r.data ? r.data : r;
                const lista = data && data.sedes ? data.sedes : (Array.isArray(data) ? data : []);
                let html = '<option value="">Seleccione una sede</option>';
                lista.forEach(function (s) {
                    html += `<option value="${parseInt(s.id, 10)}">${$('<div>').text(s.nombre || '').html()}</option>`;
                });
                $sede.html(html).prop('disabled', false);
            })
            .fail(function () {
                $sede.html('<option value="">Error al cargar sedes</option>');
            });
    });

    $('#id_sede').on('change', function () {
        const idSede = $(this).val();
        const $area = $('#id_area_asignada');

        $area.html('<option value="">Cargando áreas...</option>').prop('disabled', true);

        if (!idSede) {
            $area.html('<option value="">Primero elija una sede</option>');
            return;
        }

        $.getJSON(`${getBaseUrl()}/ajax/cargar_areas.php`, { sede_id: idSede })
            .done(function (r) {
                const data = r && r.data ? r.data : r;
                const lista = data && data.areas ? data.areas : (Array.isArray(data) ? data : []);
                let html = '<option value="">Sin área específica / Opcional</option>';
                lista.forEach(function (a) {
                    html += `<option value="${parseInt(a.id_area || a.id, 10)}">${$('<div>').text(a.nombre_area || a.nombre || '').html()}</option>`;
                });
                $area.html(html).prop('disabled', false);
            })
            .fail(function () {
                $area.html('<option value="">Error al cargar áreas</option>');
            });
    });

    // 2. Navegación Stepper Paso 1 -> Paso 2
    $('#btnIrPaso2').on('click', function() {
        let valido = true;

        ['nombre_persona_asignada', 'apellido_persona_asignada', 'fecha_asignacion', 'id_localidad', 'id_sede'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el || !el.value.trim()) {
                valido = false;
                if (el) el.classList.add('is-invalid');
            } else {
                if (el) el.classList.remove('is-invalid');
            }
        });

        if (!valido) {
            if (typeof showToast === 'function') {
                showToast('Por favor complete todos los datos requeridos de asignación y ubicación (Nombre, Apellido y Sede).', 'warning');
            } else {
                alert('Por favor complete todos los datos requeridos de asignación.');
            }
            return;
        }

        // Actualizar resumen en Paso 2
        const persona = $('#nombre_persona_asignada').val().trim() + ' ' + $('#apellido_persona_asignada').val().trim();
        const sedeText = $('#id_sede option:selected').text();
        const areaVal = $('#id_area_asignada').val();
        const areaText = areaVal ? ' - ' + $('#id_area_asignada option:selected').text() : '';
        const locText = $('#id_localidad option:selected').text();

        $('#resumen-persona').text(persona);
        $('#resumen-ubicacion').text(`${sedeText} (${locText})${areaText}`);

        $('#paso1').fadeOut(180, function() {
            $('#stepper-step-1').removeClass('active');
            $('#stepper-step-2').addClass('active');
            $('#paso2').fadeIn(240);
            $('html, body').animate({ scrollTop: $('#stepper-step-1').offset().top - 80 }, 250);
        });
    });

    $('#btnVolverPaso1, #btnEditarAsignacion').on('click', function() {
        $('#paso2').fadeOut(180, function() {
            $('#stepper-step-2').removeClass('active');
            $('#stepper-step-1').addClass('active');
            $('#paso1').fadeIn(240);
            $('html, body').animate({ scrollTop: $('#stepper-step-1').offset().top - 80 }, 250);
        });
    });

    // 3. Función para Configurar Tipo de Insumo y UI idéntica a agregar.php
    function configurarTipoInsumo($card, tipo) {
        $card.find('.input-tipo-insumo').val(tipo);

        const iconos = {
            'PC Escritorio': 'fas fa-desktop',
            'Notebook': 'fas fa-laptop',
            'Monitor': 'fas fa-tv',
            'Impresora': 'fas fa-print',
            'Escaner': 'fas fa-copy',
            'Varios': 'fas fa-boxes'
        };
        const iconoClase = iconos[tipo] || 'fas fa-tag';
        $card.find('.item-badge-tipo').html(`<i class="${iconoClase} me-1"></i>${tipo || 'Sin tipo'}`);

        // Ocultar y deshabilitar todos los campos específicos primero
        $card.find('.campos-especificos, .campos-varios, .campos-especificos-identificadores, .columna-especificaciones, .campos-pc, .campos-notebook, .campos-impresora, .campos-monitor, .campos-escaner, .extras-notebook')
            .hide()
            .find('input, select, textarea').prop('disabled', true);

        if (tipo === 'Varios') {
            $card.find('.campos-varios').show().find('input, select, textarea').prop('disabled', false);
            $card.find('.columna-especificaciones').hide();
            $card.find('.label-nombre-insumo').text('Nombre del Insumo *');
            $card.find('.help-nombre-insumo').show();
            $card.find('.extras-notebook').hide();
        } else if (tipo !== '') {
            $card.find('.columna-especificaciones').show();
            $card.find('.campos-especificos-identificadores').show().find('input, select, textarea').prop('disabled', false);
            $card.find('.label-nombre-insumo').text('Descripción');
            $card.find('.help-nombre-insumo').hide();

            let seccion = '';
            switch(tipo) {
                case 'PC Escritorio':
                case 'PC Completa':
                    seccion = '.campos-pc';
                    break;
                case 'Notebook':
                    seccion = '.campos-notebook, .extras-notebook';
                    break;
                case 'Impresora':
                    seccion = '.campos-impresora';
                    break;
                case 'Monitor':
                    seccion = '.campos-monitor';
                    break;
                case 'Escaner':
                    seccion = '.campos-escaner';
                    break;
            }
            if (seccion) {
                $card.find(seccion).show().find('input, select, textarea').prop('disabled', false);
                $card.find(seccion).find('.select2-hardware').select2({
                    theme: 'bootstrap-5',
                    language: 'es',
                    width: '100%'
                });
            }
            if (tipo === 'Notebook') {
                const tieneMicro = $card.find('.switch-micro-sd').is(':checked');
                $card.find('.input-micro-sd-gb').prop('disabled', !tieneMicro);
            }
        } else {
            $card.find('.columna-especificaciones').show();
            $card.find('.campos-none').show();
            $card.find('.extras-notebook').hide();
        }

        if (tipo !== 'Notebook') {
            $card.find('.extras-notebook').hide();
            $card.find('.switch-micro-sd').prop('checked', false);
            $card.find('.input-micro-sd-gb').prop('disabled', true).val('');
        }

        actualizarTituloResumen($card);
        validarDuplicadosEnVivo();
    }

    // 4. Función para Agregar Tarjeta de Insumo Dinámica
    function agregarTarjetaInsumo(tipoDefault = '') {
        // Colapsar tarjetas previas para estabilizar la altura del documento sin tirones
        $('.insumo-card .item-card-body').hide();
        $('.insumo-card .btn-colapsar-item i').removeClass('fa-chevron-up').addClass('fa-chevron-down');

        contadorItems++;
        const templateHtml = document.getElementById('template-insumo-card').innerHTML;
        const totalItemsActuales = $('.insumo-card').length + 1;
        
        const rendered = templateHtml
            .replace(/__INDEX__/g, contadorItems)
            .replace(/__NUMERO__/g, totalItemsActuales);

        $('#empty-state-insumos').hide();
        const $card = $(rendered).hide().appendTo('#contenedor-insumos');

        if (tipoDefault) {
            configurarTipoInsumo($card, tipoDefault);
        }

        actualizarContadorGlobal();
        
        // Entrada fluida con fadeIn y scroll suave acelerado por hardware
        $card.fadeIn(220, function() {
            if (tipoDefault === 'Varios') {
                $card.find('.input-nombre-insumo').focus();
            } else {
                $card.find('.input-id-fisico').focus();
            }
        });

        // Scroll suave nativo a la nueva tarjeta
        setTimeout(function() {
            if ($card.length && $card[0]) {
                $card[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }, 40);

        programarGuardadoBorrador();

        return $card;
    }

    // Botones de agregar tipo
    $(document).on('click', '.btn-add-tipo', function(e) {
        e.preventDefault();
        const tipo = $(this).data('tipo');
        agregarTarjetaInsumo(tipo);
    });

    // Control de Switch Micro SD en Notebooks
    $(document).on('change', '.switch-micro-sd', function() {
        const $card = $(this).closest('.insumo-card');
        const activo = $(this).is(':checked');
        $card.find('.input-micro-sd-gb').prop('disabled', !activo);
        if (!activo) {
            $card.find('.input-micro-sd-gb').val('');
        }
    });

    // Cálculo dinámico de cantidades para tipo Varios
    $(document).on('input', '.input-cant-oficina, .input-cant-deposito', function() {
        const $card = $(this).closest('.insumo-card');
        const ofi = parseInt($card.find('.input-cant-oficina').val(), 10) || 0;
        const dep = parseInt($card.find('.input-cant-deposito').val(), 10) || 0;
        const total = ofi + dep;
        $card.find('.display-oficina').text(ofi);
        $card.find('.display-deposito').text(dep);
        $card.find('.display-cant-total').text(total);
    });

    // Auto-generación del nombre y resumen del ítem
    $(document).on('input change', '.insumo-card input, .insumo-card select', function() {
        const $card = $(this).closest('.insumo-card');
        actualizarTituloResumen($card);
    });

    function actualizarTituloResumen($card) {
        const tipo = $card.find('.input-tipo-insumo').val();
        let titulo = tipo || 'Nuevo Insumo';
        const idFisico = $card.find('.input-id-fisico').val()?.trim();
        const numSerie = $card.find('.input-numero-serie').val()?.trim();
        const nombreManual = $card.find('.input-nombre-insumo').val()?.trim();

        if (nombreManual) {
            titulo = nombreManual;
        } else if (tipo === 'PC Escritorio') {
            const proc = $card.find('[name*="[procesador]"]').val()?.trim();
            const ram = $card.find('input[name*="[ram_gb]"]').val()?.trim();
            if (proc || ram) titulo = `PC ${proc || ''} ${ram ? ram + 'GB' : ''}`.trim();
        } else if (tipo === 'Notebook') {
            const marca = $card.find('input[name*="[marca_notebook]"]').val()?.trim();
            const mod = $card.find('input[name*="[modelo_notebook]"]').val()?.trim();
            if (marca || mod) titulo = `Notebook ${marca} ${mod}`.trim();
        } else if (tipo === 'Monitor') {
            const marca = $card.find('input[name*="[marca_monitor]"]').val()?.trim();
            const mod = $card.find('input[name*="[modelo_monitor]"]').val()?.trim();
            if (marca || mod) titulo = `Monitor ${marca} ${mod}`.trim();
        } else if (tipo === 'Impresora') {
            const marca = $card.find('input[name*="[marca_impresora]"]').val()?.trim();
            const mod = $card.find('input[name*="[modelo_impresora]"]').val()?.trim();
            if (marca || mod) titulo = `Impresora ${marca} ${mod}`.trim();
        } else if (tipo === 'Escaner') {
            const marca = $card.find('input[name*="[marca_escaner]"]').val()?.trim();
            const mod = $card.find('input[name*="[modelo_escaner]"]').val()?.trim();
            if (marca || mod) titulo = `Escáner ${marca} ${mod}`.trim();
        }

        if (idFisico) {
            titulo += ` (${idFisico})`;
        } else if (numSerie) {
            titulo += ` [S/N: ${numSerie}]`;
        }

        $card.find('.item-titulo-resumen').text(titulo);
    }

    // 5. Eliminar y Colapsar Items
    $(document).on('click', '.btn-eliminar-item', function() {
        const $card = $(this).closest('.insumo-card');
        const eliminar = function() {
            $card.slideUp(320, function() {
                $(this).remove();
                renumerarTarjetas();
                actualizarContadorGlobal();
                validarDuplicadosEnVivo();
                if ($('.insumo-card').length === 0) {
                    $('#empty-state-insumos').slideDown(280);
                    localStorage.removeItem('sitia_relevamiento_borrador');
                    $('#alertaBorrador').slideUp(200);
                } else {
                    programarGuardadoBorrador();
                }
            });
        };

        if (typeof showConfirm === 'function') {
            showConfirm({
                titulo: 'Eliminar Insumo',
                mensaje: '¿Desea quitar este equipo del relevamiento?',
                icono: 'fa-trash-alt text-danger',
                claseBoton: 'btn-danger',
                textoAceptar: 'Eliminar',
                onConfirm: eliminar
            });
        } else if (confirm('¿Desea quitar este equipo del relevamiento?')) {
            eliminar();
        }
    });

    $(document).on('click', '.insumo-card .card-header', function(e) {
        if ($(e.target).closest('.btn-eliminar-item').length) return;
        
        const $card = $(this).closest('.insumo-card');
        const $body = $card.find('.item-card-body');
        const $icon = $card.find('.btn-colapsar-item i');
        
        $body.slideToggle(320, function() {
            if ($body.is(':visible')) {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
    });

    function renumerarTarjetas() {
        $('.insumo-card').each(function(i) {
            $(this).find('.item-badge-numero').text('#' + (i + 1));
        });
    }

    function actualizarContadorGlobal() {
        const total = $('.insumo-card').length;
        $('#badge-contador-insumos').text(total);
        $('#btnGuardarRelevamiento').prop('disabled', total === 0);
        if (total > 0) {
            $('#dropdownAgregarInsumoInferior').show();
        } else {
            $('#dropdownAgregarInsumoInferior').hide();
        }
    }

    // 6. Validación de Unicidad (IDs y Series) en Vivo con Debounce
    $(document).on('input blur', '.input-valida-duplicado', function() {
        clearTimeout(timeoutDuplicados);
        timeoutDuplicados = setTimeout(validarDuplicadosEnVivo, 400);
    });

    function validarDuplicadosEnVivo() {
        const series = [];
        const idsFisicos = [];
        const patrimonios = [];
        let erroresInternos = [];

        $('.input-valida-duplicado').removeClass('is-invalid');

        // 1. Revisar duplicados entre las tarjetas del formulario
        $('.insumo-card').each(function(idx) {
            const numItem = idx + 1;
            const $c = $(this);
            const tipo = $c.find('.input-tipo-insumo').val();
            if (tipo === 'Varios') return;

            const s = $c.find('.input-numero-serie').val()?.trim();
            const fRaw = $c.find('.input-id-fisico').val()?.trim();
            const f = fRaw ? fRaw.toUpperCase().replace(/[- ]/g, '') : '';
            const p = $c.find('.input-id-patrimonio').val()?.trim();

            if (s) {
                const sLower = s.toLowerCase();
                if (series.includes(sLower)) {
                    erroresInternos.push(`El Número de Serie <strong>${s}</strong> está repetido en el ítem #${numItem}.`);
                    $c.find('.input-numero-serie').addClass('is-invalid');
                } else {
                    series.push(sLower);
                }
            }

            if (f) {
                if (idsFisicos.includes(f)) {
                    erroresInternos.push(`El ID Físico <strong>${fRaw}</strong> está repetido en el ítem #${numItem}.`);
                    $c.find('.input-id-fisico').addClass('is-invalid');
                } else {
                    idsFisicos.push(f);
                }
            }

            if (p) {
                const pLower = p.toLowerCase();
                if (patrimonios.includes(pLower)) {
                    erroresInternos.push(`El ID Patrimonio <strong>${p}</strong> está repetido en el ítem #${numItem}.`);
                    $c.find('.input-id-patrimonio').addClass('is-invalid');
                } else {
                    patrimonios.push(pLower);
                }
            }
        });

        if (erroresInternos.length > 0) {
            $('#contenedor-alerta-duplicados').html(`
                <div class="alert alert-danger shadow-sm mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i><strong>Atención:</strong><br>
                    ${erroresInternos.join('<br>')}
                </div>
            `).show();
            $('#btnGuardarRelevamiento').prop('disabled', true);
            return;
        }

        // 2. Validar cada insumo contra la Base de Datos vía AJAX
        let promesas = [];
        let erroresBD = [];

        $('.insumo-card').each(function(idx) {
            const numItem = idx + 1;
            const $c = $(this);
            const tipo = $c.find('.input-tipo-insumo').val();
            if (tipo === 'Varios') return;

            const s = $c.find('.input-numero-serie').val()?.trim() || '';
            const fRaw = $c.find('.input-id-fisico').val()?.trim() || '';
            const f = fRaw ? fRaw.toUpperCase().replace(/[- ]/g, '') : '';
            const p = $c.find('.input-id-patrimonio').val()?.trim() || '';

            if (s || f || p) {
                const pAjax = $.ajax({
                    url: `${getBaseUrl()}/ajax/validar_insumo_unico.php`,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        numero_serie: s,
                        id_fisico: f,
                        id_patrimonio: p
                    })
                }).done(function(resp) {
                    if (!resp.valido && resp.errores && resp.errores.length > 0) {
                        erroresBD.push(`<strong>Ítem #${numItem}:</strong> ` + resp.errores.join(', '));
                        if (f) $c.find('.input-id-fisico').addClass('is-invalid');
                        if (s) $c.find('.input-numero-serie').addClass('is-invalid');
                        if (p) $c.find('.input-id-patrimonio').addClass('is-invalid');
                    }
                });
                promesas.push(pAjax);
            }
        });

        if (promesas.length > 0) {
            $.when.apply($, promesas).always(function() {
                if (erroresBD.length > 0) {
                    $('#contenedor-alerta-duplicados').html(`
                        <div class="alert alert-danger shadow-sm mb-0">
                            <i class="fas fa-database me-2"></i><strong>Conflicto con la Base de Datos:</strong><br>
                            ${erroresBD.join('<br>')}
                        </div>
                    `).show();
                    $('#btnGuardarRelevamiento').prop('disabled', true);
                } else {
                    $('#contenedor-alerta-duplicados').hide();
                    if ($('.insumo-card').length > 0) {
                        $('#btnGuardarRelevamiento').prop('disabled', false);
                    }
                }
            });
        } else {
            $('#contenedor-alerta-duplicados').hide();
            if ($('.insumo-card').length > 0) {
                $('#btnGuardarRelevamiento').prop('disabled', false);
            }
        }
    }

    // 7. Recopilación de datos del formulario en objeto JSON limpio
    function recopilarDatosFormulario() {
        const formData = {
            _csrf: $('input[name="_csrf"]').val(),
            nombre_persona_asignada: $('#nombre_persona_asignada').val().trim(),
            apellido_persona_asignada: $('#apellido_persona_asignada').val().trim(),
            fecha_asignacion: $('#fecha_asignacion').val(),
            id_localidad: $('#id_localidad').val(),
            id_sede: $('#id_sede').val(),
            id_area_asignada: $('#id_area_asignada').val(),
            observaciones: $('#observaciones').val().trim(),
            insumos: []
        };

        $('.insumo-card').each(function() {
            const $card = $(this);
            const itemObj = {};

            $card.find('input, select, textarea').each(function() {
                if (this.disabled) return;
                const name = this.name;
                if (!name) return;

                const match = name.match(/insumos\[[^\]]+\]\[([^\]]+)\]/);
                if (match) {
                    const key = match[1];
                    if (this.type === 'checkbox') {
                        if (this.checked) {
                            itemObj[key] = this.value;
                        }
                    } else if (this.type === 'radio') {
                        if (this.checked) {
                            itemObj[key] = this.value;
                        }
                    } else {
                        itemObj[key] = this.value;
                    }
                }
            });

            const tipo = $card.find('.input-tipo-insumo').val();
            if (tipo) {
                itemObj['tipo_insumo'] = tipo;
            }

            formData.insumos.push(itemObj);
        });

        return formData;
    }

    // 8. Resaltar ítem con error sin recargar la página
    function resaltarErrorItem(itemIndex, campo, errorMsg) {
        let $targetCard = null;
        if (itemIndex !== undefined && itemIndex !== null && itemIndex >= 0) {
            $targetCard = $('.insumo-card').eq(itemIndex);
        }

        if (!$targetCard || $targetCard.length === 0) {
            $targetCard = $('.insumo-card').first();
        }

        if ($targetCard && $targetCard.length > 0) {
            // Expandir la tarjeta
            $targetCard.find('.item-card-body').slideDown(200);
            $targetCard.find('.btn-colapsar-item i').removeClass('fa-chevron-down').addClass('fa-chevron-up');

            // Resaltar tarjeta visualmente
            $('.insumo-card').removeClass('border-danger border-2 shadow');
            $targetCard.addClass('border-danger border-2 shadow');

            // Resaltar campo específico si aplica
            let $targetInput = null;
            if (campo) {
                $targetInput = $targetCard.find(`[name*="[${campo}]"], .input-${campo.replace(/_/g, '-')}`);
                if ($targetInput.length > 0) {
                    $targetInput.addClass('is-invalid');
                    $targetInput.focus();
                }
            }

            // Si el error ocurrió en el Paso 2 pero el stepper muestra el Paso 1, ir al Paso 2
            if ($('#paso1').is(':visible')) {
                $('#paso1').hide();
                $('#stepper-step-1').removeClass('active');
                $('#stepper-step-2').addClass('active');
                $('#paso2').show();
            }

            // Scroll suave acelerado al elemento con error
            setTimeout(function() {
                if ($targetInput && $targetInput.length > 0) {
                    $targetInput[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if ($targetCard[0]) {
                    $targetCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        if (typeof showToast === 'function') {
            showToast(errorMsg || 'Por favor revise los campos señalados.', 'error');
        } else {
            alert(errorMsg || 'Por favor revise los campos señalados.');
        }
    }

    // 9. Auto-Guardado en Borrador Local (LocalStorage)
    let timeoutBorrador = null;
    function programarGuardadoBorrador() {
        clearTimeout(timeoutBorrador);
        timeoutBorrador = setTimeout(guardarBorradorLocal, 700);
    }

    function guardarBorradorLocal() {
        const data = recopilarDatosFormulario();
        // Guardar solo si hay al menos un insumo cargado en el relevamiento
        if (data.insumos && data.insumos.length > 0) {
            const ahora = new Date();
            const fechaLegible = ahora.toLocaleDateString('es-AR', { hour: '2-digit', minute: '2-digit' });
            const borrador = {
                timestamp: ahora.getTime(),
                fecha_legible: fechaLegible,
                paso1: {
                    nombre_persona_asignada: data.nombre_persona_asignada,
                    apellido_persona_asignada: data.apellido_persona_asignada,
                    fecha_asignacion: data.fecha_asignacion,
                    id_localidad: data.id_localidad,
                    id_sede: data.id_sede,
                    id_area_asignada: data.id_area_asignada,
                    observaciones: data.observaciones
                },
                insumos: data.insumos
            };
            try {
                localStorage.setItem('sitia_relevamiento_borrador', JSON.stringify(borrador));
            } catch (e) {
                console.warn('No se pudo guardar el borrador en LocalStorage:', e);
            }
        }
    }

    // Escuchar cambios para auto-guardar borrador
    $(document).on('input change', '#formRelevamiento input, #formRelevamiento select, #formRelevamiento textarea', function() {
        programarGuardadoBorrador();
    });

    // Verificar borrador existente al inicio
    function verificarBorradorLocal() {
        const borradorStr = localStorage.getItem('sitia_relevamiento_borrador');
        if (!borradorStr) {
            $('#alertaBorrador').hide();
            return;
        }
        try {
            const borrador = JSON.parse(borradorStr);
            if (borrador && Array.isArray(borrador.insumos) && borrador.insumos.length > 0) {
                $('#borradorCant').text(borrador.insumos.length);
                $('#borradorFecha').text(borrador.fecha_legible || 'reciente');
                $('#alertaBorrador').slideDown(250);
            } else {
                localStorage.removeItem('sitia_relevamiento_borrador');
                $('#alertaBorrador').hide();
            }
        } catch (e) {
            localStorage.removeItem('sitia_relevamiento_borrador');
            $('#alertaBorrador').hide();
        }
    }

    $('#btnRestaurarBorrador').on('click', function() {
        const borradorStr = localStorage.getItem('sitia_relevamiento_borrador');
        if (!borradorStr) return;
        try {
            const borrador = JSON.parse(borradorStr);
            if (!borrador) return;

            // Restaurar Paso 1
            if (borrador.paso1) {
                $('#nombre_persona_asignada').val(borrador.paso1.nombre_persona_asignada || '');
                $('#apellido_persona_asignada').val(borrador.paso1.apellido_persona_asignada || '');
                if (borrador.paso1.fecha_asignacion) $('#fecha_asignacion').val(borrador.paso1.fecha_asignacion);
                $('#observaciones').val(borrador.paso1.observaciones || '');

                if (borrador.paso1.id_localidad) {
                    $('#id_localidad').val(borrador.paso1.id_localidad);
                    $.getJSON(`${getBaseUrl()}/ajax/cargar_sedes.php`, { localidad_id: borrador.paso1.id_localidad })
                        .done(function (r) {
                            const data = r && r.data ? r.data : r;
                            const lista = data && data.sedes ? data.sedes : (Array.isArray(data) ? data : []);
                            let html = '<option value="">Seleccione una sede</option>';
                            lista.forEach(function (s) {
                                html += `<option value="${parseInt(s.id, 10)}">${$('<div>').text(s.nombre || '').html()}</option>`;
                            });
                            $('#id_sede').html(html).prop('disabled', false).val(borrador.paso1.id_sede || '');

                            if (borrador.paso1.id_sede) {
                                $.getJSON(`${getBaseUrl()}/ajax/cargar_areas.php`, { sede_id: borrador.paso1.id_sede })
                                    .done(function (rArea) {
                                        const dataA = rArea && rArea.data ? rArea.data : rArea;
                                        const listaA = dataA && dataA.areas ? dataA.areas : (Array.isArray(dataA) ? dataA : []);
                                        let htmlA = '<option value="">Sin área específica / Opcional</option>';
                                        listaA.forEach(function (a) {
                                            htmlA += `<option value="${parseInt(a.id_area || a.id, 10)}">${$('<div>').text(a.nombre_area || a.nombre || '').html()}</option>`;
                                        });
                                        $('#id_area_asignada').html(htmlA).prop('disabled', false).val(borrador.paso1.id_area_asignada || '');
                                    });
                            }
                        });
                }
            }

            // Restaurar Paso 2: Insumos
            if (borrador.insumos && borrador.insumos.length > 0) {
                $('#contenedor-insumos').empty();
                borrador.insumos.forEach(function(ins) {
                    const tipo = ins.tipo_insumo || '';
                    const $card = agregarTarjetaInsumo(tipo);

                    Object.keys(ins).forEach(function(k) {
                        const val = ins[k];
                        const $input = $card.find(`[name*="[${k}]"]`);
                        if ($input.length > 0) {
                            if ($input.is(':checkbox')) {
                                $input.prop('checked', val == '1' || val === true || val === 1).trigger('change');
                            } else {
                                $input.val(val).trigger('input');
                            }
                        }
                    });
                    actualizarTituloResumen($card);
                });
                actualizarContadorGlobal();
                validarDuplicadosEnVivo();
            }

            $('#alertaBorrador').slideUp(200);
            if (typeof showToast === 'function') {
                showToast(`Borrador restaurado con éxito (${borrador.insumos.length} insumos).`, 'info');
            }
        } catch (err) {
            console.error('Error restaurando borrador:', err);
            if (typeof showToast === 'function') {
                showToast('Error al restaurar los datos del borrador.', 'danger');
            }
        }
    });

    $('#btnDescartarBorrador').on('click', function() {
        localStorage.removeItem('sitia_relevamiento_borrador');
        $('#alertaBorrador').slideUp(200);
        if (typeof showToast === 'function') {
            showToast('Borrador descartado.', 'secondary');
        }
    });

    // 10. Click en Guardar -> Validación y Modal de Confirmación
    $('#btnGuardarRelevamiento').on('click', function(e) {
        e.preventDefault();

        // Verificar cantidad de insumos
        const totalInsumos = $('.insumo-card').length;
        if (totalInsumos === 0) {
            if (typeof showToast === 'function') {
                showToast('Debe cargar al menos un insumo en el relevamiento.', 'warning');
            } else {
                alert('Debe cargar al menos un insumo.');
            }
            return;
        }

        // Verificar datos obligatorios de asignación (Paso 1)
        let paso1Invalido = false;
        ['nombre_persona_asignada', 'apellido_persona_asignada', 'id_localidad', 'id_sede'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el || !el.value.trim()) {
                paso1Invalido = true;
                if (el) el.classList.add('is-invalid');
            } else {
                if (el) el.classList.remove('is-invalid');
            }
        });

        if (paso1Invalido) {
            if (typeof showToast === 'function') {
                showToast('Complete los datos obligatorios del Paso 1 (Nombre, Apellido y Sede).', 'warning');
            } else {
                alert('Complete los datos del Paso 1.');
            }
            $('#btnVolverPaso1').trigger('click');
            return;
        }

        // Verificar insumos sin tipo o campos incompletos
        let errorItemIndex = -1;
        let errorCampo = '';
        let errorMsg = '';

        $('.insumo-card').each(function(idx) {
            const $card = $(this);
            const tipo = $card.find('.input-tipo-insumo').val();
            const numItem = idx + 1;

            if (!tipo) {
                errorItemIndex = idx;
                errorCampo = 'tipo_insumo';
                errorMsg = `El ítem #${numItem} no tiene tipo de insumo seleccionado.`;
                return false;
            }

            if (tipo === 'Varios') {
                const nom = $card.find('.input-nombre-insumo').val()?.trim();
                const cant = parseInt($card.find('.input-cant-varios').val(), 10) || 0;
                if (!nom) {
                    errorItemIndex = idx;
                    errorCampo = 'nombre_insumo';
                    errorMsg = `El ítem #${numItem} (Varios) requiere un nombre de insumo.`;
                    return false;
                }
                if (cant <= 0) {
                    errorItemIndex = idx;
                    errorCampo = 'cantidad_varios';
                    errorMsg = `El ítem #${numItem} (Varios) debe tener una cantidad mayor a 0.`;
                    return false;
                }
            } else if (tipo === 'Notebook') {
                const marca = $card.find('input[name*="[marca_notebook]"]').val()?.trim();
                const modelo = $card.find('input[name*="[modelo_notebook]"]').val()?.trim();
                if (!marca) {
                    errorItemIndex = idx;
                    errorCampo = 'marca_notebook';
                    errorMsg = `El ítem #${numItem} (Notebook) requiere indicar la marca.`;
                    return false;
                }
                if (!modelo) {
                    errorItemIndex = idx;
                    errorCampo = 'modelo_notebook';
                    errorMsg = `El ítem #${numItem} (Notebook) requiere indicar el modelo.`;
                    return false;
                }
            } else if (tipo === 'Monitor') {
                const marca = $card.find('input[name*="[marca_monitor]"]').val()?.trim();
                const modelo = $card.find('input[name*="[modelo_monitor]"]').val()?.trim();
                if (!marca) {
                    errorItemIndex = idx;
                    errorCampo = 'marca_monitor';
                    errorMsg = `El ítem #${numItem} (Monitor) requiere indicar la marca.`;
                    return false;
                }
                if (!modelo) {
                    errorItemIndex = idx;
                    errorCampo = 'modelo_monitor';
                    errorMsg = `El ítem #${numItem} (Monitor) requiere indicar el modelo.`;
                    return false;
                }
            } else if (tipo === 'Impresora') {
                const marca = $card.find('input[name*="[marca_impresora]"]').val()?.trim();
                const modelo = $card.find('input[name*="[modelo_impresora]"]').val()?.trim();
                if (!marca) {
                    errorItemIndex = idx;
                    errorCampo = 'marca_impresora';
                    errorMsg = `El ítem #${numItem} (Impresora) requiere indicar la marca.`;
                    return false;
                }
                if (!modelo) {
                    errorItemIndex = idx;
                    errorCampo = 'modelo_impresora';
                    errorMsg = `El ítem #${numItem} (Impresora) requiere indicar el modelo.`;
                    return false;
                }
            } else if (tipo === 'Escaner') {
                const marca = $card.find('input[name*="[marca_escaner]"]').val()?.trim();
                const modelo = $card.find('input[name*="[modelo_escaner]"]').val()?.trim();
                if (!marca) {
                    errorItemIndex = idx;
                    errorCampo = 'marca_escaner';
                    errorMsg = `El ítem #${numItem} (Escáner) requiere indicar la marca.`;
                    return false;
                }
                if (!modelo) {
                    errorItemIndex = idx;
                    errorCampo = 'modelo_escaner';
                    errorMsg = `El ítem #${numItem} (Escáner) requiere indicar el modelo.`;
                    return false;
                }
            }
        });

        if (errorItemIndex !== -1) {
            resaltarErrorItem(errorItemIndex, errorCampo, errorMsg);
            return;
        }

        // Verificar si existen alertas de duplicados activas
        if ($('#contenedor-alerta-duplicados').is(':visible') && $('#contenedor-alerta-duplicados .alert-danger').length > 0) {
            if (typeof showToast === 'function') {
                showToast('Corrija los números de serie o identificadores duplicados antes de continuar.', 'warning');
            } else {
                alert('Existen identificadores duplicados.');
            }
            return;
        }

        // Poblar el modal de confirmación con los datos del relevamiento
        const nombreCompleto = $('#nombre_persona_asignada').val().trim() + ' ' + $('#apellido_persona_asignada').val().trim();
        const sedeTexto = $('#id_sede option:selected').text();
        const locTexto = $('#id_localidad option:selected').text();
        const areaVal = $('#id_area_asignada').val();
        const areaTexto = areaVal ? ' - ' + $('#id_area_asignada option:selected').text() : '';
        const fechaAsig = $('#fecha_asignacion').val();

        $('#modalResumenPersona').text(nombreCompleto);
        $('#modalResumenUbicacion').text(`${sedeTexto} (${locTexto})${areaTexto}`);
        $('#modalResumenFecha').text(fechaAsig);
        $('#modalResumenTotal').text(totalInsumos);

        const conteo = {};
        $('.insumo-card').each(function() {
            const tipo = $(this).find('.input-tipo-insumo').val() || 'Sin tipo';
            conteo[tipo] = (conteo[tipo] || 0) + 1;
        });

        let badgesHtml = '';
        const iconosModal = {
            'PC Escritorio': 'fas fa-desktop text-primary',
            'Notebook': 'fas fa-laptop text-info',
            'Monitor': 'fas fa-tv text-warning',
            'Impresora': 'fas fa-print text-secondary',
            'Escaner': 'fas fa-copy text-dark',
            'Varios': 'fas fa-boxes text-success'
        };
        Object.keys(conteo).forEach(function(tipo) {
            const icono = iconosModal[tipo] || 'fas fa-tag text-muted';
            badgesHtml += `<span class="badge bg-white text-dark border shadow-sm p-2 fs-6">
                <i class="${icono} me-1"></i>${tipo}: <strong>${conteo[tipo]}</strong>
            </span>`;
        });
        $('#modalResumenDesglose').html(badgesHtml);

        const modalConfirmar = new bootstrap.Modal(document.getElementById('modalConfirmarRelevamiento'));
        modalConfirmar.show();
    });

    // Variable de control para permitir salida limpia tras guardar
    let relevamientoGuardadoConExito = false;

    // 11. Envío final vía AJAX JSON al confirmar en el modal
    $('#btnConfirmarGuardarFinal').on('click', function() {
        const $btnFinal = $(this);
        const $btnCancelar = $('#btnCancelarModalConfirmar');
        const modalEl = document.getElementById('modalConfirmarRelevamiento');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);

        $btnFinal.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        $btnCancelar.prop('disabled', true);
        $('#btnGuardarRelevamiento').prop('disabled', true);

        const formData = recopilarDatosFormulario();

        $.ajax({
            url: 'relevamiento_cargar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json'
        })
        .done(function(resp) {
            if (resp && resp.success) {
                relevamientoGuardadoConExito = true;
                localStorage.removeItem('sitia_relevamiento_borrador');
                if (modalInstance) modalInstance.hide();

                if (typeof showToast === 'function') {
                    showToast(resp.data?.mensaje || '¡Relevamiento guardado con éxito!', 'success');
                }

                setTimeout(function() {
                    window.location.href = resp.data?.redirect || `${getBaseUrl()}/pages/insumos/listar.php`;
                }, 750);
            } else {
                if (modalInstance) modalInstance.hide();
                $btnFinal.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Confirmar y Guardar');
                $btnCancelar.prop('disabled', false);
                $('#btnGuardarRelevamiento').prop('disabled', false);

                const err = resp ? resp.error : 'Ocurrió un error inesperado al guardar.';
                const idx = resp && resp.item_index !== undefined ? resp.item_index : -1;
                const campo = resp && resp.campo ? resp.campo : '';
                resaltarErrorItem(idx, campo, err);
            }
        })
        .fail(function(xhr) {
            if (modalInstance) modalInstance.hide();
            $btnFinal.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Confirmar y Guardar');
            $btnCancelar.prop('disabled', false);
            $('#btnGuardarRelevamiento').prop('disabled', false);

            let errorMsg = 'Error al comunicarse con el servidor.';
            let idx = -1;
            let campo = '';

            try {
                const resp = JSON.parse(xhr.responseText);
                if (resp && resp.error) {
                    errorMsg = resp.error;
                }
                if (resp && resp.item_index !== undefined) {
                    idx = resp.item_index;
                }
                if (resp && resp.campo) {
                    campo = resp.campo;
                }
            } catch (e) {}

            resaltarErrorItem(idx, campo, errorMsg);
        });
    });

    // Prevenir submit accidental por tecla Enter
    $('#formRelevamiento').on('submit', function(e) {
        e.preventDefault();
        $('#btnGuardarRelevamiento').trigger('click');
        return false;
    });

    // 12. Protección contra salida o cierre accidental de pestaña si hay insumos
    window.addEventListener('beforeunload', function(e) {
        if (!relevamientoGuardadoConExito && $('.insumo-card').length > 0) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // 13. Heartbeat exclusivo para relevamiento: mantener viva la sesión mientras se cargan datos
    // Solo se ejecuta en esta página y únicamente si hay datos cargados o insumos ingresados
    setInterval(function() {
        const hayInsumos = $('.insumo-card').length > 0;
        const hayDatosPaso1 = ($('#nombre_persona_asignada').val()?.trim() || '') !== '';
        
        if (hayInsumos || hayDatosPaso1) {
            $.ajax({
                url: `${getBaseUrl()}/ajax/session_ping.php`,
                method: 'GET',
                dataType: 'json',
                skipActivity: true
            }).done(function() {
                // Mantener actualizado el timestamp en localStorage para estar en sincronía con footer.php
                const ahora = Date.now();
                localStorage.setItem('sitia_last_activity', ahora);
                localStorage.setItem('sitia_last_ping', ahora);
            });
        }
    }, 4 * 60 * 1000); // Cada 4 minutos

    // Comprobar borrador al cargar
    verificarBorradorLocal();
});
</script>

<?php if ($puedeGestionarCatalogo): ?>
    <?php include __DIR__ . '/modal_nuevo_hardware.php'; ?>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
