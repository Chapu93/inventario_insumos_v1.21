<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Procesar POST (agregar/editar/eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'agregar') {
            $stmt = $db->prepare("INSERT INTO sedes_internet (id_sede, proveedor, tipo_conexion, velocidad_bajada_mbps, velocidad_subida_mbps, estado_servicio, observaciones) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                (int)$_POST['id_sede'],
                trim($_POST['proveedor']),
                trim($_POST['tipo_conexion']),
                ($_POST['velocidad_bajada_mbps'] !== '' ? (int)$_POST['velocidad_bajada_mbps'] : null),
                ($_POST['velocidad_subida_mbps'] !== '' ? (int)$_POST['velocidad_subida_mbps'] : null),
                trim($_POST['estado_servicio']),
                ($_POST['observaciones'] ?? null) ?: null,
            ]);
            $_SESSION['mensaje'] = 'Servicio de Internet agregado.';
            $_SESSION['tipo_mensaje'] = 'success';
        } elseif ($accion === 'editar') {
            $stmt = $db->prepare("UPDATE sedes_internet SET id_sede=?, proveedor=?, tipo_conexion=?, velocidad_bajada_mbps=?, velocidad_subida_mbps=?, estado_servicio=?, observaciones=? WHERE id_internet=?");
            $stmt->execute([
                (int)$_POST['id_sede'],
                trim($_POST['proveedor']),
                trim($_POST['tipo_conexion']),
                ($_POST['velocidad_bajada_mbps'] !== '' ? (int)$_POST['velocidad_bajada_mbps'] : null),
                ($_POST['velocidad_subida_mbps'] !== '' ? (int)$_POST['velocidad_subida_mbps'] : null),
                trim($_POST['estado_servicio']),
                ($_POST['observaciones'] ?? null) ?: null,
                (int)$_POST['id_internet']
            ]);
            $_SESSION['mensaje'] = 'Servicio de Internet actualizado.';
            $_SESSION['tipo_mensaje'] = 'success';
        } elseif ($accion === 'eliminar') {
            $stmt = $db->prepare("DELETE FROM sedes_internet WHERE id_internet = ?");
            $stmt->execute([(int)$_POST['id_internet']]);
            $_SESSION['mensaje'] = 'Servicio de Internet eliminado.';
            $_SESSION['tipo_mensaje'] = 'success';
        }
        header('Location: telecom_internet.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: telecom_internet.php');
        exit;
    }
}

// Listado
$sql = "SELECT si.*, s.nombre_sede, l.nombre_localidad
        FROM sedes_internet si
        JOIN sedes s ON s.id_sede = si.id_sede
        JOIN localidades l ON l.id_localidad = s.id_localidad
        ORDER BY l.nombre_localidad, s.nombre_sede";
$internet = $db->query($sql)->fetchAll();

// Sedes para select
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.nombre_localidad FROM sedes s JOIN localidades l ON l.id_localidad = s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-wifi me-2"></i>Internet por Sede</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInternet"><i class="fas fa-plus me-2"></i>Agregar</button>
        </div>
    </div>
    </div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado (<?php echo count($internet); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($internet)): ?>
            <div class="text-center py-4">
                <i class="fas fa-wifi fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Sin registros</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaInternet" data-default-order-col="0" data-default-order-dir="asc">
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th>Localidad</th>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Vel. (↓/↑ Mbps)</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internet as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nombre_sede']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nombre_localidad']); ?></td>
                                <td><?php echo htmlspecialchars($row['proveedor']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['tipo_conexion']); ?></span></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo (int)($row['velocidad_bajada_mbps'] ?? 0); ?></span>
                                    /
                                    <span class="badge bg-success"><?php echo (int)($row['velocidad_subida_mbps'] ?? 0); ?></span>
                                </td>
                                <td>
                                    <?php $est = $row['estado_servicio']; $cls = ($est==='Activo'?'estado-activa':($est==='Pendiente'?'estado-asignado':'estado-baja')); ?>
                                    <span class="badge <?php echo $cls; ?>"><?php echo $est; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar" onclick='editarInternet(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar" onclick="eliminarInternet(<?php echo (int)$row['id_internet']; ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agregar/Editar Internet -->
<div class="modal fade" id="modalInternet" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalInternetTitle">Agregar Servicio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formInternet" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="accion" id="accion" value="agregar">
          <input type="hidden" name="id_internet" id="id_internet">

          <div class="mb-3">
            <label class="form-label">Sede *</label>
            <select name="id_sede" id="id_sede" class="form-select select2" required>
              <option value="">Seleccione una sede</option>
              <?php foreach ($sedes as $s): ?>
                <option value="<?php echo $s['id_sede']; ?>"><?php echo htmlspecialchars($s['nombre_localidad'] . ' - ' . $s['nombre_sede']); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Seleccione una sede</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Proveedor *</label>
            <input type="text" name="proveedor" id="proveedor" class="form-control" required>
            <div class="invalid-feedback">Proveedor requerido</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo de conexión *</label>
            <select name="tipo_conexion" id="tipo_conexion" class="form-select" required>
              <option value="">Seleccione</option>
              <option>ADSL</option>
              <option>Fibra óptica</option>
              <option>4G</option>
              <option>5G</option>
              <option>Satelital</option>
              <option>Radioenlace</option>
            </select>
            <div class="invalid-feedback">Tipo requerido</div>
          </div>

          <div class="row g-2">
            <div class="col">
              <label class="form-label">Bajada (Mbps)</label>
              <input type="number" class="form-control" name="velocidad_bajada_mbps" id="velocidad_bajada_mbps" min="0">
            </div>
            <div class="col">
              <label class="form-label">Subida (Mbps)</label>
              <input type="number" class="form-control" name="velocidad_subida_mbps" id="velocidad_subida_mbps" min="0">
            </div>
          </div>

          <div class="mb-3 mt-2">
            <label class="form-label">Estado *</label>
            <select name="estado_servicio" id="estado_servicio" class="form-select" required>
              <option>Activo</option>
              <option>Pendiente</option>
              <option>De Baja</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formEliminar" method="POST" style="display:none">
  <input type="hidden" name="accion" value="eliminar">
  <input type="hidden" name="id_internet" id="del_id">
</form>

<script>
function editarInternet(row){
  $('#modalInternetTitle').text('Editar Servicio');
  $('#accion').val('editar');
  $('#id_internet').val(row.id_internet);
  $('#id_sede').val(row.id_sede).trigger('change');
  $('#proveedor').val(row.proveedor);
  $('#tipo_conexion').val(row.tipo_conexion);
  $('#velocidad_bajada_mbps').val(row.velocidad_bajada_mbps || '');
  $('#velocidad_subida_mbps').val(row.velocidad_subida_mbps || '');
  $('#estado_servicio').val(row.estado_servicio);
  $('#observaciones').val(row.observaciones || '');
  var m = new bootstrap.Modal(document.getElementById('modalInternet'));
  m.show();
}
function eliminarInternet(id){
  if(confirm('¿Eliminar servicio de Internet?')){
    $('#del_id').val(id);
    $('#formEliminar').submit();
  }
}
$('#modalInternet').on('hidden.bs.modal', function(){
  $('#modalInternetTitle').text('Agregar Servicio');
  $('#accion').val('agregar');
  $('#formInternet')[0].reset();
  $('#id_sede').val('').trigger('change');
  $('#formInternet').removeClass('was-validated');
});
$('#formInternet').on('submit', function(e){
  if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); }
  $(this).addClass('was-validated');
});
$(function(){ $('.select2').select2({ theme:'bootstrap-5', width: '100%' }); });
</script>

<?php include '../../includes/footer.php'; ?>

