<?php
require_once '../includes/config.php';

try {
    // Verificar autenticación y permisos
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }
    
    if (!tienePermiso('usuarios', 'cambiar_rol')) {
        json_error('Sin permisos para cambiar roles', 403);
    }
    
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $id_rol = (int)($_POST['id_rol'] ?? 0);
    
    if ($id_usuario === 0 || $id_rol === 0) {
        json_error('Datos inválidos', 400);
    }
    
    // No permitir cambiar el propio rol
    if ($id_usuario == obtenerUsuarioId()) {
        json_error('No puedes cambiar tu propio rol', 400);
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
        json_error('Usuario no encontrado', 404);
    }
    
    // Obtener nombre del nuevo rol
    $stmt = $db->prepare("SELECT nombre_rol FROM roles WHERE id_rol = ?");
    $stmt->execute([$id_rol]);
    $nuevoRol = $stmt->fetch();
    
    if (!$nuevoRol) {
        $db->rollBack();
        json_error('Rol no encontrado', 404);
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
    
    Logger::info("Rol de usuario cambiado exitosamente", [
        'usuario' => $usuario['username'],
        'rol_anterior' => $usuario['rol_actual'],
        'rol_nuevo' => $nuevoRol['nombre_rol']
    ]);
    
    // IMPORTANTE: Usar código 200 para que jQuery lo trate como success
    json_success([
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
