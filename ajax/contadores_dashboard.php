<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$conexion = conectarDB();

try {
    // Contadores principales
    $total_insumos = $conexion->query("SELECT COUNT(*) as total FROM insumos")->fetch()['total'];
    $insumos_disponibles = $conexion->query("SELECT COUNT(*) as disponibles FROM insumos WHERE estado = 'Disponible'")->fetch()['disponibles'];
    $insumos_asignados = $conexion->query("SELECT COUNT(*) as asignados FROM insumos WHERE estado = 'Asignado'")->fetch()['asignados'];

    // Total de asignaciones (remitos) - Excluye remitos anulados
    $total_asignaciones = $conexion->query("SELECT COUNT(*) as total_asignaciones FROM remitos WHERE estado != 'Anulado'")->fetch()['total_asignaciones'];

    // Asignaciones activas (insumos en estado 'Asignado' sumando cantidades en detalle) - Excluye remitos anulados
    $stmt = $conexion->query("SELECT COALESCE(SUM(d.cantidad),0) as activas
                              FROM remitos r
                              JOIN remitos_detalle d ON d.id_remito = r.id_remito
                              JOIN insumos i ON i.id_insumo = d.id_insumo
                              WHERE i.estado = 'Asignado' AND r.estado != 'Anulado'");
    $asignaciones_activas = $stmt->fetch()['activas'];

    // Insumos por tipo
    $insumos_por_tipo = $conexion->query("SELECT tipo_insumo, COUNT(*) as cantidad FROM insumos GROUP BY tipo_insumo ORDER BY cantidad DESC")->fetchAll();

    // Insumos por sede
    $insumos_por_sede = $conexion->query("SELECT s.nombre_sede, COUNT(*) as cantidad 
                                          FROM insumos i 
                                          LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
                                          GROUP BY s.id_sede, s.nombre_sede 
                                          ORDER BY cantidad DESC")->fetchAll();

    // Remitos recientes (cabecera) - Excluye remitos anulados
    $asignaciones_recientes = $conexion->query("SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada, ar.nombre_area, s.nombre_sede
                                                FROM remitos r
                                                JOIN areas ar ON r.id_area = ar.id_area
                                                JOIN sedes s ON r.id_sede = s.id_sede
                                                WHERE r.estado != 'Anulado'
                                                ORDER BY r.fecha_asignacion DESC 
                                                LIMIT 5")->fetchAll();

    Logger::debug('Contadores de dashboard cargados', [
        'total_insumos' => $total_insumos,
        'disponibles' => $insumos_disponibles,
        'asignados' => $insumos_asignados
    ]);
    
    json_success([
        'contadores' => [
            'total_insumos' => $total_insumos,
            'insumos_disponibles' => $insumos_disponibles,
            'insumos_asignados' => $insumos_asignados,
            'total_asignaciones' => $total_asignaciones,
            'asignaciones_activas' => $asignaciones_activas
        ],
        'insumos_por_tipo' => $insumos_por_tipo,
        'insumos_por_sede' => $insumos_por_sede,
        'asignaciones_recientes' => $asignaciones_recientes
    ]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar contadores del dashboard', [
        'mensaje' => $e->getMessage()
    ]);
    json_error('Error al cargar contadores: ' . $e->getMessage(), 500);
}
?>
