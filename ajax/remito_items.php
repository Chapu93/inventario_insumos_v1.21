<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['remito']) || $_GET['remito'] === '') {
        echo json_encode(['success' => false, 'error' => 'Remito requerido']);
        exit;
    }
    $numero = $_GET['remito'];
    $db = conectarDB();

    $stmt = $db->prepare("SELECT r.id_remito FROM remitos r WHERE r.numero_remito = ? LIMIT 1");
    $stmt->execute([$numero]);
    $cab = $stmt->fetch();
    if (!$cab) {
        echo json_encode(['success' => false, 'error' => 'Remito no encontrado']);
        exit;
    }
    $idRemito = (int)$cab['id_remito'];

    $sql = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, d.cantidad, COALESCE(d.cantidad_devuelta,0) AS cantidad_devuelta
            FROM remitos_detalle d
            JOIN insumos i ON i.id_insumo = d.id_insumo
            WHERE d.id_remito = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idRemito]);
    $items = $stmt->fetchAll();

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

