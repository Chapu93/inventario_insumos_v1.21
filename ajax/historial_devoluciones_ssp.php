<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = conectarDB();
    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Contar filas donde cantidad_devuelta > 0
    $total = (int)$db->query('SELECT COUNT(*) FROM remitos_detalle WHERE COALESCE(cantidad_devuelta,0) > 0')->fetchColumn();

    $where = ['COALESCE(d.cantidad_devuelta,0) > 0'];
    $params = [];
    if ($search !== '') {
        $where[] = '(r.numero_remito LIKE ? OR i.nombre_insumo LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $countSql = "SELECT COUNT(*)
                 FROM remitos_detalle d
                 JOIN remitos r ON r.id_remito = d.id_remito
                 JOIN insumos i ON i.id_insumo = d.id_insumo
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    $sql = "SELECT r.numero_remito, r.fecha_asignacion, i.nombre_insumo, d.cantidad, COALESCE(d.cantidad_devuelta,0) AS dev,
                   GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0),0) AS pend
            FROM remitos_detalle d
            JOIN remitos r ON r.id_remito = d.id_remito
            JOIN insumos i ON i.id_insumo = d.id_insumo
            $whereSql
            ORDER BY r.fecha_asignacion DESC, r.id_remito DESC
            LIMIT $start, $length";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        return [
            '<strong>' . htmlspecialchars($r['numero_remito']) . '</strong>',
            date('d/m/Y', strtotime($r['fecha_asignacion'])),
            htmlspecialchars($r['nombre_insumo']),
            '<span class="badge bg-dark">' . (int)$r['cantidad'] . '</span>',
            '<span class="badge bg-success">' . (int)$r['dev'] . '</span>',
            '<span class="badge ' . ((int)$r['pend']>0 ? 'bg-warning' : 'bg-secondary') . '">' . (int)$r['pend'] . '</span>',
        ];
    }, $rows);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

