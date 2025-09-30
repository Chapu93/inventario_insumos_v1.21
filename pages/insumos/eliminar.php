<?php
require_once '../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de insumo no válido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    $db = conectarDB();

    // Traer info
    $stmt = $db->prepare("SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, estado FROM insumos WHERE id_insumo = ?");
    $stmt->execute([$id]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        $_SESSION['mensaje'] = 'El insumo no existe.';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: listar.php');
        exit;
    }

    // No permitir acciones si está asignado
    if ($insumo['estado'] === 'Asignado') {
        $_SESSION['mensaje'] = "No se puede eliminar/dar de baja: el insumo '{$insumo['nombre_insumo']}' está asignado actualmente.";
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: listar.php');
        exit;
    }

    // Soportar reducción de cantidad para tipo "Varios" (mantener comportamiento) y registrar baja si queda en 0
    $accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
    if ($accion === 'reducir' && $insumo['tipo_insumo'] === 'Varios') {
        $cantidadReducir = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 0;
        if ($cantidadReducir < 1) {
            $_SESSION['mensaje'] = 'Cantidad a reducir inválida.';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: listar.php');
            exit;
        }
        $nuevo = max(0, ((int)$insumo['cantidad']) - $cantidadReducir);
        $db->beginTransaction();
        $db->prepare("UPDATE insumos SET cantidad = ?, estado = CASE WHEN ? > 0 THEN 'Disponible' ELSE 'De Baja' END WHERE id_insumo = ?")
           ->execute([$nuevo, $nuevo, $id]);
        if ($nuevo === 0) {
            // Asegurar tabla historial
            try { $db->query("SELECT 1 FROM insumos_bajas LIMIT 1"); }
            catch (Exception $e) {
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
            $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
               ->execute([$id, 'Baja automática por reducción total de stock']);
        }
        $db->commit();
        $_SESSION['mensaje'] = ($nuevo > 0)
            ? "Cantidad reducida en '{$insumo['nombre_insumo']}'. Nuevo stock: {$nuevo}."
            : "Insumo '{$insumo['nombre_insumo']}' dado de baja (stock agotado).";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: listar.php');
        exit;
    }

    // Reemplazar eliminación total por BAJA con historial
    $db->beginTransaction();
    // Asegurar tabla historial
    try { $db->query("SELECT 1 FROM insumos_bajas LIMIT 1"); }
    catch (Exception $e) {
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
    // Marcar insumo de baja, limpiar ubicaciones
    $db->prepare("UPDATE insumos SET estado = 'De Baja', cantidad = 0, id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
       ->execute([$id]);
    $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
       ->execute([$id, 'Baja registrada desde eliminar.php']);
    $db->commit();

    $_SESSION['mensaje'] = "Insumo '{$insumo['nombre_insumo']}' dado de baja correctamente.";
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: listar.php');
    exit;

} catch (Exception $e) {
    if ($db && $db->inTransaction()) { $db->rollBack(); }
    $_SESSION['mensaje'] = 'Error al eliminar insumo: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}