<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'ver')) {
    json_error('No tienes permisos para ver asignaciones', 403);
}

try {
    $db = conectarDB();

    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    $columns = [
        0 => 'r.numero_remito',
        1 => 'r.nombre_persona_asignada',
        2 => 'r.fecha_asignacion',
        3 => 's.nombre_sede',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'r.fecha_asignacion';

    $total = (int)$db->query('SELECT COUNT(*) FROM remitos')->fetchColumn();

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(r.numero_remito LIKE ? OR r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ? OR s.nombre_sede LIKE ? OR l.nombre_localidad LIKE ? OR a.nombre_area LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    $countSql = "SELECT COUNT(*)
                 FROM remitos r
                 JOIN sedes s ON r.id_sede = s.id_sede
                 JOIN localidades l ON s.id_localidad = l.id_localidad
                 LEFT JOIN areas a ON r.id_area = a.id_area
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    $dataSql = "SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada, s.nombre_sede, l.nombre_localidad
                FROM remitos r
                JOIN sedes s ON r.id_sede = s.id_sede
                JOIN localidades l ON s.id_localidad = l.id_localidad
                LEFT JOIN areas a ON r.id_area = a.id_area
                $whereSql
                ORDER BY $orderBy $orderDir, r.id_remito DESC
                LIMIT $start, $length";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        // Verificar permisos para cada acción
        $puedeVer = tienePermiso('asignaciones', 'ver');
        $puedeImprimir = tienePermiso('asignaciones', 'ver'); // Same permission as ver
        
        // Construir botones solo si hay permisos
        $botones = [];
        
        if ($puedeImprimir) {
            $botones[] = '<button type="button" class="btn btn-sm btn-primary" aria-label="Imprimir remito" onclick="generarRemitoPDF(\'' . htmlspecialchars($r['numero_remito'], ENT_QUOTES) . '\')" data-bs-toggle="tooltip" title="Imprimir remito"><i class="fas fa-print" aria-hidden="true"></i></button>';
        }
        
        if ($puedeVer) {
            $botones[] = '<a href="?remito=' . htmlspecialchars($r['numero_remito'], ENT_QUOTES) . '" class="btn btn-sm btn-info" aria-label="Ver detalles del remito" data-bs-toggle="tooltip" title="Ver detalles"><i class="fas fa-eye" aria-hidden="true"></i></a>';
        }
        
        $acciones = '<div class="btn-group" role="group">' . implode(' ', $botones) . '</div>';
        return [
            '<strong>' . htmlspecialchars($r['numero_remito']) . '</strong>',
            htmlspecialchars($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']),
            date('d/m/Y', strtotime($r['fecha_asignacion'])),
            htmlspecialchars($r['nombre_sede'] . ' - ' . $r['nombre_localidad']),
            $acciones,
        ];
    }, $rows);

    Logger::debug('Lista de remitos cargada (SSP)', [
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
    Logger::error('Error en lista de remitos (SSP)', [
        'mensaje' => $e->getMessage()
    ]);
    json_response(['error' => $e->getMessage()], 500);
}
?>

