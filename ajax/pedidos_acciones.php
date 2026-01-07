<?php
require_once '../includes/config.php';

if (!estaAutenticado() && (!defined('TESTING') || !TESTING)) {
    json_error('No autenticado', 401);
}

// Verificar CSRF para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf() && (!defined('TESTING') || !TESTING)) {
    json_error('Token CSRF inválido', 403);
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$usuarioId = (defined('TESTING') && isset($GLOBALS['MOCK_USER_ID'])) ? $GLOBALS['MOCK_USER_ID'] : obtenerUsuarioId();

try {
    $db = conectarDB();
    
    switch ($accion) {
        case 'crear':
            if (!tienePermiso('pedidos', 'crear') && !defined('TESTING')) json_error('Sin permiso', 403);
            
            // Datos Solicitante Externo
            $solNombre = trim($_POST['solicitante_nombre'] ?? '');
            $solApellido = trim($_POST['solicitante_apellido'] ?? '');
            $solTel = trim($_POST['solicitante_telefono'] ?? '');
            
            $tipo = $_POST['tipo'] ?? '';
            $prioridad = $_POST['prioridad'] ?? 'Media';
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            // Soporte para formato nuevo (insumos_ids, insumos_textos) y antiguo (insumo_manual)
            $insumoManual = trim($_POST['insumo_manual'] ?? '');
            $insumosIds = trim($_POST['insumos_ids'] ?? '');
            $insumosTextos = trim($_POST['insumos_textos'] ?? '');
            
            // Si viene el nuevo formato, usarlo
            if (!empty($insumosTextos) && empty($insumoManual)) {
                $insumoManual = $insumosTextos;
            }
            
            // Obtener el primer ID de insumo si hay varios
            $idInsumoRelacionado = null;
            if (!empty($insumosIds)) {
                $idsArray = explode(',', $insumosIds);
                $idInsumoRelacionado = (int)$idsArray[0] ?: null;
            } else {
                $idInsumoRelacionado = (int)($_POST['id_insumo_relacionado'] ?? 0) ?: null;
            }
            
            $sedeId = (int)($_POST['sede'] ?? 0);
            $areaId = (int)($_POST['area'] ?? 0);
            $asignadoA = (int)($_POST['asignado_a'] ?? 0) ?: null;
            
            // Validación
            if (empty($solNombre) || empty($solApellido)) json_error('El nombre y apellido del solicitante son obligatorios', 400);
            if (empty($tipo) || empty($descripcion) || empty($insumoManual)) {
                json_error('Datos incompletos. Verifique todos los campos requeridos.', 400);
            }
            
            if ($sedeId <= 0) {
                json_error('Debe seleccionar una sede válida', 400);
            }
            
            $fileName = null;
            
            // Validar PDF si se envió
            if (!empty($_FILES['nota_pdf']) && $_FILES['nota_pdf']['error'] === UPLOAD_ERR_OK) {
                // Usar validación centralizada con límite de 10MB
                $validacion = validarArchivoPdf($_FILES['nota_pdf'], 10 * 1024 * 1024);
                if (!$validacion['valido']) {
                    json_error($validacion['error'], 400);
                }
                
                $pdfFile = $_FILES['nota_pdf'];
                
                // Crear directorio si no existe
                $uploadDir = '../uploads/pedidos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Guardar PDF
                $fileName = 'pedido_' . time() . '_' . uniqid() . '.pdf';
                $targetPath = $uploadDir . $fileName;
                
                if (defined('TESTING') && TESTING) {
                    if (!rename($pdfFile['tmp_name'], $targetPath)) {
                        json_error('Error al guardar el archivo PDF (TEST MODE)', 500);
                    }
                } else {
                    if (!move_uploaded_file($pdfFile['tmp_name'], $targetPath)) {
                        json_error('Error al guardar el archivo PDF en el servidor', 500);
                    }
                }
            }
            
            $db->beginTransaction();
            try {
                // Insertar pedido (incluyendo id_insumo_relacionado y solicitante_apellido)
                $stmt = $db->prepare("INSERT INTO pedidos 
                    (id_usuario_solicitante, id_sede, id_area, tipo, prioridad, descripcion, insumo_relacionado, id_insumo_relacionado, asignado_a, estado, fecha_creacion, pdf_nota, solicitante_nombre, solicitante_apellido, solicitante_telefono)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW(), ?, ?, ?, ?)");
                
                $stmt->execute([
                    $usuarioId,
                    $sedeId,
                    $areaId ?: null,
                    $tipo,
                    $prioridad,
                    $descripcion,
                    $insumoManual,
                    $idInsumoRelacionado,
                    $asignadoA,
                    $fileName,
                    $solNombre,
                    $solApellido,
                    $solTel ?: null
                ]);
                
                $idPedido = $db->lastInsertId();
                
                // Registrar en historial
                $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Creación', 'Pedido de agente externo creado')");
                $stmtH->execute([$idPedido, $usuarioId]);
                
                $db->commit();
                json_success(['mensaje' => 'Pedido creado exitosamente', 'id' => $idPedido]);
            } catch (Exception $e) {
                $db->rollBack();
                if (file_exists($targetPath)) unlink($targetPath);
                throw $e;
            }
            break;

        case 'editar':
            if (!tienePermiso('pedidos', 'gestionar') && !tienePermiso('pedidos', 'crear') && !defined('TESTING')) json_error('Sin permiso', 403);
            
            $id = (int)($_POST['id'] ?? 0);
            // $titulo = trim($_POST['titulo'] ?? ''); // Removed
            $tipo = $_POST['tipo'] ?? '';
            $prioridad = $_POST['prioridad'] ?? 'Media';
            $descripcion = trim($_POST['descripcion'] ?? '');
            $insumoManual = trim($_POST['insumo_manual'] ?? '');
            
            // Editables nuevos (opcional, si el front lo permite)
            $solNombre = trim($_POST['solicitante_nombre'] ?? '');
            
            if ($id <= 0 || empty($tipo) || empty($descripcion) || empty($insumoManual)) {
                json_error('Datos incompletos', 400);
            }
            
            $db->beginTransaction();
            
            // Validar ownership
            $stmt = $db->prepare("SELECT id_usuario_solicitante FROM pedidos WHERE id_pedido = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            
            if (!$p) json_error('Pedido no encontrado', 404);
            
            if (!tienePermiso('pedidos', 'gestionar') && $p['id_usuario_solicitante'] != $usuarioId && !defined('TESTING')) {
                json_error('No tienes permiso para editar este pedido', 403);
            }
            
            // Update simple (sin solicitante por ahora si no viene, o agregamos campos hidden en editar)
            // Asumimos que editar viene desde un form que incluye campos nuevos?
            // Si el usuario no modificó el form de edicion, esto fallará si requerimos nombre.
            // Por ahora solo actualizamos lo que habia.
            // Si viene nombre, actualizamos.
            
            $sqlInfo = "";
            $params = [$tipo, $prioridad, $descripcion, $insumoManual];
            
            if (!empty($solNombre)) {
                 $sqlInfo = ", solicitante_nombre = ?, solicitante_telefono = ?, solicitante_email = ?";
                 $params[] = $solNombre;
                 $params[] = $_POST['solicitante_telefono'] ?? null;
                 $params[] = $_POST['solicitante_email'] ?? null;
            }

            $params[] = $id; // WHERE

            $stmt = $db->prepare("UPDATE pedidos SET tipo = ?, prioridad = ?, descripcion = ?, insumo_relacionado = ? $sqlInfo WHERE id_pedido = ?");
            $stmt->execute($params);
            
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Edición', 'Se actualizaron los datos del pedido')");
            $stmtH->execute([$id, $usuarioId]);
            
            $db->commit();
            json_success(['mensaje' => 'Pedido actualizado']);
            break;

        case 'obtener':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);
            
            // Obtener cabecera
            $stmt = $db->prepare("SELECT p.*, 
                                    u_sol.username as sol_user, u_sol.nombre as sol_nom, u_sol.apellido as sol_ape,
                                    u_asig.username as asig_user, u_asig.nombre as asig_nom, u_asig.apellido as asig_ape,
                                    s.nombre_sede,
                                    i.nombre_insumo, i.numero_serie
                                  FROM pedidos p
                                  JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                                  JOIN sedes s ON p.id_sede = s.id_sede
                                  LEFT JOIN usuarios u_asig ON p.asignado_a = u_asig.id_usuario
                                  LEFT JOIN insumos i ON p.id_insumo_relacionado = i.id_insumo
                                  WHERE p.id_pedido = ?");
            $stmt->execute([$id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pedido) json_error('Pedido no encontrado', 404);
            // Validar visibilidad
            if (!tienePermiso('pedidos', 'ver_todos') && !tienePermiso('pedidos', 'gestionar') && $pedido['id_usuario_solicitante'] != $usuarioId && !defined('TESTING')) {
                json_error('No tienes permiso para ver este pedido', 403);
            }
            
            // Obtener historial
            $stmtH = $db->prepare("SELECT h.*, u.username FROM pedidos_historial h JOIN usuarios u ON h.id_usuario = u.id_usuario WHERE h.id_pedido = ? ORDER BY h.fecha DESC");
            $stmtH->execute([$id]);
            $historial = $stmtH->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener informe si existe
            $stmtI = $db->prepare("SELECT * FROM pedidos_informes WHERE id_pedido = ?");
            $stmtI->execute([$id]);
            $informe = $stmtI->fetch(PDO::FETCH_ASSOC);
            
            json_success(['pedido' => $pedido, 'historial' => $historial, 'informe' => $informe]);
            break;

        case 'rechazar':
            // Solo el usuario asignado puede rechazar
            $id = (int)($_POST['id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            
            if ($id <= 0) json_error('ID inválido', 400);
            if (empty($motivo)) json_error('Debe indicar un motivo de rechazo', 400);
            
            $db->beginTransaction();
            
            // Verificar estado actual y si el usuario está asignado
            $stmtCurr = $db->prepare("SELECT estado, asignado_a FROM pedidos WHERE id_pedido = ?");
            $stmtCurr->execute([$id]);
            $curr = $stmtCurr->fetch();
            
            if (!$curr) { $db->rollBack(); json_error('Pedido no encontrado', 404); }
            if ($curr['estado'] === 'Completado') { $db->rollBack(); json_error('No se puede rechazar un pedido completado', 400); }
            if ($curr['estado'] === 'Pendiente') { $db->rollBack(); json_error('El pedido ya está pendiente', 400); }
            
            // Solo el usuario asignado o un admin pueden rechazar
            if ($curr['asignado_a'] != $usuarioId && !tieneRol([1, 2]) && !defined('TESTING')) {
                $db->rollBack();
                json_error('Solo el usuario asignado puede rechazar este pedido', 403);
            }
            
            // Obtener nombre del usuario que rechaza
            $usuarioRechaza = obtenerUsuario();
            $nombreRechaza = $usuarioRechaza['nombre'] . ' ' . $usuarioRechaza['apellido'] . ' (' . $usuarioRechaza['username'] . ')';
            
            // Volver a Pendiente y desasignar
            $stmt = $db->prepare("UPDATE pedidos SET estado = 'Pendiente', asignado_a = NULL WHERE id_pedido = ?");
            $stmt->execute([$id]);
            
            // Registrar en historial con detalle completo
            $detalle = "Rechazado por: " . $nombreRechaza . "\nMotivo: " . $motivo;
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Rechazado', ?)");
            $stmtH->execute([$id, $usuarioId, $detalle]);
            
            $db->commit();
            json_success(['mensaje' => 'Pedido rechazado. Ha vuelto a la lista de pendientes.']);
            break;


        case 'asignar':
            if (!tieneRol([1, 2]) && !defined('TESTING')) json_error('Sin permiso para asignar pedidos', 403);
            
            $id = (int)($_POST['id'] ?? 0);
            $asignadoA = (int)($_POST['asignado_a'] ?? 0);
            
            if ($id <= 0) json_error('ID de pedido inválido', 400);
            if ($asignadoA <= 0) json_error('Debe seleccionar un usuario válido', 400);
            
            $db->beginTransaction();
            
            // Verificar usuario destino
            $stmtUser = $db->prepare("SELECT username, nombre, apellido FROM usuarios WHERE id_usuario = ? AND activo = 1");
            $stmtUser->execute([$asignadoA]);
            $userDestino = $stmtUser->fetch();
            
            if (!$userDestino) {
                $db->rollBack();
                json_error('Usuario destino no encontrado o inactivo', 400);
            }
            
            // Verificar estado actual del pedido
            $stmtCurr = $db->prepare("SELECT estado, asignado_a FROM pedidos WHERE id_pedido = ?");
            $stmtCurr->execute([$id]);
            $curr = $stmtCurr->fetch();
            
            if (!$curr) { $db->rollBack(); json_error('Pedido no encontrado', 404); }
            if ($curr['asignado_a']) { 
                $db->rollBack(); 
                json_error('El pedido ya está asignado. Debe tomarlo o rechazarlo primero.', 400); 
            }
            if ($curr['estado'] === 'Completado' || $curr['estado'] === 'Rechazado') {
                $db->rollBack();
                json_error('No se puede asignar un pedido finalizado', 400);
            }
            
            // Realizar asignación
            $stmt = $db->prepare("UPDATE pedidos SET asignado_a = ?, estado = 'En Proceso' WHERE id_pedido = ?");
            $stmt->execute([$asignadoA, $id]);
            
            // Historial
            $nombreAsignador = obtenerUsuario()['username'];
            $nombreAsignado = $userDestino['username'];
            $detalle = "Asignado manualmente por $nombreAsignador a $nombreAsignado";
            
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Asignación', ?)");
            $stmtH->execute([$id, $usuarioId, $detalle]);
            
            $db->commit();
            json_success(['mensaje' => 'Pedido asignado correctamente a ' . $userDestino['nombre'] . ' ' . $userDestino['apellido']]);
            break;

        case 'tomar':
            if (!tienePermiso('pedidos', 'gestionar') && !defined('TESTING')) json_error('Sin permiso', 403);
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) json_error('ID inválido', 400);
            
            $db->beginTransaction();
            // Verificar estado actual
            $stmtCurr = $db->prepare("SELECT estado, asignado_a FROM pedidos WHERE id_pedido = ?");
            $stmtCurr->execute([$id]);
            $curr = $stmtCurr->fetch();
            if ($curr['asignado_a'] && $curr['asignado_a'] != $usuarioId) {
                 $db->rollBack();
                 json_error('El pedido ya está asignado a otro usuario', 400);
            }
            
            $stmt = $db->prepare("UPDATE pedidos SET asignado_a = ?, estado = 'En Proceso' WHERE id_pedido = ?");
            $stmt->execute([$usuarioId, $id]);
            
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Cambio Estado', 'Auto-asignado y puesto En Proceso')");
            $stmtH->execute([$id, $usuarioId]);
            
            $db->commit();
            json_success(['mensaje' => 'Has tomado el pedido']);
            break;

        case 'completar':
             if (!tienePermiso('pedidos', 'gestionar') && !defined('TESTING')) json_error('Sin permiso', 403);
             $id = (int)($_POST['id'] ?? 0);
             $diagnostico = trim($_POST['diagnostico'] ?? '');
             $trabajo = trim($_POST['trabajo'] ?? '');
             $resultado = $_POST['resultado'] ?? 'Solucionado';
             
             if ($id <= 0) json_error('ID inválido', 400);
             if (empty($diagnostico) || empty($trabajo)) json_error('Complete Diagnóstico y Trabajo Realizado', 400);

             $db->beginTransaction();
             
             // Verificar si ya tiene informe
             $stmtCheck = $db->prepare("SELECT id_informe FROM pedidos_informes WHERE id_pedido = ?");
             $stmtCheck->execute([$id]);
             if ($stmtCheck->fetch()) {
                 $db->rollBack();
                 json_error('Este pedido ya tiene un informe técnico', 400);
             }
             
             // Insertar informe
             $stmtI = $db->prepare("INSERT INTO pedidos_informes (id_pedido, diagnostico, trabajo_realizado, resultado) VALUES (?, ?, ?, ?)");
             $stmtI->execute([$id, $diagnostico, $trabajo, $resultado]);
             
             // Actualizar pedido
             $stmt = $db->prepare("UPDATE pedidos SET estado = 'Completado' WHERE id_pedido = ?");
             $stmt->execute([$id]);
             
             // Historial
             $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Informe generado', ?)");
             $stmtH->execute([$id, $usuarioId, "Informe generado. Resultado: $resultado"]);
             
             $db->commit();
             json_success(['mensaje' => 'Pedido completado e informe generado']);
             break;
             
        case 'eliminar':
            if (!tienePermiso('pedidos', 'eliminar')) json_error('Sin permiso', 403);
            $id = (int)($_POST['id'] ?? 0);
            
            $db->beginTransaction();
            // Borrar informe si hay (ON DELETE CASCADE está en DB, pero por seguridad)
            // Borrar historial (ON DELETE CASCADE)
            $db->prepare("DELETE FROM pedidos WHERE id_pedido = ?")->execute([$id]);
            $db->commit();
            
            json_success(['mensaje' => 'Pedido eliminado']);
            break;

        case 'subir_nota':
             $id = (int)($_POST['id'] ?? 0);
             
             // Verificar permiso: Solicitante (si no está completado) o Gestor
             $stmt = $db->prepare("SELECT id_usuario_solicitante, asignado_a, estado FROM pedidos WHERE id_pedido = ?");
             $stmt->execute([$id]);
             $p = $stmt->fetch();
             
             if (!$p) json_error('Pedido no encontrado', 404);
             
             // Permisos: Dueño o Gestor
             if ($p['id_usuario_solicitante'] != $usuarioId && !tienePermiso('pedidos', 'gestionar')) {
                 json_error('No tienes permiso para adjuntar nota a este pedido', 403);
             }
             
             if ($p['estado'] == 'Completado' || $p['estado'] == 'Rechazado') {
                 json_error('No se puede adjuntar nota a un pedido finalizado', 400);
             }
             
             if (empty($_FILES['nota_pdf']) || $_FILES['nota_pdf']['error'] !== UPLOAD_ERR_OK) {
                 json_error('Error al subir archivo o archivo inválido', 400);
             }
             
             // Usar validación centralizada con límite de 10MB
             $validacion = validarArchivoPdf($_FILES['nota_pdf'], 10 * 1024 * 1024);
             if (!$validacion['valido']) {
                 json_error($validacion['error'], 400);
             }
             
             $file = $_FILES['nota_pdf'];
             
             $uploadDir = '../uploads/pedidos/';
             if (!file_exists($uploadDir)) {
                 mkdir($uploadDir, 0755, true);
             }
             
             $fileName = 'pedido_' . $id . '_nota_' . time() . '.pdf';
             $targetPath = $uploadDir . $fileName;
             
             if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                 $db->beginTransaction();
                 $stmt = $db->prepare("UPDATE pedidos SET pdf_nota = ? WHERE id_pedido = ?");
                 $stmt->execute([$fileName, $id]);
                 
                 // Historial
                 $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Nota Adjunta', ?)");
                 $stmtH->execute([$id, $usuarioId, "Se adjuntó nota de pedido a posteriori"]);
                 
                 $db->commit();
                 
                 json_success(['mensaje' => 'Nota adjuntada correctamente']);
             } else {
                 json_error('Error al guardar archivo en servidor', 500);
             }
             break;

        case 'subir_adjunto':
             $id = (int)($_POST['id'] ?? 0);
             
             // Validación de permisos y existencia (similar a anterior)
             $stmt = $db->prepare("SELECT id_usuario_solicitante, asignado_a FROM pedidos WHERE id_pedido = ?");
             $stmt->execute([$id]);
             $p = $stmt->fetch();
             if (!$p) json_error('Pedido no encontrado', 404);
             
             if (!tienePermiso('pedidos', 'ver_todos') && $p['id_usuario_solicitante'] != $usuarioId && $p['asignado_a'] != $usuarioId) {
                 json_error('No tienes permiso', 403);
             }
             
             if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                 json_error('Error al subir archivo', 400);
             }
             
             // Usar validación centralizada con límite de 10MB
             $validacion = validarArchivoPdf($_FILES['archivo'], 10 * 1024 * 1024);
             if (!$validacion['valido']) {
                 json_error($validacion['error'], 400);
             }
             
             $file = $_FILES['archivo'];
             
             $uploadDir = '../uploads/pedidos/';
             if (!file_exists($uploadDir)) {
                 mkdir($uploadDir, 0755, true);
             }
             
             $fileName = 'pedido_' . $id . '_' . time() . '_' . uniqid() . '.pdf';
             $targetPath = $uploadDir . $fileName;
             
             if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                 $stmt = $db->prepare("INSERT INTO pedidos_adjuntos (id_pedido, id_usuario, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?)");
                 $stmt->execute([$id, $usuarioId, $file['name'], $fileName]);
                 
                 // Historial auto
                 $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Adjunto', ?)");
                 $stmtH->execute([$id, $usuarioId, "Se adjuntó archivo: " . $file['name']]);
                 
                 json_success(['mensaje' => 'Archivo subido']);
             } else {
                 json_error('Error al guardar archivo en servidor', 500);
             }
             break;

        case 'obtener_adjuntos':
             $id = (int)($_GET['id'] ?? 0);
             if ($id <= 0) json_error('ID inválido', 400);
             
             $stmt = $db->prepare("SELECT a.*, u.username as nombre_usuario 
                                   FROM pedidos_adjuntos a 
                                   JOIN usuarios u ON a.id_usuario = u.id_usuario 
                                   WHERE a.id_pedido = ? 
                                   ORDER BY a.fecha_carga DESC");
             $stmt->execute([$id]);
             $adjuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
             
             json_success(['adjuntos' => $adjuntos]);
             break;

        default:
            json_error('Acción no válida', 400);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    json_error($e->getMessage(), 500);
}
