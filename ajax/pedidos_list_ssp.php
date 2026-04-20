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
        6 => 'u_asig.username',
        7 => 'p.estado_entrega'
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 5; // Default fecha
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'p.fecha_creacion';

    // Filtros
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_tipo = $_GET['tipo'] ?? '';
    $filtro_prioridad = $_GET['prioridad'] ?? '';
    $filtro_sede = $_GET['sede'] ?? '';
    $filtro_logistica = $_GET['logistica'] ?? '';
    $modo = $_GET['modo'] ?? 'mis_pedidos'; // 'mis_pedidos' o 'pendientes'

    // Base query
    $baseFrom = " FROM pedidos p
                  JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                  JOIN sedes s ON p.id_sede = s.id_sede
                  LEFT JOIN usuarios u_asig ON p.asignado_a = u_asig.id_usuario 
                  LEFT JOIN areas a ON p.id_area = a.id_area
                  LEFT JOIN remitos r ON p.id_remito = r.id_remito ";

    $where = ["1=1"];
    $params = [];

    // Lógica de Modos y Permisos
    if ($modo === 'mis_pedidos') {
        // Mis Tareas = Solo pedidos asignados a mí (o que tomé) que no estén finalizados del todo
        $where[] = "p.asignado_a = ?";
        $params[] = $usuarioId;
        $where[] = "p.estado NOT IN ('Completado', 'Preparado', 'Rechazado')";
    } elseif ($modo === 'pendientes') {
        // Modo pendientes (para técnicos/admins) - Solo tipos técnicos
        if (!tienePermiso('pedidos', 'ver_todos') && !tienePermiso('pedidos', 'gestionar')) {
             json_error('No tienes permisos para ver pendientes globales', 403);
        }
        $where[] = "p.tipo IN ('Mantenimiento', 'Reparación', 'Soporte')";
        $where[] = "p.estado IN ('Pendiente', 'En Proceso')";
        $where[] = "(p.asignado_a IS NULL OR p.asignado_a = 0)"; // Solo los sin asignar — los asignados están en "Mis Tareas"

    } elseif ($modo === 'pedidos_insumos') {
        // Solo pedidos de insumos que están siendo preparados
        if (!tienePermiso('pedidos', 'ver_todos')) {
            json_error('No tienes permisos', 403);
        }
        $where[] = "p.tipo = 'Pedido Insumo'";
        $where[] = "p.estado = 'Pendiente'";

    } elseif ($modo === 'logistica') {
        // Gestión de Entregas: Insumos preparados o Técnicos completados
        if (!tienePermiso('pedidos', 'ver_todos')) {
            json_error('No tienes permisos', 403);
        }
        $where[] = "((p.tipo = 'Pedido Insumo' AND p.estado = 'Preparado') OR (p.tipo != 'Pedido Insumo' AND p.estado = 'Completado'))";
        $where[] = "p.estado_entrega != 'Entregado'";

    } elseif ($modo === 'todos') {
         // Historial completo
         if (!tienePermiso('pedidos', 'ver_todos')) {
            json_error('No tienes permisos', 403);
         }
    }

    // Filtros específicos
    if ($filtro_estado !== '') { $where[] = 'p.estado = ?'; $params[] = $filtro_estado; }
    if ($filtro_tipo !== '') { $where[] = 'p.tipo = ?'; $params[] = $filtro_tipo; }
    if ($filtro_prioridad !== '') { $where[] = 'p.prioridad = ?'; $params[] = $filtro_prioridad; }
    if ($filtro_sede !== '') { $where[] = 'p.id_sede = ?'; $params[] = $filtro_sede; }
    if ($filtro_logistica !== '') { $where[] = 'p.estado_entrega = ?'; $params[] = $filtro_logistica; }

    if ($search !== '') {
        $where[] = '(p.solicitante_nombre LIKE ? OR p.solicitante_apellido LIKE ? OR p.descripcion LIKE ? OR s.nombre_sede LIKE ? OR p.id_pedido LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
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
                       s.nombre_sede, a.nombre_area, r.numero_remito
                $baseFrom $whereSql 
                ORDER BY $orderBy $orderDir 
                LIMIT $start, $length";
    
    $stmt = $db->prepare($pageSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Calcular permisos UNA SOLA VEZ (evitar N llamadas dentro del loop)
    $permGestionar = tienePermiso('pedidos', 'gestionar');
    $permEliminar  = tienePermiso('pedidos', 'eliminar');
    $permVerTodos  = tienePermiso('pedidos', 'ver_todos');
    $esAdmin       = tieneRol([1, 2]);

    $data = array_map(function($r) use ($usuarioId, $permGestionar, $permEliminar, $permVerTodos, $esAdmin, $modo) {
        // Formatear datos
        $solicitante = htmlspecialchars($r['solicitante_nombre'] . ' ' . $r['solicitante_apellido']) . '<br><small class="text-muted">' . htmlspecialchars($r['nombre_sede']) . '</small>';
        
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

        // Acciones
        $botones = [];
        $botones[] = '<button class="btn btn-sm btn-info" onclick="verPedido(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Ver Detalle"><i class="fas fa-eye"></i></button>';

        if ($permGestionar && $r['estado'] !== 'Completado' && $r['estado'] !== 'Rechazado') {
            if (!$r['asignado_a'] && $r['tipo'] !== 'Pedido Insumo') {
                $botones[] = '<button class="btn btn-sm btn-primary" onclick="tomarPedido(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Tomar Pedido"><i class="fas fa-hand-paper"></i></button>';
                if ($esAdmin) {
                    $botones[] = '<button class="btn btn-sm btn-outline-primary" onclick="abrirModalAsignar(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Asignar a Tecnico..."><i class="fas fa-user-plus"></i></button>';
                }
            } elseif ($r['asignado_a'] == $usuarioId && $r['tipo'] !== 'Pedido Insumo') {
                $botones[] = '<button class="btn btn-sm btn-success" onclick="completarPedido(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Finalizar e Informar"><i class="fas fa-check"></i></button>';
            }
            if ($r['tipo'] === 'Pedido Insumo' && $r['estado'] === 'Pendiente') {
                $botones[] = '<button class="btn btn-sm btn-success" onclick="prepararPedido(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Preparar Insumos y Remito"><i class="fas fa-box-open"></i></button>';
            }
        }
        
        // Botón imprimir remito
        if ($r['tipo'] === 'Pedido Insumo' && !empty($r['numero_remito'])) {
            if ($permGestionar || $permVerTodos) {
                $botones[] = '<button class="btn btn-sm btn-primary" onclick="imprimirRemito(\'' . $r['numero_remito'] . '\')" data-bs-toggle="tooltip" title="Imprimir Remito"><i class="fas fa-print" aria-hidden="true"></i></button>';
            }
        }

        // Botones de logística (solo en tab En Tránsito)
        if ($modo === 'logistica' && ($permGestionar || $permVerTodos)) {
            $estadoEnt = $r['estado_entrega'] ?? '';
            if ($estadoEnt !== 'Enviado' && $estadoEnt !== 'Entregado') {
                $icon  = ($r['metodo_entrega'] === 'Retiro') ? 'fa-hand-holding' : 'fa-truck';
                $label = ($r['metodo_entrega'] === 'Retiro') ? 'Listo para Retiro' : 'Marcar como Enviado';
                $botones[] = '<button class="btn btn-sm btn-warning text-dark" onclick="actualizarLogistica(' . $r['id_pedido'] . ', \'' . $estadoEnt . '\', \'' . $r['metodo_entrega'] . '\')" data-bs-toggle="tooltip" title="' . $label . '"><i class="fas ' . $icon . '"></i></button>';
            }
            if ($estadoEnt !== 'Entregado') {
                $botones[] = '<button class="btn btn-sm btn-success" onclick="abrirModalEntregarLista(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Confirmar Entrega Final"><i class="fas fa-check-double"></i></button>';
            }
        }

        // Eliminar (Solo Admin)
        if ($permEliminar) {
            $botones[] = '<button class="btn btn-sm btn-danger" onclick="eliminarPedido(' . $r['id_pedido'] . ')" data-bs-toggle="tooltip" title="Eliminar definitivamente"><i class="fas fa-trash"></i></button>';
        }

        $entregaHtml = '-';
        if ($r['tipo'] === 'Pedido Insumo' || $r['estado'] === 'Completado' || $r['estado'] === 'Preparado') {
             $entregaCls = match($r['estado_entrega']) {
                 'Pendiente' => 'secondary',
                 'Preparado' => 'info text-dark',
                 'Enviado' => 'primary',
                 'Entregado' => 'success',
                 default => 'secondary'
             };
             $entregaHtml = '<span class="badge bg-' . $entregaCls . '">' . $r['estado_entrega'] . '</span>';
             if ($r['metodo_entrega'] !== 'No aplica') {
                 $entregaHtml .= '<br><small class="text-muted">' . $r['metodo_entrega'] . '</small>';
             }
        }

        return [
            $r['id_pedido'],
            $solicitante,
            '<span class="badge bg-dark">' . $r['tipo'] . '</span>',
            $prioridad,
            $estado,
            date('d/m/Y H:i', strtotime($r['fecha_creacion'])),
            $asignado,
            $entregaHtml,
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
