<?php
require_once '../includes/config.php';

try {
    // 1. Verificar autenticación
    if (!estaAutenticado()) {
        json_error('No autenticado', 401);
    }
    
    // 2. Obtener y validar parámetros
    $id_internet = (int)($_GET['id_internet'] ?? 0);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
    
    if ($id_internet <= 0) {
        json_error('ID de servicio de Internet inválido', 400);
    }
    
    $db = conectarDB();
    
    // 3. Consultar tests
    $sql = "SELECT t.*, u.username as creador_nombre
            FROM sedes_internet_tests t
            LEFT JOIN usuarios u ON t.creado_por = u.id_usuario
            WHERE t.id_internet = ? 
            ORDER BY t.fecha_test DESC, t.id_test DESC";
            
    if ($limit > 0) {
        $sql .= " LIMIT " . $limit;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_internet]);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas para el JSON (opcional, pero buena práctica)
    foreach ($tests as &$test) {
        $test['fecha_test_formateada'] = date('d/m/Y', strtotime($test['fecha_test']));
        $test['fecha_creacion_formateada'] = date('d/m/Y H:i', strtotime($test['fecha_creacion']));
        // Agregar URL completa de la captura
        $test['captura_url'] = UPLOAD_BASE_URL . 'telecom/tests/' . htmlspecialchars($test['captura_pantalla']);
    }
    
    json_success($tests);
    
} catch (Exception $e) {
    Logger::error('Error al listar tests de velocidad', [
        'mensaje' => $e->getMessage(),
        'id_internet' => $id_internet ?? 0
    ]);
    json_error('Error interno al obtener los datos: ' . $e->getMessage(), 500);
}
