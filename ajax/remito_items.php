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

    $sql = "SELECT i.id_insumo,
                   i.nombre_insumo,
                   i.tipo_insumo,
                   i.numero_serie,
                   i.id_fisico,
                   d.cantidad,
                   COALESCE(d.cantidad_devuelta,0) AS cantidad_devuelta,
                   pc.sist_op AS pc_sist_op,
                   nb.marca AS nb_marca,
                   nb.modelo AS nb_modelo,
                   imp.marca AS imp_marca,
                   imp.modelo AS imp_modelo,
                   mon.marca AS mon_marca,
                   mon.modelo AS mon_modelo,
                   esc.marca AS esc_marca,
                   esc.modelo AS esc_modelo
            FROM remitos_detalle d
            JOIN insumos i ON i.id_insumo = d.id_insumo
            LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
            LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
            LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
            LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
            LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
            WHERE d.id_remito = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idRemito]);
    $items = $stmt->fetchAll();

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

