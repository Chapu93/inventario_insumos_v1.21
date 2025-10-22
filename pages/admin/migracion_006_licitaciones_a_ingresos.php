<?php
/**
 * Migración 006: Renombrar licitaciones a ingresos y agregar campo tipo
 * 
 * Cambios:
 * 1. Renombrar tabla licitaciones -> ingresos
 * 2. Agregar campo tipo_ingreso (fondos, compra_directa, licitacion, otros)
 * 3. Renombrar cod_expediente -> nro_referencia (más genérico)
 * 4. Actualizar foreign keys en tabla insumos
 */

require_once '../../includes/config.php';

try {
    $db = conectarDB();
    $db->beginTransaction();
    
    echo "<h3>Migración 006: Licitaciones → Ingresos</h3>";
    echo "<pre>";
    
    // 1. Verificar si la tabla ingresos ya existe
    $checkTable = $db->query("SHOW TABLES LIKE 'ingresos'")->fetch();
    if ($checkTable) {
        echo "✓ La tabla 'ingresos' ya existe. Verificando estructura...\n";
    } else {
        echo "→ Renombrando tabla 'licitaciones' a 'ingresos'...\n";
        $db->exec("RENAME TABLE licitaciones TO ingresos");
        echo "✓ Tabla renombrada correctamente\n";
    }
    
    // 2. Renombrar columna id_licitacion -> id_ingreso
    $checkCol = $db->query("SHOW COLUMNS FROM ingresos LIKE 'id_ingreso'")->fetch();
    if ($checkCol) {
        echo "✓ Columna 'id_ingreso' ya existe\n";
    } else {
        echo "→ Renombrando columna 'id_licitacion' a 'id_ingreso'...\n";
        $db->exec("ALTER TABLE ingresos CHANGE COLUMN id_licitacion id_ingreso INT AUTO_INCREMENT");
        echo "✓ Columna renombrada correctamente\n";
    }
    
    // 3. Agregar columna tipo_ingreso
    $checkTipo = $db->query("SHOW COLUMNS FROM ingresos LIKE 'tipo_ingreso'")->fetch();
    if ($checkTipo) {
        echo "✓ Columna 'tipo_ingreso' ya existe\n";
    } else {
        echo "→ Agregando columna 'tipo_ingreso'...\n";
        $db->exec("ALTER TABLE ingresos 
                   ADD COLUMN tipo_ingreso ENUM('fondos', 'compra_directa', 'licitacion', 'otros') 
                   DEFAULT 'licitacion' 
                   AFTER id_ingreso");
        echo "✓ Columna 'tipo_ingreso' agregada\n";
    }
    
    // 4. Renombrar columna cod_expediente -> nro_referencia
    $checkRef = $db->query("SHOW COLUMNS FROM ingresos LIKE 'nro_referencia'")->fetch();
    if ($checkRef) {
        echo "✓ Columna 'nro_referencia' ya existe\n";
    } else {
        echo "→ Renombrando columna 'cod_expediente' a 'nro_referencia'...\n";
        $db->exec("ALTER TABLE ingresos CHANGE COLUMN cod_expediente nro_referencia VARCHAR(100)");
        echo "✓ Columna renombrada correctamente\n";
    }
    
    // 5. Actualizar tabla insumos: id_licitacion -> id_ingreso
    $checkInsumoCol = $db->query("SHOW COLUMNS FROM insumos LIKE 'id_ingreso'")->fetch();
    if ($checkInsumoCol) {
        echo "✓ Columna 'id_ingreso' ya existe en tabla insumos\n";
    } else {
        // Primero eliminar foreign key si existe
        echo "→ Actualizando referencias en tabla insumos...\n";
        try {
            $db->exec("ALTER TABLE insumos DROP FOREIGN KEY insumos_ibfk_licitacion");
        } catch (Exception $e) {
            echo "  (Foreign key no existía o nombre diferente)\n";
        }
        
        // Renombrar columna
        $db->exec("ALTER TABLE insumos CHANGE COLUMN id_licitacion id_ingreso INT NULL");
        echo "✓ Columna 'id_ingreso' creada en tabla insumos\n";
        
        // Crear foreign key nueva
        echo "→ Creando nueva foreign key...\n";
        $db->exec("ALTER TABLE insumos 
                   ADD CONSTRAINT fk_insumos_ingreso 
                   FOREIGN KEY (id_ingreso) REFERENCES ingresos(id_ingreso) 
                   ON DELETE SET NULL 
                   ON UPDATE CASCADE");
        echo "✓ Foreign key creada correctamente\n";
    }
    
    $db->commit();
    
    echo "\n";
    echo "════════════════════════════════════════\n";
    echo "✓ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "════════════════════════════════════════\n";
    echo "\nCambios realizados:\n";
    echo "• Tabla 'licitaciones' → 'ingresos'\n";
    echo "• Columna 'id_licitacion' → 'id_ingreso'\n";
    echo "• Columna 'cod_expediente' → 'nro_referencia'\n";
    echo "• Campo nuevo 'tipo_ingreso' (fondos, compra_directa, licitacion, otros)\n";
    echo "• Actualizada tabla 'insumos': id_licitacion → id_ingreso\n";
    echo "• Foreign key actualizada con ON DELETE SET NULL\n";
    echo "\n";
    echo "Próximos pasos:\n";
    echo "1. Actualizar archivos PHP para usar nueva nomenclatura\n";
    echo "2. Actualizar formularios con campo tipo_ingreso\n";
    echo "3. Agregar lógica condicional para Nro Nota vs Nro Expediente\n";
    echo "</pre>";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "</pre>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error en migración:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "<pre>";
    echo "Traza:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
