<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = conectarDB();

    echo '<h3>Migración: Numeración atómica de remitos</h3>';

    // 1) Crear tabla de secuencias si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS remito_secuencia (
        anio INT NOT NULL,
        ultimo INT NOT NULL DEFAULT 0,
        PRIMARY KEY (anio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo '<p>Tabla <strong>remito_secuencia</strong> verificada/creada.</p>';

    // 2) Inicializar secuencia con el máximo existente del año
    $anio = (int)date('Y');
    $maxStmt = $db->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(numero_remito, '_', 1) AS UNSIGNED)) AS maxseq FROM remitos WHERE numero_remito LIKE ?");
    $maxStmt->execute(["%_{$anio}"]);
    $max = (int)($maxStmt->fetch()['maxseq'] ?? 0);
    $db->prepare("INSERT INTO remito_secuencia (anio, ultimo) VALUES (?, ?) ON DUPLICATE KEY UPDATE ultimo = GREATEST(ultimo, VALUES(ultimo))")
       ->execute([$anio, $max]);
    echo '<p>Secuencia ' . htmlspecialchars((string)$anio) . ' inicializada a ' . (int)$max . '.</p>';

    // 3) Verificar duplicados en remitos.numero_remito
    $dups = $db->query("SELECT numero_remito, COUNT(*) c FROM remitos GROUP BY numero_remito HAVING c > 1 LIMIT 5")->fetchAll();
    if (!empty($dups)) {
        echo '<p style="color:#d9534f">Se detectaron números de remito duplicados. No se creará el índice único.</p>';
        echo '<ul>';
        foreach ($dups as $d) {
            echo '<li>' . htmlspecialchars($d['numero_remito']) . ' (x' . (int)$d['c'] . ')</li>';
        }
        echo '</ul>';
    } else {
        // 4) Crear índice único si no existe
        $idxExistsStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'remitos' AND index_name = 'uniq_numero_remito'");
        $idxExistsStmt->execute();
        $idxExists = (int)$idxExistsStmt->fetchColumn() > 0;
        if ($idxExists) {
            echo '<p>Índice único <strong>uniq_numero_remito</strong> ya existe.</p>';
        } else {
            $db->exec("ALTER TABLE remitos ADD UNIQUE KEY uniq_numero_remito (numero_remito)");
            echo '<p>Índice único <strong>uniq_numero_remito</strong> creado en <strong>remitos(numero_remito)</strong>.</p>';
        }
    }

    echo '<p><a href="' . htmlspecialchars(app_base_url() . '/pages/asignaciones/nueva.php') . '">Ir a Nueva Asignación</a></p>';
    echo '<p style="margin-top:8px"><a href="' . htmlspecialchars(app_base_url() . '/pages/reportes/remito.php') . '">Ver Remitos</a></p>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<p>Error en migración: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

