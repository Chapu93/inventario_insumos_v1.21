<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos para selects comunes
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $tipo = $_POST['tipo_insumo'] ?? '';
        $nombre = trim($_POST['nombre_insumo'] ?? '');
        if ($nombre === '' || $tipo === '') { throw new Exception('Datos de insumo incompletos.'); }

        // Validaciones mínimas asignación
        $id_sede = (int)($_POST['id_sede'] ?? 0);
        $id_area = (int)($_POST['id_area'] ?? 0);
        $nom = trim($_POST['nombre_persona'] ?? '');
        $ape = trim($_POST['apellido_persona'] ?? '');
        $fecha_asig = $_POST['fecha_asignacion'] ?? date('Y-m-d');
        if ($id_sede <= 0 || $id_area <= 0 || $nom === '' || $ape === '') { throw new Exception('Datos de asignación incompletos.'); }

        // Cantidad y campos específicos
        $cantidad = ($tipo === 'Varios') ? max(1, (int)($_POST['cantidad'] ?? 1)) : 1;
        $subcat = $descGen = $numSerie = $idFisico = $idPatrimonio = null;
        if ($tipo === 'Varios') {
            $subcat = ($_POST['subcategoria_varios'] ?? '') ?: null;
            $descGen = ($_POST['descripcion_general'] ?? '') ?: null;
        } else {
            $numSerie = ($_POST['numero_serie'] ?? '') ?: null;
            $idFisico = ($_POST['id_fisico'] ?? '') ?: null;
            $idPatrimonio = ($_POST['id_patrimonio'] ?? '') ?: null;
            if ($numSerie === null || $idFisico === null || $idPatrimonio === null) { throw new Exception('Serie, ID Físico e ID Patrimonio son obligatorios.'); }
        }

        // Insert insumo
        $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual) VALUES (?,?,?,?,?,?,?,?,?,'Asignado', ?, ?, ?)");
        $stmt->execute([
            $nombre, $tipo, $subcat, $descGen, $numSerie, $idFisico, $idPatrimonio,
            $cantidad, ($_POST['fecha_adquisicion'] ?? date('Y-m-d')),
            ($_POST['id_punto_stock_actual'] ?? 2), $id_sede, $id_area
        ]);
        $id_insumo = (int)$db->lastInsertId();

        // Insert específicos opcional (solo guardar si hay info)
        switch ($tipo) {
            case 'PC Completa':
                $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
                   ->execute([$id_insumo, $_POST['procesador'] ?? null, $_POST['ram_gb'] ?? null, $_POST['almacenamiento_gb'] ?? null, $_POST['mother'] ?? null]);
                break;
            case 'Notebook':
                $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb) VALUES (?,?,?,?,?,?)")
                   ->execute([$id_insumo, $_POST['marca_notebook'] ?? null, $_POST['modelo_notebook'] ?? null, $_POST['procesador_notebook'] ?? null, $_POST['ram_gb_notebook'] ?? null, $_POST['almacenamiento_gb_notebook'] ?? null]);
                break;
            case 'Impresora':
                $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id_insumo, $_POST['marca_impresora'] ?? null, $_POST['modelo_impresora'] ?? null]);
                break;
            case 'Monitor':
                $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                   ->execute([$id_insumo, $_POST['marca_monitor'] ?? null, $_POST['modelo_monitor'] ?? null, $_POST['pulgadas'] ?? null, $_POST['conexion_monitor'] ?? null]);
                break;
            case 'Escaner':
                $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id_insumo, $_POST['marca_escaner'] ?? null, $_POST['modelo_escaner'] ?? null]);
                break;
        }

        // Remito
        $numero_remito = generarNumeroRemito();
        $stmt = $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, estado, observaciones) VALUES (?,?,?,?,?,?,'Activa',?)");
        $stmt->execute([$numero_remito, $id_sede, $id_area, $nom, $ape, $fecha_asig, ($_POST['observaciones'] ?? null)]);
        $id_remito = (int)$db->lastInsertId();

        // Detalle
        $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)")
           ->execute([$id_remito, $id_insumo, $cantidad]);

        $db->commit();
        header('Location: ' . app_base_url() . '/pages/reportes/remito.php?remito=' . urlencode($numero_remito));
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar_asignado.php');
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Agregar Insumo y Asignar</h4>
            <a href="listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
</div>

<form method="POST" class="needs-validation" novalidate>
    <div class="row g-2">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-box me-2"></i>Insumo</h6></div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label">Tipo de Insumo *</label>
                        <select class="form-select" name="tipo_insumo" id="tipo_insumo" required>
                            <option value="">Seleccione</option>
                            <option value="Varios">Varios</option>
                            <option value="PC Completa">PC Completa</option>
                            <option value="Notebook">Notebook</option>
                            <option value="Impresora">Impresora</option>
                            <option value="Monitor">Monitor</option>
                            <option value="Escaner">Escaner</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre_insumo" required>
                    </div>
                    <div id="campos-varios" style="display:none">
                        <div class="mb-2">
                            <label class="form-label">Subcategoría</label>
                            <select class="form-select" name="subcategoria_varios">
                                <option value="">Seleccione</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Periféricos">Periféricos</option>
                                <option value="Red">Red</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" name="cantidad" value="1" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion_general" rows="2"></textarea>
                        </div>
                    </div>
                    <div id="campos-especificos" style="display:none">
                        <div class="mb-2"><label class="form-label">N° de Serie *</label><input class="form-control" name="numero_serie" required></div>
                        <div class="mb-2"><label class="form-label">ID Físico *</label><input class="form-control" name="id_fisico" required></div>
                        <div class="mb-2"><label class="form-label">ID Patrimonio *</label><input class="form-control" name="id_patrimonio" required></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Fecha de Adquisición</label>
                        <input type="date" class="form-control" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Punto de Almacenamiento *</label>
                        <select class="form-select" name="id_punto_stock_actual" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($puntos_stock as $p): ?>
                                <option value="<?php echo $p['id_punto_stock']; ?>" <?php echo $p['id_punto_stock']==2?'selected':''; ?>><?php echo $p['nombre_punto']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-handshake me-2"></i>Asignación</h6></div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label">Localidad *</label>
                        <select id="localidad" class="form-select" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($localidades as $loc): ?>
                                <option value="<?php echo $loc['id_localidad']; ?>"><?php echo $loc['nombre_localidad']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sede *</label>
                        <select name="id_sede" id="sede" class="form-select" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Área *</label>
                        <select name="id_area" id="area" class="form-select" required></select>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6"><label class="form-label">Nombre *</label><input class="form-control" name="nombre_persona" required></div>
                        <div class="col-sm-6"><label class="form-label">Apellido *</label><input class="form-control" name="apellido_persona" required></div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">Fecha Asignación *</label>
                        <input type="date" class="form-control" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-end gap-2">
        <a href="listar.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar y Asignar</button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>

<script>
$(function(){
  $('#tipo_insumo').on('change', function(){
    const t = $(this).val();
    if (!t) { $('#campos-varios, #campos-especificos').hide(); return; }
    if (t === 'Varios') { $('#campos-varios').show(); $('#campos-especificos').hide(); }
    else { $('#campos-especificos').show(); $('#campos-varios').hide(); }
  });
  $('#localidad').on('change', function(){
    const id = $(this).val();
    setLoading($('#sede'), 'Cargando sedes...');
    $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id }).done(r=>{
      $('#sede').html(r && r.options ? r.options : '<option value="">Seleccione</option>');
    });
  });
  $('#sede').on('change', function(){
    const id = $(this).val();
    setLoading($('#area'), 'Cargando áreas...');
    $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id }).done(r=>{
      $('#area').html(r && r.options ? r.options : '<option value="">Seleccione</option>');
    });
  });
});
</script>

<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos para selects
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $tipo = $_POST['tipo_insumo'] ?? '';
        $nombre = trim($_POST['nombre_insumo'] ?? '');
        if ($nombre === '' || $tipo === '') { throw new Exception('Complete nombre y tipo de insumo'); }

        // Validaciones básicas
        $fechaAdq = $_POST['fecha_adquisicion'] ?: null;
        $puntoStock = $_POST['id_punto_stock_actual'] ?: null;

        // Insert insumo
        $sql = "INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual) VALUES (?,?,?,?,?,?,?,?,?, 'Disponible', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nombre,
            $tipo,
            ($tipo==='Varios'?($_POST['subcategoria_varios']?:null):null),
            ($tipo==='Varios'?($_POST['descripcion_general']?:null):null),
            ($tipo!=='Varios'?($_POST['numero_serie']?:null):null),
            ($tipo!=='Varios'?($_POST['id_fisico']?:null):null),
            ($tipo!=='Varios'?($_POST['id_patrimonio']?:null):null),
            ($tipo==='Varios' ? (int)($_POST['cantidad']??1) : 1),
            $fechaAdq,
            $puntoStock
        ]);
        $idInsumo = (int)$db->lastInsertId();

        // Insert especificaciones opcionales (solo ejemplos mínimos)
        if ($tipo==='PC Completa') {
            $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
               ->execute([$idInsumo, $_POST['procesador']??null, $_POST['ram_gb']??null, $_POST['almacenamiento_gb']??null, $_POST['mother']??null]);
        } elseif ($tipo==='Notebook') {
            $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb) VALUES (?,?,?,?,?,?)")
               ->execute([$idInsumo, $_POST['marca_notebook']??null, $_POST['modelo_notebook']??null, $_POST['procesador_notebook']??null, $_POST['ram_gb_notebook']??null, $_POST['almacenamiento_gb_notebook']??null]);
        } elseif ($tipo==='Impresora') {
            $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
               ->execute([$idInsumo, $_POST['marca_impresora']??null, $_POST['modelo_impresora']??null]);
        } elseif ($tipo==='Monitor') {
            $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
               ->execute([$idInsumo, $_POST['marca_monitor']??null, $_POST['modelo_monitor']??null, $_POST['pulgadas']??null, $_POST['conexion_monitor']??null]);
        } elseif ($tipo==='Escaner') {
            $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
               ->execute([$idInsumo, $_POST['marca_escaner']??null, $_POST['modelo_escaner']??null]);
        }

        // Datos de asignación
        $idSede = (int)($_POST['id_sede'] ?? 0);
        $idArea = (int)($_POST['id_area'] ?? 0);
        $nom = trim($_POST['persona_nombre'] ?? '');
        $ape = trim($_POST['persona_apellido'] ?? '');
        $fechaAsig = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        if (!$idSede || !$idArea || $nom==='' || $ape==='') {
            throw new Exception('Complete sede, área y persona');
        }

        // Crear remito
        $numRemito = generarNumeroRemito();
        $stmt = $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, estado, observaciones) VALUES (?,?,?,?,?,?,'Activa',?)");
        $stmt->execute([$numRemito, $idSede, $idArea, $nom, $ape, $fechaAsig, ($_POST['observaciones'] ?? null)]);
        $idRemito = (int)$db->lastInsertId();

        // Detalle remito
        $cantidadAsignar = ($tipo==='Varios') ? max(1, (int)($_POST['cantidad_asignar'] ?? 1)) : 1;
        $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)")
           ->execute([$idRemito, $idInsumo, $cantidadAsignar]);

        // Actualizar insumo a Asignado y ubicación actual
        $db->prepare("UPDATE insumos SET estado='Asignado', id_sede_actual=?, id_area_asignacion_actual=? WHERE id_insumo=?")
           ->execute([$idSede, $idArea, $idInsumo]);

        $db->commit();

        $_SESSION['mensaje'] = 'Insumo creado y asignado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/reportes/remito.php?remito=' . urlencode($numRemito));
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar_asignado.php');
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-plus-square me-2"></i>Agregar Insumo Asignado</h4>
            <a href="listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
</div>

<form method="POST" id="formAgregarAsignado" class="needs-validation" novalidate>
<div class="row g-2">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-box me-2"></i>Insumo</h6></div>
            <div class="card-body p-3">
                <div class="mb-2">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select" id="tipo_insumo" name="tipo_insumo" required>
                        <option value="">Seleccione</option>
                        <option value="Varios">Varios</option>
                        <option value="PC Completa">PC Completa</option>
                        <option value="Notebook">Notebook</option>
                        <option value="Impresora">Impresora</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Escaner">Escaner</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" name="nombre_insumo" required>
                </div>
                <div id="camposVarios" style="display:none;">
                    <div class="mb-2">
                        <label class="form-label">Subcategoría</label>
                        <select class="form-select" name="subcategoria_varios">
                            <option value="">Seleccione</option>
                            <option value="Hardware">Hardware</option>
                            <option value="Periféricos">Periféricos</option>
                            <option value="Red">Red</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" class="form-control" name="cantidad" min="1" value="1" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción General</label>
                        <textarea class="form-control" name="descripcion_general" rows="2"></textarea>
                    </div>
                </div>
                <div id="camposUnitarios" style="display:none;">
                    <div class="mb-2"><label class="form-label">Número de Serie *</label><input type="text" class="form-control" name="numero_serie"></div>
                    <div class="mb-2"><label class="form-label">ID Físico *</label><input type="text" class="form-control" name="id_fisico"></div>
                    <div class="mb-2"><label class="form-label">ID Patrimonio *</label><input type="text" class="form-control" name="id_patrimonio"></div>
                </div>
                <div class="mb-2"><label class="form-label">Fecha de Adquisición</label><input type="date" class="form-control" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="mb-2">
                    <label class="form-label">Punto de Almacenamiento *</label>
                    <select class="form-select" name="id_punto_stock_actual" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($puntos_stock as $p): ?>
                        <option value="<?php echo $p['id_punto_stock']; ?>" <?php echo $p['id_punto_stock']==2?'selected':''; ?>><?php echo $p['nombre_punto']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-share-square me-2"></i>Asignación</h6></div>
            <div class="card-body p-3">
                <div class="mb-2">
                    <label class="form-label">Localidad *</label>
                    <select class="form-select" id="selLocalidad" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($localidades as $l): ?>
                        <option value="<?php echo $l['id_localidad']; ?>"><?php echo $l['nombre_localidad']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Sede *</label>
                    <select class="form-select" id="selSede" name="id_sede" required></select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Área *</label>
                    <select class="form-select" id="selArea" name="id_area" required></select>
                </div>
                <div class="row g-2">
                    <div class="col-sm-6"><label class="form-label">Nombre *</label><input type="text" class="form-control" name="persona_nombre" required></div>
                    <div class="col-sm-6"><label class="form-label">Apellido *</label><input type="text" class="form-control" name="persona_apellido" required></div>
                </div>
                <div class="mb-2"><label class="form-label">Fecha de Asignación *</label><input type="date" class="form-control" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required></div>
                <div class="mb-2"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="2"></textarea></div>
                <div class="mb-2" id="rowCantidadAsignar" style="display:none;"><label class="form-label">Cantidad a Asignar *</label><input type="number" class="form-control" name="cantidad_asignar" min="1" value="1"></div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end gap-2">
    <a href="listar.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancelar</a>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Guardar y Asignar</button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>

<script>
$(function(){
  function toggleCampos() {
    const t = $('#tipo_insumo').val();
    if (t === 'Varios') { $('#camposVarios').show(); $('#camposUnitarios').hide(); $('#rowCantidadAsignar').show(); }
    else if (t) { $('#camposVarios').hide(); $('#camposUnitarios').show(); $('#rowCantidadAsignar').hide(); }
    else { $('#camposVarios, #camposUnitarios, #rowCantidadAsignar').hide(); }
  }
  $('#tipo_insumo').on('change', toggleCampos);
  toggleCampos();

  // Cargar sedes por localidad
  $('#selLocalidad').on('change', function(){
    const id = $(this).val();
    const $s = $('#selSede');
    setLoading($s, 'Cargando sedes...');
    $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id }, function(r){
      if (r && r.success) { $s.html(r.options); $('#selArea').html('<option value="">Seleccione un área</option>'); }
      else { $s.html('<option value="">Sin sedes</option>'); }
    });
  });

  // Cargar áreas por sede
  $('#selSede').on('change', function(){
    const id = $(this).val();
    const $a = $('#selArea');
    setLoading($a, 'Cargando áreas...');
    $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id }, function(r){
      if (r && r.success) { $a.html(r.options); }
      else { $a.html('<option value="">Sin áreas</option>'); }
    });
  });
});
</script>

