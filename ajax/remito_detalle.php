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

    // Cabecera del remito
    $stmt = $db->prepare("SELECT r.id_remito, r.numero_remito, r.fecha_asignacion, r.estado, r.fecha_devolucion, r.observaciones, r.nota_solicitud,
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
        json_error('Remito no encontrado', 404);
    }
    $idRemito = (int) $cab['id_remito'];

    // Intentar asegurar columna cantidad_devuelta (solo una vez, tolerante)
    try {
        $db->query("SELECT cantidad_devuelta FROM remitos_detalle LIMIT 1");
        $hasCantidadDev = true;
    } catch (Exception $e) {
        $hasCantidadDev = false;
    }

    $selectCantidadDevuelta = $hasCantidadDev ? 'COALESCE(d.cantidad_devuelta,0)' : '0';
    $sql = "SELECT i.id_insumo,
                   i.nombre_insumo,
                   i.tipo_insumo,
                   i.numero_serie,
                   i.id_fisico,
                   d.cantidad,
                   {$selectCantidadDevuelta} AS cantidad_devuelta,
                   pc.sist_op AS pc_sist_op,
                   nb.marca AS nb_marca,
                   nb.modelo AS nb_modelo,
                   imp.marca AS imp_marca,
                   imp.modelo AS imp_modelo,
                   mon.marca AS mon_marca,
                   mon.modelo AS mon_modelo,
                   mon.pulgadas AS mon_pulgadas,
                   mon.conexion AS mon_conexion,
                   esc.marca AS esc_marca,
                   esc.modelo AS esc_modelo,
                   pc.procesador AS pc_procesador,
                   pc.ram_gb AS pc_ram,
                   pc.almacenamiento_gb AS pc_disco,
                   pc.mother AS pc_mother,
                   nb.procesador AS nb_procesador,
                   nb.ram_gb AS nb_ram,
                   nb.almacenamiento_gb AS nb_disco
            FROM remitos_detalle d
            JOIN insumos i ON i.id_insumo = d.id_insumo
            LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
            LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
            LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
            LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
            LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
            WHERE d.id_remito = ?
            ORDER BY i.nombre_insumo";
    $stmt = $db->prepare($sql);
    $stmt->execute([$idRemito]);
    $items = $stmt->fetchAll();

    Logger::debug('Detalle de remito cargado', [
        'numero_remito' => $numero,
        'estado' => $cab['estado'],
        'items_count' => count($items)
    ]);

    json_success(['cab' => $cab, 'items' => $items]);

} catch (Exception $e) {
    Logger::error('Error al cargar detalle de remito', [
        'mensaje' => $e->getMessage(),
        'numero_remito' => $numero ?? ''
    ]);
    json_error($e->getMessage(), 500);
}
?>