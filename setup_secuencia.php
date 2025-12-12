<?php
require_once 'includes/config.php';

try {
    $conexion = conectarDB();
    // Tabla específica para remitos históricos
    $sql = "CREATE TABLE IF NOT EXISTS remitos_historicos_secuencia (
        anio INT PRIMARY KEY,
        ultimo_numero INT NOT NULL DEFAULT 0
    )";
    $conexion->exec($sql);
    echo "Tabla remitos_historicos_secuencia creada correctamente.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
