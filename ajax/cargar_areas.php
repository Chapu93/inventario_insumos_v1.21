<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id'])) { json_error('ID de sede no proporcionado', 400); exit; }

$conexion = conectarDB();
$sede_id = $_GET['sede_id'];

try {
    // Primero intentar obtener áreas específicas de la sede
    $sql = "SELECT DISTINCT a.id_area, a.nombre_area 
            FROM areas a 
            JOIN sede_areas sa ON a.id_area = sa.id_area 
            WHERE sa.id_sede = ? AND sa.activa = 1 
            ORDER BY a.nombre_area";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$sede_id]);
    $areas = $stmt->fetchAll();
    
    // Si no hay áreas específicas para la sede, obtener todas las áreas
    if (empty($areas)) {
        $sql = "SELECT id_area, nombre_area FROM areas ORDER BY nombre_area";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $areas = $stmt->fetchAll();
    }
    
    json_success(['areas' => $areas]);
    
} catch (Exception $e) {
    json_error('Error al cargar áreas: ' . $e->getMessage(), 500);
}
?> 