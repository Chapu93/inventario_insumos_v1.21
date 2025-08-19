<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

try {
    $conexion->beginTransaction();
    
    // Obtener todas las sedes
    $stmt = $conexion->query("SELECT id_sede FROM sedes ORDER BY id_sede");
    $sedes = $stmt->fetchAll();
    
    // Obtener todas las áreas
    $stmt = $conexion->query("SELECT id_area FROM areas ORDER BY id_area");
    $areas = $stmt->fetchAll();
    
    // Limpiar tabla existente
    $conexion->exec("DELETE FROM sede_areas");
    
    // Insertar todas las combinaciones sede-área
    $sql = "INSERT INTO sede_areas (id_sede, id_area, activa) VALUES (?, ?, 1)";
    $stmt = $conexion->prepare($sql);
    
    $total = 0;
    foreach ($sedes as $sede) {
        foreach ($areas as $area) {
            $stmt->execute([$sede['id_sede'], $area['id_area']]);
            $total++;
        }
    }
    
    $conexion->commit();
    
    echo "Tabla sede_areas poblada correctamente. Se insertaron $total registros.";
    
} catch (Exception $e) {
    $conexion->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
