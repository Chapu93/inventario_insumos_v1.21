<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'devolver')) {
    json_error('No tienes permisos para devolver insumos', 403);
}

if (!verify_csrf()) {
    json_error('CSRF inválido', 403);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data || !isset($data['remito']) || !is_array($data['items'])) {
        json_error('Datos inválidos', 400);
        exit;
    }

    $numero = $data['remito'];
    $items = $data['items'];
    $accionDestino = trim((string)($data['accion_destino'] ?? 'disponible'));
    $motivoBaja = trim((string)($data['motivo_baja'] ?? ''));
    $personaEntrega = trim((string)($data['persona_entrega'] ?? ''));
    $observacionesDevolucion = trim((string)($data['observaciones'] ?? ($data['observaciones_devolucion'] ?? '')));

    if ($accionDestino === 'baja') {
        if (!tienePermiso('insumos', 'baja')) {
            json_error('No tienes permisos para dar de baja insumos', 403);
            exit;
        }
        if (empty($motivoBaja)) {
            json_error('Debe especificar un motivo para la baja de los insumos', 400);
            exit;
        }
        if (empty($observacionesDevolucion)) {
            $observacionesDevolucion = 'Baja: ' . $motivoBaja;
        }
    }

    $db = conectarDB();

    // Asegurar columna cantidad_devuelta para historizar devoluciones
    try {
        $db->query("SELECT cantidad_devuelta FROM remitos_detalle LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE remitos_detalle ADD COLUMN cantidad_devuelta INT NOT NULL DEFAULT 0");
    }

    // Si la acción es baja, asegurar tabla insumos_bajas
    if ($accionDestino === 'baja') {
        try {
            $db->query("SELECT 1 FROM insumos_bajas LIMIT 1");
        } catch (Exception $e) {
            $db->exec("CREATE TABLE IF NOT EXISTS insumos_bajas (
                id_baja INT NOT NULL AUTO_INCREMENT,
                id_insumo INT NOT NULL,
                fecha_baja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                observacion VARCHAR(255) NOT NULL,
                cantidad INT NOT NULL DEFAULT 1,
                PRIMARY KEY (id_baja),
                KEY idx_ib_insumo (id_insumo),
                CONSTRAINT fk_ib_insumo FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        try {
            $db->query("SELECT cantidad FROM insumos_bajas LIMIT 1");
        } catch (Exception $e) {
            try {
                $db->exec("ALTER TABLE insumos_bajas ADD COLUMN cantidad INT NOT NULL DEFAULT 1 AFTER observacion");
            } catch (Exception $e2) {}
        }
    }

    $stmt = $db->prepare("SELECT id_remito FROM remitos WHERE numero_remito = ? LIMIT 1");
    $stmt->execute([$numero]);
    $cab = $stmt->fetch();
    if (!$cab) {
        json_error('Remito no encontrado', 404);
        exit;
    }
    $idRemito = (int)$cab['id_remito'];

    $db->beginTransaction();

    $devolverList = [];
    $idRemitoDevolucion = null;
    $numeroDevolucion = null;

    foreach ($items as $it) {
        $idInsumo = (int)($it['id_insumo'] ?? 0);
        $cantidadDev = isset($it['cantidad']) ? (int)$it['cantidad'] : 1;
        $cantidadDev = max(1, $cantidadDev); // Mínimo 1
        if ($idInsumo <= 0) { continue; }

        // Obtener detalle actual del remito para ese insumo
        $stmt = $db->prepare("SELECT cantidad, COALESCE(cantidad_devuelta, 0) AS cantidad_devuelta FROM remitos_detalle WHERE id_remito = ? AND id_insumo = ? FOR UPDATE");
        $stmt->execute([$idRemito, $idInsumo]);
        $det = $stmt->fetch();
        if (!$det) { continue; }

        $cantPendiente = max(0, (int)$det['cantidad'] - (int)$det['cantidad_devuelta']);
        $aDevolver = min($cantidadDev, $cantPendiente);
        if ($aDevolver <= 0) { continue; }

        // Recuperar info del insumo (incluir campos de stock dual)
        $stmt = $db->prepare("SELECT tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito FROM insumos WHERE id_insumo = ? FOR UPDATE");
        $stmt->execute([$idInsumo]);
        $ins = $stmt->fetch();
        if (!$ins) { continue; }

        if ($ins['tipo_insumo'] === 'Varios') {
            $stockOficina = (int)($ins['cantidad_oficina'] ?? 0);
            $stockDeposito = (int)($ins['cantidad_deposito'] ?? 0);
            
            if ($accionDestino === 'baja') {
                // Al dar de baja directamente en devolución, el stock físico no reingresa a oficina
                $nuevoStockTotal = $stockOficina + $stockDeposito;
                $nuevoEstado = ($nuevoStockTotal > 0) ? 'Disponible' : 'De Baja';
                
                $db->prepare("UPDATE insumos SET estado = ?, id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
                   ->execute([$nuevoEstado, $idInsumo]);

                // Registrar en bajas
                $obsBaja = "[Remito #{$numero}] " . $motivoBaja;
                $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion, cantidad) VALUES (?, ?, ?)")
                   ->execute([$idInsumo, $obsBaja, $aDevolver]);

                // Registrar movimiento de baja
                $db->prepare("INSERT INTO insumos_movimientos_stock 
                              (id_insumo, tipo_movimiento, cantidad_movida, ubicacion_origen, ubicacion_destino,
                               cantidad_oficina_antes, cantidad_deposito_antes, cantidad_oficina_despues, cantidad_deposito_despues, 
                               observacion, fecha_movimiento) 
                              VALUES (?, 'baja', ?, 'asignacion', 'baja', ?, ?, ?, ?, ?, NOW())")
                   ->execute([
                       $idInsumo, $aDevolver, 
                       $stockOficina, $stockDeposito, 
                       $stockOficina, $stockDeposito, 
                       $obsBaja
                   ]);

                registrarAuditoria(
                    'baja_insumo',
                    'insumos',
                    "Baja en devolución de Remito #{$numero} (ID: {$idInsumo}): {$motivoBaja} - Cantidad: {$aDevolver}",
                    'insumo',
                    $idInsumo,
                    ['estado' => 'Asignado'],
                    ['estado' => $nuevoEstado, 'cantidad_baja' => $aDevolver]
                );
            } else {
                $nuevoStockOficina = $stockOficina + $aDevolver;
                $nuevoStockTotal = $nuevoStockOficina + $stockDeposito;
                
                $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, cantidad_deposito = ?, estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
                   ->execute([$nuevoStockTotal, $nuevoStockOficina, $stockDeposito, $idInsumo]);

                // Registrar el movimiento de ingreso a oficina por devolución
                $db->prepare("INSERT INTO insumos_movimientos_stock 
                              (id_insumo, tipo_movimiento, cantidad_movida, ubicacion_origen, ubicacion_destino,
                               cantidad_oficina_antes, cantidad_deposito_antes, cantidad_oficina_despues, cantidad_deposito_despues, 
                               observacion, fecha_movimiento) 
                              VALUES (?, 'devolucion', ?, 'asignacion', 'oficina', ?, ?, ?, ?, ?, NOW())")
                   ->execute([
                       $idInsumo, $aDevolver, 
                       $stockOficina, $stockDeposito, 
                       $nuevoStockOficina, $stockDeposito, 
                       "Devolución de Remito #{$numero}"
                   ]);
            }
        } else {
            // Para unitarios (PC, Notebook, Monitor, Impresora, Escaner, etc.)
            if ($accionDestino === 'baja') {
                $db->prepare("UPDATE insumos SET estado = 'De Baja', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
                   ->execute([$idInsumo]);

                $obsBaja = "[Remito #{$numero}] " . $motivoBaja;
                $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion, cantidad) VALUES (?, ?, 1)")
                   ->execute([$idInsumo, $obsBaja]);

                registrarAuditoria(
                    'baja_insumo',
                    'insumos',
                    "Baja en devolución de Remito #{$numero} (ID: {$idInsumo}): {$motivoBaja}",
                    'insumo',
                    $idInsumo,
                    ['estado' => 'Asignado'],
                    ['estado' => 'De Baja']
                );
            } else {
                $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
                   ->execute([$idInsumo]);
            }
        }

        // Actualizar historial: incrementar cantidad_devuelta, NO borrar filas
        $db->prepare("UPDATE remitos_detalle SET cantidad_devuelta = LEAST(cantidad, cantidad_devuelta + ?) WHERE id_remito = ? AND id_insumo = ?")
           ->execute([$aDevolver, $idRemito, $idInsumo]);

        // Crear cabecera de remito de devolución si aún no se creó
        if ($idRemitoDevolucion === null) {
            $numeroDevolucion = generarNumeroRemitoDevolucion($db);
            $stmtCabDev = $db->prepare("INSERT INTO remitos_devolucion 
                (numero_devolucion, id_remito_origen, fecha_devolucion, id_usuario_receptor, persona_entrega, observaciones) 
                VALUES (?, ?, NOW(), ?, ?, ?)");
            $stmtCabDev->execute([
                $numeroDevolucion,
                $idRemito,
                obtenerUsuarioId(),
                $personaEntrega ?: null,
                $observacionesDevolucion ?: null
            ]);
            $idRemitoDevolucion = (int)$db->lastInsertId();
        }

        // Insertar ítem en remitos_devolucion_detalle
        $estadoFisico = trim((string)($it['estado_fisico'] ?? ''));
        $stmtDetDev = $db->prepare("INSERT INTO remitos_devolucion_detalle 
            (id_remito_devolucion, id_insumo, cantidad, destino, motivo_baja, estado_fisico) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmtDetDev->execute([
            $idRemitoDevolucion,
            $idInsumo,
            $aDevolver,
            $accionDestino,
            $accionDestino === 'baja' ? $motivoBaja : null,
            $estadoFisico ?: null
        ]);
           
        $devolverList[] = ['id' => $idInsumo, 'cantidad' => $aDevolver, 'destino' => $accionDestino];
    }

    if (empty($devolverList)) {
        if ($db->inTransaction()) { $db->rollBack(); }
        json_error('No se seleccionaron ítems válidos para devolver o ya fueron devueltos previamente', 400);
        exit;
    }

    // Si ya no quedan items en el remito, marcar estado Devuelta y fecha_devolucion
    $restantes = $db->prepare("SELECT SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0),0)) AS restantes FROM remitos_detalle d WHERE d.id_remito = ?");
    $restantes->execute([$idRemito]);
    $c = (int)$restantes->fetch()['restantes'];
    if ($c === 0) {
        $db->prepare("UPDATE remitos SET estado = 'Devuelta', fecha_devolucion = CURDATE() WHERE id_remito = ?")
           ->execute([$idRemito]);
    }

    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        'devolver_insumos',
        'asignaciones',
        "Devolución de insumos (Destino: {$accionDestino}) - Remito: {$numero} - Remito DEV: {$numeroDevolucion}",
        'asignacion',
        $idRemito,
        null,
        ['devueltos' => $devolverList, 'restantes' => $c, 'destino' => $accionDestino, 'numero_devolucion' => $numeroDevolucion]
    );
    
    json_success([
        'message' => 'Devolución procesada correctamente',
        'numero_devolucion' => $numeroDevolucion,
        'id_remito_devolucion' => $idRemitoDevolucion,
        'url_pdf' => $numeroDevolucion ? (app_base_url() . '/pages/reportes/remito_devolucion_pdf.php?devolucion=' . urlencode($numeroDevolucion)) : null
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    
    // Registrar error en auditoría
    if (isset($numero)) {
        registrarAuditoria(
            'devolver_insumos',
            'asignaciones',
            "Error al devolver insumos - Remito: {$numero}",
            null,
            null,
            null,
            null,
            'error',
            $e->getMessage()
        );
    }
    
    json_error($e->getMessage(), 500);
}
?>

