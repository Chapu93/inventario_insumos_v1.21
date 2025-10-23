<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try {
  $db = conectarDB();
  $rows = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, 
                             DATE(fecha_finalizacion) as fecha_finalizacion, 
                             created_at,
                             (SELECT COUNT(*) FROM insumos i WHERE i.id_ingreso = ing.id_ingreso) AS num_insumos
                      FROM ingresos ing ORDER BY ing.created_at DESC")->fetchAll();
  echo json_encode(['success'=>true, 'data'=>$rows]);
} catch(Exception $e){ 
  echo json_encode(['success'=>false, 'error'=>$e->getMessage(), 'data'=>[]]); 
}
?>
