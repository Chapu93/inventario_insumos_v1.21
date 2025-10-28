<?php
require_once '../../includes/config.php';
$db = conectarDB();

$idVig = isset($_GET['id_vigilancia']) ? (int)$_GET['id_vigilancia'] : 0;
if ($idVig <= 0) { header('Location: telecom_vigilancia.php'); exit; }

// CRUD solo de dispositivos desde esta vista de detalle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar_disp') {
      $db->prepare("INSERT INTO sedes_vigilancia_dispositivos (id_vigilancia, tipo_dispositivo, marca, modelo, cantidad, ubicacion, estado) VALUES (?,?,?,?,?,?,?)")
         ->execute([$idVig, trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado'])]);
      $_SESSION['mensaje'] = 'Dispositivo agregado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'editar_disp') {
      $db->prepare("UPDATE sedes_vigilancia_dispositivos SET tipo_dispositivo=?, marca=?, modelo=?, cantidad=?, ubicacion=?, estado=? WHERE id_vigilancia_dispositivo=? AND id_vigilancia=?")
         ->execute([trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado']), (int)$_POST['id_vigilancia_dispositivo'], $idVig]);
      $_SESSION['mensaje'] = 'Dispositivo actualizado'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar_disp') {
      $db->prepare("DELETE FROM sedes_vigilancia_dispositivos WHERE id_vigilancia_dispositivo = ? AND id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia_dispositivo'], $idVig]);
      $_SESSION['mensaje'] = 'Dispositivo eliminado'; $_SESSION['tipo_mensaje'] = 'success';
    }
    header('Location: telecom_vigilancia_servicio.php?id_vigilancia='.(int)$idVig); exit;
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); $_SESSION['tipo_mensaje'] = 'danger'; header('Location: telecom_vigilancia_servicio.php?id_vigilancia='.(int)$idVig); exit;
  }
}

// Datos del servicio
$serv = $db->prepare("SELECT v.*, s.id_sede, s.nombre_sede, l.id_localidad, l.nombre_localidad FROM sedes_vigilancia v JOIN sedes s ON s.id_sede=v.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad WHERE v.id_vigilancia=?");
$serv->execute([$idVig]);
$servicio = $serv->fetch();
if (!$servicio) { $_SESSION['mensaje']='Servicio no encontrado'; $_SESSION['tipo_mensaje']='warning'; header('Location: telecom_vigilancia.php'); exit; }

// Dispositivos del servicio
$disps = $db->prepare("SELECT * FROM sedes_vigilancia_dispositivos WHERE id_vigilancia=? ORDER BY tipo_dispositivo, marca, modelo");
$disps->execute([$idVig]);
$dispositivos = $disps->fetchAll();

// KPI simples
$totalDispositivos = (int)$db->prepare("SELECT COALESCE(SUM(cantidad),0) FROM sedes_vigilancia_dispositivos WHERE id_vigilancia=?")->execute([$idVig]) ? (int)$db->query("SELECT COALESCE(SUM(cantidad),0) AS t FROM sedes_vigilancia_dispositivos WHERE id_vigilancia=".(int)$idVig)->fetchColumn() : 0;
$camarasActivas = (int)$db->query("SELECT COALESCE(SUM(cantidad),0) FROM sedes_vigilancia_dispositivos WHERE id_vigilancia=".(int)$idVig." AND tipo_dispositivo='Cámara' AND estado='Activo'")->fetchColumn();

// Tipos disponibles para filtros
$tiposDisponibles = [];
foreach ($dispositivos as $d) { $t = trim((string)$d['tipo_dispositivo']); if ($t !== '' && !in_array($t, $tiposDisponibles, true)) { $tiposDisponibles[] = $t; } }
sort($tiposDisponibles);

include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-3">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php">Vigilancia</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($servicio['nombre_localidad'].' / '.$servicio['nombre_sede'].' / '.$servicio['proveedor']); ?></li>
        </ol>
      </nav>
      <h1 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo htmlspecialchars($servicio['proveedor']); ?> @ <?php echo htmlspecialchars($servicio['nombre_sede']); ?></h1>
      <div class="text-muted small">Localidad: <?php echo htmlspecialchars($servicio['nombre_localidad']); ?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo app_base_url(); ?>/pages/admin/sede_detalle.php?id_localidad=<?php echo (int)$servicio['id_localidad']; ?>&id_sede=<?php echo (int)$servicio['id_sede']; ?>" aria-label="Ver Sede"><i class="fas fa-building me-2" aria-hidden="true"></i>Ver Sede</a>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDisp" aria-label="Agregar Dispositivo"><i class="fas fa-plus me-2" aria-hidden="true"></i>Agregar Dispositivo</button>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Servicio</h5></div>
      <div class="card-body">
        <p class="mb-1"><strong>Proveedor:</strong> <?php echo htmlspecialchars($servicio['proveedor']); ?></p>
        <p class="mb-1"><strong>Estado:</strong> <?php $e=$servicio['estado_servicio']; $cls=$e==='Activo'?'estado-activa':($e==='Pendiente'?'estado-asignado':'estado-baja'); ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></p>
        <p class="mb-1"><strong>Observaciones:</strong> <?php echo htmlspecialchars($servicio['observaciones'] ?: '-'); ?></p>
        <hr>
        <div class="d-flex gap-4">
          <div><div class="small text-muted">Dispositivos</div><div class="h4 mb-0"><?php echo $totalDispositivos; ?></div></div>
          <div><div class="small text-muted">Cámaras activas</div><div class="h4 mb-0"><?php echo (int)$camarasActivas; ?></div></div>
        </div>
        <div class="mt-3">
          <button class="btn btn-sm btn-warning" onclick='editServ(<?php echo json_encode($servicio, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'><i class="fas fa-edit me-1"></i>Editar Servicio</button>
          <button class="btn btn-sm btn-danger" onclick="delServ(<?php echo (int)$servicio['id_vigilancia']; ?>)"><i class="fas fa-trash me-1"></i>Eliminar Servicio</button>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <h5 class="mb-0"><i class="fas fa-cctv me-2"></i>Dispositivos</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="btnExport" aria-label="Exportar CSV"><i class="fas fa-file-export me-1" aria-hidden="true"></i>Exportar CSV</button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDisp" aria-label="Agregar dispositivo"><i class="fas fa-plus me-1" aria-hidden="true"></i>Agregar</button>
          </div>
        </div>
        <div class="row g-2 mt-2">
          <div class="col-md-4">
            <label class="form-label small mb-1">Tipo</label>
            <select class="form-select form-select-sm" id="filterTipo">
              <option value="">Todos</option>
              <?php foreach($tiposDisponibles as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1">Estado</label>
            <select class="form-select form-select-sm" id="filterEstado">
              <option value="">Todos</option>
              <option value="Activo">Activo</option>
              <option value="De Baja">De Baja</option>
            </select>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tblDisps"><thead><tr><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Cant.</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach($dispositivos as $d): ?>
            <tr>
              <td><span class="badge bg-info"><?php echo htmlspecialchars($d['tipo_dispositivo']); ?></span></td>
              <td><?php echo htmlspecialchars($d['marca'] ?: '-'); ?></td>
              <td><?php echo htmlspecialchars($d['modelo'] ?: '-'); ?></td>
              <td><span class="badge bg-dark"><?php echo (int)$d['cantidad']; ?></span></td>
              <td><?php echo htmlspecialchars($d['ubicacion'] ?: '-'); ?></td>
              <td><?php $e=$d['estado']; $cls=$e==='Activo'?'estado-activa':'estado-baja'; ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-warning btn-edit-disp" 
                          data-bs-toggle="tooltip" 
                          title="Editar dispositivo"
                          aria-label="Editar dispositivo" 
                          data-row='<?php echo json_encode($d, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                    <i class="fas fa-edit" aria-hidden="true"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" 
                          data-bs-toggle="tooltip" 
                          title="Eliminar dispositivo"
                          aria-label="Eliminar dispositivo" 
                          onclick="delDisp(<?php echo (int)$d['id_vigilancia_dispositivo']; ?>)">
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

<!-- Modales: Servicio (reuso de formulario) y Dispositivo -->
<div class="modal fade" id="modalServ" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="modalServTitle">Editar Servicio</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formServ" class="needs-validation" novalidate action="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php">
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="accion" id="accionServ" value="editar_serv"><input type="hidden" name="id_vigilancia" id="id_vigilancia">
      <div class="mb-2"><label class="form-label">Proveedor *</label><input type="text" name="proveedor" id="proveedor" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Estado *</label><select class="form-select" name="estado_servicio" id="estado_servicio" required><option>Activo</option><option>Pendiente</option><option>De Baja</option></select></div>
      <div class="mb-2"><label class="form-label">Observaciones</label><textarea name="observaciones" id="observaciones_serv" class="form-control" rows="2"></textarea></div>
      <input type="hidden" name="id_sede" value="<?php echo (int)$servicio['id_sede']; ?>">
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="modalDisp" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="modalDispTitle">Agregar Dispositivo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formDisp" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="accion" id="accionDisp" value="agregar_disp"><input type="hidden" name="id_vigilancia_dispositivo" id="id_vigilancia_dispositivo">
      <div class="mb-2"><label class="form-label">Tipo *</label><select name="tipo_dispositivo" id="tipo_dispositivo" class="form-select" required><option value="">Seleccione</option><option>DVR</option><option>NVR</option><option>Cámara</option><option>Sensor</option><option>Monitor</option></select></div>
      <div class="row g-2"><div class="col"><label class="form-label">Marca</label><input type="text" name="marca" id="marca" class="form-control"></div><div class="col"><label class="form-label">Modelo</label><input type="text" name="modelo" id="modelo" class="form-control"></div></div>
      <div class="row g-2 mt-1"><div class="col"><label class="form-label">Cantidad *</label><input type="number" min="1" name="cantidad" id="cantidad" class="form-control" required value="1"></div><div class="col"><label class="form-label">Ubicación</label><input type="text" name="ubicacion" id="ubicacion" class="form-control"></div></div>
      <div class="mb-2 mt-1"><label class="form-label">Estado *</label><select name="estado" id="estado" class="form-select" required><option>Activo</option><option>De Baja</option></select></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
  </form>
</div></div></div>

<form id="formDelDisp" method="POST" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar_disp">
  <input type="hidden" name="id_vigilancia_dispositivo" id="del_disp">
  </form>
<form id="formDelServ" method="POST" action="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar_serv">
  <input type="hidden" name="id_vigilancia" value="<?php echo (int)$idVig; ?>">
  </form>

<script>
function editServ(v){ $('#modalServTitle').text('Editar Servicio'); $('#accionServ').val('editar_serv'); $('#id_vigilancia').val(v.id_vigilancia); $('#proveedor').val(v.proveedor); $('#estado_servicio').val(v.estado_servicio); $('#observaciones_serv').val(v.observaciones||''); new bootstrap.Modal(document.getElementById('modalServ')).show(); }
function delServ(){ if(confirm('¿Eliminar servicio y sus dispositivos?')){ document.getElementById('formDelServ').submit(); } }
function editDisp(d){ 
  console.log('editDisp llamado', d);
  $('#modalDispTitle').text('Editar Dispositivo'); $('#accionDisp').val('editar_disp'); $('#id_vigilancia_dispositivo').val(d.id_vigilancia_dispositivo); $('#tipo_dispositivo').val(d.tipo_dispositivo); $('#marca').val(d.marca||''); $('#modelo').val(d.modelo||''); $('#cantidad').val(d.cantidad||1); $('#ubicacion').val(d.ubicacion||''); $('#estado').val(d.estado); new bootstrap.Modal(document.getElementById('modalDisp')).show(); 
}
function delDisp(id){ if(confirm('¿Eliminar dispositivo?')){ $('#del_disp').val(id); $('#formDelDisp').submit(); } }
$('#modalDisp').on('hidden.bs.modal', function(){ $('#modalDispTitle').text('Agregar Dispositivo'); $('#accionDisp').val('agregar_disp'); $('#formDisp')[0].reset(); $('#formDisp').removeClass('was-validated'); });
$('#formServ, #formDisp').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); });

// Filtros con DataTables
window.addEventListener('load', function(){
  try {
    var dt = $('#tblDisps').DataTable();
    function applyFilters(){
      var tipo = $('#filterTipo').val() || '';
      var estado = $('#filterEstado').val() || '';
      if (tipo) { dt.column(0).search('^'+tipo+'$', true, false); } else { dt.column(0).search(''); }
      if (estado) { dt.column(5).search('^'+estado+'$', true, false); } else { dt.column(5).search(''); }
      dt.draw();
    }
    $('#filterTipo, #filterEstado').on('change', applyFilters);
  } catch(e) {}
  
  // Event delegation para botones de editar (evita problema con tooltips)
  $(document).on('click', '.btn-edit-disp', function(e) {
    e.preventDefault();
    e.stopPropagation();
    try {
      var data = $(this).data('row');
      console.log('Botón editar clickeado', data);
      editDisp(data);
    } catch(err) {
      console.error('Error al editar:', err);
    }
  });
});

// Exportar CSV simple desde la tabla
document.getElementById('btnExport').addEventListener('click', function(){
  try {
    var rows = [];
    document.querySelectorAll('#tblDisps tbody tr').forEach(function(tr){
      var cols = tr.querySelectorAll('td');
      rows.push([
        cols[0].innerText.trim(), cols[1].innerText.trim(), cols[2].innerText.trim(),
        cols[3].innerText.trim(), cols[4].innerText.trim(), cols[5].innerText.trim()
      ].join(','));
    });
    var csv = 'Tipo,Marca,Modelo,Cantidad,Ubicacion,Estado\n' + rows.join('\n');
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a'); a.href = url; a.download = 'dispositivos_vigilancia_<?php echo (int)$idVig; ?>.csv'; a.click(); URL.revokeObjectURL(url);
  } catch(e) {}
});
</script>

<?php include '../../includes/footer.php'; ?>

