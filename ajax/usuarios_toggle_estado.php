<?php
require_once '../includes/config.php';

try {
    // Verificar autenticación y permisos
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }
    
    if (!tienePermiso('usuarios', 'editar')) {
        json_error('Sin permisos para modificar usuarios', 403);
    }
    
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    
    if ($id_usuario === 0) {
        json_error('ID de usuario inválido', 400);
    }
    
    // No permitir desactivar el propio usuario
    if ($id_usuario == obtenerUsuarioId()) {
        json_error('No puedes desactivar tu propio usuario', 400);
    }
    
    $db = conectarDB();
    $db->beginTransaction();
    
    // Obtener estado actual
    $stmt = $db->prepare("SELECT username, activo FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $db->rollBack();
        json_error('Usuario no encontrado', 404);
    }
    
    // Invertir estado
    $nuevoEstado = $usuario['activo'] ? 0 : 1;
    $accion = $nuevoEstado ? 'activar' : 'desactivar';
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE usuarios SET activo = ?, modificado_por = ? WHERE id_usuario = ?");
    $stmt->execute([$nuevoEstado, obtenerUsuarioId(), $id_usuario]);
    
    // Si se desactiva, cerrar sesiones activas
    if (!$nuevoEstado) {
        $stmt = $db->prepare("UPDATE sesiones SET activa = 0, fecha_cierre = NOW() 
                              WHERE id_usuario = ? AND activa = 1");
        $stmt->execute([$id_usuario]);
    }
    
    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        "{$accion}_usuario",
        'usuarios',
        "Usuario {$accion}: {$usuario['username']}",
        'usuario',
        $id_usuario,
        ['activo' => $usuario['activo']],
        ['activo' => $nuevoEstado]
    );
    
    Logger::info("Estado de usuario cambiado exitosamente", [
        'usuario' => $usuario['username'],
        'accion' => $accion,
        'nuevo_estado' => $nuevoEstado
    ]);
    
    // IMPORTANTE: Usar código 200 para que jQuery lo trate como success
    json_success([
        'mensaje' => "Usuario {$accion} correctamente",
        'nuevo_estado' => $nuevoEstado
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Logger::error('Error al cambiar estado de usuario', [
        'mensaje' => $e->getMessage(),
        'id_usuario' => $id_usuario ?? 0
    ]);
    
    json_error('Error al cambiar estado: ' . $e->getMessage(), 500);
}
