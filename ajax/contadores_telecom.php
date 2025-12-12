<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('telecom', 'ver')) {
    json_error('No tienes permisos para ver telecomunicaciones', 403);
}

try {
  $db = conectarDB();

  // KPI: sedes con internet activo vs sin internet (considerando sedes existentes)
  $totalSedes = (int)$db->query("SELECT COUNT(*) FROM sedes")->fetchColumn();
  $sedesConInternetActivo = (int)$db->query("SELECT COUNT(DISTINCT si.id_sede) FROM sedes_internet si WHERE si.estado_servicio = 'Activo'")->fetchColumn();
  $sedesSinInternet = max(0, $totalSedes - $sedesConInternetActivo);

  // KPI: líneas fijas/móviles activas
  $lineasFijas = (int)$db->query("SELECT COUNT(*) FROM sedes_telefonia_lineas WHERE tipo_linea='Fija' AND estado='Activa'")->fetchColumn();
  $lineasMoviles = (int)$db->query("SELECT COUNT(*) FROM sedes_telefonia_lineas WHERE tipo_linea='Móvil' AND estado='Activa'")->fetchColumn();

  // KPI: vigilancia activa y total de cámaras
  $vigilanciaActiva = (int)$db->query("SELECT COUNT(*) FROM sedes_vigilancia WHERE estado_servicio='Activo'")->fetchColumn();
  $totalCamaras = (int)$db->query("SELECT COALESCE(SUM(cantidad),0) FROM sedes_vigilancia_dispositivos WHERE tipo_dispositivo='Cámara' AND estado='Activo'")->fetchColumn();

  // Series: tipos de conexión
  $rowsTipos = $db->query("SELECT tipo_conexion, COUNT(*) c FROM sedes_internet WHERE estado_servicio='Activo' GROUP BY tipo_conexion ORDER BY c DESC")->fetchAll();
  $tiposLabels = array();
  $tiposData = array();
  foreach ($rowsTipos as $r) {
    $tiposLabels[] = $r['tipo_conexion'];
    $tiposData[] = (int)$r['c'];
  }
  $tipos = ['labels' => $tiposLabels, 'data' => $tiposData];

  // Series: líneas por operador
  $rowsOps = $db->query("SELECT operador, COUNT(*) c FROM sedes_telefonia_lineas WHERE estado='Activa' GROUP BY operador ORDER BY c DESC LIMIT 10")->fetchAll();
  $opsLabels = array();
  $opsData = array();
  foreach ($rowsOps as $r) {
    $opsLabels[] = ($r['operador'] ?: 'Sin operador');
    $opsData[] = (int)$r['c'];
  }
  $operadores = ['labels' => $opsLabels, 'data' => $opsData];

  // Series: dispositivos de red por tipo
  $rowsRed = $db->query("SELECT tipo_dispositivo, COALESCE(SUM(cantidad),0) c FROM sedes_red_dispositivos WHERE estado='Activo' GROUP BY tipo_dispositivo ORDER BY c DESC")->fetchAll();
  $redLabels = array();
  $redData = array();
  foreach ($rowsRed as $r) {
    $redLabels[] = $r['tipo_dispositivo'];
    $redData[] = (int)$r['c'];
  }
  $red = ['labels' => $redLabels, 'data' => $redData];

  Logger::debug('Contadores de telecomunicaciones cargados', [
      'sedes_con_internet' => $sedesConInternetActivo,
      'lineas_fijas' => $lineasFijas,
      'lineas_moviles' => $lineasMoviles,
      'vigilancia_activa' => $vigilanciaActiva
  ]);
  
  json_success([
    'kpis' => [
      'sedes_con_internet_activo' => $sedesConInternetActivo,
      'sedes_sin_internet' => $sedesSinInternet,
      'lineas_fijas_activas' => $lineasFijas,
      'lineas_moviles_activas' => $lineasMoviles,
      'vigilancia_activa' => $vigilanciaActiva,
      'total_camaras' => $totalCamaras,
    ],
    'tipos_conexion' => $tipos,
    'operadores' => $operadores,
    'red' => $red,
  ]);
  
} catch (Exception $e) {
  Logger::error('Error al cargar contadores de telecomunicaciones', [
      'mensaje' => $e->getMessage()
  ]);
  json_error($e->getMessage(), 500);
}

