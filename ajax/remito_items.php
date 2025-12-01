<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'ver')) {
    json_error('No tienes permisos para ver asignaciones', 403);
}

try {
    if (!isset($_GET['remito']) || $_GET['remito'] === '') {
        json_error('Remito requerido', 400);
    }
    
    $numero = $_GET['remito'];
    $db = conectarDB();

    $stmt = $db->prepare("SELECT r.id_remito FROM remitos r WHERE r.numero_remito = ? LIMIT 1");
    $stmt->execute([$numero]);
    $cab = $stmt->fetch();
    
    if (!$cab) {
        json_error('Remito no encontrado', 404);
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

    Logger::debug('Items de remito cargados', ['numero_remito' => $numero, 'count' => count($items)]);
    
    json_success(['items' => $items]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar items de remito', [
        'mensaje' => $e->getMessage(),
        'numero_remito' => $numero ?? ''
    ]);
    json_error($e->getMessage(), 500);
}
?>
