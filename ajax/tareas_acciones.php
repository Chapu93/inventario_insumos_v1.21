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
        case 'editar':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso para editar tareas', 403);

            $id          = (int)($_POST['id'] ?? 0);
            $titulo      = trim($_POST['titulo']      ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');

            if ($id <= 0)            json_error('ID de tarea inválido', 400);
            if (empty($titulo))      json_error('El título es obligatorio', 400);
            if (empty($descripcion)) json_error('La descripción es obligatoria', 400);

            // Verificar estado actual
            $stmt = $db->prepare("SELECT estado, es_colaborativa FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);
            $tarea = $stmt->fetch();
            if (!$tarea) json_error('Tarea no encontrada', 404);
            if ($tarea['estado'] === 'Completada') json_error('No se puede editar una tarea completada', 400);

            if ($tarea['es_colaborativa']) {
                $asignadoA = null;
                $nuevoEstado = 'En Proceso';
            } else {
                $asignadoA = (int)($_POST['asignado_a'] ?? 0) ?: null;
                // Ajustar estado según asignación si no ha cambiado manualmente
                $nuevoEstado = $tarea['estado'];
                if ($asignadoA && $tarea['estado'] === 'Pendiente') {
                    $nuevoEstado = 'En Proceso';
                } elseif (!$asignadoA && $tarea['estado'] === 'En Proceso') {
                    $nuevoEstado = 'Pendiente';
                }
            }

            $stmt = $db->prepare(
                "UPDATE tareas_internas 
                 SET titulo = ?, descripcion = ?, asignado_a = ?, estado = ?
                 WHERE id_tarea = ?"
            );
            $stmt->execute([$titulo, $descripcion, $asignadoA, $nuevoEstado, $id]);

            json_success(['mensaje' => 'Tarea actualizada correctamente']);
            break;

        // ──────────────────────────────────────────────
        case 'crear':
            if (!tienePermiso('pedidos', 'crear')) json_error('Sin permiso para crear tareas', 403);

            $titulo         = trim($_POST['titulo']      ?? '');
            $descripcion    = trim($_POST['descripcion'] ?? '');
            $esColaborativa = isset($_POST['es_colaborativa']) && $_POST['es_colaborativa'] == '1' ? 1 : 0;
            $asignadoA      = $esColaborativa ? null : ((int)($_POST['asignado_a'] ?? 0) ?: null);

            if (empty($titulo))      json_error('El título es obligatorio', 400);
            if (empty($descripcion)) json_error('La descripción es obligatoria', 400);

            $estado = $esColaborativa ? 'En Proceso' : ($asignadoA ? 'En Proceso' : 'Pendiente');

            $stmt = $db->prepare(
                "INSERT INTO tareas_internas (titulo, descripcion, estado, creado_por, asignado_a, es_colaborativa)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$titulo, $descripcion, $estado, $usuarioId, $asignadoA, $esColaborativa]);

            json_success(['mensaje' => 'Tarea creada correctamente', 'id' => $db->lastInsertId()]);
            break;

        // ──────────────────────────────────────────────
        case 'tomar':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado, asignado_a, es_colaborativa FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);
            $tarea = $stmt->fetch();

            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['es_colaborativa']) { $db->rollBack(); json_error('Esta es una tarea colaborativa y no puede ser tomada individualmente', 400); }
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

            $stmtT = $db->prepare("SELECT estado, es_colaborativa FROM tareas_internas WHERE id_tarea = ?");
            $stmtT->execute([$id]);
            $tarea = $stmtT->fetch();
            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['es_colaborativa']) { $db->rollBack(); json_error('No se puede asignar una tarea colaborativa', 400); }
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

            $stmt = $db->prepare("SELECT estado, asignado_a, es_colaborativa FROM tareas_internas WHERE id_tarea = ?");
            $stmt->execute([$id]);
            $tarea = $stmt->fetch();

            if (!$tarea) { $db->rollBack(); json_error('Tarea no encontrada', 404); }
            if ($tarea['estado'] === 'Completada') { $db->rollBack(); json_error('La tarea ya está completada', 400); }

            $comentario = trim($_POST['comentario'] ?? '');

            // Solo el asignado o un admin pueden completar si NO es colaborativa
            $esAdmin = tieneRol([1, 2]);
            if (!$tarea['es_colaborativa']) {
                if ($tarea['asignado_a'] != $usuarioId && !$esAdmin) {
                    $db->rollBack();
                    json_error('Solo el usuario asignado puede completar esta tarea', 403);
                }
            }

            // Procesar adjunto si existe
            $adjuntoPath = null;
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                require_once '../includes/validar_archivo.php';
                $validacion = validarArchivoDocumento($_FILES['adjunto']);
                if (!$validacion['valido']) {
                    $db->rollBack();
                    json_error($validacion['error'], 400);
                }
                
                $uploadDir = UPLOAD_BASE_DIR . 'tareas/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = $validacion['extension'];
                $nombreArchivo = 'tarea_' . $id . '_' . time() . '_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $uploadDir . $nombreArchivo)) {
                    $adjuntoPath = $nombreArchivo;
                } else {
                    $db->rollBack();
                    json_error('Error al guardar el archivo adjunto', 500);
                }
            }

            $stmt = $db->prepare(
                "UPDATE tareas_internas
                 SET estado = 'Completada', 
                     fecha_finalizacion = NOW(), 
                     asignado_a = COALESCE(asignado_a, ?), 
                     comentario = ?,
                     adjunto_path = ?
                 WHERE id_tarea = ?"
            );
            $stmt->execute([$usuarioId, $comentario, $adjuntoPath, $id]);

            $db->commit();
            json_success(['mensaje' => 'Tarea marcada como completada']);
            break;

        // ──────────────────────────────────────────────
        case 'liberar':
            // Vuelve a Pendiente y desasigna (solo admin)
            if (!tieneRol([1, 2])) json_error('Sin permiso para liberar tareas', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);

            $stmtVerif = $db->prepare("SELECT es_colaborativa FROM tareas_internas WHERE id_tarea = ?");
            $stmtVerif->execute([$id]);
            $tareaColab = $stmtVerif->fetchColumn();
            if ($tareaColab) json_error('No se puede liberar una tarea colaborativa', 400);

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

            // Obtener comentarios
            $stmtC = $db->prepare(
                "SELECT tc.*, u.nombre, u.apellido, u.username
                 FROM tareas_comentarios tc
                 JOIN usuarios u ON tc.id_usuario = u.id_usuario
                 WHERE tc.id_tarea = ?
                 ORDER BY tc.fecha ASC"
            );
            $stmtC->execute([$id]);
            $comentarios = $stmtC->fetchAll(PDO::FETCH_ASSOC);

            json_success(['tarea' => $tarea, 'comentarios' => $comentarios]);
            break;

        // ──────────────────────────────────────────────
        case 'agregar_comentario':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso para comentar', 403);

            $id_tarea   = (int)($_POST['id_tarea'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');

            if ($id_tarea <= 0)       json_error('ID de tarea inválido', 400);
            if (empty($comentario))   json_error('El comentario no puede estar vacío', 400);

            // Verificar que la tarea existe y no esté completada
            $stmtT = $db->prepare("SELECT estado FROM tareas_internas WHERE id_tarea = ?");
            $stmtT->execute([$id_tarea]);
            $tarea = $stmtT->fetch();
            if (!$tarea) json_error('Tarea no encontrada', 404);
            if ($tarea['estado'] === 'Completada') json_error('No se pueden añadir comentarios a una tarea completada', 400);

            $stmt = $db->prepare(
                "INSERT INTO tareas_comentarios (id_tarea, id_usuario, comentario)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$id_tarea, $usuarioId, $comentario]);

            json_success(['mensaje' => 'Comentario agregado correctamente']);
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
