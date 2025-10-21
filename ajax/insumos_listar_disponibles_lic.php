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
                 tipo_insumo AS tipo
          FROM insumos
          WHERE id_licitacion IS NULL
            AND (? = '' OR nombre_insumo LIKE CONCAT('%', ?, '%') OR numero_serie LIKE CONCAT('%', ?, '%') OR id_fisico LIKE CONCAT('%', ?, '%'))
          ORDER BY nombre_insumo";
  $stmt = $db->prepare($sql);
  $stmt->execute([$q, $q, $q, $q]);
  $rows = $stmt->fetchAll();
  echo json_encode(['success'=>true, 'data'=>$rows]);
}catch(Exception $e){ echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
