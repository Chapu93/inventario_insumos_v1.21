<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try {
  $db = conectarDB();
  $rows = $db->query("SELECT l.id_licitacion, l.cod_expediente, l.fecha_finalizacion,
                             (SELECT COUNT(*) FROM insumos i WHERE i.id_licitacion = l.id_licitacion) AS num_insumos
                      FROM licitaciones l ORDER BY l.created_at DESC")->fetchAll();
  echo json_encode(['success'=>true, 'data'=>$rows]);
} catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage(), 'data'=>[]]); }
