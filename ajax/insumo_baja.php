<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'baja')) {
    json_error('No tienes permisos para dar de baja insumos', 403);
}

if (!verify_csrf()) {
    json_error('CSRF inválido', 403);
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    if (!$data || empty($data['id_insumo']) || !isset($data['observacion'])) {
        json_error('Datos inválidos', 400);
    }

    $idInsumo = (int)$data['id_insumo'];
    $observacion = trim((string)$data['observacion']);
    $cantidadSolicitada = isset($data['cantidad']) ? max(1, (int)$data['cantidad']) : 1;
    
    if ($idInsumo <= 0 || strlen($observacion) < 3) {
        json_error('Parámetros insuficientes', 400);
    }

    $db = conectarDB();

    // Verificar estado y tipo
    $stmt = $db->prepare("SELECT estado, tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito FROM insumos WHERE id_insumo = ? FOR UPDATE");
    $stmt->execute([$idInsumo]);
    $ins = $stmt->fetch();
    
    if (!$ins) {
        json_error('Insumo no encontrado', 404);
    }
    
    if ($ins['tipo_insumo'] !== 'Varios' && $ins['estado'] === 'Asignado') {
        json_error('No se puede dar de baja un insumo asignado', 400);
    }
    
    if ($ins['tipo_insumo'] === 'Varios' && (int)$ins['cantidad'] <= 0) {
        json_error('No hay stock disponible para dar de baja', 400);
    }

    $db->beginTransaction();

    // Para tipo Varios permitir baja parcial por cantidad
    if (trim($ins['tipo_insumo']) === 'Varios') {
        $stockTotal = (int)$ins['cantidad'];
        $stockOficina = isset($ins['cantidad_oficina']) ? (int)$ins['cantidad_oficina'] : $stockTotal;
        $stockDeposito = isset($ins['cantidad_deposito']) ? (int)$ins['cantidad_deposito'] : 0;

        $aBajar = min(max(1, (int)$cantidadSolicitada), $stockTotal);
        
        $descontarOficina = min($aBajar, $stockOficina);
        $descontarDeposito = $aBajar - $descontarOficina;

        $nuevoOficina = max(0, $stockOficina - $descontarOficina);
        $nuevoDeposito = max(0, $stockDeposito - $descontarDeposito);
        $nuevoStock = $nuevoOficina + $nuevoDeposito;

        $nuevoEstado = $nuevoStock > 0 ? 'Disponible' : 'De Baja';
        
        $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, cantidad_deposito = ?, estado = ?, id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
           ->execute([$nuevoStock, $nuevoOficina, $nuevoDeposito, $nuevoEstado, $idInsumo]);
    } else {
        // Unitarios: baja total
        $db->prepare("UPDATE insumos SET estado = 'De Baja', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
           ->execute([$idInsumo]);
        $cantidadSolicitada = 1;
    }

    // Asegurar tabla de historial de bajas
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

    // Tolerante: agregar columna cantidad si no existe
    try { $db->query("SELECT cantidad FROM insumos_bajas LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE insumos_bajas ADD COLUMN cantidad INT NOT NULL DEFAULT 1 AFTER observacion"); } catch (Exception $e2) {} }

    // Registrar observación y cantidad de baja
    try {
        $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion, cantidad) VALUES (?, ?, ?)")
           ->execute([$idInsumo, $observacion, (int)$cantidadSolicitada]);
    } catch (Exception $e) {
        // Fallback si no existe columna cantidad
        $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
           ->execute([$idInsumo, $observacion]);
    }

    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        'baja_insumo',
        'insumos',
        "Baja de insumo (ID: {$idInsumo}): {$observacion} - Cantidad: {$cantidadSolicitada}",
        'insumo',
        $idInsumo,
        ['estado' => $ins['estado'], 'cantidad' => $ins['cantidad']],
        ['estado' => ($ins['tipo_insumo'] === 'Varios' ? ($nuevoStock > 0 ? 'Disponible' : 'De Baja') : 'De Baja'), 'cantidad' => isset($nuevoStock) ? $nuevoStock : 0]
    );
    
    Logger::info('Insumo dado de baja exitosamente', [
        'id_insumo' => $idInsumo,
        'tipo' => $ins['tipo_insumo'],
        'cantidad_baja' => $cantidadSolicitada,
        'observacion' => substr($observacion, 0, 50)
    ]);
    
    json_success(['message' => 'Insumo dado de baja correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { 
        $db->rollBack(); 
    }
    
    // Registrar error en auditoría
    if (isset($idInsumo) && $idInsumo > 0) {
        registrarAuditoria(
            'baja_insumo',
            'insumos',
            "Error al dar de baja insumo (ID: {$idInsumo})",
            'insumo',
            $idInsumo,
            null,
            null,
            'error',
            $e->getMessage()
        );
    }
    
    Logger::error('Error al dar de baja insumo', [
        'mensaje' => $e->getMessage(),
        'id_insumo' => $idInsumo ?? 0,
        'observacion' => isset($observacion) ? substr($observacion, 0, 50) : ''
    ]);
    
    json_error($e->getMessage(), 500);
}
?>

