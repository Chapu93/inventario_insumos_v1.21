<?php
require_once '../../includes/config.php';
requerirAutenticacion();
// Permiso ver_propios mínimo
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    die('No tienes permiso');
}

// use setasign\Fpdi\Fpdi;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$db = conectarDB();
// Obtener datos del pedido (usando datos del solicitante externo de la tabla pedidos)
$stmt = $db->prepare("SELECT p.*, 
                        s.nombre_sede,
                        l.nombre_localidad,
                        i.nombre_insumo, i.numero_serie
                      FROM pedidos p
                      JOIN sedes s ON p.id_sede = s.id_sede
                      JOIN localidades l ON s.id_localidad = l.id_localidad
                      LEFT JOIN insumos i ON p.id_insumo_relacionado = i.id_insumo
                      WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die('Pedido no encontrado');

// Helper para decode utf8
if (!function_exists('u')) {
    function u($s) { return utf8_decode($s ?? ''); }
}

// Crear PDF con soporte FPDI para membrete
$pdf = new \setasign\Fpdi\Fpdi();

// Cargar plantilla membrete
$templatePath = __DIR__ . '/../../membretada.pdf';
$templateLoaded = false;
if (file_exists($templatePath)) {
    try {
        $pdf->setSourceFile($templatePath);
        $tplIdx = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplIdx);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx);
        $templateLoaded = true;
    } catch (Exception $e) {
        $pdf->AddPage();
    }
} else {
    $pdf->AddPage();
}

// Margen superior según si hay membrete
if ($templateLoaded) {
    $pdf->SetY(35);
}

// Título
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, u('CONSTANCIA DE RECEPCIÓN'), 0, 1, 'C');
$pdf->Ln(5);

// Bloque Fecha
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, u('Fecha de Recepción: ') . date('d/m/Y H:i', strtotime($data['fecha_creacion'])), 0, 1, 'R');
$pdf->Ln(5);

// Bloque Solicitante (Agente Externo)
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, u('DATOS DEL AGENTE SOLICITANTE'), 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 6, u('Nombre y Apellido:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u($data['solicitante_nombre'] . ' ' . $data['solicitante_apellido']), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(40, 6, u('Sede:'), 0, 0);
$pdf->Cell(0, 6, u($data['nombre_sede']), 0, 1);

$pdf->Cell(40, 6, u('Localidad:'), 0, 0);
$pdf->Cell(0, 6, u($data['nombre_localidad']), 0, 1);

if (!empty($data['solicitante_telefono'])) {
    $pdf->Cell(40, 6, u('Teléfono:'), 0, 0);
    $pdf->Cell(0, 6, u($data['solicitante_telefono']), 0, 1);
}

$pdf->Ln(5);

// Bloque Insumo si existe
if (!empty($data['insumo_relacionado'])) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, u('DETALLE DEL INSUMO'), 1, 1, 'C', true);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Ln(2);

    // Soporte para múltiples insumos separados por |
    $insumos = explode('|', $data['insumo_relacionado']);
    foreach ($insumos as $index => $insumo) {
        if ($index > 0) $pdf->Ln(2);
        
        $pdf->Cell(40, 6, u('Insumo ' . (count($insumos) > 1 ? ($index + 1) : '') . ':'), 0, 0);
        $pdf->Cell(0, 6, u($insumo), 0, 1);

        // El número de serie solo aplica al primer insumo ya que la BD solo guarda uno por ahora
        // O si el texto manual ya incluye la serie, se maneja ahí.
        if ($index === 0 && !empty($data['numero_serie'])) {
            $pdf->Cell(40, 6, u('N° de Serie:'), 0, 0);
            $pdf->Cell(0, 6, u($data['numero_serie']), 0, 1);
        }
    }
}

$pdf->Ln(5);

// Bloque Detalle
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, u('DETALLE DE LA SOLICITUD'), 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(2);

$pdf->Cell(40, 6, u('Tipo:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u($data['tipo']), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u('Descripción / Detalles:'), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, u($data['descripcion']), 0, 'L');

$pdf->Ln(40);

// Firmas
$y = $pdf->GetY();
if ($y > 240) { $pdf->AddPage(); $y = 35; }

$pdf->Line(20, $y, 80, $y);
$pdf->Line(130, $y, 190, $y);

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(20, $y + 2);
$pdf->Cell(60, 4, u('Entregado por (Firma)'), 0, 0, 'C');
$pdf->SetXY(20, $y + 6);
$pdf->Cell(60, 4, u($data['solicitante_nombre'] . ' ' . $data['solicitante_apellido']), 0, 0, 'C');


$pdf->SetXY(130, $y + 2);
$pdf->Cell(60, 4, u('Recibido por (Firma y aclaración)'), 0, 0, 'C');

if (!isset($no_exit_pdf)) {
    $pdf->Output('I', 'Constancia_Recepcion.pdf');
} else {
    echo $pdf->Output('S');
}

