<?php
/**
 * Búsqueda Global del Sistema - v2.0
 * 
 * Busca simultáneamente en insumos, pedidos, remitos y sedes
 * Respeta los permisos del usuario
 * 
 * Rollback: Eliminar este archivo
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    json_success(['resultados' => [], 'mensaje' => 'Ingrese al menos 2 caracteres', 'total' => 0]);
}

try {
    $db = conectarDB();
    $like = '%' . $query . '%';
    $resultados = [];
    
    // Buscar en Insumos
    if (tienePermiso('insumos', 'ver')) {
        $stmt = $db->prepare("
            SELECT id_insumo as id, nombre_insumo as titulo, tipo_insumo as subtitulo, 'insumo' as tipo
            FROM insumos 
            WHERE nombre_insumo LIKE ? OR numero_serie LIKE ? OR id_fisico LIKE ? OR id_patrimonio LIKE ? OR subcategoria_varios LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        $resultados['insumos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Pedidos
    if (tienePermiso('pedidos', 'ver_propios') || tienePermiso('pedidos', 'ver_todos')) {
        $sql = "
            SELECT p.id_pedido as id, 
                   SUBSTRING(p.descripcion, 1, 60) as titulo, 
                   CONCAT(p.tipo, ' - ', p.estado, ' (', s.nombre_sede, ')') as subtitulo, 
                   'pedido' as tipo
            FROM pedidos p
            JOIN sedes s ON p.id_sede = s.id_sede
            WHERE (p.descripcion LIKE ? OR p.solicitante_nombre LIKE ? OR p.solicitante_apellido LIKE ? OR s.nombre_sede LIKE ? OR CAST(p.id_pedido AS CHAR) LIKE ?)
        ";
        $params = [$like, $like, $like, $like, $like];
        
        // Si no puede ver todos, solo mostrar propios
        if (!tienePermiso('pedidos', 'ver_todos')) {
            $uId = obtenerUsuarioId();
            $sql .= " AND (p.id_usuario_solicitante = ? OR p.asignado_a = ?)";
            $params[] = $uId;
            $params[] = $uId;
        }
        
        $sql .= " ORDER BY p.fecha_creacion DESC LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $resultados['pedidos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Remitos
    if (tienePermiso('asignaciones', 'ver')) {
        $stmt = $db->prepare("
            SELECT r.id_remito as id, r.numero_remito as titulo, 
                   CONCAT(r.nombre_persona_asignada, ' ', r.apellido_persona_asignada, ' (', s.nombre_sede, ' - ', l.nombre_localidad, ')') as subtitulo, 
                   'remito' as tipo
            FROM remitos r
            JOIN sedes s ON r.id_sede = s.id_sede
            JOIN localidades l ON s.id_localidad = l.id_localidad
            WHERE r.numero_remito LIKE ? 
               OR r.nombre_persona_asignada LIKE ? 
               OR r.apellido_persona_asignada LIKE ?
               OR s.nombre_sede LIKE ?
               OR l.nombre_localidad LIKE ?
            ORDER BY r.fecha_asignacion DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        $resultados['remitos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Sedes (todos pueden ver sedes)
    $stmt = $db->prepare("
        SELECT s.id_sede as id, s.nombre_sede as titulo, CONCAT(s.direccion, ', ', l.nombre_localidad) as subtitulo, 'sede' as tipo
        FROM sedes s
        JOIN localidades l ON s.id_localidad = l.id_localidad
        WHERE s.nombre_sede LIKE ? OR s.direccion LIKE ? OR l.nombre_localidad LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);
    $resultados['sedes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalResultados = array_sum(array_map('count', $resultados));
    
    json_success([
        'resultados' => $resultados,
        'total' => $totalResultados,
        'query' => $query
    ]);
    
} catch (Exception $e) {
    Logger::error('Error en búsqueda global', ['error' => $e->getMessage(), 'query' => $query]);
    json_error('Error al buscar', 500);
}
