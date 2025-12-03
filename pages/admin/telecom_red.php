<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('telecom', 'ver');

$db = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("INSERT INTO sedes_red_dispositivos (id_sede, tipo_dispositivo, marca, modelo, cantidad, ubicacion, estado, observaciones) VALUES (?,?,?,?,?,?,?,?)")
         ->execute([(int)$_POST['id_sede'], trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado']), ($_POST['observaciones'] ?? null) ?: null]);
      $_SESSION['mensaje'] = 'Dispositivo agregado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'editar') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("UPDATE sedes_red_dispositivos SET id_sede=?, tipo_dispositivo=?, marca=?, modelo=?, cantidad=?, ubicacion=?, estado=?, observaciones=? WHERE id_dispositivo=?")
         ->execute([(int)$_POST['id_sede'], trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado']), ($_POST['observaciones'] ?? null) ?: null, (int)$_POST['id_dispositivo']]);
      $_SESSION['mensaje'] = 'Dispositivo actualizado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar') {
      verificarPermiso('telecom', 'eliminar');
      $db->prepare("DELETE FROM sedes_red_dispositivos WHERE id_dispositivo = ?")->execute([(int)$_POST['id_dispositivo']]);
      $_SESSION['mensaje'] = 'Dispositivo eliminado'; $_SESSION['tipo_mensaje'] = 'success';
    }
    header('Location: telecom_red.php'); exit;
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); $_SESSION['tipo_mensaje'] = 'danger'; header('Location: telecom_red.php'); exit;
  }
}

$rows = $db->query("SELECT d.*, s.nombre_sede, l.nombre_localidad, l.id_localidad FROM sedes_red_dispositivos d JOIN sedes s ON s.id_sede=d.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede, d.tipo_dispositivo")->fetchAll();
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.nombre_localidad FROM sedes s JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();
include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-network-wired me-2"></i>Infraestructura de Red</h1>
    <?php if (tienePermiso('telecom', 'editar')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRed"><i class="fas fa-plus me-2"></i>Agregar</button>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado (<?php echo count($rows); ?>)</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaRed">
        <thead><tr><th>Localidad</th><th>Sede</th><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Cant.</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($r['nombre_localidad']); ?></strong></td>
            <td>
              <?php echo htmlspecialchars($r['nombre_sede']); ?>
              <?php if (!empty($r['observaciones'])): ?>
                <br><small class="text-muted"><i class="fas fa-comment-dots me-1"></i><?php echo htmlspecialchars($r['observaciones']); ?></small>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-info"><?php echo htmlspecialchars($r['tipo_dispositivo']); ?></span></td>
            <td><?php echo htmlspecialchars($r['marca'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($r['modelo'] ?: '-'); ?></td>
            <td><span class="badge bg-dark"><?php echo (int)$r['cantidad']; ?></span></td>
            <td><?php echo htmlspecialchars($r['ubicacion'] ?: '-'); ?></td>
            <td><?php $e=$r['estado']; $cls=$e==='Activo'?'estado-activa':'estado-baja'; ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
            <td>
              <div class="btn-group" role="group">
                <?php if (tienePermiso('telecom', 'editar')): ?>
                <button class="btn btn-sm btn-warning btn-edit-red" 
                        data-bs-toggle="tooltip" 
                        title="Editar dispositivo"
                        aria-label="Editar dispositivo" 
                        data-row='<?php echo json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                  <i class="fas fa-edit" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
                
                <?php if (tienePermiso('telecom', 'eliminar')): ?>
                <button class="btn btn-sm btn-danger" 
                        data-bs-toggle="tooltip" 
                        title="Eliminar dispositivo"
                        aria-label="Eliminar dispositivo" 
                        onclick="delRed(<?php echo (int)$r['id_dispositivo']; ?>)">
                  <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Botones de exportación -->
<div class="row mt-3">
  <div class="col-12">
    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-success" onclick="exportarExcel('tablaRed', 'infraestructura_red')">
        <i class="fas fa-file-excel me-2"></i>Exportar Excel
      </button>
      <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaRed', 'infraestructura_red')">
        <i class="fas fa-print me-2"></i>Imprimir
      </button>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRed" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="modalRedTitle">Agregar Dispositivo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formRed" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="accion" id="accion" value="agregar"><input type="hidden" name="id_dispositivo" id="id_dispositivo">
      <div class="mb-2"><label class="form-label">Localidad *</label>
        <select id="id_localidad" class="form-select" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione localidad</div>
      </div>
      <div class="mb-2"><label class="form-label">Sede *</label>
        <select name="id_sede" id="id_sede" class="form-select" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione sede</div>
      </div>
      <div class="mb-2"><label class="form-label">Tipo *</label>
        <select name="tipo_dispositivo" id="tipo_dispositivo" class="form-select" required>
          <option value="">Seleccione</option>
          <option>Switch</option><option>Router</option><option>UPS</option><option>AP</option><option>Firewall</option>
        </select>
      </div>
      <div class="row g-2">
        <div class="col"><label class="form-label">Marca</label><input type="text" name="marca" id="marca" class="form-control"></div>
        <div class="col"><label class="form-label">Modelo</label><input type="text" name="modelo" id="modelo" class="form-control"></div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col"><label class="form-label">Cantidad *</label><input type="number" min="1" name="cantidad" id="cantidad" class="form-control" required value="1"></div>
        <div class="col"><label class="form-label">Ubicación</label><input type="text" name="ubicacion" id="ubicacion" class="form-control"></div>
      </div>
      <div class="mb-2 mt-1"><label class="form-label">Estado *</label>
        <select name="estado" id="estado" class="form-select" required><option>Activo</option><option>De Baja</option></select>
      </div>
      <div class="mb-2"><label class="form-label">Observaciones</label><textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
  </form>
</div></div></div>

<form id="formDel" method="POST" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar">
  <input type="hidden" name="id_dispositivo" id="del_id">
</form>

<script>
function editRed(r){
  console.log('editRed llamado', r);
  $('#modalRedTitle').text('Editar Dispositivo'); $('#accion').val('editar');
  $('#id_dispositivo').val(r.id_dispositivo);
  if (r.id_localidad) { $('#id_localidad').val(r.id_localidad); }
  cargarSedes(r.id_localidad);
  setTimeout(function(){ $('#id_sede').val(r.id_sede).trigger('change'); }, 200);
  $('#tipo_dispositivo').val(r.tipo_dispositivo); $('#marca').val(r.marca||''); $('#modelo').val(r.modelo||'');
  $('#cantidad').val(r.cantidad||1); $('#ubicacion').val(r.ubicacion||''); $('#estado').val(r.estado); $('#observaciones').val(r.observaciones||'');
  new bootstrap.Modal(document.getElementById('modalRed')).show();
}
function delRed(id){ if(confirm('¿Eliminar dispositivo?')){ $('#del_id').val(id); $('#formDel').submit(); } }
$('#modalRed').on('hidden.bs.modal', function(){ $('#modalRedTitle').text('Agregar Dispositivo'); $('#accion').val('agregar'); $('#formRed')[0].reset(); $('#id_sede').val('').trigger('change'); $('#formRed').removeClass('was-validated'); });
$('#formRed').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); });
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades(){
  $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{
    const $loc = $('#id_localidad');
    $loc.html('<option value="">Seleccione</option>');
    if(r.success){ r.data.forEach(l=> $loc.append(`<option value="${l.id}">${l.nombre}</option>`)); }
    $loc.trigger('change.select2');
  });
}
function cargarSedes(localidad){
  const $s = $('#id_sede');
  $s.html('<option value="">Seleccione</option>');
  if(!localidad){ $s.trigger('change.select2'); return; }
  $.getJSON(`${BASE}/ajax/cargar_sedes.php`, { localidad_id: localidad }).done(r=>{
    const data = r && r.data ? r.data : r; const lista = data && data.sedes ? data.sedes : [];
    lista.forEach(x=> $s.append(`<option value="${parseInt(x.id,10)}">${x.nombre}</option>`));
    $s.trigger('change.select2');
  });
}
$(function(){
  cargarLocalidades();
  $('#id_localidad').on('change', function(){ cargarSedes($(this).val()); });
  
  // Event delegation para botones de editar (evita problema con tooltips)
  $(document).on('click', '.btn-edit-red', function(e) {
    e.preventDefault();
    e.stopPropagation();
    try {
      var data = $(this).data('row');
      console.log('Botón editar clickeado', data);
      editRed(data);
    } catch(err) {
      console.error('Error al editar:', err);
    }
  });
});
</script>

<?php include '../../includes/footer.php'; ?>

