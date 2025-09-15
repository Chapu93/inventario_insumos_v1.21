<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Cargas iniciales
$puntosStock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $tipoInsumo = trim($_POST['tipo_insumo'] ?? '');
        $nombreInsumo = trim($_POST['nombre_insumo'] ?? '');
        if ($tipoInsumo === '' || $nombreInsumo === '') {
            throw new Exception('Complete nombre y tipo de insumo');
        }

        $esVarios = ($tipoInsumo === 'Varios');
        $cantidad = $esVarios ? max(1, (int)($_POST['cantidad'] ?? 1)) : 1;
        $subcategoriaVarios = $esVarios ? (($_POST['subcategoria_varios'] ?? '') ?: null) : null;
        $descripcionGeneral = $esVarios ? (($_POST['descripcion_general'] ?? '') ?: null) : null;
        $numeroSerie = !$esVarios ? (($_POST['numero_serie'] ?? '') ?: null) : null;
        $idFisico = !$esVarios ? (($_POST['id_fisico'] ?? '') ?: null) : null;
        $idPatrimonio = !$esVarios ? (($_POST['id_patrimonio'] ?? '') ?: null) : null;

        if (!$esVarios) {
            if ($numeroSerie === null || $idFisico === null || $idPatrimonio === null) {
                throw new Exception('Serie, ID Físico e ID Patrimonio son obligatorios');
            }
        }

        $fechaAdquisicion = ($_POST['fecha_adquisicion'] ?? date('Y-m-d')) ?: null;
        $idPuntoStock = (int)($_POST['id_punto_stock_actual'] ?? 0);
        if ($idPuntoStock <= 0) {
            throw new Exception('Seleccione punto de almacenamiento');
        }

        // Datos de asignación
        $idSede = (int)($_POST['id_sede'] ?? 0);
        $idArea = (int)($_POST['id_area'] ?? 0);
        $personaNombre = trim($_POST['persona_nombre'] ?? '');
        $personaApellido = trim($_POST['persona_apellido'] ?? '');
        $fechaAsignacion = ($_POST['fecha_asignacion'] ?? date('Y-m-d')) ?: date('Y-m-d');
        $observaciones = ($_POST['observaciones'] ?? '') ?: null;
        if ($idSede <= 0 || $idArea <= 0 || $personaNombre === '' || $personaApellido === '') {
            throw new Exception('Complete Localidad/Sede/Área y datos de la persona');
        }

        // Insert del insumo (queda Asignado y con ubicación)
        $stmt = $db->prepare("INSERT INTO insumos
            (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual, id_sede_actual, id_area_asignacion_actual)
            VALUES (?,?,?,?,?,?,?,?,?,'Asignado', ?, ?, ?)");
        $stmt->execute([
            $nombreInsumo, $tipoInsumo, $subcategoriaVarios, $descripcionGeneral, $numeroSerie, $idFisico, $idPatrimonio,
            $cantidad, $fechaAdquisicion, $idPuntoStock, $idSede, $idArea
        ]);
        $idInsumo = (int)$db->lastInsertId();

        // Insert de especificaciones por tipo (opcional)
        switch ($tipoInsumo) {
            case 'PC Completa':
                $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
                   ->execute([$idInsumo, $_POST['procesador'] ?? null, $_POST['ram_gb'] ?? null, $_POST['almacenamiento_gb'] ?? null, $_POST['mother'] ?? null]);
                break;
            case 'Notebook':
                $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb) VALUES (?,?,?,?,?,?)")
                   ->execute([$idInsumo, $_POST['marca_notebook'] ?? null, $_POST['modelo_notebook'] ?? null, $_POST['procesador_notebook'] ?? null, $_POST['ram_gb_notebook'] ?? null, $_POST['almacenamiento_gb_notebook'] ?? null]);
                break;
            case 'Impresora':
                $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$idInsumo, $_POST['marca_impresora'] ?? null, $_POST['modelo_impresora'] ?? null]);
                break;
            case 'Monitor':
                $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                   ->execute([$idInsumo, $_POST['marca_monitor'] ?? null, $_POST['modelo_monitor'] ?? null, $_POST['pulgadas'] ?? null, $_POST['conexion_monitor'] ?? null]);
                break;
            case 'Escaner':
                $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$idInsumo, $_POST['marca_escaner'] ?? null, $_POST['modelo_escaner'] ?? null]);
                break;
        }

        // Crear remito y detalle
        $numRemito = generarNumeroRemito();
        $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, estado, observaciones) VALUES (?,?,?,?,?,?,'Activa',?)")
           ->execute([$numRemito, $idSede, $idArea, $personaNombre, $personaApellido, $fechaAsignacion, $observaciones]);
        $idRemito = (int)$db->lastInsertId();

        $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)")
           ->execute([$idRemito, $idInsumo, $cantidad]);

        $db->commit();

        $_SESSION['mensaje'] = 'Insumo creado y asignado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/insumos/listar.php');
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
                        <div class="mb-2"><label class="form-label">N° de Serie *</label><input class="form-control" name="numero_serie"></div>
                        <div class="mb-2"><label class="form-label">ID Físico *</label><input class="form-control" name="id_fisico"></div>
                        <div class="mb-2"><label class="form-label">ID Patrimonio *</label><input class="form-control" name="id_patrimonio"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Fecha de Adquisición</label>
                        <input type="date" class="form-control" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Punto de Almacenamiento *</label>
                        <select class="form-select" name="id_punto_stock_actual" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($puntosStock as $p): ?>
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
                        <div class="col-sm-6"><label class="form-label">Nombre *</label><input class="form-control" name="persona_nombre" required></div>
                        <div class="col-sm-6"><label class="form-label">Apellido *</label><input class="form-control" name="persona_apellido" required></div>
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
    }).fail(()=>{ $('#sede').html('<option value="">Seleccione</option>'); });
  });
  $('#sede').on('change', function(){
    const id = $(this).val();
    setLoading($('#area'), 'Cargando áreas...');
    $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id }).done(r=>{
      $('#area').html(r && r.options ? r.options : '<option value="">Seleccione</option>');
    }).fail(()=>{ $('#area').html('<option value="">Seleccione</option>'); });
  });
});
</script>

