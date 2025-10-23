<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

error_log("=== INGRESOS_GET.PHP ===");
error_log("GET params: " . json_encode($_GET));

try{
  if (empty($_GET['id'])) {
    error_log("ERROR: ID vacío");
    throw new Exception('ID requerido');
  }
  
  $id = (int)$_GET['id'];
  error_log("Buscando ingreso ID: {$id}");
  
  $db = conectarDB();
  
  $stmt = $db->prepare('SELECT id_ingreso, tipo_ingreso, nro_referencia, 
                               DATE(fecha_finalizacion) as fecha_finalizacion, 
                               descripcion, created_at 
                        FROM ingresos WHERE id_ingreso=?');
  $stmt->execute([$id]);
  $cab = $stmt->fetch();
  
  if (!$cab) {
    error_log("ERROR: Ingreso no encontrado con ID {$id}");
    throw new Exception('Ingreso no encontrado');
  }
  
  error_log("Ingreso encontrado: " . $cab['nro_referencia']);
  
  $ins = $db->prepare('SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, estado FROM insumos WHERE id_ingreso=? ORDER BY nombre_insumo');
  $ins->execute([$id]);
  $cab['insumos'] = $ins->fetchAll();
  
  error_log("Insumos encontrados: " . count($cab['insumos']));
  error_log("Respuesta exitosa");
  
  echo json_encode(['success'=>true, 'data'=>$cab]);
}catch(Exception $e){ 
  error_log("ERROR en ingresos_get.php: " . $e->getMessage());
  error_log("Trace: " . $e->getTraceAsString());
  echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); 
}
?>
