<?php
$file = 'ajax/pedidos_acciones.php';
$content = file_get_contents($file);
$find = "        case 'eliminar_remito':";
$replace = "        case 'crear_tarea_interna':
            if (tieneRol([4]) && !defined('TESTING')) json_error('Sin permiso para crear tareas internas', 403);
            \$titulo     = trim(\$_POST['titulo'] ?? '');
            \$detalle    = trim(\$_POST['detalle'] ?? '');
            if (empty(\$titulo)) json_error('El título de la tarea es obligatorio', 400);
            \$sedeUsuario = 1;
            \$stmt = \$db->prepare(\"INSERT INTO pedidos
                (id_usuario_solicitante, id_sede, tipo, prioridad, descripcion, insumo_relacionado, asignado_a, estado, fecha_creacion, metodo_entrega, solicitante_nombre, solicitante_apellido, estado_entrega)
                VALUES (?, ?, 'Tarea Interna', 'Media', ?, ?, NULL, 'Pendiente', NOW(), 'No aplica', 'Sistema', 'Interno', 'Pendiente')\");
            \$stmt->execute([\$usuarioId, \$sedeUsuario, \$titulo, \$detalle ?: null]);
            \$idPedido = \$db->lastInsertId();
            \$stmtH = \$db->prepare(\"INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Creación', 'Tarea interna creada')\");
            \$stmtH->execute([\$idPedido, \$usuarioId]);
            json_success(['mensaje' => 'Tarea interna creada', 'id' => \$idPedido]);
            break;

        case 'tomar_tarea_interna':
            if (tieneRol([4]) && !defined('TESTING')) json_error('Sin permiso', 403);
            \$id = (int)(\$_POST['id'] ?? 0);
            if (\$id <= 0) json_error('ID inválido', 400);

            \$stmtCurr = \$db->prepare(\"SELECT estado, asignado_a, tipo FROM pedidos WHERE id_pedido = ?\");
            \$stmtCurr->execute([\$id]);
            \$curr = \$stmtCurr->fetch();

            if (!\$curr) json_error('Tarea no encontrada', 404);
            if (\$curr['tipo'] !== 'Tarea Interna') json_error('Esta acción sólo aplica para tareas internas', 400);
            if (\$curr['asignado_a']) json_error('La tarea ya fue tomada por otro usuario', 400);

            \$stmt = \$db->prepare(\"UPDATE pedidos SET asignado_a = ?, estado = 'En Proceso' WHERE id_pedido = ?\");
            \$stmt->execute([\$usuarioId, \$id]);

            \$stmtH = \$db->prepare(\"INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Toma de Tarea', 'Tarea interna tomada y En Proceso')\");
            \$stmtH->execute([\$id, \$usuarioId]);

            json_success(['mensaje' => 'Tomaste la tarea correctamente']);
            break;

        case 'completar_tarea_interna':
            if (tieneRol([4]) && !defined('TESTING')) json_error('Sin permiso', 403);
            \$id      = (int)(\$_POST['id'] ?? 0);
            \$trabajo = trim(\$_POST['trabajo'] ?? '');

            if (\$id <= 0) json_error('ID inválido', 400);
            if (empty(\$trabajo)) json_error('Debe describir el trabajo realizado', 400);

            \$stmtCurr = \$db->prepare(\"SELECT asignado_a, tipo FROM pedidos WHERE id_pedido = ?\");
            \$stmtCurr->execute([\$id]);
            \$curr = \$stmtCurr->fetch();
            
            if (!\$curr) json_error('Tarea no encontrada', 404);
            if (\$curr['tipo'] !== 'Tarea Interna') json_error('Acción inválida', 400);
            if (\$curr['asignado_a'] != \$usuarioId && !tieneRol([1,2])) json_error('Solo el asignado o un admin puede completarla', 403);

            \$stmt = \$db->prepare(\"UPDATE pedidos SET estado = 'Completado', notas_entrega = ? WHERE id_pedido = ?\");
            \$stmt->execute([\$trabajo, \$id]);

            \$stmtH = \$db->prepare(\"INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Tareas Completada', ?)\");
            \$stmtH->execute([\$id, \$usuarioId, 'Trabajo realizado: ' . \$trabajo]);

            json_success(['mensaje' => 'Tarea completada exitosamente']);
            break;

" . $find;
$content = str_replace($find, $replace, $content);
file_put_contents($file, $content);
?>
