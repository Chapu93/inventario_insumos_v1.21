<?php
require_once '../../includes/config.php';
use setasign\Fpdi\Fpdi;

// Obtener ID del servicio
$id_internet = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_internet === 0) {
    http_response_code(400);
    echo 'ID de servicio requerido';
    exit;
}

$conexion = conectarDB();

// Obtener toda la información del servicio
$stmt = $conexion->prepare("
    SELECT 
        si.*,
        s.nombre_sede,
        l.nombre_localidad,
        z.nombre_zona
    FROM sedes_internet si
    JOIN sedes s ON s.id_sede = si.id_sede
    JOIN localidades l ON l.id_localidad = s.id_localidad
    LEFT JOIN zonas z ON l.id_zona = z.id_zona
    WHERE si.id_internet = ?
    LIMIT 1
");
$stmt->execute([$id_internet]);
$servicio = $stmt->fetch();

if (!$servicio) {
    http_response_code(404);
    echo 'Servicio no encontrado';
    exit;
}

// Funciones auxiliares
$enc = function($s) {
    if ($s === null) { return ''; }
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
    if ($out === false) { $out = utf8_decode((string)$s); }
    return $out;
};

$formatFecha = function($fecha) {
    if (!$fecha) return '-';
    return date('d/m/Y', strtotime($fecha));
};

// Inicializar PDF
$pdf = new Fpdi();

// Cargar plantilla membretada
$templateLoaded = false;
$templatePath = __DIR__ . '/../../membretada.pdf';
if (file_exists($templatePath)) {
    try {
        $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);
        $tplSize = $pdf->getTemplateSize($tplId);
        $pdf->AddPage($tplSize['orientation'], [$tplSize['width'], $tplSize['height']]);
        $pdf->useTemplate($tplId);
        $templateLoaded = true;
    } catch (Throwable $e) {
        error_log('[internet_historial_pdf] Error al cargar plantilla: ' . $e->getMessage());
    }
}

// Fallback a A4 si no se cargó la plantilla
if (!$templateLoaded) {
    $pdf->AddPage('P', 'A4');
}

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

// Márgenes
$leftMargin = 20;
$rightMargin = 20;
$topMargin = 15;
$lineHeight = 6;
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();
$contentWidth = $pageWidth - $leftMargin - $rightMargin;

// Offset para respetar membrete
$headerOffset = $templateLoaded ? 30.0 : 0.0;

// Título del documento
$y = $topMargin + $headerOffset;
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 8, $enc('HISTORIAL DE SERVICIO DE INTERNET'), 0, 1, 'C');
$y += 12;

// ============================================
// SECCIÓN: UBICACIÓN
// ============================================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('UBICACIÓN'), 0, 1, 'L');
$pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
$y += 10;

$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth / 2, 6, $enc('Localidad: ' . $servicio['nombre_localidad']), 0, 0, 'L');
$pdf->SetXY($leftMargin + $contentWidth / 2, $y);
$pdf->Cell($contentWidth / 2, 6, $enc('Zona: ' . ($servicio['nombre_zona'] ?: 'N/A')), 0, 1, 'L');
$y += 7;

$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('Sede: ' . $servicio['nombre_sede']), 0, 1, 'L');
$y += 10;

// ============================================
// SECCIÓN: DATOS TÉCNICOS
// ============================================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('DATOS TÉCNICOS'), 0, 1, 'L');
$pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
$y += 10;

$pdf->SetFont('Arial', '', 11);
$datos = [
    ['Proveedor:', $servicio['proveedor']],
    ['Tipo de Conexión:', $servicio['tipo_conexion']],
    ['Velocidad:', ($servicio['velocidad_mbps'] ? $servicio['velocidad_mbps'] . ' Mbps' : 'No especificada')],
    ['Simétrico:', ($servicio['simetrico'] ? 'Sí' : 'No')],
    ['WiFi:', ($servicio['tiene_wifi'] ? 'Sí' : 'No')]
];

foreach ($datos as $row) {
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell(50, 6, $enc($row[0]), 0, 0, 'L');
    $pdf->SetX($leftMargin + 50);
    $pdf->Cell($contentWidth - 50, 6, $enc($row[1]), 0, 1, 'L');
    $y += 6;
}
$y += 4;

// ============================================
// SECCIÓN: ESTADO Y FECHAS
// ============================================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('ESTADO Y FECHAS'), 0, 1, 'L');
$pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
$y += 10;

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell(30, 6, $enc('Estado:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($contentWidth - 30, 6, $enc($servicio['estado_servicio']), 0, 1, 'L');
$y += 8;

// Mostrar datos según el estado
if ($servicio['estado_servicio'] === 'Activo' && $servicio['fecha_instalacion']) {
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin + 10, $y);
    $pdf->Cell($contentWidth - 10, 6, $enc('• Fecha de Instalación: ' . $formatFecha($servicio['fecha_instalacion'])), 0, 1, 'L');
    $y += 6;
}

if ($servicio['estado_servicio'] === 'Pendiente') {
    if ($servicio['instancia_pendiente']) {
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY($leftMargin + 10, $y);
        $pdf->Cell($contentWidth - 10, 6, $enc('• Instancia: ' . $servicio['instancia_pendiente']), 0, 1, 'L');
        $y += 6;
    }
    
    if ($servicio['fecha_solicitud_autorizacion']) {
        $pdf->SetXY($leftMargin + 10, $y);
        $pdf->Cell($contentWidth - 10, 6, $enc('• Fecha de Solicitud: ' . $formatFecha($servicio['fecha_solicitud_autorizacion'])), 0, 1, 'L');
        $y += 6;
    }
    
    if ($servicio['archivo_autorizacion']) {
        $nombreArchivo = basename($servicio['archivo_autorizacion']);
        $pdf->SetXY($leftMargin + 10, $y);
        $pdf->Cell($contentWidth - 10, 6, $enc('• Archivo de Autorización: ' . $nombreArchivo), 0, 1, 'L');
        $y += 6;
    }
}

if ($servicio['estado_servicio'] === 'De Baja' && $servicio['fecha_baja']) {
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin + 10, $y);
    $pdf->Cell($contentWidth - 10, 6, $enc('• Fecha de Baja: ' . $formatFecha($servicio['fecha_baja'])), 0, 1, 'L');
    $y += 6;
}

$y += 4;

// ============================================
// SECCIÓN: OBSERVACIONES
// ============================================
if (!empty($servicio['observaciones'])) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell($contentWidth, 6, $enc('OBSERVACIONES'), 0, 1, 'L');
    $pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
    $y += 10;
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin, $y);
    $pdf->MultiCell($contentWidth, 6, $enc($servicio['observaciones']), 0, 'L');
    $y = $pdf->GetY() + 4;
}

// ============================================
// AGREGAR ARCHIVO PDF DE AUTORIZACIÓN COMO PÁGINA ADICIONAL
// ============================================
if (!empty($servicio['archivo_autorizacion'])) {
    $archivoAutorizacionPath = __DIR__ . '/../../' . $servicio['archivo_autorizacion'];
    
    if (file_exists($archivoAutorizacionPath)) {
        try {
            // Importar el archivo PDF de autorización
            $pageCount = $pdf->setSourceFile($archivoAutorizacionPath);
            
            // Agregar todas las páginas del PDF de autorización
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplIdx);
                
                // Agregar nueva página con el tamaño del documento original
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx);
            }
        } catch (Throwable $e) {
            error_log('[internet_historial_pdf] Error al incluir archivo de autorización: ' . $e->getMessage());
        }
    }
}

// Generar PDF
$nombreArchivo = 'Historial_Internet_' . $servicio['nombre_sede'] . '_' . date('Ymd') . '.pdf';
$nombreArchivo = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $nombreArchivo);
$pdf->Output('I', $nombreArchivo);
exit;
