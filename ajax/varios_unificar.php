<?php
/**
 * AJAX: Unificación y Fusión de Insumos Tipo "Varios" Duplicados (Exactos o Similares)
 * - Reasigna remitos_detalle, pedidos, movimientos_stock y bajas al Insumo Maestro.
 * - Suma las cantidades físicas (depósito y oficina) en el Insumo Maestro.
 * - Opcionalmente actualiza el nombre_insumo al nombre oficial/canónico (corrigiendo typos).
 * - Elimina físicamente de la tabla 'insumos' los registros duplicados que quedaron en 0 y sin referencias.
 * - Registra la operación completa en la tabla 'auditoria_acciones'.
 */
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autorizado. Inicie sesión.', 401);
}

if (!tienePermiso('insumos', 'editar')) {
    json_error('No tiene permisos para editar o unificar insumos.', 403);
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

// Obtener payload JSON o POST estándar
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true) ?? [];
if (empty($input)) {
    $input = $_POST;
}

// Verificar CSRF (aceptar token de JSON, Header X-CSRF-Token o $_POST)
$token = $input['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null));
if (!verify_csrf($token)) {
    json_error('Token CSRF inválido o expirado. Recargue la página.', 403);
}

$idMaestro = (int)($input['id_maestro'] ?? 0);
$nombreOficial = trim($input['nombre_oficial'] ?? '');
$nombreInsumo = trim($input['nombre_insumo'] ?? '');
$idsAUnificar = $input['ids_a_unificar'] ?? [];

if ($idMaestro <= 0) {
    json_error('El ID del Insumo Maestro es requerido.', 400);
}

try {
    $db = conectarDB();
    $db->beginTransaction();

    // 1. Validar que el insumo maestro exista y sea del tipo Varios
    $stmt = $db->prepare("SELECT * FROM insumos WHERE id_insumo = ? AND tipo_insumo = 'Varios' LIMIT 1");
    $stmt->execute([$idMaestro]);
    $maestro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$maestro) {
        $db->rollBack();
        json_error('El insumo maestro seleccionado no existe o no es de tipo Varios', 404);
    }

    // 2. Determinar cuáles son los IDs duplicados a fusionar
    $duplicados = [];

    if (!empty($idsAUnificar) && is_array($idsAUnificar)) {
        // IDs específicos enviados (ej. desde el módulo de nombres casi iguales/typos)
        $cleanIds = array_map('intval', $idsAUnificar);
        $cleanIds = array_values(array_filter($cleanIds, fn($id) => $id > 0 && $id !== $idMaestro));

        if (empty($cleanIds)) {
            $db->rollBack();
            json_error('No se especificaron IDs válidos para unificar.', 400);
        }

        $inClean = implode(',', array_fill(0, count($cleanIds), '?'));
        $stmt = $db->prepare("
            SELECT id_insumo, nombre_insumo, cantidad, cantidad_deposito, cantidad_oficina, estado, descripcion_general
            FROM insumos
            WHERE id_insumo IN ($inClean) AND tipo_insumo = 'Varios'
        ");
        $stmt->execute($cleanIds);
        $duplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif (!empty($nombreInsumo)) {
        // Búsqueda por nombre exacto normalizado
        $stmt = $db->prepare("
            SELECT id_insumo, nombre_insumo, cantidad, cantidad_deposito, cantidad_oficina, estado, descripcion_general
            FROM insumos
            WHERE tipo_insumo = 'Varios'
              AND LOWER(TRIM(nombre_insumo)) = LOWER(TRIM(?))
              AND id_insumo != ?
        ");
        $stmt->execute([$nombreInsumo, $idMaestro]);
        $duplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($duplicados)) {
        $db->rollBack();
        json_error('No se encontraron registros duplicados para unificar con este insumo.', 400);
    }

    $idsDuplicados = array_values(array_unique(array_column($duplicados, 'id_insumo')));
    $cantDuplicados = count($idsDuplicados);

    // Sumar stock de los duplicados
    $sumCantidad = 0;
    $sumDeposito = 0;
    $sumOficina = 0;

    foreach ($duplicados as $dup) {
        $sumCantidad += (int)($dup['cantidad'] ?? 0);
        $sumDeposito += (int)($dup['cantidad_deposito'] ?? 0);
        $sumOficina += (int)($dup['cantidad_oficina'] ?? 0);
    }

    $nuevaCantidad = (int)$maestro['cantidad'] + $sumCantidad;
    $nuevoDeposito = (int)$maestro['cantidad_deposito'] + $sumDeposito;
    $nuevaOficina = (int)$maestro['cantidad_oficina'] + $sumOficina;
    $nuevoEstado = ($nuevaCantidad > 0) ? 'Disponible' : 'Asignado';

    // 3. Actualizar stock (y opcionalmente nombre oficial) del Insumo Maestro
    $sqlMaestro = "
        UPDATE insumos 
        SET cantidad = ?,
            cantidad_deposito = ?,
            cantidad_oficina = ?,
            estado = ?
    ";
    $paramsMaestro = [$nuevaCantidad, $nuevoDeposito, $nuevaOficina, $nuevoEstado];

    if (!empty($nombreOficial)) {
        $sqlMaestro .= ", nombre_insumo = ?";
        $paramsMaestro[] = $nombreOficial;
    }

    $sqlMaestro .= " WHERE id_insumo = ?";
    $paramsMaestro[] = $idMaestro;

    $stmt = $db->prepare($sqlMaestro);
    $stmt->execute(array_values($paramsMaestro));

    // 4. Reasignar claves foráneas hacia el Insumo Maestro
    $inQuery = implode(',', array_fill(0, count($idsDuplicados), '?'));
    $params = array_values(array_merge([$idMaestro], $idsDuplicados));

    // 4a. remitos_detalle
    $stmt = $db->prepare("UPDATE remitos_detalle SET id_insumo = ? WHERE id_insumo IN ($inQuery)");
    $stmt->execute($params);
    $remitosAfectados = $stmt->rowCount();

    // 4b. pedidos
    $stmt = $db->prepare("UPDATE pedidos SET id_insumo_relacionado = ? WHERE id_insumo_relacionado IN ($inQuery)");
    $stmt->execute($params);
    $pedidosAfectados = $stmt->rowCount();

    // 4c. insumos_movimientos_stock
    $stmt = $db->prepare("UPDATE insumos_movimientos_stock SET id_insumo = ? WHERE id_insumo IN ($inQuery)");
    $stmt->execute($params);
    $movimientosAfectados = $stmt->rowCount();

    // 4d. insumos_bajas
    $stmt = $db->prepare("UPDATE insumos_bajas SET id_insumo = ? WHERE id_insumo IN ($inQuery)");
    $stmt->execute($params);
    $bajasAfectadas = $stmt->rowCount();

    // 5. Eliminar físicamente los registros duplicados vaciados de la tabla 'insumos'
    $stmt = $db->prepare("DELETE FROM insumos WHERE id_insumo IN ($inQuery)");
    $stmt->execute($idsDuplicados);
    $eliminados = $stmt->rowCount();

    // 6. Registrar en auditoría
    $nombreFinal = !empty($nombreOficial) ? $nombreOficial : $maestro['nombre_insumo'];
    $descAuditoria = "Unificación y eliminación de {$eliminados} registros duplicados (" . implode(', ', $idsDuplicados) . ") en el Insumo Maestro #{$idMaestro} ('{$nombreFinal}'). Remitos reasignados: {$remitosAfectados}. Stock consolidado: {$nuevaCantidad} (Dep: {$nuevoDeposito}, Of: {$nuevaOficina}). Registros eliminados de la BD.";

    registrarAuditoria(
        'unificar_stock_varios',
        'insumos',
        $descAuditoria,
        'insumos',
        $idMaestro,
        ['maestro_antes' => $maestro, 'duplicados_eliminados' => $duplicados],
        ['id_maestro' => $idMaestro, 'nombre' => $nombreFinal, 'nuevo_stock' => $nuevaCantidad, 'remitos_reasignados' => $remitosAfectados, 'registros_eliminados' => $eliminados]
    );

    $db->commit();

    json_success([
        'mensaje' => "Se unificaron y eliminaron exitosamente {$eliminados} registros duplicados en el Insumo Maestro #{$idMaestro}.",
        'id_maestro' => $idMaestro,
        'nombre_oficial' => $nombreFinal,
        'cant_eliminados' => $eliminados,
        'remitos_reasignados' => $remitosAfectados,
        'stock_consolidado' => $nuevaCantidad
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error('Error al unificar insumos tipo varios', [
        'error' => $e->getMessage(),
        'id_maestro' => $idMaestro
    ]);
    json_error('Error al procesar la unificación: ' . $e->getMessage(), 500);
}
