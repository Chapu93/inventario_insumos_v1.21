<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    json_error('Token CSRF inválido', 403);
}

$accion    = $_POST['accion'] ?? $_GET['accion'] ?? '';
$usuarioId = obtenerUsuarioId();

try {
    $db = conectarDB();

    switch ($accion) {

        // ──────────────────────────────────────────────
        case 'crear':
            if (!tienePermiso('pedidos', 'crear')) json_error('Sin permiso para crear tareas', 403);

            $titulo      = trim($_POST['titulo']      ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $asignadoA   = (int)($_POST['asignado_a'] ?? 0) ?: null;

            if (empty($titulo))      json_error('El título es obligatorio', 400);
            if (empty($descripcion)) json_error('La descripción es obligatoria', 400);

            $estado = $asignadoA ? 'En Proceso' : 'Pendiente';

            $stmt = $db->prepare(
                "INSERT INTO tareas_internas (titulo, descripcion, estado, creado_por, asignado_a)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$titulo, $descripcion, $estado, $usuarioId, $asignadoA]);

            json_success(['mensaje' => 'Tarea creada correctamente', 'id' => $db->lastInsertId()]);
            break;

        // ──────────────────────────────────────────────
        case 'tomar':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado, asignado_a FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);
            $tarea = $stmt->fetch();

            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['estado'] === 'Completada') { $db->rollBack(); json_error('La tarea ya está completada', 400); }
            if ($tarea['asignado_a'] && $tarea['asignado_a'] != $usuarioId) {
                $db->rollBack();
                json_error('La tarea ya está asignada a otro usuario', 400);
            }

            $stmt = $db->prepare("UPDATE tareas_internas SET asignado_a = ?, estado = 'En Proceso' WHERE id_tarea = ?");
            $stmt->execute([$usuarioId, $id]);

            $db->commit();
            json_success(['mensaje' => 'Tarea tomada correctamente']);
            break;

        // ──────────────────────────────────────────────
        case 'asignar':
            if (!tieneRol([1, 2])) json_error('Sin permiso para asignar tareas', 403);

            $id        = (int)($_POST['id']          ?? 0);
            $asignadoA = (int)($_POST['asignado_a']  ?? 0);

            if ($id <= 0)        json_error('ID de tarea inválido', 400);
            if ($asignadoA <= 0) json_error('Debe seleccionar un usuario válido', 400);

            $db->beginTransaction();

            $stmtU = $db->prepare("SELECT nombre, apellido, username FROM usuarios WHERE id_usuario = ? AND activo = 1");
            $stmtU->execute([$asignadoA]);
            $userDestino = $stmtU->fetch();
            if (!$userDestino) { $db->rollBack(); json_error('Usuario no encontrado o inactivo', 400); }

            $stmtT = $db->prepare("SELECT estado FROM tareas_internas WHERE id_tarea = ?");
            $stmtT->execute([$id]);
            $tarea = $stmtT->fetch();
            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['estado'] === 'Completada') { $db->rollBack(); json_error('No se puede asignar una tarea completada', 400); }

            $stmt = $db->prepare("UPDATE tareas_internas SET asignado_a = ?, estado = 'En Proceso' WHERE id_tarea = ?");
            $stmt->execute([$asignadoA, $id]);

            $db->commit();
            json_success(['mensaje' => 'Tarea asignada a ' . $userDestino['nombre'] . ' ' . $userDestino['apellido']]);
            break;

        // ──────────────────────────────────────────────
        case 'completar':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado, asignado_a FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);
            $tarea = $stmt->fetch();

            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['estado'] === 'Completada') { $db->rollBack(); json_error('La tarea ya está completada', 400); }

            // Solo el asignado o un admin pueden completar
            $esAdmin = tieneRol([1, 2]);
            if ($tarea['asignado_a'] != $usuarioId && !$esAdmin) {
                $db->rollBack();
                json_error('Solo el usuario asignado puede completar esta tarea', 403);
            }

            $stmt = $db->prepare(
                "UPDATE tareas_internas
                 SET estado = 'Completada', fecha_finalizacion = NOW(), asignado_a = COALESCE(asignado_a, ?)
                 WHERE id_tarea = ?"
            );
            $stmt->execute([$usuarioId, $id]);

            $db->commit();
            json_success(['mensaje' => 'Tarea marcada como completada']);
            break;

        // ──────────────────────────────────────────────
        case 'liberar':
            // Vuelve a Pendiente y desasigna (solo admin)
            if (!tieneRol([1, 2])) json_error('Sin permiso para liberar tareas', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $stmt = $db->prepare(
                "UPDATE tareas_internas SET estado = 'Pendiente', asignado_a = NULL WHERE id_tarea = ? AND estado != 'Completada'"
            );
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                json_error('No se pudo liberar la tarea (ya está completada o no existe)', 400);
            }

            json_success(['mensaje' => 'Tarea devuelta al pool de pendientes']);
            break;

        // ──────────────────────────────────────────────
        case 'eliminar':
            if (!tienePermiso('pedidos', 'eliminar')) json_error('Sin permiso para eliminar', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $stmt = $db->prepare("DELETE FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);

            json_success(['mensaje' => 'Tarea eliminada correctamente']);
            break;

        // ──────────────────────────────────────────────
        case 'obtener':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $stmt = $db->prepare(
                "SELECT t.*,
                        uc.nombre as creador_nombre, uc.apellido as creador_apellido, uc.username as creador_user,
                        ua.nombre as asig_nombre,   ua.apellido as asig_apellido,   ua.username as asig_user
                 FROM tareas_internas t
                 JOIN usuarios uc ON t.creado_por = uc.id_usuario
                 LEFT JOIN usuarios ua ON t.asignado_a = ua.id_usuario
                 WHERE t.id_tarea = ?"
            );
            $stmt->execute([$id]);
            $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tarea) json_error('Tarea no encontrada', 404);

            json_success(['tarea' => $tarea]);
            break;

        // ──────────────────────────────────────────────
        default:
            json_error('Acción no válida', 400);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    Logger::error('Error en tareas_acciones', ['error' => $e->getMessage()]);
    json_error('Error interno del servidor', 500);
}
