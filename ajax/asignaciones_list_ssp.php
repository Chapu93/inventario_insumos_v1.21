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

    $filtro_localidad = $_GET['localidad'] ?? '';
    $filtro_insumo = $_GET['insumo'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_area = $_GET['area'] ?? '';
    $filtro_sede = $_GET['sede'] ?? '';
    $filtro_remito = isset($_GET['remito']) ? trim($_GET['remito']) : '';

    $colFecha = ($filtro_estado === 'Devuelta') ? 'r.fecha_devolucion' : 'r.fecha_asignacion';

    $columns = [
        0 => 'r.nombre_persona_asignada',
        1 => 'l.nombre_localidad',
        2 => $colFecha,
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $columns[$orderColIdx] ?? $colFecha;

    // Base subconsulta para contar activas
    $baseFrom = " FROM remitos r 
                   LEFT JOIN remitos_detalle d ON d.id_remito = r.id_remito
                   LEFT JOIN insumos i ON d.id_insumo = i.id_insumo
                   LEFT JOIN areas ar ON r.id_area = ar.id_area
                   JOIN sedes s ON r.id_sede = s.id_sede
                   JOIN localidades l ON s.id_localidad = l.id_localidad ";

    // WHERE - Manejo de anulados
    $where = [];
    if ($filtro_estado === 'Anulado') {
        $where[] = "r.estado = 'Anulado'";
    } elseif ($filtro_estado === 'Activa' || $filtro_estado === 'Devuelta') {
        // Para Activa y Devuelta, excluir anulados
        $where[] = "r.estado != 'Anulado'";
    }
    // Si filtro_estado está vacío (Todas), no se añade ninguna condición sobre estado
    
    $params = [];
    if ($filtro_localidad !== '') { $where[] = 'l.id_localidad = ?'; $params[] = $filtro_localidad; }
    if ($filtro_insumo !== '') { 
        $where[] = 'i.tipo_insumo = ?' . ($filtro_estado === 'Activa' ? ' AND (d.cantidad - COALESCE(d.cantidad_devuelta, 0)) > 0' : ''); 
        $params[] = $filtro_insumo; 
    }
    if ($filtro_area !== '') { $where[] = 'r.id_area = ?'; $params[] = $filtro_area; }
    if ($filtro_sede !== '') { $where[] = 'r.id_sede = ?'; $params[] = $filtro_sede; }
    if ($search !== '') {
        if ($filtro_estado === 'Activa') {
            $where[] = '(r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ? OR r.numero_remito LIKE ? OR ((i.numero_serie LIKE ? OR REPLACE(i.id_fisico, \'-\', \'\') LIKE ?) AND (d.cantidad - COALESCE(d.cantidad_devuelta, 0)) > 0))';
        } else {
            $where[] = '(r.nombre_persona_asignada LIKE ? OR r.apellido_persona_asignada LIKE ? OR r.numero_remito LIKE ? OR i.numero_serie LIKE ? OR REPLACE(i.id_fisico, \'-\', \'\') LIKE ?)';
        }
        $like = '%' . $search . '%';
        $like_id_fisico = '%' . str_replace(['-', ' '], '', $search) . '%';
        array_push($params, $like, $like, $like, $like, $like_id_fisico);
    }
    if ($filtro_remito !== '') { $where[] = 'r.numero_remito = ?'; $params[] = $filtro_remito; }
    $whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    // Total sin filtros (respetando universo según estado seleccionado)
    if ($filtro_estado === 'Anulado') {
        $sqlTotal = "SELECT COUNT(*) FROM remitos WHERE estado = 'Anulado'";
    } elseif ($filtro_estado === 'Activa' || $filtro_estado === 'Devuelta') {
        $sqlTotal = "SELECT COUNT(*) FROM remitos WHERE estado != 'Anulado'";
    } else {
        // Para "Todas", contar todos sin restricción de estado
        $sqlTotal = "SELECT COUNT(*) FROM remitos";
    }
    $total = (int)$db->query($sqlTotal)->fetchColumn();

    // Filtrado y agrupado por remito
    $sqlGroup = "SELECT r.id_remito, r.numero_remito,
                        r.fecha_asignacion,
                        r.fecha_devolucion,
                        r.nombre_persona_asignada,
                        r.apellido_persona_asignada,
                        ar.nombre_area,
                        s.nombre_sede,
                        l.nombre_localidad,
                        r.estado,
                        r.remito_firmado,
                        SUM(d.cantidad) AS cantidad_insumos,
                        SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0), 0)) AS activas,
                        SUM(COALESCE(d.cantidad_devuelta, 0)) AS total_devueltos,
                        (SELECT COUNT(*) FROM remitos_devolucion rd WHERE rd.id_remito_origen = r.id_remito) AS total_remitos_devolucion,
                        (SELECT COUNT(*) FROM remitos_devolucion rd WHERE rd.id_remito_origen = r.id_remito AND rd.remito_firmado IS NOT NULL AND rd.remito_firmado != '') AS total_remitos_dev_firmados,
                        (SELECT rd.numero_devolucion FROM remitos_devolucion rd WHERE rd.id_remito_origen = r.id_remito ORDER BY rd.id_remito_devolucion DESC LIMIT 1) AS ultimo_numero_devolucion,
                        (SELECT rd.id_remito_devolucion FROM remitos_devolucion rd WHERE rd.id_remito_origen = r.id_remito ORDER BY rd.id_remito_devolucion DESC LIMIT 1) AS ultimo_id_remito_devolucion,
                        (SELECT rd.remito_firmado FROM remitos_devolucion rd WHERE rd.id_remito_origen = r.id_remito ORDER BY rd.id_remito_devolucion DESC LIMIT 1) AS ultimo_remito_dev_firmado
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

    $data = array_map(function($r) use ($filtro_estado) {
        $activas = (int)$r['activas'];
        $estadoRemito = trim((string)$r['estado']);
        $idRemito = $r['id_remito'];
        $archivoFirmado = $r['remito_firmado'];
        $numRemitoEscapado = htmlspecialchars($r['numero_remito'], ENT_QUOTES);
        $totalDevueltos = (int)($r['total_devueltos'] ?? 0);
        $tieneRemitoDev = !empty($r['ultimo_numero_devolucion']);
        $esParcial = ($activas > 0 && ($totalDevueltos > 0 || $tieneRemitoDev));

        // Determinar el estado a mostrar
        if ($estadoRemito === 'Anulado') {
            $estado = 'Anulado';
            $estadoBadge = '<span class="badge estado-anulado">Anulado</span>';
        } elseif ($activas === 0) {
            $estado = 'Devuelta';
            $estadoBadge = '<span class="badge estado-devuelta">Devuelta</span>';
        } elseif ($esParcial) {
            $estado = 'Activa';
            $estadoBadge = '<span class="badge estado-parcial" data-bs-toggle="tooltip" title="Asignación con devoluciones parciales y equipos aún activos">Devolución Parcial</span>';
        } else {
            $estado = 'Activa';
            $estadoBadge = '<span class="badge estado-activa">Activa</span>';
        }

        // Verificar permisos para cada acción
        $puedeVer = tienePermiso('asignaciones', 'ver');
        $puedeImprimir = tienePermiso('asignaciones', 'ver'); // Same permission as ver
        $puedeSubirFirmado = tienePermiso('asignaciones', 'crear'); // Permiso para adjuntar
        $puedeDevolver = tienePermiso('asignaciones', 'devolver');
        $puedeEliminar = tienePermiso('asignaciones', 'anular');
        
        // Construir botones solo si hay permisos
        $botones = [];
        
        if ($puedeVer) {
            $botones[] = '<button type="button" class="btn btn-sm btn-info text-white" aria-label="Ver asignación" onclick="abrirVerAsignacion(\'' . $numRemitoEscapado . '\')" data-bs-toggle="tooltip" title="Ver detalle"><i class="fas fa-eye" aria-hidden="true"></i></button>';
        }
        
        $esDevuelta = ($estado === 'Devuelta' || $filtro_estado === 'Devuelta');

        if ($puedeImprimir) {
            if ($esDevuelta && $tieneRemitoDev) {
                $botones[] = '<button type="button" class="btn btn-sm btn-warning text-dark" aria-label="Imprimir remitos de devolución" onclick="generarRemitosDevolucionPorRemitoPDF(\'' . $numRemitoEscapado . '\', this)" data-bs-toggle="tooltip" title="Imprimir Remitos de Devolución"><i class="fas fa-file-invoice"></i></button>';
            } else {
                $botones[] = '<button type="button" class="btn btn-sm btn-primary" aria-label="Imprimir remito" onclick="generarRemitoPDF(\'' . $numRemitoEscapado . '\')" data-bs-toggle="tooltip" title="Imprimir Remito Entrega"><i class="fas fa-print" aria-hidden="true"></i></button>';
            }
        }

        // Botón de Remito Firmado
        if ($puedeSubirFirmado && $estadoRemito !== 'Anulado') {
            if ($esDevuelta && $tieneRemitoDev) {
                // En pestaña Devuelta con remito de devolución
                $totalDevs = (int)($r['total_remitos_devolucion'] ?? 0);
                $totalFirmados = (int)($r['total_remitos_dev_firmados'] ?? 0);

                if ($totalDevs > 1) {
                    // Múltiples entregas / devoluciones asociadas al remito
                    $btnClass = ($totalFirmados === $totalDevs) ? 'btn-soft-success' : (($totalFirmados > 0) ? 'btn-soft-warning' : 'btn-soft-primary');
                    $titleTooltip = 'Ver / Subir Comprobantes de Devolución (' . $totalFirmados . '/' . $totalDevs . ' firmados)';
                    $botones[] = '<button type="button" class="btn btn-sm ' . $btnClass . ' position-relative" aria-label="Gestionar comprobantes de devolución" onclick="abrirGestionComprobantesDevolucion(\'' . $numRemitoEscapado . '\')" data-bs-toggle="tooltip" title="' . htmlspecialchars($titleTooltip, ENT_QUOTES) . '"><i class="fas fa-file-signature"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark" style="font-size:0.6rem; padding: 0.2em 0.35em;">' . $totalDevs . '</span></button>';
                } else {
                    // 1 sola entrega
                    $numDevEscapado = htmlspecialchars($r['ultimo_numero_devolucion'], ENT_QUOTES);
                    $idDev = (int)$r['ultimo_id_remito_devolucion'];
                    $firmadoDev = $r['ultimo_remito_dev_firmado'];
                    if ($firmadoDev) {
                        $urlArchivoDev = app_base_url() . '/uploads/remitos_firmados/' . $firmadoDev;
                        $botones[] = '<a href="' . $urlArchivoDev . '" data-visor-archivo="' . $urlArchivoDev . '" data-visor-titulo="Remito Devolución Firmado ' . $numDevEscapado . '" target="_blank" class="btn btn-sm btn-soft-success" data-bs-toggle="tooltip" title="Ver Remito Devolución Firmado"><i class="fas fa-file-signature"></i></a>';
                    } else {
                        $botones[] = '<button type="button" class="btn btn-sm btn-soft-primary btn-subir-remito-dev" data-id-dev="' . $idDev . '" data-numero-dev="' . $numDevEscapado . '" data-has-file="0" data-bs-toggle="tooltip" title="Adjuntar remito devolución firmado"><i class="fas fa-upload"></i></button>';
                    }
                }
            } else {
                // En pestaña Activa (o parcial): gestionar remito de entrega
                if ($archivoFirmado) {
                    $urlArchivo = app_base_url() . '/uploads/remitos_firmados/' . $archivoFirmado;
                    $botones[] = '<a href="' . $urlArchivo . '" data-visor-archivo="' . $urlArchivo . '" data-visor-titulo="Remito Entrega Firmado ' . $numRemitoEscapado . '" target="_blank" class="btn btn-sm btn-soft-success" data-bs-toggle="tooltip" title="Ver Remito Entrega Firmado"><i class="fas fa-file-signature"></i></a>';
                } else {
                    $botones[] = '<button type="button" class="btn btn-sm btn-soft-primary btn-subir-remito" data-id="' . $idRemito . '" data-numero="' . $numRemitoEscapado . '" data-has-file="0" data-bs-toggle="tooltip" title="Adjuntar remito entrega firmado"><i class="fas fa-upload"></i></button>';
                }
            }
        }
        
        if ($puedeDevolver && $estado === 'Activa') {
            $btnDevAttrs = $activas > 0
                ? 'type="button" class="btn btn-sm btn-warning" aria-label="Devolver insumos" onclick="abrirDevolucion(\'' . $numRemitoEscapado . '\')" data-bs-toggle="tooltip" title="Devolver insumos pendientes"'
                : 'type="button" class="btn btn-sm btn-warning" aria-label="Devolver insumos" disabled data-bs-toggle="tooltip" title="Sin ítems para devolver"';
            $botones[] = '<button ' . $btnDevAttrs . '><i class="fas fa-undo" aria-hidden="true"></i></button>';
        }
        
        if ($puedeEliminar && $estado === 'Activa' && !$esParcial && !$tieneRemitoDev) {
            $botones[] = '<button type="button" class="btn btn-sm btn-danger" aria-label="Anular remito" onclick="eliminarAsignacion(\'' . $numRemitoEscapado . '\')" data-bs-toggle="tooltip" title="Anular remito"><i class="fas fa-trash" aria-hidden="true"></i></button>';
        }
        
        $esDevuelta = ($estado === 'Devuelta' || $filtro_estado === 'Devuelta');
        $rawFecha = ($esDevuelta && !empty($r['fecha_devolucion'])) ? $r['fecha_devolucion'] : $r['fecha_asignacion'];
        $fechaMostrar = $rawFecha ? date('d/m/Y', strtotime($rawFecha)) : '-';

        $acciones = '<div class="btn-group" role="group">' . implode(' ', $botones) . '</div>';
        return [
            htmlspecialchars($r['nombre_persona_asignada'] . ' ' . $r['apellido_persona_asignada']),
            htmlspecialchars($r['nombre_localidad']),
            $fechaMostrar,
            $estadoBadge,
            $acciones,
        ];
    }, $rows);

    Logger::debug('Lista de asignaciones cargada (SSP)', [
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
    Logger::error('Error en lista de asignaciones (SSP)', [
        'mensaje' => $e->getMessage()
    ]);
    json_error($e->getMessage(), 500);
}
?>

