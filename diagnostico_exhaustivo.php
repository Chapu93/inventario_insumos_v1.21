<?php
$pdo = new PDO('mysql:host=localhost;dbname=inventario_analisis;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 1. REVISIÓN DETALLADA DE SERIES Y TYPOS (Levenshtein)
echo "=== 1. SERIES IDÉNTICAS O CON POSIBLES TYPOS (LEVENSHTEIN <= 2) ===\n";
$sqlSeries = "SELECT id_insumo, tipo_insumo, COALESCE(nombre_insumo, tipo_insumo) as nombre, numero_serie, estado 
              FROM insumos 
              WHERE numero_serie IS NOT NULL AND TRIM(numero_serie) != '' 
                AND UPPER(TRIM(numero_serie)) NOT IN ('S/N', 'SN', 'SIN SERIE', '0', 'NO TIENE', 'NO POSEE', 'SD', 'S/D', '-', '.')
              ORDER BY tipo_insumo, numero_serie";
$series = $pdo->query($sqlSeries)->fetchAll();

$typos = [];
$total = count($series);
for ($i = 0; $i < $total; $i++) {
    for ($j = $i + 1; $j < $total; $j++) {
        $a = $series[$i];
        $b = $series[$j];
        if ($a['tipo_insumo'] !== $b['tipo_insumo']) continue;

        $s1 = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $a['numero_serie']));
        $s2 = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $b['numero_serie']));
        if (strlen($s1) < 5 || strlen($s2) < 5) continue;

        // Si son exactamente iguales
        if ($s1 === $s2) {
            $typos[] = [
                'tipo' => 'EXACTO',
                'item1' => $a,
                'item2' => $b,
                'dist' => 0
            ];
            continue;
        }

        // Si la longitud difiere en más de 2 caracteres, saltar
        if (abs(strlen($s1) - strlen($s2)) > 2) continue;

        // Calcular distancia Levenshtein
        $dist = levenshtein($s1, $s2);
        if ($dist <= 2) {
            $typos[] = [
                'tipo' => 'TYPO_POSIBLE',
                'item1' => $a,
                'item2' => $b,
                'dist' => $dist
            ];
        }
    }
}

foreach ($typos as $t) {
    echo "[{$t['tipo']}] ({$t['item1']['tipo_insumo']}) Distancia: {$t['dist']}\n";
    echo "  - ID {$t['item1']['id_insumo']} ({$t['item1']['estado']}): Serie '{$t['item1']['numero_serie']}' | {$t['item1']['nombre']}\n";
    echo "  - ID {$t['item2']['id_insumo']} ({$t['item2']['estado']}): Serie '{$t['item2']['numero_serie']}' | {$t['item2']['nombre']}\n\n";
}

// 2. CRUCE ID FÍSICO VS ID PATRIMONIO
echo "\n=== 2. CRUCE ID FÍSICO VS ID PATRIMONIO ===\n";
$sqlCruce = "SELECT i1.id_insumo as id1, i1.tipo_insumo as tipo1, i1.id_fisico, i1.id_patrimonio as patri1, i1.estado as estado1,
                    i2.id_insumo as id2, i2.tipo_insumo as tipo2, i2.id_fisico as fisico2, i2.id_patrimonio, i2.estado as estado2
             FROM insumos i1
             JOIN insumos i2 ON i1.id_fisico = i2.id_patrimonio AND i1.id_insumo != i2.id_insumo
             WHERE i1.id_fisico IS NOT NULL AND TRIM(i1.id_fisico) != ''
               AND UPPER(TRIM(i1.id_fisico)) NOT IN ('0', 'S/P', 'S/N', '-', '.')";
$cruces = $pdo->query($sqlCruce)->fetchAll();
foreach ($cruces as $c) {
    echo "Coincidencia: Insumo {$c['id1']} [{$c['tipo1']}] Fisico='{$c['id_fisico']}' (Patr='{$c['patri1']}') vs Insumo {$c['id2']} [{$c['tipo2']}] Patrimonio='{$c['id_patrimonio']}' (Fisico='{$c['fisico2']}')\n";
}

// 3. SINÓNIMOS Y VARIACIONES EN TIPO 'VARIOS'
echo "\n=== 3. SINÓNIMOS Y VARIACIONES EN TIPO 'VARIOS' ===\n";
$sqlVarios = "SELECT id_insumo, nombre_insumo, cantidad, estado FROM insumos WHERE tipo_insumo = 'Varios' ORDER BY nombre_insumo";
$varios = $pdo->query($sqlVarios)->fetchAll();

// Diccionario de normalización de sinónimos
$reemplazos = [
    '/\bpatch\s*cord\b/i' => 'cable de red',
    '/\bpatchcord\b/i' => 'cable de red',
    '/\bfichas?\b/i' => 'conector',
    '/\bconectores?\b/i' => 'conector',
    '/\bplaca\s+de\s+red\b/i' => 'tarjeta de red',
    '/\badaptador\s+de\s+red\b/i' => 'tarjeta de red',
    '/\bauricular(es)?\b/i' => 'auricular',
    '/\bteclados?\b/i' => 'teclado',
    '/\bmouses?\b/i' => 'mouse',
    '/\braton(es)?\b/i' => 'mouse',
    '/\bdisco\s+solido\b/i' => 'ssd',
    '/\bdisco\s+ssd\b/i' => 'ssd',
    '/\bmemoria\s+ram\b/i' => 'ram',
    '/\bmemoria\b/i' => 'ram',
    '/\bcaja\s+x\s*\d+\b/i' => '',
    '/\bpack\s+x\s*\d+\b/i' => '',
    '/\bx\s*\d+\s*unidades\b/i' => '',
    '/\bmetros?\b/i' => 'mts',
    '/\bmetro\b/i' => 'mts',
    '/\bmt\b/i' => 'mts',
    '/\bm\b/i' => 'mts',
];

$gruposSinonimos = [];
foreach ($varios as $v) {
    $n = mb_strtolower(trim($v['nombre_insumo']), 'UTF-8');
    $n = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $n);
    foreach ($reemplazos as $pat => $rep) {
        $n = preg_replace($pat, $rep, $n);
    }
    // Quitar conectores
    $n = preg_replace('/[^a-z0-9]/', ' ', $n);
    $toks = array_filter(explode(' ', $n), function($t) {
        return strlen($t) > 1 && !in_array($t, ['de', 'para', 'con', 'el', 'la', 'los', 'las', 'un', 'una', 'en', 'por', 'tipo']);
    });
    sort($toks);
    $clave = implode(' ', $toks);
    if (!empty($clave)) {
        $gruposSinonimos[$clave][] = $v;
    }
}

$encontradosSinonimos = array_filter($gruposSinonimos, function($g) {
    if (count($g) < 2) return false;
    $nombres = array_unique(array_map(function($x) { return mb_strtolower(trim($x['nombre_insumo']), 'UTF-8'); }, $g));
    return count($nombres) > 1;
});

echo "Total grupos con nombres normalizados/sinónimos: " . count($encontradosSinonimos) . "\n";
foreach ($encontradosSinonimos as $clave => $items) {
    echo "  Clave normalizada: [{$clave}]\n";
    foreach ($items as $it) {
        echo "    * ID {$it['id_insumo']}: '{$it['nombre_insumo']}' | Cant: {$it['cantidad']} | Estado: {$it['estado']}\n";
    }
    echo "\n";
}

// 4. DUPLICADOS FANTASMA: RELEVAMIENTOS VS REMITOS HISTÓRICOS (Análisis detallado)
echo "\n=== 4. ANÁLISIS PROFUNDO DE DUPLICADOS FANTASMA (RELEVAMIENTO VS ANTERIOR) ===\n";
$sqlFantasmaDetalle = "
    SELECT 
        s.nombre_sede,
        COALESCE(ar.nombre_area, 'Sin Área') as area,
        i_prev.id_insumo AS id_prev,
        i_prev.tipo_insumo,
        COALESCE(i_prev.nombre_insumo, i_prev.tipo_insumo) as nombre_prev,
        COALESCE(i_prev.numero_serie, 'S/N') as serie_prev,
        r_prev.numero_remito as remito_prev,
        r_prev.fecha_asignacion as fecha_prev,
        r_prev.nombre_persona_asignada as persona_prev,
        i_rel.id_insumo AS id_rel,
        COALESCE(i_rel.nombre_insumo, i_rel.tipo_insumo) as nombre_rel,
        COALESCE(i_rel.numero_serie, 'S/N') as serie_rel,
        r_rel.numero_remito as remito_rel,
        r_rel.fecha_asignacion as fecha_rel,
        r_rel.nombre_persona_asignada as persona_rel
    FROM remitos r_rel
    JOIN remitos_detalle rd_rel ON r_rel.id_remito = rd_rel.id_remito
    JOIN insumos i_rel ON rd_rel.id_insumo = i_rel.id_insumo
    JOIN sedes s ON r_rel.id_sede = s.id_sede
    LEFT JOIN areas ar ON r_rel.id_area = ar.id_area
    JOIN remitos r_prev ON r_prev.id_sede = r_rel.id_sede
                       AND (r_prev.id_area = r_rel.id_area OR r_rel.id_area IS NULL OR r_prev.id_area IS NULL)
                       AND r_prev.fecha_asignacion < r_rel.fecha_asignacion
                       AND r_prev.estado = 'Activa'
    JOIN remitos_detalle rd_prev ON r_prev.id_remito = rd_prev.id_remito
    JOIN insumos i_prev ON rd_prev.id_insumo = i_prev.id_insumo
                       AND i_prev.tipo_insumo = i_rel.tipo_insumo
                       AND i_prev.id_insumo != i_rel.id_insumo
    WHERE (r_rel.observaciones LIKE '%relevamiento%' OR r_rel.numero_remito LIKE '%hist%')
      AND r_rel.estado = 'Activa'
      AND i_rel.tipo_insumo IN ('PC Escritorio', 'Notebook', 'Impresora')
    ORDER BY s.nombre_sede, i_rel.tipo_insumo, r_rel.fecha_asignacion
";
$stmt = $pdo->query($sqlFantasmaDetalle);
$fantasmasDetalle = $stmt->fetchAll();
echo "Total coincidencias encontradas: " . count($fantasmasDetalle) . "\n";
$mostrados = 0;
foreach ($fantasmasDetalle as $fd) {
    // Si tienen serie y difieren claramente (ambas válidas y distintas), tal vez sean dos equipos físicos reales en la misma oficina.
    $ambasTienenSerieValida = ($fd['serie_prev'] !== 'S/N' && $fd['serie_rel'] !== 'S/N' && strlen($fd['serie_prev']) > 4 && strlen($fd['serie_rel']) > 4);
    $seriesIgualesOTypos = ($ambasTienenSerieValida && levenshtein(strtoupper($fd['serie_prev']), strtoupper($fd['serie_rel'])) <= 3);
    
    // Si alguna es S/N o las series coinciden/se parecen
    if (!$ambasTienenSerieValida || $seriesIgualesOTypos) {
        $mostrados++;
        if ($mostrados <= 15) {
            echo "  [CASO SOSPECHOSO] Sede: {$fd['nombre_sede']} | Área: {$fd['area']}\n";
            echo "    * Tipo: {$fd['tipo_insumo']}\n";
            echo "    * PREVIO: Insumo #{$fd['id_prev']} ('{$fd['nombre_prev']}'), Serie: '{$fd['serie_prev']}', Remito: #{$fd['remito_prev']} ({$fd['fecha_prev']}), Asignado a: {$fd['persona_prev']}\n";
            echo "    * RELEV:  Insumo #{$fd['id_rel']} ('{$fd['nombre_rel']}'), Serie: '{$fd['serie_rel']}', Remito: #{$fd['remito_rel']} ({$fd['fecha_rel']}), Asignado a: {$fd['persona_rel']}\n\n";
        }
    }
}
echo "Total casos sospechosos (sin serie o con series coincidentes/similares): {$mostrados}\n";

