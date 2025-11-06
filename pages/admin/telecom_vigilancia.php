<?php
require_once '../../includes/config.php';
\nrequerirAutenticacion();
$db = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
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

$servicios = $db->query("SELECT v.*, s.nombre_sede, l.id_localidad, l.nombre_localidad FROM sedes_vigilancia v JOIN sedes s ON s.id_sede=v.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede, v.proveedor")->fetchAll();
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.nombre_localidad FROM sedes s JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();
// Conteo de dispositivos por servicio para mostrar en la lista maestra
$rowsDispCount = $db->query("SELECT id_vigilancia, COALESCE(SUM(cantidad),0) AS total_dispositivos FROM sedes_vigilancia_dispositivos GROUP BY id_vigilancia")->fetchAll();
$rowsCamsAct = $db->query("SELECT id_vigilancia, COALESCE(SUM(cantidad),0) AS cam_activas FROM sedes_vigilancia_dispositivos WHERE tipo_dispositivo='Cámara' AND estado='Activo' GROUP BY id_vigilancia")->fetchAll();
$dispCount = [];
$camActivas = [];
foreach ($rowsDispCount as $r) { $dispCount[(int)$r['id_vigilancia']] = (int)$r['total_dispositivos']; }
foreach ($rowsCamsAct as $r) { $camActivas[(int)$r['id_vigilancia']] = (int)$r['cam_activas']; }
include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-video me-2"></i>Vigilancia</h1>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServ"><i class="fas fa-plus me-2"></i>Agregar Servicio</button>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0"><i class="fas fa-building-shield me-2"></i>Servicios (<?php echo count($servicios); ?>)</h5><div class="small text-muted">Total dispositivos: <strong><?php echo array_sum($dispCount ?: []); ?></strong> · Cámaras activas: <strong><?php echo array_sum($camActivas ?: []); ?></strong></div></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable"><thead><tr><th>Sede</th><th>Localidad</th><th>Proveedor</th><th>Estado</th><th>Dispositivos</th><th>Cámaras activas</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach($servicios as $v): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($v['nombre_sede']); ?></strong></td>
              <td><?php echo htmlspecialchars($v['nombre_localidad']); ?></td>
              <td><?php echo htmlspecialchars($v['proveedor']); ?></td>
              <td><?php $e=$v['estado_servicio']; $cls=$e==='Activo'?'estado-activa':($e==='Pendiente'?'estado-asignado':'estado-baja'); ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
              <td><span class="badge bg-secondary"><?php echo (int)($dispCount[(int)$v['id_vigilancia']] ?? 0); ?></span></td>
              <td><span class="badge bg-success"><?php echo (int)($camActivas[(int)$v['id_vigilancia']] ?? 0); ?></span></td>
              <td>
                <div class="btn-group" role="group">
                  <a class="btn btn-sm btn-info" 
                     href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia_servicio.php?id_vigilancia=<?php echo (int)$v['id_vigilancia']; ?>" 
                     data-bs-toggle="tooltip" 
                     title="Ver detalle"
                     aria-label="Ver detalle">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                  </a>
                  <button class="btn btn-sm btn-warning btn-edit-serv" 
                          data-bs-toggle="tooltip" 
                          title="Editar servicio"
                          aria-label="Editar servicio" 
                          data-row='<?php echo json_encode($v, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                    <i class="fas fa-edit" aria-hidden="true"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" 
                          data-bs-toggle="tooltip" 
                          title="Eliminar servicio"
                          aria-label="Eliminar servicio" 
                          onclick="delServ(<?php echo (int)$v['id_vigilancia']; ?>)">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                  </button>
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
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="accion" id="accionServ" value="agregar_serv"><input type="hidden" name="id_vigilancia" id="id_vigilancia">
      <div class="mb-2"><label class="form-label">Localidad *</label>
        <select id="id_localidad_serv" class="form-select" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione localidad</div>
      </div>
      <div class="mb-2"><label class="form-label">Sede *</label>
        <select name="id_sede" id="id_sede_serv" class="form-select" required>
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

<form id="formDelServ" method="POST" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar_serv">
  <input type="hidden" name="id_vigilancia" id="del_serv">
</form>

<script>
function editServ(v){ 
  console.log('editServ llamado', v);
  $('#modalServTitle').text('Editar Servicio'); 
  $('#accionServ').val('editar_serv'); 
  $('#id_vigilancia').val(v.id_vigilancia); 
  $('#proveedor').val(v.proveedor); 
  $('#estado_servicio').val(v.estado_servicio); 
  $('#observaciones_serv').val(v.observaciones||''); 
  
  // Cargar localidad y luego sedes
  $('#id_localidad_serv').val(v.id_localidad);
  cargarSedesServ(v.id_localidad);
  
  // Esperar a que las sedes se carguen antes de preseleccionar
  setTimeout(function() {
    $('#id_sede_serv').val(v.id_sede);
    new bootstrap.Modal(document.getElementById('modalServ')).show();
  }, 300);
}
function delServ(id){ if(confirm('¿Eliminar servicio y sus dispositivos?')){ $('#del_serv').val(id); $('#formDelServ').submit(); } }
$('#modalServ').on('hidden.bs.modal', function(){ $('#modalServTitle').text('Agregar Servicio'); $('#accionServ').val('agregar_serv'); $('#formServ')[0].reset(); $('#id_sede_serv').val('').trigger('change'); $('#formServ').removeClass('was-validated'); });
$('#formServ').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); });
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidadesServ(){ $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{ const $l=$('#id_localidad_serv'); $l.html('<option value="">Seleccione</option>'); if(r.success){ r.data.forEach(x=> $l.append(`<option value="${x.id}">${x.nombre}</option>`)); } }); }
function cargarSedesServ(loc){ const $s=$('#id_sede_serv'); $s.html('<option value="">Seleccione</option>'); if(!loc){ return; } $.getJSON(`${BASE}/ajax/cargar_sedes.php`, { localidad_id: loc }).done(r=>{ const data=r&&r.data?r.data:r; const lista=data&&data.sedes?data.sedes:[]; lista.forEach(x=> $s.append(`<option value="${parseInt(x.id,10)}">${x.nombre}</option>`)); }); }
$(function(){
  const url = new URL(window.location.href);
  const qLoc = url.searchParams.get('id_localidad');
  const qSede = url.searchParams.get('id_sede');
  const qOpen = url.searchParams.get('open');
  // Cargar localidades y luego, si corresponde, preseleccionar y abrir
  cargarLocalidadesServ();
  $('#id_localidad_serv').on('change', function(){ cargarSedesServ($(this).val()); });
  if (qOpen === 'add') {
    if (qLoc) {
      const interval = setInterval(function(){
        // Esperar a que las opciones de localidad estén cargadas
        const $loc = $('#id_localidad_serv');
        if ($loc.find('option').length > 1) {
          clearInterval(interval);
          $loc.val(qLoc);
          $.when(cargarSedesServ(qLoc)).done(function(){
            setTimeout(function(){ if(qSede){ $('#id_sede_serv').val(String(qSede)); } new bootstrap.Modal(document.getElementById('modalServ')).show(); }, 150);
          });
        }
      }, 50);
    } else {
      new bootstrap.Modal(document.getElementById('modalServ')).show();
    }
  }
  
  // Event delegation para botones de editar (evita problema con tooltips)
  $(document).on('click', '.btn-edit-serv', function(e) {
    e.preventDefault();
    e.stopPropagation();
    try {
      var data = $(this).data('row');
      console.log('Botón editar clickeado', data);
      editServ(data);
    } catch(err) {
      console.error('Error al editar:', err);
    }
  });
});
// No select2 para igualar estilo de otros modales
</script>

<?php include '../../includes/footer.php'; ?>

