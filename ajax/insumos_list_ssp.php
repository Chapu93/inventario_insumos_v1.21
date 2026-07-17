<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

try {
    $db = conectarDB();

    // Parámetros DataTables
    $draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 0;
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $length = min(100, max(10, (int) ($_GET['length'] ?? 25))); // límite de seguridad
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Columnas disponibles para ordenar
    $columns = [
        0 => 'i.nombre_insumo',
        1 => 'i.tipo_insumo',
        2 => 'i.es_nuevo',
        3 => 'i.cantidad',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
    $orderBy = $columns[$orderColIdx] ?? 'i.nombre_insumo';

    // Filtros opcionales (tipo/localidad/estado) compatibles con UI actual
    $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $filtroLocalidad = isset($_GET['id_localidad']) ? trim($_GET['id_localidad']) : '';
    $filtroSede = isset($_GET['id_sede']) ? trim($_GET['id_sede']) : '';
    $filtroCondicion = isset($_GET['es_nuevo']) ? trim($_GET['es_nuevo']) : '';

    // Total sin filtros
    $total = (int) $db->query("SELECT COUNT(*) FROM insumos")->fetchColumn();

    // Construir WHERE
    $where = [];
    $params = [];
    if ($filtroTipo !== '') {
        $where[] = 'i.tipo_insumo = ?';
        $params[] = $filtroTipo;
    }
    if ($filtroEstado !== '') {
        $where[] = 'i.estado = ?';
        $params[] = $filtroEstado;
    }
    if ($filtroLocalidad !== '') {
        $where[] = 'l.id_localidad = ?';
        $params[] = $filtroLocalidad;
    }
    if ($filtroSede !== '') {
        $where[] = 'i.id_sede_actual = ?';
        $params[] = $filtroSede;
    }
    if ($filtroCondicion !== '') {
        $where[] = 'i.es_nuevo = ?';
        $params[] = $filtroCondicion;
    }
    $expandirAsignaciones = ($filtroEstado === 'Asignado');

    // Base de JOINs necesarios para toda consulta (incluso sin buscador textual)
    $baseJoins = "LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
                  LEFT JOIN localidades l ON s.id_localidad = l.id_localidad";

    // JOINs extras requeridos si estamos buscando por texto o si expandimos agrupacion
    $searchJoins = " LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
                     LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
                     LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
                     LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
                     LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
                     LEFT JOIN remitos_detalle rd ON rd.id_insumo = i.id_insumo 
                     LEFT JOIN remitos r ON r.id_remito = rd.id_remito AND r.estado = 'Activa'";

    if ($search !== '') {
        $where[] = '(
            i.nombre_insumo LIKE ? OR i.numero_serie LIKE ? OR REPLACE(i.id_fisico, \'-\', \'\') LIKE ? OR i.id_patrimonio LIKE ? OR
            pc.procesador LIKE ? OR pc.mother LIKE ? OR pc.sist_op LIKE ? OR
            nb.procesador LIKE ? OR nb.marca LIKE ? OR nb.modelo LIKE ? OR 
            imp.marca LIKE ? OR imp.modelo LIKE ? OR 
            mon.marca LIKE ? OR mon.modelo LIKE ? OR 
            esc.marca LIKE ? OR esc.modelo LIKE ? OR 
            i.subcategoria_varios LIKE ? OR
            s.nombre_sede LIKE ? OR
            l.nombre_localidad LIKE ? OR
            r.numero_remito LIKE ? OR
            CONCAT_WS(\' \', r.nombre_persona_asignada, r.apellido_persona_asignada) LIKE ?
        )';
        $like = '%' . $search . '%';
        $like_id_fisico = '%' . str_replace(['-', ' '], '', $search) . '%';
        array_push(
            $params,
            $like,
            $like,
            $like_id_fisico,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like, // subcategoria_varios
            $like, // nombre_sede
            $like, // nombre_localidad
            $like, // numero_remito
            $like  // asignado
        );
    }

    // Si estamos expandiendo (filtro Asignado), forzamos que el remito exista.
    // Esto previene que insumos con estado Asignado pero sin remito activo muestren basura (aunque no deberia pasar)
    if ($expandirAsignaciones) {
        $where[] = 'r.id_remito IS NOT NULL';
    }

    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $joinsForCount = ($search !== '' || $expandirAsignaciones) ? $baseJoins . $searchJoins : $baseJoins;
    $groupBy = $expandirAsignaciones ? "GROUP BY i.id_insumo, r.id_remito" : "GROUP BY i.id_insumo";

    // Total filtrado
    $countSql = "SELECT COUNT(*) FROM (
                    SELECT i.id_insumo
                    FROM insumos i
                    $joinsForCount
                    $whereSql
                    $groupBy
                 ) AS sub";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int) $stmt->fetchColumn();

    // Página de datos
    // Asegurarnos que agregamos los JOINS si es necesario para el GROUP BY o los campos
    $finalJoins = $baseJoins . $searchJoins;

    $dataSql = "SELECT i.id_insumo,
                       i.nombre_insumo,
                       i.tipo_insumo,
                       i.subcategoria_varios,
                       i.es_nuevo,
                       i.cantidad,
                       i.cantidad_oficina,
                       i.cantidad_deposito,
                       i.estado,
                       MAX(pc.sist_op) AS pc_sist_op,
                       MAX(nb.marca) AS nb_marca,
                       MAX(nb.modelo) AS nb_modelo,
                       MAX(imp.marca) AS imp_marca,
                       MAX(imp.modelo) AS imp_modelo,
                       MAX(mon.marca) AS mon_marca,
                       MAX(mon.modelo) AS mon_modelo,
                       MAX(esc.marca) AS esc_marca,
                       MAX(esc.modelo) AS esc_modelo,
                       MAX(r.numero_remito) AS numero_remito,
                       MAX(r.nombre_persona_asignada) AS nombre_persona_asignada,
                       MAX(r.apellido_persona_asignada) AS apellido_persona_asignada,
                       MAX(rd.cantidad) AS asignada_en_remito,
                       MAX(rd.cantidad_devuelta) AS devuelta_en_remito,
                       (SELECT COALESCE(SUM(rd_sub.cantidad - rd_sub.cantidad_devuelta), 0)
                        FROM remitos_detalle rd_sub
                        JOIN remitos r_sub ON r_sub.id_remito = rd_sub.id_remito
                        WHERE rd_sub.id_insumo = i.id_insumo AND r_sub.estado = 'Activa') AS cantidad_asignada
                FROM insumos i
                $finalJoins
                $whereSql
                $groupBy
                ORDER BY $orderBy $orderDir
                LIMIT $start, $length";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Mapear a columnas esperadas por la tabla actual
    global $filtroEstado;
    $data = array_map(function ($r) use ($filtroEstado) {
        $tipo = (string) $r['tipo_insumo'];
        $esPc = ($tipo === 'PC Escritorio' || $tipo === 'PC Completa');
        $originalName = (string) $r['nombre_insumo'];
        $displayName = $originalName;
        $extraLine = '';

        if ($esPc) {
            $sistOp = trim((string) ($r['pc_sist_op'] ?? ''));
            if ($sistOp !== '') {
                $displayName = 'CPU/' . $sistOp;
            }
        }

        if ($tipo !== 'Varios' && !$esPc) {
            $marca = '';
            $modelo = '';
            switch ($tipo) {
                case 'Notebook':
                    $marca = $r['nb_marca'] ?? '';
                    $modelo = $r['nb_modelo'] ?? '';
                    break;
                case 'Impresora':
                    $marca = $r['imp_marca'] ?? '';
                    $modelo = $r['imp_modelo'] ?? '';
                    break;
                case 'Monitor':
                    $marca = $r['mon_marca'] ?? '';
                    $modelo = $r['mon_modelo'] ?? '';
                    break;
                case 'Escaner':
                    $marca = $r['esc_marca'] ?? '';
                    $modelo = $r['esc_modelo'] ?? '';
                    break;
                default:
                    $marca = $r['nb_marca'] ?? $r['imp_marca'] ?? $r['mon_marca'] ?? $r['esc_marca'] ?? '';
                    $modelo = $r['nb_modelo'] ?? $r['imp_modelo'] ?? $r['mon_modelo'] ?? $r['esc_modelo'] ?? '';
                    break;
            }
            $marca = trim((string) $marca);
            $modelo = trim((string) $modelo);
            if ($marca !== '' || $modelo !== '') {
                $separator = ($marca !== '' && $modelo !== '') ? ' - ' : '';
                $displayName = trim($marca . $separator . $modelo);
            }
        }

        if ($displayName !== $originalName && $originalName !== '') {
            $extraLine = '<div class="text-muted small">' . htmlspecialchars($originalName) . '</div>';
        }

        $numeroRemito = $r['numero_remito'] ?? null;
        $personaAsig = trim(($r['nombre_persona_asignada'] ?? '') . ' ' . ($r['apellido_persona_asignada'] ?? ''));

        // Para tipo "Varios", mostrar la subcategoría en lugar del tipo
        $tipoDisplay = $tipo;
        if ($tipo === 'Varios' && !empty($r['subcategoria_varios'])) {
            $tipoDisplay = trim((string) $r['subcategoria_varios']);
        }
        $tipoBadge = '<span class="badge bg-info">' . htmlspecialchars($tipoDisplay) . '</span>';
        $esNuevo = isset($r['es_nuevo']) ? (int) $r['es_nuevo'] : 1;
        $condicionBadge = '<span class="badge ' . ($esNuevo ? 'bg-success' : 'bg-warning') . '">' . ($esNuevo ? 'Nuevo' : 'Usado') . '</span>';

        $cantBadge = '';
        if ($numeroRemito && $filtroEstado === 'Asignado') {
            // Mostrar info de asignacion para este remito particular sin total global
            $asignadaEnRemito = (int) ($r['asignada_en_remito'] ?? 0);
            $devueltaEnRemito = (int) ($r['devuelta_en_remito'] ?? 0);
            $pendientes = max(0, $asignadaEnRemito - $devueltaEnRemito);

            $cantBadge = '<div style="white-space: nowrap;">';
            $cantBadge .= '<span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="Asignados a ' . htmlspecialchars($personaAsig) . '"><i class="fas fa-user-check"></i> ' . $pendientes . '</span> ';
            $cantBadge .= '</div>';

            // Adjuntar extra info visual
            $extraLine .= '<div class="text-primary small mt-1"><i class="fas fa-user me-1"></i>' . htmlspecialchars($personaAsig) . ' (Rem: ' . htmlspecialchars($numeroRemito) . ')</div>';
        } else {
            // Stock display: clásico (Para "Disponibles" o "Todos")
            if ($tipo === 'Varios') {
                $cantOficina = (int) ($r['cantidad_oficina'] ?? $r['cantidad']);
                $cantDeposito = (int) ($r['cantidad_deposito'] ?? 0);
                $cantAsignada = (int) ($r['cantidad_asignada'] ?? 0);
                $cantTotal = $cantOficina + $cantDeposito;
                $cantBadge = '<div style="white-space: nowrap;">';
                $cantBadge .= '<span class="badge ' . ($cantOficina > 0 ? 'bg-success' : 'bg-secondary') . '" data-bs-toggle="tooltip" title="Stock Oficina"><i class="fas fa-building"></i> ' . $cantOficina . '</span> ';
                $cantBadge .= '<span class="badge ' . ($cantDeposito > 0 ? 'bg-primary' : 'bg-secondary') . '" data-bs-toggle="tooltip" title="Stock Depósito"><i class="fas fa-warehouse"></i> ' . $cantDeposito . '</span> ';
                if ($filtroEstado !== 'Disponible' && $cantAsignada > 0) {
                    $cantBadge .= '<span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="Asignados totales"><i class="fas fa-user-check"></i> ' . $cantAsignada . '</span> ';
                }
                $cantBadge .= '<span class="badge bg-info" data-bs-toggle="tooltip" title="Total Disponible (Ofic+Dep)"><i class="fas fa-boxes"></i> ' . $cantTotal . '</span>';
                $cantBadge .= '</div>';
            } else {
                $cantBadge = '<span class="badge ' . ((int) $r['cantidad'] > 0 ? 'bg-success' : 'bg-danger') . '">' . (int) $r['cantidad'] . '</span>';
            }
        }
        $nombreJs = json_encode((string) $r['nombre_insumo']);
        $isDeBaja = (strcasecmp(trim((string) $r['estado']), 'De Baja') === 0);

        // Verificar permisos para cada acción
        $puedeVer = tienePermiso('insumos', 'ver');
        $puedeEditar = tienePermiso('insumos', 'editar');
        $puedeBaja = tienePermiso('insumos', 'baja');
        $puedeEliminar = tienePermiso('insumos', 'eliminar');

        // Botón de reponer (solo para tipo Varios con stock en depósito y permiso editar, y si NO estamos viendo la asignacion desglosada)
        $btnReponer = '';
        if ($tipo === 'Varios' && $puedeEditar && !($numeroRemito && $filtroEstado === 'Asignado')) {
            $cantDeposito = (int) ($r['cantidad_deposito'] ?? 0);
            if ($cantDeposito > 0) {
                $btnReponer = '<button type="button" class="btn btn-sm btn-primary btn-reponer-stock" data-id="' . (int) $r['id_insumo'] . '" data-nombre="' . htmlspecialchars($displayName) . '" data-deposito="' . $cantDeposito . '" data-bs-toggle="tooltip" title="Reponer a Oficina"><i class="fas fa-exchange-alt"></i></button> ';
            }
        }

        // Construir botones solo si hay permisos
        $botones = [];

        if ($puedeVer) {
            $remitoParam = $numeroRemito ? ", '" . $numeroRemito . "'" : '';
            $botones[] = '<button type="button" class="btn btn-sm btn-info" aria-label="Ver detalles del insumo" onclick="verInsumo(' . (int) $r['id_insumo'] . $remitoParam . ')" data-bs-toggle="tooltip" title="Ver detalles"><i class="fas fa-eye" aria-hidden="true"></i></button>';
        }

        // Atajos Rápidos
        if ($numeroRemito && $filtroEstado === 'Asignado') {
            // Ir al remito devuelto
            $botones[] = '<a href="' . app_base_url() . '/pages/asignaciones/listar.php?remito=' . urlencode($numeroRemito) . '" class="btn btn-sm btn-success text-white" data-bs-toggle="tooltip" title="Ir al Remito"><i class="fas fa-file-invoice" aria-hidden="true"></i></a>';
        } else if (($filtroEstado === 'Disponible' || $filtroEstado === 'Todos') && !empty($cantBadge) && strpos($cantBadge, 'bg-danger') === false && (!isset($cantTotal) || $cantTotal > 0)) {
            // Asignación rápida (solo si parece tener stock)
            if ($puedeEditar && !$isDeBaja) {
                // Validación rápida: si no tiene cantidad disponible (0), evitamos mostrar el botón para asginación
                $stockValido = true;
                if ($tipo !== 'Varios' && (int) $r['cantidad'] <= 0)
                    $stockValido = false;
                if ($tipo === 'Varios' && ($cantOficina + $cantDeposito) <= 0)
                    $stockValido = false;

                if ($stockValido) {
                    $botones[] = '<a href="' . app_base_url() . '/pages/asignaciones/nueva_pasos.php?insumo_id=' . (int) $r['id_insumo'] . '" class="btn btn-sm btn-success text-white" data-bs-toggle="tooltip" title="Asignación rápida"><i class="fas fa-bolt" aria-hidden="true"></i></a>';
                }
            }
        }

        if ($puedeEditar) {
            $botones[] = '<a href="editar.php?id=' . (int) $r['id_insumo'] . '" class="btn btn-sm btn-warning" aria-label="Editar insumo" data-bs-toggle="tooltip" title="Editar"><i class="fas fa-edit" aria-hidden="true"></i></a>';
        }

        if ($puedeBaja) {
            $estadoClass = $isDeBaja ? 'disabled ' : '';
            $eventoOnclick = $isDeBaja ? '' : 'onclick=\'abrirModalBajaInsumo(' . (int) $r['id_insumo'] . ', ' . $nombreJs . ')\'';
            $botones[] = '<button type="button" class="btn btn-sm btn-soft-warning" aria-label="Dar de baja insumo" ' . $estadoClass . $eventoOnclick . ' data-bs-toggle="tooltip" title="Dar de baja"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>';
        }

        if ($puedeEliminar) {
            $botones[] = '<button type="button" class="btn btn-sm btn-danger btn-eliminar-insumo" aria-label="Eliminar insumo" data-id="' . (int) $r['id_insumo'] . '" data-bs-toggle="tooltip" title="Eliminar"><i class="fas fa-trash" aria-hidden="true"></i></button>';
        }

        $acciones = '<div class="btn-group" role="group">' . $btnReponer . implode(' ', $botones) . '</div>';
        return [
            '<strong>' . htmlspecialchars($displayName) . '</strong>' . $extraLine,
            $tipoBadge,
            $condicionBadge,
            $cantBadge,
            $acciones,
        ];
    }, $rows);

    Logger::debug('Lista de insumos cargada (SSP)', [
        'total' => $total,
        'filtered' => $filtered,
        'filters' => [
            'tipo' => $filtroTipo,
            'estado' => $filtroEstado
        ]
    ]);

    json_response([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ], 200);

} catch (Exception $e) {
    Logger::error('Error en lista de insumos (SSP)', [
        'mensaje' => $e->getMessage()
    ]);
    json_error($e->getMessage(), 500);
}
?>