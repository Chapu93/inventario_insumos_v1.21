<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try{
  if (empty($_GET['id'])) throw new Exception('ID requerido');
  $db = conectarDB();
  $stmt = $db->prepare('SELECT * FROM licitaciones WHERE id_licitacion=?');
  $stmt->execute([(int)$_GET['id']]);
  $cab = $stmt->fetch();
  if (!$cab) throw new Exception('No encontrado');
  $ins = $db->prepare('SELECT id_insumo, nombre_insumo, tipo_insumo FROM insumos WHERE id_licitacion=? ORDER BY nombre_insumo');
  $ins->execute([(int)$_GET['id']]);
  $cab['insumos'] = $ins->fetchAll();
  echo json_encode(['success'=>true, 'data'=>$cab]);
}catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
