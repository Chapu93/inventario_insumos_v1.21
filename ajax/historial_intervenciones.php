<?php
/**
 * Endpoint AJAX para obtener historial de intervenciones de un insumo
 * Retorna todos los pedidos (mantenimiento, reparación, soporte) relacionados con un insumo
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$id_insumo = (int)($_GET['id_insumo'] ?? 0);

if ($id_insumo <= 0) {
    json_error('ID de insumo no válido', 400);
}

try {
    $db = conectarDB();
    
    $sql = "SELECT 
                p.id_pedido,
                p.tipo,
                p.descripcion,
                p.estado,
                p.prioridad,
                p.solicitante_nombre,
                p.fecha_creacion,
                p.fecha_actualizacion,
                u.nombre as tecnico_nombre,
                u.apellido as tecnico_apellido,
                s.nombre_sede
            FROM pedidos p
            LEFT JOIN usuarios u ON p.asignado_a = u.id_usuario
            LEFT JOIN sedes s ON p.id_sede = s.id_sede
            WHERE p.id_insumo_relacionado = ?
            ORDER BY p.fecha_creacion DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_insumo]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para la respuesta
    $resultado = array_map(function($p) {
        return [
            'id' => $p['id_pedido'],
            'tipo' => $p['tipo'],
            'descripcion' => $p['descripcion'],
            'estado' => $p['estado'],
            'prioridad' => $p['prioridad'],
            'solicitante' => $p['solicitante_nombre'],
            'fecha' => date('d/m/Y H:i', strtotime($p['fecha_creacion'])),
            'fecha_actualizacion' => $p['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($p['fecha_actualizacion'])) : null,
            'tecnico' => $p['tecnico_nombre'] ? ($p['tecnico_nombre'] . ' ' . $p['tecnico_apellido']) : null,
            'sede' => $p['nombre_sede']
        ];
    }, $pedidos);
    
    // Estadísticas del insumo
    $stats = [
        'total' => count($pedidos),
        'mantenimientos' => count(array_filter($pedidos, fn($p) => $p['tipo'] === 'Mantenimiento')),
        'reparaciones' => count(array_filter($pedidos, fn($p) => $p['tipo'] === 'Reparación')),
        'soportes' => count(array_filter($pedidos, fn($p) => $p['tipo'] === 'Soporte')),
        'completados' => count(array_filter($pedidos, fn($p) => $p['estado'] === 'Completado')),
        'pendientes' => count(array_filter($pedidos, fn($p) => $p['estado'] === 'Pendiente' || $p['estado'] === 'En Proceso'))
    ];
    
    json_success([
        'id_insumo' => $id_insumo,
        'stats' => $stats,
        'data' => $resultado
    ]);
    
} catch (Exception $e) {
    json_error('Error al obtener historial: ' . $e->getMessage(), 500);
}
