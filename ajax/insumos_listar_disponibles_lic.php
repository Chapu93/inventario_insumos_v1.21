<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

try {
  $db = conectarDB();
  $q = trim($_GET['q'] ?? '');
  $sql = "SELECT id_insumo AS id,
                 CONCAT(nombre_insumo,
                        CASE WHEN tipo_insumo='Varios' THEN CONCAT(' (Cantidad: ', COALESCE(cantidad,0), ')')
                             ELSE CONCAT(IFNULL(CONCAT(' (S/N: ', NULLIF(numero_serie,''), ')'),''), IFNULL(CONCAT(' (ID: ', NULLIF(id_fisico,''), ')'),''))
                        END) AS nombre,
                 tipo_insumo AS tipo,
                 COALESCE(cantidad,1) AS max
          FROM insumos
          WHERE id_ingreso IS NULL
            AND (? = '' OR nombre_insumo LIKE CONCAT('%', ?, '%') OR numero_serie LIKE CONCAT('%', ?, '%') OR id_fisico LIKE CONCAT('%', ?, '%'))
          ORDER BY nombre_insumo";
  $stmt = $db->prepare($sql);
  $stmt->execute([$q, $q, $q, $q]);
  $rows = $stmt->fetchAll();
  // Normalizar estructura (id, nombre, tipo, max)
  $data = array_map(function($r){
    return [
      'id' => (int)$r['id'],
      'nombre' => (string)$r['nombre'],
      'tipo' => (string)$r['tipo'],
      'max' => (int)$r['max']
    ];
  }, $rows);
  Logger::debug('Insumos disponibles para licitación', ['query' => $q, 'count' => count($data)]);
  
  json_success($data);
  
} catch(Exception $e) {
    Logger::error('Error al listar insumos disponibles para licitación', [
        'mensaje' => $e->getMessage(),
        'query' => $q ?? ''
    ]);
    json_error($e->getMessage(), 500);
}
