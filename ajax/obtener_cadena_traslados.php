<?php
require_once __DIR__ . '/../includes/config.php';


if (!isset($_GET['id_internet'])) {
    echo json_encode([]);
    exit;
}

$id_internet = (int)$_GET['id_internet'];
$conexion = conectarDB();

try {
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
    
    $stmt->execute([$id_internet]);
    $cadena = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cadena);
    
} catch (Exception $e) {
    Logger::error('Error al obtener cadena de traslados', [
        'mensaje' => $e->getMessage(),
        'id_insumo' => $id_insumo ?? 0
    ]);
    echo json_encode([]);
}
