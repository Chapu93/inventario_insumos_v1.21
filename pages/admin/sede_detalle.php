<?php
require_once '../../includes/config.php';
$db = conectarDB();

// Cargar listas base
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

// Datos de sede seleccionada
$idLocalidad = isset($_GET['id_localidad']) ? (int)$_GET['id_localidad'] : 0;
$idSede = isset($_GET['id_sede']) ? (int)$_GET['id_sede'] : 0;
$sede = null;
$internet = $telefonia = $vigilancia = $vigilancia_dispositivos = $planos = [];
if ($idSede > 0) {
    // Sede + Localidad + Zona
    $stmt = $db->prepare("SELECT s.*, l.nombre_localidad, z.nombre_zona FROM sedes s JOIN localidades l ON l.id_localidad=s.id_localidad JOIN zonas z ON z.id_zona=l.id_zona WHERE s.id_sede=?");
    $stmt->execute([$idSede]);
    $sede = $stmt->fetch();

    // Internet por sede
    $stmt = $db->prepare("SELECT proveedor, tipo_conexion, velocidad_bajada_mbps, velocidad_subida_mbps, estado_servicio, simetrico FROM sedes_internet WHERE id_sede=? ORDER BY proveedor");
    $stmt->execute([$idSede]);
    $internet = $stmt->fetchAll();

    // Telefonía por sede
    $stmt = $db->prepare("SELECT tipo_linea, operador, numero, interno_ext, dispositivo_modelo, estado FROM sedes_telefonia_lineas WHERE id_sede=? ORDER BY tipo_linea, operador");
    $stmt->execute([$idSede]);
    $telefonia = $stmt->fetchAll();

    // Vigilancia (servicios)
    $stmt = $db->prepare("SELECT id_vigilancia, proveedor, estado_servicio, observaciones FROM sedes_vigilancia WHERE id_sede=? ORDER BY proveedor");
    $stmt->execute([$idSede]);
    $vigilancia = $stmt->fetchAll();

    // Dispositivos de vigilancia (por servicio)
    $stmt = $db->prepare("SELECT d.*, v.proveedor FROM sedes_vigilancia_dispositivos d JOIN sedes_vigilancia v ON v.id_vigilancia=d.id_vigilancia WHERE v.id_sede=? ORDER BY v.proveedor, d.tipo_dispositivo");
    $stmt->execute([$idSede]);
    $vigilancia_dispositivos = $stmt->fetchAll();

    // Planos
    $stmt = $db->prepare("SELECT tipo_plano, archivo, descripcion, fecha_subida FROM sedes_planos WHERE id_sede=? ORDER BY tipo_plano, fecha_subida DESC");
    $stmt->execute([$idSede]);
    $planos = $stmt->fetchAll();
}

include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12">
    <h1><i class="fas fa-building me-2"></i>Detalle de Sede</h1>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Localidad</label>
        <select name="id_localidad" id="sel_localidad" class="form-select">
          <option value="">Seleccione</option>
          <?php foreach($localidades as $l): ?>
            <option value="<?php echo $l['id_localidad']; ?>" <?php echo $idLocalidad===$l['id_localidad']?'selected':''; ?>><?php echo htmlspecialchars($l['nombre_localidad']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Sede</label>
        <select name="id_sede" id="sel_sede" class="form-select" <?php echo $idLocalidad? '' : 'disabled'; ?>>
          <option value="">Seleccione</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Ver</button>
      </div>
    </form>
  </div>
</div>

<?php if ($sede): ?>
<div class="row mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5></div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/admin/telecom_internet.php?id_localidad=<?php echo (int)$idLocalidad; ?>&id_sede=<?php echo (int)$idSede; ?>&open=add" class="btn btn-sm btn-primary w-100"><i class="fas fa-wifi me-2"></i>Agregar Internet</a></div>
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/admin/telecom_telefonia.php?id_localidad=<?php echo (int)$idLocalidad; ?>&id_sede=<?php echo (int)$idSede; ?>&open=add" class="btn btn-sm btn-primary w-100"><i class="fas fa-phone me-2"></i>Agregar Teléfono</a></div>
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php?id_localidad=<?php echo (int)$idLocalidad; ?>&id_sede=<?php echo (int)$idSede; ?>&open=add" class="btn btn-sm btn-primary w-100"><i class="fas fa-video me-2"></i>Agregar Vigilancia</a></div>
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/admin/telecom_planos.php?id_localidad=<?php echo (int)$idLocalidad; ?>&id_sede=<?php echo (int)$idSede; ?>&open=add" class="btn btn-sm btn-primary w-100"><i class="fas fa-file-upload me-2"></i>Subir Plano</a></div>
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/admin/sedes.php?id_sede=<?php echo (int)$idSede; ?>" class="btn btn-sm btn-warning w-100"><i class="fas fa-edit me-2"></i>Editar Sede</a></div>
          <div class="col-md-2"><a href="<?php echo app_base_url(); ?>/pages/reportes/remito.php" class="btn btn-sm btn-info w-100"><i class="fas fa-file-alt me-2"></i>Ver Remitos</a></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Delegado de la Sede</h5></div>
      <div class="card-body">
        <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($sede['delegado_nombre'] ?: '-'); ?></p>
        <p class="mb-1"><strong>Apellido:</strong> <?php echo htmlspecialchars($sede['delegado_apellido'] ?: '-'); ?></p>
        <p class="mb-0"><strong>Teléfono:</strong> <?php echo htmlspecialchars($sede['delegado_telefono'] ?: '-'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Responsable (Segundo Delegado)</h5></div>
      <div class="card-body">
        <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($sede['responsable_nombre'] ?? '-'); ?></p>
        <p class="mb-1"><strong>Apellido:</strong> <?php echo htmlspecialchars($sede['responsable_apellido'] ?? '-'); ?></p>
        <p class="mb-0"><strong>Teléfono:</strong> <?php echo htmlspecialchars($sede['responsable_telefono'] ?? '-'); ?></p>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Información General</h5></div>
      <div class="card-body">
        <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($sede['nombre_sede']); ?></p>
        <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($sede['direccion'] ?: '-'); ?></p>
        <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($sede['nombre_localidad']); ?></p>
        <p class="mb-1"><strong>Zona:</strong> <?php echo htmlspecialchars($sede['nombre_zona']); ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-wifi me-2"></i>Internet</h5></div>
      <div class="card-body">
        <?php if (empty($internet)): ?>
          <p class="text-muted mb-0">Sin servicios cargados</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Proveedor</th><th>Tipo</th><th>Vel. (↓/↑)</th><th>Simétrico</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($internet as $i): ?>
              <tr>
                <td><?php echo htmlspecialchars($i['proveedor']); ?></td>
                <td><?php echo htmlspecialchars($i['tipo_conexion']); ?></td>
                <td><span class="badge bg-primary"><?php echo (int)($i['velocidad_bajada_mbps'] ?? 0); ?></span> / <span class="badge bg-success"><?php echo (int)($i['velocidad_subida_mbps'] ?? 0); ?></span></td>
                <td><?php $sim = (int)($i['simetrico'] ?? 0); ?><span class="badge <?php echo $sim ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $sim ? 'Sí' : 'No'; ?></span></td>
                <td><?php echo htmlspecialchars($i['estado_servicio']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-phone me-2"></i>Telefonía</h5></div>
      <div class="card-body">
        <?php if (empty($telefonia)): ?>
          <p class="text-muted mb-0">Sin líneas cargadas</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Tipo</th><th>Operador</th><th>Número</th><th>Interno</th><th>Modelo</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($telefonia as $t): ?>
              <tr>
                <td><?php echo htmlspecialchars($t['tipo_linea']); ?></td>
                <td><?php echo htmlspecialchars($t['operador'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($t['numero'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($t['interno_ext'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($t['dispositivo_modelo'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($t['estado']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6"></div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-video me-2"></i>Vigilancia (Servicios)</h5></div>
      <div class="card-body">
        <?php if (empty($vigilancia)): ?>
          <p class="text-muted mb-0">Sin servicios de vigilancia</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Proveedor</th><th>Estado</th><th>Obs.</th></tr></thead>
            <tbody>
              <?php foreach($vigilancia as $v): ?>
              <tr>
                <td><?php echo htmlspecialchars($v['proveedor']); ?></td>
                <td><?php echo htmlspecialchars($v['estado_servicio']); ?></td>
                <td><?php echo htmlspecialchars($v['observaciones'] ?: '-'); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-cctv me-2"></i>Vigilancia (Dispositivos)</h5></div>
      <div class="card-body">
        <?php if (empty($vigilancia_dispositivos)): ?>
          <p class="text-muted mb-0">Sin dispositivos</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Proveedor</th><th>Tipo</th><th>Marca/Modelo</th><th>Cant.</th><th>Ubicación</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($vigilancia_dispositivos as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d['proveedor']); ?></td>
                <td><?php echo htmlspecialchars($d['tipo_dispositivo']); ?></td>
                <td><?php echo htmlspecialchars(($d['marca'] ?: '-') . ' ' . ($d['modelo'] ?: '')); ?></td>
                <td><span class="badge bg-dark"><?php echo (int)$d['cantidad']; ?></span></td>
                <td><?php echo htmlspecialchars($d['ubicacion'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($d['estado']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-file me-2"></i>Planos</h5></div>
      <div class="card-body">
        <?php if (empty($planos)): ?>
          <p class="text-muted mb-0">Sin planos cargados</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Archivo</th></tr></thead>
            <tbody>
              <?php foreach($planos as $p): ?>
              <tr>
                <td><?php echo htmlspecialchars($p['tipo_plano']); ?></td>
                <td><?php echo htmlspecialchars($p['descripcion'] ?: '-'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_subida'])); ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?php echo app_base_url() . '/' . $p['archivo']; ?>" target="_blank"><i class="fas fa-download"></i> Descargar</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6"></div>
</div>
<?php endif; ?>

<script>
const BASE = '<?php echo app_base_url(); ?>';
function cargarSedesLocalidad(locId){
  const $s = document.getElementById('sel_sede');
  if (!$s) return;
  $s.innerHTML = '<option value="">Seleccione</option>';
  if (!locId) { $s.setAttribute('disabled', 'disabled'); return; }
  fetch(`${BASE}/ajax/sedes_por_localidad.php?localidad_id=${encodeURIComponent(locId)}`)
    .then(r => r.json())
    .then(d => { if (!d.success) return; d.data.forEach(x => { const opt = document.createElement('option'); opt.value = x.id; opt.textContent = x.nombre; $s.appendChild(opt); }); $s.removeAttribute('disabled'); <?php if ($idSede>0): ?> $s.value = '<?php echo $idSede; ?>'; <?php endif; ?> })
    .catch(() => {});
}
document.addEventListener('DOMContentLoaded', function(){
  const selLoc = document.getElementById('sel_localidad');
  if (selLoc) { cargarSedesLocalidad(selLoc.value || ''); selLoc.addEventListener('change', function(){ cargarSedesLocalidad(this.value); }); }
});
</script>

<?php include '../../includes/footer.php'; ?>

