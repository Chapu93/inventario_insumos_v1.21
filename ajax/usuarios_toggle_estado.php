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
    
    if (!tienePermiso('usuarios', 'editar')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para modificar usuarios']);
        exit;
    }
    
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    
    if ($id_usuario === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'ID de usuario inválido']);
        exit;
    }
    
    // No permitir desactivar el propio usuario
    if ($id_usuario == obtenerUsuarioId()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'No puedes desactivar tu propio usuario']);
        exit;
    }
    
    $db = conectarDB();
    $db->beginTransaction();
    
    // Obtener estado actual
    $stmt = $db->prepare("SELECT username, activo FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
        exit;
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
    
    echo json_encode([
        'success' => true,
        'mensaje' => "Usuario {$accion} correctamente",
        'nuevo_estado' => $nuevoEstado
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('Error en usuarios_toggle_estado.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()
    ]);
}
