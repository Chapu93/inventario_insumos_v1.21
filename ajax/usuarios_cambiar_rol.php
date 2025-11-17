<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación y permisos
    if (!estaAutenticado()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'mensaje' => 'No autenticado']);
        exit;
    }
    
    if (!tienePermiso('usuarios', 'cambiar_rol')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para cambiar roles']);
        exit;
    }
    
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $id_rol = (int)($_POST['id_rol'] ?? 0);
    
    if ($id_usuario === 0 || $id_rol === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
        exit;
    }
    
    // No permitir cambiar el propio rol
    if ($id_usuario == obtenerUsuarioId()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'No puedes cambiar tu propio rol']);
        exit;
    }
    
    $db = conectarDB();
    $db->beginTransaction();
    
    // Obtener datos actuales
    $stmt = $db->prepare("SELECT u.*, r.nombre_rol as rol_actual FROM usuarios u 
                          LEFT JOIN roles r ON u.id_rol = r.id_rol 
                          WHERE u.id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
        exit;
    }
    
    // Obtener nombre del nuevo rol
    $stmt = $db->prepare("SELECT nombre_rol FROM roles WHERE id_rol = ?");
    $stmt->execute([$id_rol]);
    $nuevoRol = $stmt->fetch();
    
    if (!$nuevoRol) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'mensaje' => 'Rol no encontrado']);
        exit;
    }
    
    // Actualizar rol
    $stmt = $db->prepare("UPDATE usuarios SET id_rol = ?, modificado_por = ? WHERE id_usuario = ?");
    $stmt->execute([$id_rol, obtenerUsuarioId(), $id_usuario]);
    
    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        'cambiar_rol_usuario',
        'usuarios',
        "Rol cambiado para {$usuario['username']}: {$usuario['rol_actual']} → {$nuevoRol['nombre_rol']}",
        'usuario',
        $id_usuario,
        ['id_rol' => $usuario['id_rol'], 'rol' => $usuario['rol_actual']],
        ['id_rol' => $id_rol, 'rol' => $nuevoRol['nombre_rol']]
    );
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Rol actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Logger::error('Error al cambiar rol de usuario', [
        'mensaje' => $e->getMessage(),
        'id_usuario' => $id_usuario ?? 0,
        'id_rol' => $id_rol ?? 0
    ]);
    
    json_error('Error al cambiar rol: ' . $e->getMessage(), 500);
}
