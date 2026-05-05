<?php
require_once '../../includes/config.php';
requerirAutenticacion();
// Permiso minimo ver.
if (!tienePermiso('pedidos', 'informe') && !tienePermiso('pedidos', 'ver_todos')) {
    die('No tienes permiso para ver informes');
}

// use setasign\Fpdi\Fpdi;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$db = conectarDB();
// Obtener info completa (incluyendo numero_informe de pedidos_informes)
$stmt = $db->prepare("SELECT p.*, i.*, 
                        u_sol.nombre as sol_nom, u_sol.apellido as sol_ape,
                        s.nombre_sede,
                        u_asig.nombre as asig_nom, u_asig.apellido as asig_ape,
                        ins.nombre_insumo, ins.numero_serie
                      FROM pedidos p
                      JOIN pedidos_informes i ON p.id_pedido = i.id_pedido
                      JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                      JOIN sedes s ON p.id_sede = s.id_sede
                      LEFT JOIN usuarios u_asig ON p.asignado_a = u_asig.id_usuario
                      LEFT JOIN insumos ins ON p.id_insumo_relacionado = ins.id_insumo
                      WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die('Informe no encontrado o pedido sin informe');

// Helper para decode
if (!function_exists('u')) {
    function u($s) { return utf8_decode($s ?? ''); }
}

// Generar PDF con soporte FPDI para membrete
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

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, u('INFORME TÉCNICO'), 0, 1, 'C');

if (!empty($data['numero_informe'])) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 8, u('N° Informe: ') . $data['numero_informe'], 0, 1, 'C');
}

$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);

// Info Pedido
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, u('DATOS DE LA SOLICITUD'), 1, 1, 'C', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('N° Pedido:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, $data['id_pedido'], 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Fecha Solicitud:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($data['fecha_creacion'])), 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Solicitante:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, u($data['solicitante_nombre'] . ' ' . $data['solicitante_apellido']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Sede:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, u($data['nombre_sede']), 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Tipo:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, u($data['tipo']), 0, 1);

$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, u('Descripción Original:'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, u($data['descripcion']), 0, 'L');

if (!empty($data['insumo_relacionado'])) {
    $pdf->Ln(2);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, u('DETALLE DEL INSUMO'), 1, 1, 'C', true);
    $pdf->Ln(2);
    
    // Soporte para múltiples insumos separados por |
    $insumos = explode('|', $data['insumo_relacionado']);
    foreach ($insumos as $index => $insumo) {
        if ($index > 0) $pdf->Ln(2);
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 6, u('Insumo' . (count($insumos) > 1 ? ' ' . ($index + 1) : '') . ':'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, u($insumo), 0, 1);
        
        if ($index === 0 && !empty($data['numero_serie'])) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, u('N° de Serie:'), 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, u($data['numero_serie']), 0, 1);
        }
    }
}

$pdf->Ln(5);

// Info Informe
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, u('DETALLE TÉCNICO'), 1, 1, 'C', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Técnico Asig.:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, u($data['asig_nom'] . ' ' . $data['asig_ape']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 6, u('Fecha Informe:'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($data['fecha_informe'])), 0, 1);

$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, u('Diagnóstico:'), 0, 1);
$pdf->SetFont('Arial', '', 10);
// Fondo ligero para bloques de texto
$pdf->SetFillColor(250, 250, 250);
$pdf->MultiCell(0, 5, u($data['diagnostico']), 0, 'L', true);

$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, u('Trabajo Realizado:'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, u($data['trabajo_realizado']), 0, 'L', true);

$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, u('RESULTADO:'), 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, u($data['resultado']), 0, 1);

$pdf->Ln(40);

// Firma
$y = $pdf->GetY();
if ($y > 250) { $pdf->AddPage(); $y = 35; }

$pdf->Line(20, $y, 80, $y);
$pdf->Line(130, $y, 190, $y);

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(20, $y + 2);
$pdf->Cell(60, 4, u('Firma Solicitante / Recibí Conforme'), 0, 0, 'C');

$pdf->SetXY(130, $y + 2);
$pdf->Cell(60, 4, u('Firma Técnico / Responsable'), 0, 0, 'C');
$pdf->SetXY(130, $y + 6);
$pdf->Cell(60, 4, u($data['asig_nom'] . ' ' . $data['asig_ape']), 0, 0, 'C');

if (!isset($no_exit_pdf)) {
    $pdf->Output('I', 'Informe_' . ($data['numero_informe'] ?? $id) . '.pdf');
} else {
    echo $pdf->Output('S');
}

