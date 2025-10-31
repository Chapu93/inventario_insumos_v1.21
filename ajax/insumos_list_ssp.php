<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = conectarDB();

    // Parámetros DataTables
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25))); // límite de seguridad
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Columnas disponibles para ordenar
    $columns = [
        0 => 'i.nombre_insumo',
        1 => 'i.tipo_insumo',
        2 => 'i.es_nuevo',
        3 => 'i.cantidad',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
    $orderBy = $columns[$orderColIdx] ?? 'i.nombre_insumo';

    // Filtros opcionales (tipo/localidad/estado) compatibles con UI actual
    $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    // Se removió filtro de localidad desde la UI
    $filtroLocalidad = '';
    $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

    // Total sin filtros
    $total = (int)$db->query("SELECT COUNT(*) FROM insumos")->fetchColumn();

    // Construir WHERE
    $where = [];
    $params = [];
    if ($filtroTipo !== '') { $where[] = 'i.tipo_insumo = ?'; $params[] = $filtroTipo; }
    // Sin filtro de localidad
    if ($filtroEstado !== '') { $where[] = 'i.estado = ?'; $params[] = $filtroEstado; }
    if ($search !== '') {
        $where[] = '(i.nombre_insumo LIKE ? OR i.numero_serie LIKE ? OR i.id_fisico LIKE ? OR i.id_patrimonio LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total filtrado
    $countSql = "SELECT COUNT(*)
                 FROM insumos i
                 LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
                 LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    // Página de datos
    $dataSql = "SELECT i.id_insumo,
                       i.nombre_insumo,
                       i.tipo_insumo,
                       i.es_nuevo,
                       i.cantidad,
                       i.estado,
                       nb.marca AS nb_marca,
                       nb.modelo AS nb_modelo,
                       imp.marca AS imp_marca,
                       imp.modelo AS imp_modelo,
                       mon.marca AS mon_marca,
                       mon.modelo AS mon_modelo,
                       esc.marca AS esc_marca,
                       esc.modelo AS esc_modelo
                FROM insumos i
                LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
                LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
                LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
                LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
                LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
                LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
                $whereSql
                ORDER BY $orderBy $orderDir
                LIMIT $start, $length";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Mapear a columnas esperadas por la tabla actual
    $data = array_map(function($r){
        $tipo = (string)$r['tipo_insumo'];
        $displayName = (string)$r['nombre_insumo'];

        if ($tipo !== 'Varios' && $tipo !== 'PC Escritorio') {
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
            $marca = trim((string)$marca);
            $modelo = trim((string)$modelo);
            if ($marca !== '' || $modelo !== '') {
                $separator = ($marca !== '' && $modelo !== '') ? ' - ' : '';
                $displayName = trim($marca . $separator . $modelo);
            }
        }

        $tipoBadge = '<span class="badge bg-info">' . htmlspecialchars($tipo) . '</span>';
        $esNuevo = isset($r['es_nuevo']) ? (int)$r['es_nuevo'] : 1;
        $condicionBadge = '<span class="badge ' . ($esNuevo ? 'bg-success' : 'bg-warning') . '">' . ($esNuevo ? 'Nuevo' : 'Usado') . '</span>';
        $cantBadge = '<span class="badge ' . ((int)$r['cantidad'] > 0 ? 'bg-success' : 'bg-danger') . '">' . (int)$r['cantidad'] . '</span>';
        $nombreJs = json_encode((string)$r['nombre_insumo']);
        $isDeBaja = (strcasecmp(trim((string)$r['estado']), 'De Baja') === 0);
        $acciones = '<div class="btn-group" role="group">'
                  . '<button type="button" class="btn btn-sm btn-info" aria-label="Ver detalles del insumo" onclick="verInsumo(' . (int)$r['id_insumo'] . ')" data-bs-toggle="tooltip" title="Ver detalles"><i class="fas fa-eye" aria-hidden="true"></i></button>'
                  . ' <a href="editar.php?id=' . (int)$r['id_insumo'] . '" class="btn btn-sm btn-warning" aria-label="Editar insumo" data-bs-toggle="tooltip" title="Editar"><i class="fas fa-edit" aria-hidden="true"></i></a>'
                  . ' <button type="button" class="btn btn-sm btn-outline-warning" aria-label="Dar de baja insumo" ' . ($isDeBaja ? 'disabled ' : 'onclick=\'abrirModalBajaInsumo(' . (int)$r['id_insumo'] . ', ' . $nombreJs . ')\' ') . 'data-bs-toggle="tooltip" title="Dar de baja"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>'
                  . ' <button type="button" class="btn btn-sm btn-danger btn-eliminar-insumo" aria-label="Eliminar insumo" data-id="' . (int)$r['id_insumo'] . '" data-bs-toggle="tooltip" title="Eliminar"><i class="fas fa-trash" aria-hidden="true"></i></button>'
                  . '</div>';
        return [
            '<strong>' . htmlspecialchars($displayName) . '</strong>',
            $tipoBadge,
            $condicionBadge,
            $cantBadge,
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
