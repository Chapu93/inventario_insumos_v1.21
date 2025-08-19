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
    $stmtDet = $conexion->prepare("SELECT i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, d.cantidad
                                   FROM remitos_detalle d
                                   JOIN insumos i ON d.id_insumo = i.id_insumo
                                   JOIN remitos r ON r.id_remito = d.id_remito
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

// Fondo membretado con manejo robusto de tamaños y errores
$templateLoaded = false;
$templatePath = __DIR__ . '/../../membretada.pdf';
if (file_exists($templatePath)) {
    try {
        // Importar primera página y obtener tamaño/orientación
        $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplId);
        // Crear página con el tamaño de la plantilla
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);
        $templateLoaded = true;
    } catch (Throwable $e) {
        error_log('[remito_pdf] No se pudo cargar plantilla PDF: ' . $e->getMessage());
    }
}

// Fallback a página A4 si no se pudo cargar la plantilla
if (!$templateLoaded) {
    $pdf->AddPage('P', 'mm', 'A4');
}

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0,0,0);

// Márgenes y medidas
$leftMargin = 15;  // 1,5 cm
$rightMargin = 15; // 1,5 cm
$topMargin = 15;   // mantener margen superior
$lineHeight = 6;
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();
$contentWidth = $pageWidth - $leftMargin - $rightMargin;

// Encabezado: Remito (izq) y Fecha (esquina superior derecha)
$y = $topMargin;
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth/2, 6, 'Remito: ' . $cab['numero_remito'], 0, 0, 'L');
$pdf->SetXY($leftMargin + $contentWidth/2, $y);
$pdf->Cell($contentWidth/2, 6, 'Fecha: ' . date('d/m/Y', strtotime($cab['fecha_asignacion'])), 0, 1, 'R');

// Dos líneas en blanco antes del contenido del remito
$y += (2 * $lineHeight);

// Bloque de datos en dos columnas: Persona (izq) y Destino (der)
$colGap = 6; // separación entre columnas
$colWidth = ($contentWidth - $colGap) / 2;

// Persona Asignada (izquierda)
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($colWidth, 6, 'Persona Asignada', 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$y += 7;
$pdf->SetXY($leftMargin, $y);
$pdf->MultiCell($colWidth, 6, 'Nombre: ' . $cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada'], 0, 'L');

// Destino (derecha)
$yRightStart = $y - 7; // alinear título con el de Persona
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin + $colWidth + $colGap, $yRightStart);
$pdf->Cell($colWidth, 6, 'Destino', 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($leftMargin + $colWidth + $colGap, $yRightStart + 7);
$destinoTexto = 'Sede: ' . ($cab['nombre_sede'] ?: '-') . "\n" .
                'Localidad: ' . ($cab['nombre_localidad'] ?: '-') . ' - Zona: ' . ($cab['nombre_zona'] ?: '-') . "\n" .
                'Area: ' . ($cab['nombre_area'] ?: '-');
$pdf->MultiCell($colWidth, 6, $destinoTexto, 0, 'L');

// Calcular la posición Y más baja de ambas columnas
$y = max($pdf->GetY(), $yRightStart + 7 + 3*$lineHeight);

if (!empty($cab['observaciones'])) {
    $pdf->SetXY($leftMargin, $y += 10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'Observaciones', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin, $y += 7);
    $pdf->MultiCell($contentWidth, 6, $cab['observaciones']);
}

// Tabla de insumos
$pdf->SetXY($leftMargin, $y += 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, 'Insumos', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY($leftMargin, $y += 7);
// Anchos de columnas dentro del área de contenido (suman contentWidth)
$wNombre = 90; $wTipo = 25; $wCant = 15; $wSN = 30; $wID = $contentWidth - ($wNombre + $wTipo + $wCant + $wSN);
$pdf->Cell($wNombre, 6, 'Insumo', 1);
$pdf->Cell($wTipo, 6, 'Tipo', 1);
$pdf->Cell($wCant, 6, 'Cant.', 1);
$pdf->Cell($wSN, 6, 'S/N', 1);
$pdf->Cell($wID, 6, 'ID Fisico', 1);
$y += 6;
$pdf->SetFont('Arial', '', 10);
foreach ($items as $it) {
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell($wNombre, 6, $it['nombre_insumo'], 1);
    $pdf->Cell($wTipo, 6, $it['tipo_insumo'], 1);
    $pdf->Cell($wCant, 6, (isset($it['cantidad']) ? (int)$it['cantidad'] : 1), 1);
    $pdf->Cell($wSN, 6, ($it['numero_serie'] ?: '-'), 1);
    $pdf->Cell($wID, 6, ($it['id_fisico'] ?: '-'), 1);
    $y += 6;
}

// Área de firma antes del pie de página
$bottomMargin = 15; // 1,5 cm
$firmaY = $pageHeight - $bottomMargin - 20; // espacio para la línea y leyenda
if ($y > $firmaY - 10) {
    // Si el contenido llegó muy abajo, ajustar la firma un poco más arriba
    $firmaY = max($y + 10, $pageHeight - $bottomMargin - 20);
}
$firmaWidth = 60; // ancho de línea de firma
$firmaX1 = $leftMargin + ($contentWidth - $firmaWidth) / 2;
$firmaX2 = $firmaX1 + $firmaWidth;
$pdf->Line($firmaX1, $firmaY, $firmaX2, $firmaY);
$pdf->SetXY($leftMargin, $firmaY + 2);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($contentWidth, 6, 'Firma del agente', 0, 0, 'C');

$pdf->Output('I', $cab['numero_remito'] . '.pdf');
exit;