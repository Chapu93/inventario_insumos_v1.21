<?php
require_once __DIR__ . '/../../includes/config.php';
requerirAutenticacion();
verificarPermiso('reportes', 'ver');

// Aceptar ?remito=NUM_REMITO (todas las devoluciones del remito de entrega)
// o ?devolucion=NUM_DEV (una devolución específica)
$param_remito = trim((string)($_GET['remito'] ?? ''));
$numero_devolucion = (isset($_GET['devolucion']) && $_GET['devolucion'] !== '') ? $_GET['devolucion'] :
    ((isset($_GET['numero']) && $_GET['numero'] !== '') ? $_GET['numero'] :
    ((isset($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : ''));

if ($param_remito === '' && $numero_devolucion === '') {
    http_response_code(400);
    echo 'Número de remito de entrega o remito de devolución requerido';
    exit;
}

$conexion = conectarDB();

if ($param_remito !== '') {
    // Buscar todas las devoluciones asociadas al remito de entrega original
    $sqlCab = "SELECT rd.id_remito_devolucion, rd.numero_devolucion, rd.fecha_devolucion, 
                      rd.persona_entrega, rd.observaciones, rd.remito_firmado, rd.estado,
                      r.id_remito AS id_remito_origen, r.numero_remito AS numero_remito_origen, r.fecha_asignacion AS fecha_entrega_origen,
                      r.nombre_persona_asignada, r.apellido_persona_asignada,
                      ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona,
                      COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))), ''), u.username, 'Soporte Técnico') AS nombre_receptor
               FROM remitos_devolucion rd
               JOIN remitos r ON rd.id_remito_origen = r.id_remito
               JOIN sedes s ON r.id_sede = s.id_sede
               JOIN localidades l ON s.id_localidad = l.id_localidad
               JOIN zonas z ON l.id_zona = z.id_zona
               LEFT JOIN areas ar ON r.id_area = ar.id_area
               LEFT JOIN usuarios u ON rd.id_usuario_receptor = u.id_usuario
               WHERE r.numero_remito = ? OR r.id_remito = ?
               ORDER BY rd.id_remito_devolucion ASC";
    $stmt = $conexion->prepare($sqlCab);
    $stmt->execute([$param_remito, is_numeric($param_remito) ? (int)$param_remito : 0]);
    $listaDevoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Buscar una devolución específica
    $sqlCab = "SELECT rd.id_remito_devolucion, rd.numero_devolucion, rd.fecha_devolucion, 
                      rd.persona_entrega, rd.observaciones, rd.remito_firmado, rd.estado,
                      r.id_remito AS id_remito_origen, r.numero_remito AS numero_remito_origen, r.fecha_asignacion AS fecha_entrega_origen,
                      r.nombre_persona_asignada, r.apellido_persona_asignada,
                      ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona,
                      COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))), ''), u.username, 'Soporte Técnico') AS nombre_receptor
               FROM remitos_devolucion rd
               JOIN remitos r ON rd.id_remito_origen = r.id_remito
               JOIN sedes s ON r.id_sede = s.id_sede
               JOIN localidades l ON s.id_localidad = l.id_localidad
               JOIN zonas z ON l.id_zona = z.id_zona
               LEFT JOIN areas ar ON r.id_area = ar.id_area
               LEFT JOIN usuarios u ON rd.id_usuario_receptor = u.id_usuario
               WHERE " . (is_numeric($numero_devolucion) ? "rd.id_remito_devolucion = ?" : "rd.numero_devolucion = ?") . "
               LIMIT 1";
    $stmt = $conexion->prepare($sqlCab);
    $stmt->execute([$numero_devolucion]);
    $listaDevoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($listaDevoluciones)) {
    http_response_code(404);
    echo 'Remito(s) de devolución no encontrado(s)';
    exit;
}

// Detalle de ítems devueltos
$sqlDet = "SELECT 
                i.id_insumo,
                i.nombre_insumo,
                i.tipo_insumo,
                i.numero_serie,
                i.id_fisico,
                d.cantidad,
                d.destino,
                d.motivo_baja,
                d.estado_fisico,
                COALESCE(nb.marca, imp.marca, mon.marca, esc.marca) AS marca,
                COALESCE(nb.modelo, imp.modelo, mon.modelo, esc.modelo) AS modelo,
                pc.procesador AS pc_procesador,
                pc.ram_gb     AS pc_ram,
                pc.almacenamiento_gb AS pc_alm,
                pc.almacenamiento_secundario_gb AS pc_alm_sec,
                pc.ssd_o_superior AS pc_ssd,
                pc.ssd_secundario AS pc_ssd_sec,
                pc.mother     AS pc_mother,
                pc.sist_op    AS pc_sist_op,
                nb.procesador AS nb_procesador,
                nb.ram_gb     AS nb_ram,
                nb.almacenamiento_gb AS nb_alm,
                nb.ssd_o_superior AS nb_ssd,
                nb.cargador, nb.funda, nb.micro_sd, nb.micro_sd_gb, nb.caja, nb.adaptador_red
           FROM remitos_devolucion_detalle d
           JOIN insumos i ON d.id_insumo = i.id_insumo
           LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
           LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
           LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
           LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
           LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
           WHERE d.id_remito_devolucion = ?
           ORDER BY i.nombre_insumo";
$stmtDet = $conexion->prepare($sqlDet);

// Inicializar PDF usando FPDI para membrete institucional
$pdf = new \setasign\Fpdi\Fpdi();
$templateLoaded = false;
$templatePath = __DIR__ . '/../../membretada.pdf';
$TPL_ID = null;
$TPL_SIZE = null;

if (file_exists($templatePath)) {
    try {
        $pdf->setSourceFile($templatePath);
        $TPL_ID = $pdf->importPage(1);
        $TPL_SIZE = $pdf->getTemplateSize($TPL_ID);
        $templateLoaded = true;
    } catch (Throwable $e) {
        Logger::error('No se pudo cargar plantilla PDF de devolución', ['error' => $e->getMessage()]);
    }
}

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

// Márgenes y medidas idénticas a remito_pdf.php
$leftMargin = 15;
$rightMargin = 15;
$extraRight = $pdf->GetStringWidth('000000');
if (is_numeric($extraRight) && $extraRight > 0) {
    $rightMargin += $extraRight;
}
$extraLeft = $pdf->GetStringWidth('0000');
if (is_numeric($extraLeft) && $extraLeft > 0) {
    $leftMargin += $extraLeft;
}
$topMargin = 15;
$lineHeight = 6;
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();
$contentWidth = $pageWidth - $leftMargin - $rightMargin;

$envHeaderOffset = getenv('REMITO_PDF_HEADER_OFFSET_MM');
$headerOffset = $templateLoaded ? (is_numeric($envHeaderOffset) ? (float) $envHeaderOffset : 30.0) : 0.0;

$y = $topMargin + $headerOffset;

$enc = function ($s) {
    if ($s === null) return '';
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $s);
    return ($out === false) ? utf8_decode((string) $s) : $out;
};

foreach ($listaDevoluciones as $cab) {
    if ($templateLoaded && $TPL_ID !== null) {
        $pdf->AddPage($TPL_SIZE['orientation'], [$TPL_SIZE['width'], $TPL_SIZE['height']]);
        $pdf->useTemplate($TPL_ID);
    } else {
        $pdf->AddPage('P', 'A4');
    }

    $y = $topMargin + $headerOffset;

    // Título centrado
    $y_titulo = $y - 8;
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY($leftMargin, $y_titulo);
    $pdf->Cell($contentWidth, 6, $enc('REMITO DE DEVOLUCIÓN DE INSUMOS'), 0, 1, 'C');
    $y = $pdf->GetY() + 2;

    // Número y Fecha
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell($contentWidth / 2, 6, $enc('Número: ' . $cab['numero_devolucion']), 0, 0, 'L');
    $pdf->SetXY($leftMargin + $contentWidth / 2, $y);
    $pdf->Cell($contentWidth / 2, 6, $enc('Fecha: ' . date('d/m/Y', strtotime($cab['fecha_devolucion']))), 0, 1, 'R');

    // Referencia al remito original de entrega
    $y += $lineHeight;
    $pdf->SetXY($leftMargin, $y);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($contentWidth, 5, $enc('Remito de Entrega N° ' . $cab['numero_remito_origen']), 0, 1, 'L');

    $y += (1.5 * $lineHeight);

    // Bloque de datos en dos columnas: Entrega/Recepción (izq) y Procedencia (der)
    $colGap = 6;
    $colWidth = ($contentWidth - $colGap) / 2;

    // Columna Izquierda: Persona que Entrega y Receptor
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell($colWidth, 6, $enc('Recepción'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $yLeft = $y + 7;
    $pdf->SetXY($leftMargin, $yLeft);

    $personaEntrega = !empty(trim($cab['persona_entrega'] ?? '')) ? trim($cab['persona_entrega']) : ($cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada']);
    $datosRecepcion = 'Entregado por: ' . $personaEntrega . "\n" .
                      'Recibido por: ' . ($cab['nombre_receptor'] ?: 'Área Técnica SITIA');
    $pdf->MultiCell($colWidth, 6, $enc($datosRecepcion), 0, 'L');
    $yLeftFin = $pdf->GetY();

    // Columna Derecha: Procedencia (Sede, Localidad, Área)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($leftMargin + $colWidth + $colGap, $y);
    $pdf->Cell($colWidth, 6, $enc('Procedencia'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin + $colWidth + $colGap, $y + 7);
    $procedenciaTexto = 'Sede: ' . ($cab['nombre_sede'] ?: '-') . "\n" .
                        'Área: ' . ($cab['nombre_area'] ?: '-') . "\n" .
                        'Localidad: ' . ($cab['nombre_localidad'] ?: '-') . "\n" .
                        'Zona: ' . ($cab['nombre_zona'] ?: '-');
    $pdf->MultiCell($colWidth, 6, $enc($procedenciaTexto), 0, 'L');
    $yRightFin = $pdf->GetY();

    $y = max($yLeftFin, $yRightFin) + 6;

    // Lista de insumos devueltos en 2 columnas (mismo formato que remito_pdf.php)
    $pdf->SetXY($leftMargin, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell($contentWidth, 6, $enc('Insumos Devueltos:'), 0, 1, 'L');
    $y = $pdf->GetY() + 2;

    $offsetTresCaracteres = $pdf->GetStringWidth('000');
    $cols = 2;
    $colPad = 6;
    $colW = ($contentWidth - ($colPad * ($cols - 1))) / $cols;
    $xStart = $leftMargin + $offsetTresCaracteres;
    $yStart = $y;
    $colHeights = array_fill(0, $cols, $yStart);
    $colIndex = 0;

    $stmtDet->execute([$cab['id_remito_devolucion']]);
    $items = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $it) {
        $x = $xStart + ($colIndex * ($colW + $colPad));
        $yCol = $colHeights[$colIndex];
        $pdf->SetXY($x, $yCol);

        $pdf->SetFont('Arial', 'B', 10);
        $titulo = ($it['tipo_insumo'] === 'Varios') ? ($it['nombre_insumo'] ?: 'Varios') : ($it['tipo_insumo'] ?: '');
        $pdf->MultiCell($colW, 5, $enc($titulo), 0, 'L');
        $yCol = $pdf->GetY();

        $pdf->SetFont('Arial', '', 10);
        $bullets = [];
        $bullets[] = '- Cantidad devuelta: ' . (isset($it['cantidad']) ? (int) $it['cantidad'] : 1);
        if (!empty($it['marca'])) $bullets[] = '- Marca: ' . $it['marca'];
        if (!empty($it['modelo'])) $bullets[] = '- Modelo: ' . $it['modelo'];
        if (!empty($it['numero_serie'])) $bullets[] = '- Nro. de serie: ' . $it['numero_serie'];
        if (!empty($it['id_fisico'])) $bullets[] = '- ID físico: ' . str_replace(['-', ' '], '', $it['id_fisico']);

        // Especificaciones técnicas para PC y Notebooks
        if ($it['tipo_insumo'] === 'PC Completa' || $it['tipo_insumo'] === 'PC Escritorio') {
            if (!empty($it['pc_procesador'])) $bullets[] = '- Proc.: ' . $it['pc_procesador'];
            if (!empty($it['pc_ram'])) $bullets[] = '- RAM: ' . $it['pc_ram'] . ' GB';
            if (!empty($it['pc_alm'])) {
                $tipo1 = !empty($it['pc_ssd']) ? 'SSD' : 'HDD';
                $cap1 = ((int)$it['pc_alm'] >= 1024 && (int)$it['pc_alm'] % 1024 === 0) ? ((int)$it['pc_alm'] / 1024) . ' TB' : $it['pc_alm'] . ' GB';
                $textoAlm = '- Almacenamiento: ' . $cap1 . " ($tipo1)";
                if (!empty($it['pc_alm_sec'])) {
                    $tipo2 = !empty($it['pc_ssd_sec']) ? 'SSD' : 'HDD';
                    $cap2 = ((int)$it['pc_alm_sec'] >= 1024 && (int)$it['pc_alm_sec'] % 1024 === 0) ? ((int)$it['pc_alm_sec'] / 1024) . ' TB' : $it['pc_alm_sec'] . ' GB';
                    $textoAlm .= ' + ' . $cap2 . " ($tipo2)";
                }
                $bullets[] = $textoAlm;
            }
            if (!empty($it['pc_mother'])) $bullets[] = '- Mother: ' . $it['pc_mother'];
        } elseif ($it['tipo_insumo'] === 'Notebook') {
            if (!empty($it['nb_procesador'])) $bullets[] = '- Proc.: ' . $it['nb_procesador'];
            if (!empty($it['nb_ram'])) $bullets[] = '- RAM: ' . $it['nb_ram'] . ' GB';
            if (!empty($it['nb_alm'])) {
                $tipoNb = !empty($it['nb_ssd']) ? 'SSD' : 'HDD';
                $capNb = ((int)$it['nb_alm'] >= 1024 && (int)$it['nb_alm'] % 1024 === 0) ? ((int)$it['nb_alm'] / 1024) . ' TB' : $it['nb_alm'] . ' GB';
                $bullets[] = '- Almacenamiento: ' . $capNb . " ($tipoNb)";
            }
            $acc = [];
            if (!empty($it['cargador'])) $acc[] = 'Cargador';
            if (!empty($it['funda'])) $acc[] = 'Funda';
            if (!empty($it['caja'])) $acc[] = 'Caja';
            if (!empty($acc)) $bullets[] = '- Accesorios devueltos: ' . implode(', ', $acc);
        }

        foreach ($bullets as $line) {
            $pdf->SetXY($x + 2, $yCol);
            $pdf->MultiCell($colW - 2, 5, $enc($line), 0, 'L');
            $yCol = $pdf->GetY();
        }
        $yCol += 3;
        $colHeights[$colIndex] = $yCol;
        $colIndex = ($colIndex + 1) % $cols;
    }

    $y = max($colHeights) + 5;

    // Observaciones generales
    if (!empty(trim((string)$cab['observaciones']))) {
        if ($y > $pageHeight - 55) {
            $pdf->AddPage();
            if ($templateLoaded && $TPL_ID !== null) { $pdf->useTemplate($TPL_ID); }
            $y = $topMargin + $headerOffset;
        }
        $pdf->SetXY($leftMargin, $y);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell($contentWidth, 6, $enc('Observaciones / Motivo:'), 0, 1, 'L');
        $pdf->SetX($leftMargin);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell($contentWidth, 5, $enc($cab['observaciones']), 0, 'L');
        $y = $pdf->GetY() + 5;
    }

    // Sello de ANULADO si aplica
    if (isset($cab['estado']) && strcasecmp($cab['estado'], 'Anulado') === 0) {
        $selloPath = __DIR__ . '/../../public/img/sello_anulado_clean.png';
        if (!file_exists($selloPath)) {
            $selloPath = __DIR__ . '/../../public/img/sello_anulado.png';
        }
        if (file_exists($selloPath)) {
            $pdf->Image($selloPath, 45, 85, 120);
        }
    }

    // Doble casillero de firmas: Quien entrega (izq) y Quien recibe en depósito (der)
    $lineasDesdeFin = 7;
    $firmaY = $y + ($lineasDesdeFin * $lineHeight);
    if ($firmaY > $pageHeight - 25) {
        $pdf->AddPage();
        if ($templateLoaded && $TPL_ID !== null) { $pdf->useTemplate($TPL_ID); }
        $firmaY = $topMargin + $headerOffset + 50;
    }

    $firmaBoxWidth = ($contentWidth - 20) / 2;
    $lineaFirmaWidth = 65;

    // Firma Entrega
    $x1 = $leftMargin + ($firmaBoxWidth - $lineaFirmaWidth) / 2;
    $pdf->Line($x1, $firmaY, $x1 + $lineaFirmaWidth, $firmaY);
    $pdf->SetXY($leftMargin, $firmaY + 2);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell($firmaBoxWidth, 5, $enc("Firma y Aclaración\nQuien Entrega"), 0, 'C');

    // Firma Recepción
    $x2Start = $leftMargin + $firmaBoxWidth + 20;
    $x2 = $x2Start + ($firmaBoxWidth - $lineaFirmaWidth) / 2;
    $pdf->Line($x2, $firmaY, $x2 + $lineaFirmaWidth, $firmaY);
    $pdf->SetXY($x2Start, $firmaY + 2);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell($firmaBoxWidth, 5, $enc("Firma y Sello\nRecepción Soporte Técnico"), 0, 'C');
}

// Salida del documento PDF
$nombrePdf = (count($listaDevoluciones) > 1) 
    ? 'Remitos_Devolucion_' . $listaDevoluciones[0]['numero_remito_origen'] . '.pdf'
    : 'Remito_Devolucion_' . $listaDevoluciones[0]['numero_devolucion'] . '.pdf';
$pdf->Output('I', $nombrePdf);
exit;
