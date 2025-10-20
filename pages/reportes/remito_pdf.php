<?php
require_once '../../includes/config.php';
use setasign\Fpdi\Fpdi;

// Aceptar remito | numero | numero_remito
$numero_remito =
    (isset($_GET['remito']) && $_GET['remito'] !== '') ? $_GET['remito'] :
    ((isset($_GET['numero']) && $_GET['numero'] !== '') ? $_GET['numero'] :
    ((isset($_GET['numero_remito']) && $_GET['numero_remito'] !== '') ? $_GET['numero_remito'] : ''));

if ($numero_remito === '') {
    http_response_code(400);
    echo 'Número de remito requerido';
    exit;
}

$conexion = conectarDB();

// Cabecera: intentar nuevo esquema
$stmt = $conexion->prepare("SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada,
                                   ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona, r.observaciones
                            FROM remitos r
                            JOIN sedes s ON r.id_sede = s.id_sede
                            JOIN localidades l ON s.id_localidad = l.id_localidad
                            JOIN zonas z ON l.id_zona = z.id_zona
                            JOIN areas ar ON r.id_area = ar.id_area
                            WHERE r.numero_remito = ?
                            LIMIT 1");
$stmt->execute([$numero_remito]);
$cab = $stmt->fetch();

// Detalle
$items = [];
if ($cab) {
    $stmtDet = $conexion->prepare("SELECT 
                                        i.nombre_insumo,
                                        i.tipo_insumo,
                                        i.numero_serie,
                                        i.id_fisico,
                                        d.cantidad,
                                        COALESCE(nb.marca, imp.marca, mon.marca, esc.marca) AS marca,
                                        COALESCE(nb.modelo, imp.modelo, mon.modelo, esc.modelo) AS modelo,
                                        NULL AS accesorios,
                                        CONCAT_WS(' ', 
                                          CASE WHEN i.tipo_insumo IN ('PC Completa','Notebook') THEN CONCAT('Proc:', NULLIF(nb.procesador,'')) END,
                                          CASE WHEN i.tipo_insumo IN ('PC Completa','Notebook') THEN CONCAT('RAM:', NULLIF(nb.ram_gb,'')) END,
                                          CASE WHEN i.tipo_insumo IN ('PC Completa','Notebook') THEN CONCAT('Alm:', NULLIF(nb.almacenamiento_gb,'')) END
                                        ) AS especificaciones
                                   FROM remitos_detalle d
                                   JOIN insumos i ON d.id_insumo = i.id_insumo
                                   JOIN remitos r ON r.id_remito = d.id_remito
                                   LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
                                   LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
                                   LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
                                   LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
                                   WHERE r.numero_remito = ?
                                   ORDER BY i.nombre_insumo");
    $stmtDet->execute([$numero_remito]);
    $items = $stmtDet->fetchAll();
}

if (!$cab) {
    http_response_code(404);
    echo 'Remito no encontrado';
    exit;
}

// PDF
$pdf = new Fpdi();
// Cache simple del template para no re-parsar en múltiples usos por request
static $TPL_ID = null; static $TPL_SIZE = null;

// Fondo membretado con manejo robusto de tamaños y errores
$templateLoaded = false;
$templatePath = __DIR__ . '/../../membretada.pdf';
if (file_exists($templatePath)) {
    try {
        if ($TPL_ID === null) {
            $pdf->setSourceFile($templatePath);
            $TPL_ID = $pdf->importPage(1);
            $TPL_SIZE = $pdf->getTemplateSize($TPL_ID);
        }
        $size = $TPL_SIZE;
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($TPL_ID);
        $templateLoaded = true;
    } catch (Throwable $e) {
        error_log('[remito_pdf] No se pudo cargar plantilla PDF: ' . $e->getMessage());
    }
}

// Fallback a página A4 si no se pudo cargar la plantilla
if (!$templateLoaded) {
    $pdf->AddPage('P', 'A4');
}

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0,0,0);

// Márgenes y medidas
$leftMargin = 15;  // 1,5 cm
$rightMargin = 15; // 1,5 cm
$topMargin = 15;   // margen superior base
$lineHeight = 6;
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();
$contentWidth = $pageWidth - $leftMargin - $rightMargin;

// Offset adicional para respetar membrete cuando hay plantilla
$envHeaderOffset = getenv('REMITO_PDF_HEADER_OFFSET_MM');
$headerOffset = $templateLoaded ? (is_numeric($envHeaderOffset) ? (float)$envHeaderOffset : 30.0) : 0.0; // por defecto 30mm

// Encabezado: Número (izq) y Fecha (esquina superior derecha) debajo del membrete
$y = $topMargin + $headerOffset;
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($leftMargin, $y);
$enc = function($s) {
    if ($s === null) { return ''; }
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
    if ($out === false) { $out = utf8_decode((string)$s); }
    return $out;
};
// Truncador para ajustar textos a ancho de celda (con margen interno)
$fit = function($text, $width) use ($pdf, $enc) {
    $padding = 2; // mm
    $max = max(0, $width - $padding);
    $raw = (string)$text;
    $ellipsis = $enc('…');
    $encoded = $enc($raw);
    if ($pdf->GetStringWidth($encoded) <= $max) {
        return $encoded;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($raw) : strlen($raw);
    while ($len > 0) {
        $substr = function_exists('mb_substr') ? mb_substr($raw, 0, $len) : substr($raw, 0, $len);
        $trial = $enc($substr . '…');
        if ($pdf->GetStringWidth($trial) <= $max) {
            return $trial;
        }
        $len--;
    }
    return $ellipsis;
};

$pdf->Cell($contentWidth/2, 6, $enc('Número: ' . $cab['numero_remito']), 0, 0, 'L');
$pdf->SetXY($leftMargin + $contentWidth/2, $y);
$pdf->Cell($contentWidth/2, 6, $enc('Fecha: ' . date('d/m/Y', strtotime($cab['fecha_asignacion']))), 0, 1, 'R');

// Dos líneas en blanco antes del contenido (después de remito/fecha)
$y += (2 * $lineHeight);

// Bloque de datos en dos columnas: Persona (izq) y Destino (der)
$colGap = 6; // separación entre columnas
$colWidth = ($contentWidth - $colGap) / 2;

// Agente Asignado (izquierda)
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($colWidth, 6, $enc('Agente Asignado'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$y += 7;
$pdf->SetXY($leftMargin, $y);
$pdf->MultiCell($colWidth, 6, $enc('Nombre: ' . $cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada']), 0, 'L');

// Destino (derecha): Área, Sede, Localidad
$yRightStart = $y - 7; // alinear título con el de Persona
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin + $colWidth + $colGap, $yRightStart);
$pdf->Cell($colWidth, 6, $enc('Destino'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($leftMargin + $colWidth + $colGap, $yRightStart + 7);
$destinoTexto = 'Área: ' . ($cab['nombre_area'] ?: '-') . "\n" .
                'Sede: ' . ($cab['nombre_sede'] ?: '-') . "\n" .
                'Localidad: ' . ($cab['nombre_localidad'] ?: '-') . ' - Zona: ' . ($cab['nombre_zona'] ?: '-');
$pdf->MultiCell($colWidth, 6, $enc($destinoTexto), 0, 'L');

// Calcular la posición Y más baja de ambas columnas
$y = max($pdf->GetY(), $yRightStart + 7 + 3*$lineHeight);

if (!empty($cab['observaciones'])) {
    $pdf->SetXY($leftMargin, $y += 10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, $enc('Observaciones'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin, $y += 7);
    $pdf->MultiCell($contentWidth, 6, $enc($cab['observaciones']));
}

// Lista de insumos en 3 columnas (sin título)
$pdf->SetXY($leftMargin, $y += 10);
$pdf->SetFont('Arial', '', 10);

$cols = 3; $colPad = 6; $colW = ($contentWidth - ($colPad * ($cols - 1))) / $cols; $xStart = $leftMargin; $yStart = $y;
$colHeights = array_fill(0, $cols, $yStart);
$colIndex = 0;

foreach ($items as $it) {
    $x = $xStart + ($colIndex * ($colW + $colPad));
    $y = $colHeights[$colIndex];
    $pdf->SetXY($x, $y);
    // (Se omite el rótulo de tipo solicitado)
    // Lista de atributos
    $bullets = [];
    $bullets[] = '- ' . $enc($it['nombre_insumo'] ?: '');
    $bullets[] = '- Cantidad: ' . (isset($it['cantidad']) ? (int)$it['cantidad'] : 1);
    if (!empty($it['marca'])) { $bullets[] = '- Marca: ' . $enc($it['marca']); }
    if (!empty($it['modelo'])) { $bullets[] = '- Modelo: ' . $enc($it['modelo']); }
    if (!empty($it['numero_serie'])) { $bullets[] = '- Nro. de serie: ' . $enc($it['numero_serie']); }
    if (!empty($it['id_fisico'])) { $bullets[] = '- ID físico: ' . $enc($it['id_fisico']); }
    if (isset($it['tipo_insumo']) && $it['tipo_insumo'] === 'Notebook') {
        if (!empty($it['accesorios'])) { $bullets[] = '- Accesorios: ' . $enc($it['accesorios']); }
    }
    if (!empty($it['especificaciones'])) { $bullets[] = '- Especificaciones: ' . $enc($it['especificaciones']); }
    foreach ($bullets as $line) {
        $pdf->SetXY($x + 2, $y);
        $pdf->MultiCell($colW - 2, 5, $enc($line), 0, 'L');
        $y = $pdf->GetY();
    }
    // Espacio entre items
    $y += 3;
    $colHeights[$colIndex] = $y;
    $colIndex = ($colIndex + 1) % $cols;
}

// Área de firma: a 8 líneas del final de la tabla
$lineasDesdeFin = 8; // líneas
$firmaY = $y + ($lineasDesdeFin * $lineHeight);
// Evitar salir del área imprimible
if ($firmaY > $pageHeight - 20) { $firmaY = $pageHeight - 20; }
$firmaWidth = 60; // ancho de línea de firma
$firmaX1 = $leftMargin + ($contentWidth - $firmaWidth) / 2;
$firmaX2 = $firmaX1 + $firmaWidth;
$pdf->Line($firmaX1, $firmaY, $firmaX2, $firmaY);
$pdf->SetXY($leftMargin, $firmaY + 2);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($contentWidth, 6, $enc('Firma y aclaración del agente'), 0, 0, 'C');

$pdf->Output('I', $cab['numero_remito'] . '.pdf');
exit;