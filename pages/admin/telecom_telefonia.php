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
      $db->prepare("INSERT INTO sedes_telefonia_lineas (id_sede, tipo_linea, operador, numero, dispositivo_modelo, interno_ext, estado, observaciones) VALUES (?,?,?,?,?,?,?,?)")
         ->execute([
           (int)$_POST['id_sede'], trim($_POST['tipo_linea']), trim($_POST['operador'] ?? ''), trim($_POST['numero'] ?? ''), trim($_POST['dispositivo_modelo'] ?? ''), trim($_POST['interno_ext'] ?? ''), trim($_POST['estado']), ($_POST['observaciones'] ?? null) ?: null
         ]);
      $_SESSION['mensaje'] = 'Línea creada'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'editar') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("UPDATE sedes_telefonia_lineas SET id_sede=?, tipo_linea=?, operador=?, numero=?, dispositivo_modelo=?, interno_ext=?, estado=?, observaciones=? WHERE id_linea=?")
         ->execute([
           (int)$_POST['id_sede'], trim($_POST['tipo_linea']), trim($_POST['operador'] ?? ''), trim($_POST['numero'] ?? ''), trim($_POST['dispositivo_modelo'] ?? ''), trim($_POST['interno_ext'] ?? ''), trim($_POST['estado']), ($_POST['observaciones'] ?? null) ?: null, (int)$_POST['id_linea']
         ]);
      $_SESSION['mensaje'] = 'Línea actualizada'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar') {
      verificarPermiso('telecom', 'eliminar');
      $db->prepare("DELETE FROM sedes_telefonia_lineas WHERE id_linea = ?")->execute([(int)$_POST['id_linea']]);
      $_SESSION['mensaje'] = 'Línea eliminada'; $_SESSION['tipo_mensaje'] = 'success';
    }
    header('Location: telecom_telefonia.php'); exit;
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: telecom_telefonia.php'); exit;
  }
}

// Filtros
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_sede = isset($_GET['sede']) ? $_GET['sede'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Listado con filtros
$sql = "SELECT t.*, s.nombre_sede, l.nombre_localidad, l.id_localidad 
        FROM sedes_telefonia_lineas t 
        JOIN sedes s ON s.id_sede=t.id_sede 
        JOIN localidades l ON l.id_localidad=s.id_localidad 
        WHERE 1=1";

$params = [];

// Filtro de localidad
if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

// Filtro de sede
if ($filtro_sede) {
    $sql .= " AND s.id_sede = ?";
    $params[] = $filtro_sede;
}

// Filtro de estado
if ($filtro_estado) {
    $sql .= " AND t.estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY l.nombre_localidad, s.nombre_sede, t.tipo_linea";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Datos para filtros
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.id_localidad FROM sedes s JOIN localidades l ON l.id_localidad = s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();
$estados_linea = ['Activa', 'Pendiente', 'Baja'];
include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-phone me-2"></i>Líneas Telefónicas</h1>
    <?php if (tienePermiso('telecom', 'editar')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTel"><i class="fas fa-plus me-2"></i>Agregar</button>
    <?php endif; ?>
  </div>
</div>

<!-- Filtros -->
<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>" 
                            <?php echo $filtro_localidad == $loc['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $loc['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="sede" class="form-label">Sede</label>
            <select name="sede" id="sede" class="form-select">
                <option value="">Seleccione Localidad</option>
                <?php foreach ($sedes as $s): ?>
                    <option value="<?php echo $s['id_sede']; ?>" data-localidad="<?php echo $s['id_localidad']; ?>"
                            <?php echo $filtro_sede == $s['id_sede'] ? 'selected' : ''; ?>>
                        <?php echo $s['nombre_sede']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach ($estados_linea as $est): ?>
                    <option value="<?php echo $est; ?>" 
                            <?php echo $filtro_estado == $est ? 'selected' : ''; ?>>
                        <?php echo $est; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="telecom_telefonia.php" id="btnLimpiarFiltros" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado (<?php echo count($rows); ?>)</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaTelefonia">
        <thead><tr><th>Localidad</th><th>Sede</th><th>Tipo</th><th>Operador</th><th>Número</th><th>Dispositivo</th><th>Estado</th><th>Acciones</th></tr></thead>
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
            <td><span class="badge bg-info"><?php echo htmlspecialchars($r['tipo_linea']); ?></span></td>
            <td><?php echo htmlspecialchars($r['operador'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($r['numero'] ?: ($r['interno_ext'] ?: '-')); ?></td>
            <td><?php echo htmlspecialchars($r['dispositivo_modelo'] ?: '-'); ?></td>
            <td><?php $e=$r['estado']; $cls=$e==='Activa'?'estado-activa':($e==='Pendiente'?'estado-asignado':'estado-baja'); ?><span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span></td>
            <td>
              <div class="btn-group" role="group">
                <?php if (tienePermiso('telecom', 'ver')): ?>
                <button class="btn btn-sm btn-info btn-view-tel" 
                        data-bs-toggle="tooltip" 
                        title="Ver detalles"
                        aria-label="Ver detalles" 
                        data-row='<?php echo json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                  <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
                
                <?php if (tienePermiso('telecom', 'editar')): ?>
                <button class="btn btn-sm btn-warning btn-edit-tel" 
                        data-bs-toggle="tooltip" 
                        title="Editar línea"
                        aria-label="Editar línea" 
                        data-row='<?php echo json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                  <i class="fas fa-edit" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
                <?php if (tienePermiso('telecom', 'eliminar')): ?>
                <button class="btn btn-sm btn-danger" 
                        data-bs-toggle="tooltip" 
                        title="Eliminar línea"
                        aria-label="Eliminar línea" 
                        onclick="delTel(<?php echo (int)$r['id_linea']; ?>)">
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
      <button type="button" class="btn btn-success" onclick="exportarExcel('tablaTelefonia', 'telefonia')">
        <i class="fas fa-file-excel me-2"></i>Exportar Excel
      </button>
      <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaTelefonia', 'telefonia')">
        <i class="fas fa-print me-2"></i>Imprimir
      </button>
    </div>
  </div>
</div>

<!-- Modal Ver Detalles -->
<div class="modal fade" id="modalVerTel" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header bg-primary text-white">
    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalles de la Línea</h5>
    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <!-- Ubicación -->
    <div class="mb-4">
      <h6 class="text-dark border-bottom pb-2 mb-3"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label text-muted small mb-1">Localidad:</label>
          <p class="mb-0 fw-bold" id="view_tel_localidad"></p>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted small mb-1">Sede:</label>
          <p class="mb-0 fw-bold" id="view_tel_sede"></p>
        </div>
      </div>
    </div>

    <!-- Información de la Línea -->
    <div class="mb-4">
      <h6 class="text-dark border-bottom pb-2 mb-3"><i class="fas fa-phone me-2"></i>Información de la Línea</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label text-muted small mb-1">Tipo:</label>
          <p class="mb-0"><span class="badge bg-info" id="view_tel_tipo"></span></p>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted small mb-1">Operador:</label>
          <p class="mb-0" id="view_tel_operador"></p>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted small mb-1">Número:</label>
          <p class="mb-0 fw-bold" id="view_tel_numero"></p>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted small mb-1">Dispositivo:</label>
          <p class="mb-0" id="view_tel_dispositivo"></p>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted small mb-1">Estado:</label>
          <p class="mb-0"><span id="view_tel_estado_badge"></span></p>
        </div>
      </div>
    </div>

    <!-- Observaciones -->
    <div id="view_tel_obs_container" style="display:none;">
      <h6 class="text-dark border-bottom pb-2 mb-3"><i class="fas fa-comment-dots me-2"></i>Observaciones</h6>
      <p class="mb-0" id="view_tel_observaciones"></p>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cerrar</button>
  </div>
</div></div></div>

<div class="modal fade" id="modalTel" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-phone me-2"></i><span id="modalTelTitle">Agregar Línea</span></h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formTel" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="accion" id="accion" value="agregar"><input type="hidden" name="id_linea" id="id_linea">
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
        <select name="tipo_linea" id="tipo_linea" class="form-select" required>
          <option value="">Seleccione</option>
          <option>Fija</option><option>Móvil</option>
        </select>
      </div>
      <div class="mb-2"><label class="form-label">Operador</label><input type="text" name="operador" id="operador" class="form-control"></div>
      <div class="row g-2">
        <div class="col"><label class="form-label">Número</label><input type="text" name="numero" id="numero" class="form-control"></div>
        <div class="col"><label class="form-label">Interno/Ext</label><input type="text" name="interno_ext" id="interno_ext" class="form-control"></div>
      </div>
      <div class="mb-2"><label class="form-label">Modelo dispositivo</label><input type="text" name="dispositivo_modelo" id="dispositivo_modelo" class="form-control"></div>
      <div class="mb-2"><label class="form-label">Estado *</label>
        <select name="estado" id="estado" class="form-select" required><option>Activa</option><option>Pendiente</option><option>De Baja</option></select>
      </div>
      <div class="mb-2"><label class="form-label">Observaciones</label><textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
  </form>
</div></div></div>

<form id="formDel" method="POST" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar">
  <input type="hidden" name="id_linea" id="del_id">
</form>

<script>
function viewTel(r){
  $('#view_tel_localidad').text(r.nombre_localidad || '-');
  $('#view_tel_sede').text(r.nombre_sede || '-');
  $('#view_tel_tipo').text(r.tipo_linea || '-');
  $('#view_tel_operador').text(r.operador || '-');
  $('#view_tel_numero').text(r.numero || r.interno_ext || '-');
  $('#view_tel_dispositivo').text(r.dispositivo_modelo || '-');
  
  // Estado con badge de color
  const estado = r.estado || '-';
  let estadoClass = 'estado-baja';
  if (estado === 'Activa') estadoClass = 'estado-activa';
  else if (estado === 'Pendiente') estadoClass = 'estado-asignado';
  $('#view_tel_estado_badge').html(`<span class="badge ${estadoClass}">${estado}</span>`);
  
  if (r.observaciones && r.observaciones.trim()) {
    $('#view_tel_observaciones').text(r.observaciones);
    $('#view_tel_obs_container').show();
  } else {
    $('#view_tel_obs_container').hide();
  }
  new bootstrap.Modal(document.getElementById('modalVerTel')).show();
}
function editTel(r){
  console.log('editTel llamado', r);
  $('#modalTelTitle').text('Editar Línea'); $('#accion').val('editar');
  $('#id_linea').val(r.id_linea);
  if (r.id_localidad) { $('#id_localidad').val(r.id_localidad); }
  cargarSedes(r.id_localidad);
  setTimeout(function(){ $('#id_sede').val(r.id_sede).trigger('change'); }, 200);
  $('#tipo_linea').val(r.tipo_linea); $('#operador').val(r.operador||'');
  $('#numero').val(r.numero||''); $('#interno_ext').val(r.interno_ext||'');
  $('#dispositivo_modelo').val(r.dispositivo_modelo||''); $('#estado').val(r.estado);
  $('#observaciones').val(r.observaciones||''); new bootstrap.Modal(document.getElementById('modalTel')).show();
}
function delTel(id){ 
  showConfirm({
      titulo: 'Eliminar Línea Telefónica',
      mensaje: '¿Está seguro de que desea eliminar esta línea telefónica?',
      icono: 'fa-trash-alt text-danger',
      claseBoton: 'btn-danger',
      textoAceptar: 'Eliminar',
      onConfirm: () => {
          $('#del_id').val(id); 
          $('#formDel').submit(); 
      }
  });
}
$('#modalTel').on('hidden.bs.modal', function(){ $('#modalTelTitle').text('Agregar Línea'); $('#accion').val('agregar'); $('#formTel')[0].reset(); $('#id_sede').val('').trigger('change'); $('#formTel').removeClass('was-validated'); });
$('#formTel').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); });
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades(){
  return $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{
    const $loc = $('#id_localidad');
    $loc.html('<option value="">Seleccione</option>');
    if(r.success){ r.data.forEach(l=> $loc.append(`<option value="${l.id}">${l.nombre}</option>`)); }
  });
}
function cargarSedes(localidad){
  const $s = $('#id_sede');
  $s.html('<option value="">Seleccione</option>');
  if(!localidad){ return $.Deferred().resolve().promise(); }
  return $.getJSON(`${BASE}/ajax/cargar_sedes.php`, { localidad_id: localidad }).done(r=>{
    const data = r && r.data ? r.data : r; const lista = data && data.sedes ? data.sedes : [];
    lista.forEach(x=> $s.append(`<option value="${parseInt(x.id,10)}">${x.nombre}</option>`));
  });
}
$(function(){
  const url = new URL(window.location.href);
  const qLoc = url.searchParams.get('id_localidad');
  const qSede = url.searchParams.get('id_sede');
  const qOpen = url.searchParams.get('open');
  cargarLocalidades().done(function(){
    if (qOpen === 'add') {
      if (qLoc) {
        $('#id_localidad').val(qLoc);
        cargarSedes(qLoc).done(function(){ if(qSede){ $('#id_sede').val(String(qSede)); } new bootstrap.Modal(document.getElementById('modalTel')).show(); });
      } else {
        new bootstrap.Modal(document.getElementById('modalTel')).show();
      }
    }
  });
  $('#id_localidad').on('change', function(){ cargarSedes($(this).val()); });
  
  // Event delegation para botones de ver
  $(document).on('click', '.btn-view-tel', function(e) {
    e.preventDefault();
    e.stopPropagation();
    try {
      var data = $(this).data('row');
      viewTel(data);
    } catch(err) {
      console.error('Error al ver:', err);
    }
  });
  
  // Event delegation para botones de editar (evita problema con tooltips)
  $(document).on('click', '.btn-edit-tel', function(e) {
    e.preventDefault();
    e.stopPropagation();
    try {
      var data = $(this).data('row');
      console.log('Botón editar clickeado', data);
      editTel(data);
    } catch(err) {
      console.error('Error al editar:', err);
    }
  });
  
  // ========================================
  // FILTRADO DE SEDES POR LOCALIDAD
  // ========================================
  $('#localidad').on('change', function() {
    const idLocalidad = $(this).val();
    const sedeSelect = $('#sede');
    
    // Limpiar y deshabilitar si no hay localidad
    if (!idLocalidad) {
      sedeSelect.html('<option value="">Seleccione Localidad</option>').prop('disabled', true);
      return;
    }
    
    // Filtrar sedes
    const opciones = [`<option value="">Todas las sedes</option>`];
    $('[data-localidad]').each(function() {
      if ($(this).data('localidad') == idLocalidad) {
        opciones.push($(this)[0].outerHTML);
      }
    });
    
    sedeSelect.html(opciones.join('')).prop('disabled', false);
    
    // Mantener selección si existe
    const sedeActual = '<?php echo $filtro_sede; ?>';
    if (sedeActual) {
      sedeSelect.val(sedeActual);
    }
  });
  
  // Inicializar estado de sede al cargar
  if ($('#localidad').val()) {
    $('#localidad').trigger('change');
  } else {
    $('#sede').prop('disabled', true);
  }
  
  // sin select2 en este modal para igualar estilo
});
</script>

<?php include '../../includes/footer.php'; ?>

