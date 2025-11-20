<?php
require_once '../includes/config.php';

try {
    // Aceptar tanto POST como GET
    $ids = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $ids = isset($payload['ids']) && is_array($payload['ids']) ? array_map('intval', $payload['ids']) : [];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ids'])) {
        // Aceptar GET con parámetro ids (puede ser string "123" o array)
        $idsParam = $_GET['ids'];
        if (is_array($idsParam)) {
            $ids = array_map('intval', $idsParam);
        } else {
            // Si es string, puede ser un solo ID o varios separados por coma
            $ids = array_map('intval', explode(',', $idsParam));
        }
    }
    
    if (empty($ids)) { 
        json_success([]); 
    }
    
    $db = conectarDB();
    $in = implode(',', array_fill(0, count($ids), '?'));
    
    // Incluir campos de stock dual para tipo Varios
    $sql = "SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito 
            FROM insumos 
            WHERE id_insumo IN ($in)";
    $stmt = $db->prepare($sql);
    $stmt->execute($ids);
    $insumos = $stmt->fetchAll();
    
    Logger::debug('Insumos obtenidos por IDs', ['count' => count($insumos), 'ids' => $ids]);
    
    json_success($insumos);
    
} catch(Exception $e) { 
    Logger::error('Error al obtener insumos por IDs', [
        'mensaje' => $e->getMessage(),
        'ids' => $ids ?? []
    ]);
    json_error($e->getMessage(), 500); 
}
