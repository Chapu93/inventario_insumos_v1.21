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

    // Cabecera del remito
    $stmt = $db->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado, r.fecha_devolucion, r.observaciones,
                                 r.nombre_persona_asignada, r.apellido_persona_asignada,
                                 r.motivo_anulacion, r.fecha_anulacion,
                                 ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona
                          FROM remitos r
                          JOIN areas ar ON r.id_area = ar.id_area
                          JOIN sedes s ON r.id_sede = s.id_sede
                          JOIN localidades l ON s.id_localidad = l.id_localidad
                          JOIN zonas z ON l.id_zona = z.id_zona
                          WHERE r.numero_remito = ?
                          LIMIT 1");
    $stmt->execute([$numero]);
    $cab = $stmt->fetch();
    if (!$cab) {
        echo json_encode(['success' => false, 'error' => 'Remito no encontrado']);
        exit;
    }
    $idRemito = (int)$cab['id_remito'];

    // Intentar asegurar columna cantidad_devuelta (solo una vez, tolerante)
    try {
        $db->query("SELECT cantidad_devuelta FROM remitos_detalle LIMIT 1");
        $hasCantidadDev = true;
    } catch (Exception $e) {
        $hasCantidadDev = false;
    }

    $sql = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico,
                   d.cantidad, " . ($hasCantidadDev ? "COALESCE(d.cantidad_devuelta,0)" : "0") . " AS cantidad_devuelta
            FROM remitos_detalle d
            JOIN insumos i ON i.id_insumo = d.id_insumo
            WHERE d.id_remito = ?
            ORDER BY i.nombre_insumo";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idRemito]);
    $items = $stmt->fetchAll();

    echo json_encode(['success' => true, 'cab' => $cab, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

