<?php
require_once '../includes/config.php';
require_once '../includes/validar_archivo.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'devolver') && !tienePermiso('asignaciones', 'crear') && !tienePermiso('asignaciones', 'editar')) {
    json_error('No tienes permisos para esta acción', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

// Verificar CSRF
if (!verify_csrf($_POST['_csrf'] ?? '')) {
    json_error('Token CSRF inválido', 403);
}

try {
    $db = conectarDB();
    
    $idRemitoDevolucion = isset($_POST['id_remito_devolucion']) ? (int)$_POST['id_remito_devolucion'] : 0;
    if (!$idRemitoDevolucion) {
        // Aceptar también por id_remito si viene como remito de devolución
        $idRemitoDevolucion = isset($_POST['id_remito']) ? (int)$_POST['id_remito'] : 0;
    }

    if (!$idRemitoDevolucion) {
        json_error('ID de remito de devolución inválido', 400);
    }

    // Verificar que el remito de devolución existe
    $stmt = $db->prepare("SELECT id_remito_devolucion, numero_devolucion, remito_firmado FROM remitos_devolucion WHERE id_remito_devolucion = ?");
    $stmt->execute([$idRemitoDevolucion]);
    $remitoDev = $stmt->fetch();

    if (!$remitoDev) {
        json_error('Remito de devolución no encontrado', 404);
    }

    // Procesar Archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
        json_error('No se seleccionó ningún archivo', 400);
    }

    $validacion = validarArchivoDocumento($_FILES['archivo']);
    if (!$validacion['valido']) {
        json_error($validacion['error'], 400);
    }

    $uploadDir = UPLOAD_BASE_DIR . 'remitos_firmados/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Nombre de archivo descriptivo
    $extension = $validacion['extension'];
    $nombreLimpio = str_replace('/', '_', $remitoDev['numero_devolucion']);
    $nombreArchivo = 'remito_dev_firmado_' . $nombreLimpio . '_' . time() . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        json_error('Error al mover el archivo al servidor', 500);
    }

    // Eliminar archivo anterior si existía
    if (!empty($remitoDev['remito_firmado'])) {
        $archivoAnterior = $uploadDir . $remitoDev['remito_firmado'];
        if (file_exists($archivoAnterior)) {
            unlink($archivoAnterior);
        }
    }

    // Actualizar Base de Datos
    $stmtUpdate = $db->prepare("UPDATE remitos_devolucion SET remito_firmado = ? WHERE id_remito_devolucion = ?");
    $stmtUpdate->execute([$nombreArchivo, $idRemitoDevolucion]);

    // Registrar en Auditoría
    registrarAuditoria('subir_remito_devolucion_firmado', 'remitos', "Se adjuntó remito firmado para la devolución #{$remitoDev['numero_devolucion']}", 'remito_devolucion', $idRemitoDevolucion);

    json_success([
        'mensaje' => 'Remito de devolución firmado subido correctamente', 
        'archivo' => $nombreArchivo,
        'url' => app_base_url() . '/uploads/remitos_firmados/' . $nombreArchivo
    ]);

} catch (Exception $e) {
    Logger::error("Error al subir remito de devolución firmado", ['error' => $e->getMessage(), 'id_remito_devolucion' => $_POST['id_remito_devolucion'] ?? '0']);
    json_error('Error interno del servidor: ' . $e->getMessage(), 500);
}
