<?php
/**
 * SITIA - Endpoint AJAX para guardar nuevos modelos en el catálogo de hardware
 * Solo accesible para Administradores y Super Administradores
 */

require_once __DIR__ . '/../includes/config.php';

// 1. Verificar autenticación
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

// 2. Verificar rol restringido (Solo Super Administrador o Administrador)
if (!tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador'])) {
    json_error('Acceso denegado: solo Administradores y Super Administradores pueden registrar nuevos modelos en el catálogo', 403);
}

try {
    // Obtener parámetros desde JSON o POST estándar
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // 3. Verificar CSRF (soportando JSON body, header X-CSRF-Token o $_POST)
    $tokenCSRF = $input['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf($tokenCSRF)) {
        json_error('Token CSRF inválido o expirado. Por favor, recargue la página.', 403);
    }

    $tipoHardware = trim($input['tipo_hardware'] ?? '');
    $marca = trim($input['marca'] ?? '');
    $modelo = trim($input['modelo'] ?? '');
    $tipoRam = strtoupper(trim($input['tipo_ram'] ?? ''));
    $tipoEquipo = strtolower(trim($input['tipo_equipo'] ?? 'pc'));

    // Validar tipo de hardware
    if (!in_array($tipoHardware, ['motherboard', 'procesador'], true)) {
        json_error('Tipo de hardware inválido. Debe ser "motherboard" o "procesador".', 400);
    }

    // Validar campos obligatorios
    if (empty($marca) || mb_strlen($marca) < 2 || mb_strlen($marca) > 50) {
        json_error('La marca es obligatoria y debe tener entre 2 y 50 caracteres.', 400);
    }

    if (empty($modelo) || mb_strlen($modelo) < 2 || mb_strlen($modelo) > 100) {
        json_error('El modelo es obligatorio y debe tener entre 2 y 100 caracteres.', 400);
    }

    // Normalizar tipos de RAM aceptados
    $ramsPermitidas = ['DDR5', 'DDR4', 'DDR3', 'DDR2', 'DDR', 'OTRO'];
    if (empty($tipoRam) || !in_array($tipoRam, $ramsPermitidas, true)) {
        json_error('Debe seleccionar un tipo de memoria RAM válido (DDR5, DDR4, DDR3, DDR2, DDR o Otro).', 400);
    }

    $db = conectarDB();

    if ($tipoHardware === 'motherboard') {
        // Verificar si ya existe en catalogo_motherboards
        $stmtCheck = $db->prepare("SELECT id_mother, marca, modelo, tipo_ram, activo 
                                   FROM catalogo_motherboards 
                                   WHERE LOWER(marca) = LOWER(?) AND LOWER(modelo) = LOWER(?)");
        $stmtCheck->execute([$marca, $modelo]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            // Si ya existe pero estaba inactivo, reactivarlo
            if (!$existente['activo']) {
                $db->prepare("UPDATE catalogo_motherboards SET activo = 1 WHERE id_mother = ?")->execute([$existente['id_mother']]);
            }
            $valorCompleto = trim($existente['marca'] . ' ' . $existente['modelo']);
            json_success([
                'id' => (int)$existente['id_mother'],
                'tipo_hardware' => 'motherboard',
                'marca' => $existente['marca'],
                'modelo' => $existente['modelo'],
                'tipo_ram' => $existente['tipo_ram'],
                'valor_completo' => $valorCompleto,
                'etiqueta' => $valorCompleto,
                'mensaje' => 'El modelo ya se encuentra registrado en el catálogo.'
            ]);
        }

        // Insertar nuevo registro
        $stmtIns = $db->prepare("INSERT INTO catalogo_motherboards (marca, modelo, tipo_ram, activo, fecha_creacion) 
                                 VALUES (?, ?, ?, 1, NOW())");
        $stmtIns->execute([$marca, $modelo, $tipoRam]);
        $idNuevo = (int)$db->lastInsertId();

        $valorCompleto = trim("{$marca} {$modelo}");

        // Registrar auditoría
        if (function_exists('registrarAuditoria')) {
            registrarAuditoria('crear', 'catalogo_motherboards', "Nuevo modelo de Motherboard: {$valorCompleto} ({$tipoRam})");
        }

        Logger::info('Nuevo modelo de Motherboard registrado', [
            'id' => $idNuevo,
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo_ram' => $tipoRam,
            'usuario_id' => obtenerUsuarioId()
        ]);

        json_success([
            'id' => $idNuevo,
            'tipo_hardware' => 'motherboard',
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo_ram' => $tipoRam,
            'valor_completo' => $valorCompleto,
            'etiqueta' => $valorCompleto,
            'mensaje' => 'Motherboard registrada con éxito en el catálogo.'
        ]);

    } else {
        // Procesador
        if (!in_array($tipoEquipo, ['pc', 'notebook', 'ambos'], true)) {
            $tipoEquipo = 'pc';
        }

        $stmtCheck = $db->prepare("SELECT id_procesador, marca, modelo, tipo_ram, tipo_equipo, activo 
                                   FROM catalogo_procesadores 
                                   WHERE LOWER(marca) = LOWER(?) AND LOWER(modelo) = LOWER(?) AND tipo_equipo = ?");
        $stmtCheck->execute([$marca, $modelo, $tipoEquipo]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if (!$existente['activo']) {
                $db->prepare("UPDATE catalogo_procesadores SET activo = 1 WHERE id_procesador = ?")->execute([$existente['id_procesador']]);
            }
            $valorCompleto = trim($existente['marca'] . ' ' . $existente['modelo']);
            json_success([
                'id' => (int)$existente['id_procesador'],
                'tipo_hardware' => 'procesador',
                'marca' => $existente['marca'],
                'modelo' => $existente['modelo'],
                'tipo_ram' => $existente['tipo_ram'],
                'tipo_equipo' => $existente['tipo_equipo'],
                'valor_completo' => $valorCompleto,
                'etiqueta' => $valorCompleto,
                'mensaje' => 'El procesador ya se encuentra registrado en el catálogo.'
            ]);
        }

        $stmtIns = $db->prepare("INSERT INTO catalogo_procesadores (marca, modelo, tipo_ram, tipo_equipo, activo, fecha_creacion) 
                                 VALUES (?, ?, ?, ?, 1, NOW())");
        $stmtIns->execute([$marca, $modelo, $tipoRam, $tipoEquipo]);
        $idNuevo = (int)$db->lastInsertId();

        $valorCompleto = trim("{$marca} {$modelo}");

        if (function_exists('registrarAuditoria')) {
            registrarAuditoria('crear', 'catalogo_procesadores', "Nuevo modelo de Procesador: {$valorCompleto} ({$tipoRam} - {$tipoEquipo})");
        }

        Logger::info('Nuevo modelo de Procesador registrado', [
            'id' => $idNuevo,
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo_ram' => $tipoRam,
            'tipo_equipo' => $tipoEquipo,
            'usuario_id' => obtenerUsuarioId()
        ]);

        json_success([
            'id' => $idNuevo,
            'tipo_hardware' => 'procesador',
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo_ram' => $tipoRam,
            'tipo_equipo' => $tipoEquipo,
            'valor_completo' => $valorCompleto,
            'etiqueta' => $valorCompleto,
            'mensaje' => 'Procesador registrado con éxito en el catálogo.'
        ]);
    }

} catch (Exception $e) {
    Logger::error('Error al guardar modelo en catálogo de hardware', ['error' => $e->getMessage()]);
    json_error('Error interno del servidor al registrar el modelo: ' . $e->getMessage(), 500);
}
