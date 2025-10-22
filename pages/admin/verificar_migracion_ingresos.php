<?php
/**
 * Verificación Post-Migración 006: Ingresos
 */

require_once '../../includes/config.php';

try {
    $db = conectarDB();
    
    echo "<h3>Verificación de Migración 006: Licitaciones → Ingresos</h3>";
    echo "<pre>";
    
    // 1. Verificar que existe tabla ingresos
    echo "1. Verificando tabla 'ingresos'...\n";
    $check = $db->query("SHOW TABLES LIKE 'ingresos'")->fetch();
    if ($check) {
        echo "   ✓ Tabla 'ingresos' existe\n\n";
    } else {
        echo "   ✗ ERROR: Tabla 'ingresos' NO existe\n";
        exit;
    }
    
    // 2. Verificar estructura de tabla ingresos
    echo "2. Estructura de tabla 'ingresos':\n";
    $columns = $db->query("SHOW COLUMNS FROM ingresos")->fetchAll();
    echo "   Columnas encontradas:\n";
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Verificar columnas específicas
    $requiredCols = ['id_ingreso', 'tipo_ingreso', 'nro_referencia', 'fecha_finalizacion', 'descripcion'];
    $foundCols = array_column($columns, 'Field');
    $missing = array_diff($requiredCols, $foundCols);
    
    if (empty($missing)) {
        echo "   ✓ Todas las columnas requeridas existen\n\n";
    } else {
        echo "   ✗ ERROR: Faltan columnas: " . implode(', ', $missing) . "\n";
    }
    
    // 3. Verificar tabla insumos
    echo "3. Verificando columna 'id_ingreso' en tabla 'insumos'...\n";
    $checkCol = $db->query("SHOW COLUMNS FROM insumos LIKE 'id_ingreso'")->fetch();
    if ($checkCol) {
        echo "   ✓ Columna 'id_ingreso' existe en tabla insumos\n";
        echo "   Tipo: {$checkCol['Type']}, Null: {$checkCol['Null']}\n\n";
    } else {
        echo "   ✗ ERROR: Columna 'id_ingreso' NO existe en tabla insumos\n";
    }
    
    // 4. Verificar Foreign Key
    echo "4. Verificando Foreign Key...\n";
    $fk = $db->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'insumos' 
          AND COLUMN_NAME = 'id_ingreso'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetch();
    
    if ($fk) {
        echo "   ✓ Foreign Key existe:\n";
        echo "   Constraint: {$fk['CONSTRAINT_NAME']}\n";
        echo "   Referencia: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n\n";
    } else {
        echo "   ⚠ WARNING: No se encontró Foreign Key\n\n";
    }
    
    // 5. Verificar datos migrados
    echo "5. Datos en tabla 'ingresos':\n";
    $count = $db->query("SELECT COUNT(*) FROM ingresos")->fetchColumn();
    echo "   Total de registros: {$count}\n";
    
    if ($count > 0) {
        echo "\n   Distribución por tipo:\n";
        $tipos = $db->query("
            SELECT tipo_ingreso, COUNT(*) as total 
            FROM ingresos 
            GROUP BY tipo_ingreso
        ")->fetchAll();
        
        foreach ($tipos as $tipo) {
            echo "   - {$tipo['tipo_ingreso']}: {$tipo['total']}\n";
        }
        
        echo "\n   Primeros 5 registros:\n";
        $ejemplos = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia FROM ingresos LIMIT 5")->fetchAll();
        foreach ($ejemplos as $ej) {
            echo "   - ID {$ej['id_ingreso']}: [{$ej['tipo_ingreso']}] {$ej['nro_referencia']}\n";
        }
    }
    echo "\n";
    
    // 6. Verificar insumos asociados
    echo "6. Insumos asociados a ingresos:\n";
    $insumosConIngreso = $db->query("SELECT COUNT(*) FROM insumos WHERE id_ingreso IS NOT NULL")->fetchColumn();
    $insumosTotal = $db->query("SELECT COUNT(*) FROM insumos")->fetchColumn();
    echo "   Insumos con ingreso: {$insumosConIngreso} / {$insumosTotal}\n";
    
    if ($insumosConIngreso > 0) {
        echo "\n   Distribución de insumos por tipo de ingreso:\n";
        $dist = $db->query("
            SELECT ing.tipo_ingreso, COUNT(ins.id_insumo) as total
            FROM ingresos ing
            LEFT JOIN insumos ins ON ins.id_ingreso = ing.id_ingreso
            GROUP BY ing.tipo_ingreso
        ")->fetchAll();
        
        foreach ($dist as $d) {
            echo "   - {$d['tipo_ingreso']}: {$d['total']} insumos\n";
        }
    }
    echo "\n";
    
    // 7. Verificar que NO existe tabla licitaciones (fue renombrada)
    echo "7. Verificando que tabla 'licitaciones' fue renombrada...\n";
    $oldTable = $db->query("SHOW TABLES LIKE 'licitaciones'")->fetch();
    if ($oldTable) {
        echo "   ⚠ WARNING: La tabla 'licitaciones' todavía existe\n";
        echo "   Esto sugiere que la migración no se completó correctamente\n";
    } else {
        echo "   ✓ Tabla 'licitaciones' no existe (fue renombrada correctamente)\n";
    }
    echo "\n";
    
    echo "════════════════════════════════════════\n";
    echo "✓ VERIFICACIÓN COMPLETADA\n";
    echo "════════════════════════════════════════\n";
    echo "\nResumen:\n";
    echo "• Tabla 'ingresos': " . ($check ? "✓ OK" : "✗ ERROR") . "\n";
    echo "• Columnas requeridas: " . (empty($missing) ? "✓ OK" : "✗ ERROR") . "\n";
    echo "• FK en insumos: " . ($fk ? "✓ OK" : "⚠ WARNING") . "\n";
    echo "• Registros migrados: {$count}\n";
    echo "• Insumos asociados: {$insumosConIngreso}\n";
    echo "\n";
    
    if ($check && empty($missing) && $count > 0) {
        echo "✅ LA MIGRACIÓN FUE EXITOSA\n";
        echo "\nPróximos pasos:\n";
        echo "1. Ir a: Insumos → Ingresos\n";
        echo "2. Verificar que el listado se carga correctamente\n";
        echo "3. Probar crear un nuevo ingreso\n";
        echo "4. Probar asignar insumo a un ingreso\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error en verificación:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
