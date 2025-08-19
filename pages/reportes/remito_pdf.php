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

$y = 30;
$pdf->SetXY(15, $y);
$pdf->Cell(0, 6, 'Remito: ' . $cab['numero_remito'], 0, 1);
$pdf->SetXY(15, $y += 7);
$pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y', strtotime($cab['fecha_asignacion'])), 0, 1);

$pdf->SetXY(15, $y += 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, 'Destino', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(15, $y += 7);
$pdf->Cell(0, 6, 'Sede: ' . ($cab['nombre_sede'] ?: '-'), 0, 1);
$pdf->SetXY(15, $y += 6);
$pdf->Cell(0, 6, 'Localidad: ' . ($cab['nombre_localidad'] ?: '-') . ' - Zona: ' . ($cab['nombre_zona'] ?: '-'), 0, 1);
$pdf->SetXY(15, $y += 6);
$pdf->Cell(0, 6, 'Area: ' . ($cab['nombre_area'] ?: '-'), 0, 1);

$pdf->SetXY(15, $y += 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, 'Persona Asignada', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(15, $y += 7);
$pdf->Cell(0, 6, 'Nombre: ' . $cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada'], 0, 1);

if (!empty($cab['observaciones'])) {
    $pdf->SetXY(15, $y += 10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'Observaciones', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(15, $y += 7);
    $pdf->MultiCell(180, 6, $cab['observaciones']);
}

// Tabla de insumos
$pdf->SetXY(15, $y += 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, 'Insumos', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(15, $y += 7);
$pdf->Cell(90, 6, 'Insumo', 1);
$pdf->Cell(25, 6, 'Tipo', 1);
$pdf->Cell(15, 6, 'Cant.', 1);
$pdf->Cell(30, 6, 'S/N', 1);
$pdf->Cell(30, 6, 'ID Fisico', 1);
$y += 6;
$pdf->SetFont('Arial', '', 10);
foreach ($items as $it) {
    $pdf->SetXY(15, $y);
    $pdf->Cell(90, 6, $it['nombre_insumo'], 1);
    $pdf->Cell(25, 6, $it['tipo_insumo'], 1);
    $pdf->Cell(15, 6, (isset($it['cantidad']) ? (int)$it['cantidad'] : 1), 1);
    $pdf->Cell(30, 6, ($it['numero_serie'] ?: '-'), 1);
    $pdf->Cell(30, 6, ($it['id_fisico'] ?: '-'), 1);
    $y += 6;
}

$pdf->Output('I', $cab['numero_remito'] . '.pdf');
exit;