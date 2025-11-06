<?php
require_once '../../includes/config.php';
\nrequerirAutenticacion();

$conexion = conectarDB();

// Obtener datos para los select
$localidades = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$puntos_stock = $conexion->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF
    if (!verify_csrf()) {
        $_SESSION['mensaje'] = 'CSRF inválido';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar_nueva.php');
        exit;
    }
    error_log('Formulario POST recibido en agregar_nueva.php');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Verificar campos de asignación
    $campos_asignacion = ['id_sede', 'id_area_asignada', 'nombre_persona_asignada', 'apellido_persona_asignada'];
    foreach ($campos_asignacion as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            error_log('Campo de asignación faltante: ' . $campo);
            $_SESSION['mensaje'] = "Error: Complete todos los datos de asignación";
            $_SESSION['tipo_mensaje'] = "danger";
            header("Location: agregar_nueva.php");
            exit;
        }
    }
    
    // Verificar campos de insumo
    // tipo_insumo siempre es obligatorio
    if (!isset($_POST['tipo_insumo']) || empty($_POST['tipo_insumo'])) {
        error_log('Campo requerido faltante: tipo_insumo');
        $_SESSION['mensaje'] = "Error: Debe seleccionar un tipo de insumo";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: agregar_nueva.php");
        exit;
    }
    
    $tipo_insumo = $_POST['tipo_insumo'];
    
    // nombre_insumo solo es obligatorio para tipo "Varios"
    if ($tipo_insumo === 'Varios' && (!isset($_POST['nombre_insumo']) || empty($_POST['nombre_insumo']))) {
        error_log('Campo requerido faltante: nombre_insumo (tipo Varios)');
        $_SESSION['mensaje'] = "Error: El nombre del insumo es obligatorio para tipo Varios";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: agregar_nueva.php");
        exit;
    }
    
    try {
        $conexion->beginTransaction();
        
        // Datos de asignación (Paso 1)
        $idSede = (int)$_POST['id_sede'];
        $idArea = (int)$_POST['id_area_asignada'];
        $nombre = trim($_POST['nombre_persona_asignada']);
        $apellido = trim($_POST['apellido_persona_asignada']);
        $fechaAsig = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        $obs = ($_POST['observaciones'] ?? '') ?: null;
        
        $tipo_insumo = $_POST['tipo_insumo'];
        
        // Configurar cantidad según tipo
        $cantidad = ($tipo_insumo == 'Varios') ? ($_POST['cantidad'] ?: 1) : ($_POST['cantidad_especifica'] ?: 1);
        
        // NO validamos id_patrimonio en backend para agregar_nueva
        // Los campos se envían correctamente desde el formulario
        
        // Validar que no existan duplicados de número de serie, ID físico o ID patrimonio
        $numero_serie = ($tipo_insumo != 'Varios') ? ($_POST['numero_serie'] ?: null) : null;
        $id_fisico = ($tipo_insumo != 'Varios') ? ($_POST['id_fisico'] ?: null) : null;
        $id_patrimonio = ($tipo_insumo != 'Varios') ? ($_POST['id_patrimonio'] ?: null) : null;
        
        $validacion = validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, null, $conexion);
        if (!$validacion['valido']) {
            $_SESSION['mensaje'] = 'Error: ' . implode('. ', $validacion['errores']);
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: agregar_nueva.php');
            exit;
        }
        
        // Insertar insumo principal (con estado ASIGNADO y ubicación)
        $sql = "INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, 
                                   numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, 
                                   id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual, id_ingreso, es_nuevo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Asignado', ?, ?, ?, ?, ?)";
        
        $esNuevo = isset($_POST['es_nuevo']) && $_POST['es_nuevo'] == '1' ? 1 : 0;
        $idIngreso = !empty($_POST['id_ingreso']) ? (int)$_POST['id_ingreso'] : null;
        $fechaAdquisicion = $_POST['fecha_adquisicion'] ?: null;
        
        // Si se asigna manualmente un ingreso, usar su fecha de finalización
        if ($idIngreso) {
            $stmtIng = $conexion->prepare('SELECT DATE(fecha_finalizacion) as fecha_finalizacion FROM ingresos WHERE id_ingreso = ?');
            $stmtIng->execute([$idIngreso]);
            $ingreso = $stmtIng->fetch();
            if ($ingreso && !empty($ingreso['fecha_finalizacion'])) {
                $fechaAdquisicion = $ingreso['fecha_finalizacion'];
                error_log("Ingreso asignado - Fecha: " . $fechaAdquisicion);
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
            $fechaAdquisicion,
            $_POST['id_punto_stock_actual'] ?: 2,
            $idSede,
            $idArea,
            $idIngreso,
            $esNuevo
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
        
        // Crear remito (cabecera) y detalle
        $numero = generarNumeroRemito($conexion);
        $sql = "INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$numero, $idSede, $idArea, $nombre, $apellido, $fechaAsig, $obs]);
        $idRemito = $conexion->lastInsertId();
        
        // Detalle del remito
        $cantidadAsignar = ($tipo_insumo === 'Varios') ? max(1, (int)($_POST['cantidad_asignar'] ?? $cantidad)) : 1;
        $sql = "INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$idRemito, $id_insumo, $cantidadAsignar]);
        
        $conexion->commit();
        
        error_log('Insumo agregado y asignado correctamente');
        error_log('ID del insumo: ' . $id_insumo . ', ID del remito: ' . $idRemito);
        $_SESSION['mensaje'] = "Insumo creado y asignado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        header('Location: ' . app_base_url() . '/pages/insumos/listar.php');
        exit;
        
    } catch (Exception $e) {
        $conexion->rollBack();
        error_log('Error al agregar insumo asignado: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: agregar_nueva.php");
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Agregar Insumo Asignado (Pasos)</h4>
            <a href="listar.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
        </div>
    </div>
</div>

<!-- Stepper visual -->
<div class="stepper">
    <div class="step step-1 active"><span class="circle">1</span><span>Datos de Asignación</span></div>
    <div class="divider"></div>
    <div class="step step-2"><span class="circle">2</span><span>Alta de Insumo</span></div>
  </div>

<div class="card">
    <div class="card-body">
        <form method="POST" id="formAgregarNueva" class="needs-validation" novalidate>
            <?php echo csrf_input(); ?>
            <!-- Paso 1: Cabecera asignación -->
            <div id="paso1">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="row">
                            <div class="col-md-6">
                        <h6 class="mb-3 section-title">Agente Asignado</h6>
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre_persona_asignada" name="nombre_persona_asignada" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" id="apellido_persona_asignada" name="apellido_persona_asignada" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Fecha de Asignación *</label>
                            <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                            </div>
                            <div class="col-md-6">
                        <h6 class="mb-3 section-title">Ubicación</h6>
                        <div class="mb-3">
                            <label class="form-label">Localidad *</label>
                            <select class="form-select" id="id_localidad">
                                <option value="">Seleccione una localidad</option>
                                <?php foreach ($localidades as $loc): ?>
                                    <option value="<?php echo $loc['id_localidad']; ?>"><?php echo htmlspecialchars($loc['nombre_localidad']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sede *</label>
                            <select class="form-select" id="id_sede" name="id_sede" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área *</label>
                            <select class="form-select" id="id_area_asignada" name="id_area_asignada" required></select>
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
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-primary mt-3 mt-md-0" id="btnSiguiente"><i class="fas fa-arrow-right me-2"></i>Siguiente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Alta de Insumo -->
            <div id="paso2" style="display:none;">
                <div class="row">
                    <div class="col-12">
                        <!-- Bloque selección de tipo (igual a agregar.php) -->
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario en 3 columnas (copiado de agregar.php) -->
                        <div class="row g-2" id="formulario-campos" style="display: none;">
                            <!-- COLUMNA 1: Información Básica -->
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información Básica</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="mb-2">
                                            <label id="label-nombre-insumo" class="form-label">Nombre del Insumo *</label>
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
                                            
                                            <div class="mb-2">
                                                <label for="cantidad" class="form-label">Cantidad *</label>
                                                <input type="number" class="form-control form-control-sm w-100" id="cantidad" name="cantidad" value="1" min="1" required>
                                                <div class="invalid-feedback">La cantidad es obligatoria</div>
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
                                    <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Información Común</h6></div>
                                    <div class="card-body px-3 pt-3 pb-0">
                                        <div class="mb-2"><label class="form-label">Fecha de Adquisición</label><input type="date" class="form-control form-control-sm w-100" id="fecha_adquisicion_nueva" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>"></div>
                                        <div class="mb-2"><label class="form-label">Punto de Almacenamiento *</label><select class="form-select form-select-sm w-100" name="id_punto_stock_actual" required><option value="">Seleccione punto de almacenamiento</option><?php foreach ($puntos_stock as $p): ?><option value="<?php echo $p['id_punto_stock']; ?>" <?php echo $p['id_punto_stock']==2?'selected':''; ?>><?php echo $p['nombre_punto']; ?></option><?php endforeach; ?></select></div>
                                        
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="es_nuevo_nueva" name="es_nuevo" value="1" checked>
                                                <label class="form-check-label" for="es_nuevo_nueva">
                                                    <strong>Insumo Nuevo</strong> <small class="text-muted">(desmarcar si es usado)</small>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-0">
                                            <label for="id_ingreso_nueva" class="form-label">Tipo de Ingreso</label>
                                            <select class="form-select form-select-sm w-100" id="id_ingreso_nueva" name="id_ingreso" onchange="cambiarTipoIngresoNueva()">
                                                <option value="">Sin ingreso asociado</option>
                                                <?php
                                                $ingresos = $conexion->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at FROM ingresos ORDER BY created_at DESC")->fetchAll();
                                                $tipos = ['fondos' => 'Fondos', 'compra_directa' => 'Compra Directa', 'licitacion' => 'Licitación', 'otros' => 'Otros'];
                                                
                                                foreach ($tipos as $tipoKey => $tipoLabel):
                                                    $ingresosTipo = array_filter($ingresos, function($ing) use ($tipoKey) {
                                                        return $ing['tipo_ingreso'] === $tipoKey;
                                                    });
                                                    if (empty($ingresosTipo)) continue;
                                                ?>
                                                    <optgroup label="<?php echo $tipoLabel; ?>">
                                                        <?php foreach ($ingresosTipo as $ing): ?>
                                                            <option value="<?php echo $ing['id_ingreso']; ?>" data-tipo="<?php echo $ing['tipo_ingreso']; ?>">
                                                                <?php echo htmlspecialchars($ing['nro_referencia']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted" id="help_ingreso_nueva">Opcional: Asociar a un ingreso</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card border-0 shadow-sm mt-2" id="extras-notebook" style="display:none;">
                                    <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-laptop me-2 text-primary"></i>Accesorios Notebook</h6></div>
                                    <div class="card-body p-3">
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="cargador" name="cargador" value="1"><label class="form-check-label" for="cargador">Cargador</label></div>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="funda" name="funda" value="1"><label class="form-check-label" for="funda">Funda</label></div>
                                        <div class="row g-2 align-items-center mb-2"><div class="col-auto"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="micro_sd" name="micro_sd" value="1"><label class="form-check-label" for="micro_sd">Micro SD</label></div></div><div class="col"><input type="number" min="0" step="1" class="form-control form-control-sm" id="micro_sd_gb" name="micro_sd_gb" placeholder="Tamaño (GB)" disabled></div></div>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="caja" name="caja" value="1"><label class="form-check-label" for="caja">Caja</label></div>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="adaptador_red" name="adaptador_red" value="1"><label class="form-check-label" for="adaptador_red">Adaptador de red</label></div>
                                    </div>
                                </div>
                            </div>
                            <!-- COLUMNA 3: Especificaciones -->
                            <div class="col-md-4" id="columna-especificaciones">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-microchip me-2 text-primary"></i>Especificaciones</h6></div>
                                    <div class="card-body p-3">
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
                                                    <option value="DisplayPort">DisplayPort</option>
                                                    <option value="DVI">DVI</option>
                                                </select>
                                                <div class="invalid-feedback">La conexión es obligatoria</div>
                                            </div>
                                        </div>
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

                        <!-- Contenedor para errores de duplicados -->
                        <div id="error-duplicados" class="mt-3" style="display:none;"></div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary" id="btnVolver"><i class="fas fa-arrow-left me-1"></i>Volver</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar y Asignar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Paso 1 -> Paso 2 y viceversa
$('#btnSiguiente').on('click', function(){
  let ok = true;
  ['id_localidad','id_sede','id_area_asignada','nombre_persona_asignada','apellido_persona_asignada','fecha_asignacion']
    .forEach(id => { const el = document.getElementById(id); if (!el || !el.checkValidity()) { ok = false; el && el.classList.add('is-invalid'); }});
  if (!ok) return;
  $('#paso1').hide();
  $('#paso2').show();
  $('.stepper .step').removeClass('active');
  $('.stepper .step-2').addClass('active');
});
$('#btnVolver').on('click', function(){
  $('#paso2').hide();
  $('#paso1').show();
  $('.stepper .step').removeClass('active');
  $('.stepper .step-1').addClass('active');
});

// Cargar sedes y áreas
$('#id_localidad').on('change', function(){
  const id = $(this).val();
  setLoading($('#id_sede'), 'Cargando sedes...');
  $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id })
    .done(r => {
      const data = r && r.data ? r.data : r;
      const lista = data && data.sedes ? data.sedes : [];
      let html = '<option value="">Seleccione una sede</option>';
      lista.forEach(s => { html += `<option value="${parseInt(s.id,10)}">${$('<div>').text(s.nombre||'').html()}</option>`; });
      $('#id_sede').html(html);
      $('#id_area_asignada').html('<option value="">Seleccione un área</option>');
    })
    .fail(()=> $('#id_sede').html('<option value="">Seleccione una sede</option>'));
});
$('#id_sede').on('change', function(){
  const id = $(this).val();
  setLoading($('#id_area_asignada'), 'Cargando áreas...');
  $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id })
    .done(r => {
      const data = r && r.data ? r.data : r;
      const lista = data && data.areas ? data.areas : [];
      let html = '<option value="">Seleccione un área</option>';
      lista.forEach(a => { html += `<option value="${parseInt(a.id_area || a.id,10)}">${$('<div>').text(a.nombre_area || a.nombre || '').html()}</option>`; });
      $('#id_area_asignada').html(html);
    })
    .fail(()=> $('#id_area_asignada').html('<option value="">Seleccione un área</option>'));
});

// Dinámica del tipo de insumo
function toggleCampos() {
  const t = $('#tipo_insumo').val();
  console.log('toggleCampos llamado, tipo:', t);
  
  // Asegurar que numero_serie NUNCA sea requerido
  $('#numero_serie').prop('required', false);
  
  if (!t) {
    // Ocultar todo cuando no hay selección
    $('#formulario-campos').hide();
    $('#campos-varios, #campos-especificos, #campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').hide();
    // Ocultar help text cuando no hay tipo
    $('#help-nombre-insumo').hide();
    // Deshabilitar campos requeridos cuando están ocultos
    $('#numero_serie, #id_fisico, #id_patrimonio').prop('required', false);
    // Deshabilitar todos los campos required de especificaciones
    $('#campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').find('input, select').prop('required', false);
    return;
  }
  // Mostrar grilla principal cuando hay tipo seleccionado
  $('#formulario-campos').show();
  
  // IMPORTANTE: descripcion_general siempre opcional
  $('textarea[name="descripcion_general"]').prop('required', false);
  
  // Ajustar label y validación para nombre_insumo según tipo
  if (t === 'Varios') {
    $('#label-nombre-insumo').text('Nombre del Insumo *');
    $('#invalid-nombre-insumo').text('El nombre del insumo es obligatorio');
    $('#nombre_insumo').prop('required', true);
    // Mostrar help text solo para Varios
    const $helpText = $('#help-nombre-insumo');
    if ($helpText.length) {
      $helpText.show();
      console.log('Help text mostrado para tipo Varios');
    } else {
      console.warn('Elemento #help-nombre-insumo no encontrado');
    }
  } else if (t !== '') {
    $('#label-nombre-insumo').text('Descripción');
    $('#invalid-nombre-insumo').text('La descripción es opcional');
    $('#nombre_insumo').prop('required', false);
    // Ocultar help text para otros tipos
    $('#help-nombre-insumo').hide();
  }
  
  if (t === 'Varios') {
    $('#campos-varios').show();
    $('#campos-especificos, #campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').hide();
    // Ocultar columna de especificaciones para Varios
    $('#columna-especificaciones').hide();
    // Mostrar help text para Varios
    $('#help-nombre-insumo').show();
    // Deshabilitar required en campos ocultos
    $('#numero_serie, #id_fisico, #id_patrimonio').prop('required', false);
    // Deshabilitar todos los campos required de especificaciones
    $('#campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').find('input, select').prop('required', false);
  } else {
    console.log('Tipo NO varios, mostrando campos específicos');
    $('#campos-varios').hide();
    $('#campos-especificos').show();
    // Mostrar columna de especificaciones para tipos unitarios
    $('#columna-especificaciones').show();
    // Ocultar help text para otros tipos
    $('#help-nombre-insumo').hide();
    console.log('Columna especificaciones mostrada');
    
    // Habilitar required solo en id_fisico e id_patrimonio
    $('#id_fisico, #id_patrimonio').prop('required', true);
    
    // IMPORTANTE: numero_serie NUNCA es requerido - ejecutar múltiples veces para asegurar
    $('#numero_serie').prop('required', false);
    $('#numero_serie').removeAttr('required');
    // Usar setTimeout para asegurar después de que se muestre el campo
    setTimeout(function() {
      $('#numero_serie').prop('required', false);
      $('#numero_serie').removeAttr('required');
      console.log('numero_serie establecido como NO requerido (después de mostrar):', $('#numero_serie').prop('required'));
    }, 50);
    
    // Primero ocultar y deshabilitar required de TODOS los tipos
    $('#campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').hide();
    $('#campos-pc, #campos-notebook, #campos-impresora, #campos-monitor, #campos-escaner').find('input, select').prop('required', false);
    
    // Luego mostrar solo el tipo seleccionado
    console.log('Mostrando especificaciones para tipo:', t);
    if (t === 'PC Escritorio') {
      console.log('Intentando mostrar #campos-pc');
      $('#campos-pc').show();
      console.log('Estado de #campos-pc después de .show():', $('#campos-pc').is(':visible'));
    }
    if (t === 'Notebook') {
      console.log('Mostrando #campos-notebook');
      $('#campos-notebook').show();
    }
    if (t === 'Impresora') {
      console.log('Mostrando #campos-impresora');
      $('#campos-impresora').show();
    }
    if (t === 'Monitor') {
      console.log('Mostrando #campos-monitor');
      $('#campos-monitor').show();
    }
    if (t === 'Escaner') {
      console.log('Mostrando #campos-escaner');
      $('#campos-escaner').show();
    }
  }
  // Mostrar accesorios notebook en la segunda columna
  if (t === 'Notebook') {
    $('#extras-notebook').slideDown(150);
  } else {
    $('#extras-notebook').slideUp(150);
    $('#micro_sd').prop('checked', false);
    $('#micro_sd_gb').prop('disabled', true).val('');
  }
}
$('#tipo_insumo').on('change', toggleCampos);
$(function(){ 
  // Asegurar que numero_serie nunca sea requerido al cargar la página
  $('#numero_serie').prop('required', false);
  $('#numero_serie').removeAttr('required');
  
  // También asegurar después de un pequeño delay para evitar conflictos
  setTimeout(function() {
    $('#numero_serie').prop('required', false);
    $('#numero_serie').removeAttr('required');
    console.log('Verificación final: numero_serie requerido?', $('#numero_serie').prop('required'));
  }, 100);
  
  toggleCampos(); 
  
  // Asegurar nuevamente después de toggleCampos
  setTimeout(function() {
    $('#numero_serie').prop('required', false);
    $('#numero_serie').removeAttr('required');
  }, 200);
});

// Enable/disable tamaño Micro SD
$(document).on('change', '#micro_sd', function(){
  const on = $(this).is(':checked');
  $('#micro_sd_gb').prop('disabled', !on);
  if (!on) { $('#micro_sd_gb').val(''); }
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
        $('button[type="submit"]').prop('disabled', false);
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

// Función para cambiar label según tipo de ingreso
function cambiarTipoIngresoNueva() {
  const select = document.getElementById('id_ingreso_nueva');
  const label = document.querySelector('label[for="id_ingreso_nueva"]');
  const help = document.getElementById('help_ingreso_nueva');
  
  if (!select || !label) return;
  
  const selectedOption = select.options[select.selectedIndex];
  const tipo = selectedOption.getAttribute('data-tipo');
  
  switch(tipo) {
    case 'fondos':
      label.innerHTML = '<i class="fas fa-money-bill me-1"></i>Nro. de Nota';
      if (help) help.textContent = 'Ingreso por fondos';
      break;
    case 'licitacion':
      label.innerHTML = '<i class="fas fa-file-signature me-1"></i>Nro. de Expediente';
      if (help) help.textContent = 'Ingreso por licitación';
      break;
    case 'compra_directa':
      label.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>Nro. de Expediente';
      if (help) help.textContent = 'Ingreso por compra directa';
      break;
    case 'otros':
      label.innerHTML = '<i class="fas fa-ellipsis-h me-1"></i>Nro. de Referencia';
      if (help) help.textContent = 'Otros ingresos';
      break;
    default:
      label.textContent = 'Tipo de Ingreso';
      if (help) help.textContent = 'Opcional: Asociar a un ingreso';
  }
}

// DEBUG: Detectar submit del formulario
$('#formAgregarNueva').on('submit', function(e) {
  console.log('========================================');
  console.log('FORMULARIO SUBMIT DISPARADO');
  console.log('========================================');
  console.log('Tipo insumo:', $('#tipo_insumo').val());
  console.log('Nombre insumo:', $('[name="nombre_insumo"]').val());
  console.log('ID Sede:', $('#id_sede').val());
  console.log('ID Area:', $('#id_area_asignada').val());
  console.log('Nombre persona:', $('#nombre_persona_asignada').val());
  console.log('Apellido persona:', $('#apellido_persona_asignada').val());
  
  // Verificar campos inválidos
  const invalidos = $(this).find(':invalid');
  console.log('Campos inválidos encontrados:', invalidos.length);
  
  if (invalidos.length > 0) {
    console.log('BLOQUEANDO SUBMIT - Campos inválidos:');
    invalidos.each(function() {
      console.log('  - Campo:', this.name || this.id, 'Valor:', $(this).val(), 'Tipo:', this.type);
    });
    e.preventDefault();
    alert('Hay campos requeridos sin completar. Revisa la consola (F12) para más detalles.');
    return false;
  }
  
  console.log('✅ Validación OK - Enviando formulario...');
});
</script>


<script>
// DEBUG: Detectar submit del formulario
$('#formAgregarNueva').on('submit', function(e) {
  console.log('========================================');
  console.log('FORMULARIO SUBMIT DISPARADO');
  console.log('========================================');
  console.log('Tipo insumo:', $('#tipo_insumo').val());
  console.log('Nombre insumo:', $('[name="nombre_insumo"]').val());
  console.log('ID Sede:', $('#id_sede').val());
  console.log('ID Area:', $('#id_area_asignada').val());
  console.log('Nombre persona:', $('#nombre_persona_asignada').val());
  console.log('Apellido persona:', $('#apellido_persona_asignada').val());
  
  // Verificar campos inválidos
  const invalidos = $(this).find(':invalid');
  console.log('Campos inválidos encontrados:', invalidos.length);
  
  if (invalidos.length > 0) {
    console.log('BLOQUEANDO SUBMIT - Campos inválidos:');
    invalidos.each(function() {
      console.log('  - Campo:', this.name || this.id, 'Valor:', $(this).val(), 'Tipo:', this.type);
    });
    e.preventDefault();
    alert('Hay campos requeridos sin completar. Revisa la consola (F12) para más detalles.');
    return false;
  }
  
  console.log('✅ Validación OK - Enviando formulario...');
});
</script>
