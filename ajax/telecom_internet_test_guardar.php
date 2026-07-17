<?php
require_once '../includes/config.php';
require_once '../includes/validar_archivo.php';

try {
    // 1. Verificar autenticación y permisos
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }
    
    if (!tienePermiso('telecom', 'editar')) {
        json_error('Sin permisos para modificar telecomunicaciones', 403);
    }
    
    // 2. Verificar CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
        json_error('Petición inválida o token CSRF expirado', 403);
    }
    
    // 3. Sanitizar inputs
    $id_test = (int)($_POST['id_test'] ?? 0);
    $id_internet = (int)($_POST['id_internet'] ?? 0);
    $fecha_test = trim($_POST['fecha_test'] ?? '');
    $velocidad_bajada = (int)($_POST['velocidad_bajada'] ?? 0);
    $velocidad_subida = (int)($_POST['velocidad_subida'] ?? 0);
    $ping = (isset($_POST['ping']) && $_POST['ping'] !== '') ? (int)$_POST['ping'] : null;
    
    // 4. Validar campos requeridos
    if ($id_internet <= 0) {
        json_error('ID de servicio de Internet inválido', 400);
    }
    if (empty($fecha_test)) {
        json_error('La fecha del test es obligatoria', 400);
    }
    if ($velocidad_bajada <= 0 || $velocidad_subida <= 0) {
        json_error('Las velocidades de bajada y subida deben ser mayores a 0 Mbps', 400);
    }
    
    $db = conectarDB();
    
    // Verificar que exista el servicio de internet
    $stmtSvc = $db->prepare("SELECT id_internet, id_sede FROM sedes_internet WHERE id_internet = ?");
    $stmtSvc->execute([$id_internet]);
    $servicio = $stmtSvc->fetch();
    if (!$servicio) {
        json_error('El servicio de Internet especificado no existe', 404);
    }
    
    $uploadDir = UPLOAD_BASE_DIR . 'telecom/tests/';
    $nombreArchivo = null;
    $subioArchivo = isset($_FILES['captura']) && $_FILES['captura']['error'] !== UPLOAD_ERR_NO_FILE;
    
    // Si es creación, el archivo es obligatorio
    if ($id_test === 0 && !$subioArchivo) {
        json_error('Debe adjuntar la captura de pantalla del test de velocidad', 400);
    }
    
    // 5. Procesar archivo si se subió uno
    if ($subioArchivo) {
        $validacion = validarArchivoImagen($_FILES['captura']);
        if (!$validacion['valido']) {
            json_error($validacion['error'], 400);
        }
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = $validacion['extension'];
        $nombreArchivo = 'test_' . $id_internet . '_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;
        
        if (!move_uploaded_file($_FILES['captura']['tmp_name'], $rutaDestino)) {
            json_error('Error al guardar la captura de pantalla en el servidor', 500);
        }
    }
    
    $db->beginTransaction();
    
    if ($id_test > 0) {
        // --- EDICIÓN ---
        // Obtener datos anteriores
        $stmtOld = $db->prepare("SELECT * FROM sedes_internet_tests WHERE id_test = ?");
        $stmtOld->execute([$id_test]);
        $oldData = $stmtOld->fetch();
        
        if (!$oldData) {
            $db->rollBack();
            if ($nombreArchivo && file_exists($uploadDir . $nombreArchivo)) {
                unlink($uploadDir . $nombreArchivo);
            }
            json_error('Test de velocidad no encontrado', 404);
        }
        
        // Si se subió un nuevo archivo, eliminar el viejo
        if ($subioArchivo) {
            $archivoViejo = $uploadDir . $oldData['captura_pantalla'];
            if (!empty($oldData['captura_pantalla']) && file_exists($archivoViejo)) {
                @unlink($archivoViejo);
            }
            
            $sql = "UPDATE sedes_internet_tests SET 
                        fecha_test = ?, 
                        velocidad_bajada = ?, 
                        velocidad_subida = ?, 
                        ping = ?, 
                        captura_pantalla = ? 
                    WHERE id_test = ?";
            $stmtUpdate = $db->prepare($sql);
            $stmtUpdate->execute([$fecha_test, $velocidad_bajada, $velocidad_subida, $ping, $nombreArchivo, $id_test]);
        } else {
            $sql = "UPDATE sedes_internet_tests SET 
                        fecha_test = ?, 
                        velocidad_bajada = ?, 
                        velocidad_subida = ?, 
                        ping = ? 
                    WHERE id_test = ?";
            $stmtUpdate = $db->prepare($sql);
            $stmtUpdate->execute([$fecha_test, $velocidad_bajada, $velocidad_subida, $ping, $id_test]);
        }
        
        // Auditoría
        registrarAuditoria(
            'editar_internet_test',
            'sedes_internet',
            "Test de velocidad editado para servicio #{$id_internet}. Bajada: {$velocidad_bajada} Mbps, Subida: {$velocidad_subida} Mbps",
            'sedes_internet',
            $id_internet,
            $oldData,
            [
                'fecha_test' => $fecha_test,
                'velocidad_bajada' => $velocidad_bajada,
                'velocidad_subida' => $velocidad_subida,
                'ping' => $ping,
                'captura_pantalla' => $nombreArchivo ?: $oldData['captura_pantalla']
            ]
        );
        
        $mensaje = 'Test de velocidad modificado exitosamente';
        
    } else {
        // --- CREACIÓN ---
        // Lógica de rotación: si ya hay 3 o más tests, eliminar el más antiguo
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM sedes_internet_tests WHERE id_internet = ?");
        $stmtCount->execute([$id_internet]);
        $cantidadActual = (int)$stmtCount->fetchColumn();
        
        if ($cantidadActual >= 3) {
            // Buscar el más antiguo (ordenado por fecha de test ascendente, luego ID ascendente)
            $stmtOldest = $db->prepare("SELECT id_test, captura_pantalla FROM sedes_internet_tests WHERE id_internet = ? ORDER BY fecha_test ASC, id_test ASC LIMIT 1");
            $stmtOldest->execute([$id_internet]);
            $oldest = $stmtOldest->fetch();
            
            if ($oldest) {
                // Eliminar archivo físico
                $archivoViejo = $uploadDir . $oldest['captura_pantalla'];
                if (!empty($oldest['captura_pantalla']) && file_exists($archivoViejo)) {
                    @unlink($archivoViejo);
                }
                
                // Borrar de base de datos
                $stmtDel = $db->prepare("DELETE FROM sedes_internet_tests WHERE id_test = ?");
                $stmtDel->execute([$oldest['id_test']]);
            }
        }
        
        // Insertar nuevo test
        $sql = "INSERT INTO sedes_internet_tests (id_internet, fecha_test, velocidad_bajada, velocidad_subida, ping, captura_pantalla, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $db->prepare($sql);
        $stmtInsert->execute([$id_internet, $fecha_test, $velocidad_bajada, $velocidad_subida, $ping, $nombreArchivo, obtenerUsuarioId()]);
        
        $nuevoId = $db->lastInsertId();
        
        // Auditoría
        registrarAuditoria(
            'crear_internet_test',
            'sedes_internet',
            "Nuevo test de velocidad registrado para servicio #{$id_internet}. Bajada: {$velocidad_bajada} Mbps, Subida: {$velocidad_subida} Mbps",
            'sedes_internet',
            $id_internet,
            null,
            [
                'id_test' => $nuevoId,
                'fecha_test' => $fecha_test,
                'velocidad_bajada' => $velocidad_bajada,
                'velocidad_subida' => $velocidad_subida,
                'ping' => $ping,
                'captura_pantalla' => $nombreArchivo
            ]
        );
        
        $mensaje = 'Test de velocidad registrado exitosamente';
    }
    
    $db->commit();
    
    json_success([
        'mensaje' => $mensaje,
        'id_internet' => $id_internet
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Limpieza de archivo subido si falla la base de datos
    if (isset($rutaDestino) && file_exists($rutaDestino)) {
        @unlink($rutaDestino);
    }
    
    Logger::error('Error al guardar test de velocidad', [
        'mensaje' => $e->getMessage(),
        'id_internet' => $id_internet ?? 0,
        'id_test' => $id_test ?? 0
    ]);
    
    json_error('Error al procesar la solicitud: ' . $e->getMessage(), 500);
}
