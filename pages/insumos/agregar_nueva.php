<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos para selects
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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

        $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual) VALUES (?,?,?,?,?,?,?,?,?, 'Asignado', ?, ?, ?)");
        $stmt->execute([
            $nombreInsumo,
            $tipo,
            ($tipo==='Varios'?($_POST['subcategoria_varios']?:null):null),
            ($tipo==='Varios'?($_POST['descripcion_general']?:null):null),
            ($tipo!=='Varios'?($_POST['numero_serie']?:null):null),
            ($tipo!=='Varios'?($_POST['id_fisico']?:null):null),
            ($tipo!=='Varios'?($_POST['id_patrimonio']?:null):null),
            $cantidad,
            $_POST['fecha_adquisicion'] ?: null,
            $_POST['id_punto_stock_actual'] ?: 2,
            $idSede,
            $idArea
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
                        $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb) VALUES (?,?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_notebook'] ?: null, $_POST['modelo_notebook'] ?: null, $_POST['procesador_notebook'] ?: null, $_POST['ram_gb_notebook'] ?: null, $_POST['almacenamiento_gb_notebook'] ?: null]);
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
        $numero = generarNumeroRemito();
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
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Información Común</h6></div>
                                    <div class="card-body p-3">
                                        <div class="mb-2"><label class="form-label">Fecha de Adquisición</label><input type="date" class="form-control form-control-sm w-100" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>"></div>
                                        <div class="mb-2"><label class="form-label">Punto de Almacenamiento *</label><select class="form-select form-select-sm w-100" name="id_punto_stock_actual" required><option value="">Seleccione punto de almacenamiento</option><?php foreach ($puntos_stock as $p): ?><option value="<?php echo $p['id_punto_stock']; ?>" <?php echo $p['id_punto_stock']==2?'selected':''; ?>><?php echo $p['nombre_punto']; ?></option><?php endforeach; ?></select></div>
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
    .done(r => { $('#id_area_asignada').html(r && r.options ? r.options : '<option value="">Seleccione un área</option>'); })
    .fail(()=> $('#id_area_asignada').html('<option value="">Seleccione un área</option>'));
});

// Dinámica del tipo de insumo
function toggleCampos() {
  const t = $('#tipo_insumo').val();
  if (!t) {
    // Ocultar todo cuando no hay selección
    $('#formulario-campos').hide();
    $('#campos-varios, #campos-especificos, #esp-pc, #esp-notebook, #esp-impresora, #esp-monitor, #esp-escaner').hide();
    return;
  }
  // Mostrar grilla principal cuando hay tipo seleccionado
  $('#formulario-campos').show();
  if (t === 'Varios') {
    $('#campos-varios').show();
    $('#campos-especificos, #esp-pc, #esp-notebook, #esp-impresora, #esp-monitor, #esp-escaner').hide();
    // Ocultar columna de especificaciones para Varios
    $('#columna-especificaciones').hide();
  } else {
    $('#campos-varios').hide();
    $('#campos-especificos').show();
    $('#esp-pc, #esp-notebook, #esp-impresora, #esp-monitor, #esp-escaner').hide();
    // Mostrar columna de especificaciones para tipos unitarios
    $('#columna-especificaciones').show();
    if (t === 'PC Completa') $('#esp-pc').show();
    if (t === 'Notebook') $('#esp-notebook').show();
    if (t === 'Impresora') $('#esp-impresora').show();
    if (t === 'Monitor') $('#esp-monitor').show();
    if (t === 'Escaner') $('#esp-escaner').show();
  }
}
$('#tipo_insumo').on('change', toggleCampos);
$(function(){ toggleCampos(); });
</script>

