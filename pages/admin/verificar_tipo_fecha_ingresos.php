<?php
/**
 * Verificar tipo de columna fecha_finalizacion en ingresos
 */

require_once '../../includes/config.php';

try {
    $db = conectarDB();
    
    echo "<h3>Verificación de Tipo de Columna fecha_finalizacion</h3>";
    echo "<pre>";
    
    // Ver tipo de columna
    $col = $db->query("SHOW COLUMNS FROM ingresos WHERE Field = 'fecha_finalizacion'")->fetch();
    
    echo "Columna: fecha_finalizacion\n";
    echo "Tipo: {$col['Type']}\n";
    echo "Null: {$col['Null']}\n";
    echo "Default: " . ($col['Default'] ?? 'NULL') . "\n\n";
    
    // Verificar timezone de MySQL
    $tz = $db->query("SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz")->fetch();
    echo "MySQL Timezone:\n";
    echo "Session: {$tz['session_tz']}\n";
    echo "Global: {$tz['global_tz']}\n\n";
    
    // Prueba de inserción
    echo "PRUEBA DE INSERCIÓN:\n";
    echo str_repeat("=", 60) . "\n";
    
    $db->beginTransaction();
    
    $fechaPrueba = '2025-12-31';
    echo "1. Insertando fecha: {$fechaPrueba}\n";
    
    $stmt = $db->prepare('INSERT INTO ingresos (tipo_ingreso, nro_referencia, fecha_finalizacion, descripcion) VALUES (?, ?, ?, ?)');
    $stmt->execute(['otros', 'TEST-FECHA-' . time(), $fechaPrueba, 'Prueba de fecha']);
    $idTest = $db->lastInsertId();
    
    // Leer de diferentes formas
    $r1 = $db->query("SELECT fecha_finalizacion FROM ingresos WHERE id_ingreso = {$idTest}")->fetchColumn();
    $r2 = $db->query("SELECT DATE(fecha_finalizacion) FROM ingresos WHERE id_ingreso = {$idTest}")->fetchColumn();
    
    echo "2. Fecha guardada (sin DATE): {$r1}\n";
    echo "3. Fecha guardada (con DATE): {$r2}\n\n";
    
    if ($r2 === $fechaPrueba) {
        echo "✓ La fecha se guarda CORRECTAMENTE\n\n";
    } else {
        echo "✗ ERROR: Fecha guardada ({$r2}) != Fecha enviada ({$fechaPrueba})\n";
        echo "Diferencia: " . (strtotime($r2) - strtotime($fechaPrueba)) . " segundos\n\n";
    }
    
    $db->rollBack();
    echo "4. Prueba revertida (rollback)\n\n";
    
    // Recomendación
    echo str_repeat("=", 60) . "\n";
    if ($col['Type'] === 'date') {
        echo "✓ TIPO CORRECTO: DATE\n";
        echo "  No debería haber problemas de timezone\n\n";
    } else {
        echo "⚠ TIPO: {$col['Type']}\n";
        echo "  RECOMENDACIÓN: Cambiar a DATE\n\n";
        echo "SQL para corregir:\n";
        echo "ALTER TABLE ingresos MODIFY COLUMN fecha_finalizacion DATE NULL;\n\n";
    }
    
    echo "CONCLUSIÓN:\n";
    echo "Si el tipo es DATE y sigue habiendo -1 día, el problema está en:\n";
    echo "1. JavaScript al enviar la fecha (verificar formato)\n";
    echo "2. PHP al recibir la fecha (verificar conversión)\n";
    echo "3. MySQL al guardar (verificar sql_mode)\n";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
