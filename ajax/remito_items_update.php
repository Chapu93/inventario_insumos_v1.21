<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'editar')) {
    json_error('No tienes permisos para editar asignaciones', 403);
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      json_error('Método inválido', 405);
  }
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  if (!$input || empty($input['remito']) || empty($input['items']) || !is_array($input['items'])) {
    json_error('Datos incompletos', 400);
  }
  
  if (!verify_csrf()) {
      json_error('CSRF inválido', 403);
  }
  $db = conectarDB();
  $db->beginTransaction();

  // Encontrar id_remito
  $stmt = $db->prepare('SELECT id_remito FROM remitos WHERE numero_remito = ? LIMIT 1');
  $stmt->execute([$input['remito']]);
  $row = $stmt->fetch();
  if (!$row) { throw new Exception('Remito no encontrado'); }
  $idRemito = (int)$row['id_remito'];

  foreach ($input['items'] as $it) {
    $idInsumo = (int)($it['id_insumo'] ?? 0);
    $newQty = isset($it['cantidad']) ? max(1, (int)$it['cantidad']) : null;
    if ($idInsumo <= 0 || $newQty === null) { continue; }

    // Obtener tipo/stock actual del insumo
    $s = $db->prepare('SELECT tipo_insumo, cantidad FROM insumos WHERE id_insumo = ? FOR UPDATE');
    $s->execute([$idInsumo]);
    $ins = $s->fetch();
    if (!$ins) { throw new Exception('Insumo no encontrado'); }

    // Obtener detalle actual
    $sd = $db->prepare('SELECT cantidad FROM remitos_detalle WHERE id_remito = ? AND id_insumo = ? FOR UPDATE');
    $sd->execute([$idRemito, $idInsumo]);
    $det = $sd->fetch();
    if (!$det) { throw new Exception('Detalle no encontrado'); }

    $oldQty = (int)$det['cantidad'];
    if ($oldQty === $newQty) { continue; }

    if ($ins['tipo_insumo'] === 'Varios') {
      // Reponer la cantidad anterior al stock y descontar la nueva
      $stock = (int)$ins['cantidad'];
      $stock += $oldQty;      // devolvemos lo anterior
      if ($stock < $newQty) { throw new Exception('Stock insuficiente para actualizar'); }
      $stock -= $newQty;      // aplicamos lo nuevo
      $estado = $stock > 0 ? 'Disponible' : 'Asignado';
      $db->prepare('UPDATE insumos SET cantidad = ?, estado = ? WHERE id_insumo = ?')->execute([$stock, $estado, $idInsumo]);
    } else {
      // No-"Varios": cantidad es 1 siempre
      if ($newQty !== 1) { throw new Exception('Cantidad inválida para tipo no "Varios"'); }
    }

    // Actualizar detalle
    $db->prepare('UPDATE remitos_detalle SET cantidad = ? WHERE id_remito = ? AND id_insumo = ?')->execute([$newQty, $idRemito, $idInsumo]);
  }

  $db->commit();
  
  Logger::info('Items de remito actualizados', [
      'numero_remito' => $input['remito'],
      'items_count' => count($input['items'])
  ]);
  
  json_success(['message' => 'Cantidades actualizadas correctamente']);
  
} catch (Exception $e) {
  if (isset($db) && $db->inTransaction()) {
      $db->rollBack();
  }
  Logger::error('Error al actualizar items de remito', [
      'mensaje' => $e->getMessage(),
      'numero_remito' => $input['remito'] ?? ''
  ]);
  json_error($e->getMessage(), 500);
}
