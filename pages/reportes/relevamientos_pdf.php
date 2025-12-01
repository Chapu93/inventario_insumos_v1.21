<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('reportes', 'ver');

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
function dibujarFormulario($pdf, $x, $y, $enc, $anchoFormulario, $altoFormulario, $numFormularios = 6) {
    
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    // Ajustar tamaños según cantidad de formularios (4 = más grandes, 6 = más pequeños)
    if ($numFormularios == 4) {
        // Tamaños más grandes para 4 formularios
        $tamanoTitulo = 13;
        $tamanoFuente = 10;
        $lineHeight = 6.5;
        $margenInterno = 3;
        $espacioCampos = 1.5;
    } else {
        // Tamaños estándar para 6 formularios
        $tamanoTitulo = 12;
        $tamanoFuente = 9;
        $lineHeight = 5.5;
        $margenInterno = 2;
        $espacioCampos = 1;
    }
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', $tamanoTitulo);
    $pdf->SetXY($x + $margenInterno, $y + 3);
    $pdf->Cell($anchoFormulario - ($margenInterno * 2), 6, $enc('RELEVAMIENTOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + $margenInterno, $y + 9, $x + $anchoFormulario - $margenInterno, $y + 9);
    
    // Configurar fuente para campos
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $posY = $y + 11;
    $anchoEtiqueta = 32;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - ($margenInterno * 2);
    
    // ID PC
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID PC:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // ID Monitor
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID Monitor:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // ID Impresora
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('ID Impresora:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Procesador
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Procesador:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // RAM
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('RAM:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Almacenamiento
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Almacenamiento:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Sistema Operativo
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sistema Operativo:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Motherboard
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Motherboard:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Sede/Oficina
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Sede/Oficina:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
    $posY += $lineHeight + $espacioCampos;
    
    // Detalles (campo más grande)
    if ($numFormularios == 4) {
        // Para 4 formularios: altura base (3.5x) + 3 saltos de línea más
        $altoDetalles = ($lineHeight * 3.5) + ($lineHeight * 3);
    } else {
        // Para 6 formularios: altura estándar
        $altoDetalles = $lineHeight * 3;
    }
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $altoDetalles, $enc('Detalles:'), 0, 0, 'T');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $altoDetalles);
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

// Determinar cantidad de formularios según parámetro
$numFormularios = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 6; // Por defecto 6
if (!in_array($numFormularios, [4, 6])) {
    $numFormularios = 6; // Si no es válido, usar 6 por defecto
}

// Calcular filas según cantidad elegida
$filas = ($numFormularios == 4) ? 2 : 3; // 4 formularios = 2 filas, 6 formularios = 3 filas

// Calcular altura exacta de cada formulario
$altoDisponible = $altoA4 - $margenSuperior - $margenInferior;
$altoForm = ($altoDisponible - ($espacioEntreFormularios * ($filas - 1))) / $filas;

// Generar todos los formularios en una sola página (2 columnas)
for ($fila = 0; $fila < $filas; $fila++) {
    for ($col = 0; $col < $columnas; $col++) {
        $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
        $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
        
        dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoForm, $numFormularios);
    }
}

// Salida del PDF
$pdf->Output('D', 'Planilla_Relevamientos_' . date('Y-m-d') . '.pdf');
