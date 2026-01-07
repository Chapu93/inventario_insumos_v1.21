<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
</head>
<body>
    <h1>Session Debug</h1>
    <pre>
<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
?>
    </pre>
    
    <p><a href="pages/pedidos/listar.php">Go to Pedidos</a></p>
</body>
</html>
