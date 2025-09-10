<?php
require_once '../../includes/config.php';
include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12">
    <h1><i class="fas fa-chart-pie me-2"></i>Resumen Telecomunicaciones</h1>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="dashboard-card" id="kpi-sedes-internet-activo"><h3>0</h3><p>Sedes con Internet Activo</p></div></div>
  <div class="col-md-3"><div class="dashboard-card" id="kpi-sedes-sin-internet" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);"><h3>0</h3><p>Sedes sin Internet</p></div></div>
  <div class="col-md-3"><div class="dashboard-card" id="kpi-lineas-fijas" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);"><h3>0</h3><p>Líneas Fijas Activas</p></div></div>
  <div class="col-md-3"><div class="dashboard-card" id="kpi-lineas-moviles" style="background: linear-gradient(135deg, #20c997 0%, #198754 100%);"><h3>0</h3><p>Líneas Móviles Activas</p></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-network-wired me-2"></i>Conexiones por Tipo</h5></div>
      <div class="card-body"><canvas id="chartTiposConexion" height="180"></canvas></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-signal me-2"></i>Líneas por Operador</h5></div>
      <div class="card-body"><canvas id="chartOperadores" height="180"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-server me-2"></i>Dispositivos de Red</h5></div>
      <div class="card-body"><canvas id="chartRed" height="180"></canvas></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-video me-2"></i>Vigilancia</h5></div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-6"><h3 id="kpi-sedes-vigilancia">0</h3><p>Servicios Activos</p></div>
          <div class="col-6"><h3 id="kpi-camaras">0</h3><p>Total Cámaras</p></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function cargarResumen(){
  fetch('<?php echo app_base_url(); ?>/ajax/contadores_telecom.php')
    .then(r => r.json())
    .then(d => {
      if(!d.success) throw new Error(d.error || 'Error');
      // KPIs
      document.querySelector('#kpi-sedes-internet-activo h3').textContent = d.kpis.sedes_con_internet_activo;
      document.querySelector('#kpi-sedes-sin-internet h3').textContent = d.kpis.sedes_sin_internet;
      document.querySelector('#kpi-lineas-fijas h3').textContent = d.kpis.lineas_fijas_activas;
      document.querySelector('#kpi-lineas-moviles h3').textContent = d.kpis.lineas_moviles_activas;
      document.querySelector('#kpi-sedes-vigilancia').textContent = d.kpis.vigilancia_activa;
      document.querySelector('#kpi-camaras').textContent = d.kpis.total_camaras;
      // Charts
      new Chart(document.getElementById('chartTiposConexion'), { type:'doughnut', data:{ labels:d.tipos_conexion.labels, datasets:[{ data:d.tipos_conexion.data }] }, options:{responsive:true} });
      new Chart(document.getElementById('chartOperadores'), { type:'bar', data:{ labels:d.operadores.labels, datasets:[{ label:'Líneas', data:d.operadores.data }] }, options:{responsive:true} });
      new Chart(document.getElementById('chartRed'), { type:'bar', data:{ labels:d.red.labels, datasets:[{ label:'Dispositivos', data:d.red.data }] }, options:{responsive:true} });
    })
    .catch(err => console.error('contadores_telecom', err));
}
document.addEventListener('DOMContentLoaded', cargarResumen);
</script>

<?php include '../../includes/footer.php'; ?>

