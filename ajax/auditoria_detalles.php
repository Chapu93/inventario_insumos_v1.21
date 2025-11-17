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
    
    if (!tienePermiso('auditoria', 'ver_todo')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
        exit;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
        exit;
    }
    
    $db = conectarDB();
    
    $sql = "SELECT 
                a.*,
                u.username,
                CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                u.email
            FROM auditoria_acciones a
            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE a.id_auditoria = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $auditoria = $stmt->fetch();
    
    if (!$auditoria) {
        http_response_code(404);
        echo json_encode(['success' => false, 'mensaje' => 'Registro no encontrado']);
        exit;
    }
    
    // Parsear JSON de datos_antes y datos_despues
    $datos_antes = null;
    $datos_despues = null;
    
    if (!empty($auditoria['datos_antes'])) {
        $datos_antes = json_decode($auditoria['datos_antes'], true);
    }
    
    if (!empty($auditoria['datos_despues'])) {
        $datos_despues = json_decode($auditoria['datos_despues'], true);
    }
    
    $response = [
        'success' => true,
        'data' => [
            'id_auditoria' => $auditoria['id_auditoria'],
            'fecha_accion' => date('d/m/Y H:i:s', strtotime($auditoria['fecha_accion'])),
            'usuario' => $auditoria['username'] ?? 'Sistema',
            'nombre_completo' => $auditoria['nombre_completo'] ?? '',
            'email' => $auditoria['email'] ?? '',
            'modulo' => $auditoria['modulo'],
            'accion' => $auditoria['accion'],
            'descripcion' => $auditoria['descripcion'],
            'entidad_tipo' => $auditoria['entidad_tipo'],
            'entidad_id' => $auditoria['entidad_id'],
            'resultado' => $auditoria['resultado'],
            'mensaje_error' => $auditoria['mensaje_error'],
            'ip_address' => $auditoria['ip_address'],
            'datos_antes' => $datos_antes,
            'datos_despues' => $datos_despues
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    Logger::error('Error al obtener detalles de auditoría', [
        'mensaje' => $e->getMessage(),
        'id_auditoria' => $id ?? 0
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener detalles: ' . $e->getMessage()
    ]);
}
