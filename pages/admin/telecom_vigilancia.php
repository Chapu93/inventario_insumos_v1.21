<?php
require_once '../../includes/config.php';
$db = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar_serv') {
      $db->prepare("INSERT INTO sedes_vigilancia (id_sede, proveedor, estado_servicio, observaciones) VALUES (?,?,?,?)")
         ->execute([(int)$_POST['id_sede'], trim($_POST['proveedor']), trim($_POST['estado_servicio']), ($_POST['observaciones'] ?? null) ?: null]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia agregado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'editar_serv') {
      $db->prepare("UPDATE sedes_vigilancia SET id_sede=?, proveedor=?, estado_servicio=?, observaciones=? WHERE id_vigilancia=?")
         ->execute([(int)$_POST['id_sede'], trim($_POST['proveedor']), trim($_POST['estado_servicio']), ($_POST['observaciones'] ?? null) ?: null, (int)$_POST['id_vigilancia']]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia actualizado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar_serv') {
      $db->prepare("DELETE FROM sedes_vigilancia_dispositivos WHERE id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia']]);
      $db->prepare("DELETE FROM sedes_vigilancia WHERE id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia']]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia eliminado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'agregar_disp') {
      $db->prepare("INSERT INTO sedes_vigilancia_dispositivos (id_vigilancia, tipo_dispositivo, marca, modelo, cantidad, ubicacion, estado) VALUES (?,?,?,?,?,?,?)")
         ->execute([(int)$_POST['id_vigilancia'], trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado'])]);
      $_SESSION['mensaje'] = 'Dispositivo agregado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'editar_disp') {
      $db->prepare("UPDATE sedes_vigilancia_dispositivos SET id_vigilancia=?, tipo_dispositivo=?, marca=?, modelo=?, cantidad=?, ubicacion=?, estado=? WHERE id_vigilancia_dispositivo=?")
         ->execute([(int)$_POST['id_vigilancia'], trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado']), (int)$_POST['id_vigilancia_dispositivo']]);
      $_SESSION['mensaje'] = 'Dispositivo actualizado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar_disp') {
      $db->prepare("DELETE FROM sedes_vigilancia_dispositivos WHERE id_vigilancia_dispositivo = ?")->execute([(int)$_POST['id_vigilancia_dispositivo']]);
      $_SESSION['mensaje'] = 'Dispositivo eliminado'; $_SESSION['tipo_mensaje'] = 'success';
    }
    header('Location: telecom_vigilancia.php'); exit;
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); $_SESSION['tipo_mensaje'] = 'danger'; header('Location: telecom_vigilancia.php'); exit;
  }
}

$servicios = $db->query("SELECT v.*, s.nombre_sede, l.nombre_localidad FROM sedes_vigilancia v JOIN sedes s ON s.id_sede=v.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede, v.proveedor")->fetchAll();
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.nombre_localidad FROM sedes s JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();
$disps = $db->query("SELECT d.*, v.proveedor, s.nombre_sede, l.nombre_localidad FROM sedes_vigilancia_dispositivos d JOIN sedes_vigilancia v ON v.id_vigilancia=d.id_vigilancia JOIN sedes s ON s.id_sede=v.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede, v.proveedor, d.tipo_dispositivo")->fetchAll();
include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-video me-2"></i>Vigilancia</h1>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServ"><i class="fas fa-plus me-2"></i>Agregar Servicio</button>
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDisp"><i class="fas fa-plus me-2"></i>Agregar Dispositivo</button>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-building-shield me-2"></i>Servicios (<?php echo count($servicios); ?>)</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable"><thead><tr><th>Sede</th><th>Localidad</th><th>Proveedor</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach($servicios as $v): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($v['nombre_sede']); ?></strong></td>
              <td><?php echo htmlspecialchars($v['nombre_localidad']); ?></td>
              <td><?php echo htmlspecialchars($v['proveedor']); ?></td>
              <td><?php $e=$v['estado_servicio']; $cls=$e==='Activo'?'estado-activa':($e==='Pendiente'?'estado-asignado':'estado-baja'); ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-warning" onclick='editServ(<?php echo json_encode($v, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="delServ(<?php echo (int)$v['id_vigilancia']; ?>)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-camera me-2"></i>Dispositivos (<?php echo count($disps); ?>)</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable"><thead><tr><th>Sede</th><th>Proveedor</th><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Cant.</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach($disps as $d): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($d['nombre_sede']); ?></strong></td>
              <td><?php echo htmlspecialchars($d['proveedor']); ?></td>
              <td><span class="badge bg-info"><?php echo htmlspecialchars($d['tipo_dispositivo']); ?></span></td>
              <td><?php echo htmlspecialchars($d['marca'] ?: '-'); ?></td>
              <td><?php echo htmlspecialchars($d['modelo'] ?: '-'); ?></td>
              <td><span class="badge bg-dark"><?php echo (int)$d['cantidad']; ?></span></td>
              <td><?php echo htmlspecialchars($d['ubicacion'] ?: '-'); ?></td>
              <td><?php $e=$d['estado']; $cls=$e==='Activo'?'estado-activa':'estado-baja'; ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-warning" onclick='editDisp(<?php echo json_encode($d, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="delDisp(<?php echo (int)$d['id_vigilancia_dispositivo']; ?>)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modales -->
<div class="modal fade" id="modalServ" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="modalServTitle">Agregar Servicio</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formServ" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="accion" id="accionServ" value="agregar_serv"><input type="hidden" name="id_vigilancia" id="id_vigilancia">
      <div class="mb-2"><label class="form-label">Localidad *</label>
        <select id="id_localidad_serv" class="form-select select2" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione localidad</div>
      </div>
      <div class="mb-2"><label class="form-label">Sede *</label>
        <select name="id_sede" id="id_sede_serv" class="form-select select2" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione sede</div>
      </div>
      <div class="mb-2"><label class="form-label">Proveedor *</label><input type="text" name="proveedor" id="proveedor" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Estado *</label><select class="form-select" name="estado_servicio" id="estado_servicio" required><option>Activo</option><option>Pendiente</option><option>De Baja</option></select></div>
      <div class="mb-2"><label class="form-label">Observaciones</label><textarea name="observaciones" id="observaciones_serv" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="modalDisp" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="modalDispTitle">Agregar Dispositivo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formDisp" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="accion" id="accionDisp" value="agregar_disp"><input type="hidden" name="id_vigilancia_dispositivo" id="id_vigilancia_dispositivo">
      <div class="mb-2"><label class="form-label">Servicio *</label>
        <select name="id_vigilancia" id="id_vigilancia_sel" class="form-select" required>
          <option value="">Seleccione</option>
          <?php foreach($servicios as $v): ?><option value="<?php echo $v['id_vigilancia']; ?>"><?php echo htmlspecialchars($v['nombre_localidad'].' - '.$v['nombre_sede'].' - '.$v['proveedor']); ?></option><?php endforeach; ?>
        </select><div class="invalid-feedback">Seleccione servicio</div>
      </div>
      <div class="mb-2"><label class="form-label">Tipo *</label><select name="tipo_dispositivo" id="tipo_dispositivo" class="form-select" required><option value="">Seleccione</option><option>DVR</option><option>NVR</option><option>Cámara</option><option>Sensor</option><option>Monitor</option></select></div>
      <div class="row g-2"><div class="col"><label class="form-label">Marca</label><input type="text" name="marca" id="marca" class="form-control"></div><div class="col"><label class="form-label">Modelo</label><input type="text" name="modelo" id="modelo" class="form-control"></div></div>
      <div class="row g-2 mt-1"><div class="col"><label class="form-label">Cantidad *</label><input type="number" min="1" name="cantidad" id="cantidad" class="form-control" required value="1"></div><div class="col"><label class="form-label">Ubicación</label><input type="text" name="ubicacion" id="ubicacion" class="form-control"></div></div>
      <div class="mb-2 mt-1"><label class="form-label">Estado *</label><select name="estado" id="estado" class="form-select" required><option>Activo</option><option>De Baja</option></select></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
  </form>
</div></div></div>

<form id="formDelServ" method="POST" style="display:none"><input type="hidden" name="accion" value="eliminar_serv"><input type="hidden" name="id_vigilancia" id="del_serv"></form>
<form id="formDelDisp" method="POST" style="display:none"><input type="hidden" name="accion" value="eliminar_disp"><input type="hidden" name="id_vigilancia_dispositivo" id="del_disp"></form>

<script>
function editServ(v){ $('#modalServTitle').text('Editar Servicio'); $('#accionServ').val('editar_serv'); $('#id_vigilancia').val(v.id_vigilancia); $('#id_sede_serv').val(v.id_sede).trigger('change'); $('#proveedor').val(v.proveedor); $('#estado_servicio').val(v.estado_servicio); $('#observaciones_serv').val(v.observaciones||''); new bootstrap.Modal(document.getElementById('modalServ')).show(); }
function delServ(id){ if(confirm('¿Eliminar servicio y sus dispositivos?')){ $('#del_serv').val(id); $('#formDelServ').submit(); } }
function editDisp(d){ $('#modalDispTitle').text('Editar Dispositivo'); $('#accionDisp').val('editar_disp'); $('#id_vigilancia_dispositivo').val(d.id_vigilancia_dispositivo); $('#id_vigilancia_sel').val(d.id_vigilancia); $('#tipo_dispositivo').val(d.tipo_dispositivo); $('#marca').val(d.marca||''); $('#modelo').val(d.modelo||''); $('#cantidad').val(d.cantidad||1); $('#ubicacion').val(d.ubicacion||''); $('#estado').val(d.estado); new bootstrap.Modal(document.getElementById('modalDisp')).show(); }
function delDisp(id){ if(confirm('¿Eliminar dispositivo?')){ $('#del_disp').val(id); $('#formDelDisp').submit(); } }
$('#modalServ').on('hidden.bs.modal', function(){ $('#modalServTitle').text('Agregar Servicio'); $('#accionServ').val('agregar_serv'); $('#formServ')[0].reset(); $('#id_sede_serv').val('').trigger('change'); $('#formServ').removeClass('was-validated'); });
$('#modalDisp').on('hidden.bs.modal', function(){ $('#modalDispTitle').text('Agregar Dispositivo'); $('#accionDisp').val('agregar_disp'); $('#formDisp')[0].reset(); $('#id_vigilancia_sel').val(''); $('#formDisp').removeClass('was-validated'); });
$('#formServ, #formDisp').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); });
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidadesServ(){ $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{ const $l=$('#id_localidad_serv'); $l.html('<option value="">Seleccione</option>'); if(r.success){ r.data.forEach(x=> $l.append(`<option value="${x.id}">${x.nombre}</option>`)); } $l.trigger('change.select2'); }); }
function cargarSedesServ(loc){ const $s=$('#id_sede_serv'); $s.html('<option value="">Seleccione</option>'); if(!loc){ $s.trigger('change.select2'); return; } $.getJSON(`${BASE}/ajax/sedes_por_localidad.php`, { localidad_id: loc }).done(r=>{ if(r.success){ r.data.forEach(x=> $s.append(`<option value="${x.id}">${x.nombre}</option>`)); } $s.trigger('change.select2'); }); }
$(function(){ cargarLocalidadesServ(); $('#id_localidad_serv').on('change', function(){ cargarSedesServ($(this).val()); }); });
$('#modalServ').on('shown.bs.modal', function(){
  $('#modalServ .select2').each(function(){
    var $el = $(this);
    try { if ($el.hasClass('select2-hidden-accessible')) { $el.select2('destroy'); } } catch(e) {}
    $el.select2({ theme:'bootstrap-5', language:'es', width:'100%', dropdownParent: $('#modalServ') });
  });
});
</script>

<?php include '../../includes/footer.php'; ?>

