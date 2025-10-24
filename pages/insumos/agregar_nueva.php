<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos para selects
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        $db->beginTransaction();

        // Paso 1: Asignación (cabecera)
        $idSede = (int)($_POST['id_sede'] ?? 0);
        $idArea = (int)($_POST['id_area_asignada'] ?? 0);
        $nombre = trim($_POST['nombre_persona_asignada'] ?? '');
        $apellido = trim($_POST['apellido_persona_asignada'] ?? '');
        $fechaAsig = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        $obs = ($_POST['observaciones'] ?? '') ?: null;
        if (!$idSede || !$idArea || $nombre === '' || $apellido === '') {
            throw new Exception('Complete datos de ubicación y agente asignado');
        }

        // Paso 2: Alta de insumo (mismo criterio que agregar.php)
        $tipo = $_POST['tipo_insumo'] ?? '';
        $nombreInsumo = trim($_POST['nombre_insumo'] ?? '');
        if ($nombreInsumo === '' || $tipo === '') { throw new Exception('Complete nombre y tipo de insumo'); }

        $cantidad = ($tipo === 'Varios') ? (int)($_POST['cantidad'] ?: 1) : (int)($_POST['cantidad_especifica'] ?: 1);
        if ($tipo !== 'Varios') {
            $idPat = isset($_POST['id_patrimonio']) ? trim((string)$_POST['id_patrimonio']) : '';
            if ($idPat === '') { throw new Exception('El ID Patrimonio es obligatorio para este tipo de insumo.'); }
        }

        $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual, es_nuevo, id_ingreso) VALUES (?,?,?,?,?,?,?,?,?, 'Asignado', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nombreInsumo,
            $tipo,
            ($tipo==='Varios'?($_POST['subcategoria_varios']?:null):null),
            ($tipo==='Varios'?($_POST['descripcion_general']?:null):null),
            ($tipo!=='Varios'?($_POST['numero_serie']?:null):null),
            ($tipo!=='Varios'?($_POST['id_fisico']?:null):null),
            ($tipo!=='Varios'?($_POST['id_patrimonio']?:null):null),
            $cantidad,
            $fechaAdquisicion,
            $_POST['id_punto_stock_actual'] ?: 2,
            $idSede,
            $idArea,
            $esNuevo,
            $idIngreso
        ]);
        $idInsumo = (int)$db->lastInsertId();

        if ($tipo !== 'Varios') {
            switch ($tipo) {
                case 'PC Completa':
                    if (!empty($_POST['procesador']) || !empty($_POST['ram_gb']) || !empty($_POST['almacenamiento_gb']) || !empty($_POST['mother'])) {
                        $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['procesador'] ?: null, $_POST['ram_gb'] ?: null, $_POST['almacenamiento_gb'] ?: null, $_POST['mother'] ?: null]);
                    }
                    break;
                case 'Notebook':
                    if (!empty($_POST['marca_notebook']) || !empty($_POST['modelo_notebook'])) {
                        $stmtN = $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb, cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                        $cargador = isset($_POST['cargador']) ? 1 : 0;
                        $funda = isset($_POST['funda']) ? 1 : 0;
                        $microSd = isset($_POST['micro_sd']) ? 1 : 0;
                        $microSdGb = $microSd ? (($_POST['micro_sd_gb'] !== '' ? (int)$_POST['micro_sd_gb'] : null)) : null;
                        $caja = isset($_POST['caja']) ? 1 : 0;
                        $adaptadorRed = isset($_POST['adaptador_red']) ? 1 : 0;
                        $stmtN->execute([$idInsumo, $_POST['marca_notebook'] ?: null, $_POST['modelo_notebook'] ?: null, $_POST['procesador_notebook'] ?: null, $_POST['ram_gb_notebook'] ?: null, $_POST['almacenamiento_gb_notebook'] ?: null, $cargador, $funda, $microSd, $microSdGb, $caja, $adaptadorRed]);
                    }
                    break;
                case 'Impresora':
                    if (!empty($_POST['marca_impresora']) || !empty($_POST['modelo_impresora'])) {
                        $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_impresora'] ?: null, $_POST['modelo_impresora'] ?: null]);
                    }
                    break;
                case 'Monitor':
                    if (!empty($_POST['marca_monitor']) || !empty($_POST['modelo_monitor'])) {
                        $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_monitor'] ?: null, $_POST['modelo_monitor'] ?: null, $_POST['pulgadas'] ?: null, $_POST['conexion_monitor'] ?: null]);
                    }
                    break;
                case 'Escaner':
                    if (!empty($_POST['marca_escaner']) || !empty($_POST['modelo_escaner'])) {
                        $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_escaner'] ?: null, $_POST['modelo_escaner'] ?: null]);
                    }
                    break;
            }
        }

        // Crear remito (cabecera) y detalle por el nuevo insumo
        $numero = generarNumeroRemito($db);
        $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?,?,?,?,?,?,?)")
           ->execute([$numero, $idSede, $idArea, $nombre, $apellido, $fechaAsig, $obs]);
        $idRemito = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)")
           ->execute([$idRemito, $idInsumo, ($tipo==='Varios'? max(1, (int)($_POST['cantidad_asignar'] ?? $cantidad)) : 1)]);

        $db->commit();
        $_SESSION['mensaje'] = 'Insumo creado y asignado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/insumos/listar.php');
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar_nueva.php');
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
                            <select class="form-select" id="id_localidad" required>
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
                                                <option value="PC Completa">PC Completa</option>
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
                                            <label class="form-label">Nombre del Insumo *</label>
                                            <input type="text" class="form-control form-control-sm w-100" name="nombre_insumo" required>
                                        </div>
                                        <div id="campos-varios" style="display: none;">
                                            <div class="mb-2">
                                                <label class="form-label">Subcategoría</label>
                                                <select class="form-select form-select-sm w-100" name="subcategoria_varios">
                                                    <option value="">Seleccione subcategoría</option>
                                                    <option value="Hardware">Hardware</option>
                                                    <option value="Periféricos">Periféricos</option>
                                                    <option value="Red">Red</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Cantidad *</label>
                                                <input type="number" class="form-control form-control-sm w-100" name="cantidad" value="1" min="1" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Descripción General</label>
                                                <textarea class="form-control form-control-sm w-100" name="descripcion_general" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div id="campos-especificos" style="display: none;">
                                            <div class="mb-2"><label class="form-label">Número de Serie *</label><input type="text" class="form-control form-control-sm w-100" name="numero_serie"></div>
                                            <div class="mb-2"><label class="form-label">ID Físico *</label><input type="text" class="form-control form-control-sm w-100" name="id_fisico"></div>
                                            <div class="mb-2"><label class="form-label">ID Patrimonio *</label><input type="text" class="form-control form-control-sm w-100" name="id_patrimonio"></div>
                                            <div class="mb-2"><label class="form-label">Cantidad</label><input type="number" class="form-control form-control-sm w-100" name="cantidad_especifica" value="1" min="1" readonly><small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1 (carga unitaria)</small></div>
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
                                                $ingresos = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at FROM ingresos ORDER BY created_at DESC")->fetchAll();
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
                                            <div class="mb-2"><label class="form-label">Procesador *</label><input type="text" class="form-control form-control-sm w-100" name="procesador"></div>
                                            <div class="mb-2"><label class="form-label">RAM (GB) *</label><input type="number" class="form-control form-control-sm w-100" name="ram_gb" min="1"></div>
                                            <div class="mb-2"><label class="form-label">Almacenamiento (GB) *</label><input type="number" class="form-control form-control-sm w-100" name="almacenamiento_gb" min="1"></div>
                                            <div class="mb-2"><label class="form-label">Motherboard *</label><input type="text" class="form-control form-control-sm w-100" name="mother"></div>
                                        </div>
                                        <div class="campos-especificos" id="campos-notebook" style="display: none;">
                                            <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" name="marca_notebook"></div>
                                            <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" name="modelo_notebook"></div>
                                            <div class="mb-2"><label class="form-label">Procesador *</label><input type="text" class="form-control form-control-sm w-100" name="procesador_notebook"></div>
                                            <div class="mb-2"><label class="form-label">RAM (GB) *</label><input type="number" class="form-control form-control-sm w-100" name="ram_gb_notebook" min="1"></div>
                                            <div class="mb-2"><label class="form-label">Almacenamiento (GB) *</label><input type="number" class="form-control form-control-sm w-100" name="almacenamiento_gb_notebook" min="1"></div>
                                        </div>
                                        <div class="campos-especificos" id="campos-impresora" style="display: none;">
                                            <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" name="marca_impresora"></div>
                                            <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" name="modelo_impresora"></div>
                                        </div>
                                        <div class="campos-especificos" id="campos-monitor" style="display: none;">
                                            <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" name="marca_monitor"></div>
                                            <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" name="modelo_monitor"></div>
                                            <div class="mb-2"><label class="form-label">Pulgadas *</label><input type="number" class="form-control form-control-sm w-100" name="pulgadas" step="0.1" min="1"></div>
                                            <div class="mb-2"><label class="form-label">Conexión *</label><select class="form-select form-select-sm w-100" name="conexion_monitor" required><option value="">Seleccione conexión</option><option value="VGA">VGA</option><option value="HDMI">HDMI</option></select></div>
                                        </div>
                                        <div class="campos-especificos" id="campos-escaner" style="display: none;">
                                            <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" name="marca_escaner"></div>
                                            <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" name="modelo_escaner"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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

// Función para cambiar label según tipo de ingreso

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
</script>

<?php include '../../includes/footer.php'; ?>
