<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Detectar si venimos de un ingreso específico
$fromIngreso = isset($_GET['from']) && $_GET['from'] === 'ingreso';
$returnToId = isset($_GET['return_to_id']) ? (int)$_GET['return_to_id'] : 0;

// Si venimos de un ingreso, obtener sus datos
$ingresoData = null;
if ($fromIngreso && $returnToId > 0) {
    // Usar DATE() para obtener solo la fecha sin hora
    $stmt = $conexion->prepare('SELECT id_ingreso, tipo_ingreso, nro_referencia, DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos WHERE id_ingreso = ?');
    $stmt->execute([$returnToId]);
    $ingresoData = $stmt->fetch();
}

// Obtener datos para los select
$stmt = $conexion->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto");
$puntos_stock = $stmt->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF
    if (!verify_csrf()) {
        $_SESSION['mensaje'] = 'CSRF inválido';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar.php');
        exit;
    }
    error_log('Formulario POST recibido en agregar.php');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Verificar campos requeridos
    // tipo_insumo siempre es obligatorio
    if (!isset($_POST['tipo_insumo']) || empty($_POST['tipo_insumo'])) {
        error_log('Campo requerido faltante: tipo_insumo');
        $_SESSION['mensaje'] = "Error: Debe seleccionar un tipo de insumo";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: agregar.php");
        exit;
    }
    
    // nombre_insumo solo es obligatorio para tipo "Varios"
    $tipo_insumo = $_POST['tipo_insumo'];
    if ($tipo_insumo === 'Varios' && (!isset($_POST['nombre_insumo']) || empty($_POST['nombre_insumo']))) {
        error_log('Campo requerido faltante: nombre_insumo (tipo Varios)');
        $_SESSION['mensaje'] = "Error: El nombre del insumo es obligatorio para tipo Varios";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: agregar.php");
        exit;
    }
    
    try {
        $conexion->beginTransaction();
        
        $tipo_insumo = $_POST['tipo_insumo'];
        
        // Configurar cantidad según tipo
        if ($tipo_insumo == 'Varios') {
            // Stock dual para tipo Varios
            $cantidadOficina = isset($_POST['cantidad_oficina']) ? (int)$_POST['cantidad_oficina'] : 0;
            $cantidadDeposito = isset($_POST['cantidad_deposito']) ? (int)$_POST['cantidad_deposito'] : 0;
            $cantidad = $cantidadOficina + $cantidadDeposito;
        } else {
            $cantidad = $_POST['cantidad_especifica'] ?: 1;
            $cantidadOficina = null;
            $cantidadDeposito = null;
        }
        // Validación backend: exigir ID Patrimonio para no "Varios"
        if ($tipo_insumo != 'Varios') {
            $idPat = isset($_POST['id_patrimonio']) ? trim((string)$_POST['id_patrimonio']) : '';
            if ($idPat === '') {
                $_SESSION['mensaje'] = 'Error: El ID Patrimonio es obligatorio para este tipo de insumo.';
                $_SESSION['tipo_mensaje'] = 'danger';
                header('Location: agregar.php');
                exit;
            }
        }
        
        // Validar que no existan duplicados de número de serie, ID físico o ID patrimonio
        $numero_serie = ($tipo_insumo != 'Varios') ? ($_POST['numero_serie'] ?: null) : null;
        $id_fisico = ($tipo_insumo != 'Varios') ? ($_POST['id_fisico'] ?: null) : null;
        $id_patrimonio = ($tipo_insumo != 'Varios') ? ($_POST['id_patrimonio'] ?: null) : null;
        
        $validacion = validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, null, $conexion);
        if (!$validacion['valido']) {
            $_SESSION['mensaje'] = 'Error: ' . implode('. ', $validacion['errores']);
            $_SESSION['tipo_mensaje'] = 'danger';
            
            // Preservar datos del formulario
            if ($fromIngreso && $returnToId > 0) {
                header('Location: agregar.php?from=ingreso&return_to_id=' . $returnToId);
            } else {
                header('Location: agregar.php');
            }
            exit;
        }
        
        // Insertar insumo principal
        $sql = "INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, 
                                   numero_serie, id_fisico, id_patrimonio, cantidad, cantidad_oficina, cantidad_deposito,
                                   fecha_adquisicion, estado, id_punto_stock_actual, id_ingreso, es_nuevo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $esNuevo = isset($_POST['es_nuevo']) && $_POST['es_nuevo'] == '1' ? 1 : 0;
        
        // Si venimos de un ingreso, usar el return_to_id y la fecha del ingreso
        $fromPost = isset($_POST['from']) ? $_POST['from'] : '';
        $returnToIdPost = isset($_POST['return_to_id']) ? (int)$_POST['return_to_id'] : 0;
        
        if ($fromPost === 'ingreso' && $returnToIdPost > 0) {
            // Obtener datos del ingreso
            $stmtIng = $conexion->prepare('SELECT DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos WHERE id_ingreso = ?');
            $stmtIng->execute([$returnToIdPost]);
            $ingreso = $stmtIng->fetch();
            
            $idIngreso = $returnToIdPost; // Asignar automáticamente al ingreso
            // Usar fecha exacta del ingreso (formato YYYY-MM-DD sin hora)
            $fechaAdquisicion = !empty($ingreso['fecha_finalizacion']) ? $ingreso['fecha_finalizacion'] : null;
            
            error_log("Insumo desde ingreso - Fecha finalizacion: " . ($ingreso['fecha_finalizacion'] ?? 'NULL'));
            error_log("Fecha adquisicion asignada: " . ($fechaAdquisicion ?? 'NULL'));
        } else {
            // Modo normal: usar lo que viene del form
            $idIngreso = !empty($_POST['id_ingreso']) ? (int)$_POST['id_ingreso'] : null;
            $fechaAdquisicion = $_POST['fecha_adquisicion'] ?: null;
            
            // Si se asigna manualmente un ingreso, usar su fecha de finalización
            if ($idIngreso) {
                $stmtIng = $conexion->prepare('SELECT DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos WHERE id_ingreso = ?');
                $stmtIng->execute([$idIngreso]);
                $ingreso = $stmtIng->fetch();
                if ($ingreso && !empty($ingreso['fecha_finalizacion'])) {
                    $fechaAdquisicion = $ingreso['fecha_finalizacion'];
                    error_log("Ingreso asignado manualmente - Fecha: " . $fechaAdquisicion);
                }
            }
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            $_POST['nombre_insumo'] ?: null,
            $tipo_insumo,
            ($tipo_insumo == 'Varios') ? ($_POST['subcategoria_varios'] ?: null) : null,
            $_POST['descripcion_general'] ?: null,
            ($tipo_insumo != 'Varios') ? ($_POST['numero_serie'] ?: null) : null,
            ($tipo_insumo != 'Varios') ? ($_POST['id_fisico'] ?: null) : null,
            ($tipo_insumo != 'Varios') ? ($_POST['id_patrimonio'] ?: null) : null,
            $cantidad,
            $cantidadOficina,
            $cantidadDeposito,
            $fechaAdquisicion,
            'Disponible', // Estado inicial siempre disponible
            $_POST['id_punto_stock_actual'] ?: 2, // Por defecto Depósito
            $idIngreso, // Ingreso asociado
            $esNuevo // 1=Nuevo, 0=Usado
        ]);
        
        $id_insumo = $conexion->lastInsertId();
        
        // Insertar datos específicos según el tipo (solo para tipos que no son "Varios")
        if ($tipo_insumo != 'Varios') {
            switch ($tipo_insumo) {
                case 'PC Completa':
                case 'PC Escritorio':
                    if (!empty($_POST['procesador']) || !empty($_POST['ram_gb']) || !empty($_POST['almacenamiento_gb']) || !empty($_POST['mother']) || !empty($_POST['sist_op'])) {
                        $sql = "INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother, sist_op) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([
                            $id_insumo,
                            $_POST['procesador'] ?: null,
                            $_POST['ram_gb'] ?: null,
                            $_POST['almacenamiento_gb'] ?: null,
                            $_POST['mother'] ?: null,
                            $_POST['sist_op'] ?: null
                        ]);
                    }
                    break;
                    
                case 'Notebook':
                    if (!empty($_POST['marca_notebook']) || !empty($_POST['modelo_notebook'])) {
                        $sql = "INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb, cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $cargador = isset($_POST['cargador']) ? 1 : 0;
                        $funda = isset($_POST['funda']) ? 1 : 0;
                        $microSd = isset($_POST['micro_sd']) ? 1 : 0;
                        $microSdGb = $microSd ? (($_POST['micro_sd_gb'] !== '' ? (int)$_POST['micro_sd_gb'] : null)) : null;
                        $caja = isset($_POST['caja']) ? 1 : 0;
                        $adaptadorRed = isset($_POST['adaptador_red']) ? 1 : 0;
                        $stmt->execute([
                            $id_insumo,
                            $_POST['marca_notebook'] ?: null,
                            $_POST['modelo_notebook'] ?: null,
                            $_POST['procesador_notebook'] ?: null,
                            $_POST['ram_gb_notebook'] ?: null,
                            $_POST['almacenamiento_gb_notebook'] ?: null,
                            $cargador,
                            $funda,
                            $microSd,
                            $microSdGb,
                            $caja,
                            $adaptadorRed
                        ]);
                    }
                    break;
                    
                case 'Impresora':
                    if (!empty($_POST['marca_impresora']) || !empty($_POST['modelo_impresora'])) {
                        $sql = "INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_impresora'], $_POST['modelo_impresora']]);
                    }
                    break;
                    
                case 'Monitor':
                    if (!empty($_POST['marca_monitor']) || !empty($_POST['modelo_monitor'])) {
                        $sql = "INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_monitor'], $_POST['modelo_monitor'], $_POST['pulgadas'] ?: null, $_POST['conexion_monitor']]);
                    }
                    break;
                    
                case 'Escaner':
                    if (!empty($_POST['marca_escaner']) || !empty($_POST['modelo_escaner'])) {
                        $sql = "INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_escaner'], $_POST['modelo_escaner']]);
                    }
                    break;
            }
        }
        
        $conexion->commit();
        
        error_log('Insumo agregado correctamente, redirigiendo a listar.php');
        error_log('ID del insumo insertado: ' . $id_insumo);
        $_SESSION['mensaje'] = "Insumo agregado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        
        // Redirección condicional: volver a ingreso (pasos) si corresponde
        $from = isset($_GET['from']) ? $_GET['from'] : (isset($_POST['from']) ? $_POST['from'] : '');
        $returnToId = isset($_GET['return_to_id']) ? (int)$_GET['return_to_id'] : (isset($_POST['return_to_id']) ? (int)$_POST['return_to_id'] : 0);
        
        if ($from === 'ingreso') {
            // Redirigir a ingresos_editar.php con parámetro from=agregar
            $url = app_base_url() . '/pages/insumos/ingresos_editar.php?from=agregar&added_id=' . urlencode($id_insumo);
            if ($returnToId > 0) {
                $url .= '&id=' . $returnToId;
            }
            header('Location: ' . $url);
        } elseif ($from === 'licitacion' || $from === 'ingreso') {
            // Redirigir a edición de ingreso
            header('Location: ' . app_base_url() . '/pages/insumos/ingresos_editar.php?from=agregar&added_id=' . urlencode($id_insumo));
        } else {
            header('Location: listar.php');
        }
        exit;
        
    } catch (Exception $e) {
        $conexion->rollBack();
        error_log('Error al agregar insumo: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $_SESSION['mensaje'] = "Error al agregar insumo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
        
        // Asegurar que no haya salida antes del header
        if (headers_sent()) {
            error_log('Headers ya enviados, usando JavaScript para redirección');
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href = 'agregar.php';</script>";
        } else {
            header("Location: agregar.php");
        }
        exit;
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fas fa-plus me-2"></i>Agregar Insumo
            </h4>
            <?php
            // Botón volver inteligente: vuelve al ingreso si corresponde
            if ($fromIngreso && $returnToId > 0) {
                $urlVolver = 'ingresos_editar.php?id=' . $returnToId;
            } else {
                $urlVolver = 'listar.php';
            }
            ?>
            <a href="<?php echo $urlVolver; ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" id="formInsumo" class="needs-validation" novalidate action="agregar.php">
            <?php
              $from = isset($_GET['from']) ? $_GET['from'] : '';
              $returnToId = isset($_GET['return_to_id']) ? (int)$_GET['return_to_id'] : 0;
              
              if ($from === 'ingreso') {
                  echo '<input type="hidden" name="from" value="ingreso">';
                  if ($returnToId > 0) {
                      echo '<input type="hidden" name="return_to_id" value="' . $returnToId . '">';
                  }
              } elseif ($from === 'licitacion') {
                  echo '<input type="hidden" name="from" value="licitacion">';
              }
            ?>
            <?php echo csrf_input(); ?>
            <!-- Selección de tipo de insumo -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            <h6 class="mb-0">Tipo de Insumo *</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-select" id="tipo_insumo" name="tipo_insumo" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="Varios">Varios</option>
                                    <option value="PC Escritorio">PC Escritorio</option>
                                    <option value="Notebook">Notebook</option>
                                    <option value="Impresora">Impresora</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Escaner">Escaner</option>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar un tipo de insumo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario en 3 columnas -->
            <div class="row g-2" id="formulario-campos" style="display: none;">
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
                                <label id="label-nombre-insumo" for="nombre_insumo" class="form-label">Nombre del Insumo *</label>
                                <input type="text" class="form-control form-control-sm w-100" id="nombre_insumo" name="nombre_insumo" required>
                                <small id="help-nombre-insumo" class="form-text text-muted" style="display: none;">Insumo + Marca + Modelo + Conexión</small>
                                <div id="invalid-nombre-insumo" class="invalid-feedback">El nombre del insumo es obligatorio</div>
                            </div>
                            
                            <!-- Campos específicos para tipo "Varios" -->
                            <div id="campos-varios" style="display: none;">
                                <div class="mb-2">
                                    <label for="subcategoria_varios" class="form-label">Subcategoría</label>
                                    <select class="form-select form-select-sm w-100" id="subcategoria_varios" name="subcategoria_varios">
                                        <option value="">Seleccione subcategoría</option>
                                        <option value="Hardware">Hardware</option>
                                        <option value="Periféricos">Periféricos</option>
                                        <option value="Red">Red</option>
                                    </select>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label for="cantidad_oficina" class="form-label">
                                            <i class="fas fa-building text-success"></i> Cantidad Oficina *
                                        </label>
                                        <input type="number" class="form-control form-control-sm" id="cantidad_oficina" name="cantidad_oficina" value="0" min="0" required>
                                        <small class="form-text text-muted">Stock para asignaciones</small>
                                    </div>
                                    <div class="col-6">
                                        <label for="cantidad_deposito" class="form-label">
                                            <i class="fas fa-warehouse text-primary"></i> Cantidad Depósito *
                                        </label>
                                        <input type="number" class="form-control form-control-sm" id="cantidad_deposito" name="cantidad_deposito" value="0" min="0" required>
                                        <small class="form-text text-muted">Stock de reserva</small>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="alert alert-info py-2 mb-0">
                                        <small>
                                            <strong>Total:</strong> <span id="cantidad_total">0</span> unidades
                                            (Oficina: <span id="display_oficina">0</span> + Depósito: <span id="display_deposito">0</span>)
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="descripcion_general" class="form-label">Descripción General</label>
                                    <textarea class="form-control form-control-sm w-100" id="descripcion_general" name="descripcion_general" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos específicos para otros tipos -->
                            <div id="campos-especificos" style="display: none;">
                                <div class="mb-2">
                                    <label for="numero_serie" class="form-label">Número de Serie</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="numero_serie" name="numero_serie">
                                </div>
                                
                                <div class="mb-2">
                                    <label for="id_fisico" class="form-label">ID Físico *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="id_fisico" name="id_fisico" required>
                                    <div class="invalid-feedback">El ID físico es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="id_patrimonio" class="form-label">ID Patrimonio *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="id_patrimonio" name="id_patrimonio" required>
                                    <div class="invalid-feedback">El ID patrimonio es obligatorio</div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="cantidad_especifica" class="form-label">Cantidad</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="cantidad_especifica" name="cantidad_especifica" value="1" min="1" readonly>
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
                                <label for="fecha_adquisicion" class="form-label">Fecha de Adquisición</label>
                                <?php
                                // Si venimos de un ingreso, usar su fecha de finalización SIN conversión
                                $fechaDefault = date('Y-m-d');
                                if ($fromIngreso && $ingresoData && !empty($ingresoData['fecha_finalizacion'])) {
                                    // Usar la fecha tal cual viene de la BD (formato YYYY-MM-DD)
                                    $fechaDefault = $ingresoData['fecha_finalizacion'];
                                }
                                ?>
                                <input type="date" class="form-control form-control-sm w-100" id="fecha_adquisicion" name="fecha_adquisicion" value="<?php echo htmlspecialchars($fechaDefault); ?>" <?php echo ($fromIngreso && $ingresoData) ? 'readonly' : ''; ?>>
                                <?php if ($fromIngreso && $ingresoData): ?>
                                    <small class="text-muted">Fecha del ingreso: <?php echo htmlspecialchars($fechaDefault); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-2">
                                <label for="id_punto_stock_actual" class="form-label">Punto de Almacenamiento *</label>
                                <select class="form-select form-select-sm w-100" id="id_punto_stock_actual" name="id_punto_stock_actual" required>
                                    <option value="">Seleccione punto de almacenamiento</option>
                                    <?php foreach ($puntos_stock as $punto): ?>
                                        <option value="<?php echo $punto['id_punto_stock']; ?>" 
                                                <?php echo $punto['id_punto_stock'] == 2 ? 'selected' : ''; ?>>
                                            <?php echo $punto['nombre_punto']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar un punto de almacenamiento</div>
                            </div>
                            
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="es_nuevo" name="es_nuevo" value="1" checked>
                                    <label class="form-check-label" for="es_nuevo">
                                        <strong>Insumo Nuevo</strong> <small class="text-muted">(desmarcar si es usado)</small>
                                    </label>
                                </div>
                            </div>
                            
                            <?php if ($fromIngreso && $ingresoData): ?>
                                <!-- Cuando venimos de un ingreso, mostrar info pero no permitir cambio -->
                                <div class="mb-0">
                                    <label class="form-label">Ingreso Asociado</label>
                                    <div class="alert alert-info mb-0 py-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong><?php 
                                            $tipoLabel = match($ingresoData['tipo_ingreso']) {
                                                'fondos' => 'Fondos',
                                                'compra_directa' => 'Compra Directa',
                                                'licitacion' => 'Licitación',
                                                'otros' => 'Otros',
                                                default => 'Ingreso'
                                            };
                                            echo $tipoLabel;
                                        ?>:</strong> <?php echo htmlspecialchars($ingresoData['nro_referencia']); ?>
                                    </div>
                                    <small class="text-muted">Este insumo se asignará automáticamente a este ingreso</small>
                                </div>
                            <?php else: ?>
                                <!-- Modo normal: mostrar select de ingreso -->
                                <div class="mb-0">
                                    <label for="id_ingreso" class="form-label">Tipo de Ingreso</label>
                                    <select class="form-select form-select-sm w-100" id="select_tipo_ingreso" name="id_ingreso" onchange="cambiarTipoIngreso()">
                                        <option value="">Sin ingreso asociado</option>
                                        <?php
                                        // Agrupar por tipo de ingreso, ordenados por más reciente primero
                                        $ingresos = $conexion->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at FROM ingresos ORDER BY created_at DESC")->fetchAll();
                                        $tipos = ['fondos' => 'Fondos', 'compra_directa' => 'Compra Directa', 'licitacion' => 'Licitación', 'otros' => 'Otros'];
                                        
                                        foreach ($tipos as $tipoKey => $tipoLabel):
                                            $ingresosTipo = array_filter($ingresos, function($ing) use ($tipoKey) {
                                                return $ing['tipo_ingreso'] === $tipoKey;
                                            });
                                            if (count($ingresosTipo) > 0):
                                        ?>
                                            <optgroup label="<?php echo $tipoLabel; ?>">
                                                <?php foreach ($ingresosTipo as $ing): ?>
                                                    <option value="<?php echo $ing['id_ingreso']; ?>" data-tipo="<?php echo $ing['tipo_ingreso']; ?>">
                                                        <?php echo htmlspecialchars($ing['nro_referencia']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </select>
                                    <small class="text-muted" id="help_ingreso">Opcional: Asociar insumo a un ingreso (Fondos, Licitación, etc.)</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Extras Notebook: tarjeta independiente debajo de Información Común -->
                    <div class="card border-0 shadow-sm mt-2" id="extras-notebook" style="display:none;">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-laptop me-2 text-primary"></i>Accesorios Notebook</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cargador" name="cargador" value="1">
                                <label class="form-check-label" for="cargador">Cargador</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="funda" name="funda" value="1">
                                <label class="form-check-label" for="funda">Funda</label>
                            </div>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="micro_sd" name="micro_sd" value="1">
                                        <label class="form-check-label" for="micro_sd">Micro SD</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="number" min="0" step="1" class="form-control form-control-sm" id="micro_sd_gb" name="micro_sd_gb" placeholder="Tamaño (GB)" disabled>
                                </div>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="caja" name="caja" value="1">
                                <label class="form-check-label" for="caja">Caja</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="adaptador_red" name="adaptador_red" value="1">
                                <label class="form-check-label" for="adaptador_red">Adaptador de red</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- COLUMNA 3: Especificaciones -->
                <div class="col-md-4" id="columna-especificaciones">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-microchip me-2 text-primary"></i>Especificaciones
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Campos para PC Escritorio -->
                            <div class="campos-especificos" id="campos-pc" style="display: none;">
                                <div class="mb-2">
                                    <label for="procesador" class="form-label">Procesador *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="procesador" name="procesador" required>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="ram_gb" class="form-label">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="ram_gb" name="ram_gb" min="1" required>
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="almacenamiento_gb" class="form-label">Almacenamiento (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb" name="almacenamiento_gb" min="1" required>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="mother" class="form-label">Motherboard *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="mother" name="mother" required>
                                    <div class="invalid-feedback">La motherboard es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="sist_op" class="form-label">Sistema Operativo</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="sist_op" name="sist_op" placeholder="Ej: Windows 11 Pro">
                                </div>
                            </div>
                            
                            <!-- Campos para Notebook -->
                            <div class="campos-especificos" id="campos-notebook" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_notebook" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_notebook" name="marca_notebook" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_notebook" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_notebook" name="modelo_notebook" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="procesador_notebook" class="form-label">Procesador *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="procesador_notebook" name="procesador_notebook" required>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="ram_gb_notebook" class="form-label">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="ram_gb_notebook" name="ram_gb_notebook" min="1" required>
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="almacenamiento_gb_notebook" class="form-label">Almacenamiento (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb_notebook" name="almacenamiento_gb_notebook" min="1" required>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Impresora -->
                            <div class="campos-especificos" id="campos-impresora" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_impresora" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_impresora" name="marca_impresora" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_impresora" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_impresora" name="modelo_impresora" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Monitor -->
                            <div class="campos-especificos" id="campos-monitor" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_monitor" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_monitor" name="marca_monitor" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_monitor" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_monitor" name="modelo_monitor" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="pulgadas" class="form-label">Pulgadas *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="pulgadas" name="pulgadas" step="0.1" min="1" required>
                                    <div class="invalid-feedback">Las pulgadas son obligatorias</div>
                                </div>
                                <div class="mb-2">
                                    <label for="conexion_monitor" class="form-label">Conexión *</label>
                                    <select class="form-select form-select-sm w-100" id="conexion_monitor" name="conexion_monitor" required>
                                        <option value="">Seleccione conexión</option>
                                        <option value="VGA">VGA</option>
                                        <option value="HDMI">HDMI</option>
                                    </select>
                                    <div class="invalid-feedback">La conexión es obligatoria</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Escaner -->
                            <div class="campos-especificos" id="campos-escaner" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_escaner" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_escaner" name="marca_escaner" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_escaner" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_escaner" name="modelo_escaner" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <!-- Contenedor para errores de duplicados -->
            <div id="error-duplicados" class="mt-3" style="display:none;"></div>
            
            <div class="row mt-3" id="botones-formulario" style="display: none;">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo $urlVolver; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i>Guardar Insumo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    console.log('Formulario de agregar cargado');
    
    // Mostrar/ocultar campos según tipo de insumo
    $('#tipo_insumo').on('change', function() {
        const tipo = $(this).val();
        console.log('Tipo seleccionado:', tipo);
        
        // Ocultar todos los campos específicos primero
        $('.campos-especificos').hide();
        $('#campos-varios').hide();
        $('#campos-especificos').hide();
        
        if (tipo === 'Varios') {
            console.log('Mostrando campos para Varios');
            $('#campos-varios').show();
            $('#campos-especificos').hide();
            $('#columna-especificaciones').hide();
            $('#help-nombre-insumo').show(); // Mostrar help text para Varios
        } else if (tipo !== '') {
            $('#help-nombre-insumo').hide(); // Ocultar help text para otros tipos
            console.log('Mostrando campos específicos para:', tipo);
            $('#campos-varios').hide();
            $('#campos-especificos').show();
            $('#columna-especificaciones').show();
            
            // Mostrar campos específicos según tipo
            switch(tipo) {
                case 'PC Escritorio':
                    $('#campos-pc').show();
                    break;
                case 'Notebook':
                    $('#campos-notebook').show();
                    break;
                case 'Impresora':
                    $('#campos-impresora').show();
                    break;
                case 'Monitor':
                    $('#campos-monitor').show();
                    break;
                case 'Escaner':
                    $('#campos-escaner').show();
                    break;
            }
        }
        // Mostrar card de extras notebook debajo de Información Común
        if (tipo === 'Notebook') {
            $('#extras-notebook').slideDown(150);
        } else {
            $('#extras-notebook').slideUp(150);
            $('#micro_sd').prop('checked', false);
            $('#micro_sd_gb').prop('disabled', true).val('');
        }
        
        // Mostrar/ocultar formulario y botones
        if (tipo === '' || tipo === null) {
            console.log('Ocultando formulario y botones');
            $('#formulario-campos').hide();
            $('#botones-formulario').hide();
        } else {
            console.log('Mostrando formulario y botones');
            $('#formulario-campos').show();
            $('#botones-formulario').show();
        }
        
        // Actualizar validación de campos
        actualizarValidacionCampos(tipo);
    });
    
    // Función para actualizar validación de campos según tipo
    function actualizarValidacionCampos(tipo) {
        console.log('Actualizando validación para tipo:', tipo);
        
        // Resetear todos los campos requeridos
        $('input, select, textarea').prop('required', false);
        
        // IMPORTANTE: numero_serie NUNCA es requerido
        $('#numero_serie').prop('required', false);
        $('#numero_serie').removeAttr('required');
        
        // Campos básicos siempre requeridos
        $('#nombre_insumo').prop('required', true);
        $('#tipo_insumo').prop('required', true);
        
        // IMPORTANTE: descripcion_general siempre opcional
        $('#descripcion_general').prop('required', false);

        // Ajustar label y mensaje para nombre_insumo según tipo
        if (tipo === 'Varios') {
            $('#label-nombre-insumo').text('Nombre del Insumo *');
            $('#invalid-nombre-insumo').text('El nombre del insumo es obligatorio');
            $('#nombre_insumo').prop('required', true);
            $('#help-nombre-insumo').show(); // Mostrar help text solo para Varios
        } else if (tipo !== '') {
            $('#label-nombre-insumo').text('Descripción');
            $('#invalid-nombre-insumo').text('La descripción es opcional');
            $('#nombre_insumo').prop('required', false);
            $('#help-nombre-insumo').hide(); // Ocultar help text para otros tipos
        }
        
        if (tipo === 'Varios') {
            $('#cantidad').prop('required', true);
        } else if (tipo !== '') {
            // numero_serie NO es requerido (ya establecido arriba)
            $('#id_fisico').prop('required', true);
            $('#id_patrimonio').prop('required', true);
            $('#cantidad_especifica').prop('required', true);
            
            // Campos específicos según tipo
            switch(tipo) {
                case 'PC Escritorio':
                    $('#procesador, #ram_gb, #almacenamiento_gb, #mother').prop('required', true);
                    break;
                case 'Notebook':
                    $('#marca_notebook, #modelo_notebook, #procesador_notebook, #ram_gb_notebook, #almacenamiento_gb_notebook').prop('required', true);
                    break;
                case 'Impresora':
                    $('#marca_impresora, #modelo_impresora').prop('required', true);
                    break;
                case 'Monitor':
                    $('#marca_monitor, #modelo_monitor, #pulgadas, #conexion_monitor').prop('required', true);
                    break;
                case 'Escaner':
                    $('#marca_escaner, #modelo_escaner').prop('required', true);
                    break;
            }
        }
    }
    
    // Asegurar que numero_serie nunca sea requerido al cargar la página
    $(function(){ 
        $('#numero_serie').prop('required', false);
        $('#numero_serie').removeAttr('required');
        // Ejecutar validación inicial si hay un tipo seleccionado
        const tipoInicial = $('#tipo_insumo').val();
        if (tipoInicial) {
            actualizarValidacionCampos(tipoInicial);
        }
    });
    
    // Validación en tiempo real de duplicados
    let timeoutValidacion = null;
    function validarDuplicados() {
        clearTimeout(timeoutValidacion);
        
        const numeroSerie = $('#numero_serie').val()?.trim() || '';
        const idFisico = $('#id_fisico').val()?.trim() || '';
        const idPatrimonio = $('#id_patrimonio').val()?.trim() || '';
        
        // Si todos están vacíos, no validar
        if (!numeroSerie && !idFisico && !idPatrimonio) {
            $('#error-duplicados').hide();
            return;
        }
        
        timeoutValidacion = setTimeout(function() {
            $.ajax({
                url: getAppBase() + '/ajax/validar_insumo_unico.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    numero_serie: numeroSerie,
                    id_fisico: idFisico,
                    id_patrimonio: idPatrimonio
                }),
                success: function(resp) {
                    if (!resp.valido) {
                        $('#error-duplicados').html(
                            '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' +
                            resp.errores.join('<br>') +
                            '</div>'
                        ).show();
                        $('button[type="submit"]').prop('disabled', true);
                    } else {
                        $('#error-duplicados').hide();
                        $('button[type="submit"]').prop('disabled', false);
                    }
                },
                error: function() {
                    console.error('Error al validar duplicados');
                }
            });
        }, 500); // Esperar 500ms después de que el usuario deje de escribir
    }
    
    // Aplicar validación cuando el usuario escriba en los campos
    $('#numero_serie, #id_fisico, #id_patrimonio').on('input blur', validarDuplicados);
    
    // Validación del formulario
    $('#formInsumo').on('submit', function(e) {
        console.log('Formulario enviado desde agregar.php');
        console.log('Datos del formulario:', $(this).serialize());
        
        // Validar formulario antes de enviar
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Formulario no válido, mostrando errores');
            $(this).addClass('was-validated');
            return false;
        }
        
        // Mostrar loading
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
        
        // Permitir el envío del formulario
        console.log('Formulario válido, enviando...');
        return true;
    });
    
    // Habilitar/deshabilitar tamaño Micro SD
    $(document).on('change', '#micro_sd', function(){
        const on = $(this).is(':checked');
        $('#micro_sd_gb').prop('disabled', !on);
        if (!on) { $('#micro_sd_gb').val(''); }
    });

    // Manejo de errores de envío
    $(document).on('submit', '#formInsumo', function() {
        // Timeout para detectar si el formulario se queda colgado
        setTimeout(function() {
            if ($('button[type="submit"]').prop('disabled')) {
                console.log('Formulario parece estar colgado, habilitando botón');
                $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar Insumo');
                alert('El formulario parece estar tardando más de lo esperado. Por favor, inténtelo de nuevo.');
            }
        }, 10000); // 10 segundos
    });
});

// Cambiar etiqueta según tipo de ingreso seleccionado
function cambiarTipoIngreso() {
    const select = document.getElementById('select_tipo_ingreso');
    const label = document.querySelector('label[for="id_ingreso"]');
    const help = document.getElementById('help_ingreso');
    
    if (!select || !label) return;
    
    const selectedOption = select.options[select.selectedIndex];
    const tipo = selectedOption.getAttribute('data-tipo');
    
    if (!tipo) {
        label.textContent = 'Tipo de Ingreso';
        if (help) help.textContent = 'Opcional: Asociar insumo a un ingreso';
        return;
    }
    
    // Cambiar label según tipo
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
        default:
            label.textContent = 'Tipo de Ingreso';
            if (help) help.textContent = 'Opcional: Asociar insumo a un ingreso';
    }
}

// Calcular total automático para stock dual (Varios)
function actualizarTotalCantidad() {
    const oficina = parseInt($('#cantidad_oficina').val()) || 0;
    const deposito = parseInt($('#cantidad_deposito').val()) || 0;
    const total = oficina + deposito;
    
    $('#cantidad_total').text(total);
    $('#display_oficina').text(oficina);
    $('#display_deposito').text(deposito);
}

// Eventos para actualizar total en tiempo real
$(document).on('input change', '#cantidad_oficina, #cantidad_deposito', actualizarTotalCantidad);

// Actualizar al cargar si es tipo Varios
if ($('#tipo_insumo').val() === 'Varios') {
    actualizarTotalCantidad();
}
</script> 