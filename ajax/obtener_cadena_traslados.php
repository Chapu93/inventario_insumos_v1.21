<?php
require_once __DIR__ . '/../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('telecomunicaciones', 'ver')) {
    json_error('No tienes permisos para ver telecomunicaciones', 403);
}

$idInternet = isset($_GET['id_internet']) ? (int)$_GET['id_internet'] : 0;

if ($idInternet <= 0) {
    json_success([]); // Return empty array for invalid ID as per original behavior, or standardized error? Original returned empty array.
}

try {
    $conexion = conectarDB();
    
    // Obtener toda la cadena de traslados (desde el original hasta el actual)
    $stmt = $conexion->prepare("
        WITH RECURSIVE cadena_traslados AS (
            -- Servicio actual
            SELECT 
                id_internet,
                proveedor,
                tipo_conexion,
                velocidad_mbps,
                fecha_traslado,
                id_servicio_trasladado_desde,
                id_servicio_trasladado_a,
                0 as nivel
            FROM sedes_internet
            WHERE id_internet = ?
            
            UNION ALL
            
            -- Servicios anteriores (ir hacia atrás)
            SELECT 
                i.id_internet,
                i.proveedor,
                i.tipo_conexion,
                i.velocidad_mbps,
                i.fecha_traslado,
                i.id_servicio_trasladado_desde,
                i.id_servicio_trasladado_a,
                c.nivel - 1
            FROM sedes_internet i
            INNER JOIN cadena_traslados c ON i.id_internet = c.id_servicio_trasladado_desde
        )
        SELECT * FROM cadena_traslados
        ORDER BY nivel ASC
    ");
    
    $stmt->execute([$idInternet]);
    $cadena = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Logger::debug('Cadena de traslados obtenida', ['id_internet' => $idInternet, 'count' => count($cadena)]);
    
    json_success($cadena);
    
} catch (Exception $e) {
    Logger::error('Error al obtener cadena de traslados', [
        'mensaje' => $e->getMessage(),
        'id_internet' => $idInternet
    ]);
    json_error('Error al obtener cadena de traslados', 500);
}
?>
