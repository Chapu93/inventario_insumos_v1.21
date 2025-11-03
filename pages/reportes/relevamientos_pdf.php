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
function dibujarFormulario($pdf, $x, $y, $enc) {
    $anchoFormulario = 90; // Mitad de A4 menos márgenes
    $altoFormulario = 130; // Altura del formulario
    
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($x + 2, $y + 5);
    $pdf->Cell($anchoFormulario - 4, 7, $enc('RELEVAMIENTOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + 2, $y + 12, $x + $anchoFormulario - 2, $y + 12);
    
    // Configurar fuente para campos
    $pdf->SetFont('Arial', '', 9);
    $posY = $y + 18;
    $lineHeight = 8;
    $anchoEtiqueta = 32;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - 6;
    
    // ID
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // Nro de Serie
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Nro de Serie:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // Procesador
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Procesador:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // RAM
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('RAM:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // Almacenamiento
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Almacenamiento:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // Sistema Operativo
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sistema Operativo:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 2;
    
    // Motherboard
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Motherboard:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
}

// Generar múltiples páginas con 2 formularios cada una
$numFormularios = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 10; // Por defecto 10 formularios (5 páginas)
$numPaginas = ceil($numFormularios / 2);

for ($pagina = 0; $pagina < $numPaginas; $pagina++) {
    $pdf->AddPage();
    
    $margenSuperior = 15;
    $margenLateral = 10;
    $espacioEntreFormularios = 5;
    
    // Altura y ancho de cada formulario
    $altoForm = 130;
    $anchoForm = 90;
    
    // Calcular posiciones
    $y1 = $margenSuperior;
    $x1 = $margenLateral;
    $x2 = $margenLateral + $anchoForm + $espacioEntreFormularios;
    
    // Formulario izquierdo
    $formIndex = $pagina * 2;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x1, $y1, $enc);
    }
    
    // Formulario derecho
    $formIndex = $pagina * 2 + 1;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x2, $y1, $enc);
    }
}

// Salida del PDF
$pdf->Output('D', 'Planilla_Relevamientos_' . date('Y-m-d') . '.pdf');
