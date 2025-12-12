<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('telecom', 'ver');

include '../../includes/header.php';
?>

<div class="row">
  <div class="col-12">
    <h1><i class="fas fa-chart-pie me-2"></i>Resumen Telecomunicaciones</h1>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="dashboard-card dashboard-card--primary" id="kpi-sedes-internet-activo"><h3>0</h3><p>Sedes con Internet Activo</p></div></div>
  <div class="col-md-3"><div class="dashboard-card dashboard-card--danger" id="kpi-sedes-sin-internet"><h3>0</h3><p>Sedes sin Internet</p></div></div>
  <div class="col-md-3"><div class="dashboard-card dashboard-card--info" id="kpi-lineas-fijas"><h3>0</h3><p>Líneas Fijas Activas</p></div></div>
  <div class="col-md-3"><div class="dashboard-card dashboard-card--success" id="kpi-lineas-moviles"><h3>0</h3><p>Líneas Móviles Activas</p></div></div>
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
      console.log('Datos recibidos:', d);
      if(!d.success) {
        console.error('Error en respuesta:', d.error);
        throw new Error(d.error || 'Error');
      }
      
      // Los datos vienen envueltos en d.data por json_success()
      const data = d.data || {};
      const kpis = data.kpis || {};
      const tiposConexion = data.tipos_conexion || { labels: [], data: [] };
      const operadores = data.operadores || { labels: [], data: [] };
      const red = data.red || { labels: [], data: [] };
      
      // KPIs
      document.querySelector('#kpi-sedes-internet-activo h3').textContent = kpis.sedes_con_internet_activo || 0;
      document.querySelector('#kpi-sedes-sin-internet h3').textContent = kpis.sedes_sin_internet || 0;
      document.querySelector('#kpi-lineas-fijas h3').textContent = kpis.lineas_fijas_activas || 0;
      document.querySelector('#kpi-lineas-moviles h3').textContent = kpis.lineas_moviles_activas || 0;
      document.querySelector('#kpi-sedes-vigilancia').textContent = kpis.vigilancia_activa || 0;
      document.querySelector('#kpi-camaras').textContent = kpis.total_camaras || 0;
      
      // Colores para gráficos
      const colores = ['#0066cc', '#ff6b6b', '#51cf66', '#ffd93d', '#6c5ce7', '#00b894', '#fdcb6e', '#e17055'];
      
      // Charts - agregar colores y opciones por defecto
      if (tiposConexion.labels && tiposConexion.labels.length > 0) {
        new Chart(document.getElementById('chartTiposConexion'), { 
          type:'doughnut', 
          data:{ 
            labels:tiposConexion.labels, 
            datasets:[{ 
              data:tiposConexion.data,
              backgroundColor: colores.slice(0, tiposConexion.data.length)
            }] 
          }, 
          options:{responsive:true, maintainAspectRatio: true}
        });
      } else {
        document.getElementById('chartTiposConexion').parentElement.innerHTML = '<p class="text-muted text-center">Sin datos</p>';
      }
      
      if (operadores.labels && operadores.labels.length > 0) {
        new Chart(document.getElementById('chartOperadores'), { 
          type:'bar', 
          data:{ 
            labels:operadores.labels, 
            datasets:[{ 
              label:'Líneas', 
              data:operadores.data,
              backgroundColor: colores[0],
              borderColor: colores[0],
              borderWidth: 1
            }] 
          }, 
          options:{responsive:true, maintainAspectRatio: true, indexAxis: 'y'}
        });
      } else {
        document.getElementById('chartOperadores').parentElement.innerHTML = '<p class="text-muted text-center">Sin datos</p>';
      }
      
      if (red.labels && red.labels.length > 0) {
        new Chart(document.getElementById('chartRed'), { 
          type:'bar', 
          data:{ 
            labels:red.labels, 
            datasets:[{ 
              label:'Dispositivos', 
              data:red.data,
              backgroundColor: colores[2],
              borderColor: colores[2],
              borderWidth: 1
            }] 
          }, 
          options:{responsive:true, maintainAspectRatio: true, indexAxis: 'y'}
        });
      } else {
        document.getElementById('chartRed').parentElement.innerHTML = '<p class="text-muted text-center">Sin datos</p>';
      }
    })
    .catch(err => {
      console.error('Error al cargar contadores_telecom:', err);
      alert('Error al cargar los datos: ' + err.message);
    });
}
document.addEventListener('DOMContentLoaded', cargarResumen);
</script>

<?php include '../../includes/footer.php'; ?>

