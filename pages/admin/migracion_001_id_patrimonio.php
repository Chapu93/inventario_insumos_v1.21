<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = conectarDB();

    $existsStmt = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'insumos' AND COLUMN_NAME = 'id_patrimonio'");
    $existsStmt->execute();
    $exists = (int)$existsStmt->fetchColumn() > 0;

    if ($exists) {
        echo '<p>La columna <strong>id_patrimonio</strong> ya existe en la tabla <strong>insumos</strong>.</p>';
    } else {
        $db->exec("ALTER TABLE insumos ADD COLUMN id_patrimonio VARCHAR(50) NULL AFTER id_fisico");
        echo '<p>Columna <strong>id_patrimonio</strong> agregada correctamente a la tabla <strong>insumos</strong>.</p>';
    }

    echo '<p><a href="' . htmlspecialchars(app_base_url() . '/pages/insumos/listar.php') . '">Volver a Insumos</a></p>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<p>Error al ejecutar migración: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
