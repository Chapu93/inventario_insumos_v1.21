<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = conectarDB();
    echo '<h3>Migración: Integridad de datos (insumos)</h3>';

    // 1) Unicidad id_patrimonio para tipos != 'Varios' usando columna generada
    // Crear columna generada si no existe: id_patrimonio_idx = (CASE WHEN tipo_insumo<>'Varios' THEN id_patrimonio ELSE NULL END)
    $colExists = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='insumos' AND COLUMN_NAME='id_patrimonio_idx'");
    $colExists->execute();
    if ((int)$colExists->fetchColumn() === 0) {
        // Si la versión no soporta generated columns, caemos a índice único parcial simulado (no posible en MySQL <8); probamos generated
        $db->exec("ALTER TABLE insumos ADD COLUMN id_patrimonio_idx VARCHAR(50) GENERATED ALWAYS AS (CASE WHEN (tipo_insumo <> 'Varios') THEN id_patrimonio ELSE NULL END) VIRTUAL");
        echo '<p>Columna generada <strong>id_patrimonio_idx</strong> creada.</p>';
    } else {
        echo '<p>Columna generada <strong>id_patrimonio_idx</strong> ya existe.</p>';
    }
    // Índice único sobre columna generada (permite múltiples NULL)
    $idxExists = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'insumos' AND index_name = 'uniq_id_patrimonio_idx'");
    $idxExists->execute();
    if ((int)$idxExists->fetchColumn() === 0) {
        $db->exec("ALTER TABLE insumos ADD UNIQUE KEY uniq_id_patrimonio_idx (id_patrimonio_idx)");
        echo '<p>Índice único <strong>uniq_id_patrimonio_idx</strong> creado.</p>';
    } else {
        echo '<p>Índice único <strong>uniq_id_patrimonio_idx</strong> ya existe.</p>';
    }

    // 2) Normalizar estado a conjunto conocido (si ENUM no es viable en tu versión, dejamos validación lógica)
    // Verificar y, si es posible, convertir estado a ENUM sin romper datos
    try {
        $db->exec("ALTER TABLE insumos MODIFY COLUMN estado ENUM('Disponible','Asignado','De Baja') NOT NULL DEFAULT 'Disponible'");
        echo '<p>Columna <strong>estado</strong> normalizada a ENUM.</p>';
    } catch (Exception $e) {
        echo '<p style=\'color:#d9534f\'>No se pudo convertir <strong>estado</strong> a ENUM: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }

    // 3) Índices útiles
    $ensureIdx = function(string $table, string $idx, string $def) use ($db) {
        $q = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
        $q->execute([$table, $idx]);
        if ((int)$q->fetchColumn() === 0) { $db->exec("ALTER TABLE $table ADD INDEX $idx $def"); return true; }
        return false;
    };
    if ($ensureIdx('remitos', 'idx_r_fecha', '(fecha_asignacion)')) {
        echo '<p>Índice <strong>idx_r_fecha</strong> creado en <strong>remitos(fecha_asignacion)</strong>.</p>';
    } else { echo '<p>Índice <strong>idx_r_fecha</strong> ya existe.</p>'; }

    echo '<p><a href="' . htmlspecialchars(app_base_url() . '/pages/dashboard.php') . '">Volver al Dashboard</a></p>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<p>Error en migración: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

