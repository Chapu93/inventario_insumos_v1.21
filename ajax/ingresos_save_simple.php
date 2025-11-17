<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verify_csrf($input['_csrf'] ?? '')) {
        json_error('CSRF inválido', 403);
    }
    
    $tipo_ingreso = trim($input['tipo_ingreso'] ?? '');
    $nro_referencia = trim($input['nro_referencia'] ?? '');
    $fecha_finalizacion = !empty($input['fecha_finalizacion']) ? $input['fecha_finalizacion'] : null;
    $descripcion = $input['descripcion'] ?? null;
    
    Logger::info("Guardar ingreso", [
        'fecha' => $fecha_finalizacion,
        'tipo' => $tipo_ingreso,
        'referencia' => $nro_referencia
    ]);
    
    // Validaciones
    if (empty($tipo_ingreso)) {
        json_error('El tipo de ingreso es obligatorio', 400);
    }
    
    if (empty($nro_referencia)) {
        json_error('El número de referencia es obligatorio', 400);
    }
    
    $tiposValidos = ['fondos', 'compra_directa', 'licitacion', 'otros'];
    if (!in_array($tipo_ingreso, $tiposValidos)) {
        json_error('Tipo de ingreso no válido', 400);
    }
    
    $db = conectarDB();
    
    // Verificar que no exista el número de referencia con el mismo tipo
    $stmt = $db->prepare('SELECT id_ingreso FROM ingresos WHERE tipo_ingreso = ? AND nro_referencia = ?');
    $stmt->execute([$tipo_ingreso, $nro_referencia]);
    if ($stmt->fetch()) {
        json_error('Ya existe un ingreso de este tipo con ese número de referencia', 409);
    }
    
    // Insertar ingreso
    $stmt = $db->prepare('INSERT INTO ingresos (tipo_ingreso, nro_referencia, fecha_finalizacion, descripcion) VALUES (?, ?, ?, ?)');
    $stmt->execute([$tipo_ingreso, $nro_referencia, $fecha_finalizacion, $descripcion]);
    
    $id = $db->lastInsertId();
    
    // Verificar fecha guardada
    $verificar = $db->prepare('SELECT DATE(fecha_finalizacion) as fecha FROM ingresos WHERE id_ingreso = ?');
    $verificar->execute([$id]);
    $fechaGuardada = $verificar->fetchColumn();
    
    Logger::info("Ingreso creado", [
        'id' => $id,
        'fecha_enviada' => $fecha_finalizacion,
        'fecha_guardada' => $fechaGuardada,
        'coinciden' => $fecha_finalizacion === $fechaGuardada
    ]);
    
    if ($fecha_finalizacion && $fechaGuardada && $fecha_finalizacion !== $fechaGuardada) {
        Logger::warning("Discrepancia en fecha de ingreso", [
            'id' => $id,
            'enviada' => $fecha_finalizacion,
            'guardada' => $fechaGuardada
        ]);
    }
    
    json_success([
        'id' => $id,
        'message' => 'Ingreso creado correctamente'
    ]);
    
} catch (Exception $e) {
    Logger::error("Error al guardar ingreso", [
        'mensaje' => $e->getMessage(),
        'tipo' => $tipo_ingreso ?? 'desconocido',
        'referencia' => $nro_referencia ?? 'desconocido'
    ]);
    json_error('Error al guardar: ' . $e->getMessage(), 500);
}
?>
