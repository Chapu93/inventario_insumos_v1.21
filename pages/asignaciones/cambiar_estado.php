<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('asignaciones', 'editar');

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

    // Solo permitir 'Devuelta'. Evitar uso para devolver stock si hay parciales
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

    $sql = "SELECT d.id_insumo, d.cantidad, COALESCE(d.cantidad_devuelta,0) AS cantidad_devuelta, i.tipo_insumo, i.cantidad AS stock_actual, i.cantidad_oficina, i.cantidad_deposito
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

    // Devolver automáticamente todos los pendientes y dejar registro
    $db->beginTransaction();
    foreach ($detalles as $row) {
        $idInsumo = (int)$row['id_insumo'];
        $asignados = (int)$row['cantidad'];
        $devueltos = (int)$row['cantidad_devuelta'];
        $pend = max(0, $asignados - $devueltos);
        if ($pend <= 0) { continue; }

        if ($row['tipo_insumo'] === 'Varios') {
            $stockOficina = isset($row['cantidad_oficina']) ? (int)$row['cantidad_oficina'] : (int)$row['stock_actual'];
            $stockDeposito = isset($row['cantidad_deposito']) ? (int)$row['cantidad_deposito'] : 0;
            
            $nuevoStockTotal = $stockOficina + $stockDeposito + $pend;
            $nuevoStockOficina = $stockOficina + $pend;
            $nuevoStockDeposito = $stockDeposito;
            
            $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, cantidad_deposito = ?, estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$nuevoStockTotal, $nuevoStockOficina, $nuevoStockDeposito, $idInsumo]);
        } else {
            $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$idInsumo]);
        }

        // Registrar devolución completa en el detalle
        $db->prepare("UPDATE remitos_detalle SET cantidad_devuelta = cantidad WHERE id_remito = ? AND id_insumo = ?")
           ->execute([$idRemito, $idInsumo]);
    }

    // Marcar remito como Devuelta
    $db->prepare("UPDATE remitos SET estado = 'Devuelta', fecha_devolucion = CURDATE() WHERE numero_remito = ?")
       ->execute([$remito]);

    $db->commit();

    $_SESSION['mensaje'] = "Remito $remito marcado como Devuelto. Se devolvieron los insumos pendientes.";
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