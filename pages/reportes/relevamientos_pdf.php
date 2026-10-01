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

// Parámetros de tipo de planilla e inclusión de secciones
$tipo = $_GET['tipo'] ?? 'completa';

if ($tipo === 'parcial') {
    $incPc   = isset($_GET['inc_pc'])   ? (int)$_GET['inc_pc']   : 1;
    $incMon  = isset($_GET['inc_mon'])  ? (int)$_GET['inc_mon']  : 1;
    $incImp  = isset($_GET['inc_imp'])  ? (int)$_GET['inc_imp']  : 1;
    $incEsc  = isset($_GET['inc_esc'])  ? (int)$_GET['inc_esc']  : 1;
    $incPeri = isset($_GET['inc_peri']) ? (int)$_GET['inc_peri'] : 1;
    $incEst  = isset($_GET['inc_est'])  ? (int)$_GET['inc_est']  : 1;
    $incNb   = isset($_GET['inc_nb'])   ? (int)$_GET['inc_nb']   : 1;
    
    // Si desmarcó todo, forzar al menos la visualización de PC
    if (!$incPc && !$incMon && !$incImp && !$incEsc && !$incPeri && !$incEst && !$incNb) {
        $incPc = 1;
    }
} else {
    // Completa / Insumos: Incluye todas las secciones
    $incPc   = 1;
    $incMon  = 1;
    $incImp  = 1;
    $incEsc  = 1;
    $incPeri = 1;
    $incEst  = 1;
    $incNb   = 1;
}

// Función para dibujar el encabezado de la hoja (Localidad y Sede superiores)
function dibujarEncabezadoPagina($pdf, $enc, $titulo = 'PLANILLA DE RELEVAMIENTO DE INSUMOS') {
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetXY(10, 8);
    $pdf->Cell(190, 6, $enc($titulo), 0, 1, 'C');
    
    // Cuadros para Localidad, Sede y Fecha
    $pdf->SetFont('Arial', 'B', 10);
    
    // Localidad
    $pdf->SetXY(10, 16);
    $pdf->Cell(19, 6.5, $enc('Localidad:'), 0, 0, 'L');
    $pdf->Rect(29, 16, 48, 6.5);
    
    // Sede
    $pdf->SetXY(81, 16);
    $pdf->Cell(12, 6.5, $enc('Sede:'), 0, 0, 'L');
    $pdf->Rect(93, 16, 55, 6.5);
    
    // Fecha
    $pdf->SetXY(152, 16);
    $pdf->Cell(13, 6.5, $enc('Fecha:'), 0, 0, 'L');
    $pdf->Rect(165, 16, 35, 6.5);
    
    // Línea divisoria suave
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->Line(10, 25, 200, 25);
    $pdf->SetDrawColor(0, 0, 0); // Restaurar color
}

// Función auxiliar para dibujar casilleros de verificación (checkboxes)
function dibujarCheckbox($pdf, $x, $y, $texto, $enc, $anchoTexto = 0) {
    $pdf->Rect($x, $y + 0.3, 2.8, 2.8);
    $pdf->SetXY($x + 3.5, $y);
    $pdf->Cell($anchoTexto, 3.6, $enc($texto), 0, 0, 'L');
}

// Función para dibujar la Planilla de Infraestructura de Red y Telecomunicaciones
function generarPlanillaRed($pdf, $enc) {
    $pdf->AddPage();
    dibujarEncabezadoPagina($pdf, $enc, 'PLANILLA DE RELEVAMIENTO DE INFRAESTRUCTURA DE RED');
    
    // Datos Generales
    $pdf->SetFont('Arial', 'B', 9.5);
    
    // Fila 1: Proveedor y Tipo de conexión
    $pdf->SetXY(10, 28);
    $pdf->Cell(20, 6, $enc('Proveedor:'), 0, 0, 'L');
    $pdf->Rect(30, 28, 65, 6);
    
    $pdf->SetXY(100, 28);
    $pdf->Cell(28, 6, $enc('Tipo conexión:'), 0, 0, 'L');
    $pdf->Rect(128, 28, 72, 6);

    // Fila 2: Velocidad y ¿Tiene WiFi?
    $pdf->SetXY(10, 36);
    $pdf->Cell(20, 6, $enc('Velocidad:'), 0, 0, 'L');
    $pdf->Rect(30, 36, 65, 6);

    $pdf->SetXY(100, 36);
    $pdf->Cell(25, 6, $enc('¿Tiene WiFi?:'), 0, 0, 'L');
    
    dibujarCheckbox($pdf, 128, 37.5, 'Sí', $enc, 12);
    dibujarCheckbox($pdf, 150, 37.5, 'No', $enc, 12);

    // ---------------------------------------------------------
    // TABLA 1: DISPOSITIVOS DE RED (Filas a la mitad de altura: 7.5mm)
    // ---------------------------------------------------------
    $yTabla1 = 45;
    $columnasRed = [
        ['titulo' => 'Dispositivo',   'ancho' => 35],
        ['titulo' => 'Marca',         'ancho' => 30],
        ['titulo' => 'Modelo',        'ancho' => 30],
        ['titulo' => 'Cantidad',      'ancho' => 20],
        ['titulo' => 'Ubicación',     'ancho' => 35],
        ['titulo' => 'Observaciones', 'ancho' => 40]
    ];

    // Encabezado Tabla 1
    $pdf->SetFillColor(230, 235, 245);
    $pdf->SetFont('Arial', 'B', 8.5);
    $xCur = 10;
    foreach ($columnasRed as $col) {
        $pdf->SetXY($xCur, $yTabla1);
        $pdf->Cell($col['ancho'], 6, $enc($col['titulo']), 1, 0, 'C', true);
        $xCur += $col['ancho'];
    }

    // 15 Filas vacías para Dispositivos de Red (alto = 7.5 mm)
    $altoFila = 7.5;
    $cantFilasRed = 15;
    $pdf->SetFillColor(255, 255, 255);
    for ($i = 0; $i < $cantFilasRed; $i++) {
        $yFila = $yTabla1 + 6 + ($i * $altoFila);
        $xCur = 10;
        foreach ($columnasRed as $col) {
            $pdf->Rect($xCur, $yFila, $col['ancho'], $altoFila);
            $xCur += $col['ancho'];
        }
    }

    // ---------------------------------------------------------
    // TABLA 2: LÍNEAS TELEFÓNICAS
    // ---------------------------------------------------------
    $ySeccionTel = $yTabla1 + 6 + ($cantFilasRed * $altoFila) + 5;
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, $ySeccionTel);
    $pdf->Cell(190, 5, $enc('RELEVAMIENTO DE LÍNEAS TELEFÓNICAS'), 0, 1, 'L');

    $yTabla2 = $ySeccionTel + 6;
    $columnasTel = [
        ['titulo' => 'Responsable',   'ancho' => 60],
        ['titulo' => 'Número',        'ancho' => 40],
        ['titulo' => 'Tipo',          'ancho' => 45],
        ['titulo' => 'Operador',      'ancho' => 45]
    ];

    // Encabezado Tabla 2
    $pdf->SetFillColor(230, 235, 245);
    $pdf->SetFont('Arial', 'B', 8.5);
    $xCur = 10;
    foreach ($columnasTel as $col) {
        $pdf->SetXY($xCur, $yTabla2);
        $pdf->Cell($col['ancho'], 6, $enc($col['titulo']), 1, 0, 'C', true);
        $xCur += $col['ancho'];
    }

    // 6 Filas vacías para Líneas Telefónicas (alto = 7.5 mm)
    $cantFilasTel = 6;
    for ($i = 0; $i < $cantFilasTel; $i++) {
        $yFila = $yTabla2 + 6 + ($i * $altoFila);
        $xCur = 10;
        foreach ($columnasTel as $col) {
            $pdf->Rect($xCur, $yFila, $col['ancho'], $altoFila);
            $xCur += $col['ancho'];
        }
    }
}

// Función para dibujar la Planilla de Sistema de Vigilancia
function generarPlanillaVigilancia($pdf, $enc) {
    $pdf->AddPage();
    dibujarEncabezadoPagina($pdf, $enc, 'PLANILLA DE RELEVAMIENTO DE SISTEMA DE VIGILANCIA');
    
    // Datos Generales
    $pdf->SetFont('Arial', 'B', 9.5);
    
    // Fila 1: Proveedor y Tipo
    $pdf->SetXY(10, 28);
    $pdf->Cell(20, 6.5, $enc('Proveedor:'), 0, 0, 'L');
    $pdf->Rect(30, 28, 65, 6.5);
    
    $pdf->SetXY(100, 28);
    $pdf->Cell(12, 6.5, $enc('Tipo:'), 0, 0, 'L');
    $pdf->Rect(112, 28, 88, 6.5);

    // Tabla de Dispositivos (15 Filas a la mitad de altura: 7.5mm)
    $yTabla = 38.5;
    $columnas = [
        ['titulo' => 'Dispositivo',   'ancho' => 35],
        ['titulo' => 'Marca',         'ancho' => 30],
        ['titulo' => 'Modelo',        'ancho' => 30],
        ['titulo' => 'Cantidad',      'ancho' => 20],
        ['titulo' => 'Ubicación',     'ancho' => 35],
        ['titulo' => 'Observaciones', 'ancho' => 40]
    ];

    // Encabezado de Tabla
    $pdf->SetFillColor(230, 235, 245);
    $pdf->SetFont('Arial', 'B', 9);
    $xCur = 10;
    foreach ($columnas as $col) {
        $pdf->SetXY($xCur, $yTabla);
        $pdf->Cell($col['ancho'], 6.5, $enc($col['titulo']), 1, 0, 'C', true);
        $xCur += $col['ancho'];
    }

    // 15 Filas vacías (alto = 7.5 mm)
    $altoFila = 7.5;
    $pdf->SetFillColor(255, 255, 255);
    for ($i = 0; $i < 15; $i++) {
        $yFila = $yTabla + 6.5 + ($i * $altoFila);
        $xCur = 10;
        foreach ($columnas as $col) {
            $pdf->Rect($xCur, $yFila, $col['ancho'], $altoFila);
            $xCur += $col['ancho'];
        }
    }
}

// Función para dibujar un formulario de PC / Insumos
function dibujarFormulario($pdf, $x, $y, $enc, $anchoFormulario, $altoFormulario, $numFormularios = 4, $incPc = 1, $incMon = 1, $incImp = 1, $incEsc = 1, $incPeri = 1, $incEst = 1, $altoEstadoFisicoFijado = null) {
    
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    $tamanoTitulo = 11.0;
    $tamanoFuente = 8.8;
    $lineHeight = 4.9;
    $margenInterno = 2.5;
    $espacioCampos = 0.5;
    $anchoEtiqueta = 40;
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', $tamanoTitulo);
    $pdf->SetXY($x + $margenInterno, $y + 2.5);
    $pdf->Cell($anchoFormulario - ($margenInterno * 2), 5, $enc('RELEVAMIENTO INSUMOS'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + $margenInterno, $y + 8, $x + $anchoFormulario - $margenInterno, $y + 8);
    
    // Configurar fuente para campos
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $posY = $y + 9.5;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - ($margenInterno * 2);
    
    // Construcción dinámica de campos a incluir
    $campos = [];

    if ($incPc) {
        $campos[] = 'ID PC:';
        $campos[] = 'Número de Serie PC:';
        $campos[] = 'Procesador:';
        $campos[] = 'RAM:';
        $campos[] = 'Almacenamiento:';
        $campos[] = 'Sistema Operativo:';
        $campos[] = 'Motherboard:';
    }

    if ($incMon) {
        $campos[] = 'ID Monitor:';
        $campos[] = 'Monitor número de serie:';
        $campos[] = 'Monitor marca/modelo:';
        $campos[] = 'Monitor pulgadas/conexión:';
    }

    if ($incImp) {
        $campos[] = 'ID Impresora:';
        $campos[] = 'Impresora número de serie:';
        $campos[] = 'Impresora marca/modelo:';
    }

    if ($incEsc) {
        $campos[] = 'ID Escáner:';
        $campos[] = 'Núm. de serie escáner:';
        $campos[] = 'Escáner marca/modelo:';
    }

    if ($incPeri) {
        $campos[] = 'Periféricos:';
        $campos[] = 'Teclado marca/modelo:';
        $campos[] = 'Mouse marca/modelo:';
        $campos[] = 'Auriculares marca/modelo:';
        $campos[] = 'Parlantes marca/modelo:';
        $campos[] = 'Webcam marca/modelo:';
    }

    if ($incEst) {
        $campos[] = 'Estabilizador:';
    }

    foreach ($campos as $etiq) {
        $esHeaderID = in_array($etiq, ['ID PC:', 'ID Monitor:', 'ID Impresora:', 'ID Escáner:', 'Periféricos:', 'Estabilizador:']);
        if ($esHeaderID) {
            $pdf->SetFont('Arial', 'B', $tamanoFuente);
        } else {
            $pdf->SetFont('Arial', '', $tamanoFuente);
        }

        $pdf->SetXY($x + $margenInterno, $posY);
        $pdf->Cell($anchoEtiqueta, $lineHeight, $enc($etiq), 0, 0, 'L');

        if ($esHeaderID) {
            $anchoTexto = $pdf->GetStringWidth($enc($etiq));
            $pdf->SetDrawColor(160, 160, 160); // Subrayado gris sutil
            $pdf->Line($x + $margenInterno, $posY + $lineHeight - 0.4, $x + $margenInterno + $anchoTexto, $posY + $lineHeight - 0.4);
            $pdf->SetDrawColor(0, 0, 0); // Restaurar negro
        }

        // Periféricos NO lleva recuadro para ingresar datos
        if ($etiq !== 'Periféricos:') {
            $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
        }

        $posY += $lineHeight + $espacioCampos;
    }
    
    // Estado Físico
    if ($altoEstadoFisicoFijado !== null) {
        $altoEstadoFisico = $altoEstadoFisicoFijado;
    } else {
        $espacioLibre = ($y + $altoFormulario - $margenInterno) - $posY;
        $altoEstadoFisico = max($lineHeight, $espacioLibre);
    }
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Estado Físico:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $altoEstadoFisico);
}

// Función para dibujar un formulario de Notebook
function dibujarFormularioNotebook($pdf, $x, $y, $enc, $anchoFormulario, $altoFormulario, $altoEstadoFisicoFijado = null) {
    // Bordes del formulario
    $pdf->Rect($x, $y, $anchoFormulario, $altoFormulario);
    
    $tamanoTitulo = 11.0;
    $tamanoFuente = 8.8;
    $lineHeight = 5.4;
    $margenInterno = 2.5;
    $espacioCampos = 0.6;
    $anchoEtiqueta = 40;
    
    // Título del formulario
    $pdf->SetFont('Arial', 'B', $tamanoTitulo);
    $pdf->SetXY($x + $margenInterno, $y + 2.5);
    $pdf->Cell($anchoFormulario - ($margenInterno * 2), 5, $enc('RELEVAMIENTO NOTEBOOK'), 0, 0, 'C');
    
    // Línea separadora
    $pdf->Line($x + $margenInterno, $y + 8, $x + $anchoFormulario - $margenInterno, $y + 8);
    
    // Configurar fuente para campos
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $posY = $y + 9.5;
    $anchoCampo = $anchoFormulario - $anchoEtiqueta - ($margenInterno * 2);
    
    $camposNB = [
        'ID Notebook:',
        'Núm. de serie notebook:',
        'Notebook marca/modelo:',
        'Procesador:',
        'RAM:',
        'Almacenamiento:',
        'Sistema Operativo:',
        'Motherboard:'
    ];

    foreach ($camposNB as $etiq) {
        $esHeaderID = ($etiq === 'ID Notebook:');
        if ($esHeaderID) {
            $pdf->SetFont('Arial', 'B', $tamanoFuente);
        } else {
            $pdf->SetFont('Arial', '', $tamanoFuente);
        }

        $pdf->SetXY($x + $margenInterno, $posY);
        $pdf->Cell($anchoEtiqueta, $lineHeight, $enc($etiq), 0, 0, 'L');

        if ($esHeaderID) {
            $anchoTexto = $pdf->GetStringWidth($enc($etiq));
            $pdf->SetDrawColor(160, 160, 160);
            $pdf->Line($x + $margenInterno, $posY + $lineHeight - 0.4, $x + $margenInterno + $anchoTexto, $posY + $lineHeight - 0.4);
            $pdf->SetDrawColor(0, 0, 0);
        }

        $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $lineHeight);
        $posY += $lineHeight + $espacioCampos;
    }

    // Sección Accesorios (Checkboxes horizontales)
    $pdf->SetFont('Arial', 'B', $tamanoFuente);
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoFormulario - ($margenInterno * 2), $lineHeight, $enc('Accesorios:'), 0, 0, 'L');
    $posY += $lineHeight + 0.4;
    
    $pdf->SetFont('Arial', '', $tamanoFuente);
    // Fila 1 de Checkboxes (Cargador, Funda, Caja)
    dibujarCheckbox($pdf, $x + $margenInterno, $posY, 'Cargador', $enc, 22);
    dibujarCheckbox($pdf, $x + $margenInterno + 38, $posY, 'Funda', $enc, 20);
    dibujarCheckbox($pdf, $x + $margenInterno + 63, $posY, 'Caja', $enc, 18);
    $posY += 4.5;

    // Fila 2 de Checkboxes (Adaptador de red, Micro SD GB)
    dibujarCheckbox($pdf, $x + $margenInterno, $posY, 'Adaptador de red', $enc, 35);
    
    // Checkbox Micro SD y Cuadro GB
    dibujarCheckbox($pdf, $x + $margenInterno + 38, $posY, 'Micro SD:', $enc, 15);
    $pdf->Rect($x + $margenInterno + 57, $posY + 0.3, 13, 3.0);
    $pdf->SetXY($x + $margenInterno + 71, $posY);
    $pdf->Cell(8, 3.6, $enc('GB'), 0, 0, 'L');
    $posY += 4.8;

    // Estado Físico
    if ($altoEstadoFisicoFijado !== null) {
        $altoEstadoFisico = $altoEstadoFisicoFijado;
    } else {
        $espacioLibre = ($y + $altoFormulario - $margenInterno) - $posY;
        $altoEstadoFisico = max($lineHeight, $espacioLibre);
    }
    $pdf->SetFont('Arial', '', $tamanoFuente);
    $pdf->SetXY($x + $margenInterno, $posY);
    $pdf->Cell($anchoEtiqueta, $lineHeight, $enc('Estado Físico:'), 0, 0, 'L');
    $pdf->Rect($x + $anchoEtiqueta + $margenInterno, $posY, $anchoCampo, $altoEstadoFisico);
}

// ----------------------------------------------------
// GENERACIÓN DE DOCUMENTO PDF (Formato 2x2 Dinámico)
// ----------------------------------------------------

// Modo de salida: Inline ('I') para visualización en navegador/visor, o Download ('D') si viene descargar=1
$modoSalida = (isset($_GET['descargar']) && $_GET['descargar'] === '1') ? 'D' : 'I';

if ($tipo === 'red') {
    generarPlanillaRed($pdf, $enc);
    $pdf->Output($modoSalida, 'Planilla_Relevamiento_Red_' . date('Y-m-d') . '.pdf');
    exit;
} elseif ($tipo === 'vigilancia') {
    generarPlanillaVigilancia($pdf, $enc);
    $pdf->Output($modoSalida, 'Planilla_Relevamiento_Vigilancia_' . date('Y-m-d') . '.pdf');
    exit;
}

$margenSuperior = 28;
$margenInferior = 10;
$margenLateral = 10;
$espacioEntreFormularios = 5;

$anchoA4 = 210; // mm
$altoA4 = 297; // mm

$altoDisponible = $altoA4 - $margenSuperior - $margenInferior; // 259 mm

$columnas = 2;
$anchoForm = ($anchoA4 - ($margenLateral * 2) - $espacioEntreFormularios) / $columnas; // 92.5 mm

$generarPC = ($incPc || $incMon || $incImp || $incEsc || $incPeri || $incEst);

$pdf->AddPage();
dibujarEncabezadoPagina($pdf, $enc, 'PLANILLA DE RELEVAMIENTO DE INSUMOS');

if ($generarPC && $incNb) {
    // Contar cuántos campos se activaron dinámicamente en Insumos
    $numCamposInsumos = 0;
    if ($incPc)   $numCamposInsumos += 7;
    if ($incMon)  $numCamposInsumos += 4;
    if ($incImp)  $numCamposInsumos += 3;
    if ($incEsc)  $numCamposInsumos += 3;
    if ($incPeri) $numCamposInsumos += 6; // Periféricos cabecera + Teclado, Mouse, Auriculares, Parlantes, Webcam
    if ($incEst)  $numCamposInsumos += 1;

    // Altura fija consumida por los renglones de campos
    $lineHeightInsumos = 4.9;
    $espacioCamposInsumos = 0.5;
    $altoFijoInsumos = 9.5 + ($numCamposInsumos * ($lineHeightInsumos + $espacioCamposInsumos));

    $lineHeightNB = 5.4;
    $espacioCamposNB = 0.6;
    $altoFijoNotebook = 9.5 + (8 * ($lineHeightNB + $espacioCamposNB)) + 14.0; // 71.5 mm

    // Espacio total usable para ambas tarjetas
    $altoDisponibleTotal = $altoDisponible - $espacioEntreFormularios; // 254 mm
    $espacioLibreTotalEF = $altoDisponibleTotal - $altoFijoInsumos - $altoFijoNotebook - 6; // 6 mm de márgenes internos
    
    if ($espacioLibreTotalEF < 15) {
        $espacioLibreTotalEF = 15;
    }

    // Repartir 66% para Estado Físico de Insumos y 34% para Estado Físico de Notebooks
    $altoEF1 = round($espacioLibreTotalEF * 0.66, 1);
    $altoEF2 = round($espacioLibreTotalEF * 0.34, 1);

    // Alturas finales de cada tarjeta
    $altoForm1 = round($altoFijoInsumos + $altoEF1 + 3, 1);
    $altoForm2 = round($altoDisponibleTotal - $altoForm1, 1);

    for ($col = 0; $col < 2; $col++) {
        $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
        
        // Fila 0 (Superior): PC / Insumos
        $yTop = $margenSuperior;
        dibujarFormulario($pdf, $x, $yTop, $enc, $anchoForm, $altoForm1, 4, $incPc, $incMon, $incImp, $incEsc, $incPeri, $incEst, $altoEF1);

        // Fila 1 (Inferior): Notebook
        $yBot = $margenSuperior + $altoForm1 + $espacioEntreFormularios;
        dibujarFormularioNotebook($pdf, $x, $yBot, $enc, $anchoForm, $altoForm2, $altoEF2);
    }
} elseif ($generarPC) {
    // Solo PC / Insumos (Notebooks desmarcado)
    $numCamposInsumos = 0;
    if ($incPc)   $numCamposInsumos += 7;
    if ($incMon)  $numCamposInsumos += 4;
    if ($incImp)  $numCamposInsumos += 3;
    if ($incEsc)  $numCamposInsumos += 3;
    if ($incPeri) $numCamposInsumos += 6;
    if ($incEst)  $numCamposInsumos += 1;

    $lineHeightInsumos = 4.9;
    $espacioCamposInsumos = 0.5;
    $altoFijoInsumos = 9.5 + ($numCamposInsumos * ($lineHeightInsumos + $espacioCamposInsumos));

    // Si los campos caben en 127 mm (por ej: PC, Monitores, Periféricos y Estabilizadores <= 18 campos), usar formato 2x2 (4 tarjetas)
    if ($numCamposInsumos <= 18) {
        $altoForm = ($altoDisponible - $espacioEntreFormularios) / 2; // 127 mm
        for ($fila = 0; $fila < 2; $fila++) {
            for ($col = 0; $col < 2; $col++) {
                $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
                $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
                
                dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoForm, 4, $incPc, $incMon, $incImp, $incEsc, $incPeri, $incEst);
            }
        }
    } else {
        // Si hay muchos campos (ej: 24 campos completos), 2 tarjetas a la altura completa de la página (259 mm)
        $columnasPC = 2;
        $altoFormPC = $altoDisponible; // 259 mm

        for ($col = 0; $col < $columnasPC; $col++) {
            $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
            $y = $margenSuperior;
            
            dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoFormPC, 2, $incPc, $incMon, $incImp, $incEsc, $incPeri, $incEst);
        }
    }
} elseif ($incNb) {
    // Solo Notebooks (Insumos desmarcado: 4 tarjetas Notebook de 2x2)
    $altoForm = ($altoDisponible - $espacioEntreFormularios) / 2; // 127 mm
    for ($fila = 0; $fila < 2; $fila++) {
        for ($col = 0; $col < 2; $col++) {
            $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
            $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
            
            dibujarFormularioNotebook($pdf, $x, $y, $enc, $anchoForm, $altoForm);
        }
    }
} elseif ($generarPC) {
    // Solo PC / Insumos (4 tarjetas PC de 2x2)
    $altoForm = ($altoDisponible - $espacioEntreFormularios) / 2; // 127 mm
    for ($fila = 0; $fila < 2; $fila++) {
        for ($col = 0; $col < 2; $col++) {
            $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
            $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
            
            dibujarFormulario($pdf, $x, $y, $enc, $anchoForm, $altoForm, 4, $incPc, $incMon, $incImp, $incEsc, $incPeri);
        }
    }
} elseif ($incNb) {
    // Solo Notebooks (4 tarjetas Notebook de 2x2)
    $altoForm = ($altoDisponible - $espacioEntreFormularios) / 2; // 127 mm
    for ($fila = 0; $fila < 2; $fila++) {
        for ($col = 0; $col < 2; $col++) {
            $x = $margenLateral + ($col * ($anchoForm + $espacioEntreFormularios));
            $y = $margenSuperior + ($fila * ($altoForm + $espacioEntreFormularios));
            
            dibujarFormularioNotebook($pdf, $x, $y, $enc, $anchoForm, $altoForm);
        }
    }
}

// Salida del PDF
$nombreArchivo = ($tipo === 'parcial') ? 'Planilla_Relevamiento_Parcial_' : 'Planilla_Relevamiento_Completa_';
$pdf->Output($modoSalida, $nombreArchivo . date('Y-m-d') . '.pdf');
