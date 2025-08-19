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

    // Bloque: no eliminar si está asignado
    if ($insumo['estado'] === 'Asignado') {
        $_SESSION['mensaje'] = "No se puede eliminar: el insumo '{$insumo['nombre_insumo']}' está asignado actualmente.";
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: listar.php');
        exit;
    }

    // Soportar reducción de cantidad para tipo "Varios"
    $accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
    if ($accion === 'reducir') {
        if ($insumo['tipo_insumo'] !== 'Varios') {
            $_SESSION['mensaje'] = 'Solo se puede reducir cantidad en insumos de tipo "Varios".';
            $_SESSION['tipo_mensaje'] = 'warning';
            header('Location: listar.php');
            exit;
        }
        $cantidadReducir = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 0;
        if ($cantidadReducir < 1) {
            $_SESSION['mensaje'] = 'Cantidad a reducir inválida.';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: listar.php');
            exit;
        }
        if ($cantidadReducir >= (int)$insumo['cantidad']) {
            // Si reduce igual o más que el stock, eliminar completamente
            $db->beginTransaction();
            switch ($insumo['tipo_insumo']) {
                case 'PC Completa':
                    $db->prepare("DELETE FROM pcs_completas WHERE id_insumo = ?")->execute([$id]);
                    break;
                case 'Notebook':
                    $db->prepare("DELETE FROM notebooks WHERE id_insumo = ?")->execute([$id]);
                    break;
                case 'Impresora':
                    $db->prepare("DELETE FROM impresoras WHERE id_insumo = ?")->execute([$id]);
                    break;
                case 'Monitor':
                    $db->prepare("DELETE FROM monitores WHERE id_insumo = ?")->execute([$id]);
                    break;
                case 'Escaner':
                    $db->prepare("DELETE FROM escaneres WHERE id_insumo = ?")->execute([$id]);
                    break;
            }
            // Eliminar referencias de remitos si existe la tabla detalle
            $existeRemitosDetalle = (bool)$db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'remitos_detalle'")->fetchColumn();
            if ($existeRemitosDetalle) {
                $db->prepare("DELETE FROM remitos_detalle WHERE id_insumo = ?")->execute([$id]);
            }
            $db->prepare("DELETE FROM insumos WHERE id_insumo = ?")->execute([$id]);
            $db->commit();
            $_SESSION['mensaje'] = "Insumo '{$insumo['nombre_insumo']}' eliminado correctamente (reducción total).";
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: listar.php');
            exit;
        } else {
            // Reducir stock y mantener registro
            $nuevo = ((int)$insumo['cantidad']) - $cantidadReducir;
            $db->beginTransaction();
            $db->prepare("UPDATE insumos SET cantidad = ?, estado = 'Disponible' WHERE id_insumo = ?")
               ->execute([$nuevo, $id]);
            $db->commit();
            $_SESSION['mensaje'] = "Cantidad reducida en '{$insumo['nombre_insumo']}'. Nuevo stock: {$nuevo}.";
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: listar.php');
            exit;
        }
    }

    // Eliminación completa (default)
    $db->beginTransaction();

    // Eliminar tablas específicas
    switch ($insumo['tipo_insumo']) {
        case 'PC Completa':
            $db->prepare("DELETE FROM pcs_completas WHERE id_insumo = ?")->execute([$id]);
            break;
        case 'Notebook':
            $db->prepare("DELETE FROM notebooks WHERE id_insumo = ?")->execute([$id]);
            break;
        case 'Impresora':
            $db->prepare("DELETE FROM impresoras WHERE id_insumo = ?")->execute([$id]);
            break;
        case 'Monitor':
            $db->prepare("DELETE FROM monitores WHERE id_insumo = ?")->execute([$id]);
            break;
        case 'Escaner':
            $db->prepare("DELETE FROM escaneres WHERE id_insumo = ?")->execute([$id]);
            break;
    }

    // Limpieza esquema nuevo: detalle de remitos si existe la tabla
    $existeRemitosDetalle = (bool)$db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'remitos_detalle'")->fetchColumn();
    if ($existeRemitosDetalle) {
        $db->prepare("DELETE FROM remitos_detalle WHERE id_insumo = ?")->execute([$id]);
    }

    // Eliminar insumo
    $db->prepare("DELETE FROM insumos WHERE id_insumo = ?")->execute([$id]);

    $db->commit();

    $_SESSION['mensaje'] = "Insumo '{$insumo['nombre_insumo']}' eliminado correctamente.";
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