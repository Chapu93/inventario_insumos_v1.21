<?php
/**
 * SITIA - Endpoint AJAX para administración completa del catálogo de hardware
 * (Creación, Edición, Toggle de estado y Eliminación de modelos)
 * Solo accesible para Administradores y Super Administradores
 */

require_once __DIR__ . '/../includes/config.php';

// 1. Verificar autenticación
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// 2. Verificar rol (Solo Super Administrador o Administrador)
if (!tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador'])) {
    json_error('Acceso denegado: solo Administradores pueden gestionar el catálogo de hardware.', 403);
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // 3. Verificar CSRF (soportando JSON body, header X-CSRF-Token o $_POST)
    $tokenCSRF = $input['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf($tokenCSRF)) {
        json_error('Token CSRF inválido o expirado. Por favor recargue la página.', 403);
    }

    $accion = trim($input['accion'] ?? '');
    $tipoHardware = trim($input['tipo_hardware'] ?? '');

    if (!in_array($tipoHardware, ['motherboard', 'procesador'], true)) {
        json_error('Tipo de hardware inválido. Debe ser "motherboard" o "procesador".', 400);
    }

    $db = conectarDB();

    switch ($accion) {

        case 'guardar':
            $id = (int)($input['id'] ?? 0);
            $marca = trim($input['marca'] ?? '');
            $modelo = trim($input['modelo'] ?? '');
            $tipoRam = strtoupper(trim($input['tipo_ram'] ?? ''));
            $tipoEquipo = strtolower(trim($input['tipo_equipo'] ?? 'pc'));

            if (empty($marca) || mb_strlen($marca) < 2 || mb_strlen($marca) > 50) {
                json_error('La marca es obligatoria y debe tener entre 2 y 50 caracteres.', 400);
            }

            if (empty($modelo) || mb_strlen($modelo) < 2 || mb_strlen($modelo) > 100) {
                json_error('El modelo es obligatorio y debe tener entre 2 y 100 caracteres.', 400);
            }

            $ramsPermitidas = ['DDR5', 'DDR4', 'DDR3', 'DDR2', 'DDR', 'OTRO'];
            if (empty($tipoRam) || !in_array($tipoRam, $ramsPermitidas, true)) {
                json_error('Debe seleccionar un tipo de memoria RAM válido.', 400);
            }

            if ($tipoHardware === 'motherboard') {
                // Verificar duplicados (excluyendo el actual si es edición)
                $sqlDup = "SELECT id_mother FROM catalogo_motherboards WHERE LOWER(marca) = LOWER(?) AND LOWER(modelo) = LOWER(?)";
                $paramsDup = [$marca, $modelo];
                if ($id > 0) {
                    $sqlDup .= " AND id_mother != ?";
                    $paramsDup[] = $id;
                }
                $stmtDup = $db->prepare($sqlDup);
                $stmtDup->execute($paramsDup);
                if ($stmtDup->fetch()) {
                    json_error('Ya existe otra motherboard registrada con la misma marca y modelo.', 409);
                }

                if ($id > 0) {
                    // Editar
                    $stmtUpd = $db->prepare("UPDATE catalogo_motherboards SET marca = ?, modelo = ?, tipo_ram = ? WHERE id_mother = ?");
                    $stmtUpd->execute([$marca, $modelo, $tipoRam, $id]);

                    if (function_exists('registrarAuditoria')) {
                        registrarAuditoria('editar', 'catalogo_motherboards', "Modificado modelo Motherboard ID {$id}: {$marca} {$modelo} ({$tipoRam})");
                    }

                    json_success([
                        'id' => $id,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'tipo_ram' => $tipoRam,
                        'mensaje' => 'Modelo de motherboard actualizado correctamente.'
                    ]);
                } else {
                    // Crear
                    $stmtIns = $db->prepare("INSERT INTO catalogo_motherboards (marca, modelo, tipo_ram, activo, fecha_creacion) VALUES (?, ?, ?, 1, NOW())");
                    $stmtIns->execute([$marca, $modelo, $tipoRam]);
                    $idNuevo = (int)$db->lastInsertId();

                    if (function_exists('registrarAuditoria')) {
                        registrarAuditoria('crear', 'catalogo_motherboards', "Nuevo modelo Motherboard: {$marca} {$modelo} ({$tipoRam})");
                    }

                    json_success([
                        'id' => $idNuevo,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'tipo_ram' => $tipoRam,
                        'mensaje' => 'Modelo de motherboard registrado con éxito en el catálogo.'
                    ]);
                }

            } else {
                // Procesador
                if (!in_array($tipoEquipo, ['pc', 'notebook', 'ambos'], true)) {
                    $tipoEquipo = 'pc';
                }

                $sqlDup = "SELECT id_procesador FROM catalogo_procesadores WHERE LOWER(marca) = LOWER(?) AND LOWER(modelo) = LOWER(?) AND tipo_equipo = ?";
                $paramsDup = [$marca, $modelo, $tipoEquipo];
                if ($id > 0) {
                    $sqlDup .= " AND id_procesador != ?";
                    $paramsDup[] = $id;
                }
                $stmtDup = $db->prepare($sqlDup);
                $stmtDup->execute($paramsDup);
                if ($stmtDup->fetch()) {
                    json_error('Ya existe otro procesador registrado con la misma marca, modelo y tipo de equipo.', 409);
                }

                if ($id > 0) {
                    // Editar
                    $stmtUpd = $db->prepare("UPDATE catalogo_procesadores SET marca = ?, modelo = ?, tipo_ram = ?, tipo_equipo = ? WHERE id_procesador = ?");
                    $stmtUpd->execute([$marca, $modelo, $tipoRam, $tipoEquipo, $id]);

                    if (function_exists('registrarAuditoria')) {
                        registrarAuditoria('editar', 'catalogo_procesadores', "Modificado modelo Procesador ID {$id}: {$marca} {$modelo} ({$tipoRam} - {$tipoEquipo})");
                    }

                    json_success([
                        'id' => $id,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'tipo_ram' => $tipoRam,
                        'tipo_equipo' => $tipoEquipo,
                        'mensaje' => 'Modelo de procesador actualizado correctamente.'
                    ]);
                } else {
                    // Crear
                    $stmtIns = $db->prepare("INSERT INTO catalogo_procesadores (marca, modelo, tipo_ram, tipo_equipo, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, NOW())");
                    $stmtIns->execute([$marca, $modelo, $tipoRam, $tipoEquipo]);
                    $idNuevo = (int)$db->lastInsertId();

                    if (function_exists('registrarAuditoria')) {
                        registrarAuditoria('crear', 'catalogo_procesadores', "Nuevo modelo Procesador: {$marca} {$modelo} ({$tipoRam} - {$tipoEquipo})");
                    }

                    json_success([
                        'id' => $idNuevo,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'tipo_ram' => $tipoRam,
                        'tipo_equipo' => $tipoEquipo,
                        'mensaje' => 'Modelo de procesador registrado con éxito en el catálogo.'
                    ]);
                }
            }
            break;

        case 'toggle_estado':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                json_error('ID de modelo no válido.', 400);
            }

            if ($tipoHardware === 'motherboard') {
                $stmt = $db->prepare("SELECT id_mother, marca, modelo, activo FROM catalogo_motherboards WHERE id_mother = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) {
                    json_error('Motherboard no encontrada.', 404);
                }

                $nuevoEstado = $item['activo'] ? 0 : 1;
                $db->prepare("UPDATE catalogo_motherboards SET activo = ? WHERE id_mother = ?")->execute([$nuevoEstado, $id]);

                if (function_exists('registrarAuditoria')) {
                    $accionStr = $nuevoEstado ? 'activar' : 'desactivar';
                    registrarAuditoria($accionStr, 'catalogo_motherboards', "Estado de Motherboard ID {$id} ({$item['marca']} {$item['modelo']}) cambiado a " . ($nuevoEstado ? 'Activo' : 'Inactivo'));
                }

                json_success([
                    'id' => $id,
                    'activo' => $nuevoEstado,
                    'mensaje' => $nuevoEstado ? 'Modelo activado correctamente.' : 'Modelo desactivado (ya no aparecerá en listas de carga).'
                ]);

            } else {
                $stmt = $db->prepare("SELECT id_procesador, marca, modelo, activo FROM catalogo_procesadores WHERE id_procesador = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) {
                    json_error('Procesador no encontrado.', 404);
                }

                $nuevoEstado = $item['activo'] ? 0 : 1;
                $db->prepare("UPDATE catalogo_procesadores SET activo = ? WHERE id_procesador = ?")->execute([$nuevoEstado, $id]);

                if (function_exists('registrarAuditoria')) {
                    $accionStr = $nuevoEstado ? 'activar' : 'desactivar';
                    registrarAuditoria($accionStr, 'catalogo_procesadores', "Estado de Procesador ID {$id} ({$item['marca']} {$item['modelo']}) cambiado a " . ($nuevoEstado ? 'Activo' : 'Inactivo'));
                }

                json_success([
                    'id' => $id,
                    'activo' => $nuevoEstado,
                    'mensaje' => $nuevoEstado ? 'Modelo activado correctamente.' : 'Modelo desactivado (ya no aparecerá en listas de carga).'
                ]);
            }
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            $forzar = !empty($input['forzar']);

            if ($id <= 0) {
                json_error('ID de modelo no válido.', 400);
            }

            if ($tipoHardware === 'motherboard') {
                $stmt = $db->prepare("SELECT id_mother, marca, modelo FROM catalogo_motherboards WHERE id_mother = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) {
                    json_error('Motherboard no encontrada.', 404);
                }

                $marca = trim($item['marca']);
                $modelo = trim($item['modelo']);
                $completo = trim("{$marca} {$modelo}");

                // Comprobar uso en pcs_completas
                $stmtUso = $db->prepare("SELECT COUNT(*) FROM pcs_completas WHERE LOWER(TRIM(mother)) = LOWER(?) OR LOWER(TRIM(mother)) = LOWER(?)");
                $stmtUso->execute([$modelo, $completo]);
                $cantidadUso = (int)$stmtUso->fetchColumn();

                if ($cantidadUso > 0 && !$forzar) {
                    json_error("Este modelo está asignado a {$cantidadUso} PC(s) registrada(s) en el inventario. Se recomienda desactivarlo para que no aparezca en nuevas cargas.", 409, [
                        'en_uso' => true,
                        'cantidad_equipos' => $cantidadUso,
                        'modelo' => $completo
                    ]);
                }

                // Proceder a eliminar
                $db->prepare("DELETE FROM catalogo_motherboards WHERE id_mother = ?")->execute([$id]);

                if (function_exists('registrarAuditoria')) {
                    registrarAuditoria('eliminar', 'catalogo_motherboards', "Eliminado modelo de Motherboard ID {$id} ({$completo})");
                }

                json_success([
                    'id' => $id,
                    'mensaje' => 'Modelo de motherboard eliminado correctamente del catálogo.'
                ]);

            } else {
                $stmt = $db->prepare("SELECT id_procesador, marca, modelo FROM catalogo_procesadores WHERE id_procesador = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) {
                    json_error('Procesador no encontrado.', 404);
                }

                $marca = trim($item['marca']);
                $modelo = trim($item['modelo']);
                $completo = trim("{$marca} {$modelo}");

                // Comprobar uso en pcs_completas y notebooks
                $stmtUsoPc = $db->prepare("SELECT COUNT(*) FROM pcs_completas WHERE LOWER(TRIM(procesador)) = LOWER(?) OR LOWER(TRIM(procesador)) = LOWER(?)");
                $stmtUsoPc->execute([$modelo, $completo]);
                $cantPc = (int)$stmtUsoPc->fetchColumn();

                $stmtUsoNb = $db->prepare("SELECT COUNT(*) FROM notebooks WHERE LOWER(TRIM(procesador)) = LOWER(?) OR LOWER(TRIM(procesador)) = LOWER(?)");
                $stmtUsoNb->execute([$modelo, $completo]);
                $cantNb = (int)$stmtUsoNb->fetchColumn();

                $totalUso = $cantPc + $cantNb;

                if ($totalUso > 0 && !$forzar) {
                    json_error("Este procesador está asignado a {$totalUso} equipo(s) ({$cantPc} PCs y {$cantNb} Notebooks). Se recomienda desactivarlo para que no aparezca en nuevas cargas.", 409, [
                        'en_uso' => true,
                        'cantidad_equipos' => $totalUso,
                        'modelo' => $completo
                    ]);
                }

                $db->prepare("DELETE FROM catalogo_procesadores WHERE id_procesador = ?")->execute([$id]);

                if (function_exists('registrarAuditoria')) {
                    registrarAuditoria('eliminar', 'catalogo_procesadores', "Eliminado modelo de Procesador ID {$id} ({$completo})");
                }

                json_success([
                    'id' => $id,
                    'mensaje' => 'Modelo de procesador eliminado correctamente del catálogo.'
                ]);
            }
            break;

        default:
            json_error('Acción no reconocida.', 400);
    }

} catch (Exception $e) {
    Logger::error('Error en catalogo_acciones.php', ['error' => $e->getMessage()]);
    json_error('Error interno del servidor: ' . $e->getMessage(), 500);
}
