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
        // Viñeta simple y fecha
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY($leftMargin, $y);
        $pdf->Cell(10, 6, $enc('•'), 0, 0, 'C');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 6, $enc($formatFecha($evento['fecha'])), 0, 0, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell($contentWidth - 40, 6, $enc($evento['descripcion']), 0, 1, 'L');
        $y += 6;
    }
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
// OBTENER TODOS LOS PDFs DE LA CADENA DE TRASLADOS
// ============================================
$todosLosPdfs = [];

// Obtener toda la cadena de servicios (desde el más antiguo hasta el actual)
// Primero ir hacia atrás hasta encontrar el servicio original
$stmtCadena = $conexion->prepare("
    WITH RECURSIVE cadena_traslados AS (
        -- Servicio actual
        SELECT 
            id_internet,
            archivo_autorizacion,
            fecha_solicitud_autorizacion,
            fecha_instalacion,
            id_servicio_trasladado_desde,
            0 as nivel
        FROM sedes_internet
        WHERE id_internet = ?
        
        UNION ALL
        
        -- Servicios anteriores (ir hacia atrás)
        SELECT 
            i.id_internet,
            i.archivo_autorizacion,
            i.fecha_solicitud_autorizacion,
            i.fecha_instalacion,
            i.id_servicio_trasladado_desde,
            c.nivel - 1
        FROM sedes_internet i
        INNER JOIN cadena_traslados c ON i.id_internet = c.id_servicio_trasladado_desde
    )
    SELECT * FROM cadena_traslados
    ORDER BY nivel ASC
");

$stmtCadena->execute([$id_internet]);
$cadenaServicios = $stmtCadena->fetchAll();

// Recolectar todos los PDFs en orden cronológico
foreach ($cadenaServicios as $srv) {
    if (!empty($srv['archivo_autorizacion'])) {
        $todosLosPdfs[] = [
            'ruta' => $srv['archivo_autorizacion'],
            'fecha' => $srv['fecha_solicitud_autorizacion'] ?: $srv['fecha_instalacion'],
            'id_servicio' => $srv['id_internet']
        ];
    }
}

// ============================================
// AGREGAR TODOS LOS PDFs COMO PÁGINAS ADICIONALES
// ============================================
foreach ($todosLosPdfs as $pdfInfo) {
    $archivoPath = __DIR__ . '/../../' . $pdfInfo['ruta'];
    
    if (file_exists($archivoPath)) {
        try {
            // Importar el archivo PDF
            $pageCount = $pdf->setSourceFile($archivoPath);
            
            // Agregar todas las páginas del PDF
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
            error_log('[internet_historial_pdf] Error al incluir PDF (servicio #' . $pdfInfo['id_servicio'] . '): ' . $e->getMessage());
        }
    }
}

// Generar PDF
$nombreArchivo = 'Historial_Internet_' . $servicio['nombre_sede'] . '_' . date('Ymd') . '.pdf';
$nombreArchivo = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $nombreArchivo);
$pdf->Output('I', $nombreArchivo);
exit;
