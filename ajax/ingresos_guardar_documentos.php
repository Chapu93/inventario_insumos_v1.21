<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'editar')) {
    json_error('No tienes permisos', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

// Verificar CSRF
if (!verify_csrf($_POST['_csrf'] ?? '')) {
    json_error('Token CSRF inválido', 403);
}

try {
    // Obtener ID del ingreso de la URL
    $idIngreso = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$idIngreso) {
        json_error('ID de ingreso inválido', 400);
    }
    
    $db = conectarDB();
    
    // Verificar que el ingreso existe
    $stmt = $db->prepare('SELECT id_ingreso FROM ingresos WHERE id_ingreso = ?');
    $stmt->execute([$idIngreso]);
    if (!$stmt->fetch()) {
        json_error('Ingreso no encontrado', 404);
    }
    
    $documentosGuardados = [];
    $erroresCarga = [];
    $usuarioId = obtenerUsuarioId();
    $uploadDir = UPLOAD_BASE_DIR . 'ingresos/';
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $documentosGuardados = [];
    
    // Procesar remito
    if (!empty($_FILES['archivo_remito']['name'])) {
        $resultado = procesarArchivoAdjunto($_FILES['archivo_remito'], $idIngreso, 'remito', $uploadDir, $db, $usuarioId);
        if ($resultado['success']) {
            $documentosGuardados['remito'] = $resultado;
        } else {
            $erroresCarga[] = "Remito: " . $resultado['error'];
            Logger::warning("Error al cargar remito", ['error' => $resultado['error']]);
        }
    }
    
    // Procesar documentación
    if (!empty($_FILES['archivo_documentacion']['name'])) {
        $resultado = procesarArchivoAdjunto($_FILES['archivo_documentacion'], $idIngreso, 'documentacion', $uploadDir, $db, $usuarioId);
        if ($resultado['success']) {
            $documentosGuardados['documentacion'] = $resultado;
        } else {
            $erroresCarga[] = "Documentación: " . $resultado['error'];
            Logger::warning("Error al cargar documentación", ['error' => $resultado['error']]);
        }
    }
    
    if (empty($documentosGuardados) && !empty($_FILES)) {
        json_error('No se pudo guardar ninguno de los archivos: ' . implode(', ', $erroresCarga), 400);
    }

    json_success([
        'id_ingreso' => $idIngreso,
        'documentos' => $documentosGuardados,
        'errores_archivos' => $erroresCarga,
        'message' => 'Documentos procesados' . (empty($erroresCarga) ? ' correctamente' : '. Algunos archivos fallaron: ' . implode(', ', $erroresCarga))
    ]);
    
} catch (Exception $e) {
    Logger::error("Error al guardar documentos de ingreso", [
        'mensaje' => $e->getMessage(),
        'id_ingreso' => $_GET['id'] ?? 'no_disponible'
    ]);
    json_error('Error al guardar documentos: ' . $e->getMessage(), 500);
}
?>
