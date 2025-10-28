<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valido' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $numero_serie = !empty($input['numero_serie']) ? trim($input['numero_serie']) : null;
    $id_fisico = !empty($input['id_fisico']) ? trim($input['id_fisico']) : null;
    $id_patrimonio = !empty($input['id_patrimonio']) ? trim($input['id_patrimonio']) : null;
    $id_insumo_excluir = !empty($input['id_insumo_excluir']) ? (int)$input['id_insumo_excluir'] : null;
    
    // Si todos los campos están vacíos, no hay nada que validar
    if (empty($numero_serie) && empty($id_fisico) && empty($id_patrimonio)) {
        echo json_encode([
            'valido' => true,
            'errores' => []
        ]);
        exit;
    }
    
    $validacion = validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, $id_insumo_excluir);
    
    echo json_encode($validacion);
    
} catch (Exception $e) {
    echo json_encode([
        'valido' => false,
        'errores' => ['Error al validar: ' . $e->getMessage()]
    ]);
}
?>
