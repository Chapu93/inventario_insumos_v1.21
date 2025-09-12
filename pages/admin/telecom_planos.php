<?php
require_once '../../includes/config.php';
$db = conectarDB();

// Configuración de uploads
$uploadDir = __DIR__ . '/../../public/uploads/planos';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

// Procesamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'subir') {
      if (!isset($_POST['id_sede']) || !$_POST['id_sede']) { throw new Exception('Sede requerida'); }
      if (!isset($_POST['tipo_plano']) || !$_POST['tipo_plano']) { throw new Exception('Tipo de plano requerido'); }
      if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) { throw new Exception('Archivo requerido'); }
      $idSede = (int)$_POST['id_sede'];
      $tipo = $_POST['tipo_plano'];
      $desc = trim($_POST['descripcion'] ?? '');
      $file = $_FILES['archivo'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf','png','jpg','jpeg','svg'];
      if (!in_array($ext, $allowed, true)) { throw new Exception('Formato no permitido'); }
      $safeName = 'plano_' . $idSede . '_' . $tipo . '_' . time() . '.' . $ext;
      $dest = $uploadDir . '/' . $safeName;
      if (!move_uploaded_file($file['tmp_name'], $dest)) { throw new Exception('No se pudo guardar el archivo'); }
      $db->prepare("INSERT INTO sedes_planos (id_sede, tipo_plano, archivo, descripcion) VALUES (?,?,?,?)")
         ->execute([$idSede, $tipo, 'public/uploads/planos/' . $safeName, $desc ?: null]);
      $_SESSION['mensaje'] = 'Plano subido correctamente'; $_SESSION['tipo_mensaje'] = 'success';
    } elseif ($accion === 'eliminar') {
      $id = (int)$_POST['id_plano'];
      $stmt = $db->prepare("SELECT archivo FROM sedes_planos WHERE id_plano=?"); $stmt->execute([$id]); $row = $stmt->fetch();
      if ($row) {
        $path = __DIR__ . '/../../' . $row['archivo'];
        if (is_file($path)) { @unlink($path); }
        $db->prepare("DELETE FROM sedes_planos WHERE id_plano=?")->execute([$id]);
      }
      $_SESSION['mensaje'] = 'Plano eliminado'; $_SESSION['tipo_mensaje'] = 'success';
    }
    header('Location: telecom_planos.php'); exit;
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); $_SESSION['tipo_mensaje'] = 'danger'; header('Location: telecom_planos.php'); exit;
  }
}

// Datos
$planos = $db->query("SELECT p.*, s.nombre_sede, l.nombre_localidad FROM sedes_planos p JOIN sedes s ON s.id_sede=p.id_sede JOIN localidades l ON l.id_localidad=s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede, p.tipo_plano, p.fecha_subida DESC")->fetchAll();

include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-draw-polygon me-2"></i>Planos de Sede</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPlano"><i class="fas fa-upload me-2"></i>Subir Plano</button>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado (<?php echo count($planos); ?>)</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable">
        <thead><tr><th>Sede</th><th>Localidad</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Archivo</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach($planos as $p): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($p['nombre_sede']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['nombre_localidad']); ?></td>
            <td><span class="badge bg-info"><?php echo htmlspecialchars($p['tipo_plano']); ?></span></td>
            <td><?php echo htmlspecialchars($p['descripcion'] ?: '-'); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_subida'])); ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?php echo app_base_url() . '/' . $p['archivo']; ?>" target="_blank"><i class="fas fa-file"></i> Abrir</a></td>
            <td>
              <form method="POST" onsubmit="return confirm('¿Eliminar plano?');" style="display:inline">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_plano" value="<?php echo (int)$p['id_plano']; ?>">
                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPlano" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Subir Plano</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    <div class="modal-body">
      <input type="hidden" name="accion" value="subir">
      <div class="mb-2"><label class="form-label">Localidad *</label>
        <select id="id_localidad" class="form-select select2" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione localidad</div>
      </div>
      <div class="mb-2"><label class="form-label">Sede *</label>
        <select name="id_sede" id="id_sede" class="form-select select2" required>
          <option value="">Seleccione</option>
        </select><div class="invalid-feedback">Seleccione sede</div>
      </div>
      <div class="mb-2"><label class="form-label">Tipo *</label>
        <select name="tipo_plano" class="form-select" required><option value="">Seleccione</option><option>Red</option><option>Vigilancia</option></select>
      </div>
      <div class="mb-2"><label class="form-label">Descripción</label><input type="text" name="descripcion" class="form-control"></div>
      <div class="mb-2"><label class="form-label">Archivo (pdf/png/jpg/jpeg/svg) *</label><input type="file" name="archivo" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.svg" required></div>
      <div class="form-text">Tamaño sugerido &lt; 10 MB.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Subir</button></div>
  </form>
</div></div></div>

<script>
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades(){ $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{ const $l=$('#id_localidad'); $l.html('<option value="">Seleccione</option>'); if(r.success){ r.data.forEach(x=> $l.append(`<option value="${x.id}">${x.nombre}</option>`)); } $l.trigger('change.select2'); }); }
function cargarSedes(loc){ const $s=$('#id_sede'); $s.html('<option value="">Seleccione</option>'); if(!loc){ $s.trigger('change.select2'); return; } $.getJSON(`${BASE}/ajax/sedes_por_localidad.php`, { localidad_id: loc }).done(r=>{ if(r.success){ r.data.forEach(x=> $s.append(`<option value="${x.id}">${x.nombre}</option>`)); } $s.trigger('change.select2'); }); }
$(function(){ cargarLocalidades(); $('#id_localidad').on('change', function(){ cargarSedes($(this).val()); }); $('form.needs-validation').on('submit', function(e){ if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); } $(this).addClass('was-validated'); }); });
$('#modalPlano').on('shown.bs.modal', function(){
  $('#modalPlano .select2').each(function(){
    var $el = $(this);
    try { if ($el.hasClass('select2-hidden-accessible')) { $el.select2('destroy'); } } catch(e) {}
    $el.select2({ theme:'bootstrap-5', language:'es', width:'100%', dropdownParent: $('#modalPlano') });
  });
});
</script>

<?php include '../../includes/footer.php'; ?>

