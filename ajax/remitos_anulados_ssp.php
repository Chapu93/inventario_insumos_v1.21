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
    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Contar total de remitos anulados
    $total = (int)$db->query("SELECT COUNT(*) FROM remitos WHERE estado = 'Anulado'")->fetchColumn();

    $where = ["r.estado = 'Anulado'"];
    $params = [];
    if ($search !== '') {
        $where[] = '(r.numero_remito LIKE ? OR r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ? OR s.nombre_sede LIKE ? OR a.nombre_area LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $countSql = "SELECT COUNT(*)
                 FROM remitos r
                 LEFT JOIN sedes s ON s.id_sede = r.id_sede
                 LEFT JOIN areas a ON a.id_area = r.id_area
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    $sql = "SELECT r.numero_remito, r.fecha_asignacion, r.fecha_anulacion, r.motivo_anulacion,
                   r.nombre_persona_asignada, r.apellido_persona_asignada,
                   s.nombre_sede, a.nombre_area
            FROM remitos r
            LEFT JOIN sedes s ON s.id_sede = r.id_sede
            LEFT JOIN areas a ON a.id_area = r.id_area
            $whereSql
            ORDER BY r.fecha_anulacion DESC, r.id_remito DESC
            LIMIT $start, $length";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        $nombreCompleto = htmlspecialchars(trim($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']));
        $sede = htmlspecialchars($r['nombre_sede'] ?? '-');
        $area = htmlspecialchars($r['nombre_area'] ?? '-');
        $fechaAsignacion = date('d/m/Y', strtotime($r['fecha_asignacion']));
        $fechaAnulacion = $r['fecha_anulacion'] ? date('d/m/Y H:i', strtotime($r['fecha_anulacion'])) : '-';
        $motivo = htmlspecialchars($r['motivo_anulacion'] ?? '-');
        
        $btnVer = '<button type="button" class="btn btn-sm btn-outline-primary" onclick="mostrarRemitoResumen(\'' . htmlspecialchars($r['numero_remito']) . '\')">
                    <i class="fas fa-eye"></i> Ver
                   </button>';
        
        return [
            '<strong>' . htmlspecialchars($r['numero_remito']) . '</strong>',
            $fechaAsignacion,
            $fechaAnulacion,
            $nombreCompleto,
            $sede,
            $area,
            '<small>' . $motivo . '</small>',
            $btnVer
        ];
    }, $rows);

    Logger::debug('Remitos anulados cargados (SSP)', [
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
    Logger::error('Error en remitos anulados (SSP)', [
        'mensaje' => $e->getMessage()
    ]);
    json_response(['error' => $e->getMessage()], 500);
}
?>
