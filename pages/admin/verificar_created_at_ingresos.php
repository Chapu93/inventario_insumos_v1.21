<?php
/**
 * Verificar campo created_at en tabla ingresos
 */

require_once '../../includes/config.php';

try {
    $db = conectarDB();
    
    echo "<h3>Verificación de campo created_at en tabla ingresos</h3>";
    echo "<pre>";
    
    // 1. Verificar estructura de la tabla
    echo "1. ESTRUCTURA DE TABLA 'ingresos':\n";
    echo str_repeat("=", 60) . "\n";
    $columns = $db->query("SHOW COLUMNS FROM ingresos")->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("%-20s %-15s %-8s %-8s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'],
            $col['Default'] ?? ''
        );
    }
    echo "\n";
    
    // 2. Verificar si existe created_at
    $hasCreatedAt = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'created_at') {
            $hasCreatedAt = true;
            echo "✓ Campo 'created_at' EXISTE\n";
            echo "  Tipo: {$col['Type']}\n";
            echo "  Null: {$col['Null']}\n";
            echo "  Default: " . ($col['Default'] ?: 'NULL') . "\n\n";
            break;
        }
    }
    
    if (!$hasCreatedAt) {
        echo "✗ Campo 'created_at' NO EXISTE\n\n";
        echo "SOLUCIÓN: Ejecutar el siguiente SQL:\n";
        echo "ALTER TABLE ingresos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;\n\n";
    }
    
    // 3. Verificar datos y fechas
    echo "2. DATOS EN TABLA 'ingresos':\n";
    echo str_repeat("=", 60) . "\n";
    $total = $db->query("SELECT COUNT(*) FROM ingresos")->fetchColumn();
    echo "Total de registros: {$total}\n\n";
    
    if ($total > 0) {
        // Ver distribución de fechas
        if ($hasCreatedAt) {
            echo "Distribución por fecha de creación:\n";
            $fechas = $db->query("
                SELECT 
                    DATE(created_at) as fecha,
                    COUNT(*) as cantidad
                FROM ingresos 
                GROUP BY DATE(created_at)
                ORDER BY fecha DESC
                LIMIT 10
            ")->fetchAll();
            
            foreach ($fechas as $f) {
                echo "  {$f['fecha']}: {$f['cantidad']} ingreso(s)\n";
            }
            echo "\n";
        }
        
        // Ver últimos 10 registros ordenados por created_at
        echo "Últimos 10 ingresos ordenados por created_at DESC:\n";
        echo str_repeat("-", 100) . "\n";
        echo sprintf("%-5s %-15s %-25s %-20s\n", "ID", "Tipo", "Nro. Referencia", "Fecha Creación");
        echo str_repeat("-", 100) . "\n";
        
        if ($hasCreatedAt) {
            $ultimos = $db->query("
                SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at
                FROM ingresos 
                ORDER BY created_at DESC
                LIMIT 10
            ")->fetchAll();
        } else {
            $ultimos = $db->query("
                SELECT id_ingreso, tipo_ingreso, nro_referencia, 'NO DISPONIBLE' as created_at
                FROM ingresos 
                LIMIT 10
            ")->fetchAll();
        }
        
        foreach ($ultimos as $ing) {
            $fecha = $ing['created_at'] !== 'NO DISPONIBLE' 
                ? date('Y-m-d H:i:s', strtotime($ing['created_at']))
                : 'NO DISPONIBLE';
            echo sprintf("%-5d %-15s %-25s %-20s\n", 
                $ing['id_ingreso'],
                $ing['tipo_ingreso'],
                substr($ing['nro_referencia'], 0, 25),
                $fecha
            );
        }
        echo "\n";
        
        // Verificar si todas tienen la misma fecha (migración)
        if ($hasCreatedAt && $total > 1) {
            $fechasUnicas = $db->query("
                SELECT COUNT(DISTINCT DATE(created_at)) as distintas
                FROM ingresos
            ")->fetchColumn();
            
            if ($fechasUnicas == 1) {
                echo "⚠️  ADVERTENCIA: Todos los ingresos tienen la MISMA FECHA\n";
                echo "    Esto es normal si todos fueron migrados al mismo tiempo.\n";
                echo "    Los nuevos ingresos que crees aparecerán primero.\n\n";
            } else {
                echo "✓ Los ingresos tienen fechas diferentes ({$fechasUnicas} fechas distintas)\n\n";
            }
        }
    }
    
    // 4. Probar query del formulario
    echo "3. PRUEBA DE QUERY DEL FORMULARIO:\n";
    echo str_repeat("=", 60) . "\n";
    if ($hasCreatedAt) {
        echo "Query: SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at \n";
        echo "       FROM ingresos ORDER BY created_at DESC\n\n";
        
        $test = $db->query("
            SELECT id_ingreso, tipo_ingreso, nro_referencia, created_at 
            FROM ingresos 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
        
        echo "Resultado (primeros 5):\n";
        foreach ($test as $t) {
            echo "  [{$t['tipo_ingreso']}] {$t['nro_referencia']} - {$t['created_at']}\n";
        }
        echo "\n✓ Query ejecuta correctamente\n\n";
    } else {
        echo "✗ No se puede probar: falta campo created_at\n\n";
    }
    
    // 5. Resumen
    echo str_repeat("=", 60) . "\n";
    echo "RESUMEN:\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($hasCreatedAt) {
        echo "✓ Campo created_at: EXISTE\n";
        echo "✓ Query ORDER BY created_at DESC: FUNCIONA\n";
        echo "✓ Total de ingresos: {$total}\n";
        
        if ($total > 0) {
            $masReciente = $db->query("SELECT created_at FROM ingresos ORDER BY created_at DESC LIMIT 1")->fetchColumn();
            $masAntiguo = $db->query("SELECT created_at FROM ingresos ORDER BY created_at ASC LIMIT 1")->fetchColumn();
            echo "✓ Más reciente: {$masReciente}\n";
            echo "✓ Más antiguo: {$masAntiguo}\n";
        }
        
        echo "\nCONCLUSIÓN: ✅ TODO CORRECTO\n";
        echo "\nSi todos tienen la misma fecha (migración), es normal.\n";
        echo "Los NUEVOS ingresos que crees aparecerán PRIMERO en la lista.\n";
    } else {
        echo "✗ Campo created_at: NO EXISTE\n";
        echo "\nCONCLUSIÓN: ⚠️  FALTA AGREGAR CAMPO\n";
        echo "\nSOLUCIÓN:\n";
        echo "ALTER TABLE ingresos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
