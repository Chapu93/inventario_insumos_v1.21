<?php
require_once '../../includes/config.php';

// 1. Verificar autenticación y permisos
requerirAutenticacion();

if (!tienePermiso('insumos', 'ver')) {
    $_SESSION['mensaje'] = 'No tienes permiso para acceder a esta acción';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

try {
    $db = conectarDB();

    // 2. Obtener y sanitizar filtros (vienen por GET)
    $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $filtroLocalidad = isset($_GET['id_localidad']) ? trim($_GET['id_localidad']) : '';
    $filtroSede = isset($_GET['id_sede']) ? trim($_GET['id_sede']) : '';
    $filtroCondicion = isset($_GET['es_nuevo']) ? trim($_GET['es_nuevo']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // 3. Determinar nombres de Localidad y Sede para el archivo
    $nombreLocalidadDesc = 'Todas';
    $nombreSedeDesc = 'Todas';

    if ($filtroLocalidad !== '') {
        $stmtLoc = $db->prepare("SELECT nombre_localidad FROM localidades WHERE id_localidad = ?");
        $stmtLoc->execute([$filtroLocalidad]);
        $resLoc = $stmtLoc->fetchColumn();
        if ($resLoc) {
            $nombreLocalidadDesc = trim($resLoc);
        }
    }

    if ($filtroSede !== '') {
        $stmtSed = $db->prepare("SELECT nombre_sede FROM sedes WHERE id_sede = ?");
        $stmtSed->execute([$filtroSede]);
        $resSed = $stmtSed->fetchColumn();
        if ($resSed) {
            $nombreSedeDesc = trim($resSed);
        }
    }

    // Normalizar nombres para el header de descarga
    $locClean = preg_replace('/[^a-zA-Z0-9_]/', '_', str_replace(' ', '_', $nombreLocalidadDesc));
    $sedClean = preg_replace('/[^a-zA-Z0-9_]/', '_', str_replace(' ', '_', $nombreSedeDesc));
    $fechaDescarga = date('Y-m-d');
    $nombreArchivo = "reporte_insumos_{$locClean}_{$sedClean}_{$fechaDescarga}.xls";

    // 4. Construir consulta SQL
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

    $baseJoins = "LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede
                  LEFT JOIN localidades l ON s.id_localidad = l.id_localidad";

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
            $like, $like, $like_id_fisico, $like,
            $like, $like, $like,
            $like, $like, $like,
            $like, $like,
            $like, $like,
            $like, $like,
            $like, // subcategoria_varios
            $like, // nombre_sede
            $like, // nombre_localidad
            $like, // numero_remito
            $like  // asignado
        );
    }

    if ($expandirAsignaciones) {
        $where[] = 'r.id_remito IS NOT NULL';
    }

    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    $groupBy = $expandirAsignaciones ? "GROUP BY i.id_insumo, r.id_remito" : "GROUP BY i.id_insumo";

    $dataSql = "SELECT i.id_insumo,
                       i.nombre_insumo,
                       i.tipo_insumo,
                       i.subcategoria_varios,
                       i.es_nuevo,
                       i.cantidad,
                       i.cantidad_oficina,
                       i.cantidad_deposito,
                       i.estado,
                       i.numero_serie,
                       i.id_fisico,
                       i.id_patrimonio,
                       l.nombre_localidad,
                       s.nombre_sede,
                       MAX(pc.sist_op) AS pc_sist_op,
                       MAX(pc.procesador) AS pc_procesador,
                       MAX(pc.ram_gb) AS pc_ram,
                       MAX(pc.almacenamiento_gb) AS pc_disco,
                       MAX(nb.marca) AS nb_marca,
                       MAX(nb.modelo) AS nb_modelo,
                       MAX(nb.procesador) AS nb_procesador,
                       MAX(nb.ram_gb) AS nb_ram,
                       MAX(nb.almacenamiento_gb) AS nb_disco,
                       MAX(imp.marca) AS imp_marca,
                       MAX(imp.modelo) AS imp_modelo,
                       MAX(mon.marca) AS mon_marca,
                       MAX(mon.modelo) AS mon_modelo,
                       MAX(esc.marca) AS esc_marca,
                       MAX(esc.modelo) AS esc_modelo,
                       MAX(r.numero_remito) AS numero_remito,
                       MAX(r.fecha_asignacion) AS fecha_asignacion,
                       MAX(r.nombre_persona_asignada) AS nombre_persona_asignada,
                       MAX(r.apellido_persona_asignada) AS apellido_persona_asignada,
                       MAX(rd.cantidad) AS asignada_en_remito,
                       MAX(rd.cantidad_devuelta) AS devuelta_en_remito,
                       (SELECT COALESCE(SUM(rd_sub.cantidad - rd_sub.cantidad_devuelta), 0)
                        FROM remitos_detalle rd_sub
                        JOIN remitos r_sub ON r_sub.id_remito = rd_sub.id_remito
                        WHERE rd_sub.id_insumo = i.id_insumo AND r_sub.estado = 'Activa') AS cantidad_asignada
                FROM insumos i
                $baseJoins $searchJoins
                $whereSql
                $groupBy
                ORDER BY i.nombre_insumo ASC";

    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Configurar headers HTTP para descarga
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    // Imprimir BOM para UTF-8 en Excel
    echo "\xEF\xBB\xBF";
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
            .text { mso-number-format:"\@"; } /* Formato de texto para Excel para evitar notacion cientifica */
            .header-cell {
                background-color: #2e7d32;
                color: #ffffff;
                font-weight: bold;
                border: 1px solid #1b5e20;
                text-align: center;
            }
            .data-cell {
                border: 1px solid #e0e0e0;
                vertical-align: middle;
            }
            .title-cell {
                font-size: 16px;
                font-weight: bold;
                color: #2e7d32;
                text-align: left;
            }
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td colspan="10" class="title-cell">REPORTE DE INVENTARIO DE INSUMOS</td>
            </tr>
            <tr>
                <td colspan="3"><b>Localidad del Reporte:</b> <?php echo htmlspecialchars($nombreLocalidadDesc); ?></td>
                <td colspan="7" style="text-align: right;"><b>Fecha de Exportación:</b> <?php echo htmlspecialchars(date('d/m/Y H:i')); ?></td>
            </tr>
            <tr>
                <td colspan="3"><b>Sede del Reporte:</b> <?php echo htmlspecialchars($nombreSedeDesc); ?></td>
                <td colspan="7" style="text-align: right;"><b>Filtros Activos:</b> <?php echo htmlspecialchars(trim(($filtroTipo ? "Tipo: $filtroTipo | " : "") . ($filtroEstado ? "Estado: $filtroEstado | " : "") . ($filtroCondicion !== '' ? ($filtroCondicion ? "Condición: Nuevo | " : "Condición: Usado | ") : "") . ($search ? "Búsqueda: $search | " : ""), " |")); ?></td>
            </tr>
            <tr><td colspan="10"></td></tr>
            <thead>
                <tr>
                    <th class="header-cell" style="width: 150px;">Localidad</th>
                    <th class="header-cell" style="width: 200px;">Sede</th>
                    <th class="header-cell" style="width: 200px;">Asignado A</th>
                    <th class="header-cell" style="width: 150px;">Fecha Asignación</th>
                    <th class="header-cell" style="width: 250px;">Insumo</th>
                    <th class="header-cell" style="width: 120px;">Tipo</th>
                    <th class="header-cell" style="width: 80px;">Cantidad</th>
                    <th class="header-cell" style="width: 150px;">Número de Serie</th>
                    <th class="header-cell" style="width: 120px;">ID Físico</th>
                    <th class="header-cell" style="width: 350px;">Detalles Técnicos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" class="data-cell" style="text-align: center; color: #757575; height: 40px;">No se encontraron insumos que coincidan con los filtros.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $tipo = (string)$r['tipo_insumo'];
                        $esPc = ($tipo === 'PC Escritorio' || $tipo === 'PC Completa');
                        $originalName = (string)$r['nombre_insumo'];
                        
                        // 1. Limpieza y formateo del nombre del Insumo sin datos de asignación
                        $displayName = $originalName;
                        if ($esPc) {
                            $sistOp = trim((string)$r['pc_sist_op']);
                            if ($sistOp !== '') {
                                $displayName = 'CPU/' . $sistOp;
                            }
                        } else if ($tipo !== 'Varios') {
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
                            }
                            $marca = trim((string)$marca);
                            $modelo = trim((string)$modelo);
                            if ($marca !== '' || $modelo !== '') {
                                $separator = ($marca !== '' && $modelo !== '') ? ' - ' : '';
                                $displayName = trim($marca . $separator . $modelo);
                            }
                        }

                        // 2. Persona Asignada (columna independiente)
                        $numeroRemito = $r['numero_remito'] ?? null;
                        $personaAsig = '-';
                        if ($r['estado'] === 'Asignado' && $numeroRemito) {
                            $personaAsig = trim(($r['nombre_persona_asignada'] ?? '') . ' ' . ($r['apellido_persona_asignada'] ?? ''));
                            if ($personaAsig === '') {
                                $personaAsig = 'Asignado s/n';
                            } else {
                                $personaAsig .= ' (Rem: ' . $numeroRemito . ')';
                            }
                        }

                        // 3. Cantidad formateada limpia
                        $cantidadVal = 1;
                        if ($tipo === 'Varios') {
                            if ($filtroEstado === 'Asignado' && $numeroRemito) {
                                $asignadaEnRemito = (int)($r['asignada_en_remito'] ?? 0);
                                $devueltaEnRemito = (int)($r['devuelta_en_remito'] ?? 0);
                                $cantidadVal = max(0, $asignadaEnRemito - $devueltaEnRemito);
                            } else {
                                $cantOficina = (int)($r['cantidad_oficina'] ?? $r['cantidad']);
                                $cantDeposito = (int)($r['cantidad_deposito'] ?? 0);
                                $cantidadVal = $cantOficina + $cantDeposito;
                            }
                        } else {
                            $cantidadVal = (int)$r['cantidad'];
                        }

                        // 4. Construcción de detalles técnicos específicos de hardware
                        $detallesTecnicos = '-';
                        if ($esPc) {
                            $proc = trim((string)$r['pc_procesador']);
                            $ram = trim((string)$r['pc_ram']);
                            $disco = trim((string)$r['pc_disco']);
                            $so = trim((string)$r['pc_sist_op']);
                            $detallesTecnicos = "Proc: " . ($proc !== '' ? $proc : '-') . " | RAM: " . ($ram !== '' ? $ram : '-') . " | Disco: " . ($disco !== '' ? $disco : '-') . " | SO: " . ($so !== '' ? $so : '-');
                        } else if ($tipo === 'Notebook') {
                            $proc = trim((string)$r['nb_procesador']);
                            $ram = trim((string)$r['nb_ram']);
                            $disco = trim((string)$r['nb_disco']);
                            $detallesTecnicos = "Proc: " . ($proc !== '' ? $proc : '-') . " | RAM: " . ($ram !== '' ? $ram : '-') . " | Disco: " . ($proc !== '' ? $disco : '-');
                        } else if ($tipo === 'Varios' && !empty($r['subcategoria_varios'])) {
                            $detallesTecnicos = "Subcategoría: " . trim((string)$r['subcategoria_varios']);
                        } else {
                            // Para Monitores, Impresoras, Escáneres podemos listar su modelo si no se detecta nada más
                            $marca = trim((string)($r['imp_marca'] ?? $r['mon_marca'] ?? $r['esc_marca'] ?? ''));
                            $modelo = trim((string)($r['imp_modelo'] ?? $r['mon_modelo'] ?? $r['esc_modelo'] ?? ''));
                            if ($marca !== '' || $modelo !== '') {
                                $detallesTecnicos = "Marca: " . ($marca !== '' ? $marca : '-') . " | Modelo: " . ($modelo !== '' ? $modelo : '-');
                            }
                        }

                        // 5. Ubicación (Localidad y Sede)
                        $locNombre = trim((string)$r['nombre_localidad']);
                        $sedNombre = trim((string)$r['nombre_sede']);

                        // 6. Fecha de Asignación
                        $fechaAsigStr = '-';
                        if ($r['estado'] === 'Asignado' && !empty($r['fecha_asignacion'])) {
                            $fechaAsigStr = date('d/m/Y', strtotime($r['fecha_asignacion']));
                        }
                        ?>
                        <tr>
                            <td class="data-cell"><?php echo htmlspecialchars($locNombre !== '' ? $locNombre : '-'); ?></td>
                            <td class="data-cell"><?php echo htmlspecialchars($sedNombre !== '' ? $sedNombre : '-'); ?></td>
                            <td class="data-cell"><?php echo htmlspecialchars($personaAsig); ?></td>
                            <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($fechaAsigStr); ?></td>
                            <td class="data-cell"><b><?php echo htmlspecialchars($displayName); ?></b></td>
                            <td class="data-cell" style="text-align: center;"><?php echo htmlspecialchars($tipo); ?></td>
                            <td class="data-cell" style="text-align: center;"><?php echo $cantidadVal; ?></td>
                            <td class="data-cell text" style="text-align: center;"><?php echo htmlspecialchars($r['numero_serie'] !== '' ? $r['numero_serie'] : '-'); ?></td>
                            <td class="data-cell text" style="text-align: center;"><?php echo htmlspecialchars($r['id_fisico'] !== '' ? $r['id_fisico'] : '-'); ?></td>
                            <td class="data-cell" style="font-size: 11px;"><?php echo htmlspecialchars($detallesTecnicos); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    Logger::error('Error al exportar insumos a Excel', ['error' => $e->getMessage()]);
    echo "<h3>Error al generar la exportación</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
