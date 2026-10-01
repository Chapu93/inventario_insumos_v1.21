<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'editar') && !tienePermiso('asignaciones', 'anular')) {
    json_error('No tienes permisos para eliminar comprobantes de devolución', 403);
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
    
    $idDev = isset($_POST['id_remito_devolucion']) ? (int)$_POST['id_remito_devolucion'] : 0;
    if (!$idDev) {
        json_error('ID de remito de devolución inválido', 400);
    }

    // Obtener info del archivo
    $stmt = $db->prepare("SELECT numero_devolucion, remito_firmado FROM remitos_devolucion WHERE id_remito_devolucion = ?");
    $stmt->execute([$idDev]);
    $dev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dev) {
        json_error('Remito de devolución no encontrado', 404);
    }

    if (empty($dev['remito_firmado'])) {
        json_error('El remito de devolución no tiene un documento adjunto', 400);
    }

    // Eliminar archivo físico
    $uploadDir = UPLOAD_BASE_DIR . 'remitos_firmados/';
    $archivoRuta = $uploadDir . $dev['remito_firmado'];
    
    if (file_exists($archivoRuta)) {
        @unlink($archivoRuta);
    }

    // Actualizar Base de Datos
    $stmtUpdate = $db->prepare("UPDATE remitos_devolucion SET remito_firmado = NULL WHERE id_remito_devolucion = ?");
    $stmtUpdate->execute([$idDev]);

    // Registrar en Auditoría
    registrarAuditoria(
        'eliminar_remito_dev_firmado',
        'remitos_devolucion',
        "Se eliminó el comprobante firmado de la devolución #{$dev['numero_devolucion']}",
        'remito_devolucion',
        $idDev
    );

    json_success(['mensaje' => 'Comprobante eliminado correctamente']);

} catch (Exception $e) {
    Logger::error("Error al eliminar comprobante de devolución firmado", ['error' => $e->getMessage(), 'id_dev' => $_POST['id_remito_devolucion'] ?? '0']);
    json_error('Error interno del servidor', 500);
}
