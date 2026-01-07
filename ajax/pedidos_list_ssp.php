<?php
// Server Side Processing for Pedidos List
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// Permiso básico de lectura
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    json_error('No tienes permisos para ver pedidos', 403);
}

try {
    $db = conectarDB();
    $usuarioId = obtenerUsuarioId();
    $usuario = obtenerUsuario();

    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    $columns = [
        0 => 'p.id_pedido',
        1 => 'p.solicitante_nombre',
        2 => 'p.tipo',
        3 => 'p.prioridad',
        4 => 'p.estado',
        5 => 'p.fecha_creacion',
        6 => 's.nombre_sede', // Sede
        7 => 'u_asig.username' // Asignado
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 5; // Default fecha
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'p.fecha_creacion';

    // Filtros
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_tipo = $_GET['tipo'] ?? '';
    $filtro_prioridad = $_GET['prioridad'] ?? '';
    $filtro_sede = $_GET['sede'] ?? '';
    $modo = $_GET['modo'] ?? 'mis_pedidos'; // 'mis_pedidos' o 'pendientes'

    // Base query
    $baseFrom = " FROM pedidos p
                  JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                  JOIN sedes s ON p.id_sede = s.id_sede
                  LEFT JOIN usuarios u_asig ON p.asignado_a = u_asig.id_usuario 
                  LEFT JOIN areas a ON p.id_area = a.id_area ";

    $where = ["1=1"];
    $params = [];

    // Lógica de Modos y Permisos
    if ($modo === 'mis_pedidos') {
        // Mis Pedidos = Solo pedidos asignados a mí (o que tomé)
        $where[] = "p.asignado_a = ?";
        $params[] = $usuarioId;
    } elseif ($modo === 'pendientes') {
        // Modo pendientes (para técnicos/admins)
        if (!tienePermiso('pedidos', 'ver_todos') && !tienePermiso('pedidos', 'gestionar')) {
             json_error('No tienes permisos para ver pendientes globales', 403);
        }
        
        $where[] = "(p.asignado_a IS NULL OR p.asignado_a = 0)";
        
        // Además, filtrar por estados "abiertos"
        $where[] = "p.estado IN ('Pendiente', 'En Proceso')";

    } elseif ($modo === 'todos') {
         // Para consultores o vista completa
         if (!tienePermiso('pedidos', 'ver_todos')) {
            json_error('No tienes permisos', 403);
         }
    }

    // Filtros específicos
    if ($filtro_estado !== '') { $where[] = 'p.estado = ?'; $params[] = $filtro_estado; }
    if ($filtro_tipo !== '') { $where[] = 'p.tipo = ?'; $params[] = $filtro_tipo; }
    if ($filtro_prioridad !== '') { $where[] = 'p.prioridad = ?'; $params[] = $filtro_prioridad; }
    if ($filtro_sede !== '') { $where[] = 'p.id_sede = ?'; $params[] = $filtro_sede; }

    if ($search !== '') {
        $where[] = '(p.solicitante_nombre LIKE ? OR p.descripcion LIKE ? OR s.nombre_sede LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // Total filtered
    $sqlCount = "SELECT COUNT(*) $baseFrom $whereSql";
    $stmt = $db->prepare($sqlCount);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    // Total records (sin filtros de búsqueda, pero respetando modo)
    $total = $filtered; 

    // Data
    $pageSql = "SELECT p.*, 
                       u_asig.username as asignado_user, u_asig.nombre as asignado_nombre, u_asig.apellido as asignado_apellido,
                       s.nombre_sede, a.nombre_area
                $baseFrom $whereSql 
                ORDER BY $orderBy $orderDir 
                LIMIT $start, $length";
    
    $stmt = $db->prepare($pageSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r) use ($usuarioId) {
        // Formatear datos
        $solicitante = htmlspecialchars($r['solicitante_nombre']) . '<br><small class="text-muted">' . htmlspecialchars($r['nombre_sede']) . '</small>';
        
        $asignado = $r['asignado_a'] 
            ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['asignado_nombre'] . ' ' . $r['asignado_apellido']) . '</span>' 
            : '<span class="badge bg-secondary">Sin Asignar</span>';
        
        $prioridadClass = match($r['prioridad']) {
            'Alta' => 'danger',
            'Media' => 'warning text-dark',
            'Baja' => 'success',
            default => 'secondary'
        };
        $prioridad = '<span class="badge bg-' . $prioridadClass . '">' . $r['prioridad'] . '</span>';
        
        $estadoClass = match($r['estado']) {
            'Pendiente' => 'warning text-dark',
            'En Proceso' => 'primary',
            'Completado' => 'success',
            'Rechazado' => 'danger',
            default => 'secondary'
        };
        $estado = '<span class="badge bg-' . $estadoClass . '">' . $r['estado'] . '</span>';

        // Acciones (validar permisos localmente)
        $botones = [];
        // Ver siempre
        $botones[] = '<button class="btn btn-sm btn-info" onclick="verPedido(' . $r['id_pedido'] . ')" title="Ver Detalle"><i class="fas fa-eye"></i></button>';
        
        // Gestionar (Tomar) - Validar permisos
        $puedeGestionar = tienePermiso('pedidos', 'gestionar');
        if ($puedeGestionar && $r['estado'] !== 'Completado' && $r['estado'] !== 'Rechazado') {
            if (!$r['asignado_a']) {
                $botones[] = '<button class="btn btn-sm btn-primary" onclick="tomarPedido(' . $r['id_pedido'] . ')" title="Tomar Pedido"><i class="fas fa-hand-paper"></i></button>';
                
                // Boton Asignar (Solo Admin/SuperAdmin) - Usamos tienePermiso('pedidos', 'asignar') si existe, o hardcodeamos roles
                // Como 'asignar' no es un permiso estándar en todos lados, verificamos rol o permiso específico
                if (tieneRol([1, 2])) {
                    $botones[] = '<button class="btn btn-sm btn-outline-primary" onclick="abrirModalAsignar(' . $r['id_pedido'] . ')" title="Asignar a..."><i class="fas fa-user-plus"></i></button>';
                }
            } elseif ($r['asignado_a'] == $usuarioId) {
                // Si es mío y está pendiente/proceso, botón para completar
                $botones[] = '<button class="btn btn-sm btn-success" onclick="completarPedido(' . $r['id_pedido'] . ')" title="Completar"><i class="fas fa-check"></i></button>';
            }
        }
        
        // Eliminar (Solo Admin)
        $puedeEliminar = tienePermiso('pedidos', 'eliminar');
        if ($puedeEliminar) {
             $botones[] = '<button class="btn btn-sm btn-danger" onclick="eliminarPedido(' . $r['id_pedido'] . ')" title="Eliminar"><i class="fas fa-trash"></i></button>';
        }

        return [
            $r['id_pedido'],
            $solicitante,
            '<span class="badge bg-dark">' . $r['tipo'] . '</span>',
            $prioridad,
            $estado,
            date('d/m/Y H:i', strtotime($r['fecha_creacion'])),
            $asignado,
            '<div class="btn-group">' . implode('', $botones) . '</div>'
        ];
    }, $rows);

    json_response([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ]);

} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}
?>
