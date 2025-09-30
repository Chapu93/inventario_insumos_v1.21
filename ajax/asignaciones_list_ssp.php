<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = conectarDB();

    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    $columns = [
        0 => 'r.nombre_persona_asignada',
        1 => 'l.nombre_localidad',
        2 => 'r.fecha_asignacion',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'r.fecha_asignacion';

    $filtro_localidad = $_GET['localidad'] ?? '';
    $filtro_insumo = $_GET['insumo'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_area = $_GET['area'] ?? '';

    // Base subconsulta para contar activas
    $baseFrom = " FROM remitos r 
                   JOIN remitos_detalle d ON d.id_remito = r.id_remito
                   JOIN insumos i ON d.id_insumo = i.id_insumo
                   JOIN areas ar ON r.id_area = ar.id_area
                   JOIN sedes s ON r.id_sede = s.id_sede
                   JOIN localidades l ON s.id_localidad = l.id_localidad ";

    // WHERE
    $where = [];
    $params = [];
    if ($filtro_localidad !== '') { $where[] = 'l.id_localidad = ?'; $params[] = $filtro_localidad; }
    if ($filtro_insumo !== '') { $where[] = 'i.tipo_insumo = ?'; $params[] = $filtro_insumo; }
    if ($filtro_area !== '') { $where[] = 'r.id_area = ?'; $params[] = $filtro_area; }
    if ($search !== '') {
        $where[] = '(r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ? OR r.numero_remito LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    // Total (por remito)
    $total = (int)$db->query('SELECT COUNT(*) FROM remitos')->fetchColumn();

    // Filtrado y agrupado por remito
    $sqlGroup = "SELECT r.numero_remito,
                        r.fecha_asignacion,
                        r.nombre_persona_asignada,
                        r.apellido_persona_asignada,
                        ar.nombre_area,
                        s.nombre_sede,
                        l.nombre_localidad,
                        SUM(d.cantidad) AS cantidad_insumos,
                        SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0), 0)) AS activas
                 $baseFrom
                 $whereSql
                 GROUP BY r.id_remito";

    // Total filtrado
    $countSql = "SELECT COUNT(*) FROM ( $sqlGroup ) t";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    // Estado HAVING
    $having = '';
    $extParams = $params;
    if ($filtro_estado === 'Activa') { $having = ' HAVING activas > 0'; }
    if ($filtro_estado === 'Devuelta') { $having = ' HAVING activas = 0'; }

    // Page data
    // Orden por fecha más reciente como primario; agrega desempate por id_remito DESC
    $pageSql = $sqlGroup . $having . " ORDER BY $orderBy $orderDir, r.id_remito DESC LIMIT $start, $length";
    $stmt = $db->prepare($pageSql);
    $stmt->execute($extParams);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        $estado = ((int)$r['activas'] > 0) ? 'Activa' : 'Devuelta';
        $estadoBadge = '<span class="badge estado-' . strtolower($estado) . '">' . $estado . '</span>';
        $acciones = '<div class="btn-group" role="group">'
                  . '<button type="button" class="btn btn-sm btn-info" aria-label="Ver asignación" onclick="abrirVerAsignacion(\'' . htmlspecialchars($r['numero_remito'], ENT_QUOTES) . '\')" data-bs-toggle="tooltip" title="Ver asignación"><i class="fas fa-eye" aria-hidden="true"></i></button>'
                  . ' <button type="button" class="btn btn-sm btn-primary" aria-label="Imprimir remito" onclick="generarRemitoPDF(\'' . htmlspecialchars($r['numero_remito'], ENT_QUOTES) . '\')" data-bs-toggle="tooltip" title="Imprimir remito"><i class="fas fa-print" aria-hidden="true"></i></button>'
                  . ' <button type="button" class="btn btn-sm btn-warning" aria-label="Devolver insumos" onclick="abrirDevolucion(\'' . htmlspecialchars($r['numero_remito'], ENT_QUOTES) . '\')" data-bs-toggle="tooltip" title="Devolver insumos"><i class="fas fa-undo" aria-hidden="true"></i></button>'
                  . '</div>';
        return [
            htmlspecialchars($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']),
            htmlspecialchars($r['nombre_localidad']),
            date('d/m/Y', strtotime($r['fecha_asignacion'])),
            $estadoBadge,
            $acciones,
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

