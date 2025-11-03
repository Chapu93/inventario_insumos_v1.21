<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try{
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método inválido');
  $payload = json_decode(file_get_contents('php://input'), true);
  $ids = isset($payload['ids']) && is_array($payload['ids']) ? array_map('intval', $payload['ids']) : [];
  if (empty($ids)) { echo json_encode(['success'=>true, 'data'=>[]]); exit; }
  $db = conectarDB();
  $in = implode(',', array_fill(0, count($ids), '?'));
  $sql = "SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad FROM insumos WHERE id_insumo IN ($in)";
  $stmt = $db->prepare($sql);
  $stmt->execute($ids);
  echo json_encode(['success'=>true, 'data'=>$stmt->fetchAll()]);
}catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
