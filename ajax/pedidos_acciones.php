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
        case 'editar':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso para editar pedidos', 403);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID de pedido inválido', 400);

            // Obtener datos del POST
            $sol_nombre    = trim($_POST['solicitante_nombre']   ?? '');
            $sol_apellido  = trim($_POST['solicitante_apellido'] ?? '');
            $sol_telefono  = trim($_POST['solicitante_telefono'] ?? '');
            $id_sede       = (int)($_POST['id_sede']             ?? 0);
            $id_area       = (int)($_POST['id_area']             ?? 0) ?: null;
            $tipo          = trim($_POST['tipo']                ?? '');
            $prioridad     = trim($_POST['prioridad']           ?? 'Media');
            $descripcion   = trim($_POST['descripcion']         ?? '');
            
            // Lógica de insumos idéntica a 'crear'
            $insumosIds = trim($_POST['id_insumo_relacionado'] ?? '');
            $insumosTextos = trim($_POST['insumo_relacionado'] ?? '');
            
            $idInsumoRelacionado = null;
            if (!empty($insumosIds)) {
                $idsArray = explode(',', $insumosIds);
                $idInsumoRelacionado = (int)$idsArray[0] ?: null;
            }

            // Validaciones básicas
            if (empty($sol_nombre) || empty($sol_apellido)) json_error('Nombre y Apellido del solicitante son obligatorios', 400);
            if ($id_sede <= 0)                             json_error('La sede es obligatoria', 400);
            if (empty($tipo))                               json_error('El tipo de pedido es obligatorio', 400);
            if (empty($descripcion))                        json_error('La descripción es obligatoria', 400);

            $db->beginTransaction();

            // Verificar si el pedido existe y su estado
            $stmt = $db->prepare("SELECT estado, pdf_nota FROM pedidos WHERE id_pedido = ?");
            $stmt->execute([$id]);
            $pedidoActual = $stmt->fetch();

            if (!$pedidoActual) json_error('Pedido no encontrado', 404);
            if ($pedidoActual['estado'] === 'Completado' || $pedidoActual['estado'] === 'Rechazado') {
                json_error('No se puede editar un pedido finalizado', 400);
            }

            // Procesar PDF si se envió uno nuevo
            $pdfNota = $pedidoActual['pdf_nota'];
            if (!empty($_FILES['nota_pedido']) && $_FILES['nota_pedido']['error'] === UPLOAD_ERR_OK) {
                $val = validarArchivoPdf($_FILES['nota_pedido']);
                if (!$val['valido']) json_error($val['error'], 400);

                $nombreArchivoNota = 'nota_sol_' . time() . '_' . uniqid() . '.' . $val['extension'];
                $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (move_uploaded_file($_FILES['nota_pedido']['tmp_name'], $uploadDir . $nombreArchivoNota)) {
                    // Eliminar el anterior si existía
                    if ($pdfNota && file_exists($uploadDir . $pdfNota)) {
                        @unlink($uploadDir . $pdfNota);
                    }
                    $pdfNota = $nombreArchivoNota;
                } else {
                    json_error('Error al guardar el nuevo archivo PDF', 500);
                }
            }

            // Actualizar pedido
            $sql = "UPDATE pedidos SET 
                    solicitante_nombre = ?, solicitante_apellido = ?, solicitante_telefono = ?,
                    id_sede = ?, id_area = ?, tipo = ?, prioridad = ?, descripcion = ?, 
                    insumo_relacionado = ?, id_insumo_relacionado = ?, pdf_nota = ?
                    WHERE id_pedido = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $sol_nombre, $sol_apellido, $sol_telefono,
                $id_sede, $id_area, $tipo, $prioridad, $descripcion, 
                $insumosTextos, $idInsumoRelacionado, $pdfNota,
                $id
            ]);

            // Registrar en historial
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Edición', 'Se actualizaron los datos del pedido')");
            $stmtH->execute([$id, $usuarioId]);

            $db->commit();
            json_success(['mensaje' => 'Pedido actualizado correctamente']);
            break;

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
            $metodoEntrega = $_POST['metodo_entrega'] ?? 'No aplica';
            
            // Validación
            if (empty($solNombre) || empty($solApellido)) json_error('El nombre y apellido del solicitante son obligatorios', 400);
            if (empty($tipo) || empty($descripcion)) {
                json_error('Datos incompletos. Verifique todos los campos requeridos.', 400);
            }
            // Para pedidos técnicos, el insumo relacionado es requerido
            if ($tipo !== 'Pedido Insumo' && empty($insumoManual)) {
                json_error('Debe indicar el insumo o equipo relacionado con la solicitud.', 400);
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
                $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
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
                    (id_usuario_solicitante, id_sede, id_area, tipo, prioridad, descripcion, insumo_relacionado, id_insumo_relacionado, asignado_a, estado, fecha_creacion, pdf_nota, solicitante_nombre, solicitante_apellido, solicitante_telefono, metodo_entrega)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW(), ?, ?, ?, ?, ?)");
                
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
                    $solTel ?: null,
                    $metodoEntrega
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



        case 'obtener':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);
            
            // Obtener cabecera
            $stmt = $db->prepare("SELECT p.*, 
                                    u_sol.username as sol_user, u_sol.nombre as sol_nom, u_sol.apellido as sol_ape,
                                    u_asig.username as asig_user, u_asig.nombre as asig_nom, u_asig.apellido as asig_ape,
                                    s.nombre_sede, s.id_localidad,
                                    i.nombre_insumo, i.numero_serie,
                                    r.numero_remito
                                  FROM pedidos p
                                  JOIN usuarios u_sol ON p.id_usuario_solicitante = u_sol.id_usuario
                                  JOIN sedes s ON p.id_sede = s.id_sede
                                  LEFT JOIN usuarios u_asig ON p.asignado_a = u_asig.id_usuario
                                  LEFT JOIN insumos i ON p.id_insumo_relacionado = i.id_insumo
                                  LEFT JOIN remitos r ON p.id_remito = r.id_remito
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
            
            // Obtener items del remito si el pedido tiene uno asociado
            $remito_items = [];
            if (!empty($pedido['id_remito'])) {
                $stmtRI = $db->prepare("SELECT rd.cantidad, i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico
                                        FROM remitos_detalle rd
                                        JOIN insumos i ON rd.id_insumo = i.id_insumo
                                        WHERE rd.id_remito = ?
                                        ORDER BY i.nombre_insumo");
                $stmtRI->execute([$pedido['id_remito']]);
                $remito_items = $stmtRI->fetchAll(PDO::FETCH_ASSOC);
            }

            json_success(['pedido' => $pedido, 'historial' => $historial, 'informe' => $informe, 'remito_items' => $remito_items]);
            break;

        case 'rechazar':
            // Solo el usuario asignado puede rechazar
            $id = (int)($_POST['id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            
            if ($id <= 0) json_error('ID inválido', 400);
            if (empty($motivo)) json_error('Debe indicar un motivo de rechazo', 400);
            
            $db->beginTransaction();
            
            // Verificar estado actual y si el usuario está asignado
            $stmtCurr = $db->prepare("SELECT estado, asignado_a, id_remito FROM pedidos WHERE id_pedido = ?");
            $stmtCurr->execute([$id]);
            $curr = $stmtCurr->fetch();
            
            if (!$curr) { $db->rollBack(); json_error('Pedido no encontrado', 404); }
            if ($curr['estado'] === 'Completado' || $curr['estado'] === 'Preparado') { 
                $db->rollBack(); 
                if (!empty($curr['id_remito'])) {
                    json_error('No se puede rechazar un pedido que ya generó un remito. Anule el remito primero.', 400);
                } else {
                    json_error('No se puede rechazar un pedido procesado o completado', 400); 
                }
            }
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

             $darDeBaja = !empty($_POST['dar_de_baja']) && ($_POST['dar_de_baja'] == '1');

             $db->beginTransaction();
             
             // Verificar tipo y si ya tiene informe
             $stmtCheck = $db->prepare("SELECT tipo, (SELECT id_informe FROM pedidos_informes WHERE id_pedido = p.id_pedido) as id_informe FROM pedidos p WHERE p.id_pedido = ?");
             $stmtCheck->execute([$id]);
             $check = $stmtCheck->fetch();

             if (!$check) json_error('Pedido no encontrado', 404);
             if ($check['tipo'] === 'Pedido Insumo') json_error('Los pedidos de insumo no requieren informe técnico. Deben marcarse como Preparados.', 400);
             if ($check['id_informe']) {
                 $db->rollBack();
                 json_error('Este pedido ya tiene un informe técnico', 400);
             }
             // Generar número de informe anual
             $numeroInforme = generarNumeroInforme($db);
             
             // Insertar informe
             $stmtI = $db->prepare("INSERT INTO pedidos_informes (id_pedido, numero_informe, diagnostico, trabajo_realizado, resultado) VALUES (?, ?, ?, ?, ?)");
             $stmtI->execute([$id, $numeroInforme, $diagnostico, $trabajo, $resultado]);
             
             // Datos de Entrega
             $metodoEntrega = $_POST['metodo_entrega'] ?? 'No aplica';
             $fechaEstimada = $_POST['fecha_estimada_entrega'] ?? null;
             $notasEntrega = trim($_POST['notas_entrega'] ?? '');

             // Actualizar pedido
             $estadoEntrega = 'Preparado';
             
             // Si se marca baja por falla técnica, el equipo ya no se devuelve reparado
             if ($darDeBaja && ($resultado === 'Sin Solución' || $resultado === 'Requiere Repuestos')) {
                 $metodoEntrega = 'No aplica';
                 $estadoEntrega = 'De Baja'; 
                 
                 // Obtener el insumo relacionado
                 $stmtInsumo = $db->prepare("SELECT id_insumo_relacionado FROM pedidos WHERE id_pedido = ?");
                 $stmtInsumo->execute([$id]);
                 $idInsumo = $stmtInsumo->fetchColumn();

                 if ($idInsumo) {
                     // 1. Marcar insumo como baja
                     $db->prepare("UPDATE insumos SET estado = 'De Baja', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
                        ->execute([$idInsumo]);

                     // 2. Registrar en historial de bajas
                     $obsBaja = "Baja técnica (Informe #$numeroInforme): $diagnostico";
                     $db->prepare("INSERT INTO insumos_bajas (id_insumo, observacion) VALUES (?, ?)")
                        ->execute([$idInsumo, substr($obsBaja, 0, 250)]);
                        
                     registrarAuditoria('baja_insumo', 'insumos', "Baja técnica desde Pedido #$id (Informe #$numeroInforme)", 'insumo', $idInsumo);
                 }
             }

             $stmt = $db->prepare("UPDATE pedidos SET 
                                     estado = 'Completado', 
                                     estado_entrega = ?, 
                                     metodo_entrega = ?, 
                                     fecha_estimada_entrega = ?, 
                                     notas_entrega = ?,
                                     fecha_preparacion = NOW(),
                                     fecha_entrega = CASE WHEN ? = 'De Baja' THEN NOW() ELSE fecha_entrega END
                                   WHERE id_pedido = ?");
             $stmt->execute([$estadoEntrega, $metodoEntrega, $fechaEstimada, $notasEntrega, $estadoEntrega, $id]);
             
             // Historial
             $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Informe generado', ?)");
             $descHist = "Informe generado. Resultado: $resultado" . ($darDeBaja ? " - Insumo dado de baja." : "");
             $stmtH->execute([$id, $usuarioId, $descHist]);
             
             $db->commit();
             json_success(['mensaje' => 'Pedido completado e informe generado']);
             break;
             
        case 'eliminar':
            if (!tienePermiso('pedidos', 'eliminar')) json_error('Sin permiso', 403);
            $id = (int)($_POST['id'] ?? 0);
            
            $db->beginTransaction();
            
            // Verificar estado para evitar borrar remitos/stock huérfano
            $stmtCheck = $db->prepare("SELECT estado, id_remito FROM pedidos WHERE id_pedido = ?");
            $stmtCheck->execute([$id]);
            $pedido = $stmtCheck->fetch();
            
            if (!$pedido) {
                $db->rollBack();
                json_error('Pedido no encontrado', 404);
            }
            
            if (in_array($pedido['estado'], ['Preparado', 'Completado'])) {
                $db->rollBack();
                if (!empty($pedido['id_remito'])) {
                    json_error('No se puede eliminar un pedido que ya generó un remito. Para devolver el stock, anule el remito en el módulo de Asignaciones.', 400);
                } else {
                    json_error('No se puede eliminar un pedido que ya está procesado o completado.', 400);
                }
            }
            
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
             
             $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
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
             
             $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
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

        case 'actualizar_entrega':
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso', 403);
            
            $id = (int)($_POST['id'] ?? 0);
            $estadoEntrega = $_POST['estado_entrega'] ?? '';
            $metodoEntrega = $_POST['metodo_entrega'] ?? '';
            $receptor = trim($_POST['receptor_nombre'] ?? '');
            
            if ($id <= 0 || empty($estadoEntrega)) json_error('Datos incompletos', 400);
            
            $db->beginTransaction();
            
            $sql = "UPDATE pedidos SET estado_entrega = ?";
            $params = [$estadoEntrega];
            
            if (!empty($metodoEntrega)) {
                $sql .= ", metodo_entrega = ?";
                $params[] = $metodoEntrega;
            }
            
            if ($estadoEntrega === 'Entregado') {
                // Validar carga obligatoria del remito firmado
                if (empty($_FILES['remito_firmado']) || $_FILES['remito_firmado']['error'] !== UPLOAD_ERR_OK) {
                    $db->rollBack();
                    json_error('Debe adjuntar el archivo del remito firmado o constancia de entrega.', 400);
                }
                
                // Usar validarArchivoPlano que permite PDF e imágenes (JPG, PNG)
                require_once '../includes/validar_archivo.php';
                $validacion = validarArchivoPlano($_FILES['remito_firmado'], 10 * 1024 * 1024);
                
                if (!$validacion['valido']) {
                    $db->rollBack();
                    json_error('Error en el archivo adjunto: ' . $validacion['error'], 400);
                }
                
                $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = $validacion['extension'];
                $fileName = 'remito_entregado_' . time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                
                if (defined('TESTING') && TESTING) {
                    rename($_FILES['remito_firmado']['tmp_name'], $targetPath);
                } else {
                    if (!move_uploaded_file($_FILES['remito_firmado']['tmp_name'], $targetPath)) {
                        $db->rollBack();
                        json_error('Error al guardar el archivo adjunto en el servidor', 500);
                    }
                }

                $sql .= ", fecha_entrega = NOW(), receptor_nombre = ?, estado = 'Completado', remito_firmado = ?";
                $params[] = $receptor ?: 'No especificado';
                $params[] = $fileName;
            }
            
            $sql .= " WHERE id_pedido = ?";
            $params[] = $id;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Historial
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Entrega', ?)");
            $detalle = "Estado de entrega cambiado a: $estadoEntrega";
            if ($metodoEntrega) $detalle .= " (Método: $metodoEntrega)";
            if ($receptor) $detalle .= ". Receptor: $receptor";
            $stmtH->execute([$id, $usuarioId, $detalle]);
            
            $db->commit();
            json_success(['mensaje' => 'Estado de entrega actualizado']);
            break;

        case 'preparar':
            // Marcar un Pedido de Insumo como "Preparado" para que pase a logística
            if (!tienePermiso('pedidos', 'gestionar')) json_error('Sin permiso', 403);
            
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_error('ID inválido', 400);
            
            $db->beginTransaction();
            
            // Verificar tipo
            $stmt = $db->prepare("SELECT tipo, estado FROM pedidos WHERE id_pedido = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            
            if (!$p) json_error('Pedido no encontrado', 404);
            if ($p['tipo'] !== 'Pedido Insumo') json_error('Solo los pedidos de insumo pueden marcarse como preparados', 400);
            
            $stmt = $db->prepare("UPDATE pedidos SET estado = 'Preparado', estado_entrega = 'Preparado', fecha_preparacion = NOW() WHERE id_pedido = ?");
            $stmt->execute([$id]);
            
            $stmtH = $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Preparado', 'Pedido de insumos preparado y listo para logística')");
            $stmtH->execute([$id, $usuarioId]);
            
            $db->commit();
            json_success(['mensaje' => 'Pedido marcado como preparado']);
            break;

        case 'obtener_entregas_pendientes':
            if (!tienePermiso('pedidos', 'ver_todos')) json_error('Sin permiso', 403);
            
            $stmt = $db->query("SELECT p.id_pedido, p.tipo, p.descripcion, p.solicitante_nombre, p.solicitante_apellido, 
                                       p.metodo_entrega, p.estado_entrega, p.fecha_creacion, s.nombre_sede, a.nombre_area
                                FROM pedidos p
                                JOIN sedes s ON p.id_sede = s.id_sede
                                LEFT JOIN areas a ON p.id_area = a.id_area
                                WHERE p.tipo = 'Pedido Insumo' AND p.estado_entrega != 'Entregado'
                                ORDER BY p.fecha_creacion DESC");
            $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_success(['entregas' => $entregas]);
            break;

        default:
            json_error('Acción no válida', 400);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    json_error($e->getMessage(), 500);
}
