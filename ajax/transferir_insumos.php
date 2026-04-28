<?php
/**
 * Endpoint para transferencia masiva de insumos entre personas
 *
 * Recibe una lista de insumos (con sus remitos de origen) y los transfiere
 * a una nueva persona/sede/área en una sola operación atómica.
 *
 * Flujo por insumo:
 *   1. Incrementa cantidad_devuelta en remito_origen → historiza la devolución
 *   2. Si remito_origen queda sin pendientes → marca como 'Devuelta'
 *   3. Crea un único remito nuevo para el destino con todos los insumos
 *   4. Actualiza insumos.estado / id_sede_actual / id_area_actual
 *   5. Registra en auditoría como 'transferencia_masiva'
 */
require_once '../includes/config.php';

// ── Autenticación y permisos ──────────────────────────────────────────────────
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}
if (!tienePermiso('asignaciones', 'crear')) {
    json_error('Sin permiso para crear asignaciones', 403);
}
if (!tienePermiso('asignaciones', 'devolver')) {
    json_error('Sin permiso para devolver insumos', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}
if (!verify_csrf()) {
    json_error('Token CSRF inválido', 403);
}

// ── Leer y validar payload ────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    json_error('Payload JSON inválido', 400);
}

// Destino
$idSedeDestino  = (int)($data['id_sede_destino']  ?? 0);
$idAreaDestino  = (int)($data['id_area_destino']  ?? 0);
$nombreDestino  = trim($data['nombre_destino']     ?? '');
$apellidoDestino= trim($data['apellido_destino']   ?? '');
$observaciones  = trim($data['observaciones']      ?? '');

// Persona origen (para auditoría)
$nombreOrigen   = trim($data['nombre_origen']      ?? '');
$apellidoOrigen = trim($data['apellido_origen']    ?? '');

// Lista de insumos a transferir
// Cada item: { id_insumo, id_remito, cantidad }
$items = $data['items'] ?? [];

if (!$idSedeDestino || !$idAreaDestino || !$nombreDestino || !$apellidoDestino) {
    json_error('Datos del destino incompletos', 400);
}
if (empty($items) || !is_array($items)) {
    json_error('Debe seleccionar al menos un insumo', 400);
}

// ── Proceso principal ─────────────────────────────────────────────────────────
try {
    $db = conectarDB();

    // Asegurar columna cantidad_devuelta (compatibilidad)
    try {
        $db->query("SELECT cantidad_devuelta FROM remitos_detalle LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE remitos_detalle ADD COLUMN cantidad_devuelta INT NOT NULL DEFAULT 0");
    }

    // Verificar que la sede y área destino existen
    $stmtSede = $db->prepare("SELECT id_sede FROM sedes WHERE id_sede = ? LIMIT 1");
    $stmtSede->execute([$idSedeDestino]);
    if (!$stmtSede->fetch()) {
        json_error('Sede destino no encontrada', 404);
    }

    $stmtArea = $db->prepare("SELECT id_area FROM areas WHERE id_area = ? LIMIT 1");
    $stmtArea->execute([$idAreaDestino]);
    if (!$stmtArea->fetch()) {
        json_error('Área destino no encontrada', 404);
    }

    $db->beginTransaction();

    // ── Generar número de remito destino ──────────────────────────────────────
    $numeroRemitoNuevo = generarNumeroRemito($db);

    // ── Crear cabecera del remito destino ─────────────────────────────────────
    $obsTexto = $observaciones ?: "Transferencia desde {$nombreOrigen} {$apellidoOrigen}";
    $stmtCab = $db->prepare(
        "INSERT INTO remitos
            (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada,
             fecha_asignacion, observaciones)
         VALUES (?, ?, ?, ?, ?, CURDATE(), ?)"
    );
    $stmtCab->execute([
        $numeroRemitoNuevo,
        $idSedeDestino,
        $idAreaDestino,
        $nombreDestino,
        $apellidoDestino,
        $obsTexto,
    ]);
    $idRemitoNuevo = (int)$db->lastInsertId();

    // ── Procesar cada insumo ──────────────────────────────────────────────────
    $insumosTransferidos = [];   // Para auditoría
    $remitosAfectados    = [];   // id_remito => true (para verificar cierre)

    foreach ($items as $item) {
        $idInsumo = (int)($item['id_insumo'] ?? 0);
        $idRemito = (int)($item['id_remito'] ?? 0);
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));

        if ($idInsumo <= 0 || $idRemito <= 0) { continue; }

        // Bloquear y leer el detalle del remito origen
        $stmtDet = $db->prepare(
            "SELECT rd.cantidad, COALESCE(rd.cantidad_devuelta, 0) AS devuelta, i.tipo_insumo
             FROM remitos_detalle rd
             JOIN insumos i ON i.id_insumo = rd.id_insumo
             WHERE rd.id_remito = ? AND rd.id_insumo = ?
             FOR UPDATE"
        );
        $stmtDet->execute([$idRemito, $idInsumo]);
        $det = $stmtDet->fetch();
        if (!$det) { continue; }

        $asignados  = (int)$det['cantidad'];
        $devueltos  = (int)$det['devuelta'];
        $pendiente  = max(0, $asignados - $devueltos);
        $aTransferir = min($cantidad, $pendiente);
        if ($aTransferir <= 0) { continue; }

        // 1. Registrar devolución en el remito origen
        $db->prepare(
            "UPDATE remitos_detalle
             SET cantidad_devuelta = LEAST(cantidad, COALESCE(cantidad_devuelta, 0) + ?)
             WHERE id_remito = ? AND id_insumo = ?"
        )->execute([$aTransferir, $idRemito, $idInsumo]);

        $remitosAfectados[$idRemito] = true;

        // 2. Insertar insumo en el remito destino
        // Si ya fue insertado (insumo de tipo 'Varios' repetido), sumar cantidad
        $stmtCheck = $db->prepare(
            "SELECT 1 FROM remitos_detalle WHERE id_remito = ? AND id_insumo = ? LIMIT 1"
        );
        $stmtCheck->execute([$idRemitoNuevo, $idInsumo]);
        if ($stmtCheck->fetch()) {
            $db->prepare(
                "UPDATE remitos_detalle SET cantidad = cantidad + ? WHERE id_remito = ? AND id_insumo = ?"
            )->execute([$aTransferir, $idRemitoNuevo, $idInsumo]);
        } else {
            $db->prepare(
                "INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?, ?, ?)"
            )->execute([$idRemitoNuevo, $idInsumo, $aTransferir]);
        }

        // 3. Actualizar estado del insumo
        if ($det['tipo_insumo'] === 'Varios') {
            // Para tipo Varios, el estado se mantiene Asignado, solo cambia destino
            $db->prepare(
                "UPDATE insumos
                 SET estado = 'Asignado',
                     id_sede_actual = ?,
                     id_area_asignacion_actual = ?,
                     id_punto_stock_actual = NULL
                 WHERE id_insumo = ?"
            )->execute([$idSedeDestino, $idAreaDestino, $idInsumo]);
        } else {
            // Para unitarios (PC, Notebook, Monitor, etc.)
            $db->prepare(
                "UPDATE insumos
                 SET estado = 'Asignado',
                     id_sede_actual = ?,
                     id_area_asignacion_actual = ?,
                     id_punto_stock_actual = NULL,
                     es_nuevo = IF(es_nuevo = 1, 0, es_nuevo)
                 WHERE id_insumo = ?"
            )->execute([$idSedeDestino, $idAreaDestino, $idInsumo]);
        }

        $insumosTransferidos[] = [
            'id_insumo'     => $idInsumo,
            'id_remito_org' => $idRemito,
            'cantidad'      => $aTransferir,
        ];
    }

    // Si no se transfirió nada (todos sin pendiente), hacer rollback limpio
    if (empty($insumosTransferidos)) {
        $db->rollBack();
        json_error('No hay insumos con pendientes para transferir', 400);
    }

    // ── Verificar y cerrar remitos origen que quedaron sin pendientes ─────────
    foreach (array_keys($remitosAfectados) as $idRem) {
        $stmtRes = $db->prepare(
            "SELECT SUM(GREATEST(cantidad - COALESCE(cantidad_devuelta, 0), 0)) AS restantes
             FROM remitos_detalle WHERE id_remito = ?"
        );
        $stmtRes->execute([$idRem]);
        $restantes = (int)$stmtRes->fetch()['restantes'];
        if ($restantes === 0) {
            $db->prepare(
                "UPDATE remitos SET estado = 'Devuelta', fecha_devolucion = CURDATE() WHERE id_remito = ?"
            )->execute([$idRem]);
        }
    }

    $db->commit();

    // ── Auditoría ─────────────────────────────────────────────────────────────
    registrarAuditoria(
        'transferencia_masiva',
        'asignaciones',
        "Transferencia de " . count($insumosTransferidos) . " insumo(s) de "
            . "{$nombreOrigen} {$apellidoOrigen} a {$nombreDestino} {$apellidoDestino} "
            . "- Remito generado: {$numeroRemitoNuevo}",
        'remito',
        $idRemitoNuevo,
        ['origen' => "{$nombreOrigen} {$apellidoOrigen}"],
        [
            'destino'          => "{$nombreDestino} {$apellidoDestino}",
            'numero_remito'    => $numeroRemitoNuevo,
            'insumos'          => $insumosTransferidos,
            'remitos_origen'   => array_keys($remitosAfectados),
        ]
    );

    json_success([
        'numero_remito'    => $numeroRemitoNuevo,
        'id_remito'        => $idRemitoNuevo,
        'total_transferidos'=> count($insumosTransferidos),
        'mensaje'          => 'Transferencia realizada correctamente. Remito: ' . $numeroRemitoNuevo,
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    Logger::error('Error en transferir_insumos', ['error' => $e->getMessage()]);
    registrarAuditoria(
        'transferencia_masiva',
        'asignaciones',
        "Error en transferencia: {$nombreOrigen} {$apellidoOrigen} → {$nombreDestino} {$apellidoDestino}",
        null, null, null, null,
        'error',
        $e->getMessage()
    );
    json_error($e->getMessage(), 500);
}
