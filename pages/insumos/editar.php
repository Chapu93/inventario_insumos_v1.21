<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'editar');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = conectarDB();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de insumo no válido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}
$id = (int)$_GET['id'];

// Traer insumo
$stmt = $db->prepare("SELECT * FROM insumos WHERE id_insumo = ?");
$stmt->execute([$id]);
$insumo = $stmt->fetch();
if (!$insumo) {
    $_SESSION['mensaje'] = 'El insumo no existe.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}
$tipo_insumo = $insumo['tipo_insumo'];

// Permitir edición aunque esté asignado (según requisito)

// Tablas específicas
$esp = [];
switch ($tipo_insumo) {
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

// Catálogos oficiales de hardware y permisos
$puedeGestionarCatalogo = tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador']);
$catalogoMothers = obtenerCatalogoMotherboards($db);
$catalogoCpusPc = obtenerCatalogoProcesadores('pc', $db);
$catalogoCpusNb = obtenerCatalogoProcesadores('notebook', $db);

$origen = trim($_GET['origen'] ?? $_POST['origen'] ?? '');
$origenEstado = isset($_GET['origen_estado']) ? trim($_GET['origen_estado']) : (isset($_POST['origen_estado']) ? trim($_POST['origen_estado']) : null);
$idRemitoContexto = (int)($_GET['id_remito'] ?? $_POST['id_remito'] ?? 0);
$numeroRemitoContexto = trim($_GET['remito'] ?? $_POST['remito'] ?? '');
$dtPage = (int)($_GET['dt_page'] ?? $_POST['dt_page'] ?? 0);

// Obtener remito activo asociado al insumo (si está asignado)
if ($idRemitoContexto > 0) {
    $qRem = $db->prepare("SELECT r.*, s.nombre_sede, l.nombre_localidad, ar.nombre_area,
                                 d.cantidad AS cantidad_asignada, d.cantidad_devuelta
                          FROM remitos r 
                          JOIN remitos_detalle d ON r.id_remito = d.id_remito 
                          LEFT JOIN sedes s ON r.id_sede = s.id_sede
                          LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
                          LEFT JOIN areas ar ON r.id_area = ar.id_area
                          WHERE d.id_insumo = ? AND r.id_remito = ? AND r.estado = 'Activa' 
                          LIMIT 1");
    $qRem->execute([$id, $idRemitoContexto]);
    $remitoActivo = $qRem->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif (!empty($numeroRemitoContexto)) {
    $qRem = $db->prepare("SELECT r.*, s.nombre_sede, l.nombre_localidad, ar.nombre_area,
                                 d.cantidad AS cantidad_asignada, d.cantidad_devuelta
                          FROM remitos r 
                          JOIN remitos_detalle d ON r.id_remito = d.id_remito 
                          LEFT JOIN sedes s ON r.id_sede = s.id_sede
                          LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
                          LEFT JOIN areas ar ON r.id_area = ar.id_area
                          WHERE d.id_insumo = ? AND r.numero_remito = ? AND r.estado = 'Activa' 
                          LIMIT 1");
    $qRem->execute([$id, $numeroRemitoContexto]);
    $remitoActivo = $qRem->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $qRem = $db->prepare("SELECT r.*, s.nombre_sede, l.nombre_localidad, ar.nombre_area,
                                 d.cantidad AS cantidad_asignada, d.cantidad_devuelta
                          FROM remitos r 
                          JOIN remitos_detalle d ON r.id_remito = d.id_remito 
                          LEFT JOIN sedes s ON r.id_sede = s.id_sede
                          LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
                          LEFT JOIN areas ar ON r.id_area = ar.id_area
                          WHERE d.id_insumo = ? AND r.estado = 'Activa' 
                          ORDER BY r.fecha_asignacion DESC 
                          LIMIT 1");
    $qRem->execute([$id]);
    $remitoActivo = $qRem->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($tipo_insumo === 'Varios') {
    if ($origen === 'asignados') {
        $estaAsignado = true;
    } elseif ($origen === 'disponibles') {
        $estaAsignado = false;
    } else {
        $estaAsignado = ($insumo['estado'] === 'Asignado' && (int)($insumo['cantidad_oficina'] ?? 0) === 0 && (int)($insumo['cantidad_deposito'] ?? 0) === 0);
    }
} else {
    $estaAsignado = ($insumo['estado'] === 'Asignado');
}


// Puntos de stock
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        $db->beginTransaction();

        // NO permitir cambiar tipo
        $tipo_fijo = $tipo_insumo;

        $nombre = trim($_POST['nombre_insumo'] ?? '') ?: null;
        $fecha = $_POST['fecha_adquisicion'] ?: null;
        $estado = $_POST['estado'] ?? 'Disponible';
        // Para tipo Varios, punto de stock siempre es NULL
        $punto = ($tipo_fijo == 'Varios') ? null : ($_POST['id_punto_stock_actual'] ?: null);
        $esNuevo = isset($_POST['es_nuevo']) && $_POST['es_nuevo'] == '1' ? 1 : 0;
        $idIngreso = !empty($_POST['id_ingreso']) ? (int)$_POST['id_ingreso'] : null;
        
        // Si se asigna un ingreso, usar su fecha de finalización como fecha de adquisición
        if ($idIngreso) {
            $stmtIng = $db->prepare('SELECT DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos WHERE id_ingreso = ?');
            $stmtIng->execute([$idIngreso]);
            $ingreso = $stmtIng->fetch();
            if ($ingreso && !empty($ingreso['fecha_finalizacion'])) {
                $fecha = $ingreso['fecha_finalizacion'];
                Logger::info("Editar insumo - Ingreso asignado", ['fecha' => $fecha]);
            }
        }

        // Campos específicos según tipo
        $subcat = $numero_serie = $id_fisico = $id_patrimonio = null;
        $cantidad = 1;
        
        // Descripción general es OPCIONAL para TODOS los tipos
        $desc = ($_POST['descripcion_general'] ?? '') ?: null;

        if ($tipo_fijo === 'Varios') {
            $subcat = ($_POST['subcategoria_varios'] ?? '') ?: null;

            if ($estaAsignado) {
                // Si el insumo está asignado a sede/persona, no se altera el stock de oficina/depósito
                $cantidadOficina = (int)($insumo['cantidad_oficina'] ?? 0);
                $cantidadDeposito = (int)($insumo['cantidad_deposito'] ?? 0);
                $cantidad = (int)$insumo['cantidad'];
                $nuevoEstado = 'Asignado';
            } else {
                // Stock dual para tipo Varios en stock (Disponible)
                $cantidadOficina = isset($_POST['cantidad_oficina']) ? max(0, (int)$_POST['cantidad_oficina']) : 0;
                $cantidadDeposito = isset($_POST['cantidad_deposito']) ? max(0, (int)$_POST['cantidad_deposito']) : 0;
                $cantidad = $cantidadOficina + $cantidadDeposito;
                $nuevoEstado = ($cantidad > 0) ? 'Disponible' : 'Asignado';

                // Detectar cambios y registrar ajuste manual en historial
                $oldOficina = (int)($insumo['cantidad_oficina'] ?? $insumo['cantidad']);
                $oldDeposito = (int)($insumo['cantidad_deposito'] ?? 0);
                
                if ($cantidadOficina !== $oldOficina || $cantidadDeposito !== $oldDeposito) {
                    $difOficina = $cantidadOficina - $oldOficina;
                    $difDeposito = $cantidadDeposito - $oldDeposito;
                    $cantMovida = abs($difOficina) + abs($difDeposito);
                    
                    $db->prepare("INSERT INTO insumos_movimientos_stock 
                                  (id_insumo, tipo_movimiento, cantidad_movida, ubicacion_origen, ubicacion_destino,
                                   cantidad_oficina_antes, cantidad_deposito_antes, cantidad_oficina_despues, cantidad_deposito_despues, 
                                   observacion, fecha_movimiento) 
                                  VALUES (?, 'ajuste_manual', ?, 'edicion', 'edicion', ?, ?, ?, ?, 'Edición manual directa', NOW())")
                       ->execute([
                           $id, $cantMovida, 
                           $oldOficina, $oldDeposito, 
                           $cantidadOficina, $cantidadDeposito
                       ]);
                }
            }
        } else {
            $cantidadOficina = null;
            $cantidadDeposito = null;
            $numero_serie = ($_POST['numero_serie'] ?? '') ?: null;
            $id_fisico = ($_POST['id_fisico'] ?? '') ?: null;
            if ($id_fisico !== null) {
                $id_fisico = strtoupper(str_replace(['-', ' '], '', trim($id_fisico)));
            }
            $id_patrimonio = ($_POST['id_patrimonio'] ?? '') ?: null;
            $cantidad = 1; // fijo
            $nuevoEstado = $insumo['estado'];
        }

        // Validar unicidad de número de serie, ID físico y patrimonio (excluyendo el insumo actual)
        if (!empty($numero_serie) || !empty($id_fisico) || !empty($id_patrimonio)) {
            $valUnico = validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, $id, $db);
            if (!$valUnico['valido']) {
                throw new Exception(implode('<br>', $valUnico['errores']));
            }
        }

        // Actualizar insumo
        if ($tipo_fijo === 'Varios') {
            if ($estaAsignado) {
                // En vista asignada: preservar sede y estado original del insumo
                $sql = "UPDATE insumos
                        SET nombre_insumo = ?, subcategoria_varios = ?, descripcion_general = ?,
                            numero_serie = ?, id_fisico = ?, id_patrimonio = ?, cantidad = ?, 
                            cantidad_oficina = ?, cantidad_deposito = ?, estado = ?,
                            fecha_adquisicion = ?, id_punto_stock_actual = ?, id_ingreso = ?, es_nuevo = ?
                        WHERE id_insumo = ?";
                $db->prepare($sql)->execute([
                    $nombre, $subcat, $desc,
                    $numero_serie, $id_fisico, $id_patrimonio, $cantidad,
                    $cantidadOficina, $cantidadDeposito, $nuevoEstado,
                    $fecha, $punto, $idIngreso, $esNuevo,
                    $id
                ]);
            } else {
                $sql = "UPDATE insumos
                        SET nombre_insumo = ?, subcategoria_varios = ?, descripcion_general = ?,
                            numero_serie = ?, id_fisico = ?, id_patrimonio = ?, cantidad = ?, 
                            cantidad_oficina = ?, cantidad_deposito = ?, estado = ?,
                            id_sede_actual = NULL, id_area_asignacion_actual = NULL,
                            fecha_adquisicion = ?, id_punto_stock_actual = ?, id_ingreso = ?, es_nuevo = ?
                        WHERE id_insumo = ?";
                $db->prepare($sql)->execute([
                    $nombre, $subcat, $desc,
                    $numero_serie, $id_fisico, $id_patrimonio, $cantidad,
                    $cantidadOficina, $cantidadDeposito, $nuevoEstado,
                    $fecha, $punto, $idIngreso, $esNuevo,
                    $id
                ]);
            }
        } else {
            $sql = "UPDATE insumos
                    SET nombre_insumo = ?, subcategoria_varios = ?, descripcion_general = ?,
                        numero_serie = ?, id_fisico = ?, id_patrimonio = ?, cantidad = ?, 
                        cantidad_oficina = ?, cantidad_deposito = ?,
                        fecha_adquisicion = ?, id_punto_stock_actual = ?, id_ingreso = ?, es_nuevo = ?
                    WHERE id_insumo = ?";
            $db->prepare($sql)->execute([
                $nombre, $subcat, $desc,
                $numero_serie, $id_fisico, $id_patrimonio, $cantidad,
                $cantidadOficina, $cantidadDeposito,
                $fecha, $punto, $idIngreso, $esNuevo,
                $id
            ]);
        }

        // Actualizar tabla específica
        switch ($tipo_fijo) {
            case 'PC Completa':
            case 'PC Escritorio':
                $db->prepare("DELETE FROM pcs_completas WHERE id_insumo = ?")->execute([$id]);
                $almacenamientoSecundario = !empty($_POST['almacenamiento_secundario_gb']) ? (int)$_POST['almacenamiento_secundario_gb'] : null;
                $ssdSecundario = ($almacenamientoSecundario && isset($_POST['ssd_secundario'])) ? 1 : 0;
                $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother, sist_op, ssd_o_superior, almacenamiento_secundario_gb, ssd_secundario) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([
                       $id,
                       ($_POST['procesador'] ?? null),
                       ($_POST['ram_gb'] ?? null),
                       ($_POST['almacenamiento_gb'] ?? null),
                       ($_POST['mother'] ?? null),
                       ($_POST['sist_op'] ?? null),
                       (isset($_POST['ssd_o_superior']) ? 1 : 0),
                       $almacenamientoSecundario,
                       $ssdSecundario
                   ]);
                break;
            case 'Notebook':
                $db->prepare("DELETE FROM notebooks WHERE id_insumo = ?")->execute([$id]);
                $stmt = $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb, cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red, ssd_o_superior) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $cargador = isset($_POST['cargador']) ? 1 : 0;
                $funda = isset($_POST['funda']) ? 1 : 0;
                $microSd = isset($_POST['micro_sd']) ? 1 : 0;
                $microSdGb = $microSd ? (($_POST['micro_sd_gb'] !== '' ? (int)$_POST['micro_sd_gb'] : null)) : null;
                $caja = isset($_POST['caja']) ? 1 : 0;
                $adaptadorRed = isset($_POST['adaptador_red']) ? 1 : 0;
                $ssdoSuperiorNb = isset($_POST['ssd_o_superior_notebook']) ? 1 : 0;
                $stmt->execute([
                    $id,
                    ($_POST['marca_notebook'] ?? null),
                    ($_POST['modelo_notebook'] ?? null),
                    ($_POST['procesador_notebook'] ?? null),
                    ($_POST['ram_gb_notebook'] ?? null),
                    ($_POST['almacenamiento_gb_notebook'] ?? null),
                    $cargador,
                    $funda,
                    $microSd,
                    $microSdGb,
                    $caja,
                    $adaptadorRed,
                    $ssdoSuperiorNb
                ]);

                // Procesamiento de Declaración Jurada asociada al Remito Activo
                $qRem = $db->prepare("SELECT r.* FROM remitos r JOIN remitos_detalle d ON r.id_remito = d.id_remito WHERE d.id_insumo = ? AND r.estado = 'Activa' LIMIT 1");
                $qRem->execute([$id]);
                $remAct = $qRem->fetch(PDO::FETCH_ASSOC);
                
                if ($remAct) {
                    $declaracionNombre = $remAct['declaracion_jurada'] ?: null;
                    $eliminarDj = isset($_POST['eliminar_dj']) ? 1 : 0;
                    $uploadDir = UPLOAD_BASE_DIR . 'documentos/';
                    
                    if ($eliminarDj && $declaracionNombre) {
                        $archivoAnterior = $uploadDir . $declaracionNombre;
                        if (file_exists($archivoAnterior)) {
                            unlink($archivoAnterior);
                        }
                        $declaracionNombre = null;
                        
                        $stmtUpdRem = $db->prepare("UPDATE remitos SET declaracion_jurada = NULL WHERE id_remito = ?");
                        $stmtUpdRem->execute([$remAct['id_remito']]);
                        
                        registrarAuditoria('eliminar_declaracion_jurada', 'remitos', "Se eliminó declaración jurada de la notebook ID $id en remito #{$remAct['numero_remito']}", 'remito', $remAct['id_remito']);
                    }
                    
                    if (!empty($_FILES['declaracion_jurada']) && $_FILES['declaracion_jurada']['error'] === UPLOAD_ERR_OK) {
                        require_once '../../includes/validar_archivo.php';
                        $validacion = validarArchivoPdf($_FILES['declaracion_jurada'], 10 * 1024 * 1024);
                        if (!$validacion['valido']) {
                            throw new Exception("Error en la declaración jurada: " . $validacion['error']);
                        }
                        
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        if ($declaracionNombre) {
                            $archivoAnterior = $uploadDir . $declaracionNombre;
                            if (file_exists($archivoAnterior)) {
                                unlink($archivoAnterior);
                            }
                        }
                        
                        $declaracionNombre = 'dj_notebook_' . $id . '_' . time() . '.' . $validacion['extension'];
                        $rutaDestino = $uploadDir . $declaracionNombre;
                        
                        if (!move_uploaded_file($_FILES['declaracion_jurada']['tmp_name'], $rutaDestino)) {
                            throw new Exception("Error al guardar la declaración jurada en el servidor.");
                        }
                        
                        $stmtUpdRem = $db->prepare("UPDATE remitos SET declaracion_jurada = ? WHERE id_remito = ?");
                        $stmtUpdRem->execute([$declaracionNombre, $remAct['id_remito']]);
                        
                        registrarAuditoria('subir_declaracion_jurada', 'remitos', "Se subió/reemplazó declaración jurada de la notebook ID $id en remito #{$remAct['numero_remito']}", 'remito', $remAct['id_remito']);
                    }
                }
                break;
            case 'Impresora':
                $db->prepare("DELETE FROM impresoras WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id, ($_POST['marca_impresora'] ?? null), ($_POST['modelo_impresora'] ?? null)]);
                break;
            case 'Monitor':
                $db->prepare("DELETE FROM monitores WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                   ->execute([
                       $id,
                       ($_POST['marca_monitor'] ?? null),
                       ($_POST['modelo_monitor'] ?? null),
                       ($_POST['pulgadas'] ?? null),
                       ($_POST['conexion_monitor'] ?? null)
                   ]);
                break;
            case 'Escaner':
                $db->prepare("DELETE FROM escaneres WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id, ($_POST['marca_escaner'] ?? null), ($_POST['modelo_escaner'] ?? null)]);
                break;
        }

        $db->commit();
        
        // Registrar en auditoría
        registrarAuditoria(
            'editar_insumo',
            'insumos',
            "Insumo editado: {$nombre} (ID: {$id})",
            'insumo',
            $id,
            $insumo, // Estado anterior
            [ // Estado posterior
                'nombre_insumo' => $nombre,
                'tipo_insumo' => $tipo_fijo,
                'cantidad' => $cantidad
            ]
        );

        $_SESSION['mensaje'] = "Insumo actualizado correctamente";
        $_SESSION['tipo_mensaje'] = "success";

        $returnUrlPost = !empty($_POST['return_url']) ? $_POST['return_url'] : 'listar.php';
        // Validación de seguridad contra Open Redirect
        if (strpos($returnUrlPost, 'http') === 0) {
            $parsed = parse_url($returnUrlPost);
            if (!isset($parsed['host']) || $parsed['host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
                $returnUrlPost = 'listar.php';
            }
        }
        if (strpos($returnUrlPost, 'editar.php') !== false) {
            $returnUrlPost = 'listar.php';
        }

        // Preservar dt_page y agregar highlight para volver a la misma página con el item en foco
        $parsedUrl = parse_url($returnUrlPost);
        $queryParams = [];
        if (!empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        if ($dtPage > 0) {
            $queryParams['dt_page'] = $dtPage;
        }
        $queryParams['highlight'] = $id;

        $newQuery = http_build_query($queryParams);
        $baseUrlPart = strtok($returnUrlPost, '?');
        $returnUrlPost = $baseUrlPart . ($newQuery ? '?' . $newQuery : '');

        header("Location: " . $returnUrlPost);
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = "Error al actualizar insumo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: editar.php?id=".$id);
        exit;
    }
}

// Determinar URL de retorno segura
$defaultReturn = 'listar.php';
if ($origen === 'asignados') {
    $defaultReturn = 'listar.php?estado=Asignado';
} elseif ($origen === 'disponibles') {
    $defaultReturn = 'listar.php?estado=Disponible';
} elseif ($origen === 'todos') {
    $defaultReturn = 'listar.php?estado=';
} elseif ($origenEstado !== null) {
    $defaultReturn = 'listar.php?estado=' . urlencode($origenEstado);
}

if (!empty($_GET['return_to'])) {
    $returnUrl = $_GET['return_to'];
} elseif (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'editar.php') === false) {
    // Si viene de ver.php, listar.php u otra pantalla del sistema, preservar la URL exacta de origen
    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (!$refererHost || $refererHost === ($_SERVER['HTTP_HOST'] ?? '')) {
        $returnUrl = $_SERVER['HTTP_REFERER'];
    } else {
        $returnUrl = $defaultReturn;
    }
} elseif (!empty($origen) || $origenEstado !== null) {
    $returnUrl = $defaultReturn;
} else {
    $returnUrl = $defaultReturn;
}

if (strpos($returnUrl, 'editar.php') !== false) {
    $returnUrl = $defaultReturn;
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Insumo</h4>
            <a href="<?php echo htmlspecialchars($returnUrl); ?>" data-return-url="<?php echo htmlspecialchars($returnUrl); ?>" class="btn btn-secondary btn-sm btn-volver"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" id="formInsumo" class="needs-validation" enctype="multipart/form-data" novalidate>
            <?php echo csrf_input(); ?>
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
            <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen); ?>">
            <input type="hidden" name="origen_estado" value="<?php echo htmlspecialchars($origenEstado ?? ''); ?>">
            <input type="hidden" name="id_remito" value="<?php echo (int)$idRemitoContexto; ?>">
            <input type="hidden" name="remito" value="<?php echo htmlspecialchars($numeroRemitoContexto); ?>">
            <input type="hidden" name="dt_page" value="<?php echo (int)$dtPage; ?>">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            <h6 class="mb-0">Tipo de Insumo</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($tipo_insumo); ?>" disabled>
                                <input type="hidden" name="tipo_insumo" value="<?php echo htmlspecialchars($tipo_insumo); ?>">
                                <small class="text-muted">El tipo no se puede cambiar.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-2" id="formulario-campos">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información Básica</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label id="label-nombre-insumo" class="form-label" for="nombre_insumo">
                                    <?php echo $tipo_insumo === 'Varios' ? 'Nombre del Insumo *' : 'Descripción'; ?>
                                </label>
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    id="nombre_insumo"
                                    name="nombre_insumo"
                                    value="<?php echo htmlspecialchars($insumo['nombre_insumo']); ?>"
                                    <?php echo $tipo_insumo === 'Varios' ? 'required' : ''; ?>
                                >
                                <small id="help-nombre-insumo" class="form-text text-muted" style="display: <?php echo $tipo_insumo === 'Varios' ? 'block' : 'none'; ?>;">Insumo + Marca + Modelo + Conexión</small>
                                <div id="invalid-nombre-insumo" class="invalid-feedback"><?php echo $tipo_insumo === 'Varios' ? 'El nombre del insumo es obligatorio' : 'La descripción es opcional'; ?></div>
                            </div>

                            <?php if ($tipo_insumo === 'Varios'): ?>
                                <div id="campos-varios">
                                    <div class="mb-2">
                                        <label class="form-label">Subcategoría</label>
                                        <select class="form-select form-select-sm" name="subcategoria_varios">
                                            <option value="">Seleccione subcategoría</option>
                                            <option value="Hardware" <?php echo $insumo['subcategoria_varios']==='Hardware'?'selected':''; ?>>Hardware</option>
                                            <option value="Periféricos" <?php echo $insumo['subcategoria_varios']==='Periféricos'?'selected':''; ?>>Periféricos</option>
                                            <option value="Red" <?php echo $insumo['subcategoria_varios']==='Red'?'selected':''; ?>>Red</option>
                                        </select>
                                    </div>
                                    <?php if ($estaAsignado): ?>
                                        <?php
                                            $cantAsig = $remitoActivo ? max(0, (int)$remitoActivo['cantidad_asignada'] - (int)$remitoActivo['cantidad_devuelta']) : (int)$insumo['cantidad'];
                                        ?>
                                        <div class="mb-2">
                                            <label class="form-label mb-1">
                                                <i class="fas fa-file-invoice text-primary me-1"></i>Cantidad Asignada <?php echo $remitoActivo ? '(Remito #' . htmlspecialchars($remitoActivo['numero_remito']) . ')' : ''; ?>
                                            </label>
                                            <input type="number" class="form-control form-control-sm bg-light" value="<?php echo $cantAsig; ?>" readonly>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-lock me-1"></i>Esta asignación está fija por remito. No se alteran las cantidades de stock libre desde esta vista.
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label mb-1">
                                                    <i class="fas fa-building text-success"></i> Cantidad Oficina
                                                </label>
                                                <input type="number" class="form-control" id="edit_cantidad_oficina" name="cantidad_oficina" value="<?php echo (int)($insumo['cantidad_oficina'] ?? $insumo['cantidad']); ?>" min="0" required>
                                                <small class="form-text text-muted d-block">Stock para asignaciones</small>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label mb-1">
                                                    <i class="fas fa-warehouse text-primary"></i> Cantidad Depósito
                                                </label>
                                                <input type="number" class="form-control" id="edit_cantidad_deposito" name="cantidad_deposito" value="<?php echo (int)($insumo['cantidad_deposito'] ?? 0); ?>" min="0" required>
                                                <small class="form-text text-muted d-block">Stock de reserva</small>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="alert alert-info py-2 mb-0">
                                                <small>
                                                    <strong>Total:</strong> <span id="edit_cantidad_total"><?php echo (int)$insumo['cantidad']; ?></span> unidades
                                                    (Oficina: <span id="edit_display_oficina"><?php echo (int)($insumo['cantidad_oficina'] ?? $insumo['cantidad']); ?></span> + Depósito: <span id="edit_display_deposito"><?php echo (int)($insumo['cantidad_deposito'] ?? 0); ?></span>)
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-2">
                                        <label class="form-label">Descripción General</label>
                                        <textarea class="form-control form-control-sm" name="descripcion_general" rows="2"><?php echo htmlspecialchars($insumo['descripcion_general'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div id="campos-especificos">
                                    <div id="error-duplicados" class="mb-3" style="display:none;"></div>
                                    <div class="mb-2">
                                        <label class="form-label" for="numero_serie">Número de Serie</label>
                                        <input type="text" class="form-control form-control-sm" name="numero_serie" id="numero_serie" value="<?php echo htmlspecialchars($insumo['numero_serie'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="id_fisico">ID Físico</label>
                                        <input type="text" class="form-control form-control-sm" name="id_fisico" id="id_fisico" value="<?php echo htmlspecialchars($insumo['id_fisico'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="id_patrimonio">ID Patrimonio</label>
                                        <input type="text" class="form-control form-control-sm" name="id_patrimonio" id="id_patrimonio" value="<?php echo htmlspecialchars($insumo['id_patrimonio'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" class="form-control form-control-sm" value="1" readonly>
                                        <small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1 (carga unitaria)</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Información Común</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label">Fecha de Adquisición</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_adquisicion" value="<?php echo htmlspecialchars($insumo['fecha_adquisicion'] ?? ''); ?>" <?php echo !empty($insumo['id_ingreso']) ? 'readonly' : ''; ?>>
                                <?php if (!empty($insumo['id_ingreso'])): ?>
                                    <small class="text-muted">Fecha establecida por el ingreso asociado</small>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($insumo['estado']); ?>" disabled>
                                <small class="text-muted">El estado no se modifica desde esta pantalla.</small>
                            </div>
                            <div class="mb-2" id="campo-punto-almacenamiento" <?php echo ($tipo_insumo === 'Varios') ? 'style="display:none;"' : ''; ?>>
                                <label for="id_punto_stock_actual" class="form-label">Punto de Almacenamiento</label>
                                <select class="form-select form-select-sm" id="id_punto_stock_actual" name="id_punto_stock_actual">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($puntos_stock as $punto): ?>
                                        <option value="<?php echo $punto['id_punto_stock']; ?>" 
                                                <?php echo $insumo['id_punto_stock_actual'] == $punto['id_punto_stock'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($punto['nombre_punto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="es_nuevo" name="es_nuevo" value="1" <?php echo ($insumo['es_nuevo'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="es_nuevo">
                                        <strong>Insumo Nuevo</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="id_ingreso" class="form-label">Tipo de Ingreso</label>
                                <select class="form-select form-select-sm" id="select_tipo_ingreso_edit" name="id_ingreso" onchange="cambiarTipoIngresoEdit()">
                                    <option value="">Sin ingreso asociado</option>
                                    <?php
                                    // Ordenados por más reciente primero
                                    $ingresos = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at FROM ingresos ORDER BY created_at DESC")->fetchAll();
                                    $tipos = ['fondos' => 'Fondos', 'compra_directa' => 'Compra Directa', 'licitacion' => 'Licitación', 'otros' => 'Otros'];
                                    
                                    foreach ($tipos as $tipoKey => $tipoLabel):
                                        $ingresosTipo = array_filter($ingresos, function($ing) use ($tipoKey) {
                                            return $ing['tipo_ingreso'] === $tipoKey;
                                        });
                                        if (count($ingresosTipo) > 0):
                                    ?>
                                        <optgroup label="<?php echo $tipoLabel; ?>">
                                            <?php foreach ($ingresosTipo as $ing): ?>
                                                <option value="<?php echo $ing['id_ingreso']; ?>" 
                                                        data-tipo="<?php echo $ing['tipo_ingreso']; ?>"
                                                        <?php echo ($insumo['id_ingreso'] ?? null) == $ing['id_ingreso'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ing['nro_referencia']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </select>
                                <small class="text-muted" id="help_ingreso_edit">Opcional: Asociar insumo a un ingreso</small>
                            </div>
                            
                            <script>
                            function cambiarTipoIngresoEdit() {
                                const select = document.getElementById('select_tipo_ingreso_edit');
                                const label = document.querySelector('label[for="id_ingreso"]');
                                const help = document.getElementById('help_ingreso_edit');
                                
                                if (!select || !label) return;
                                
                                const selectedOption = select.options[select.selectedIndex];
                                const tipo = selectedOption.getAttribute('data-tipo');
                                
                                if (!tipo) {
                                    label.textContent = 'Tipo de Ingreso';
                                    if (help) help.textContent = 'Opcional: Asociar insumo a un ingreso';
                                    return;
                                }
                                
                                switch(tipo) {
                                    case 'fondos':
                                        label.innerHTML = '<i class="fas fa-money-bill me-1"></i>Nro. de Nota (Fondos)';
                                        if (help) help.textContent = 'Número de nota de fondos';
                                        break;
                                    case 'licitacion':
                                        label.innerHTML = '<i class="fas fa-file-signature me-1"></i>Nro. de Expediente (Licitación)';
                                        if (help) help.textContent = 'Número de expediente de licitación';
                                        break;
                                    case 'compra_directa':
                                        label.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>Nro. de Expediente (Compra Directa)';
                                        if (help) help.textContent = 'Número de expediente de compra directa';
                                        break;
                                    case 'otros':
                                        label.innerHTML = '<i class="fas fa-ellipsis-h me-1"></i>Nro. de Referencia (Otros)';
                                        if (help) help.textContent = 'Número de referencia';
                                        break;
                                }
                            }
                            // Ejecutar al cargar si hay selección
                            $(document).ready(function(){ cambiarTipoIngresoEdit(); });
                            </script>
                        </div>
                    </div>
                </div>

                <?php if ($tipo_insumo !== 'Varios'): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-cogs me-2 text-primary"></i>Especificaciones</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if ($tipo_insumo === 'PC Escritorio'): ?>
                                <div class="mb-2">
                                    <label class="form-label">Procesador</label>
                                    <?php 
                                    $valCpuActual = trim($esp['procesador'] ?? '');
                                    $cpuPcEnCatalogo = false;
                                    foreach ($catalogoCpusPc as $c) {
                                        if (strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuActual) === 0) {
                                            $cpuPcEnCatalogo = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($puedeGestionarCatalogo): ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm select2-hardware select-cpu-catalog" name="procesador">
                                                <option value="">Seleccione procesador...</option>
                                                <?php if ($valCpuActual !== '' && !$cpuPcEnCatalogo): ?>
                                                    <option value="<?php echo htmlspecialchars($valCpuActual); ?>" selected><?php echo htmlspecialchars($valCpuActual); ?> (Actual / No catalogado)</option>
                                                <?php endif; ?>
                                                <?php foreach ($catalogoCpusPc as $c): 
                                                    $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                    $sel = ($valCpuActual !== '' && strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuActual) === 0) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                        <?php echo $valC; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="procesador" data-tipo-equipo="pc" title="Agregar nuevo modelo de Procesador">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm select2-hardware select-cpu-catalog" name="procesador">
                                            <option value="">Seleccione procesador...</option>
                                            <?php if ($valCpuActual !== '' && !$cpuPcEnCatalogo): ?>
                                                <option value="<?php echo htmlspecialchars($valCpuActual); ?>" selected><?php echo htmlspecialchars($valCpuActual); ?> (Actual / No catalogado)</option>
                                            <?php endif; ?>
                                            <?php foreach ($catalogoCpusPc as $c): 
                                                $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                $sel = ($valCpuActual !== '' && strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuActual) === 0) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                    <?php echo $valC; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">RAM (GB)</label>
                                    <input type="number" class="form-control form-control-sm" name="ram_gb" value="<?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                     <label class="form-label">Mother</label>
                                     <?php 
                                     $valMotherActual = trim($esp['mother'] ?? '');
                                     $motherEnCatalogo = false;
                                     foreach ($catalogoMothers as $m) {
                                         if (strcasecmp(trim($m['marca'] . ' ' . $m['modelo']), $valMotherActual) === 0) {
                                             $motherEnCatalogo = true;
                                             break;
                                         }
                                     }
                                     ?>
                                     <?php if ($puedeGestionarCatalogo): ?>
                                         <div class="input-group input-group-sm">
                                             <select class="form-select form-select-sm select2-hardware select-mother-catalog" name="mother">
                                                 <option value="">Seleccione placa madre...</option>
                                                 <?php if ($valMotherActual !== '' && !$motherEnCatalogo): ?>
                                                     <option value="<?php echo htmlspecialchars($valMotherActual); ?>" selected><?php echo htmlspecialchars($valMotherActual); ?> (Actual / No catalogado)</option>
                                                 <?php endif; ?>
                                                 <?php foreach ($catalogoMothers as $m): 
                                                     $valM = htmlspecialchars(trim($m['marca'] . ' ' . $m['modelo']));
                                                     $sel = ($valMotherActual !== '' && strcasecmp(trim($m['marca'] . ' ' . $m['modelo']), $valMotherActual) === 0) ? 'selected' : '';
                                                 ?>
                                                     <option value="<?php echo $valM; ?>" data-ram="<?php echo htmlspecialchars($m['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                         <?php echo $valM; ?>
                                                     </option>
                                                 <?php endforeach; ?>
                                             </select>
                                             <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="motherboard" title="Agregar nuevo modelo de Motherboard">
                                                 <i class="fas fa-plus"></i>
                                             </button>
                                         </div>
                                     <?php else: ?>
                                         <select class="form-select form-select-sm select2-hardware select-mother-catalog" name="mother">
                                             <option value="">Seleccione placa madre...</option>
                                             <?php if ($valMotherActual !== '' && !$motherEnCatalogo): ?>
                                                 <option value="<?php echo htmlspecialchars($valMotherActual); ?>" selected><?php echo htmlspecialchars($valMotherActual); ?> (Actual / No catalogado)</option>
                                             <?php endif; ?>
                                             <?php foreach ($catalogoMothers as $m): 
                                                 $valM = htmlspecialchars(trim($m['marca'] . ' ' . $m['modelo']));
                                                 $sel = ($valMotherActual !== '' && strcasecmp(trim($m['marca'] . ' ' . $m['modelo']), $valMotherActual) === 0) ? 'selected' : '';
                                             ?>
                                                 <option value="<?php echo $valM; ?>" data-ram="<?php echo htmlspecialchars($m['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                     <?php echo $valM; ?>
                                                 </option>
                                             <?php endforeach; ?>
                                         </select>
                                     <?php endif; ?>
                                 </div>
                                 <div class="mb-2">
                                     <label class="form-label">Sistema Operativo</label>
                                     <input type="text" class="form-control form-control-sm" id="sist_op" name="sist_op" value="<?php echo htmlspecialchars($esp['sist_op'] ?? ''); ?>">
                                 </div>
                                 <div class="mb-2">
                                       <label class="form-label">Almacenamiento Principal *</label>
                                       <?php 
                                       $valPc = $esp['almacenamiento_gb'] ?? null;
                                       $opciones = obtenerOpcionesAlmacenamiento();
                                       $valoresEnOpciones = array_column($opciones, 'capacidad_gb');
                                       ?>
                                       <select class="form-select form-select-sm" name="almacenamiento_gb" required>
                                           <option value="">Seleccione capacidad...</option>
                                           <?php foreach ($opciones as $opc): ?>
                                               <option value="<?php echo $opc['capacidad_gb']; ?>" <?php echo ($valPc !== null && (int)$valPc === (int)$opc['capacidad_gb']) ? 'selected' : ''; ?>>
                                                   <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                               </option>
                                           <?php endforeach; ?>
                                           <?php if ($valPc !== null && !in_array((int)$valPc, $valoresEnOpciones)): ?>
                                               <option value="<?php echo $valPc; ?>" selected><?php echo $valPc; ?> GB (Valor actual)</option>
                                           <?php endif; ?>
                                       </select>
                                   </div>
                                   <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                       <?php $esSsdPc = !empty($esp['ssd_o_superior']); ?>
                                       <div class="form-check form-switch mb-0">
                                           <input class="form-check-input switch-disco" type="checkbox" id="ssd_o_superior" name="ssd_o_superior" value="1" data-target-badge="badge_disco_pc_edit" <?php echo $esSsdPc ? 'checked' : ''; ?>>
                                           <label class="form-check-label mb-0" for="ssd_o_superior">
                                               <span id="badge_disco_pc_edit" class="badge <?php echo $esSsdPc ? 'bg-success' : 'bg-secondary'; ?>">
                                                   <i class="fas <?php echo $esSsdPc ? 'fa-bolt' : 'fa-hdd'; ?> me-1"></i><?php echo $esSsdPc ? 'SSD' : 'HDD'; ?>
                                               </span>
                                           </label>
                                       </div>
                                   </div>
                                   <div class="mb-2">
                                       <label class="form-label">Almacenamiento Secundario <span class="text-muted fw-normal">(Opcional)</span></label>
                                       <?php $valPcSec = $esp['almacenamiento_secundario_gb'] ?? null; ?>
                                       <select class="form-select form-select-sm" name="almacenamiento_secundario_gb">
                                           <option value="">Sin almacenamiento secundario</option>
                                           <?php foreach ($opciones as $opc): ?>
                                               <option value="<?php echo $opc['capacidad_gb']; ?>" <?php echo ($valPcSec !== null && (int)$valPcSec === (int)$opc['capacidad_gb']) ? 'selected' : ''; ?>>
                                                   <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                               </option>
                                           <?php endforeach; ?>
                                           <?php if ($valPcSec !== null && !in_array((int)$valPcSec, $valoresEnOpciones)): ?>
                                               <option value="<?php echo $valPcSec; ?>" selected><?php echo $valPcSec; ?> GB (Valor actual)</option>
                                           <?php endif; ?>
                                       </select>
                                   </div>
                                   <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                       <?php $esSsdPcSec = !empty($esp['ssd_secundario']); ?>
                                       <div class="form-check form-switch mb-0">
                                           <input class="form-check-input switch-disco" type="checkbox" id="ssd_secundario" name="ssd_secundario" value="1" data-target-badge="badge_disco_pc_sec_edit" <?php echo $esSsdPcSec ? 'checked' : ''; ?>>
                                           <label class="form-check-label mb-0" for="ssd_secundario">
                                               <span id="badge_disco_pc_sec_edit" class="badge <?php echo $esSsdPcSec ? 'bg-success' : 'bg-secondary'; ?>">
                                                   <i class="fas <?php echo $esSsdPcSec ? 'fa-bolt' : 'fa-hdd'; ?> me-1"></i><?php echo $esSsdPcSec ? 'SSD' : 'HDD'; ?>
                                               </span>
                                           </label>
                                       </div>
                                   </div>
                            <?php elseif ($tipo_insumo === 'Notebook'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_notebook" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_notebook" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                                <div class="mb-2">
                                    <label class="form-label">Procesador</label>
                                    <?php 
                                    $valCpuNbActual = trim($esp['procesador'] ?? '');
                                    $cpuNbEnCatalogo = false;
                                    foreach ($catalogoCpusNb as $c) {
                                        if (strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuNbActual) === 0) {
                                            $cpuNbEnCatalogo = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($puedeGestionarCatalogo): ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm select2-hardware select-cpu-catalog" name="procesador_notebook">
                                                <option value="">Seleccione procesador...</option>
                                                <?php if ($valCpuNbActual !== '' && !$cpuNbEnCatalogo): ?>
                                                    <option value="<?php echo htmlspecialchars($valCpuNbActual); ?>" selected><?php echo htmlspecialchars($valCpuNbActual); ?> (Actual / No catalogado)</option>
                                                <?php endif; ?>
                                                <?php foreach ($catalogoCpusNb as $c): 
                                                    $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                    $sel = ($valCpuNbActual !== '' && strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuNbActual) === 0) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                        <?php echo $valC; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-nuevo-hardware" type="button" data-tipo="procesador" data-tipo-equipo="notebook" title="Agregar nuevo modelo de Procesador">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm select2-hardware select-cpu-catalog" name="procesador_notebook">
                                            <option value="">Seleccione procesador...</option>
                                            <?php if ($valCpuNbActual !== '' && !$cpuNbEnCatalogo): ?>
                                                <option value="<?php echo htmlspecialchars($valCpuNbActual); ?>" selected><?php echo htmlspecialchars($valCpuNbActual); ?> (Actual / No catalogado)</option>
                                            <?php endif; ?>
                                            <?php foreach ($catalogoCpusNb as $c): 
                                                $valC = htmlspecialchars(trim($c['marca'] . ' ' . $c['modelo']));
                                                $sel = ($valCpuNbActual !== '' && strcasecmp(trim($c['marca'] . ' ' . $c['modelo']), $valCpuNbActual) === 0) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $valC; ?>" data-ram="<?php echo htmlspecialchars($c['tipo_ram']); ?>" <?php echo $sel; ?>>
                                                    <?php echo $valC; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2"><label class="form-label">RAM (GB)</label><input type="number" class="form-control form-control-sm" name="ram_gb_notebook" value="<?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?>"></div>
                                <div class="mb-2">
                                    <label class="form-label">Almacenamiento *</label>
                                    <?php 
                                    $valNb = $esp['almacenamiento_gb'] ?? null;
                                    if (!isset($opciones)) {
                                        $opciones = obtenerOpcionesAlmacenamiento();
                                        $valoresEnOpciones = array_column($opciones, 'capacidad_gb');
                                    }
                                    ?>
                                    <select class="form-select form-select-sm" name="almacenamiento_gb_notebook" required>
                                        <option value="">Seleccione capacidad...</option>
                                        <?php foreach ($opciones as $opc): ?>
                                            <option value="<?php echo $opc['capacidad_gb']; ?>" <?php echo ($valNb !== null && (int)$valNb === (int)$opc['capacidad_gb']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($opc['etiqueta']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($valNb !== null && !in_array((int)$valNb, $valoresEnOpciones)): ?>
                                            <option value="<?php echo $valNb; ?>" selected><?php echo $valNb; ?> GB (Valor actual)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="mb-2 d-flex justify-content-end align-items-center" style="min-height: 31px;">
                                    <?php $esSsdNb = !empty($esp['ssd_o_superior']); ?>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input switch-disco" type="checkbox" id="ssd_o_superior_notebook" name="ssd_o_superior_notebook" value="1" data-target-badge="badge_disco_nb_edit" <?php echo $esSsdNb ? 'checked' : ''; ?>>
                                        <label class="form-check-label mb-0" for="ssd_o_superior_notebook">
                                            <span id="badge_disco_nb_edit" class="badge <?php echo $esSsdNb ? 'bg-success' : 'bg-secondary'; ?>">
                                                <i class="fas <?php echo $esSsdNb ? 'fa-bolt' : 'fa-hdd'; ?> me-1"></i><?php echo $esSsdNb ? 'SSD' : 'HDD'; ?>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="cargador" name="cargador" value="1" <?php echo !empty($esp['cargador']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cargador">Cargador</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="funda" name="funda" value="1" <?php echo !empty($esp['funda']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="funda">Funda</label>
                                </div>
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="micro_sd" name="micro_sd" value="1" <?php echo !empty($esp['micro_sd']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="micro_sd">Micro SD</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <input type="number" min="0" step="1" class="form-control form-control-sm" id="micro_sd_gb" name="micro_sd_gb" placeholder="Tamaño (GB)" value="<?php echo isset($esp['micro_sd_gb']) ? (int)$esp['micro_sd_gb'] : ''; ?>" <?php echo !empty($esp['micro_sd']) ? '' : 'disabled'; ?>>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="caja" name="caja" value="1" <?php echo !empty($esp['caja']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="caja">Caja</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="adaptador_red" name="adaptador_red" value="1" <?php echo !empty($esp['adaptador_red']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="adaptador_red">Adaptador de red</label>
                                </div>
                            <?php elseif ($tipo_insumo === 'Impresora'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_impresora" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_impresora" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                            <?php elseif ($tipo_insumo === 'Monitor'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_monitor" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_monitor" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Pulgadas</label><input type="number" step="0.1" class="form-control form-control-sm" name="pulgadas" value="<?php echo htmlspecialchars($esp['pulgadas'] ?? ''); ?>"></div>
                                <div class="mb-2">
                                    <label class="form-label">Conexión</label>
                                    <select class="form-select form-select-sm" name="conexion_monitor">
                                        <?php $cx = $esp['conexion'] ?? ''; ?>
                                        <option value="">Seleccione</option>
                                        <option value="VGA" <?php echo $cx==='VGA'?'selected':''; ?>>VGA</option>
                                        <option value="HDMI" <?php echo $cx==='HDMI'?'selected':''; ?>>HDMI</option>
                                        <option value="Ambas" <?php echo $cx==='Ambas'?'selected':''; ?>>Ambas</option>
                                    </select>
                                </div>
                            <?php elseif ($tipo_insumo === 'Escaner'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_escaner" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_escaner" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($tipo_insumo === 'Notebook' && !empty($remitoActivo)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-warning alert-permanent p-3 rounded shadow-sm mb-0">
                            <label for="declaracion_jurada" class="form-label mb-1 fw-bold">
                                <i class="fas fa-file-pdf me-2 text-warning"></i>Declaración Jurada (Opcional)
                            </label>
                            <p class="small mb-2 text-dark">Ha seleccionado una Notebook. Modo Histórico: Puede adjuntar la declaración jurada si dispone de ella.</p>
                            
                            <?php if (!empty($remitoActivo['declaracion_jurada'])): ?>
                                <div class="d-flex align-items-center mb-2 gap-2">
                                    <a href="<?php echo app_base_url(); ?>/uploads/documentos/<?php echo htmlspecialchars($remitoActivo['declaracion_jurada']); ?>" 
                                       data-visor-archivo="<?php echo app_base_url(); ?>/uploads/documentos/<?php echo htmlspecialchars($remitoActivo['declaracion_jurada']); ?>"
                                       data-visor-titulo="DDJJ Asignación Actual"
                                       target="_blank" class="btn btn-xs btn-outline-success py-1 px-2 fw-bold">
                                        <i class="fas fa-eye me-1"></i>Ver actual
                                    </a>
                                    <div class="form-check form-switch mb-0 ms-2">
                                        <input class="form-check-input" type="checkbox" id="eliminar_dj" name="eliminar_dj" value="1">
                                        <label class="form-check-label text-danger fw-bold mb-0 small" for="eliminar_dj">Eliminar</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" class="form-control form-control-sm bg-white" name="declaracion_jurada" id="declaracion_jurada" accept=".pdf">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="<?php echo htmlspecialchars($returnUrl); ?>" data-return-url="<?php echo htmlspecialchars($returnUrl); ?>" class="btn btn-secondary btn-volver"><i class="fas fa-times me-2"></i>Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($puedeGestionarCatalogo): ?>
    <?php include __DIR__ . '/modal_nuevo_hardware.php'; ?>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
<script>
$(document).ready(function() {
    $('.select2-hardware').select2({
        theme: 'bootstrap-5',
        language: 'es',
        width: '100%'
    });
});

(function(){
  const micro = document.getElementById('micro_sd');
  const gb = document.getElementById('micro_sd_gb');
  if (micro && gb) {
    micro.addEventListener('change', function(){
      if (this.checked) {
        gb.removeAttribute('disabled');
      } else {
        gb.value = '';
        gb.setAttribute('disabled','disabled');
      }
    });
  }
})();

// Calcular total automático para stock dual en edición (Varios)
function actualizarTotalCantidadEdit() {
    const oficina = parseInt($('#edit_cantidad_oficina').val()) || 0;
    const deposito = parseInt($('#edit_cantidad_deposito').val()) || 0;
    const total = oficina + deposito;
    
    $('#edit_cantidad_total').text(total);
    $('#edit_display_oficina').text(oficina);
    $('#edit_display_deposito').text(deposito);
}

// Eventos para actualizar total en tiempo real
$(document).on('input change', '#edit_cantidad_oficina, #edit_cantidad_deposito', actualizarTotalCantidadEdit);

// Actualizar al cargar la página
$(document).ready(function() {
    if ($('#edit_cantidad_oficina').length) {
        actualizarTotalCantidadEdit();
    }
});

// Validación en tiempo real de duplicados (Serie, ID Físico, Patrimonio)
let timeoutValidacionEdit = null;
const idInsumoActual = <?php echo (int)$id; ?>;

function validarDuplicadosEdit() {
    clearTimeout(timeoutValidacionEdit);
    const numeroSerie = $('#numero_serie').val()?.trim() || '';
    const idFisico = $('#id_fisico').val()?.trim() || '';
    const idPatrimonio = $('#id_patrimonio').val()?.trim() || '';

    if (!numeroSerie && !idFisico && !idPatrimonio) {
        $('#error-duplicados').hide().empty();
        $('button[type="submit"]').prop('disabled', false);
        return;
    }

    timeoutValidacionEdit = setTimeout(function() {
        $.ajax({
            url: (typeof getAppBase === 'function' ? getAppBase() : '') + '/ajax/validar_insumo_unico.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                numero_serie: numeroSerie,
                id_fisico: idFisico,
                id_patrimonio: idPatrimonio,
                id_insumo_excluir: idInsumoActual
            }),
            success: function(resp) {
                if (!resp.valido && resp.errores && resp.errores.length > 0) {
                    $('#error-duplicados').html(
                        '<div class="alert alert-danger py-2 mb-2"><i class="fas fa-exclamation-triangle me-2"></i>' +
                        resp.errores.join('<br>') +
                        '</div>'
                    ).show();
                    $('button[type="submit"]').prop('disabled', true);
                } else {
                    $('#error-duplicados').hide().empty();
                    $('button[type="submit"]').prop('disabled', false);
                }
            },
            error: function() {
                console.error('Error al validar duplicados en edición');
            }
        });
    }, 400);
}

$(document).on('input blur', '#numero_serie, #id_fisico, #id_patrimonio', validarDuplicadosEdit);
</script>