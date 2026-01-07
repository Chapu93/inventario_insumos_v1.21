<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db = conectarDB();
    $sql = file_get_contents(__DIR__ . '/migracion_pedidos.sql');
    
    // Separar sentencias por punto y coma (básico)
    // Nota: Esto puede fallar si hay ; dentro de strings, pero para este script simple está bien.
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "Migración completada exitosamente.";
    
} catch (Exception $e) {
    echo "Error en la migración: " . $e->getMessage();
}
?>
