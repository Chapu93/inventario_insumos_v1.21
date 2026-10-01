<?php
/**
 * Análisis Exhaustivo de Duplicados sobre el Backup de Producción (inventario_analisis)
 */

$pdo = new PDO('mysql:host=localhost;dbname=inventario_analisis;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

echo "========================================================================\n";
echo "ANÁLISIS EXHAUSTIVO DE DUPLICADOS EN PRODUCCIÓN (backup_inventario_modif)\n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// ESTRATEGIA 1: IDENTIFICADORES FÍSICOS NORMALIZADOS (SERIES Y PATRIMONIOS)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "ESTRATEGIA 1: IDENTIFICADORES FÍSICOS NORMALIZADOS\n";
echo "------------------------------------------------------------------------\n";

// 1.1 Números de Serie
$sqlSerie = "
    SELECT 
        LOWER(REGEXP_REPLACE(numero_serie, '[^a-zA-Z0-9]', '')) AS serie_norm,
        COUNT(*) as total,
        GROUP_CONCAT(id_insumo ORDER BY id_insumo ASC) as ids,
        GROUP_CONCAT(DISTINCT tipo_insumo) as tipos,
        GROUP_CONCAT(DISTINCT numero_serie SEPARATOR ' | ') as series_originales,
        GROUP_CONCAT(COALESCE(nombre_insumo, tipo_insumo) SEPARATOR ' || ') as nombres
    FROM insumos 
    WHERE numero_serie IS NOT NULL 
      AND TRIM(numero_serie) != '' 
      AND UPPER(TRIM(numero_serie)) NOT IN ('S/N', 'SN', 'SIN SERIE', '0', 'NO TIENE', 'NO POSEE', 'SD', 'S/D', '-', '.')
    GROUP BY serie_norm
    HAVING total > 1 AND LENGTH(serie_norm) >= 4
    ORDER BY total DESC, serie_norm ASC;
";
$stmt = $pdo->query($sqlSerie);
$seriesDuplicadas = $stmt->fetchAll();

echo "[1.1] Números de Serie Idénticos o Normalizados: " . count($seriesDuplicadas) . " grupos encontrados\n";
foreach ($seriesDuplicadas as $sd) {
    echo "  -> Serie: '{$sd['serie_norm']}' (Total: {$sd['total']} registros)\n";
    echo "     IDs: [{$sd['ids']}] | Tipos: {$sd['tipos']}\n";
    echo "     Originales: {$sd['series_originales']}\n";
    echo "     Insumos: {$sd['nombres']}\n\n";
}

// 1.2 Patrimonios Normalizados
$sqlPatri = "
    SELECT 
        LOWER(REGEXP_REPLACE(id_patrimonio, '[^a-zA-Z0-9]', '')) AS patri_norm,
        COUNT(*) as total,
        GROUP_CONCAT(id_insumo ORDER BY id_insumo ASC) as ids,
        GROUP_CONCAT(DISTINCT tipo_insumo) as tipos,
        GROUP_CONCAT(DISTINCT id_patrimonio SEPARATOR ' | ') as patrimonios_originales,
        GROUP_CONCAT(COALESCE(nombre_insumo, tipo_insumo) SEPARATOR ' || ') as nombres
    FROM insumos 
    WHERE id_patrimonio IS NOT NULL 
      AND TRIM(id_patrimonio) != '' 
      AND UPPER(TRIM(id_patrimonio)) NOT IN ('0', 'S/P', 'SP', 'SIN PATRIMONIO', 'NO TIENE', 'NO POSEE', 'SD', 'S/D', '-', '.')
    GROUP BY patri_norm
    HAVING total > 1 AND LENGTH(patri_norm) >= 3
    ORDER BY total DESC;
";
$stmt = $pdo->query($sqlPatri);
$patrisDuplicados = $stmt->fetchAll();

echo "[1.2] Patrimonios Idénticos o Normalizados: " . count($patrisDuplicados) . " grupos encontrados\n";
foreach ($patrisDuplicados as $pd) {
    echo "  -> Patrimonio: '{$pd['patri_norm']}' (Total: {$pd['total']} registros)\n";
    echo "     IDs: [{$pd['ids']}] | Tipos: {$pd['tipos']}\n";
    echo "     Originales: {$pd['patrimonios_originales']}\n\n";
}

// 1.3 Cruce ID Físico vs ID Patrimonio
$sqlFisicoPatri = "
    SELECT 
        i1.id_insumo as id_1, i1.tipo_insumo as tipo_1, i1.id_fisico as valor_cruzado,
        i2.id_insumo as id_2, i2.tipo_insumo as tipo_2, i2.id_patrimonio
    FROM insumos i1
    JOIN insumos i2 ON i1.id_fisico = i2.id_patrimonio AND i1.id_insumo != i2.id_insumo
    WHERE i1.id_fisico IS NOT NULL AND TRIM(i1.id_fisico) != ''
      AND UPPER(TRIM(i1.id_fisico)) NOT IN ('0', 'S/P', 'S/N', '-', '.')
    LIMIT 10;
";
$stmt = $pdo->query($sqlFisicoPatri);
$cruceFisico = $stmt->fetchAll();
echo "[1.3] Cruce ID Físico == ID Patrimonio: " . count($cruceFisico) . " coincidencias encontradas\n";
foreach ($cruceFisico as $cf) {
    echo "  -> Insumo ID {$cf['id_1']} (Físico: '{$cf['valor_cruzado']}') coincide con Insumo ID {$cf['id_2']} (Patrimonio: '{$cf['id_patrimonio']}')\n";
}
echo "\n";


// -----------------------------------------------------------------------------
// ESTRATEGIA 2: INVARIANZA DE TOKENS / ORDEN DE PALABRAS EN TIPO "VARIOS"
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "ESTRATEGIA 2: INVARIANZA DE TOKENS / ORDEN DE PALABRAS EN 'VARIOS'\n";
echo "------------------------------------------------------------------------\n";

$sqlVarios = "SELECT id_insumo, nombre_insumo, cantidad, estado FROM insumos WHERE tipo_insumo = 'Varios'";
$stmt = $pdo->query($sqlVarios);
$todosVarios = $stmt->fetchAll();

// Normalizador de bolsa de palabras
function normalizarTokens($nombre) {
    $n = mb_strtolower(trim($nombre), 'UTF-8');
    // Quitar acentos
    $n = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $n);
    // Quitar caracteres especiales
    $n = preg_replace('/[^a-z0-9]/', ' ', $n);
    $tokens = array_filter(explode(' ', $n), function($t) {
        // Ignorar palabras de enlace triviales
        return strlen($t) > 1 && !in_array($t, ['de', 'para', 'con', 'el', 'la', 'los', 'las', 'un', 'una', 'en']);
    });
    sort($tokens);
    return implode(' ', $tokens);
}

$gruposTokens = [];
foreach ($todosVarios as $v) {
    $tok = normalizarTokens($v['nombre_insumo']);
    if (empty($tok)) continue;
    $gruposTokens[$tok][] = $v;
}

$duplicadosTokens = array_filter($gruposTokens, function($g) {
    if (count($g) < 2) return false;
    // Verificar que al menos dos nombres originales sean diferentes (para encontrar los que el orden cambia o pequeñas variaciones)
    $nombres = array_unique(array_map(function($x) { return mb_strtolower(trim($x['nombre_insumo']), 'UTF-8'); }, $g));
    return count($nombres) > 1;
});

echo "Total grupos con palabras en diferente orden o variantes de formato: " . count($duplicadosTokens) . "\n";
$i = 0;
foreach ($duplicadosTokens as $tok => $items) {
    $i++;
    if ($i > 15) { echo "  ... (y " . (count($duplicadosTokens) - 15) . " grupos más)\n"; break; }
    echo "  -> Bolsa de tokens: [{$tok}]\n";
    foreach ($items as $it) {
        echo "     * ID {$it['id_insumo']}: '{$it['nombre_insumo']}' (Cant: {$it['cantidad']}, Estado: {$it['estado']})\n";
    }
    echo "\n";
}


// -----------------------------------------------------------------------------
// ESTRATEGIA 3: CRUCE RELEVAMIENTO VS REMITO HISTÓRICO (DUPLICADOS FANTASMA)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "ESTRATEGIA 3: CRUCE RELEVAMIENTOS VS REMITOS ANTERIORES EN LA MISMA SEDE\n";
echo "------------------------------------------------------------------------\n";

$sqlRelevVsPrevio = "
    SELECT 
        s.nombre_sede,
        ar.nombre_area,
        r_hist.id_remito AS id_remito_relev,
        r_hist.numero_remito AS remito_relev,
        r_hist.fecha_asignacion AS fecha_relev,
        i_hist.id_insumo AS id_insumo_relev,
        i_hist.tipo_insumo,
        COALESCE(i_hist.nombre_insumo, i_hist.tipo_insumo) AS nombre_relev,
        r_prev.id_remito AS id_remito_previo,
        r_prev.numero_remito AS remito_previo,
        r_prev.fecha_asignacion AS fecha_previo,
        i_prev.id_insumo AS id_insumo_previo,
        COALESCE(i_prev.nombre_insumo, i_prev.tipo_insumo) AS nombre_previo
    FROM remitos r_hist
    JOIN remitos_detalle rd_hist ON r_hist.id_remito = rd_hist.id_remito
    JOIN insumos i_hist ON rd_hist.id_insumo = i_hist.id_insumo
    JOIN sedes s ON r_hist.id_sede = s.id_sede
    LEFT JOIN areas ar ON r_hist.id_area = ar.id_area
    JOIN remitos r_prev ON r_prev.id_sede = r_hist.id_sede 
                       AND (r_prev.id_area = r_hist.id_area OR r_hist.id_area IS NULL OR r_prev.id_area IS NULL)
                       AND r_prev.fecha_asignacion < r_hist.fecha_asignacion
                       AND r_prev.estado = 'Activa'
    JOIN remitos_detalle rd_prev ON r_prev.id_remito = rd_prev.id_remito
    JOIN insumos i_prev ON rd_prev.id_insumo = i_prev.id_insumo 
                       AND i_prev.tipo_insumo = i_hist.tipo_insumo
                       AND i_prev.id_insumo != i_hist.id_insumo
    WHERE (r_hist.observaciones LIKE '%relevamiento%' OR r_hist.numero_remito LIKE '%hist%')
      AND r_hist.estado = 'Activa'
      AND i_hist.tipo_insumo IN ('PC Escritorio', 'Notebook', 'Impresora', 'Monitor', 'Escaner')
    GROUP BY i_hist.id_insumo, i_prev.id_insumo
    ORDER BY s.nombre_sede ASC, i_hist.tipo_insumo ASC
    LIMIT 30;
";
$stmt = $pdo->query($sqlRelevVsPrevio);
$fantasmas = $stmt->fetchAll();

echo "Equipos de hardware que ya estaban en remito previo y se volvieron a cargar en relevamiento: " . count($fantasmas) . "\n";
$j = 0;
foreach ($fantasmas as $f) {
    $j++;
    if ($j > 10) { echo "  ... (y más coincidencias)\n"; break; }
    echo "  -> Sede: {$f['nombre_sede']} (" . ($f['nombre_area'] ?: 'Área General') . ")\n";
    echo "     * Tipo: {$f['tipo_insumo']}\n";
    echo "     * Remito Previo: #{$f['remito_previo']} ({$f['fecha_previo']}) -> Insumo ID {$f['id_insumo_previo']} ('{$f['nombre_previo']}')\n";
    echo "     * Carga Relevamiento: #{$f['remito_relev']} ({$f['fecha_relev']}) -> Insumo ID {$f['id_insumo_relev']} ('{$f['nombre_relev']}')\n\n";
}


// -----------------------------------------------------------------------------
// ESTRATEGIA 4: HUELLA TÉCNICA IDÉNTICA EN PCS (MISMA SEDE Y ESPECIFICACIONES)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "ESTRATEGIA 4: HUELLA TÉCNICA IDÉNTICA EN PCS (MISMA SEDE Y SPECS)\n";
echo "------------------------------------------------------------------------\n";

$sqlHuellaPc = "
    SELECT 
        s.nombre_sede,
        ar.nombre_area,
        p.mother,
        p.procesador,
        p.ram_gb,
        p.almacenamiento_gb,
        COUNT(DISTINCT i.id_insumo) as total_pcs,
        GROUP_CONCAT(i.id_insumo ORDER BY i.id_insumo ASC) as ids,
        GROUP_CONCAT(DISTINCT COALESCE(i.numero_serie, 'S/N')) as series,
        GROUP_CONCAT(DISTINCT r.nombre_persona_asignada) as personas
    FROM pcs_completas p
    JOIN insumos i ON p.id_insumo = i.id_insumo
    LEFT JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
    LEFT JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
    LEFT JOIN sedes s ON r.id_sede = s.id_sede
    LEFT JOIN areas ar ON r.id_area = ar.id_area
    WHERE s.id_sede IS NOT NULL 
      AND p.mother IS NOT NULL AND p.mother != ''
      AND p.procesador IS NOT NULL AND p.procesador != ''
    GROUP BY s.id_sede, r.id_area, p.mother, p.procesador, p.ram_gb, p.almacenamiento_gb, r.nombre_persona_asignada
    HAVING total_pcs > 1
    ORDER BY total_pcs DESC
    LIMIT 15;
";
$stmt = $pdo->query($sqlHuellaPc);
$huellasPc = $stmt->fetchAll();

echo "Grupos de PCs con especificaciones idénticas asignadas a la misma persona o área: " . count($huellasPc) . "\n";
foreach ($huellasPc as $hp) {
    echo "  -> Sede: {$hp['nombre_sede']} | Área: " . ($hp['nombre_area'] ?: 'General') . " | Persona: {$hp['personas']}\n";
    echo "     Hardware: Mother: {$hp['mother']} | CPU: {$hp['procesador']} | RAM: {$hp['ram_gb']}GB | Almacenamiento: {$hp['almacenamiento_gb']}GB\n";
    echo "     Total PCs: {$hp['total_pcs']} (IDs: [{$hp['ids']}]) | Series: {$hp['series']}\n\n";
}

// -----------------------------------------------------------------------------
// ESTRATEGIA 5: IMPRESORAS Y MONITORES DUPLICADOS (MISMA SEDE Y MODELO)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "ESTRATEGIA 5: IMPRESORAS Y MONITORES DUPLICADOS (MISMA SEDE Y MODELO)\n";
echo "------------------------------------------------------------------------\n";

$sqlImp = "
    SELECT 
        s.nombre_sede,
        imp.marca,
        imp.modelo,
        COUNT(DISTINCT i.id_insumo) as total,
        GROUP_CONCAT(i.id_insumo) as ids,
        GROUP_CONCAT(DISTINCT COALESCE(i.numero_serie, 'S/N')) as series
    FROM impresoras imp
    JOIN insumos i ON imp.id_insumo = i.id_insumo
    LEFT JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
    LEFT JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
    LEFT JOIN sedes s ON r.id_sede = s.id_sede
    WHERE s.id_sede IS NOT NULL AND imp.modelo IS NOT NULL AND imp.modelo != ''
    GROUP BY s.id_sede, imp.marca, imp.modelo
    HAVING total > 1
    ORDER BY total DESC;
";
$stmt = $pdo->query($sqlImp);
$impsDuplicadas = $stmt->fetchAll();

echo "Grupos de Impresoras con misma marca y modelo en la misma Sede: " . count($impsDuplicadas) . "\n";
foreach ($impsDuplicadas as $imp) {
    echo "  -> Sede: {$imp['nombre_sede']} | {$imp['marca']} {$imp['modelo']}\n";
    echo "     Total: {$imp['total']} (IDs: [{$imp['ids']}]) | Series: {$imp['series']}\n\n";
}

$sqlMon = "
    SELECT 
        s.nombre_sede,
        m.marca,
        m.modelo,
        m.pulgadas,
        COUNT(DISTINCT i.id_insumo) as total,
        GROUP_CONCAT(i.id_insumo) as ids,
        GROUP_CONCAT(DISTINCT COALESCE(i.numero_serie, 'S/N')) as series
    FROM monitores m
    JOIN insumos i ON m.id_insumo = i.id_insumo
    LEFT JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
    LEFT JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
    LEFT JOIN sedes s ON r.id_sede = s.id_sede
    WHERE s.id_sede IS NOT NULL AND m.modelo IS NOT NULL AND m.modelo != ''
    GROUP BY s.id_sede, m.marca, m.modelo, m.pulgadas
    HAVING total > 1
    ORDER BY total DESC
    LIMIT 10;
";
$stmt = $pdo->query($sqlMon);
$monDuplicados = $stmt->fetchAll();

echo "Grupos de Monitores con misma marca, modelo y pulgadas en la misma Sede: " . count($monDuplicados) . "\n";
foreach ($monDuplicados as $mon) {
    echo "  -> Sede: {$mon['nombre_sede']} | {$mon['marca']} {$mon['modelo']} ({$mon['pulgadas']}\")\n";
    echo "     Total: {$mon['total']} (IDs: [{$mon['ids']}]) | Series: {$mon['series']}\n\n";
}

echo "========================================================================\n";
echo "FIN DEL ANÁLISIS\n";
echo "========================================================================\n";
