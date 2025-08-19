<?php
require_once '../../includes/config.php';
$db = conectarDB();

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        $idsInsumo = isset($_POST['insumos']) ? array_map('intval', (array)$_POST['insumos']) : [];
        if (empty($idsInsumo)) { throw new Exception('Seleccione al menos un insumo'); }
        $idSede = (int)$_POST['id_sede'];
        $idArea = (int)$_POST['id_area'];
        $fecha = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $obs = trim($_POST['observaciones'] ?? '');
        if (!$idSede || !$idArea || !$nombre || !$apellido) { throw new Exception('Datos incompletos'); }

        $numero = generarNumeroRemito();
        $cantVarios = isset($_POST['cantidades_varios']) && is_array($_POST['cantidades_varios']) ? $_POST['cantidades_varios'] : [];

        // Insertar cabecera de remito (esquema nuevo)
        $stmtCab = $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?,?,?,?,?,?,?)");
        $stmtCab->execute([$numero, $idSede, $idArea, $nombre, $apellido, $fecha, $obs ?: null]);
        $idRemito = (int)$db->lastInsertId();
        $stmtDet = $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)");

        foreach ($idsInsumo as $idIns) {
            // Lock row
            $stmt = $db->prepare("SELECT tipo_insumo, cantidad FROM insumos WHERE id_insumo = ? FOR UPDATE");
            $stmt->execute([$idIns]);
            $ins = $stmt->fetch();
            if (!$ins) { throw new Exception('Insumo no encontrado: ' . $idIns); }

            $reps = 1;
            if ($ins['tipo_insumo'] === 'Varios') {
                $sol = isset($cantVarios[$idIns]) ? max(1, (int)$cantVarios[$idIns]) : 1;
                if ($sol > (int)$ins['cantidad']) { throw new Exception('Stock insuficiente en insumo ' . $idIns); }
                $reps = $sol;
            }

            $stmtDet->execute([$idRemito, $idIns, $reps]);

            if ($ins['tipo_insumo'] === 'Varios') {
                $nuevo = (int)$ins['cantidad'] - $reps;
                $estado = $nuevo > 0 ? 'Disponible' : 'Asignado';
                $upd = $db->prepare("UPDATE insumos SET cantidad=?, estado=?, id_sede_actual=?, id_area_asignacion_actual=? WHERE id_insumo=?");
                $upd->execute([$nuevo, $estado, $idSede, $idArea, $idIns]);
            } else {
                $upd = $db->prepare("UPDATE insumos SET estado='Asignado', id_sede_actual=?, id_area_asignacion_actual=? WHERE id_insumo=?");
                $upd->execute([$idSede, $idArea, $idIns]);
            }
        }
        $db->commit();
        header('Location: ' . app_base_url() . '/pages/reportes/remito.php?remito=' . urlencode($numero));
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $error = $e->getMessage();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<div class="row">
  <div class="col-12">
    <h1><i class="fas fa-plus me-2"></i>Nueva Asignación (Simple)</h1>
  </div>
</div>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <form method="POST" id="formSimple" class="needs-validation" novalidate>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Localidad *</label>
          <select id="sel_localidad" class="form-select" required></select>
          <div class="invalid-feedback">Seleccione una localidad</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Sede *</label>
          <select id="sel_sede" name="id_sede" class="form-select" required></select>
          <div class="invalid-feedback">Seleccione una sede</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Área *</label>
          <select id="sel_area" name="id_area" class="form-select" required></select>
          <div class="invalid-feedback">Seleccione un área</div>
        </div>
        <div class="col-12">
          <label class="form-label">Insumos Disponibles *</label>
          <select id="sel_insumos" name="insumos[]" class="form-select" multiple required></select>
          <div class="invalid-feedback">Seleccione al menos un insumo</div>
        </div>
        <div class="col-12" id="cantidades_varios_container"></div>
        <div class="col-md-4">
          <label class="form-label">Fecha *</label>
          <input type="date" class="form-control" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nombre *</label>
          <input type="text" class="form-control" name="nombre" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Apellido *</label>
          <input type="text" class="form-control" name="apellido" required>
        </div>
        <div class="col-12">
          <label class="form-label">Observaciones</label>
          <textarea class="form-control" name="observaciones" rows="2"></textarea>
        </div>
      </div>
      <div class="mt-3 d-flex justify-content-end gap-2">
        <a href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php" class="btn btn-secondary">Cancelar</a>
        <button class="btn btn-primary" type="submit">Crear Asignación</button>
      </div>
    </form>
  </div>
</div>
<script>
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades() {
  const url = `${BASE}/ajax/localidades_list.php`;
  $.getJSON(url).done(r => {
    if (r.success) {
      $('#sel_localidad').html('<option value="">Seleccione una localidad</option>');
      r.data.forEach(l => {
        $('#sel_localidad').append(`<option value="${l.id}">${l.nombre}</option>`);
      });
      $('#sel_localidad').trigger('change.select2');
    } else { console.error(r.error); }
  }).fail((x)=> console.error('localidades_list fail', x.status, x.responseText));
}
function cargarSedes(localidadId) {
  const url = `${BASE}/ajax/sedes_por_localidad.php`;
  $.getJSON(url, { localidad_id: localidadId }).done(r => {
    $('#sel_sede').html('<option value="">Seleccione una sede</option>');
    if (r.success) {
      r.data.forEach(s => { $('#sel_sede').append(`<option value="${s.id}">${s.nombre}</option>`); });
    }
    $('#sel_sede').trigger('change.select2');
  }).fail((x)=> console.error('sedes_por_localidad fail', x.status, x.responseText));
}
function cargarAreas(sedeId) {
  const url = `${BASE}/ajax/areas_por_sede.php`;
  $.getJSON(url, { sede_id: sedeId }).done(r => {
    $('#sel_area').html('<option value="">Seleccione un área</option>');
    if (r.success) {
      r.data.forEach(a => { $('#sel_area').append(`<option value="${a.id}">${a.nombre}</option>`); });
    }
    $('#sel_area').trigger('change.select2');
  }).fail((x)=> console.error('areas_por_sede fail', x.status, x.responseText));
}
function cargarInsumos(sedeId) {
  const url = `${BASE}/ajax/insumos_disponibles.php`;
  $.getJSON(url, { sede_id: sedeId }).done(r => {
    $('#sel_insumos').empty();
    const cont = $('#cantidades_varios_container');
    cont.empty();
    if (r.success) {
      r.data.forEach(i => { $('#sel_insumos').append(`<option value="${i.id}" data-tipo="${i.tipo}" data-max="${i.max}">${i.nombre}</option>`); });
    }
    $('#sel_insumos').trigger('change.select2');
  }).fail((x)=> console.error('insumos_disponibles fail', x.status, x.responseText));
}
function refrescarCantidadesVarios() {
  const cont = $('#cantidades_varios_container');
  cont.empty();
  const sel = $('#sel_insumos option:selected');
  sel.each(function(){
    const tipo = $(this).data('tipo');
    const max = parseInt($(this).data('max') || 1, 10);
    const id = $(this).val();
    const label = $(this).text();
    if (tipo === 'Varios') {
      cont.append(`<div class="row g-2 align-items-center mb-2"><div class="col-sm-8"><small>${label}</small></div><div class="col-sm-4 d-flex align-items-center gap-2"><input type="number" class="form-control form-control-sm" name="cantidades_varios[${id}]" min="1" max="${max}" value="1"><small class="text-muted">max ${max}</small></div></div>`);
    }
  });
}
$(function(){
  $('#sel_localidad, #sel_sede, #sel_area, #sel_insumos').select2({ theme: 'bootstrap-5', width: '100%' });
  cargarLocalidades();
  $('#sel_localidad').on('change', function(){ const id = $(this).val(); $('#sel_sede').empty(); $('#sel_area').empty(); $('#sel_insumos').empty(); if(id){ cargarSedes(id); }});
  $('#sel_sede').on('change', function(){ const id = $(this).val(); $('#sel_area').empty(); $('#sel_insumos').empty(); if(id){ cargarAreas(id); cargarInsumos(id); }});
  $('#sel_insumos').on('change', refrescarCantidadesVarios);
});
</script>
<?php include '../../includes/footer.php'; ?>