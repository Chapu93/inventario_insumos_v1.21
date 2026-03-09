<?php
/**
 * Endpoint para buscar insumos asignados (para relacionar con pedidos)
 * Retorna insumos que tienen asignación activa o están disponibles
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

try {
    $db = conectarDB();
    
    $busqueda = trim($_GET['q'] ?? '');
    $soloAsignados = ($_GET['solo_asignados'] ?? '0') === '1';
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
    
    if (strlen($busqueda) < 2) {
        json_success(['items' => [], 'mensaje' => 'Ingrese al menos 2 caracteres']);
        exit;
    }
    
    $like = '%' . $busqueda . '%';
    
    if ($soloAsignados) {
        // Buscar solo insumos con asignación activa
        // Excluir insumos que ya tienen un pedido pendiente o en proceso
        $sql = "SELECT DISTINCT
                    i.id_insumo,
                    i.nombre_insumo,
                    i.tipo_insumo,
                    i.numero_serie,
                    i.id_fisico,
                    i.id_patrimonio,
                    i.estado,
                    r.numero_remito,
                    r.nombre_persona_asignada,
                    r.apellido_persona_asignada,
                    s.nombre_sede,
                    a.nombre_area
                FROM insumos i
                INNER JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
                INNER JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
                LEFT JOIN sedes s ON r.id_sede = s.id_sede
                LEFT JOIN areas a ON r.id_area = a.id_area
                WHERE i.estado = 'Asignado'
                  AND NOT EXISTS (
                    SELECT 1 FROM pedidos p 
                    WHERE p.id_insumo_relacionado = i.id_insumo 
                    AND p.estado IN ('Pendiente', 'En Proceso')
                  )
                  AND (
                    i.tipo_insumo LIKE ?
                    OR i.nombre_insumo LIKE ? 
                    OR i.numero_serie LIKE ? 
                    OR i.id_fisico LIKE ?
                    OR i.id_patrimonio LIKE ?
                    OR CONCAT(r.nombre_persona_asignada, ' ', r.apellido_persona_asignada) LIKE ?
                  )
                ORDER BY i.nombre_insumo, i.tipo_insumo
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
    } else {
        // Buscar todos los insumos (asignados o disponibles)
        $sql = "SELECT 
                    i.id_insumo,
                    i.nombre_insumo,
                    i.tipo_insumo,
                    i.numero_serie,
                    i.id_fisico,
                    i.id_patrimonio,
                    i.estado,
                    r.numero_remito,
                    r.nombre_persona_asignada,
                    r.apellido_persona_asignada,
                    s.nombre_sede,
                    a.nombre_area
                FROM insumos i
                LEFT JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
                LEFT JOIN remitos r ON rd.id_remito = r.id_remito AND r.estado = 'Activa'
                LEFT JOIN sedes s ON r.id_sede = s.id_sede OR i.id_sede_actual = s.id_sede
                LEFT JOIN areas a ON r.id_area = a.id_area
                WHERE i.estado != 'De Baja'
                  AND i.tipo_insumo != 'Varios'
                  AND (
                    i.tipo_insumo LIKE ?
                    OR i.nombre_insumo LIKE ? 
                    OR i.numero_serie LIKE ? 
                    OR i.id_fisico LIKE ?
                    OR i.id_patrimonio LIKE ?
                    OR CONCAT(COALESCE(r.nombre_persona_asignada,''), ' ', COALESCE(r.apellido_persona_asignada,'')) LIKE ?
                  )
                ORDER BY i.estado DESC, i.nombre_insumo
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
    }
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear para Select2
    $items = array_map(function($row) {
        $texto = $row['tipo_insumo'];
        if (!empty($row['nombre_insumo'])) {
            $texto .= ' - ' . $row['nombre_insumo'];
        }
        if (!empty($row['numero_serie'])) {
            $texto .= ' (S/N: ' . $row['numero_serie'] . ')';
        } elseif (!empty($row['id_fisico'])) {
            $texto .= ' (ID: ' . $row['id_fisico'] . ')';
        }
        
        $asignado = '';
        if (!empty($row['nombre_persona_asignada'])) {
            $asignado = $row['nombre_persona_asignada'] . ' ' . $row['apellido_persona_asignada'];
            if (!empty($row['nombre_sede'])) {
                $asignado .= ' (' . $row['nombre_sede'] . ')';
            }
        }
        
        return [
            'id' => $row['id_insumo'],
            'text' => $texto,
            'tipo' => $row['tipo_insumo'],
            'nombre' => $row['nombre_insumo'],
            'numero_serie' => $row['numero_serie'],
            'estado' => $row['estado'],
            'asignado_a' => $asignado,
            'sede' => $row['nombre_sede'] ?? '',
            'area' => $row['nombre_area'] ?? '',
            'remito' => $row['numero_remito'] ?? ''
        ];
    }, $resultados);
    
    json_success(['items' => $items, 'total' => count($items)]);
    
} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}
