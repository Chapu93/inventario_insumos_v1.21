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
// SECCIÓN: ESTADO ACTUAL
// ============================================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('ESTADO ACTUAL'), 0, 1, 'L');
$pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
$y += 10;

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell(30, 6, $enc('Estado:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($contentWidth - 30, 6, $enc($servicio['estado_servicio']), 0, 1, 'L');
$y += 10;

// ============================================
// SECCIÓN: HISTORIAL DEL SERVICIO
// ============================================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 6, $enc('HISTORIAL Y TRAZABILIDAD'), 0, 1, 'L');
$pdf->Line($leftMargin, $y + 6, $leftMargin + $contentWidth, $y + 6);
$y += 10;

$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetXY($leftMargin, $y);
$pdf->Cell($contentWidth, 5, $enc('Registro cronológico de eventos del servicio:'), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$y += 8;

// Construir timeline de eventos
$eventos = [];

// Evento: Solicitud (si existe fecha de solicitud)
if ($servicio['fecha_solicitud_autorizacion']) {
    $eventos[] = [
        'fecha' => $servicio['fecha_solicitud_autorizacion'],
        'tipo' => 'Solicitud',
        'descripcion' => 'Solicitud del servicio',
        'instancia' => $servicio['instancia_pendiente'] ?: null
    ];
}

// Evento: Instalación (si existe fecha de instalación)
if ($servicio['fecha_instalacion']) {
    $eventos[] = [
        'fecha' => $servicio['fecha_instalacion'],
        'tipo' => 'Instalación',
        'descripcion' => 'Instalación del servicio completada',
        'instancia' => null
    ];
}

// Evento: Baja (si existe fecha de baja)
if ($servicio['fecha_baja']) {
    $eventos[] = [
        'fecha' => $servicio['fecha_baja'],
        'tipo' => 'Baja',
        'descripcion' => 'Servicio dado de baja',
        'instancia' => null
    ];
}

// Ordenar eventos por fecha
usort($eventos, function($a, $b) {
    return strtotime($a['fecha']) - strtotime($b['fecha']);
});

// Mostrar timeline de eventos
if (empty($eventos)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetXY($leftMargin + 10, $y);
    $pdf->Cell($contentWidth - 10, 6, $enc('No hay eventos registrados en el historial'), 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $y += 8;
} else {
    foreach ($eventos as $idx => $evento) {
        // Icono y fecha
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY($leftMargin + 5, $y);
        
        // Icono según tipo de evento
        $icono = '•';
        if ($evento['tipo'] === 'Solicitud') $icono = '→';
        elseif ($evento['tipo'] === 'Instalación') $icono = '✓';
        elseif ($evento['tipo'] === 'Baja') $icono = '✗';
        
        $pdf->Cell(15, 6, $enc($icono), 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 6, $enc($formatFecha($evento['fecha'])), 0, 0, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell($contentWidth - 45, 6, $enc($evento['descripcion']), 0, 1, 'L');
        $y += 6;
        
        // Instancia (si existe)
        if ($evento['instancia']) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($leftMargin + 20, $y);
            $pdf->Cell($contentWidth - 20, 5, $enc('Instancia: ' . $evento['instancia']), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $y += 5;
        }
        
        // Línea punteada entre eventos (excepto el último)
        if ($idx < count($eventos) - 1) {
            $pdf->SetLineWidth(0.2);
            $pdf->SetDrawColor(200, 200, 200);
            for ($i = 0; $i < ($contentWidth - 20); $i += 2) {
                $pdf->Line($leftMargin + 15 + $i, $y + 1, $leftMargin + 15 + $i + 1, $y + 1);
            }
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.5);
            $y += 4;
        }
    }
    $y += 6;
}

// Si hay archivo de autorización, mencionarlo en el historial
if ($servicio['archivo_autorizacion']) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetXY($leftMargin, $y);
    $pdf->Cell($contentWidth, 6, $enc('Documentación Adjunta:'), 0, 1, 'L');
    $y += 6;
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY($leftMargin + 10, $y);
    $pdf->Cell($contentWidth - 10, 6, $enc('• Archivo de Autorización Superior (ver página siguiente)'), 0, 1, 'L');
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
                
                // Forzar orientación vertical (Portrait) y tamaño A4 para impresión estándar
                $pdf->AddPage('P', 'A4');
                
                // Calcular escalado para ajustar al A4 vertical
                $a4Width = 210; // mm
                $a4Height = 297; // mm
                $scale = min($a4Width / $size['width'], $a4Height / $size['height']);
                
                // Centrar en la página
                $x = ($a4Width - ($size['width'] * $scale)) / 2;
                $y_page = ($a4Height - ($size['height'] * $scale)) / 2;
                
                // Usar el template escalado y centrado
                $pdf->useTemplate($tplIdx, $x, $y_page, $size['width'] * $scale, $size['height'] * $scale);
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
