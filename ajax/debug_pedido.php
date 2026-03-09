<?php
// ajax/debug_pedido.php
require_once '../includes/config.php';

// Force session
if (session_status() === PHP_SESSION_NONE) session_start();

// Mock request
$_POST['accion'] = 'obtener';
$_GET['accion'] = 'obtener';
$_POST['id'] = 5; // ID from screenshot
$_GET['id'] = 5;

// Capture output
ob_start();
include 'pedidos_acciones.php';
$output = ob_get_clean();

// Analyze
$data = json_decode($output);
if ($data === null) {
    echo "<h1>INVALID JSON</h1>";
    echo "Error: " . json_last_error_msg();
    echo "<pre>[" . htmlspecialchars($output) . "]</pre>";
} else {
    echo "<h1>VALID JSON</h1>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}
?>
