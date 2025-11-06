<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación y permisos
    if (!estaAutenticado()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
    
    if (!tienePermiso('auditoria', 'ver_todo')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        exit;
    }
    
    $db = conectarDB();
    
    // Parámetros DataTables
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    
    // Filtros adicionales
    $filtroUsuario = isset($_GET['filtro_usuario']) ? trim($_GET['filtro_usuario']) : '';
    $filtroModulo = isset($_GET['filtro_modulo']) ? trim($_GET['filtro_modulo']) : '';
    $filtroResultado = isset($_GET['filtro_resultado']) ? trim($_GET['filtro_resultado']) : '';
    $filtroFechaDesde = isset($_GET['filtro_fecha_desde']) ? trim($_GET['filtro_fecha_desde']) : '';
    
    // Columnas disponibles para ordenar
    $columns = [
        0 => 'a.fecha_accion',
        1 => 'u.username',
        2 => 'a.modulo',
        3 => 'a.accion',
        4 => 'a.descripcion',
        5 => 'a.resultado',
        6 => 'a.ip_address'
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'a.fecha_accion';
    
    // Total de registros
    $total = (int)$db->query("SELECT COUNT(*) FROM auditoria_acciones")->fetchColumn();
    
    // Construir WHERE
    $where = [];
    $params = [];
    
    if ($filtroUsuario !== '') {
        $where[] = 'a.id_usuario = ?';
        $params[] = (int)$filtroUsuario;
    }
    
    if ($filtroModulo !== '') {
        $where[] = 'a.modulo = ?';
        $params[] = $filtroModulo;
    }
    
    if ($filtroResultado !== '') {
        $where[] = 'a.resultado = ?';
        $params[] = $filtroResultado;
    }
    
    if ($filtroFechaDesde !== '') {
        $where[] = 'DATE(a.fecha_accion) >= ?';
        $params[] = $filtroFechaDesde;
    }
    
    if ($search !== '') {
        $where[] = '(u.username LIKE ? OR a.accion LIKE ? OR a.descripcion LIKE ? OR a.ip_address LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    
    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    
    // Total filtrado
    $countSql = "SELECT COUNT(*)
                 FROM auditoria_acciones a
                 LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();
    
    // Obtener datos
    $dataSql = "SELECT 
                    a.id_auditoria,
                    a.fecha_accion,
                    a.accion,
                    a.modulo,
                    a.descripcion,
                    a.resultado,
                    a.ip_address,
                    a.mensaje_error,
                    u.id_usuario,
                    u.username,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
                FROM auditoria_acciones a
                LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
                $whereSql
                ORDER BY $orderBy $orderDir
                LIMIT $start, $length";
    
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    
    // Mapear datos para DataTable
    $data = array_map(function($r) {
        // Fecha formateada
        $fecha = date('d/m/Y H:i:s', strtotime($r['fecha_accion']));
        
        // Usuario
        $usuario = htmlspecialchars($r['username'] ?? 'Sistema');
        $nombreCompleto = htmlspecialchars($r['nombre_completo'] ?? '');
        if ($nombreCompleto) {
            $usuario .= '<div class="text-muted small">' . $nombreCompleto . '</div>';
        }
        
        // Módulo
        $modulo = '<span class="badge bg-info">' . htmlspecialchars($r['modulo']) . '</span>';
        
        // Acción
        $accion = '<span class="badge bg-primary">' . htmlspecialchars($r['accion']) . '</span>';
        
        // Descripción (limitada)
        $desc = htmlspecialchars($r['descripcion']);
        if (strlen($desc) > 80) {
            $desc = substr($desc, 0, 80) . '...';
        }
        
        // Resultado
        if ($r['resultado'] === 'exito') {
            $resultado = '<span class="badge bg-success"><i class="fas fa-check"></i> Éxito</span>';
        } else {
            $resultado = '<span class="badge bg-danger"><i class="fas fa-times"></i> Error</span>';
            if ($r['mensaje_error']) {
                $resultado .= '<div class="text-danger small mt-1">' . htmlspecialchars(substr($r['mensaje_error'], 0, 50)) . '</div>';
            }
        }
        
        // IP
        $ip = htmlspecialchars($r['ip_address'] ?? 'N/A');
        
        // Botón detalles
        $btnDetalles = '<button type="button" class="btn btn-sm btn-outline-info btn-ver-detalles" data-id="' . $r['id_auditoria'] . '" data-bs-toggle="tooltip" title="Ver Detalles"><i class="fas fa-eye"></i></button>';
        
        return [
            $fecha,
            $usuario,
            $modulo,
            $accion,
            $desc,
            $resultado,
            $ip,
            $btnDetalles
        ];
    }, $rows);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log('Error en auditoria_list_ssp.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
