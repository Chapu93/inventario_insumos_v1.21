<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'anular')) { // Usamos permiso de anular para poder eliminar adjuntos
    json_error('No tienes permisos para eliminar adjuntos', 403);
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

    // Obtener info del archivo
    $stmt = $db->prepare("SELECT numero_remito, remito_firmado FROM remitos WHERE id_remito = ?");
    $stmt->execute([$idRemito]);
    $remito = $stmt->fetch();

    if (!$remito) {
        json_error('Remito no encontrado', 404);
    }

    if (empty($remito['remito_firmado'])) {
        json_error('El remito no tiene un documento adjunto', 400);
    }

    // Eliminar archivo físico
    $uploadDir = UPLOAD_BASE_DIR . 'remitos_firmados/';
    $archivoRuta = $uploadDir . $remito['remito_firmado'];
    
    if (file_exists($archivoRuta)) {
        unlink($archivoRuta);
    }

    // Actualizar Base de Datos
    $stmtUpdate = $db->prepare("UPDATE remitos SET remito_firmado = NULL WHERE id_remito = ?");
    $stmtUpdate->execute([$idRemito]);

    // Registrar en Auditoría
    registrarAuditoria('eliminar_remito_firmado', 'remitos', "Se eliminó el remito firmado del remito #{$remito['numero_remito']}", 'remito', $idRemito);

    json_success(['mensaje' => 'Documento eliminado correctamente']);

} catch (Exception $e) {
    Logger::error("Error al eliminar remito firmado", ['error' => $e->getMessage(), 'id_remito' => $_POST['id_remito'] ?? '0']);
    json_error('Error interno del servidor', 500);
}
