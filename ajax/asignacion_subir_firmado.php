<?php
require_once '../includes/config.php';
require_once '../includes/validar_archivo.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'crear')) {
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
    
    $idRemito = isset($_POST['id_remito']) ? (int)$_POST['id_remito'] : 0;
    if (!$idRemito) {
        json_error('ID de remito inválido', 400);
    }

    // Verificar que el remito existe
    $stmt = $db->prepare("SELECT numero_remito, remito_firmado FROM remitos WHERE id_remito = ?");
    $stmt->execute([$idRemito]);
    $remito = $stmt->fetch();

    if (!$remito) {
        json_error('Remito no encontrado', 404);
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
    $nombreLimpio = str_replace('/', '_', $remito['numero_remito']);
    $nombreArchivo = 'remito_firmado_' . $nombreLimpio . '_' . time() . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        json_error('Error al mover el archivo al servidor', 500);
    }

    // Eliminar archivo anterior si existía
    if (!empty($remito['remito_firmado'])) {
        $archivoAnterior = $uploadDir . $remito['remito_firmado'];
        if (file_exists($archivoAnterior)) {
            unlink($archivoAnterior);
        }
    }

    // Actualizar Base de Datos
    $stmtUpdate = $db->prepare("UPDATE remitos SET remito_firmado = ? WHERE id_remito = ?");
    $stmtUpdate->execute([$nombreArchivo, $idRemito]);

    // Registrar en Auditoría
    registrarAuditoria('subir_remito_firmado', 'remitos', "Se adjuntó remito firmado para el remito #{$remito['numero_remito']}", 'remito', $idRemito);

    json_success(['mensaje' => 'Remito firmado subido correctamente', 'archivo' => $nombreArchivo]);

} catch (Exception $e) {
    Logger::error("Error al subir remito firmado", ['error' => $e->getMessage(), 'id_remito' => $_POST['id_remito'] ?? '0']);
    json_error('Error interno del servidor: ' . $e->getMessage(), 500);
}
