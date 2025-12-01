<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;

if ($sedeId <= 0) {
    json_error('sede_id requerido', 400);
}
try {
    $db = conectarDB();
    // Para tipo Varios, mostrar total disponible (oficina + depósito)
    $sql = "SELECT id_insumo, nombre_insumo, tipo_insumo, numero_serie, id_fisico, 
                   cantidad,
                   cantidad_oficina, 
                   cantidad_deposito
            FROM insumos
            WHERE estado='Disponible'
              AND (id_sede_actual = ? OR id_sede_actual IS NULL)
              AND (tipo_insumo <> 'Varios' OR cantidad > 0)
            ORDER BY nombre_insumo";
    $stmt = $db->prepare($sql);
    $stmt->execute([$sedeId]);
    $rows = $stmt->fetchAll();
    $data = array_map(function($r){
        $texto = $r['nombre_insumo'];
        if ($r['tipo_insumo'] === 'Varios') {
            $texto .= ' (Cantidad: ' . (int)$r['cantidad'] . ')';
        } else {
            if (!empty($r['numero_serie'])) { $texto .= ' (S/N: ' . $r['numero_serie'] . ')'; }
            if (!empty($r['id_fisico'])) { $texto .= ' (ID: ' . $r['id_fisico'] . ')'; }
        }
        return [
            'id' => (int)$r['id_insumo'],
            'nombre' => $texto,
            'tipo' => $r['tipo_insumo'],
            'max' => ($r['tipo_insumo'] === 'Varios') ? (int)$r['cantidad'] : 1
        ];
    }, $rows);
    Logger::debug('Insumos disponibles cargados', ['sede_id' => $sedeId, 'count' => count($data)]);
    
    json_success($data);
    
} catch (Exception $e) {
    Logger::error('Error al cargar insumos disponibles', [
        'mensaje' => $e->getMessage(),
        'sede_id' => $sedeId
    ]);
    json_error($e->getMessage(), 500);
}