<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    die('No autenticado');
}

if (!tienePermiso('insumos', 'ver')) {
    die('No tienes permisos');
}

try {
    $id_documento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_documento) {
        http_response_code(400);
        die('ID de documento inválido');
    }
    
    $db = conectarDB();
    
    // Obtener documento
    $stmt = $db->prepare('SELECT * FROM ingresos_documentos WHERE id_documento = ?');
    $stmt->execute([$id_documento]);
    $documento = $stmt->fetch();
    
    if (!$documento) {
        http_response_code(404);
        die('Documento no encontrado');
    }
    
    $rutaArchivo = UPLOAD_BASE_DIR . 'ingresos/' . $documento['ruta_archivo'];
    
    if (!file_exists($rutaArchivo)) {
        http_response_code(404);
        die('Archivo no encontrado en el sistema');
    }
    
    // Determinar tipo MIME
    $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip'
    ];
    
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    // Enviar archivo: inline para visor (si viene ver=1 o inline=1) o attachment para forzar descarga
    $disposition = ((isset($_GET['ver']) && $_GET['ver'] == '1') || (isset($_GET['inline']) && $_GET['inline'] == '1')) ? 'inline' : 'attachment';
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($documento['nombre_archivo']) . '"');
    header('Content-Length: ' . filesize($rutaArchivo));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($rutaArchivo);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error al descargar documento: ' . $e->getMessage());
}
?>
