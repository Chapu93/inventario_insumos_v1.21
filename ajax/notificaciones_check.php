<?php
// Abrir la sesión en modo SOLO LECTURA antes de que config.php/auth.php la abra en modo escritura.
// Esto evita que el polling periódico de notificaciones renueve el timestamp de la sesión,
// lo que causaría que el usuario permanezca logueado indefinidamente.
if (session_status() === PHP_SESSION_NONE) {
    session_start(['read_and_close' => true]);
}
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$usuarioId = obtenerUsuarioId();

try {
    $db = conectarDB();
    
    // 1. Obtener notificaciones NO MOSTRADAS (para alertas Toast)
    $stmtUnshown = $db->prepare(
        "SELECT id_notificacion, tipo, titulo, mensaje, url, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') AS fecha
         FROM notificaciones 
         WHERE id_usuario = ? AND mostrada = 0 
         ORDER BY id_notificacion ASC LIMIT 10"
    );
    $stmtUnshown->execute([$usuarioId]);
    $unshown = $stmtUnshown->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener conteo de no leídas para el badge de la campana
    $stmtUnreadCount = $db->prepare(
        "SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0"
    );
    $stmtUnreadCount->execute([$usuarioId]);
    $unreadCount = (int)$stmtUnreadCount->fetchColumn();

    // 3. Obtener las últimas 7 notificaciones recientes para el dropdown de la campana
    $stmtBell = $db->prepare(
        "SELECT id_notificacion, tipo, titulo, mensaje, url, leida, DATE_FORMAT(fecha_creacion, '%d/%m/%H:%i') AS fecha
         FROM notificaciones 
         WHERE id_usuario = ? 
         ORDER BY id_notificacion DESC LIMIT 7"
    );
    $stmtBell->execute([$usuarioId]);
    $bellItems = $stmtBell->fetchAll(PDO::FETCH_ASSOC);

    // 4. Verificar si es la primera carga tras el login para el resumen de bienvenida
    $resumenLogin = null;
    if (!empty($_SESSION['mostrar_bienvenida_login'])) {
        unset($_SESSION['mostrar_bienvenida_login']); // Solo 1 vez por sesión

        // Contar tareas pendientes asignadas directamente
        $stmtPropias = $db->prepare(
            "SELECT COUNT(*) FROM tareas_internas WHERE asignado_a = ? AND estado != 'Completada'"
        );
        $stmtPropias->execute([$usuarioId]);
        $propias = (int)$stmtPropias->fetchColumn();

        // Contar tareas colaborativas pendientes
        $stmtColab = $db->prepare(
            "SELECT COUNT(*) FROM tareas_internas WHERE es_colaborativa = 1 AND estado != 'Completada'"
        );
        $stmtColab->execute();
        $colaborativas = (int)$stmtColab->fetchColumn();

        // Contar pedidos pendientes asignados al usuario (columna correcta: asignado_a)
        $stmtPedidos = $db->prepare(
            "SELECT COUNT(*) FROM pedidos WHERE asignado_a = ? AND estado NOT IN ('Completado', 'Rechazado', 'Sin Stock', 'De Baja')"
        );
        $stmtPedidos->execute([$usuarioId]);
        $pedidosPropios = (int)$stmtPedidos->fetchColumn();

        // Obtener la lista de tareas/pedidos no completados para el resumen
        $stmtListaTareas = $db->prepare(
            "SELECT 'tarea' AS tipo_item, id_tarea AS id, titulo, estado 
             FROM tareas_internas 
             WHERE (asignado_a = ? OR es_colaborativa = 1) AND estado != 'Completada'
             ORDER BY id_tarea DESC LIMIT 5"
        );
        $stmtListaTareas->execute([$usuarioId]);
        $itemsPendientes = $stmtListaTareas->fetchAll(PDO::FETCH_ASSOC);

        $resumenLogin = [
            'propias' => $propias + $pedidosPropios,
            'colaborativas' => $colaborativas,
            'total' => $propias + $pedidosPropios + $colaborativas,
            'items' => $itemsPendientes,
            'usuario_nombre' => $_SESSION['nombre_completo'] ?? 'Usuario'
        ];
    }

    json_success([
        'unshown' => $unshown,
        'unread_count' => $unreadCount,
        'bell_items' => $bellItems,
        'resumen_login' => $resumenLogin
    ]);

} catch (Exception $e) {
    Logger::error("Error en notificaciones_check", ['error' => $e->getMessage()]);
    json_error('Error al consultar notificaciones', 500);
}
