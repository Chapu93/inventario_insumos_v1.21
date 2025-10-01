<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!verify_csrf()) {
    echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data || empty($data['id_insumo']) || !isset($data['observacion'])) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    $idInsumo = (int)$data['id_insumo'];
    $observacion = trim((string)$data['observacion']);
    if ($idInsumo <= 0 || strlen($observacion) < 3) {
        echo json_encode(['success' => false, 'error' => 'Parámetros insuficientes']);
        exit;
    }

    $db = conectarDB();

    // Verificar que no esté asignado
    $stmt = $db->prepare("SELECT estado FROM insumos WHERE id_insumo = ? FOR UPDATE");
    $stmt->execute([$idInsumo]);
    $ins = $stmt->fetch();
    if (!$ins) {
        echo json_encode(['success' => false, 'error' => 'Insumo no encontrado']);
        exit;
    }
    if ($ins['estado'] === 'Asignado') {
        echo json_encode(['success' => false, 'error' => 'No se puede dar de baja un insumo asignado']);
        exit;
    }

    $db->beginTransaction();

    // Cambiar estado a De Baja y limpiar ubicaciones actuales
    $db->prepare("UPDATE insumos SET estado = 'De Baja', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
       ->execute([$idInsumo]);

    // Asegurar tabla de historial de bajas
    try {
        $db->query("SELECT 1 FROM insumos_bajas LIMIT 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS insumos_bajas (
            id_baja INT NOT NULL AUTO_INCREMENT,
            id_insumo INT NOT NULL,
            fecha_baja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            observacion VARCHAR(255) NOT NULL,
            PRIMARY KEY (id_baja),
            KEY idx_ib_insumo (id_insumo),
            CONSTRAINT fk_ib_insumo FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // Registrar observación de baja
    $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
       ->execute([$idInsumo, $observacion]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

