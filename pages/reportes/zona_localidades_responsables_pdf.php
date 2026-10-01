<?php
// pages/reportes/zona_localidades_responsables_pdf.php
// Generador de PDF oficial con campos interactivos editables (AcroForm) para Estructura Territorial y Delegados

require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('reportes', 'ver');

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Clase FPDF con soporte nativo para Campos de Formulario Interactivos (AcroForm)
 * Permite que los cuadros de texto sean editables directamente dentro de cualquier visor de PDF (Adobe, Chrome, Edge).
 */
class FPDF_Form extends FPDF {
    protected $forms = [];

    public function TextField($name, $w, $h, $x = null, $y = null, $value = '') {
        if ($x !== null) $this->SetX($x);
        if ($y !== null) $this->SetY($y);
        
        $x = $this->GetX();
        $y = $this->GetY();
        
        $this->forms[] = [
            'name'  => $name,
            'x'     => $x,
            'y'     => $y,
            'w'     => $w,
            'h'     => $h,
            'value' => $value,
            'page'  => $this->page
        ];
        
        // Dibujar recuadro suave como contenedor del campo editable
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(203, 213, 225); // #cbd5e1
        $this->SetFillColor(248, 250, 252); // #f8fafc
        $this->Rect($x, $y, $w, $h, 'DF');

        // Si ya trae valor por defecto, escribirlo
        if ($value !== '') {
            $this->SetFont('Arial', '', 7.5);
            $this->SetTextColor(15, 23, 42);
            $this->SetXY($x + 1, $y + 0.5);
            $this->Cell($w - 2, $h - 1, $this->encodeText($value), 0, 0, 'L');
        }
    }

    protected function encodeText($s) {
        if ($s === null) return '';
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
        return ($out !== false) ? $out : utf8_decode((string)$s);
    }

    protected function _putcatalog() {
        parent::_putcatalog();
        if (!empty($this->forms)) {
            $this->_out('/AcroForm << /Fields [' . $this->_getfieldsobjects() . '] /NeedAppearances true >>');
        }
    }

    protected function _getfieldsobjects() {
        $refs = [];
        foreach ($this->forms as $i => $form) {
            $n = $this->n + 1 + $i;
            $refs[] = $n . ' 0 R';
        }
        return implode(' ', $refs);
    }

    protected function _putendpage() {
        parent::_putendpage();
        if (!empty($this->forms)) {
            foreach ($this->forms as $i => $form) {
                if ($form['page'] == $this->page) {
                    $this->_newobj();
                    $h = $this->h;
                    $x1 = $form['x'] * $this->k;
                    $y1 = ($h - $form['y'] - $form['h']) * $this->k;
                    $x2 = ($form['x'] + $form['w']) * $this->k;
                    $y2 = ($h - $form['y']) * $this->k;
                    
                    $val = $this->_escape($this->encodeText($form['value']));
                    $name = $this->_escape($this->encodeText($form['name']));
                    
                    $this->_out('<</Type /Annot /Subtype /Widget /FT /Tx');
                    $this->_out('/Rect [' . sprintf('%.2f %.2f %.2f %.2f', $x1, $y1, $x2, $y2) . ']');
                    $this->_out('/T (' . $name . ') /V (' . $val . ') /DV (' . $val . ')');
                    $this->_out('/F 4 /BS <</W 1 /S /S>> /MK <</BC [0.8 0.85 0.9] /BG [0.97 0.98 0.99]>>');
                    $this->_out('/DA (/Helv 8 Tf 0.06 0.09 0.16 g)>>');
                    $this->_out('endobj');
                }
            }
        }
    }
}

// Obtener delegados enviados por POST/GET
$delegados = $_POST['delegados'] ?? $_GET['delegados'] ?? [];

// Helper de codificación
$enc = function($s) {
    if ($s === null) return '';
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
    return ($out !== false) ? $out : utf8_decode((string)$s);
};

// Crear PDF Horizontal A4 Landscape
$pdf = new FPDF_Form('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetTitle('Estructura Territorial Zonas y Delegados Responsables');
$pdf->AddPage();

// Datos de Zonas y Localidades (El Bolsón en Zona Andina)
$zonas_datos = [
    1 => ['nombre' => 'ZONA VALLE INFERIOR', 'localidades' => ['Viedma', 'General Conesa']],
    2 => ['nombre' => 'ZONA ALTO VALLE ESTE', 'localidades' => ['Villa Regina', 'Chichinales', 'Ing. Huergo']],
    3 => ['nombre' => 'ZONA ATLÁNTICA', 'localidades' => ['San Antonio Oeste', 'Sierra Grande', 'Valcheta']],
    4 => ['nombre' => 'ZONA ALTO VALLE CENTRO', 'localidades' => ['General Roca', 'Allen']],
    5 => ['nombre' => 'ZONA VALLE MEDIO', 'localidades' => ['Choele Choel', 'Lamarque', 'Luis Beltrán', 'Darwin', 'Belisle', 'Chimpay', 'Río Colorado', 'Colonia Josefa']],
    6 => ['nombre' => 'ZONA ALTO VALLE OESTE I', 'localidades' => ['Cipolletti', 'Fernández Oro']],
    7 => ['nombre' => 'ZONA ALTO VALLE OESTE II', 'localidades' => ['Cinco Saltos']],
    8 => ['nombre' => 'ZONA ALTO VALLE OESTE III', 'localidades' => ['Catriel']],
    9 => ['nombre' => 'ZONA LÍNEA SUR', 'localidades' => ['Ramos Mexía', 'Sierra Colorada', 'Los Menucos', 'Maquinchao', 'Ing. Jacobacci', 'Comallo']],
    10 => ['nombre' => 'ZONA ANDINA', 'localidades' => ['Bariloche', 'El Bolsón']]
];

$columnas = [
    [1, 2, 3],       // Columna 1
    [4, 5],          // Columna 2
    [6, 7, 8, 10],   // Columna 3 (incluye Bariloche y El Bolsón en Zona Andina)
    [9]              // Columna 4
];

// 1. DIBUJAR ENCABEZADO INSTITUCIONAL
$pdf->SetLineWidth(0.4);
$pdf->SetDrawColor(0, 0, 0);

// Caja izquierda: Secretaría
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(8, 8, 115, 14, 'DF');
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetXY(8, 9.5);
$pdf->Cell(115, 4, $enc('SECRETARÍA DE NIÑEZ, ADOLESCENCIA Y FAMILIA'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 7);
$pdf->SetX(8);
$pdf->Cell(115, 4, $enc('DIRECCIÓN DE INFORMÁTICA, TELECOMUNICACIONES Y REGISTRO ÚNICO NOMINAL'), 0, 1, 'C');

// Caja derecha: Título
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(126, 8, 163, 14, 'DF');
$pdf->SetFont('Arial', 'B', 9.5);
$pdf->SetXY(126, 12.5);
$pdf->Cell(163, 5, $enc('ESTRUCTURA TERRITORIAL: ZONAS, LOCALIDADES Y DELEGADOS RESPONSABLES'), 0, 1, 'C');

// 2. DIBUJAR COLUMNAS Y ZONAS CON CAMPOS EDITABLES
$startX = 8;
$startY = 25;
$colWidth = 68;
$colGap = 3;

foreach ($columnas as $colIndex => $colZonas) {
    $currentX = $startX + ($colIndex * ($colWidth + $colGap));
    $currentY = $startY;

    foreach ($colZonas as $idZona) {
        if (!isset($zonas_datos[$idZona])) continue;
        $z = $zonas_datos[$idZona];

        // Título de la Zona
        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetLineWidth(0.35);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Rect($currentX, $currentY, $colWidth, 5.5, 'DF');
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($currentX, $currentY + 0.8);
        $pdf->Cell($colWidth, 4, $enc($z['nombre']), 0, 1, 'C');
        $currentY += 5.5;

        $zoneStartY = $currentY;
        
        foreach ($z['localidades'] as $loc) {
            $cardY = $currentY + 1.2;
            $cardHeight = 10;

            // Tarjeta de Localidad
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetLineWidth(0.25);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect($currentX + 1.5, $cardY, $colWidth - 3, $cardHeight, 'DF');

            // Nombre de la localidad (Sin el prefijo "LOCALIDAD:")
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetXY($currentX + 2, $cardY + 1);
            $pdf->Cell($colWidth - 4, 3.5, $enc(mb_strtoupper($loc, 'UTF-8')), 0, 1, 'C');

            // Etiqueta Delegado:
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->SetXY($currentX + 2.5, $cardY + 5.2);
            $pdf->Cell(14, 3.8, $enc('DELEGADO:'), 0, 0, 'L');

            // Campo de Texto Interactivo Editable (AcroForm)
            $nombreDelegado = $delegados[$loc] ?? '';
            $fieldName = 'delegado_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $loc);
            $pdf->TextField($fieldName, $colWidth - 21, 4, $currentX + 17.5, $cardY + 5, $nombreDelegado);

            $currentY += $cardHeight + 1.2;
        }

        // Borde exterior de la zona
        $pdf->SetLineWidth(0.35);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Rect($currentX, $zoneStartY, $colWidth, ($currentY - $zoneStartY + 1), 'D');
        $currentY += 3;
    }
}

// Salida inline en el navegador
$pdf->Output('I', 'Estructura_Territorial_Zonas_Delegados.pdf');
