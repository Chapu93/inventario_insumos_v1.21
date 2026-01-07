<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// Permitir a quien pueda interactuar con areas, para pedidos 'crear' es suficiente si esta autenticado?
// Mejor verificar al menos login.
// Si se usa en dropdown público de creación de pedidos (interno), con 'estaAutenticado' basta.

try {
    $db = conectarDB();
    $sql = "SELECT id_area as id, nombre_area as nombre FROM areas ORDER BY nombre_area";
    $stmt = $db->query($sql);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver array directo en data, estilo json_success($data)
    json_success($areas);
    
} catch (Exception $e) {
    json_error('Error al cargar áreas: ' . $e->getMessage(), 500);
}
