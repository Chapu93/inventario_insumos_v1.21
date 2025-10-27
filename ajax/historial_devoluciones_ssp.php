<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = conectarDB();
    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Contar remitos que tienen al menos una devolución
    $total = (int)$db->query("SELECT COUNT(DISTINCT r.id_remito) 
                              FROM remitos r 
                              JOIN remitos_detalle d ON d.id_remito = r.id_remito 
                              WHERE COALESCE(d.cantidad_devuelta,0) > 0")->fetchColumn();

    $where = ['COALESCE(d.cantidad_devuelta,0) > 0'];
    $params = [];
    if ($search !== '') {
        $where[] = '(r.numero_remito LIKE ? OR r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // Contar remitos filtrados
    $countSql = "SELECT COUNT(DISTINCT r.id_remito)
                 FROM remitos_detalle d
                 JOIN remitos r ON r.id_remito = d.id_remito
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    // Agrupar por remito con totales
    $sql = "SELECT r.numero_remito, 
                   r.fecha_asignacion, 
                   r.fecha_devolucion,
                   r.nombre_persona_asignada,
                   r.apellido_persona_asignada,
                   SUM(d.cantidad) AS total_asignados,
                   SUM(COALESCE(d.cantidad_devuelta,0)) AS total_devueltos,
                   SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0),0)) AS total_pendientes,
                   COUNT(d.id_detalle) AS num_items
            FROM remitos_detalle d
            JOIN remitos r ON r.id_remito = d.id_remito
            $whereSql
            GROUP BY r.id_remito, r.numero_remito, r.fecha_asignacion, r.fecha_devolucion, r.nombre_persona_asignada, r.apellido_persona_asignada
            ORDER BY r.fecha_devolucion DESC, r.fecha_asignacion DESC, r.id_remito DESC
            LIMIT $start, $length";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        $fechaAsignacion = date('d/m/Y', strtotime($r['fecha_asignacion']));
        $fechaDevolucion = $r['fecha_devolucion'] ? date('d/m/Y', strtotime($r['fecha_devolucion'])) : '<span class="text-muted">-</span>';
        $nombreCompleto = htmlspecialchars(trim($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']));
        $totalPendientes = (int)$r['total_pendientes'];
        $numItems = (int)$r['num_items'];
        
        $btnVer = '<button type="button" class="btn btn-sm btn-outline-primary" onclick="mostrarRemitoResumen(\'' . htmlspecialchars($r['numero_remito']) . '\')">
                    <i class="fas fa-eye"></i> Ver
                   </button>';
        
        return [
            '<strong>' . htmlspecialchars($r['numero_remito']) . '</strong>',
            $fechaAsignacion,
            $fechaDevolucion,
            $nombreCompleto,
            '<span class="badge bg-info">' . $numItems . '</span>',
            '<span class="badge bg-dark">' . (int)$r['total_asignados'] . '</span>',
            '<span class="badge bg-success">' . (int)$r['total_devueltos'] . '</span>',
            '<span class="badge ' . ($totalPendientes > 0 ? 'bg-warning' : 'bg-secondary') . '">' . $totalPendientes . '</span>',
            $btnVer
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

