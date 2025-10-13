<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = conectarDB();
    echo '<h3>Migración: FKs e índices</h3>';

    $schema = $db->query("SELECT DATABASE()")->fetchColumn();
    $hasIndex = function(PDO $db, string $schema, string $table, string $index): bool {
        $q = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?");
        $q->execute([$schema, $table, $index]);
        return ((int)$q->fetchColumn()) > 0;
    };
    $hasFk = function(PDO $db, string $schema, string $table, string $fkName): bool {
        $q = $db->prepare("SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'");
        $q->execute([$schema, $table, $fkName]);
        return ((int)$q->fetchColumn()) > 0;
    };

    // remitos_detalle -> remitos / insumos (índices y FKs)
    if (!$hasIndex($db, $schema, 'remitos_detalle', 'idx_rd_remito')) {
        $db->exec("ALTER TABLE remitos_detalle ADD INDEX idx_rd_remito (id_remito)");
    }
    if (!$hasIndex($db, $schema, 'remitos_detalle', 'idx_rd_insumo')) {
        $db->exec("ALTER TABLE remitos_detalle ADD INDEX idx_rd_insumo (id_insumo)");
    }
    if (!$hasFk($db, $schema, 'remitos_detalle', 'fk_rd_remito')) {
        $db->exec("ALTER TABLE remitos_detalle ADD CONSTRAINT fk_rd_remito FOREIGN KEY (id_remito) REFERENCES remitos(id_remito) ON DELETE CASCADE");
    }
    if (!$hasFk($db, $schema, 'remitos_detalle', 'fk_rd_insumo')) {
        $db->exec("ALTER TABLE remitos_detalle ADD CONSTRAINT fk_rd_insumo FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo) ON DELETE RESTRICT");
    }

    // remitos -> sedes, areas (índices y FKs)
    if (!$hasIndex($db, $schema, 'remitos', 'idx_r_sede')) {
        $db->exec("ALTER TABLE remitos ADD INDEX idx_r_sede (id_sede)");
    }
    if (!$hasIndex($db, $schema, 'remitos', 'idx_r_area')) {
        $db->exec("ALTER TABLE remitos ADD INDEX idx_r_area (id_area)");
    }
    if (!$hasFk($db, $schema, 'remitos', 'fk_r_sede')) {
        $db->exec("ALTER TABLE remitos ADD CONSTRAINT fk_r_sede FOREIGN KEY (id_sede) REFERENCES sedes(id_sede) ON DELETE RESTRICT");
    }
    if (!$hasFk($db, $schema, 'remitos', 'fk_r_area')) {
        $db->exec("ALTER TABLE remitos ADD CONSTRAINT fk_r_area FOREIGN KEY (id_area) REFERENCES areas(id_area) ON DELETE RESTRICT");
    }

    // insumos -> puntos_stock (actual), sedes/areas actuales (índices y FKs)
    if (!$hasIndex($db, $schema, 'insumos', 'idx_i_estado')) {
        $db->exec("ALTER TABLE insumos ADD INDEX idx_i_estado (estado)");
    }
    if (!$hasIndex($db, $schema, 'insumos', 'idx_i_tipo')) {
        $db->exec("ALTER TABLE insumos ADD INDEX idx_i_tipo (tipo_insumo)");
    }
    if (!$hasIndex($db, $schema, 'insumos', 'idx_i_punto')) {
        $db->exec("ALTER TABLE insumos ADD INDEX idx_i_punto (id_punto_stock_actual)");
    }
    if (!$hasIndex($db, $schema, 'insumos', 'idx_i_sede_actual')) {
        $db->exec("ALTER TABLE insumos ADD INDEX idx_i_sede_actual (id_sede_actual)");
    }
    if (!$hasIndex($db, $schema, 'insumos', 'idx_i_area_actual')) {
        $db->exec("ALTER TABLE insumos ADD INDEX idx_i_area_actual (id_area_asignacion_actual)");
    }
    if (!$hasFk($db, $schema, 'insumos', 'fk_i_punto')) {
        $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_punto FOREIGN KEY (id_punto_stock_actual) REFERENCES puntos_stock(id_punto_stock) ON DELETE SET NULL");
    }
    if (!$hasFk($db, $schema, 'insumos', 'fk_i_sede_actual')) {
        $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_sede_actual FOREIGN KEY (id_sede_actual) REFERENCES sedes(id_sede) ON DELETE SET NULL");
    }
    if (!$hasFk($db, $schema, 'insumos', 'fk_i_area_actual')) {
        $db->exec("ALTER TABLE insumos ADD CONSTRAINT fk_i_area_actual FOREIGN KEY (id_area_asignacion_actual) REFERENCES areas(id_area) ON DELETE SET NULL");
    }

    echo '<p>Índices y llaves foráneas aplicados (solo los faltantes).</p>';
    echo '<p><a href="' . htmlspecialchars(app_base_url() . '/pages/dashboard.php') . '">Volver al Dashboard</a></p>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

