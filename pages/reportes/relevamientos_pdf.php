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
    
    // Título del formulario (ajustado dinámicamente)
    $tamanoTitulo = $anchoFormulario < 45 ? 7 : ($anchoFormulario < 50 ? 8 : 9);
    $pdf->SetFont('Arial', 'B', $tamanoTitulo);
    $pdf->SetXY($x + 1, $y + 2);
    $pdf->Cell($anchoFormulario - 2, 4, $enc('RELEVAMIENTOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + 1, $y + 6, $x + $anchoFormulario - 1, $y + 6);
    
    // Configurar fuente para campos (ajustada según tamaño del formulario)
    $tamanoFuente = $anchoFormulario < 45 ? 6 : ($anchoFormulario < 50 ? 7 : 7.5);
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $posY = $y + 8;
    $lineHeight = $altoFormulario < 55 ? 4.2 : 4.5;
    $anchoEtiqueta = $anchoFormulario < 45 ? 22 : 24;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - 4;
    
    // ID
    $pdf->SetXY($x + 1, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Nro de Serie
    $pdf->SetXY($x + 1, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Nro Serie:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Procesador
    $pdf->SetXY($x + 1, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Procesador:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // RAM
    $pdf->SetXY($x + 1, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('RAM:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Almacenamiento
    $pdf->SetXY($x + 1, $posY);
    $etiquetaAlmacenamiento = $anchoFormulario < 45 ? $enc('Almacen.:') : $enc('Almacenamiento:');
    $pdf->Cell($anchoEtiqueta, $lineHeight, $etiquetaAlmacenamiento, 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Sistema Operativo
    $pdf->SetXY($x + 1, $posY);
    $etiquetaSO = $anchoFormulario < 45 ? $enc('SO:') : ($anchoFormulario < 50 ? $enc('Sistema Op.:') : $enc('Sistema Op.:'));
    $pdf->Cell($anchoEtiqueta, $lineHeight, $etiquetaSO, 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Motherboard
    $pdf->SetXY($x + 1, $posY);
    $etiquetaMother = $anchoFormulario < 45 ? $enc('Mother:') : $enc('Motherboard:');
    $pdf->Cell($anchoEtiqueta, $lineHeight, $etiquetaMother, 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + 0.5;
    
    // Sede/Oficina
    $pdf->SetXY($x + 1, $posY);
    $etiquetaSede = $anchoFormulario < 45 ? $enc('Sede:') : $enc('Sede/Oficina:');
    $pdf->Cell($anchoEtiqueta, $lineHeight, $etiquetaSede, 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + 1, $posY, $anchoCampo, $lineHeight);
}

// Generar UNA SOLA página con la mayor cantidad de formularios posible
$pdf->AddPage();

// Configuración de márgenes y espacios
$margenSuperior = 8;
$margenInferior = 8;
$margenLateral = 6;
$espacioEntreFormularios = 3;

// Dimensiones de A4
$anchoA4 = 210; // mm
$altoA4 = 297; // mm

// Configuración: 4 columnas x 5 filas = 20 formularios por página
$columnas = 4;
$filas = 5;

// Calcular dimensiones de cada formulario
$anchoDisponible = $anchoA4 - ($margenLateral * 2);
$altoDisponible = $altoA4 - $margenSuperior - $margenInferior;

$anchoForm = ($anchoDisponible - ($espacioEntreFormularios * ($columnas - 1))) / $columnas;
$altoForm = ($altoDisponible - ($espacioEntreFormularios * ($filas - 1))) / $filas;

// Generar todos los formularios en una sola página
$formIndex = 0;
for ($fila = 0; $fila < $filas; $fila++) {
    for ($col = 0; $col < $columnas; $col++) {
        $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
        $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
        
        dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoForm);
    }
}

// Salida del PDF
$pdf->Output('D', 'Planilla_Relevamientos_' . date('Y-m-d') . '.pdf');
