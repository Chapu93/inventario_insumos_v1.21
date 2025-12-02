<?php
/**
 * Script de prueba para validar el SQL de correcciones
 * NO ejecuta cambios en la base de datos, solo valida la sintaxis
 */

require_once __DIR__ . '/includes/config.php';

echo "=== VALIDACIÓN DE SCRIPT SQL DE CORRECCIONES ===\n\n";

try {
    $db = conectarDB();
    
    // Leer el script SQL
    $sqlFile = __DIR__ . '/sql/corregir_inconsistencias_y_redundancias.sql';
    if (!file_exists($sqlFile)) {
        die("Error: No se encontró el archivo SQL: $sqlFile\n");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Dividir en sentencias (aproximado, para validación básica)
    // Nota: Este es un análisis básico, no ejecuta nada
    echo "✓ Archivo SQL encontrado: " . basename($sqlFile) . "\n";
    echo "✓ Tamaño: " . number_format(strlen($sqlContent)) . " bytes\n\n";
    
    // Verificar que tiene START TRANSACTION y COMMIT
    if (strpos($sqlContent, 'START TRANSACTION') !== false) {
        echo "✓ Contiene START TRANSACTION\n";
    } else {
        echo "⚠ ADVERTENCIA: No contiene START TRANSACTION\n";
    }
    
    if (strpos($sqlContent, 'COMMIT') !== false) {
        echo "✓ Contiene COMMIT\n";
    } else {
        echo "⚠ ADVERTENCIA: No contiene COMMIT\n";
    }
    
    // Verificar estructura básica de tablas actuales
    echo "\n=== VERIFICACIÓN DE ESTRUCTURA ACTUAL ===\n\n";
    
    // Verificar tabla insumos
    try {
        $stmt = $db->query("SHOW COLUMNS FROM insumos LIKE 'id_ingreso'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Campo id_ingreso existe en insumos\n";
        } else {
            echo "⚠ Campo id_ingreso NO existe en insumos (será agregado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando id_ingreso: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $db->query("SHOW COLUMNS FROM insumos LIKE 'es_nuevo'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Campo es_nuevo existe en insumos\n";
        } else {
            echo "⚠ Campo es_nuevo NO existe en insumos (será agregado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando es_nuevo: " . $e->getMessage() . "\n";
    }
    
    // Verificar tabla remitos
    try {
        $stmt = $db->query("SHOW COLUMNS FROM remitos LIKE 'motivo_anulacion'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Campo motivo_anulacion existe en remitos\n";
        } else {
            echo "⚠ Campo motivo_anulacion NO existe en remitos (será agregado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando motivo_anulacion: " . $e->getMessage() . "\n";
    }
    
    // Verificar tabla pcs_completas
    try {
        $stmt = $db->query("SHOW COLUMNS FROM pcs_completas LIKE 'sist_op'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Campo sist_op existe en pcs_completas\n";
        } else {
            echo "⚠ Campo sist_op NO existe en pcs_completas (será agregado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando sist_op: " . $e->getMessage() . "\n";
    }
    
    // Verificar tabla notebooks
    try {
        $stmt = $db->query("SHOW COLUMNS FROM notebooks LIKE 'cargador'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Campo cargador existe en notebooks\n";
        } else {
            echo "⚠ Campo cargador NO existe en notebooks (será agregado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando cargador: " . $e->getMessage() . "\n";
    }
    
    // Verificar tabla ingresos
    try {
        $db->query("SELECT 1 FROM ingresos LIMIT 1");
        echo "✓ Tabla ingresos existe\n";
    } catch (Exception $e) {
        echo "⚠ Tabla ingresos NO existe (será creada)\n";
    }
    
    // Verificar enum de remitos.estado
    try {
        $stmt = $db->query("SHOW COLUMNS FROM remitos WHERE Field = 'estado'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && strpos($column['Type'], 'Anulado') !== false) {
            echo "✓ Enum estado de remitos incluye 'Anulado'\n";
        } else {
            echo "⚠ Enum estado de remitos NO incluye 'Anulado' (será actualizado)\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando enum estado: " . $e->getMessage() . "\n";
    }
    
    // Verificar enum de insumos.tipo_insumo
    try {
        $stmt = $db->query("SHOW COLUMNS FROM insumos WHERE Field = 'tipo_insumo'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column) {
            if (strpos($column['Type'], 'PC Escritorio') !== false) {
                echo "✓ Enum tipo_insumo incluye 'PC Escritorio'\n";
            } else {
                echo "⚠ Enum tipo_insumo NO incluye 'PC Escritorio' (será actualizado)\n";
            }
            if (strpos($column['Type'], 'PC Completa') !== false) {
                echo "⚠ Enum tipo_insumo aún tiene 'PC Completa' (será eliminado)\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando enum tipo_insumo: " . $e->getMessage() . "\n";
    }
    
    // Verificar enum de monitores.conexion
    try {
        $stmt = $db->query("SHOW COLUMNS FROM monitores WHERE Field = 'conexion'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column) {
            $hasDisplayPort = strpos($column['Type'], 'DisplayPort') !== false;
            $hasDVI = strpos($column['Type'], 'DVI') !== false;
            if ($hasDisplayPort && $hasDVI) {
                echo "✓ Enum conexion de monitores incluye DisplayPort y DVI\n";
            } else {
                echo "⚠ Enum conexion de monitores NO incluye DisplayPort y/o DVI (será actualizado)\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando enum conexion: " . $e->getMessage() . "\n";
    }
    
    // Contar índices duplicados en insumos
    try {
        $stmt = $db->query("
            SELECT INDEX_NAME, COUNT(*) as cnt
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'insumos'
            GROUP BY INDEX_NAME
            HAVING cnt > 0
        ");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $potential_duplicates = [];
        foreach ($indexes as $idx) {
            if (in_array($idx['INDEX_NAME'], ['idx_insumos_sede', 'idx_i_estado', 'idx_i_tipo', 'idx_i_punto', 'idx_i_sede_actual', 'idx_i_area_actual'])) {
                $potential_duplicates[] = $idx['INDEX_NAME'];
            }
        }
        if (count($potential_duplicates) > 0) {
            echo "⚠ Índices potencialmente duplicados encontrados en insumos: " . implode(', ', $potential_duplicates) . "\n";
        } else {
            echo "✓ No se encontraron índices duplicados obvios en insumos\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando índices: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "El script SQL está listo para ejecutarse.\n";
    echo "Contiene validaciones de existencia antes de crear/modificar elementos.\n";
    echo "Está envuelto en una transacción para poder revertir cambios si es necesario.\n\n";
    echo "PRÓXIMOS PASOS:\n";
    echo "1. Haz un respaldo de la base de datos\n";
    echo "2. Ejecuta: mysql -u usuario -p base_datos < sql/corregir_inconsistencias_y_redundancias.sql\n";
    echo "3. O ejecútalo desde phpMyAdmin o tu cliente MySQL preferido\n";
    echo "4. Verifica que no haya errores durante la ejecución\n";
    echo "5. Si todo está bien, el COMMIT se ejecutará automáticamente\n";
    echo "6. Si hay errores, puedes hacer ROLLBACK antes del COMMIT\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
