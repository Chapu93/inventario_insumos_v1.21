<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    json_error('Token CSRF inválido', 403);
}

$usuarioId = obtenerUsuarioId();
$accion = $_POST['accion'] ?? '';

try {
    $db = conectarDB();

    switch ($accion) {
        case 'marcar_mostradas':
            $ids = $_POST['ids'] ?? [];
            if (is_string($ids)) {
                $ids = json_decode($ids, true);
            }
            if (empty($ids) || !is_array($ids)) {
                json_success(['mensaje' => 'Sin IDs para actualizar']);
            }
            $idsClean = array_map('intval', array_filter($ids, 'is_numeric'));
            if (empty($idsClean)) {
                json_success(['mensaje' => 'Sin IDs válidos']);
            }

            $inClause = implode(',', array_fill(0, count($idsClean), '?'));
            $params = array_merge([$usuarioId], $idsClean);

            $stmt = $db->prepare("UPDATE notificaciones SET mostrada = 1 WHERE id_usuario = ? AND id_notificacion IN ($inClause)");
            $stmt->execute($params);

            json_success(['mensaje' => 'Notificaciones marcadas como mostradas']);
            break;

        case 'marcar_leida':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_error('ID inválido', 400);
            }

            $stmt = $db->prepare("UPDATE notificaciones SET leida = 1, mostrada = 1 WHERE id_usuario = ? AND id_notificacion = ?");
            $stmt->execute([$usuarioId, $id]);

            json_success(['mensaje' => 'Notificación marcada como leída']);
            break;

        case 'marcar_todas_leidas':
            $stmt = $db->prepare("UPDATE notificaciones SET leida = 1, mostrada = 1 WHERE id_usuario = ?");
            $stmt->execute([$usuarioId]);

            json_success(['mensaje' => 'Todas las notificaciones marcadas como leídas']);
            break;

        case 'limpiar_todas':
        case 'limpiar_bandeja':
            $stmt = $db->prepare("DELETE FROM notificaciones WHERE id_usuario = ?");
            $stmt->execute([$usuarioId]);

            json_success(['mensaje' => 'Bandeja de notificaciones limpiada correctamente']);
            break;

        default:
            json_error('Acción no válida', 400);
            break;
    }

} catch (Exception $e) {
    Logger::error("Error en notificaciones_marcar", ['error' => $e->getMessage()]);
    json_error('Error al actualizar notificaciones', 500);
}
