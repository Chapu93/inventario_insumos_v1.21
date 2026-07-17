<?php
require_once '../includes/config.php';

try {
    // 1. Verificar autenticación y permisos
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }
    
    if (!tienePermiso('telecom', 'editar')) {
        json_error('Sin permisos para modificar telecomunicaciones', 403);
    }
    
    // 2. Verificar CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
        json_error('Petición inválida o token CSRF expirado', 403);
    }
    
    // 3. Obtener y validar parámetros
    $id_test = (int)($_POST['id_test'] ?? 0);
    
    if ($id_test <= 0) {
        json_error('ID de test inválido', 400);
    }
    
    $db = conectarDB();
    
    // 4. Obtener datos antes de eliminar (para borrar archivo físico y auditoría)
    $stmtSvc = $db->prepare("SELECT * FROM sedes_internet_tests WHERE id_test = ?");
    $stmtSvc->execute([$id_test]);
    $test = $stmtSvc->fetch();
    
    if (!$test) {
        json_error('Test de velocidad no encontrado', 404);
    }
    
    $db->beginTransaction();
    
    // 5. Eliminar de la Base de Datos
    $stmtDel = $db->prepare("DELETE FROM sedes_internet_tests WHERE id_test = ?");
    $stmtDel->execute([$id_test]);
    
    // 6. Eliminar archivo físico
    $uploadDir = UPLOAD_BASE_DIR . 'telecom/tests/';
    $rutaArchivo = $uploadDir . $test['captura_pantalla'];
    if (!empty($test['captura_pantalla']) && file_exists($rutaArchivo)) {
        @unlink($rutaArchivo);
    }
    
    // 7. Registrar en auditoría
    registrarAuditoria(
        'eliminar_internet_test',
        'sedes_internet',
        "Test de velocidad eliminado para servicio #{$test['id_internet']}. Bajada: {$test['velocidad_bajada']} Mbps, Subida: {$test['velocidad_subida']} Mbps",
        'sedes_internet',
        $test['id_internet'],
        $test,
        null
    );
    
    $db->commit();
    
    json_success([
        'mensaje' => 'Test de velocidad eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Logger::error('Error al eliminar test de velocidad', [
        'mensaje' => $e->getMessage(),
        'id_test' => $id_test ?? 0
    ]);
    
    json_error('Error al procesar la solicitud: ' . $e->getMessage(), 500);
}
