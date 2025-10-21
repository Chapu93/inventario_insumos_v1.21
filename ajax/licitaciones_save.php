<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try{
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método inválido');
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!$payload) throw new Exception('Datos inválidos');
  if (!verify_csrf()) throw new Exception('CSRF inválido');
  $db = conectarDB();
  $db->beginTransaction();

  $id = isset($payload['id_licitacion']) && $payload['id_licitacion'] ? (int)$payload['id_licitacion'] : null;
  $cod = trim($payload['cod_expediente'] ?? '');
  $fin = $payload['fecha_finalizacion'] ?? null;
  $desc = $payload['descripcion'] ?? null;
  $insumos = is_array($payload['insumos'] ?? null) ? array_map('intval', $payload['insumos']) : [];
  if ($cod === '') throw new Exception('Código expediente requerido');

  if ($id) {
    $stmt = $db->prepare('UPDATE licitaciones SET cod_expediente=?, descripcion=?, fecha_finalizacion=? WHERE id_licitacion=?');
    $stmt->execute([$cod, $desc, $fin, $id]);
  } else {
    $stmt = $db->prepare('INSERT INTO licitaciones (cod_expediente, descripcion, fecha_finalizacion) VALUES (?,?,?)');
    $stmt->execute([$cod, $desc, $fin]);
    $id = (int)$db->lastInsertId();
  }

  // Desasignar insumos actuales de esta licitación, luego asignar los enviados
  $db->prepare('UPDATE insumos SET id_licitacion=NULL WHERE id_licitacion=?')->execute([$id]);
  if (!empty($insumos)) {
    // Solo asignar insumos que no pertenezcan ya a otra licitación
    $in = implode(',', array_fill(0, count($insumos), '?'));
    $params = $insumos;
    $params[] = $id;
    $db->prepare("UPDATE insumos SET id_licitacion=? WHERE id_insumo IN ($in) AND (id_licitacion IS NULL OR id_licitacion=?)")
       ->execute(array_merge([$id], $insumos, [$id]));
  }

  $db->commit();
  echo json_encode(['success'=>true, 'id_licitacion'=>$id]);
}catch(Exception $e){ if (isset($db) && $db->inTransaction()) $db->rollBack(); echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
