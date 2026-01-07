<?php
// ajax/debug_full.php
require_once '../includes/config.php';
header('Content-Type: text/html; charset=utf-8');

// Ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

$auth = estaAutenticado();
$user = obtenerUsuario();

echo "<h1>Debug Info</h1>";
echo "<h2>1. Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Authenticated: " . ($auth ? 'YES' : 'NO') . "<br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>2. Permissions</h2>";
if ($user) {
    echo "ID Rol: " . $user['id_rol'] . "<br>";
    echo "Permisos: " . $user['permisos'] . "<br>";
    echo "Can 'ver_todos': " . (tienePermiso('pedidos', 'ver_todos') ? 'YES' : 'NO') . "<br>";
    echo "Can 'gestionar': " . (tienePermiso('pedidos', 'gestionar') ? 'YES' : 'NO') . "<br>";
} else {
    echo "No user data.<br>";
}

echo "<h2>3. SSP Simulation (Modo: Pendientes)</h2>";
// Simulate simple query
try {
    $db = conectarDB();
    echo "DB Connection: OK<br>";
    
    // Test base query
    $sql = "SELECT COUNT(*) FROM pedidos";
    echo "Total Pedidos (Raw): " . $db->query($sql)->fetchColumn() . "<br>";
    
    // Simulate SSP Logic
    $modo = 'pendientes'; // Force pending mode
    $where = ["1=1"];
    // Logic from ssp
    $where[] = "(asignado_a IS NULL OR asignado_a = 0)";
    $where[] = "estado IN ('Pendiente', 'En Proceso')";
    
    $sql2 = "SELECT COUNT(*) FROM pedidos WHERE " . implode(' AND ', $where);
    echo "Pending Pedidos Query: $sql2<br>";
    echo "Pending Count: " . $db->query($sql2)->fetchColumn() . "<br>";
    
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Environment</h2>";
echo "APP_BASE_URL: " . app_base_url() . "<br>";
?>
