<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'eliminar')) {
    json_error('No tienes permisos para eliminar insumos', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verify_csrf($input['_csrf'] ?? '')) {
        json_error('CSRF inválido', 403);
    }
    
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        json_error('ID no válido', 400);
    }
    
    $db = conectarDB();
    $db->beginTransaction();
    
    // Obtener info del ingreso antes de eliminar para auditoría
    $stmt = $db->prepare('SELECT tipo_ingreso, nro_referencia FROM ingresos WHERE id_ingreso = ?');
    $stmt->execute([$id]);
    $ingreso = $stmt->fetch();
    
    if (!$ingreso) {
        $db->rollBack();
        json_error('Ingreso no encontrado', 404);
    }
    
    // Desvincular insumos asociados (SET NULL)
    $stmt = $db->prepare('UPDATE insumos SET id_ingreso = NULL WHERE id_ingreso = ?');
    $stmt->execute([$id]);
    
    // Eliminar ingreso
    $stmt = $db->prepare('DELETE FROM ingresos WHERE id_ingreso = ?');
    $stmt->execute([$id]);
    
    $db->commit();
    
    Logger::info('Ingreso eliminado', [
        'id' => $id,
        'tipo' => $ingreso['tipo_ingreso'],
        'referencia' => $ingreso['nro_referencia']
    ]);
    
    json_success(['message' => 'Ingreso eliminado correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error('Error al eliminar ingreso', [
        'mensaje' => $e->getMessage(),
        'id' => $id ?? 0
    ]);
    json_error('Error al eliminar: ' . $e->getMessage(), 500);
}
?>
