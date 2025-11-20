<?php
require_once '../includes/config.php';

try {
    $db = conectarDB();
    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    $total = (int)$db->query('SELECT COUNT(*) FROM insumos_bajas')->fetchColumn();

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(i.nombre_insumo LIKE ? OR b.observacion LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    $whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    $countSql = "SELECT COUNT(*)
                 FROM insumos_bajas b
                 JOIN insumos i ON i.id_insumo = b.id_insumo
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();


    $sql = "SELECT b.fecha_baja, i.nombre_insumo, i.tipo_insumo, b.cantidad, b.observacion
            FROM insumos_bajas b
            JOIN insumos i ON i.id_insumo = b.id_insumo
            $whereSql
            ORDER BY b.fecha_baja DESC, b.id_baja DESC
            LIMIT $start, $length";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r) {
        return [
            date('d/m/Y H:i', strtotime($r['fecha_baja'])),
            htmlspecialchars($r['nombre_insumo']),
            htmlspecialchars($r['tipo_insumo']),
            (isset($r['cantidad']) ? (int)$r['cantidad'] : 1),
            htmlspecialchars($r['observacion'] ?? '')
        ];
    }, $rows);

    Logger::debug('Historial de bajas cargado (SSP)', [
        'total' => $total,
        'filtered' => $filtered,
        'search' => $search
    ]);
    
    json_response([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ], 200);
    
} catch (Exception $e) {
    Logger::error('Error en historial de bajas (SSP)', [
        'mensaje' => $e->getMessage()
    ]);
    json_response(['error' => $e->getMessage()], 500);
}
?>

