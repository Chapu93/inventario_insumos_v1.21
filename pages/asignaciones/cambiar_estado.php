<?php
require_once '../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$remito = isset($_GET['remito']) ? trim($_GET['remito']) : '';
$nuevoEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

if ($remito === '' || $nuevoEstado === '') {
    $_SESSION['mensaje'] = 'Parámetros insuficientes.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

try {
    $db = conectarDB();

    if (strcasecmp($nuevoEstado, 'Devuelta') !== 0) {
        $_SESSION['mensaje'] = 'Estado no soportado.';
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: listar.php');
        exit;
    }

    $stmt = $db->prepare("SELECT id_remito FROM remitos WHERE numero_remito = ? LIMIT 1");
    $stmt->execute([$remito]);
    $cab = $stmt->fetch();
    if (!$cab) {
        $_SESSION['mensaje'] = 'Remito no encontrado.';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: listar.php');
        exit;
    }

    $idRemito = (int)$cab['id_remito'];

    $sql = "SELECT d.id_insumo, d.cantidad, i.tipo_insumo, i.cantidad AS stock_actual
            FROM remitos_detalle d
            JOIN insumos i ON i.id_insumo = d.id_insumo
            WHERE d.id_remito = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idRemito]);
    $detalles = $stmt->fetchAll();
    if (empty($detalles)) {
        $_SESSION['mensaje'] = 'El remito no tiene insumos para devolver.';
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: listar.php');
        exit;
    }

    $db->beginTransaction();
    foreach ($detalles as $row) {
        $idInsumo = (int)$row['id_insumo'];
        $cant = max(1, (int)$row['cantidad']);
        $tipo = $row['tipo_insumo'];

        if ($tipo === 'Varios') {
            $nuevoStock = ((int)$row['stock_actual']) + $cant;
            $db->prepare("UPDATE insumos SET cantidad = ?, estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$nuevoStock, $idInsumo]);
        } else {
            $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$idInsumo]);
        }
    }
    // Marcar cabecera como Devuelta
    $db->prepare("UPDATE remitos SET estado = 'Devuelta', fecha_devolucion = CURDATE() WHERE numero_remito = ?")
       ->execute([$remito]);

    $db->commit();

    $_SESSION['mensaje'] = "Remito $remito marcado como Devuelto. Estados/stock actualizados.";
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: listar.php');
    exit;

} catch (Exception $e) {
    if ($db && $db->inTransaction()) { $db->rollBack(); }
    $_SESSION['mensaje'] = 'Error al cambiar estado: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}