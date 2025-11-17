<?php
require_once '../includes/config.php';

Logger::debug("Ingresos GET endpoint", ['params' => $_GET]);

try {
    if (empty($_GET['id'])) {
        Logger::warning("ID vacío en ingresos_get");
        json_error('ID requerido', 400);
    }
    
    $id = (int)$_GET['id'];
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
    
    $ins = $db->prepare('SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, estado 
                          FROM insumos WHERE id_ingreso=? ORDER BY nombre_insumo');
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
