<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

try {
    $db = conectarDB();
    $rows = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, 
                               DATE(fecha_finalizacion) as fecha_finalizacion, 
                               created_at,
                               (SELECT COUNT(*) FROM insumos i WHERE i.id_ingreso = ing.id_ingreso) AS num_insumos
                        FROM ingresos ing ORDER BY ing.created_at DESC")->fetchAll();
    
    Logger::debug('Lista de ingresos cargada', ['count' => count($rows)]);
    
    json_success($rows);
    
} catch(Exception $e) { 
    Logger::error('Error al cargar lista de ingresos', ['mensaje' => $e->getMessage()]);
    json_error($e->getMessage(), 500); 
}
?>
