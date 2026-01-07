<?php
/**
 * Eliminar documento de ingreso
 * Solo para Administradores y Superadministradores
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// Solo Admin (1) y Superadmin (2) pueden eliminar documentos
if (!tieneRol([1, 2])) {
    json_error('No tienes permisos para eliminar documentos. Solo administradores.', 403);
}

// Verificar CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if (!verify_csrf($data['_csrf'] ?? '')) {
    json_error('Token CSRF inválido', 403);
}

$id_documento = isset($data['id']) ? (int)$data['id'] : 0;

if (!$id_documento) {
    json_error('ID de documento inválido', 400);
}

try {
    $db = conectarDB();
    
    // Obtener información del documento antes de eliminar
    $stmt = $db->prepare('SELECT id_documento, id_ingreso, nombre_archivo, ruta_archivo FROM ingresos_documentos WHERE id_documento = ?');
    $stmt->execute([$id_documento]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$documento) {
        json_error('Documento no encontrado', 404);
    }
    
    $db->beginTransaction();
    
    // Eliminar registro de la base de datos
    $stmtDelete = $db->prepare('DELETE FROM ingresos_documentos WHERE id_documento = ?');
    $stmtDelete->execute([$id_documento]);
    
    // Intentar eliminar el archivo físico
    $rutaArchivo = __DIR__ . '/../uploads/ingresos/' . $documento['ruta_archivo'];
    if (file_exists($rutaArchivo)) {
        unlink($rutaArchivo);
    }
    
    // Registrar en auditoría
    $usuario = obtenerUsuario();
    Logger::info("Documento de ingreso eliminado", [
        'id_documento' => $id_documento,
        'id_ingreso' => $documento['id_ingreso'],
        'nombre_archivo' => $documento['nombre_archivo'],
        'eliminado_por' => $usuario['username']
    ]);
    
    $db->commit();
    
    json_success(['mensaje' => 'Documento eliminado correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error("Error al eliminar documento de ingreso", [
        'mensaje' => $e->getMessage(),
        'id_documento' => $id_documento
    ]);
    json_error('Error al eliminar documento: ' . $e->getMessage(), 500);
}
?>
