<?php
/**
 * Interfaz de Diagnóstico y Unificación de Insumos Repetidos tipo "Varios"
 * Versión Cómoda, Amplia y con Detección de Nombres Casi Iguales (Fuzzy & Typos)
 * - Pestaña 1: Catálogo de Modelos Repetidos Exactos (52 grupos)
 * - Pestaña 2: Nombres Casi Iguales y Typos (16 clusters de variantes: Kelix vs Kelyx, Cordir vs Coradir, etc.)
 * - Pestaña 3: Comparativa detallada por Sede (Previas vs Relevamiento) a ancho completo
 * - Pestaña 4: Detalle de IDs individuales en la base de datos
 */
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'ver');
$puedeEditar = tienePermiso('insumos', 'editar');

$db = conectarDB();

// =========================================================================
// 1. OBTENER TODOS LOS INSUMOS VARIOS PARA AGRUPACIÓN EXACTA Y FUZZY
// =========================================================================
$stmtTodos = $db->query("
    SELECT id_insumo, nombre_insumo, subcategoria_varios, cantidad, cantidad_deposito, cantidad_oficina, estado,
           id_sede_actual, id_area_asignacion_actual, fecha_adquisicion
    FROM insumos
    WHERE tipo_insumo = 'Varios'
    ORDER BY nombre_insumo ASC, id_insumo ASC
");
$todosInsumos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

function canonico($str) {
    $s = mb_strtolower(trim($str));
    return preg_replace("/[^a-z0-9]/", "", $s);
}

// Agrupar por nombre exacto normalizado
$porNombre = [];
foreach ($todosInsumos as $ins) {
    $n = trim($ins['nombre_insumo']);
    $norm = mb_strtolower($n);
    if (!isset($porNombre[$norm])) {
        $porNombre[$norm] = [
            'nombre' => $n,
            'nombre_norm' => $norm,
            'canon' => canonico($n),
            'subcategoria' => $ins['subcategoria_varios'] ?? 'Varios',
            'total_registros' => 0,
            'stock_disponible' => 0,
            'stock_deposito' => 0,
            'stock_oficina' => 0,
            'ids' => [],
            'insumos' => []
        ];
    }
    $porNombre[$norm]['total_registros']++;
    $porNombre[$norm]['stock_disponible'] += (int)$ins['cantidad'];
    $porNombre[$norm]['stock_deposito'] += (int)$ins['cantidad_deposito'];
    $porNombre[$norm]['stock_oficina'] += (int)$ins['cantidad_oficina'];
    $porNombre[$norm]['ids'][] = (int)$ins['id_insumo'];
    $porNombre[$norm]['insumos'][] = $ins;
}

// 2. Coexistencias globales en sedes (Previas vs Relevamiento)
$sqlCoexistencias = "
    SELECT 
        LOWER(TRIM(i.nombre_insumo)) as nombre_norm,
        r.id_sede,
        SUM(CASE WHEN r.numero_remito LIKE '%_hist' OR r.fecha_asignacion >= '2026-08-10' THEN rd.cantidad ELSE 0 END) as cant_relevamiento,
        SUM(CASE WHEN r.numero_remito NOT LIKE '%_hist' AND r.fecha_asignacion < '2026-08-10' THEN rd.cantidad ELSE 0 END) as cant_previo
    FROM remitos_detalle rd
    JOIN remitos r ON rd.id_remito = r.id_remito
    JOIN insumos i ON rd.id_insumo = i.id_insumo
    WHERE i.tipo_insumo = 'Varios' AND r.estado = 'Activa'
    GROUP BY LOWER(TRIM(i.nombre_insumo)), r.id_sede
    HAVING cant_relevamiento > 0 AND cant_previo > 0
";
$stmtCoex = $db->query($sqlCoexistencias);
$coexistenciasList = $stmtCoex->fetchAll(PDO::FETCH_ASSOC);
$productosConCoexistencia = array_unique(array_column($coexistenciasList, 'nombre_norm'));

// 3. Filtrar los grupos que tienen 2 o más registros exactos
$gruposDuplicados = [];
foreach ($porNombre as $norm => $item) {
    if ($item['total_registros'] > 1) {
        $item['tiene_coexistencia'] = in_array($norm, $productosConCoexistencia);
        $item['ids_lista'] = implode(',', $item['ids']);
        $gruposDuplicados[] = $item;
    }
}

// Ordenar por mayor dispersión
usort($gruposDuplicados, fn($a, $b) => $b['total_registros'] <=> $a['total_registros']);

// Totales de KPIs
$totalGrupos = count($gruposDuplicados);
$totalRegistrosInvolucrados = array_sum(array_column($gruposDuplicados, 'total_registros'));
$totalRedundantesASanear = $totalRegistrosInvolucrados - $totalGrupos;
$totalStockLibreGlobal = array_sum(array_column($gruposDuplicados, 'stock_disponible'));
$totalConAlertaCoexistencia = count(array_filter($gruposDuplicados, fn($g) => $g['tiene_coexistencia']));

// =========================================================================
// 4. DETECCIÓN DE NOMBRES CASI IGUALES (CLUSTERS FUZZY & TYPOS)
// =========================================================================
$nombresUnicos = array_values($porNombre);
$gruposSimilares = [];
$visitados = [];

for ($i = 0; $i < count($nombresUnicos); $i++) {
    if (isset($visitados[$i])) continue;
    
    $cluster = [$nombresUnicos[$i]];
    $motivos = [];
    
    for ($j = $i + 1; $j < count($nombresUnicos); $j++) {
        if (isset($visitados[$j])) continue;
        
        $itemA = $nombresUnicos[$i];
        $itemB = $nombresUnicos[$j];
        
        $c1 = $itemA['canon'];
        $c2 = $itemB['canon'];
        
        $match = false;
        $motivo = '';
        
        // Criterio 1: Idénticos al quitar signos y espacios (ej. K-120 vs K120, LK 303 vs LK-303)
        if ($c1 === $c2) {
            $match = true;
            $motivo = 'Mismo modelo (guiones o espacios)';
        } else {
            // Criterio 2: Errores de tipeo (Levenshtein 1 o 2 con mismos números de modelo)
            $lev = levenshtein($c1, $c2);
            similar_text($c1, $c2, $pct);
            
            if ($lev === 1 && $pct >= 90) {
                preg_match_all("/\d+/", $c1, $m1);
                preg_match_all("/\d+/", $c2, $m2);
                $nums1 = implode('', $m1[0] ?? []);
                $nums2 = implode('', $m2[0] ?? []);
                
                if ($nums1 === $nums2) {
                    $match = true;
                    $motivo = 'Typo de 1 letra (ej. Kelix vs Kelyx, Lyon vs Lyonn)';
                } elseif (abs(strlen($c1) - strlen($c2)) === 1 && (str_contains($c1, $nums2) || str_contains($c2, $nums1))) {
                    $match = true;
                    $motivo = 'Variación de 1 caracter (' . round($pct, 1) . '% similitud)';
                }
            } elseif ($lev === 2 && $pct >= 93) {
                preg_match_all("/\d+/", $c1, $m1);
                preg_match_all("/\d+/", $c2, $m2);
                if (implode('', $m1[0] ?? []) === implode('', $m2[0] ?? [])) {
                    $match = true;
                    $motivo = 'Typo ortográfico menor (' . round($pct, 1) . '% similitud)';
                }
            }
        }
        
        if ($match) {
            $cluster[] = $itemB;
            $motivos[] = $motivo;
            $visitados[$j] = true;
        }
    }
    
    if (count($cluster) > 1) {
        $visitados[$i] = true;
        // Elegir como nombre sugerido la variante que tiene más registros
        usort($cluster, fn($a, $b) => $b['total_registros'] <=> $a['total_registros']);
        
        $allIdsCluster = [];
        foreach ($cluster as $cItem) {
            $allIdsCluster = array_merge($allIdsCluster, $cItem['ids']);
        }
        
        $gruposSimilares[] = [
            'nombre_sugerido' => $cluster[0]['nombre'],
            'subcategoria' => $cluster[0]['subcategoria'],
            'variantes' => $cluster,
            'motivos' => array_unique($motivos),
            'total_ids' => array_sum(array_column($cluster, 'total_registros')),
            'stock_total' => array_sum(array_column($cluster, 'stock_disponible')),
            'all_ids' => $allIdsCluster
        ];
    }
}

// =========================================================================
// 5. DETERMINAR ÍTEM SELECCIONADO Y CARGA DE DETALLES
// =========================================================================
$itemSeleccionadoNorm = trim($_GET['item'] ?? '');
if ($itemSeleccionadoNorm === '' && !empty($gruposDuplicados)) {
    $itemSeleccionadoNorm = $gruposDuplicados[0]['nombre_norm'];
}

$tabActiva = trim($_GET['tab'] ?? 'catalogo');

$grupoActual = null;
foreach ($gruposDuplicados as $g) {
    if ($g['nombre_norm'] === $itemSeleccionadoNorm) {
        $grupoActual = $g;
        break;
    }
}
if (!$grupoActual && !empty($gruposDuplicados)) {
    $grupoActual = $gruposDuplicados[0];
    $itemSeleccionadoNorm = $grupoActual['nombre_norm'];
}

$insumosDelGrupo = [];
$asignacionesPorSede = [];
$totalAsignadoActivo = 0;
$totalPrevioGral = 0;
$totalRelevamientoGral = 0;
$tieneCoexistenciaGral = false;
$idMaestroSugerido = 0;

if ($grupoActual) {
    $stmtIns = $db->prepare("
        SELECT i.*, 
               s.nombre_sede, l.nombre_localidad, a.nombre_area
        FROM insumos i
        LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
        LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
        LEFT JOIN areas a ON i.id_area_asignacion_actual = a.id_area
        WHERE i.tipo_insumo = 'Varios' AND LOWER(TRIM(i.nombre_insumo)) = ?
        ORDER BY i.id_insumo ASC
    ");
    $stmtIns->execute([$itemSeleccionadoNorm]);
    $insumosDelGrupo = $stmtIns->fetchAll(PDO::FETCH_ASSOC);

    $maxStock = -1;
    foreach ($insumosDelGrupo as $ins) {
        $stk = (int)$ins['cantidad'];
        if ($stk > $maxStock) {
            $maxStock = $stk;
            $idMaestroSugerido = (int)$ins['id_insumo'];
        }
    }
    if ($idMaestroSugerido === 0 && !empty($insumosDelGrupo)) {
        $idMaestroSugerido = (int)$insumosDelGrupo[0]['id_insumo'];
    }

    $idsGrupo = array_column($insumosDelGrupo, 'id_insumo');

    if (!empty($idsGrupo)) {
        $inQ = implode(',', array_fill(0, count($idsGrupo), '?'));
        $stmtAsig = $db->prepare("
            SELECT 
                rd.id_detalle, rd.id_remito, rd.id_insumo, rd.cantidad as cant_asignada, rd.cantidad_devuelta,
                r.numero_remito, r.fecha_asignacion, r.estado as estado_remito,
                r.id_sede, COALESCE(s.nombre_sede, 'Sin Sede') as nombre_sede,
                COALESCE(l.nombre_localidad, '') as nombre_localidad,
                r.id_area, COALESCE(a.nombre_area, 'Sin Área') as nombre_area,
                CONCAT(COALESCE(r.nombre_persona_asignada,''), ' ', COALESCE(r.apellido_persona_asignada,'')) as persona_asignada,
                CASE 
                    WHEN r.numero_remito LIKE '%_hist' OR r.fecha_asignacion >= '2026-08-10' THEN 1
                    ELSE 0
                END as es_relevamiento
            FROM remitos_detalle rd
            JOIN remitos r ON rd.id_remito = r.id_remito
            LEFT JOIN sedes s ON r.id_sede = s.id_sede
            LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
            LEFT JOIN areas a ON r.id_area = a.id_area
            WHERE rd.id_insumo IN ($inQ)
            ORDER BY s.nombre_sede, l.nombre_localidad, r.fecha_asignacion ASC
        ");
        $stmtAsig->execute($idsGrupo);
        $todasAsig = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);

        foreach ($todasAsig as $a) {
            $sedeId = $a['id_sede'] ?? 0;
            $sedeKey = $sedeId . '_' . $a['nombre_sede'] . '_' . $a['nombre_localidad'];
            if (!isset($asignacionesPorSede[$sedeKey])) {
                $asignacionesPorSede[$sedeKey] = [
                    'id_sede' => $sedeId,
                    'nombre_sede' => $a['nombre_sede'],
                    'nombre_localidad' => $a['nombre_localidad'],
                    'total_previo' => 0,
                    'total_relevamiento' => 0,
                    'total_sede' => 0,
                    'asignaciones' => []
                ];
            }
            $cantActiva = max(0, (int)$a['cant_asignada'] - (int)$a['cantidad_devuelta']);
            $asignacionesPorSede[$sedeKey]['total_sede'] += $cantActiva;
            $totalAsignadoActivo += $cantActiva;

            if ($a['es_relevamiento'] == 1) {
                $asignacionesPorSede[$sedeKey]['total_relevamiento'] += $cantActiva;
                $totalRelevamientoGral += $cantActiva;
            } else {
                $asignacionesPorSede[$sedeKey]['total_previo'] += $cantActiva;
                $totalPrevioGral += $cantActiva;
            }
            $asignacionesPorSede[$sedeKey]['asignaciones'][] = $a;
        }

        foreach ($asignacionesPorSede as $sInfo) {
            if ($sInfo['total_previo'] > 0 && $sInfo['total_relevamiento'] > 0) {
                $tieneCoexistenciaGral = true;
                break;
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Breadcrumb y Cabecera Principal -->
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?php echo app_base_url(); ?>/index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php">Insumos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Saneamiento de Insumos Varios</li>
                </ol>
            </nav>
            <h2 class="mb-0 fw-bold d-flex align-items-center flex-wrap gap-2">
                <i class="fas fa-boxes-stacked text-primary"></i>
                <span>Saneamiento y Unificación de Insumos "Varios"</span>
            </h2>
            <p class="text-muted mb-0 small">
                Consolidación de catálogo fragmentado, cruce comparativo por sede y fusión segura con eliminación de registros redundantes.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver al Listado
            </a>
            <a href="<?php echo app_base_url(); ?>/diagnostico_duplicados.php" class="btn btn-outline-info btn-sm ms-1">
                <i class="fas fa-magnifying-glass me-1"></i>Duplicados Hardware
            </a>
        </div>
    </div>

    <!-- KPIs Globales -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-body">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Modelos Repetidos Exactos</div>
                        <div class="fs-4 fw-bold text-primary"><?php echo $totalGrupos; ?></div>
                        <div class="small text-muted">productos idénticos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-body">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 me-3">
                        <i class="fas fa-spell-check fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Nombres Casi Iguales</div>
                        <div class="fs-4 fw-bold text-warning"><?php echo count($gruposSimilares); ?> clusters</div>
                        <div class="small text-muted">typos y variantes ortográficas</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-body">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3 me-3">
                        <i class="fas fa-triangle-exclamation fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Con Coexistencia</div>
                        <div class="fs-4 fw-bold text-danger"><?php echo $totalConAlertaCoexistencia; ?></div>
                        <div class="small text-muted">solapamiento previo vs relevamiento</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-body">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3">
                        <i class="fas fa-trash-arrow-up fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Registros a Eliminar</div>
                        <div class="fs-4 fw-bold text-success"><?php echo $totalRedundantesASanear; ?></div>
                        <div class="small text-muted">filas duplicadas a sanear</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas Principales a Ancho Completo -->
    <div class="card border shadow-sm rounded-3 bg-body mb-4">
        <div class="card-header bg-body pt-3 pb-0 border-bottom">
            <ul class="nav nav-tabs border-0" id="tabsModoVista" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link fs-6 py-2 px-3 <?php echo ($tabActiva === 'catalogo') ? 'active fw-bold' : 'text-muted'; ?>" 
                            id="tab-catalogo-btn" data-bs-toggle="tab" data-bs-target="#tab-catalogo" type="button" role="tab">
                        <i class="fas fa-table-list me-2 text-primary"></i>1. Catálogo Exacto (<?php echo $totalGrupos; ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fs-6 py-2 px-3 <?php echo ($tabActiva === 'similares') ? 'active fw-bold' : 'text-muted'; ?>" 
                            id="tab-similares-btn" data-bs-toggle="tab" data-bs-target="#tab-similares" type="button" role="tab">
                        <i class="fas fa-spell-check me-2 text-warning"></i>2. Nombres Casi Iguales y Typos (<?php echo count($gruposSimilares); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fs-6 py-2 px-3 <?php echo ($tabActiva === 'comparativa') ? 'active fw-bold' : 'text-muted'; ?>" 
                            id="tab-comparativa-btn" data-bs-toggle="tab" data-bs-target="#tab-comparativa" type="button" role="tab">
                        <i class="fas fa-code-compare me-2 text-success"></i>3. Comparativa por Sede (Previas vs Relevamiento)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fs-6 py-2 px-3 <?php echo ($tabActiva === 'registros') ? 'active fw-bold' : 'text-muted'; ?>" 
                            id="tab-registros-btn" data-bs-toggle="tab" data-bs-target="#tab-registros" type="button" role="tab">
                        <i class="fas fa-database me-2 text-secondary"></i>4. Detalle de IDs en BD (<?php echo count($insumosDelGrupo); ?>)
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content" id="tabsModoVistaContent">

                <!-- ========================================================================= -->
                <!-- PESTAÑA 1: CATÁLOGO DE PRODUCTOS REPETIDOS EXACTOS (TABLA GENERAL) -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade <?php echo ($tabActiva === 'catalogo') ? 'show active' : ''; ?>" id="tab-catalogo" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 480px;">
                            <div class="input-group">
                                <span class="input-group-text bg-body"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="filtroTablaGeneral" class="form-control" placeholder="Buscar por nombre de producto...">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm active" id="btnFiltroTodos">
                                Todos (<?php echo $totalGrupos; ?>)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnFiltroAlertas">
                                <i class="fas fa-triangle-exclamation me-1"></i>Con Coexistencia (<?php echo $totalConAlertaCoexistencia; ?>)
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0" id="tablaCatalogoRepetidos">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase">
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Producto / Insumo</th>
                                    <th>Subcategoría</th>
                                    <th class="text-center">Dispersión (IDs)</th>
                                    <th class="text-center">Stock Depósito</th>
                                    <th class="text-center">Stock Oficina</th>
                                    <th class="text-center">Stock Total Libre</th>
                                    <th class="text-center">Diagnóstico</th>
                                    <th class="text-end" style="width: 220px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $pos = 1; foreach ($gruposDuplicados as $g): 
                                    $isSelected = ($g['nombre_norm'] === $itemSeleccionadoNorm);
                                ?>
                                    <tr class="fila-producto <?php echo $isSelected ? 'table-primary table-opacity-10' : ''; ?> <?php echo $g['tiene_coexistencia'] ? 'fila-alerta' : ''; ?>"
                                        data-nombre="<?php echo htmlspecialchars(strtolower($g['nombre'])); ?>"
                                        data-coexistencia="<?php echo $g['tiene_coexistencia'] ? '1' : '0'; ?>">
                                        <td class="text-center text-muted small"><?php echo $pos++; ?></td>
                                        <td>
                                            <a href="?item=<?php echo urlencode($g['nombre_norm']); ?>&tab=comparativa" class="fw-bold text-decoration-none fs-6">
                                                <?php echo htmlspecialchars($g['nombre']); ?>
                                            </a>
                                            <?php if ($isSelected): ?>
                                                <span class="badge bg-primary ms-1 small">Seleccionado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($g['subcategoria']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-1 fs-6">
                                                <?php echo $g['total_registros']; ?> IDs
                                            </span>
                                            <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                                IDs: #<?php echo str_replace(',', ', #', $g['ids_lista']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold"><?php echo (int)$g['stock_deposito']; ?></td>
                                        <td class="text-center fw-semibold"><?php echo (int)$g['stock_oficina']; ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success fs-6"><?php echo (int)$g['stock_disponible']; ?> unid</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($g['tiene_coexistencia']): ?>
                                                <span class="badge bg-danger py-1 px-2 d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-triangle-exclamation"></i> Coexistencia Detectada
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border py-1 px-2">
                                                    <i class="fas fa-check me-1"></i>Sin solapamiento
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?item=<?php echo urlencode($g['nombre_norm']); ?>&tab=comparativa" class="btn btn-primary" title="Ver Comparativa Detallada">
                                                    <i class="fas fa-code-compare me-1"></i>Comparar
                                                </a>
                                                <?php if ($puedeEditar): ?>
                                                    <button type="button" class="btn btn-success btn-abrir-unificar-directo"
                                                            data-nombre="<?php echo htmlspecialchars($g['nombre']); ?>"
                                                            data-ids="<?php echo htmlspecialchars($g['ids_lista']); ?>"
                                                            title="Unificar Stock y Eliminar Duplicados Vacíos">
                                                        <i class="fas fa-compress-alt me-1"></i>Unificar
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- PESTAÑA 2: NOMBRES CASI IGUALES Y TYPOS (CLUSTERS FUZZY) -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade <?php echo ($tabActiva === 'similares') ? 'show active' : ''; ?>" id="tab-similares" role="tabpanel">
                    <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between mb-4 py-2 px-3">
                        <div>
                            <i class="fas fa-spell-check text-warning fa-lg me-2"></i>
                            <strong>Detección de Variaciones Ortográficas y Typos:</strong> 
                            Se detectaron <strong><?php echo count($gruposSimilares); ?> clusters</strong> de productos con nombres casi idénticos (diferencia de guiones, espacios o errores de tipeo como <em>Kelix</em> vs <em>Kelyx</em>, <em>Cordir</em> vs <em>Coradir</em>, etc.).
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($gruposSimilares as $idx => $cluster): ?>
                            <div class="card border shadow-sm rounded-3 bg-body">
                                <div class="card-header bg-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">
                                            <i class="fas fa-spell-check me-1"></i>Cluster #<?php echo ($idx + 1); ?>
                                        </span>
                                        <strong class="fs-6"><?php echo htmlspecialchars($cluster['nombre_sugerido']); ?></strong>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($cluster['subcategoria']); ?></span>
                                        <span class="badge bg-primary"><?php echo $cluster['total_ids']; ?> IDs en total</span>
                                        <span class="badge bg-success"><?php echo $cluster['stock_total']; ?> unid en stock</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small d-none d-md-inline">
                                            <i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars(implode('; ', $cluster['motivos'])); ?>
                                        </span>
                                        <?php if ($puedeEditar): ?>
                                            <button type="button" class="btn btn-sm btn-success btn-abrir-fusionar-cluster"
                                                    data-cluster='<?php echo json_encode($cluster, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>'>
                                                <i class="fas fa-compress-alt me-1"></i>Fusionar estas Variantes
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr class="small text-muted text-uppercase">
                                                    <th>Variante Escrita en Base de Datos</th>
                                                    <th class="text-center" style="width: 130px;">Registros (IDs)</th>
                                                    <th class="text-center" style="width: 130px;">Stock Libre</th>
                                                    <th>IDs que la componen</th>
                                                    <th class="text-end" style="width: 140px;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cluster['variantes'] as $v): ?>
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <i class="fas fa-chevron-right text-muted me-2 small"></i>
                                                            <?php echo htmlspecialchars($v['nombre']); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                                <?php echo $v['total_registros']; ?> IDs
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                                <?php echo $v['stock_disponible']; ?> unid
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <code class="small">#<?php echo implode(', #', $v['ids']); ?></code>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="?tab=comparativa&item=<?php echo urlencode($v['nombre_norm']); ?>" class="btn btn-outline-primary btn-sm py-0 px-2" title="Ver Comparativa de Sedes">
                                                                <i class="fas fa-eye me-1"></i>Ver Sedes
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- PESTAÑA 3: COMPARATIVA DETALLADA POR SEDE (ANCHO COMPLETO) -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade <?php echo ($tabActiva === 'comparativa') ? 'show active' : ''; ?>" id="tab-comparativa" role="tabpanel">
                    
                    <?php if ($grupoActual): ?>
                        <!-- Barra de Selección Rápida Superior -->
                        <div class="card border bg-body-tertiary shadow-sm mb-4">
                            <div class="card-body p-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-lg-7">
                                        <label for="selectProductoRapido" class="form-label fw-bold small text-uppercase text-muted mb-1">
                                            <i class="fas fa-box-open text-primary me-1"></i>Seleccionar Producto para Comparar:
                                        </label>
                                        <select id="selectProductoRapido" class="form-select form-select-lg fw-semibold" onchange="location.href='?tab=comparativa&item=' + encodeURIComponent(this.value)">
                                            <?php foreach ($gruposDuplicados as $g): ?>
                                                <option value="<?php echo htmlspecialchars($g['nombre_norm']); ?>" <?php echo ($g['nombre_norm'] === $itemSeleccionadoNorm) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($g['nombre']); ?> (<?php echo $g['total_registros']; ?> IDs en BD) <?php echo $g['tiene_coexistencia'] ? '⚠️ [Coexistencia]' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-5 text-lg-end d-flex align-items-center justify-content-lg-end gap-2 mt-3 mt-lg-0">
                                        <a href="?item=<?php echo urlencode($itemSeleccionadoNorm); ?>&tab=registros" class="btn btn-outline-secondary">
                                            <i class="fas fa-database me-1"></i>Ver <?php echo count($insumosDelGrupo); ?> IDs en BD
                                        </a>
                                        <?php if ($puedeEditar): ?>
                                            <button type="button" class="btn btn-success shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalUnificarStock">
                                                <i class="fas fa-compress-alt me-1"></i>Unificar en 1 Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen y Métricas del Producto Seleccionado -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="p-3 border rounded-3 bg-body text-center shadow-sm">
                                    <div class="text-muted small fw-semibold">IDs Dispersos en BD</div>
                                    <div class="fs-4 fw-bold text-warning"><?php echo $grupoActual['total_registros']; ?> registros</div>
                                    <div class="small text-muted">IDs: #<?php echo str_replace(',', ', #', $grupoActual['ids_lista']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded-3 bg-body text-center shadow-sm">
                                    <div class="text-muted small fw-semibold">Stock Libre Físico</div>
                                    <div class="fs-4 fw-bold text-success"><?php echo (int)$grupoActual['stock_disponible']; ?> unid</div>
                                    <div class="small text-muted">Depósito: <?php echo (int)$grupoActual['stock_deposito']; ?> | Oficina: <?php echo (int)$grupoActual['stock_oficina']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded-3 bg-body text-center shadow-sm">
                                    <div class="text-muted small fw-semibold">Asignado en Sedes</div>
                                    <div class="fs-4 fw-bold text-primary"><?php echo $totalAsignadoActivo; ?> unid</div>
                                    <div class="small text-muted">en <?php echo count($asignacionesPorSede); ?> sedes distintas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded-3 bg-body text-center shadow-sm">
                                    <div class="text-muted small fw-semibold">Gran Total Físico</div>
                                    <div class="fs-4 fw-bold text-dark dark-text-light"><?php echo ((int)$grupoActual['stock_disponible'] + $totalAsignadoActivo); ?> unid</div>
                                    <div class="small text-muted">Stock libre + Asignaciones</div>
                                </div>
                            </div>
                        </div>

                        <!-- Comparativa por Sede -->
                        <?php if (empty($asignacionesPorSede)): ?>
                            <div class="card border shadow-sm rounded-3 text-center py-5 bg-body">
                                <div class="text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>Sin Asignaciones en Remitos</h5>
                                    <p class="small mb-0">Este producto tiene múltiples IDs creados en depósito/oficina pero aún no fue asignado a ninguna sede.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between mb-4 py-2 px-3">
                                <div class="small">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    <strong>Criterio de Diagnóstico:</strong>
                                    <span class="badge bg-secondary me-1">Previa</span> Asignación oficial antes del 10/08/2026.
                                    <span class="badge bg-info text-dark ms-2 me-1">Relevamiento</span> Carga masiva o histórica (`_hist`) a partir de agosto.
                                </div>
                                <div>
                                    <?php if ($tieneCoexistenciaGral): ?>
                                        <span class="badge bg-danger py-1 px-2">
                                            <i class="fas fa-triangle-exclamation me-1"></i>Coexistencia detectada en una o más sedes
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success py-1 px-2">
                                            <i class="fas fa-check me-1"></i>Asignaciones bien diferenciadas sin solapamiento
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($asignacionesPorSede as $sKey => $sInfo): 
                                    $esAlerta = ($sInfo['total_previo'] > 0 && $sInfo['total_relevamiento'] > 0);
                                    $cardBorder = $esAlerta ? 'border-danger border-2' : 'border';
                                ?>
                                    <div class="card <?php echo $cardBorder; ?> shadow-sm rounded-3 bg-body">
                                        <div class="card-header bg-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-building <?php echo $esAlerta ? 'text-danger' : 'text-primary'; ?>"></i>
                                                <strong class="fs-6"><?php echo htmlspecialchars($sInfo['nombre_sede']); ?></strong>
                                                <?php if (!empty($sInfo['nombre_localidad'])): ?>
                                                    <span class="badge bg-light text-dark border small"><?php echo htmlspecialchars($sInfo['nombre_localidad']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge bg-secondary">Previas: <?php echo $sInfo['total_previo']; ?> unid</span>
                                                <span class="badge bg-info text-dark">Relevamiento: <?php echo $sInfo['total_relevamiento']; ?> unid</span>
                                                <span class="badge bg-primary">Total Sede: <?php echo $sInfo['total_sede']; ?> unid</span>
                                                <?php if ($esAlerta): ?>
                                                    <span class="badge bg-danger d-inline-flex align-items-center gap-1">
                                                        <i class="fas fa-triangle-exclamation"></i> Coexistencia Detectada
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr class="small text-muted text-uppercase">
                                                            <th style="width: 140px;">Periodo</th>
                                                            <th style="width: 110px;">Fecha</th>
                                                            <th style="width: 160px;">Remito Oficial</th>
                                                            <th>Persona Asignada</th>
                                                            <th>Área / Oficina</th>
                                                            <th class="text-center" style="width: 90px;">Cantidad</th>
                                                            <th class="text-center" style="width: 110px;">Insumo ID</th>
                                                            <th class="text-end" style="width: 80px;">PDF</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sInfo['asignaciones'] as $asig): 
                                                            $esRel = ($asig['es_relevamiento'] == 1);
                                                            $badgeTipo = $esRel 
                                                                ? '<span class="badge bg-info text-dark"><i class="fas fa-clipboard-check me-1"></i>Relevamiento</span>' 
                                                                : '<span class="badge bg-secondary"><i class="fas fa-clock-rotate-left me-1"></i>Previa</span>';
                                                            $fechaFmt = !empty($asig['fecha_asignacion']) ? date('d/m/Y', strtotime($asig['fecha_asignacion'])) : '-';
                                                        ?>
                                                            <tr class="<?php echo $esRel ? 'table-info table-opacity-10' : ''; ?>">
                                                                <td><?php echo $badgeTipo; ?></td>
                                                                <td class="small fw-semibold"><?php echo $fechaFmt; ?></td>
                                                                <td>
                                                                    <a href="<?php echo app_base_url(); ?>/pages/reportes/remito.php?remito=<?php echo urlencode($asig['numero_remito']); ?>" target="_blank" class="fw-bold text-decoration-none">
                                                                        <i class="fas fa-file-lines me-1 text-muted"></i>#<?php echo htmlspecialchars($asig['numero_remito']); ?>
                                                                    </a>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-user text-muted me-1 small"></i>
                                                                    <strong><?php echo htmlspecialchars(trim($asig['persona_asignada']) ?: '(Sin especificar)'); ?></strong>
                                                                </td>
                                                                <td class="text-muted small">
                                                                    <?php echo htmlspecialchars($asig['nombre_area'] ?: 'General'); ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-light text-dark border fw-bold fs-6">
                                                                        <?php echo (int)$asig['cant_asignada']; ?>
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <code class="fw-bold">#<?php echo $asig['id_insumo']; ?></code>
                                                                </td>
                                                                <td class="text-end">
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" 
                                                                            data-visor-pdf="<?php echo app_base_url(); ?>/pages/reportes/remito_pdf.php?remito=<?php echo urlencode($asig['numero_remito']); ?>" 
                                                                            title="Ver PDF del Remito">
                                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <!-- ========================================================================= -->
                <!-- PESTAÑA 4: DETALLE DE IDS EN BASE DE DATOS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade <?php echo ($tabActiva === 'registros') ? 'show active' : ''; ?>" id="tab-registros" role="tabpanel">
                    <?php if ($grupoActual): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-database text-secondary me-2"></i>Registros individuales de "<?php echo htmlspecialchars($grupoActual['nombre']); ?>"
                                </h5>
                                <div class="text-muted small">
                                    Actualmente existen <?php echo count($insumosDelGrupo); ?> filas independientes que se fusionarán en el Insumo Maestro (eliminando las vaciadas).
                                </div>
                            </div>
                            <?php if ($puedeEditar): ?>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUnificarStock">
                                    <i class="fas fa-compress-alt me-1"></i>Unificar estos <?php echo count($insumosDelGrupo); ?> Registros
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase">
                                        <th class="text-center" style="width: 70px;">ID</th>
                                        <th>Rol en Unificación</th>
                                        <th>Estado Actual</th>
                                        <th class="text-center">Stock Depósito</th>
                                        <th class="text-center">Stock Oficina</th>
                                        <th class="text-center">Total Stock</th>
                                        <th>Sede Actual</th>
                                        <th>Área</th>
                                        <th>Fecha Alta</th>
                                        <th class="text-end">Ficha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($insumosDelGrupo as $ins): 
                                        $esMaestro = ((int)$ins['id_insumo'] === $idMaestroSugerido);
                                        $badgeEstado = match($ins['estado']) {
                                            'Disponible' => 'bg-success',
                                            'Asignado' => 'bg-primary',
                                            'De Baja' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                        <tr class="<?php echo $esMaestro ? 'table-success table-opacity-10' : ''; ?>">
                                            <td class="text-center fw-bold">
                                                <code>#<?php echo $ins['id_insumo']; ?></code>
                                            </td>
                                            <td>
                                                <?php if ($esMaestro): ?>
                                                    <span class="badge bg-success d-inline-flex align-items-center gap-1 py-1 px-2">
                                                        <i class="fas fa-crown"></i> Insumo Maestro Recomendado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border py-1 px-2">
                                                        <i class="fas fa-trash me-1 text-danger"></i> Se eliminará tras reasignar
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeEstado; ?>">
                                                    <?php echo htmlspecialchars($ins['estado']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-semibold"><?php echo (int)$ins['cantidad_deposito']; ?></td>
                                            <td class="text-center fw-semibold"><?php echo (int)$ins['cantidad_oficina']; ?></td>
                                            <td class="text-center fw-bold fs-6"><?php echo (int)$ins['cantidad']; ?></td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($ins['nombre_sede'] ?: '-'); ?>
                                                <?php if ($ins['nombre_localidad']): ?>
                                                    <span class="text-muted">(<?php echo htmlspecialchars($ins['nombre_localidad']); ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($ins['nombre_area'] ?: '-'); ?></td>
                                            <td class="small text-muted"><?php echo !empty($ins['fecha_adquisicion']) ? date('d/m/Y', strtotime($ins['fecha_adquisicion'])) : '-'; ?></td>
                                            <td class="text-end">
                                                <a href="<?php echo app_base_url(); ?>/pages/insumos/ver.php?id=<?php echo $ins['id_insumo']; ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Abrir Ficha de Insumo">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: UNIFICACIÓN DE MODELOS EXACTOS -->
<!-- ========================================================================= -->
<?php if ($puedeEditar && $grupoActual): ?>
    <div class="modal fade" id="modalUnificarStock" tabindex="-1" aria-labelledby="modalUnificarStockLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalUnificarStockLabel">
                        <i class="fas fa-compress-alt me-2"></i>Unificar Stock de "<span id="modalNombreProducto"><?php echo htmlspecialchars($grupoActual['nombre']); ?></span>"
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-info d-flex align-items-center mb-3">
                        <i class="fas fa-circle-info fa-2x me-3 text-info"></i>
                        <div>
                            <strong>¿Cómo funciona la unificación y saneamiento?</strong>
                            <ul class="mb-0 ps-3 small mt-1">
                                <li>Se selecciona un <strong>Insumo Maestro</strong> que conservará todo el catálogo y stock.</li>
                                <li>Todas las asignaciones en remitos se reasignan al Maestro (<strong>se preserva el 100% de la trazabilidad por sede, persona y fecha</strong>).</li>
                                <li>El stock físico libre (depósito y oficina) se suma en el Maestro.</li>
                                <li><strong>Los demás registros duplicados que quedan en cero se eliminan de la base de datos</strong> para dejar el catálogo limpio.</li>
                            </ul>
                        </div>
                    </div>

                    <form id="formUnificarStock">
                        <input type="hidden" name="_csrf" id="csrfTokenInput" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="nombre_insumo" id="nombreInsumoInput" value="<?php echo htmlspecialchars($grupoActual['nombre']); ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione el Insumo Maestro a conservar:</label>
                            <div class="list-group" id="listaMaestrosModal">
                                <?php foreach ($insumosDelGrupo as $ins): 
                                    $esSugerido = ((int)$ins['id_insumo'] === $idMaestroSugerido);
                                ?>
                                    <label class="list-group-item d-flex align-items-center justify-content-between py-2 cursor-pointer">
                                        <div class="d-flex align-items-center gap-3">
                                            <input class="form-check-input me-1" type="radio" name="id_maestro" value="<?php echo $ins['id_insumo']; ?>" <?php echo $esSugerido ? 'checked' : ''; ?>>
                                            <div>
                                                <strong class="me-2">Insumo #<?php echo $ins['id_insumo']; ?></strong>
                                                <span class="badge bg-light text-dark border small me-1">Stock: <?php echo (int)$ins['cantidad']; ?> (Dep: <?php echo (int)$ins['cantidad_deposito']; ?>, Of: <?php echo (int)$ins['cantidad_oficina']; ?>)</span>
                                                <span class="badge bg-secondary small"><?php echo htmlspecialchars($ins['estado']); ?></span>
                                                <?php if (!empty($ins['nombre_sede'])): ?>
                                                    <span class="text-muted small ms-2"><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($ins['nombre_sede']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($esSugerido): ?>
                                            <span class="badge bg-success"><i class="fas fa-crown me-1"></i>Recomendado</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border text-muted small">
                            <i class="fas fa-shield-halved text-success me-1"></i>
                            Operación atómica en transacción PDO. Auditoría completa con respaldo previo en formato JSON.
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold px-4" id="btnConfirmarUnificacion">
                        <i class="fas fa-check me-1"></i>Confirmar Unificación y Eliminar Duplicados
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL 2: FUSIÓN DE CLUSTERS (NOMBRES CASI IGUALES / TYPOS) -->
<!-- ========================================================================= -->
<?php if ($puedeEditar): ?>
    <div class="modal fade" id="modalFusionarCluster" tabindex="-1" aria-labelledby="modalFusionarClusterLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="modalFusionarClusterLabel">
                        <i class="fas fa-spell-check me-2"></i>Fusionar Variantes Ortográficas y Typos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-warning d-flex align-items-center mb-3">
                        <i class="fas fa-triangle-exclamation fa-2x me-3 text-warning"></i>
                        <div>
                            <strong>Corrección de Nombre y Consolidación de Catálogo</strong>
                            <p class="mb-0 small">
                                Esta acción unificará todas las variantes seleccionadas bajo un <strong>Nombre Oficial Canónico</strong>. 
                                Todos los remitos quedarán apuntando al Insumo Maestro elegido, se consolidará el stock físico y los registros duplicados vaciados se eliminarán de la base de datos.
                            </p>
                        </div>
                    </div>

                    <form id="formFusionarCluster">
                        <input type="hidden" name="_csrf" id="csrfTokenCluster" value="<?php echo csrf_token(); ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">1. Nombre Oficial a Establecer (Nombre canónico corregido):</label>
                            <input type="text" class="form-control form-control-lg fw-bold" id="inputNombreOficialCluster" required>
                            <div class="form-text small">Podés seleccionar una de las variantes existentes o tipear el nombre oficial correcto.</div>
                            <div class="d-flex flex-wrap gap-1 mt-2" id="botonesNombresSugeridos"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">2. Seleccione el Insumo Maestro a conservar:</label>
                            <div class="list-group" id="listaMaestrosCluster" style="max-height: 280px; overflow-y: auto;">
                                <!-- Se llena dinámicamente con JavaScript -->
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border text-muted small">
                            <i class="fas fa-shield-halved text-success me-1"></i>
                            Todas las asignaciones activas conservarán su historial y trazabilidad apuntando al ID Maestro seleccionado.
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-bold px-4 text-dark" id="btnConfirmarFusionCluster">
                        <i class="fas fa-check me-1"></i>Confirmar Fusión y Eliminar Vacíos
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfGlobal = '<?php echo csrf_token(); ?>';

    // 1. Filtro instantáneo de la tabla general del catálogo
    const filtroInput = document.getElementById('filtroTablaGeneral');
    const filasProducto = document.querySelectorAll('.fila-producto');
    const btnFiltroTodos = document.getElementById('btnFiltroTodos');
    const btnFiltroAlertas = document.getElementById('btnFiltroAlertas');

    let soloAlertas = false;

    function aplicarFiltro() {
        const query = filtroInput ? filtroInput.value.toLowerCase().trim() : '';
        filasProducto.forEach(function(fila) {
            const nombre = fila.getAttribute('data-nombre') || '';
            const esAlerta = fila.getAttribute('data-coexistencia') === '1';

            const coincideTexto = (query === '' || nombre.includes(query));
            const coincideAlerta = (!soloAlertas || esAlerta);

            if (coincideTexto && coincideAlerta) {
                fila.classList.remove('d-none');
            } else {
                fila.classList.add('d-none');
            }
        });
    }

    if (filtroInput) {
        filtroInput.addEventListener('input', aplicarFiltro);
    }

    if (btnFiltroTodos && btnFiltroAlertas) {
        btnFiltroTodos.addEventListener('click', function() {
            soloAlertas = false;
            btnFiltroTodos.classList.add('active');
            btnFiltroAlertas.classList.remove('active');
            aplicarFiltro();
        });

        btnFiltroAlertas.addEventListener('click', function() {
            soloAlertas = true;
            btnFiltroAlertas.classList.add('active');
            btnFiltroTodos.classList.remove('active');
            aplicarFiltro();
        });
    }

    // 2. Unificación Exacta vía AJAX
    const btnConfirmar = document.getElementById('btnConfirmarUnificacion');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            const form = document.getElementById('formUnificarStock');
            const radioMaestro = form.querySelector('input[name="id_maestro"]:checked');
            if (!radioMaestro) {
                if (typeof showToast === 'function') {
                    showToast('Por favor seleccione el Insumo Maestro a conservar.', 'warning');
                } else {
                    alert('Por favor seleccione el Insumo Maestro a conservar.');
                }
                return;
            }

            const idMaestro = radioMaestro.value;
            const nombreInsumo = form.querySelector('input[name="nombre_insumo"]').value;

            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Unificando...';

            fetch('<?php echo app_base_url(); ?>/ajax/varios_unificar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfGlobal
                },
                body: JSON.stringify({
                    _csrf: csrfGlobal,
                    id_maestro: idMaestro,
                    nombre_insumo: nombreInsumo
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast(data.data.mensaje || 'Unificación completada con éxito', 'success');
                    } else {
                        alert(data.data.mensaje || 'Unificación completada con éxito');
                    }
                    setTimeout(function() {
                        window.location.href = '?tab=comparativa&item=' + encodeURIComponent(nombreInsumo.toLowerCase());
                    }, 1200);
                } else {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Unificación y Eliminar Duplicados';
                    if (typeof showToast === 'function') {
                        showToast(data.error || 'Error al procesar la unificación', 'error');
                    } else {
                        alert(data.error || 'Error al procesar la unificación');
                    }
                }
            })
            .catch(err => {
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Unificación y Eliminar Duplicados';
                console.error(err);
                if (typeof showToast === 'function') {
                    showToast('Error de comunicación con el servidor', 'error');
                } else {
                    alert('Error de comunicación con el servidor');
                }
            });
        });
    }

    // 3. Fusión de Variantes / Typos (Clusters)
    let clusterActivo = null;
    const modalFusionarElem = document.getElementById('modalFusionarCluster');
    const bsModalCluster = modalFusionarElem ? new bootstrap.Modal(modalFusionarElem) : null;

    document.querySelectorAll('.btn-abrir-fusionar-cluster').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                clusterActivo = JSON.parse(this.getAttribute('data-cluster'));
            } catch (e) {
                console.error(e);
                return;
            }

            if (!clusterActivo) return;

            // Rellenar input de nombre oficial con el sugerido
            const inputNombre = document.getElementById('inputNombreOficialCluster');
            inputNombre.value = clusterActivo.nombre_sugerido;

            // Botones de variantes para elegir rápido
            const contenedorBotones = document.getElementById('botonesNombresSugeridos');
            contenedorBotones.innerHTML = '';
            clusterActivo.variantes.forEach(function(v) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn btn-outline-secondary btn-sm py-0 px-2';
                b.textContent = v.nombre;
                b.onclick = function() {
                    inputNombre.value = v.nombre;
                };
                contenedorBotones.appendChild(b);
            });

            // Rellenar lista de IDs disponibles para ser Maestro
            const listaMaestros = document.getElementById('listaMaestrosCluster');
            listaMaestros.innerHTML = '<div class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando opciones de Insumos...</div>';

            bsModalCluster.show();

            // Cargar info de los insumos del cluster
            fetch('<?php echo app_base_url(); ?>/ajax/insumos_por_ids.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: clusterActivo.all_ids })
            })
            .then(res => res.json())
            .then(data => {
                const insumos = data.data || [];
                if (insumos.length === 0) {
                    listaMaestros.innerHTML = '<div class="alert alert-warning small">No se pudieron recuperar los registros de insumos.</div>';
                    return;
                }

                // Sugerir el ID con mayor stock o menor ID
                let mejorId = insumos[0].id_insumo;
                let maxStock = -1;
                insumos.forEach(ins => {
                    const stk = parseInt(ins.cantidad || 0);
                    if (stk > maxStock) {
                        maxStock = stk;
                        mejorId = ins.id_insumo;
                    }
                });

                let html = '';
                insumos.forEach(ins => {
                    const isChecked = (ins.id_insumo == mejorId) ? 'checked' : '';
                    html += `
                        <label class="list-group-item d-flex align-items-center justify-content-between py-2 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <input class="form-check-input me-1" type="radio" name="id_maestro_cluster" value="${ins.id_insumo}" ${isChecked}>
                                <div>
                                    <strong class="me-2">#${ins.id_insumo}</strong> - <span>${ins.nombre_insumo || ''}</span>
                                    <span class="badge bg-light text-dark border small ms-2">Stock: ${ins.cantidad || 0}</span>
                                    <span class="badge bg-secondary small">${ins.estado || ''}</span>
                                </div>
                            </div>
                            ${isChecked ? '<span class="badge bg-success"><i class="fas fa-crown me-1"></i>Recomendado</span>' : ''}
                        </label>
                    `;
                });
                listaMaestros.innerHTML = html;
            })
            .catch(err => {
                // Fallback con los IDs conocidos
                let html = '';
                clusterActivo.variantes.forEach(v => {
                    v.ids.forEach(id => {
                        html += `
                            <label class="list-group-item d-flex align-items-center justify-content-between py-2 cursor-pointer">
                                <div class="d-flex align-items-center gap-3">
                                    <input class="form-check-input me-1" type="radio" name="id_maestro_cluster" value="${id}">
                                    <div><strong>Insumo #${id}</strong> - <span>${v.nombre}</span></div>
                                </div>
                            </label>
                        `;
                    });
                });
                listaMaestros.innerHTML = html;
            });
        });
    });

    // Confirmar Fusión de Variantes
    const btnConfirmarCluster = document.getElementById('btnConfirmarFusionCluster');
    if (btnConfirmarCluster) {
        btnConfirmarCluster.addEventListener('click', function() {
            if (!clusterActivo) return;

            const radio = document.querySelector('input[name="id_maestro_cluster"]:checked');
            if (!radio) {
                if (typeof showToast === 'function') {
                    showToast('Seleccione el Insumo Maestro a conservar.', 'warning');
                } else {
                    alert('Seleccione el Insumo Maestro a conservar.');
                }
                return;
            }

            const idMaestro = radio.value;
            const nombreOficial = document.getElementById('inputNombreOficialCluster').value.trim();

            if (!nombreOficial) {
                if (typeof showToast === 'function') {
                    showToast('Ingrese el nombre oficial canónico.', 'warning');
                } else {
                    alert('Ingrese el nombre oficial canónico.');
                }
                return;
            }

            btnConfirmarCluster.disabled = true;
            btnConfirmarCluster.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Fusionando...';

            fetch('<?php echo app_base_url(); ?>/ajax/varios_unificar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfGlobal
                },
                body: JSON.stringify({
                    _csrf: csrfGlobal,
                    id_maestro: idMaestro,
                    nombre_oficial: nombreOficial,
                    ids_a_unificar: clusterActivo.all_ids
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast(data.data.mensaje || 'Variantes fusionadas con éxito', 'success');
                    } else {
                        alert(data.data.mensaje || 'Variantes fusionadas con éxito');
                    }
                    setTimeout(function() {
                        window.location.href = '?tab=similares';
                    }, 1200);
                } else {
                    btnConfirmarCluster.disabled = false;
                    btnConfirmarCluster.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Fusión y Eliminar Vacíos';
                    if (typeof showToast === 'function') {
                        showToast(data.error || 'Error al fusionar variantes', 'error');
                    } else {
                        alert(data.error || 'Error al fusionar variantes');
                    }
                }
            })
            .catch(err => {
                btnConfirmarCluster.disabled = false;
                btnConfirmarCluster.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Fusión y Eliminar Vacíos';
                console.error(err);
                if (typeof showToast === 'function') {
                    showToast('Error de comunicación con el servidor', 'error');
                } else {
                    alert('Error de comunicación con el servidor');
                }
            });
        });
    }

    // 4. Botón rápido de unificar desde la tabla del catálogo
    document.querySelectorAll('.btn-abrir-unificar-directo').forEach(btn => {
        btn.addEventListener('click', function() {
            const nombre = this.getAttribute('data-nombre');
            window.location.href = '?tab=comparativa&item=' + encodeURIComponent(nombre.toLowerCase()) + '#modalUnificarStock';
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
