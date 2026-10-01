<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

$q = $_GET['q'] ?? '';

$db = conectarDB();
// Busca insumos por nombre o serie
$term = "%$q%";
$term_clean = '%' . str_replace(['-', ' '], '', $q) . '%';

$stmt = $db->prepare("SELECT id_insumo, nombre_insumo, numero_serie, tipo_insumo, id_fisico, id_patrimonio 
                      FROM insumos 
                      WHERE (nombre_insumo LIKE ? 
                             OR numero_serie LIKE ? OR REPLACE(numero_serie, '-', '') LIKE ?
                             OR id_fisico LIKE ? OR REPLACE(id_fisico, '-', '') LIKE ? 
                             OR id_patrimonio LIKE ? OR REPLACE(id_patrimonio, '-', '') LIKE ? 
                             OR tipo_insumo LIKE ? OR subcategoria_varios LIKE ?)
                      LIMIT 20");
$stmt->execute([$term, $term, $term_clean, $term, $term_clean, $term, $term_clean, $term, $term]);
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
