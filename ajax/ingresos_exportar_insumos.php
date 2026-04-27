<?php
require_once '../includes/config.php';

if (!estaAutenticado() && (!defined('TESTING') || !TESTING)) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver') && (!defined('TESTING') || !TESTING)) {
    json_error('Sin permiso', 403);
}

$id_ingreso = (int)($_GET['id'] ?? 0);

if ($id_ingreso <= 0) {
    json_error('ID de ingreso inválido', 400);
}

try {
    $db = conectarDB();
    
    // Obtener detalles del ingreso
    $stmtIngreso = $db->prepare("SELECT nro_referencia, tipo_ingreso FROM ingresos WHERE id_ingreso = ?");
    $stmtIngreso->execute([$id_ingreso]);
    $ingreso = $stmtIngreso->fetch(PDO::FETCH_ASSOC);
    
    if (!$ingreso) {
        json_error('Ingreso no encontrado', 404);
    }

    $sql = "SELECT 
                ing.nro_referencia,
                ins.nombre_insumo,
                ins.tipo_insumo,
                ins.numero_serie,
                ins.estado,
                s.nombre_sede,
                l.nombre_localidad,
                a.nombre_area,
                r.numero_remito,
                r.nombre_persona_asignada,
                r.apellido_persona_asignada,
                r.fecha_asignacion
            FROM ingresos ing
            JOIN insumos ins ON ing.id_ingreso = ins.id_ingreso
            LEFT JOIN sedes s ON ins.id_sede_actual = s.id_sede
            LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
            LEFT JOIN areas a ON ins.id_area_asignacion_actual = a.id_area
            LEFT JOIN remitos_detalle rd ON ins.id_insumo = rd.id_insumo 
                AND (ins.tipo_insumo != 'Varios' OR rd.cantidad > rd.cantidad_devuelta)
            LEFT JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
            WHERE ing.id_ingreso = ?
            ORDER BY ins.nombre_insumo ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_ingreso]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_success([
        'ingreso' => $ingreso,
        'data' => $resultados
    ]);

} catch (Exception $e) {
    Logger::error('Error en ingresos_exportar_insumos: ' . $e->getMessage());
    json_error('Error interno del servidor', 500);
}
