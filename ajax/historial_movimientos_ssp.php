<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = conectarDB();

    // Parámetros DataTables
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Columnas disponibles para ordenar
    $columns = [
        0 => 'm.fecha_movimiento',
        1 => 'i.nombre_insumo',
        2 => 'm.tipo_movimiento',
        3 => 'm.cantidad_movida',
    ];
    $orderColIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
    $orderBy = $columns[$orderColIdx] ?? 'm.fecha_movimiento';

    // Filtro por tipo de movimiento
    $filtroTipo = isset($_GET['tipo_movimiento']) ? trim($_GET['tipo_movimiento']) : '';

    // Total de registros sin filtros
    $total = (int)$db->query("SELECT COUNT(*) FROM insumos_movimientos_stock")->fetchColumn();

    // Construir WHERE
    $where = [];
    $params = [];
    
    if ($filtroTipo !== '') {
        $where[] = 'm.tipo_movimiento = ?';
        $params[] = $filtroTipo;
    }
    
    if ($search !== '') {
        $where[] = '(i.nombre_insumo LIKE ? OR i.subcategoria_varios LIKE ? OR m.observacion LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    
    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total filtrado
    $countSql = "SELECT COUNT(*)
                 FROM insumos_movimientos_stock m
                 JOIN insumos i ON i.id_insumo = m.id_insumo
                 $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $filtered = (int)$stmt->fetchColumn();

    // Obtener datos
    $dataSql = "SELECT 
                    m.id_movimiento,
                    m.fecha_movimiento,
                    m.tipo_movimiento,
                    m.cantidad_movida,
                    m.ubicacion_origen,
                    m.ubicacion_destino,
                    m.cantidad_oficina_antes,
                    m.cantidad_deposito_antes,
                    m.cantidad_oficina_despues,
                    m.cantidad_deposito_despues,
                    m.observacion,
                    i.id_insumo,
                    i.nombre_insumo,
                    i.subcategoria_varios
                FROM insumos_movimientos_stock m
                JOIN insumos i ON i.id_insumo = m.id_insumo
                $whereSql
                ORDER BY $orderBy $orderDir
                LIMIT $start, $length";
    
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Mapear datos para DataTable
    $data = array_map(function($r) {
        // Formatear fecha
        $fecha = date('d/m/Y H:i', strtotime($r['fecha_movimiento']));
        
        // Nombre del insumo con subcategoría si existe
        $nombre = htmlspecialchars($r['nombre_insumo']);
        if (!empty($r['subcategoria_varios'])) {
            $nombre .= '<div class="text-muted small">' . htmlspecialchars($r['subcategoria_varios']) . '</div>';
        }
        
        // Tipo de movimiento con badge
        $tipoMovimiento = $r['tipo_movimiento'];
        $badgeClass = 'bg-secondary';
        $tipoTexto = '';
        
        switch ($tipoMovimiento) {
            case 'reposicion_oficina':
                $badgeClass = 'bg-primary';
                $tipoTexto = 'Reposición Oficina';
                break;
            case 'devolucion_a_deposito':
                $badgeClass = 'bg-info';
                $tipoTexto = 'Devolución a Depósito';
                break;
            case 'ajuste_manual':
                $badgeClass = 'bg-warning';
                $tipoTexto = 'Ajuste Manual';
                break;
            case 'ingreso_nuevo':
                $badgeClass = 'bg-success';
                $tipoTexto = 'Ingreso Nuevo';
                break;
            default:
                $tipoTexto = $tipoMovimiento;
        }
        
        $tipoBadge = '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($tipoTexto) . '</span>';
        
        // Cantidad con flecha direccional
        $cantidad = (int)$r['cantidad_movida'];
        $origen = $r['ubicacion_origen'];
        $destino = $r['ubicacion_destino'];
        
        $origenTexto = '';
        $destinoTexto = '';
        
        switch ($origen) {
            case 'deposito': $origenTexto = '<i class="fas fa-warehouse text-primary"></i> Depósito'; break;
            case 'oficina': $origenTexto = '<i class="fas fa-building text-success"></i> Oficina'; break;
            case 'externo': $origenTexto = '<i class="fas fa-truck"></i> Externo'; break;
            default: $origenTexto = 'N/A';
        }
        
        switch ($destino) {
            case 'deposito': $destinoTexto = '<i class="fas fa-warehouse text-primary"></i> Depósito'; break;
            case 'oficina': $destinoTexto = '<i class="fas fa-building text-success"></i> Oficina'; break;
            case 'externo': $destinoTexto = '<i class="fas fa-truck"></i> Externo'; break;
            default: $destinoTexto = 'N/A';
        }
        
        $movimiento = '<div class="small">' . $origenTexto . ' <i class="fas fa-arrow-right mx-1"></i> ' . $destinoTexto . '</div>';
        $movimiento .= '<strong>' . $cantidad . '</strong> unidades';
        
        // Stock antes/después
        $stockInfo = '<div class="small">';
        $stockInfo .= '<strong>Antes:</strong> ';
        $stockInfo .= '<i class="fas fa-building text-success"></i> ' . (int)$r['cantidad_oficina_antes'] . ' + ';
        $stockInfo .= '<i class="fas fa-warehouse text-primary"></i> ' . (int)$r['cantidad_deposito_antes'];
        $stockInfo .= '<br>';
        $stockInfo .= '<strong>Después:</strong> ';
        $stockInfo .= '<i class="fas fa-building text-success"></i> ' . (int)$r['cantidad_oficina_despues'] . ' + ';
        $stockInfo .= '<i class="fas fa-warehouse text-primary"></i> ' . (int)$r['cantidad_deposito_despues'];
        $stockInfo .= '</div>';
        
        // Observación (si existe)
        $obs = !empty($r['observacion']) ? '<div class="text-muted small mt-1">' . htmlspecialchars($r['observacion']) . '</div>' : '';
        
        return [
            $fecha,
            $nombre,
            $tipoBadge,
            $movimiento,
            $stockInfo . $obs
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
