<?php
/**
 * SITIA - Script de Soporte y Verificación de Migración de Almacenamiento
 * 
 * Uso vía terminal:
 *   php migrar_almacenamiento_datos.php --dry-run   (Simulación sin cambios)
 *   php migrar_almacenamiento_datos.php --apply     (Aplica cambios definitivamente)
 * 
 * Uso vía navegador (solo administradores autenticados):
 *   http://localhost/inventario_app/migrar_almacenamiento_datos.php
 */

require_once __DIR__ . '/includes/config.php';

$esCli = (php_sapi_name() === 'cli');

if (!$esCli) {
    requerirAutenticacion();
    if (!tienePermiso('admin', 'ver') && !tieneRol('Super Administrador')) {
        die("Acceso restringido a administradores.");
    }
}

$db = conectarDB();

$modoDryRun = true;
if ($esCli) {
    global $argv;
    if (isset($argv[1]) && $argv[1] === '--apply') {
        $modoDryRun = false;
    }
} else {
    if (isset($_GET['ejecutar']) && $_GET['ejecutar'] === '1') {
        $modoDryRun = false;
    }
}

$salida = [];
$salida[] = "============================================================";
$salida[] = "SITIA - VERIFICACIÓN Y MIGRACIÓN DE ALMACENAMIENTO";
$salida[] = "Modo: " . ($modoDryRun ? "SIMULACIÓN (Dry-run: no se modificarán datos)" : "EJECUCIÓN REAL (Se aplicarán cambios en la BD)");
$salida[] = "============================================================" . PHP_EOL;

try {
    // 1. Verificar tabla opciones_almacenamiento
    $stmtOpc = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'opciones_almacenamiento'");
    if ((int)$stmtOpc->fetchColumn() === 0) {
        $salida[] = "Creando tabla opciones_almacenamiento...";
        if (!$modoDryRun) {
            $db->exec("CREATE TABLE IF NOT EXISTS `opciones_almacenamiento` (
              `id_opcion` INT NOT NULL AUTO_INCREMENT,
              `capacidad_gb` INT NOT NULL,
              `etiqueta` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
              `orden` INT NOT NULL DEFAULT 0,
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id_opcion`),
              UNIQUE KEY `uk_capacidad` (`capacidad_gb`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        }
    }

    // Sincronizar registros del catálogo opciones_almacenamiento
    $opcionesDefinidas = [
        [32, '32 GB', 10],
        [80, '80 GB', 20],
        [160, '160 GB', 30],
        [256, '256 GB', 40],
        [320, '320 GB', 50],
        [480, '480 GB', 60],
        [512, '512 GB', 70],
        [1024, '1 TB (1024 GB)', 80],
        [2048, '2 TB (2048 GB)', 90],
        [4096, '4 TB (4096 GB)', 100],
        [8192, '8 TB (8192 GB)', 110],
        [16384, '16 TB (16384 GB)', 120],
    ];
    if (!$modoDryRun) {
        $db->exec("DELETE FROM `opciones_almacenamiento` WHERE `capacidad_gb` IN (64, 120, 128, 240, 250, 500)");
        $stmtIns = $db->prepare("INSERT INTO `opciones_almacenamiento` (`capacidad_gb`, `etiqueta`, `orden`, `activo`) 
            VALUES (?, ?, ?, 1) 
            ON DUPLICATE KEY UPDATE `etiqueta` = VALUES(`etiqueta`), `orden` = VALUES(`orden`), `activo` = 1");
        foreach ($opcionesDefinidas as $opc) {
            $stmtIns->execute($opc);
        }
    }

    // 2. Verificar columna ssd_o_superior en notebooks y columnas secundarias en pcs_completas
    $stmtCol = $db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'notebooks' AND column_name = 'ssd_o_superior'");
    if ((int)$stmtCol->fetchColumn() === 0) {
        $salida[] = "Agregando columna ssd_o_superior en notebooks...";
        if (!$modoDryRun) {
            $db->exec("ALTER TABLE `notebooks` ADD COLUMN `ssd_o_superior` TINYINT(1) NOT NULL DEFAULT 0;");
        }
    }

    $stmtColPc = $db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pcs_completas' AND column_name = 'almacenamiento_secundario_gb'");
    if ((int)$stmtColPc->fetchColumn() === 0) {
        $salida[] = "Agregando columnas de almacenamiento secundario en pcs_completas...";
        if (!$modoDryRun) {
            $db->exec("ALTER TABLE `pcs_completas` 
                ADD COLUMN `almacenamiento_secundario_gb` INT NULL DEFAULT NULL AFTER `ssd_o_superior`,
                ADD COLUMN `ssd_secundario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `almacenamiento_secundario_gb`;");
        }
    }

    // 3. Crear backups
    if (!$modoDryRun) {
        $db->exec("CREATE TABLE IF NOT EXISTS pcs_completas_backup_disco AS SELECT * FROM pcs_completas;");
        $db->exec("CREATE TABLE IF NOT EXISTS notebooks_backup_disco AS SELECT * FROM notebooks;");
        $salida[] = "Tablas de respaldo creadas o verificadas: pcs_completas_backup_disco, notebooks_backup_disco.";
    }

    // 4. Analizar registros que requieren normalización
    $capacidadesEstandar = [32, 80, 160, 256, 320, 480, 512, 1024, 2048, 4096, 8192, 16384];

    $funcionNormalizar = function($val) use ($capacidadesEstandar) {
        if ($val === null || $val === '') return null;
        $v = (int)$val;
        // Casos especiales directos
        if ($v === 1 || $v === 1000 || $v === 1204 || $v === 1023) return 1024;
        if ($v === 64) return 80;
        if (in_array($v, [120, 128, 150, 186], true)) return 160;
        if (in_array($v, [240, 250], true)) return 256;
        if ($v === 500) return 512;

        // Si ya es estándar, conservar
        if (in_array($v, $capacidadesEstandar, true)) return $v;

        // Buscar el más cercano
        $masCercano = $capacidadesEstandar[0];
        $menorDif = abs($v - $masCercano);
        foreach ($capacidadesEstandar as $c) {
            $dif = abs($v - $c);
            if ($dif < $menorDif) {
                $menorDif = $dif;
                $masCercano = $c;
            }
        }
        return $masCercano;
    };

    // Analizar PCs
    $pcs = $db->query("SELECT p.id_pc_completa, p.id_insumo, i.nombre_insumo, p.almacenamiento_gb, p.ssd_o_superior FROM pcs_completas p LEFT JOIN insumos i ON i.id_insumo = p.id_insumo")->fetchAll(PDO::FETCH_ASSOC);
    $pcsModificadas = 0;

    $salida[] = PHP_EOL . "--- REVISIÓN EN PCS COMPLETAS (" . count($pcs) . " registros) ---";
    foreach ($pcs as $pc) {
        $actual = $pc['almacenamiento_gb'];
        $normalizado = $funcionNormalizar($actual);
        if ($actual !== null && (int)$actual !== (int)$normalizado) {
            $pcsModificadas++;
            $salida[] = sprintf("  PC ID: %d (Insumo: %d, '%s') => Valor actual: %s GB -> Nuevo valor: %d GB", 
                $pc['id_pc_completa'], $pc['id_insumo'], $pc['nombre_insumo'] ?? 'Sin nombre', $actual, $normalizado);
            if (!$modoDryRun) {
                $db->prepare("UPDATE pcs_completas SET almacenamiento_gb = ? WHERE id_pc_completa = ?")->execute([$normalizado, $pc['id_pc_completa']]);
            }
        }
    }
    if ($pcsModificadas === 0) {
        $salida[] = "  Todos los registros de PCs ya coinciden con valores estándar comerciales.";
    } else {
        $salida[] = "  Total de PCs a actualizar: " . $pcsModificadas;
    }

    // Analizar Notebooks
    $nbs = $db->query("SELECT n.id_notebook, n.id_insumo, i.nombre_insumo, n.marca, n.modelo, n.almacenamiento_gb FROM notebooks n LEFT JOIN insumos i ON i.id_insumo = n.id_insumo")->fetchAll(PDO::FETCH_ASSOC);
    $nbsModificadas = 0;

    $salida[] = PHP_EOL . "--- REVISIÓN EN NOTEBOOKS (" . count($nbs) . " registros) ---";
    foreach ($nbs as $nb) {
        $actual = $nb['almacenamiento_gb'];
        $normalizado = $funcionNormalizar($actual);
        if ($actual !== null && (int)$actual !== (int)$normalizado) {
            $nbsModificadas++;
            $salida[] = sprintf("  NB ID: %d (Insumo: %d, '%s %s') => Valor actual: %s GB -> Nuevo valor: %d GB", 
                $nb['id_notebook'], $nb['id_insumo'], $nb['marca'], $nb['modelo'], $actual, $normalizado);
            if (!$modoDryRun) {
                $db->prepare("UPDATE notebooks SET almacenamiento_gb = ? WHERE id_notebook = ?")->execute([$normalizado, $nb['id_notebook']]);
            }
        }
    }
    if ($nbsModificadas === 0) {
        $salida[] = "  Todos los registros de Notebooks ya coinciden con valores estándar comerciales.";
    } else {
        $salida[] = "  Total de Notebooks a actualizar: " . $nbsModificadas;
    }

    $salida[] = PHP_EOL . "============================================================";
    $salida[] = $modoDryRun ? "Simulación completada sin errores. Para aplicar: php migrar_almacenamiento_datos.php --apply" : "Migración completada y aplicada con éxito.";
    $salida[] = "============================================================";

} catch (Exception $e) {
    $salida[] = "ERROR: " . $e->getMessage();
}

if ($esCli) {
    echo implode(PHP_EOL, $salida) . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo implode(PHP_EOL, $salida);
}
