<?php
/**
 * SITIA - Endpoint AJAX para alternar estado activo/inactivo de una Sede
 */

require_once '../includes/config.php';

try {
    // 1. Verificar autenticación
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }

    // 2. Verificar permisos de edición de sedes
    if (!tienePermiso('sedes', 'editar')) {
        json_error('No tiene permiso para modificar sedes', 403);
    }

    // 3. Verificar CSRF
    $tokenCSRF = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf($tokenCSRF)) {
        json_error('Token CSRF inválido o expirado. Recargue la página.', 403);
    }

    $id_sede = (int)($_POST['id_sede'] ?? 0);
    if ($id_sede <= 0) {
        json_error('ID de sede inválido', 400);
    }

    $db = conectarDB();

    // Obtener datos actuales de la sede
    $stmt = $db->prepare("SELECT id_sede, nombre_sede, activo FROM sedes WHERE id_sede = ?");
    $stmt->execute([$id_sede]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sede) {
        json_error('La sede no existe', 404);
    }

    $nuevoEstado = !empty($sede['activo']) ? 0 : 1;
    $accion = $nuevoEstado ? 'activada' : 'desactivada';

    // Si se intenta desactivar, validar que no tenga insumos asignados ni pendientes de devolución
    if ($nuevoEstado === 0) {
        $stmtInsumos = $db->prepare("SELECT COUNT(*) FROM insumos WHERE id_sede_actual = ? AND estado != 'De Baja'");
        $stmtInsumos->execute([$id_sede]);
        $cantInsumos = (int)$stmtInsumos->fetchColumn();

        $stmtRem = $db->prepare("
            SELECT COALESCE(SUM(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0)), 0) 
            FROM remitos_detalle rd 
            JOIN remitos r ON rd.id_remito = r.id_remito 
            WHERE r.id_sede = ?
        ");
        $stmtRem->execute([$id_sede]);
        $cantRemitosPendientes = (int)$stmtRem->fetchColumn();

        $totalPendientes = max($cantInsumos, $cantRemitosPendientes);

        if ($totalPendientes > 0) {
            json_error("No se puede desactivar la sede '{$sede['nombre_sede']}' porque aún cuenta con {$totalPendientes} insumo(s) asignado(s) o pendientes de devolución. Debe registrar la devolución de la totalidad de los insumos antes de desactivarla.", 400);
        }
    }

    // Actualizar estado
    $stmtUpd = $db->prepare("UPDATE sedes SET activo = ? WHERE id_sede = ?");
    $stmtUpd->execute([$nuevoEstado, $id_sede]);

    // Registrar auditoría si existe la función
    if (function_exists('registrarAuditoria')) {
        $accionAuditoria = $nuevoEstado ? 'activar' : 'desactivar';
        registrarAuditoria(
            "{$accionAuditoria}_sede",
            'sedes',
            "Sede {$accion}: {$sede['nombre_sede']}",
            'sede',
            $id_sede,
            ['activo' => $sede['activo']],
            ['activo' => $nuevoEstado]
        );
    }

    $mensaje = "Sede '{$sede['nombre_sede']}' {$accion} correctamente.";

    json_success([
        'id_sede' => $id_sede,
        'activo' => $nuevoEstado,
        'mensaje' => $mensaje
    ]);

} catch (Exception $e) {
    Logger::error('Error al cambiar estado de sede', [
        'error' => $e->getMessage(),
        'id_sede' => $_POST['id_sede'] ?? null
    ]);
    json_error('Error del servidor: ' . $e->getMessage(), 500);
}
