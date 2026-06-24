<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'eliminar');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si es GET, redirigir con error (no permitir acciones destructivas vía GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acción no permitida. Por favor utilice los botones de la interfaz.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

// Verificar CSRF
if (!verify_csrf()) {
    $_SESSION['mensaje'] = 'Error de seguridad (CSRF). Por favor intente nuevamente.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

// Obtener ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['mensaje'] = 'ID de insumo no válido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

try {
    $db = conectarDB();

    // Traer info
    $stmt = $db->prepare("SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito, estado FROM insumos WHERE id_insumo = ?");
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

    // Soportar reducción de cantidad para tipo "Varios"
    $accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';
    if ($accion === 'reducir' && $insumo['tipo_insumo'] === 'Varios') {
        $cantidadReducir = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
        if ($cantidadReducir < 1) {
            $_SESSION['mensaje'] = 'Cantidad a reducir inválida.';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: listar.php');
            exit;
        }
        $stockOficina = isset($insumo['cantidad_oficina']) ? (int)$insumo['cantidad_oficina'] : (int)$insumo['cantidad'];
        $stockDeposito = isset($insumo['cantidad_deposito']) ? (int)$insumo['cantidad_deposito'] : 0;

        $descontarOficina = min($cantidadReducir, $stockOficina);
        $descontarDeposito = $cantidadReducir - $descontarOficina;

        $nuevoOficina = max(0, $stockOficina - $descontarOficina);
        $nuevoDeposito = max(0, $stockDeposito - $descontarDeposito);
        $nuevoTotal = $nuevoOficina + $nuevoDeposito;

        $db->beginTransaction();
        $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, cantidad_deposito = ?, estado = CASE WHEN ? > 0 THEN 'Disponible' ELSE 'De Baja' END WHERE id_insumo = ?")
           ->execute([$nuevoTotal, $nuevoOficina, $nuevoDeposito, $nuevoTotal, $id]);
        if ($nuevoTotal === 0) {
            // Asegurar tabla historial
            try { $db->query("SELECT 1 FROM insumos_bajas LIMIT 1"); }
            catch (Exception $e) {
                // Crear tabla si no existe (aunque debería existir)
            }
            $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
               ->execute([$id, 'Baja automática por reducción total de stock']);
        }
        $db->commit();
        $_SESSION['mensaje'] = ($nuevoTotal > 0)
            ? "Cantidad reducida en '{$insumo['nombre_insumo']}'. Nuevo stock: {$nuevoTotal}."
            : "Insumo '{$insumo['nombre_insumo']}' dado de baja (stock agotado).";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: listar.php');
        exit;
    }

    // Reemplazar eliminación total por BAJA con historial
    $db->beginTransaction();
    
    // Marcar insumo de baja, limpiar ubicaciones
    $db->prepare("UPDATE insumos SET estado = 'De Baja', cantidad = 0, cantidad_oficina = 0, cantidad_deposito = 0, id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
       ->execute([$id]);
    
    // Registrar en historial
    $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
       ->execute([$id, 'Baja registrada desde eliminar.php']);
       
    $db->commit();

    $_SESSION['mensaje'] = "Insumo '{$insumo['nombre_insumo']}' dado de baja correctamente.";
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: listar.php');
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    $_SESSION['mensaje'] = 'Error al eliminar insumo: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}