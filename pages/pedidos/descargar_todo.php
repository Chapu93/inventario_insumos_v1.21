<?php
require_once '../../includes/config.php';
requerirAutenticacion();

// El permiso es ver el pedido
if (!tienePermiso('pedidos', 'ver_propios') && !tienePermiso('pedidos', 'ver_todos')) {
    die('No tienes permiso para descargar archivos');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$db = conectarDB();

// Obtener datos del pedido
$stmt = $db->prepare("SELECT p.*, r.numero_remito FROM pedidos p LEFT JOIN remitos r ON p.id_remito = r.id_remito WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) die('Pedido no encontrado');

// Variables para evitar el exit en los scripts incluidos
$no_exit_pdf = true;

// 1. Generar/Obtener el primer documento (Remito o Informe)
$mergedPdf = new \setasign\Fpdi\Fpdi();

try {
    if ($pedido['tipo'] === 'Pedido Insumo') {
        if (!empty($pedido['numero_remito'])) {
            // Cargar Remito
            $_GET['remito'] = $pedido['numero_remito'];
            ob_start();
            include '../reportes/remito_pdf.php';
            $pdfContent = ob_get_clean();
            
            if ($pdfContent && strlen($pdfContent) > 100) {
                $tempFile = tempnam(sys_get_temp_dir(), 'rem');
                file_put_contents($tempFile, $pdfContent);
                importPdfPages($mergedPdf, $tempFile);
                unlink($tempFile);
            } else {
                Logger::error("Error capturando remito PDF: contenido vacío o muy corto", ['pedido' => $id]);
            }
        }
    } else {
        // Para Reparación, Soporte, Mantenimiento, Tarea Interna
        // Verificar si tiene informe técnico
        $stmtInf = $db->prepare("SELECT id_informe FROM pedidos_informes WHERE id_pedido = ?");
        $stmtInf->execute([$id]);
        if ($stmtInf->fetch()) {
            $_GET['id'] = $id;
            ob_start();
            include 'informe_pdf.php';
            $pdfContent = ob_get_clean();
            
            if ($pdfContent && strlen($pdfContent) > 100) {
                $tempFile = tempnam(sys_get_temp_dir(), 'inf');
                file_put_contents($tempFile, $pdfContent);
                importPdfPages($mergedPdf, $tempFile);
                unlink($tempFile);
            } else {
                Logger::error("Error capturando informe PDF: contenido vacío o muy corto", ['pedido' => $id]);
            }
        }

        // TAMBIÉN anexar la Constancia de Recepción
        $_GET['id'] = $id;
        ob_start();
        include 'constancia_pdf.php';
        $pdfConstancia = ob_get_clean();
        if ($pdfConstancia && strlen($pdfConstancia) > 100) {
            $tempFile = tempnam(sys_get_temp_dir(), 'con');
            file_put_contents($tempFile, $pdfConstancia);
            importPdfPages($mergedPdf, $tempFile);
            unlink($tempFile);
        }
    }

    // 2. Adjuntar Nota Solicitud (si existe)
    if (!empty($pedido['pdf_nota'])) {
        $notaPath = UPLOAD_BASE_DIR . 'pedidos/' . $pedido['pdf_nota'];
        if (file_exists($notaPath)) {
            $ext = strtolower(pathinfo($notaPath, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                importPdfPages($mergedPdf, $notaPath);
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $mergedPdf->AddPage();
                $mergedPdf->Image($notaPath, 10, 10, 190);
            }
        }
    }

    // 3. Adjuntar Remito Firmado (si existe)
    if (!empty($pedido['remito_firmado'])) {
        $firmadoPath = UPLOAD_BASE_DIR . 'pedidos/' . $pedido['remito_firmado'];
        if (!empty($pedido['id_remito'])) {
            $remitoFirmadoPath = UPLOAD_BASE_DIR . 'remitos_firmados/' . $pedido['remito_firmado'];
            if (file_exists($remitoFirmadoPath)) {
                $firmadoPath = $remitoFirmadoPath;
            }
        }
        
        if (file_exists($firmadoPath)) {
            $ext = strtolower(pathinfo($firmadoPath, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                importPdfPages($mergedPdf, $firmadoPath);
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $mergedPdf->AddPage();
                $mergedPdf->Image($firmadoPath, 10, 10, 190);
            }
        }
    }

    // 4. Adjuntar otros archivos de pedidos_adjuntos
    $stmtAdj = $db->prepare("SELECT ruta_archivo FROM pedidos_adjuntos WHERE id_pedido = ? ORDER BY fecha_carga ASC");
    $stmtAdj->execute([$id]);
    $adjuntos = $stmtAdj->fetchAll(PDO::FETCH_ASSOC);

    foreach ($adjuntos as $adj) {
        $adjPath = UPLOAD_BASE_DIR . 'pedidos/' . $adj['ruta_archivo'];
        if (file_exists($adjPath)) {
            $ext = strtolower(pathinfo($adjPath, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                importPdfPages($mergedPdf, $adjPath);
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $mergedPdf->AddPage();
                // Ajustar imagen al ancho de la página (con márgenes)
                $mergedPdf->Image($adjPath, 10, 10, 190);
            }
        }
    }

    // Limpiar cualquier salida accidental antes del PDF final
    if (ob_get_level()) ob_end_clean();
    
    // Salida del PDF final
    $mergedPdf->Output('D', 'Pedido_' . $id . '_Completo.pdf');

} catch (Exception $e) {
    die('Error al generar el documento unificado: ' . $e->getMessage());
}

/**
 * Función auxiliar para importar todas las páginas de un PDF a otro
 */
function importPdfPages($pdf, $filePath) {
    try {
        $pageCount = $pdf->setSourceFile($filePath);
        for ($n = 1; $n <= $pageCount; $n++) {
            $tplIdx = $pdf->importPage($n);
            $size = $pdf->getTemplateSize($tplIdx);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);
        }
    } catch (Exception $e) {
        // Si falla una importación por versión de PDF incompatible (ej: PDF 1.5+), intentar normalizarlo a PDF 1.4 usando Ghostscript
        try {
            $tempOutput = tempnam(sys_get_temp_dir(), 'pdf14_');
            $cmd = "env -u LD_LIBRARY_PATH gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($tempOutput) . " " . escapeshellarg($filePath);
            exec($cmd, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($tempOutput) && filesize($tempOutput) > 0) {
                $pageCount = $pdf->setSourceFile($tempOutput);
                for ($n = 1; $n <= $pageCount; $n++) {
                    $tplIdx = $pdf->importPage($n);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }
                unlink($tempOutput);
                return;
            }
            if (file_exists($tempOutput)) {
                unlink($tempOutput);
            }
        } catch (Exception $ex) {
            // Ignorar y caer al fallback visual
        }

        // Si falla todo, añadir una página de error
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Error al importar archivo: ' . basename($filePath), 0, 1);
    }
}
