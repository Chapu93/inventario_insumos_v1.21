<?php
// ajax/debug_json.php
// This script simulates the JSON response for DataTables to verify structure and content
require_once '../includes/config.php';

// Force authentication for the test since we are bypassing login in this debug script if needed, 
// but ideally we rely on the session we know exists.
if (session_status() === PHP_SESSION_NONE) session_start();

// Mock request parameters that DataTables would send
$_GET['draw'] = 1;
$_GET['start'] = 0;
$_GET['length'] = 10;
$_GET['modo'] = 'pendientes';
// We need to set these to avoid 'undefined index' notices in ssp script if they are used directly
$_GET['search'] = ['value' => ''];
$_GET['order'] = [[ 'column' => 5, 'dir' => 'desc' ]];

// Capture output to check for whitespace
ob_start();
include 'pedidos_list_ssp.php';
$output = ob_get_clean();

// Check if output is valid JSON
$data = json_decode($output);
if ($data === null) {
    echo "<h1>INVALID JSON DETECTED</h1>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    echo "<h3>Raw Output (look for leading whitespace/chars):</h3>";
    echo "<pre>[" . htmlspecialchars($output) . "]</pre>";
    echo "<h3>Hex Dump of first 100 bytes:</h3>";
    echo "<pre>";
    $hex = bin2hex(substr($output, 0, 100));
    echo chunk_split($hex, 2, ' ');
    echo "</pre>";
} else {
    echo "<h1>VALID JSON CONFIRMED</h1>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}
?>
