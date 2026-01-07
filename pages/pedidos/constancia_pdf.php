<?php
require_once '../../includes/config.php';
requerirAutenticacion();
// Permiso ver_propios mínimo
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    die('No tienes permiso');
}

use setasign\Fpdi\Fpdi;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$db = conectarDB();
// Obtener datos del pedido
$stmt = $db->prepare("SELECT p.*, 
                        u_sol.nombre as sol_nom, u_sol.apellido as sol_ape,
                        s.nombre_sede,
                        l.nombre_localidad
                      FROM pedidos p
                      JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                      JOIN sedes s ON p.id_sede = s.id_sede
                      JOIN localidades l ON s.id_localidad = l.id_localidad
                      WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die('Pedido no encontrado');

// Helper para decode utf8
function u($s) { return utf8_decode($s ?? ''); }

// Crear PDF
$pdf = new Fpdi();
$pdf->AddPage();

// Título
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, u('CONSTANCIA DE RECEPCIÓN / SOLICITUD'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, u('N° de Pedido: ') . $data['id_pedido'], 0, 1, 'C');
$pdf->Ln(5);

// Bloque Fecha
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, u('Fecha de Solicitud: ') . date('d/m/Y H:i', strtotime($data['fecha_creacion'])), 0, 1, 'R');
$pdf->Ln(5);

// Bloque Solicitante
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, u('DATOS DEL SOLICITANTE'), 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(2);

$pdf->Cell(40, 6, u('Nombre:'), 0, 0);
$pdf->Cell(0, 6, u($data['sol_nom'] . ' ' . $data['sol_ape']), 0, 1);

$pdf->Cell(40, 6, u('Sede:'), 0, 0);
$pdf->Cell(0, 6, u($data['nombre_sede']), 0, 1);

$pdf->Cell(40, 6, u('Localidad:'), 0, 0);
$pdf->Cell(0, 6, u($data['nombre_localidad']), 0, 1);

$pdf->Ln(5);

// Bloque Detalle
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, u('DETALLE DE LA SOLICITUD'), 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(2);

$pdf->Cell(40, 6, u('Tipo:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u($data['tipo']), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(40, 6, u('Prioridad:'), 0, 0);
$pdf->Cell(0, 6, u($data['prioridad']), 0, 1);

$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u('Asunto:'), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, u($data['titulo']), 0, 'L');

$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, u('Descripción / Detalles:'), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, u($data['descripcion']), 0, 'L');

$pdf->Ln(20);

// Firmas
$y = $pdf->GetY();
if ($y > 240) { $pdf->AddPage(); $y = 30; }

$pdf->Line(20, $y, 80, $y);
$pdf->Line(130, $y, 190, $y);

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(20, $y + 2);
$pdf->Cell(60, 4, u('Entregado por (Firma)'), 0, 0, 'C');
$pdf->SetXY(20, $y + 6);
$pdf->Cell(60, 4, u($data['sol_nom'] . ' ' . $data['sol_ape']), 0, 0, 'C');


$pdf->SetXY(130, $y + 2);
$pdf->Cell(60, 4, u('Recibido por (Firma y aclaración)'), 0, 0, 'C');

$pdf->Output('I', 'Constancia_' . $id . '.pdf');
?>
