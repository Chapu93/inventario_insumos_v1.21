<?php
require_once '../../includes/config.php';
requerirAutenticacion();
// Permiso ver_propios mínimo
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    die('No tienes permiso');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$db = conectarDB();
// Obtener datos del pedido
$stmt = $db->prepare("SELECT p.*, 
                        s.nombre_sede,
                        l.nombre_localidad,
                        ins.id_insumo, ins.nombre_insumo, ins.numero_serie, ins.id_fisico, ins.id_patrimonio
                      FROM pedidos p
                      JOIN sedes s ON p.id_sede = s.id_sede
                      JOIN localidades l ON s.id_localidad = l.id_localidad
                      LEFT JOIN insumos ins ON p.id_insumo_relacionado = ins.id_insumo
                      WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die('Pedido no encontrado');

// Helper para decode utf8
if (!function_exists('u')) {
    function u($s) { return utf8_decode($s ?? ''); }
}

// Generar PDF con soporte FPDI para membrete
$pdf = new \setasign\Fpdi\Fpdi();
$pdf->SetMargins(15, 10, 15);

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
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, u('CONSTANCIA DE RECEPCIÓN'), 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, u('N° Pedido: ') . $data['id_pedido'], 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);

// Sección: DATOS DE LA SOLICITUD
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetLineWidth(0.3);
$pdf->SetDrawColor(160, 174, 192);
$pdf->Cell(0, 7, u('DATOS DE LA SOLICITUD'), 1, 1, 'C', false);
$pdf->Ln(3);

// Fila 1: N° Pedido & Fecha Recepción
$pdf->SetFont('Arial', 'B', 10);
$lbl = u('N° Pedido:');
$w = $pdf->GetStringWidth($lbl) + 1.5;
$pdf->Cell($w, 6, $lbl, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90 - $w, 6, $data['id_pedido'], 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$lbl2 = u('Fecha Recepción:');
$w2 = $pdf->GetStringWidth($lbl2) + 1.5;
$pdf->Cell($w2, 6, $lbl2, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($data['fecha_creacion'])), 0, 1);

// Fila 2: Solicitante & Localidad
$pdf->SetFont('Arial', 'B', 10);
$lbl = u('Solicitante:');
$w = $pdf->GetStringWidth($lbl) + 1.5;
$pdf->Cell($w, 6, $lbl, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90 - $w, 6, u($data['solicitante_nombre'] . ' ' . $data['solicitante_apellido']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$lbl2 = u('Localidad:');
$w2 = $pdf->GetStringWidth($lbl2) + 1.5;
$pdf->Cell($w2, 6, $lbl2, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, u($data['nombre_localidad']), 0, 1);

// Fila 3: Sede & Tipo
$pdf->SetFont('Arial', 'B', 10);
$lbl = u('Sede:');
$w = $pdf->GetStringWidth($lbl) + 1.5;
$pdf->Cell($w, 6, $lbl, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90 - $w, 6, u($data['nombre_sede']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$lbl2 = u('Tipo:');
$w2 = $pdf->GetStringWidth($lbl2) + 1.5;
$pdf->Cell($w2, 6, $lbl2, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, u($data['tipo']), 0, 1);

if (!empty($data['solicitante_telefono'])) {
    $pdf->SetFont('Arial', 'B', 10);
    $lbl = u('Teléfono:');
    $w = $pdf->GetStringWidth($lbl) + 1.5;
    $pdf->Cell($w, 6, $lbl, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, u($data['solicitante_telefono']), 0, 1);
}

$pdf->Ln(2);

// Sección: DETALLE DEL INSUMO (si existe)
if (!empty($data['insumo_relacionado'])) {
    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetLineWidth(0.3);
    $pdf->SetDrawColor(160, 174, 192);
    $pdf->Cell(0, 7, u('DETALLE DEL INSUMO'), 1, 1, 'C', false);
    $pdf->Ln(3);
    
    // Soporte para múltiples insumos separados por |
    $insumos = explode('|', $data['insumo_relacionado']);
    foreach ($insumos as $index => $insumo) {
        if ($index > 0) $pdf->Ln(2);
        
        // Extraer número de serie de la cadena del insumo (si existe)
        $sn_individual = null;
        if (preg_match('/\(S\/N:\s*([^)]+)\)/i', $insumo, $matches_sn)) {
            $sn_individual = trim($matches_sn[1]);
        }
        
        // Extraer ID físico de la cadena del insumo (si existe)
        $id_individual = null;
        if (preg_match('/\(ID:\s*([^)]+)\)/i', $insumo, $matches_id)) {
            $id_individual = trim($matches_id[1]);
        }
        
        // Fallback para el primer insumo con datos de base de datos si no venían en la cadena
        if (empty($sn_individual) && $index === 0 && !empty($data['numero_serie'])) {
            $sn_individual = trim($data['numero_serie']);
        }
        
        // Limpiar el N° de serie o ID redundante entre paréntesis del string del insumo
        $insumo_limpio = preg_replace('/\s*\((S\/N|ID):\s*[^)]+\)/i', '', $insumo);
        
        // Si el tipo de insumo es "Varios - ...", usar solo el nombre
        if (stripos($insumo_limpio, 'Varios - ') === 0) {
            $insumo_limpio = substr($insumo_limpio, 9);
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $lbl = u('Insumo' . (count($insumos) > 1 ? ' ' . ($index + 1) : '') . ':');
        $w = $pdf->GetStringWidth($lbl) + 1.5;
        $pdf->Cell($w, 6, $lbl, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, u($insumo_limpio), 0, 1);
        
        // ID Insumo
        $idInsumo = ($index === 0 && !empty($data['id_insumo'])) ? $data['id_insumo'] : (!empty($data['id_insumo_relacionado']) && $index === 0 ? $data['id_insumo_relacionado'] : null);
        if (!empty($idInsumo)) {
            $pdf->SetFont('Arial', 'B', 10);
            $lbl_id_insumo = u('ID Insumo:');
            $w_id_insumo = $pdf->GetStringWidth($lbl_id_insumo) + 1.5;
            $pdf->Cell($w_id_insumo, 6, $lbl_id_insumo, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, '#' . $idInsumo, 0, 1);
        }

        // Imprimir N° de Serie si existe
        if (!empty($sn_individual)) {
            $pdf->SetFont('Arial', 'B', 10);
            $lbl_sn = u('N° de Serie:');
            $w_sn = $pdf->GetStringWidth($lbl_sn) + 1.5;
            $pdf->Cell($w_sn, 6, $lbl_sn, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, u($sn_individual), 0, 1);
        } elseif (!empty($id_individual)) {
            // ID físico alternativo
            $pdf->SetFont('Arial', 'B', 10);
            $lbl_id = u('ID Físico:');
            $w_id = $pdf->GetStringWidth($lbl_id) + 1.5;
            $pdf->Cell($w_id, 6, $lbl_id, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, u($id_individual), 0, 1);
        }

        if ($index === 0 && !empty($data['id_patrimonio'])) {
            $pdf->SetFont('Arial', 'B', 10);
            $lbl_pat = u('ID Patrimonio:');
            $w_pat = $pdf->GetStringWidth($lbl_pat) + 1.5;
            $pdf->Cell($w_pat, 6, $lbl_pat, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, u($data['id_patrimonio']), 0, 1);
        }
    }
}

$pdf->Ln(4);

// Sección: DETALLE DE LA SOLICITUD
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetLineWidth(0.3);
$pdf->SetDrawColor(160, 174, 192);
$pdf->Cell(0, 7, u('DESCRIPCIÓN DEL REQUERIMIENTO'), 1, 1, 'C', false);
$pdf->Ln(3);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, u('Detalle / Observaciones:'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, u($data['descripcion']), 0, 'L', false);

$pdf->Ln(40);

// Firmas
$y = $pdf->GetY();
if ($y > 240) { $pdf->AddPage(); $y = 35; }

$pdf->Line(30, $y, 90, $y);
$pdf->Line(120, $y, 180, $y);

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(30, $y + 2);
$pdf->Cell(60, 4, u('Entregado por (Firma)'), 0, 0, 'C');
$pdf->SetXY(30, $y + 6);
$pdf->Cell(60, 4, u($data['solicitante_nombre'] . ' ' . $data['solicitante_apellido']), 0, 0, 'C');

$pdf->SetXY(120, $y + 2);
$pdf->Cell(60, 4, u('Recibido por (Firma y aclaración)'), 0, 0, 'C');

if (!isset($no_exit_pdf)) {
    $pdf->Output('I', 'Constancia_Recepcion_' . $id . '.pdf');
} else {
    echo $pdf->Output('S');
}
