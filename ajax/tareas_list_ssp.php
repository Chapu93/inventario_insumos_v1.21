<?php
// Server Side Processing para Tareas Internas
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    json_error('Sin permiso para ver tareas', 403);
}

try {
    $db        = conectarDB();
    $usuarioId = obtenerUsuarioId();

    $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 0;
    $start  = max(0, (int)($_GET['start']  ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $modo   = $_GET['modo'] ?? 'activas'; // 'activas' | 'completadas'
    $filtro_asignado = $_GET['asignado_a'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';

    // Columnas ordenables
    $columns = [
        0 => 't.id_tarea',
        1 => 't.titulo',
        2 => 't.estado',
        3 => 'uc.apellido',
        4 => 'ua.apellido',
        5 => 't.fecha_creacion',
        6 => 't.fecha_finalizacion',
    ];

    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 5;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
    $orderBy     = $columns[$orderColIdx] ?? 't.fecha_creacion';

    $baseFrom = " FROM tareas_internas t
                  JOIN usuarios uc ON t.creado_por = uc.id_usuario
                  LEFT JOIN usuarios ua ON t.asignado_a = ua.id_usuario ";

    $where  = ['1=1'];
    $params = [];

    if ($modo === 'activas') {
        $where[] = "t.estado IN ('Pendiente', 'En Proceso')";
        $where[] = "(t.asignado_a IS NULL OR t.asignado_a = 0)";
    } elseif ($modo === 'mis_tareas') {
        $where[] = "t.asignado_a = ?";
        $params[] = $usuarioId;
        $where[] = "t.estado IN ('Pendiente', 'En Proceso')";
    } elseif ($modo === 'completadas') {
        $where[] = "t.estado = 'Completada'";
    } elseif ($modo === 'historial') {
        // En el historial de tareas internas mostramos todo sin restricción base
    }
    
    if ($filtro_asignado !== '') {
        $where[] = 't.asignado_a = ?';
        $params[] = (int)$filtro_asignado;
    }

    if ($filtro_estado !== '') {
        $estado_busqueda = ($filtro_estado === 'Completado') ? 'Completada' : $filtro_estado;
        $where[] = 't.estado = ?';
        $params[] = $estado_busqueda;
    }

    if ($search !== '') {
        $where[] = "(t.titulo LIKE ? OR uc.nombre LIKE ? OR uc.apellido LIKE ? OR ua.nombre LIKE ? OR ua.apellido LIKE ? OR ua.username LIKE ?)";
        $like    = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // Total filtrado
    $stmtCount = $db->prepare("SELECT COUNT(*) $baseFrom $whereSql");
    $stmtCount->execute($params);
    $filtered = (int)$stmtCount->fetchColumn();

    // Datos paginados
    $pageSql = "SELECT t.*,
                       uc.nombre as creador_nombre, uc.apellido as creador_apellido, uc.username as creador_user,
                       ua.nombre as asig_nombre,   ua.apellido as asig_apellido,   ua.username as asig_user
                $baseFrom $whereSql
                ORDER BY $orderBy $orderDir
                LIMIT $start, $length";

    $stmt = $db->prepare($pageSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Calcular permisos una vez
    $permGestionar = tienePermiso('pedidos', 'gestionar');
    $permEliminar  = tienePermiso('pedidos', 'eliminar');
    $esAdmin       = tieneRol([1, 2]);

    $data = array_map(function($r) use ($usuarioId, $permGestionar, $permEliminar, $esAdmin) {

        // Estado
        $estadoClass = match($r['estado']) {
            'Pendiente'  => 'warning text-dark',
            'En Proceso' => 'primary',
            'Completada' => 'success',
            default      => 'secondary'
        };
        $estadoHtml = '<span class="badge bg-' . $estadoClass . '">' . $r['estado'] . '</span>';

        // Asignado
        if ($r['es_colaborativa']) {
            $asignadoHtml = '<span class="badge" style="background-color: #6f42c1; color: white;"><i class="fas fa-users me-1"></i>Colaborativa</span>';
        } else {
            $asignadoHtml = $r['asignado_a']
                ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['asig_nombre'] . ' ' . $r['asig_apellido']) . '</span>'
                : '<span class="badge bg-secondary">Sin Asignar</span>';
        }

        // Descripción truncada
        $descCorta = htmlspecialchars(mb_substr($r['descripcion'], 0, 90));
        if (mb_strlen($r['descripcion']) > 90) $descCorta .= '…';

        // Botones
        $botones = [];
        $botones[] = '<button class="btn btn-sm btn-info" onclick="verTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Ver detalle"><i class="fas fa-eye"></i></button>';

        if ($r['estado'] !== 'Completada' && $permGestionar) {
            $botones[] = '<button class="btn btn-sm btn-warning text-dark" onclick="editarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Editar tarea"><i class="fas fa-edit"></i></button>';
        }

        if ($r['estado'] !== 'Completada') {
            if ($permGestionar) {
                if ($r['es_colaborativa']) {
                    // Tarea colaborativa: cualquier gestor/técnico/operador puede completarla
                    $botones[] = '<button class="btn btn-sm btn-success" onclick="completarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Marcar completada"><i class="fas fa-check"></i></button>';
                } else {
                    if (!$r['asignado_a']) {
                        // Sin asignar: cualquier gestor puede tomar
                        $botones[] = '<button class="btn btn-sm btn-primary" onclick="tomarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Tomar tarea"><i class="fas fa-hand-paper"></i></button>';
                        if ($esAdmin) {
                            $botones[] = '<button class="btn btn-sm btn-outline-primary" onclick="abrirModalAsignarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Asignar a..."><i class="fas fa-user-plus"></i></button>';
                        }
                    } elseif ($r['asignado_a'] == $usuarioId || $esAdmin) {
                        // Asignado al usuario actual (o admin): puede completar
                        $botones[] = '<button class="btn btn-sm btn-success" onclick="completarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Marcar completada"><i class="fas fa-check"></i></button>';
                        if ($esAdmin) {
                            $botones[] = '<button class="btn btn-sm btn-warning text-dark" onclick="liberarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Devolver a pendiente"><i class="fas fa-undo"></i></button>';
                        }
                    }
                }
            }
        }

        if ($permEliminar) {
            $botones[] = '<button class="btn btn-sm btn-danger" onclick="eliminarTarea(' . $r['id_tarea'] . ')" data-bs-toggle="tooltip" title="Eliminar tarea"><i class="fas fa-trash"></i></button>';
        }

        $fechaFin = $r['fecha_finalizacion']
            ? date('d/m/Y H:i', strtotime($r['fecha_finalizacion']))
            : '-';

        return [
            '#' . $r['id_tarea'],
            '<strong>' . htmlspecialchars($r['titulo']) . '</strong>',
            $estadoHtml,
            htmlspecialchars($r['creador_nombre'] . ' ' . $r['creador_apellido']),
            $asignadoHtml,
            date('d/m/Y H:i', strtotime($r['fecha_creacion'])),
            $fechaFin,
            '<div class="btn-group">' . implode('', $botones) . '</div>',
        ];
    }, $rows);

    json_response([
        'draw'            => $draw,
        'recordsTotal'    => $filtered,
        'recordsFiltered' => $filtered,
        'data'            => $data,
    ]);

} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}
