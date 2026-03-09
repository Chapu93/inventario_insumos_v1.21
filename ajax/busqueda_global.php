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
            WHERE nombre_insumo LIKE ? OR numero_serie LIKE ? OR id_fisico LIKE ? OR id_patrimonio LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like]);
        $resultados['insumos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Pedidos
    if (tienePermiso('pedidos', 'ver_propios') || tienePermiso('pedidos', 'ver_todos')) {
        $sql = "
            SELECT id_pedido as id, 
                   SUBSTRING(descripcion, 1, 60) as titulo, 
                   CONCAT(tipo, ' - ', estado) as subtitulo, 
                   'pedido' as tipo
            FROM pedidos 
            WHERE descripcion LIKE ? OR solicitante_nombre LIKE ? OR CAST(id_pedido AS CHAR) LIKE ?
        ";
        $params = [$like, $like, $like];
        
        // Si no puede ver todos, solo mostrar propios
        if (!tienePermiso('pedidos', 'ver_todos')) {
            $uId = obtenerUsuarioId();
            $sql .= " AND (id_usuario_solicitante = ? OR asignado_a = ?)";
            $params[] = $uId;
            $params[] = $uId;
        }
        
        $sql .= " ORDER BY fecha_creacion DESC LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $resultados['pedidos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Remitos
    if (tienePermiso('asignaciones', 'ver')) {
        $stmt = $db->prepare("
            SELECT id_remito as id, numero_remito as titulo, 
                   CONCAT(nombre_persona_asignada, ' ', apellido_persona_asignada) as subtitulo, 
                   'remito' as tipo
            FROM remitos 
            WHERE numero_remito LIKE ? 
               OR nombre_persona_asignada LIKE ? 
               OR apellido_persona_asignada LIKE ?
            ORDER BY fecha_asignacion DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like]);
        $resultados['remitos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Buscar en Sedes (todos pueden ver sedes)
    $stmt = $db->prepare("
        SELECT id_sede as id, nombre_sede as titulo, direccion as subtitulo, 'sede' as tipo
        FROM sedes 
        WHERE nombre_sede LIKE ? OR direccion LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$like, $like]);
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
