<?php
require_once '../../includes/config.php';

$db = conectarDB();

echo "<h3>Migración 005: Agregar campo es_nuevo a tabla insumos</h3>";

try {
    $db->beginTransaction();
    
    // Verificar si la columna ya existe
    $check = $db->query("SHOW COLUMNS FROM insumos LIKE 'es_nuevo'");
    if ($check->rowCount() == 0) {
        echo "<p>Agregando columna 'es_nuevo'...</p>";
        $db->exec("ALTER TABLE insumos ADD COLUMN es_nuevo TINYINT(1) DEFAULT 1 COMMENT '1=Nuevo, 0=Usado' AFTER id_licitacion");
        echo "<p style='color: green;'>✓ Columna 'es_nuevo' agregada correctamente</p>";
    } else {
        echo "<p style='color: orange;'>⚠ La columna 'es_nuevo' ya existe</p>";
    }
    
    $db->commit();
    echo "<p style='color: green; font-weight: bold;'>✓ Migración completada exitosamente</p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<p style='color: red;'>✗ Error en la migración: " . $e->getMessage() . "</p>";
}

echo "<br><a href='../../pages/insumos/listar.php' class='btn btn-primary'>Volver a Insumos</a>";
?>
