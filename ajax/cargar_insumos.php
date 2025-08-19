<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id'])) {
    echo json_encode(['error' => 'ID de sede no proporcionado']);
    exit;
}

$conexion = conectarDB();
$sede_id = $_GET['sede_id'];

try {
    // Obtener insumos disponibles en la sede especificada
    $sql = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, i.cantidad 
            FROM insumos i 
            WHERE i.estado = 'Disponible' 
            AND (i.id_sede_actual = ? OR i.id_sede_actual IS NULL)
            AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
            ORDER BY i.nombre_insumo";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$sede_id]);
    $insumos = $stmt->fetchAll();
    
    $options = '<option value="">Seleccione un insumo</option>';
    foreach ($insumos as $insumo) {
        $display_text = $insumo['nombre_insumo'];
        
        // Agregar información específica según el tipo
        if ($insumo['tipo_insumo'] == 'Varios') {
            $display_text .= ' (Cantidad: ' . $insumo['cantidad'] . ')';
        } else {
            if ($insumo['numero_serie']) {
                $display_text .= ' (S/N: ' . $insumo['numero_serie'] . ')';
            }
            if ($insumo['id_fisico']) {
                $display_text .= ' (ID: ' . $insumo['id_fisico'] . ')';
            }
        }
        
        $display_text .= ' - ' . $insumo['tipo_insumo'];
        
        $dataTipo = htmlspecialchars($insumo['tipo_insumo']);
        $dataMax = ($insumo['tipo_insumo'] === 'Varios') ? (int)$insumo['cantidad'] : 1;
        $options .= '<option value="' . (int)$insumo['id_insumo'] . '" data-tipo="' . $dataTipo . '" data-max="' . $dataMax . '">' . htmlspecialchars($display_text) . '</option>';
    }
    
    echo json_encode(['success' => true, 'options' => $options]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al cargar insumos: ' . $e->getMessage()]);
}
?> 