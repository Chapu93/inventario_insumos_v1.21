<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

Logger::debug("Ingresos GET endpoint", ['params' => $_GET]);

try {
    if (empty($_GET['id'])) {
        Logger::warning("ID vacío en ingresos_get");
        json_error('ID requerido', 400);
    }

    $id = (int) $_GET['id'];
    Logger::debug("Buscando ingreso", ['id' => $id]);

    $db = conectarDB();

    $stmt = $db->prepare('SELECT id_ingreso, tipo_ingreso, nro_referencia, 
                                 DATE(fecha_finalizacion) as fecha_finalizacion, 
                                 descripcion, created_at 
                          FROM ingresos WHERE id_ingreso=?');
    $stmt->execute([$id]);
    $cab = $stmt->fetch();

    if (!$cab) {
        Logger::warning("Ingreso no encontrado", ['id' => $id]);
        json_error('Ingreso no encontrado', 404);
    }

    Logger::debug("Ingreso encontrado", ['referencia' => $cab['nro_referencia']]);

    $ins = $db->prepare('
        SELECT 
            tipo_insumo,
            CASE WHEN tipo_insumo = \'Varios\' THEN nombre_insumo ELSE tipo_insumo END AS nombre_grupo,
            SUM(
                CASE 
                    WHEN tipo_insumo = \'Varios\' THEN
                        COALESCE(cantidad, 0)
                        + COALESCE((
                            SELECT SUM(rd.cantidad - rd.cantidad_devuelta)
                            FROM remitos_detalle rd
                            JOIN remitos r ON r.id_remito = rd.id_remito
                            WHERE rd.id_insumo = i.id_insumo
                              AND (r.estado = \'Activa\' OR r.estado = \'Devuelta\')
                        ), 0)
                    ELSE 1
                END
            ) AS cantidad_total
        FROM insumos i
        WHERE id_ingreso = ? 
        GROUP BY tipo_insumo,
                 CASE WHEN tipo_insumo = \'Varios\' THEN i.id_insumo ELSE 0 END,
                 CASE WHEN tipo_insumo = \'Varios\' THEN nombre_insumo ELSE tipo_insumo END
        ORDER BY tipo_insumo, nombre_grupo
    ');
    $ins->execute([$id]);
    $cab['insumos'] = $ins->fetchAll();

    Logger::debug("Insumos cargados", ['count' => count($cab['insumos'])]);

    json_success($cab);

} catch (Exception $e) {
    Logger::error("Error en ingresos_get", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    json_error($e->getMessage(), 500);
}
?>