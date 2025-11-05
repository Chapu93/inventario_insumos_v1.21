<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;
if ($sedeId <= 0) {
    echo json_encode(['success' => false, 'error' => 'sede_id requerido']);
    exit;
}
try {
    $db = conectarDB();
    // Para tipo Varios, mostrar total disponible (oficina + depósito)
    $sql = "SELECT id_insumo, nombre_insumo, tipo_insumo, numero_serie, id_fisico, 
                   cantidad,
                   cantidad_oficina, 
                   cantidad_deposito
            FROM insumos
            WHERE estado='Disponible'
              AND (id_sede_actual = ? OR id_sede_actual IS NULL)
              AND (tipo_insumo <> 'Varios' OR cantidad > 0)
            ORDER BY nombre_insumo";
    $stmt = $db->prepare($sql);
    $stmt->execute([$sedeId]);
    $rows = $stmt->fetchAll();
    $data = array_map(function($r){
        $texto = $r['nombre_insumo'];
        if ($r['tipo_insumo'] === 'Varios') {
            $texto .= ' (Cantidad: ' . (int)$r['cantidad'] . ')';
        } else {
            if (!empty($r['numero_serie'])) { $texto .= ' (S/N: ' . $r['numero_serie'] . ')'; }
            if (!empty($r['id_fisico'])) { $texto .= ' (ID: ' . $r['id_fisico'] . ')'; }
        }
        return [
            'id' => (int)$r['id_insumo'],
            'nombre' => $texto,
            'tipo' => $r['tipo_insumo'],
            'max' => ($r['tipo_insumo'] === 'Varios') ? (int)$r['cantidad'] : 1
        ];
    }, $rows);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}