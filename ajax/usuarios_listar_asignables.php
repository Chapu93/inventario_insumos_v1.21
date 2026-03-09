<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!estaAutenticado()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $db = conectarDB();
    
    // Roles permitidos para ser asignados: 
    // 1: Superadministrador
    // 2: Administrador
    // 3: Operador
    $sql = "SELECT id_usuario, username, nombre, apellido, id_rol 
            FROM usuarios 
            WHERE id_rol IN (1, 2, 3, 4) AND activo = 1 
            ORDER BY apellido ASC, nombre ASC";
            
    $stmt = $db->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    
} catch (Exception $e) {
    Logger::error('Error en usuarios_listar_asignables', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'error' => 'Error al obtener usuarios']);
}
