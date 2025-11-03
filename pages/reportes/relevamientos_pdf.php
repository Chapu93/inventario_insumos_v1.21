<?php
require_once '../../includes/config.php';

// Cargar autoload para FPDF
require_once __DIR__ . '/../../vendor/autoload.php';

// Crear PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false); // Control manual de páginas

// Función para codificar texto a ISO-8859-1
$enc = function($s) {
    if ($s === null) { return ''; }
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
    if ($out === false) { $out = utf8_decode((string)$s); }
    return $out;
};

// Función para dibujar un formulario
function dibujarFormulario($pdf, $x, $y, $enc, $anchoFormulario, $altoFormulario) {
    
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($x + 2, $y + 3);
    $pdf->Cell($anchoFormulario - 4, 6, $enc('RELEVAMIENTOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + 2, $y + 9, $x + $anchoFormulario - 2, $y + 9);
    
    // Configurar fuente para campos (tamaño cómodo y legible)
    $tamanoFuente = 9;
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $posY = $y + 11;
    $lineHeight = 5.5; // Altura cómoda para escritura manual
    $anchoEtiqueta = 30;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - 4;
    
    // ID
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Nro de Serie
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Nro de Serie:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Procesador
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Procesador:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // RAM
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('RAM:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Almacenamiento
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Almacenamiento:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Sistema Operativo
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sistema Operativo:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Motherboard
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Motherboard:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Sede/Oficina
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sede/Oficina:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
}

// Generar UNA SOLA página con 2 formularios de ancho
$pdf->AddPage();

// Configuración de márgenes y espacios
$margenSuperior = 10;
$margenInferior = 10;
$margenLateral = 10;
$espacioEntreFormularios = 5;

// Dimensiones de A4
$anchoA4 = 210; // mm
$altoA4 = 297; // mm

// Configuración: 2 columnas (ancho fijo)
$columnas = 2;

// Calcular dimensiones de cada formulario (2 de ancho)
$anchoDisponible = $anchoA4 - ($margenLateral * 2);
$anchoForm = ($anchoDisponible - $espacioEntreFormularios) / $columnas;

// Calcular altura necesaria para un formulario con todos los campos
// Título: ~5mm + separador: ~2mm + 8 campos con espaciado: ~8 * 5.5mm = ~44mm
$altoFormNecesario = 55; // Altura mínima cómoda para todos los campos

// Calcular cuántas filas caben de manera prolija
$altoDisponible = $altoA4 - $margenSuperior - $margenInferior;
$filas = floor(($altoDisponible + $espacioEntreFormularios) / ($altoFormNecesario + $espacioEntreFormularios));

// Recalcular altura exacta de cada formulario para que quepan bien
$altoForm = ($altoDisponible - ($espacioEntreFormularios * ($filas - 1))) / $filas;

// Generar todos los formularios en una sola página (2 columnas)
for ($fila = 0; $fila < $filas; $fila++) {
    for ($col = 0; $col < $columnas; $col++) {
        $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
        $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
        
        dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoForm);
    }
}

// Salida del PDF
$pdf->Output('D', 'Planilla_Relevamientos_' . date('Y-m-d') . '.pdf');
