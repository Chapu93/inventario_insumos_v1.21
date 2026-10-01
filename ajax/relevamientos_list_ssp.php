<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver relevamientos', 403);
}

try {
    $db = conectarDB();

    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Filtros específicos
    $id_localidad = isset($_GET['id_localidad']) ? (int)$_GET['id_localidad'] : 0;
    $id_sede = isset($_GET['id_sede']) ? (int)$_GET['id_sede'] : 0;
    $fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

    // Mapeo de columnas para ordenamiento
    $columns = [
        0 => 'r.numero_remito',
        1 => 'r.fecha_asignacion',
        2 => 's.nombre_sede',
        3 => 'r.nombre_persona_asignada',
        4 => 'u.username',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? 'r.fecha_asignacion';

    // Construcción de condiciones WHERE
    $where = [];
    $params = [];

    // Base: Relevamientos históricos no anulados
    $where[] = '(r.numero_remito LIKE ? OR r.observaciones LIKE ? OR aud.entidad_id IS NOT NULL)';
    $params[] = '%_hist';
    $params[] = '%Relevamiento%';

    $where[] = "r.estado != 'Anulado'";

    // Filtros de ubicación
    if ($id_localidad > 0) {
        $where[] = 'l.id_localidad = ?';
        $params[] = $id_localidad;
    }
    if ($id_sede > 0) {
        $where[] = 's.id_sede = ?';
        $params[] = $id_sede;
    }

    // Filtro de fechas
    if ($fecha_desde !== '') {
        $where[] = 'r.fecha_asignacion >= ?';
        $params[] = $fecha_desde;
    }
    if ($fecha_hasta !== '') {
        $where[] = 'r.fecha_asignacion <= ?';
        $params[] = $fecha_hasta;
    }

    // Búsqueda global de DataTables
    if ($search !== '') {
        $where[] = '(r.numero_remito LIKE ? 
                    OR r.nombre_persona_asignada LIKE ? 
                    OR r.apellido_persona_asignada LIKE ? 
                    OR s.nombre_sede LIKE ? 
                    OR l.nombre_localidad LIKE ? 
                    OR a.nombre_area LIKE ?
                    OR u.username LIKE ?
                    OR u.nombre LIKE ?
                    OR u.apellido LIKE ?
                    OR r.observaciones LIKE ?)';
        $like = '%' . $search . '%';
        for ($k = 0; $k < 10; $k++) {
            $params[] = $like;
        }
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // Estructura FROM y JOINs compatible con ONLY_FULL_GROUP_BY (MySQL 5.7+ / MariaDB)
    $sqlFrom = "FROM remitos r
    JOIN sedes s ON r.id_sede = s.id_sede
    JOIN localidades l ON s.id_localidad = l.id_localidad
    LEFT JOIN zonas z ON l.id_zona = z.id_zona
    LEFT JOIN areas a ON r.id_area = a.id_area
    LEFT JOIN (
        SELECT entidad_id, MAX(id_usuario) AS id_usuario
        FROM auditoria_acciones
        WHERE accion = 'relevamiento_carga_masiva'
        GROUP BY entidad_id
    ) aud ON aud.entidad_id = r.id_remito
    LEFT JOIN usuarios u ON aud.id_usuario = u.id_usuario";

    // Contar total filtrado (sin necesidad de tabla temporal derivada)
    $countSql = "SELECT COUNT(*) $sqlFrom $whereSql";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $filtered = (int)$stmtCount->fetchColumn();

    // Contar total absoluto de relevamientos
    $totalSql = "SELECT COUNT(DISTINCT r.id_remito) 
                 FROM remitos r 
                 LEFT JOIN auditoria_acciones aud ON (aud.entidad_id = r.id_remito AND aud.accion = 'relevamiento_carga_masiva')
                 WHERE (r.numero_remito LIKE '%_hist' OR r.observaciones LIKE '%Relevamiento%' OR aud.id_auditoria IS NOT NULL) 
                   AND r.estado != 'Anulado'";
    $total = (int)$db->query($totalSql)->fetchColumn();

    // Obtener datos paginados
    $selectCols = "SELECT 
        r.id_remito,
        r.numero_remito,
        r.fecha_asignacion,
        r.nombre_persona_asignada,
        r.apellido_persona_asignada,
        r.observaciones,
        r.remito_firmado,
        s.id_sede,
        s.nombre_sede,
        l.id_localidad,
        l.nombre_localidad,
        z.nombre_zona,
        a.nombre_area,
        u.username AS cargado_por_username,
        u.nombre AS cargado_por_nombre,
        u.apellido AS cargado_por_apellido";

    $dataSql = "$selectCols $sqlFrom $whereSql ORDER BY $orderBy $orderDir, r.id_remito DESC LIMIT $start, $length";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $puedeVerDetalle = tienePermiso('insumos', 'ver');
    $puedeVerRemito = tienePermiso('asignaciones', 'ver');
    $puedeSubirFirmado = tienePermiso('asignaciones', 'crear');

    $data = array_map(function($r) use ($puedeVerDetalle, $puedeVerRemito, $puedeSubirFirmado) {
        $idRemito = (int)$r['id_remito'];
        $numRemito = htmlspecialchars($r['numero_remito'], ENT_QUOTES);

        // 1. Columna Remito Histórico
        $colRemito = '<span class="badge bg-secondary font-monospace"><i class="fas fa-history me-1"></i>' . $numRemito . '</span>';

        // 2. Columna Fecha
        $colFecha = $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '-';

        // 3. Columna Sede / Localidad / Zona
        $zonaBadge = !empty($r['nombre_zona']) ? ' <span class="badge bg-info text-dark ms-1">' . htmlspecialchars($r['nombre_zona']) . '</span>' : '';
        $areaText = !empty($r['nombre_area']) ? '<div class="small text-muted">' . htmlspecialchars($r['nombre_area']) . '</div>' : '';
        $colSede = '<div>' . htmlspecialchars($r['nombre_sede']) . '</div>'
                 . '<div class="small text-muted">' . htmlspecialchars($r['nombre_localidad']) . $zonaBadge . '</div>'
                 . $areaText;

        // 4. Columna Responsable
        $respNombre = trim($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']);
        $colResp = '<div class="text-truncate" style="max-width: 180px;" title="' . htmlspecialchars($respNombre) . '">'
                 . htmlspecialchars($respNombre ?: 'Sin asignar') . '</div>';

        // 5. Columna Cargado Por (Técnico)
        if (!empty($r['cargado_por_username'])) {
            $colTecnico = '<span class="badge bg-secondary">' . htmlspecialchars($r['cargado_por_username']) . '</span>';
        } else {
            $colTecnico = '<span class="text-muted small">Carga inicial</span>';
        }

        // 6. Columna Acciones
        $botones = [];
        
        // Ver Detalle de Insumos (Modal)
        if ($puedeVerDetalle) {
            $botones[] = '<button type="button" class="btn btn-sm btn-info text-white btn-ver-detalle-relevamiento" '
                       . 'data-id="' . $idRemito . '" data-numero="' . $numRemito . '" '
                       . 'data-bs-toggle="tooltip" title="Ver detalle">'
                       . '<i class="fas fa-eye"></i></button>';
        }

        // Ver Remito Histórico PDF
        if ($puedeVerRemito) {
            $botones[] = '<button type="button" class="btn btn-sm btn-primary" '
                       . 'onclick="generarRemitoPDF(\'' . $numRemito . '\')" '
                       . 'data-bs-toggle="tooltip" title="Ver Remito PDF">'
                       . '<i class="fas fa-file-pdf"></i></button>';
        }

        // Remito Firmado
        if ($puedeSubirFirmado) {
            $archivoFirmado = $r['remito_firmado'];
            if ($archivoFirmado) {
                $urlArchivo = app_base_url() . '/uploads/remitos_firmados/' . $archivoFirmado;
                $botones[] = '<a href="' . $urlArchivo . '" data-visor-archivo="' . $urlArchivo . '" '
                           . 'data-visor-titulo="Remito Firmado ' . $numRemito . '" target="_blank" '
                           . 'class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Ver Remito Firmado">'
                           . '<i class="fas fa-file-signature"></i></a>';
            }
        }

        $colAcciones = '<div class="btn-group" role="group">' . implode(' ', $botones) . '</div>';

        return [
            $colRemito,
            $colFecha,
            $colSede,
            $colResp,
            $colTecnico,
            $colAcciones
        ];
    }, $rows);

    json_response([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data
    ]);

} catch (Exception $e) {
    Logger::error('Error en ajax/relevamientos_list_ssp.php', [
        'mensaje' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    json_response([
        'draw' => $draw ?? 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar la lista de relevamientos: ' . $e->getMessage()
    ], 500);
}
