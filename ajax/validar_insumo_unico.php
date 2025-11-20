<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['valido' => false, 'error' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $numero_serie = !empty($input['numero_serie']) ? trim($input['numero_serie']) : null;
    $id_fisico = !empty($input['id_fisico']) ? trim($input['id_fisico']) : null;
    $id_patrimonio = !empty($input['id_patrimonio']) ? trim($input['id_patrimonio']) : null;
    $id_insumo_excluir = !empty($input['id_insumo_excluir']) ? (int)$input['id_insumo_excluir'] : null;
    
    // Si todos los campos están vacíos, no hay nada que validar
    if (empty($numero_serie) && empty($id_fisico) && empty($id_patrimonio)) {
        json_response([
            'valido' => true,
            'errores' => [],
            'timestamp' => date('c')
        ], 200);
    }
    
    $validacion = validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, $id_insumo_excluir);
    
    json_response(array_merge($validacion, ['timestamp' => date('c')]), 200);
    
} catch (Exception $e) {
    Logger::error('Error en validación de insumo único', [
        'mensaje' => $e->getMessage(),
        'numero_serie' => $numero_serie ?? null,
        'id_fisico' => $id_fisico ?? null
    ]);
    json_response([
        'valido' => false,
        'errores' => ['Error al validar: ' . $e->getMessage()],
        'timestamp' => date('c')
    ], 500);
}
?>
