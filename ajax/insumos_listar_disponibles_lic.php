<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try{
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
          WHERE id_licitacion IS NULL
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
  echo json_encode(['success'=>true, 'data'=>$data]);
}catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
