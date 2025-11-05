<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try{
  // Aceptar tanto POST como GET
  $ids = [];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $ids = isset($payload['ids']) && is_array($payload['ids']) ? array_map('intval', $payload['ids']) : [];
  } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ids'])) {
    // Aceptar GET con parámetro ids (puede ser string "123" o array)
    $idsParam = $_GET['ids'];
    if (is_array($idsParam)) {
      $ids = array_map('intval', $idsParam);
    } else {
      // Si es string, puede ser un solo ID o varios separados por coma
      $ids = array_map('intval', explode(',', $idsParam));
    }
  }
  
  if (empty($ids)) { echo json_encode(['success'=>true, 'data'=>[]]); exit; }
  
  $db = conectarDB();
  $in = implode(',', array_fill(0, count($ids), '?'));
  // Incluir campos de stock dual para tipo Varios
  $sql = "SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito 
          FROM insumos 
          WHERE id_insumo IN ($in)";
  $stmt = $db->prepare($sql);
  $stmt->execute($ids);
  echo json_encode(['success'=>true, 'data'=>$stmt->fetchAll()]);
}catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
