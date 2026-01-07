<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$q = $_GET['q'] ?? '';

$db = conectarDB();
// Busca insumos por nombre o serie
$stmt = $db->prepare("SELECT id_insumo, nombre_insumo, numero_serie, tipo_insumo 
                      FROM insumos 
                      WHERE (nombre_insumo LIKE ? OR numero_serie LIKE ?)
                      LIMIT 20");
$term = "%$q%";
$stmt->execute([$term, $term]);
$rows = $stmt->fetchAll();

$items = array_map(function($r){
    $text = $r['nombre_insumo'];
    if($r['numero_serie']) $text .= ' (SN: '.$r['numero_serie'].')';
    $text .= ' - ' . $r['tipo_insumo'];
    
    return [
        'id' => $r['id_insumo'],
        'text' => $text
    ];
}, $rows);

json_success(['items' => $items]);
?>
