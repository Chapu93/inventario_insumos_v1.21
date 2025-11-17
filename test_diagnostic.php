<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNÓSTICO DEL SISTEMA ===\n\n";

// Test 1: Verificar .env
echo "1. Verificando .env...\n";
if (file_exists(__DIR__ . '/.env')) {
    echo "   ✓ Archivo .env existe\n";
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (strpos($envContent, 'DB_USER') !== false) {
        echo "   ✓ .env contiene configuración\n";
    } else {
        echo "   ✗ .env no contiene DB_USER\n";
    }
} else {
    echo "   ✗ Archivo .env NO existe\n";
}

echo "\n2. Verificando includes/Logger.php...\n";
if (file_exists(__DIR__ . '/includes/Logger.php')) {
    echo "   ✓ Logger.php existe\n";
    try {
        require_once __DIR__ . '/includes/Logger.php';
        echo "   ✓ Logger.php se carga correctamente\n";
        echo "   ✓ Clase Logger disponible: " . (class_exists('Logger') ? 'SÍ' : 'NO') . "\n";
    } catch (Exception $e) {
        echo "   ✗ Error al cargar Logger.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ Logger.php NO existe\n";
}

echo "\n3. Verificando includes/config.php...\n";
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "   ✓ config.php existe\n";
    try {
        require_once __DIR__ . '/includes/config.php';
        echo "   ✓ config.php se carga correctamente\n";
        echo "   ✓ DB_USER: " . (defined('DB_USER') ? DB_USER : 'NO DEFINIDO') . "\n";
        echo "   ✓ DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO') . "\n";
        echo "   ✓ APP_ENV: " . getenv('APP_ENV') . "\n";
        echo "   ✓ LOG_LEVEL: " . getenv('LOG_LEVEL') . "\n";
    } catch (Exception $e) {
        echo "   ✗ Error al cargar config.php: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   ✗ config.php NO existe\n";
}

echo "\n4. Verificando funciones helper...\n";
echo "   json_success existe: " . (function_exists('json_success') ? 'SÍ' : 'NO') . "\n";
echo "   json_error existe: " . (function_exists('json_error') ? 'SÍ' : 'NO') . "\n";
echo "   conectarDB existe: " . (function_exists('conectarDB') ? 'SÍ' : 'NO') . "\n";

echo "\n5. Probando conexión a BD...\n";
if (function_exists('conectarDB')) {
    try {
        $db = conectarDB();
        echo "   ✓ Conexión exitosa a la base de datos\n";
        echo "   ✓ Base de datos: " . DB_NAME . "\n";
    } catch (Exception $e) {
        echo "   ✗ Error de conexión: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ Función conectarDB no disponible\n";
}

echo "\n6. Verificando permisos de logs/...\n";
if (is_dir(__DIR__ . '/logs')) {
    echo "   ✓ Directorio logs/ existe\n";
    echo "   ✓ Es escribible: " . (is_writable(__DIR__ . '/logs') ? 'SÍ' : 'NO') . "\n";
} else {
    echo "   ✗ Directorio logs/ NO existe\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
