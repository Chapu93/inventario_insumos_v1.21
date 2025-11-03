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
    $anchoFormulario = 95; // Ancho para 4 formularios por página
    $altoFormulario = 65; // Altura para 4 formularios por página
    
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetXY($x + 2, $y + 3);
    $pdf->Cell($anchoFormulario - 4, 5, $enc('RELEVAMIENTOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + 2, $y + 8, $x + $anchoFormulario - 2, $y + 8);
    
    // Configurar fuente para campos (más pequeña para que quepan todos)
    $pdf->SetFont('Arial', '', 8);
    $posY = $y + 11;
    $lineHeight = 5.5;
    $anchoEtiqueta = 28;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - 6;
    
    // ID
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 2, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 1;
    
    // Nro de Serie
    $pdf->SetXY($x + 2, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Nro Serie:'), 0, 0, 'L');
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
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sistema Op.:'), 0, 0, 'L');
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

// Generar múltiples páginas con 4 formularios cada una (2x2)
$numFormularios = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 20; // Por defecto 20 formularios (5 páginas)
$numPaginas = ceil($numFormularios / 4);

for ($pagina = 0; $pagina < $numPaginas; $pagina++) {
    $pdf->AddPage();
    
    $margenSuperior = 10;
    $margenLateral = 8;
    $espacioEntreFormularios = 4;
    
    // Altura y ancho de cada formulario (ajustados para 4 por página)
    $altoForm = 65;
    $anchoForm = 95;
    
    // Calcular posiciones para 4 formularios (2x2)
    $y1 = $margenSuperior; // Arriba
    $y2 = $margenSuperior + $altoForm + $espacioEntreFormularios; // Abajo
    $x1 = $margenLateral; // Izquierda
    $x2 = $margenLateral + $anchoForm + $espacioEntreFormularios; // Derecha
    
    // Formulario 1: Arriba izquierda
    $formIndex = $pagina * 4;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x1, $y1, $enc);
    }
    
    // Formulario 2: Arriba derecha
    $formIndex = $pagina * 4 + 1;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x2, $y1, $enc);
    }
    
    // Formulario 3: Abajo izquierda
    $formIndex = $pagina * 4 + 2;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x1, $y2, $enc);
    }
    
    // Formulario 4: Abajo derecha
    $formIndex = $pagina * 4 + 3;
    if ($formIndex < $numFormularios) {
        dibujarFormulario($pdf, $x2, $y2, $enc);
    }
}

// Salida del PDF
$pdf->Output('D', 'Planilla_Relevamientos_' . date('Y-m-d') . '.pdf');
