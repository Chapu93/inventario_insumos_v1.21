<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = conectarDB();
    echo '<h3>Migración: FKs e índices</h3>';

    // Índice único ya creado en migración previa para remitos(numero_remito)

    // FKs básicos (si no existen)
    $db->beginTransaction();
    // remitos_detalle -> remitos
    $db->exec("ALTER TABLE remitos_detalle ADD INDEX idx_rd_remito (id_remito)");
    $db->exec("ALTER TABLE remitos_detalle ADD INDEX idx_rd_insumo (id_insumo)");
    // Evitar duplicación del constraint: MySQL no falla si el nombre ya existe? Mejor TRY/CATCH
    try { $db->exec("ALTER TABLE remitos_detalle ADD CONSTRAINT fk_rd_remito FOREIGN KEY (id_remito) REFERENCES remitos(id_remito) ON DELETE CASCADE"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE remitos_detalle ADD CONSTRAINT fk_rd_insumo FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo) ON DELETE RESTRICT"); } catch (Exception $e) {}

    // remitos -> sedes, areas
    $db->exec("ALTER TABLE remitos ADD INDEX idx_r_sede (id_sede)");
    $db->exec("ALTER TABLE remitos ADD INDEX idx_r_area (id_area)");
    try { $db->exec("ALTER TABLE remitos ADD CONSTRAINT fk_r_sede FOREIGN KEY (id_sede) REFERENCES sedes(id_sede) ON DELETE RESTRICT"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE remitos ADD CONSTRAINT fk_r_area FOREIGN KEY (id_area) REFERENCES areas(id_area) ON DELETE RESTRICT"); } catch (Exception $e) {}

    // insumos -> puntos_stock (actual), sedes/areas actuales
    $db->exec("ALTER TABLE insumos ADD INDEX idx_i_estado (estado)");
    $db->exec("ALTER TABLE insumos ADD INDEX idx_i_tipo (tipo_insumo)");
    $db->exec("ALTER TABLE insumos ADD INDEX idx_i_punto (id_punto_stock_actual)");
    $db->exec("ALTER TABLE insumos ADD INDEX idx_i_sede_actual (id_sede_actual)");
    $db->exec("ALTER TABLE insumos ADD INDEX idx_i_area_actual (id_area_asignacion_actual)");
    try { $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_punto FOREIGN KEY (id_punto_stock_actual) REFERENCES puntos_stock(id_punto_stock) ON DELETE SET NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_sede_actual FOREIGN KEY (id_sede_actual) REFERENCES sedes(id_sede) ON DELETE SET NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_area_actual FOREIGN KEY (id_area_asignacion_actual) REFERENCES areas(id_area) ON DELETE SET NULL"); } catch (Exception $e) {}

    $db->commit();
    echo '<p>Índices y llaves foráneas aplicados (cuando corresponda).</p>';
    echo '<p><a href="' . htmlspecialchars(app_base_url() . '/pages/dashboard.php') . '">Volver al Dashboard</a></p>';
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(500);
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

