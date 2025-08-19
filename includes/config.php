<?php
// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'inventario_insumos_v1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_SOCKET', getenv('DB_SOCKET') ?: null);

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión de Insumos');
define('APP_VERSION', '1.0');
define('BASE_URL', rtrim(getenv('APP_BASE_URL') ?: '/inventario_app', '/'));

// Configuración de sesión
session_start();

// Autoload de Composer si existe
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

function app_base_url(): string {
    $env = getenv('APP_BASE_URL');
    if ($env && $env !== '/') {
        return rtrim($env, '/');
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    // Intentar deducir base por carpeta antes de /pages/
    if (strpos($script, '/pages/') !== false) {
        return substr($script, 0, strpos($script, '/pages/'));
    }
    // Intentar antes de /ajax/
    if (strpos($script, '/ajax/') !== false) {
        return substr($script, 0, strpos($script, '/ajax/'));
    }
    // Fallback: raíz
    return '';
}

// Función para conectar a la base de datos
function conectarDB() {
    try {
        $useSocket = defined('DB_SOCKET') && DB_SOCKET;
        if ($useSocket) {
            $dsn = 'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        }
        $conexion = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $conexion;
    } catch(PDOException $e) {
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        die('Error de conexión a la base de datos.');
    }
}

// Función para generar números de remito únicos
function generarNumeroRemito() {
    $conexion = conectarDB();
    $anio = date('Y');
    // Buscar el último remito del año (esquema nuevo en tabla remitos)
    $stmt = $conexion->prepare("SELECT numero_remito FROM remitos WHERE numero_remito LIKE ? ORDER BY numero_remito DESC LIMIT 1");
    $stmt->execute(["REMITO_{$anio}_%"]);
    $ultimo = $stmt->fetch();
    $secuencia = 0;
    if ($ultimo && isset($ultimo['numero_remito'])) {
        $partes = explode('_', $ultimo['numero_remito']);
        $n = end($partes);
        if (ctype_digit($n)) {
            $secuencia = (int)$n;
        }
    }
    // Incrementar y asegurar unicidad en caso de colisiones
    do {
        $secuencia++;
        $numero = sprintf('REMITO_%s_%04d', $anio, $secuencia);
    } while (remitoExiste($numero));
    return $numero;
}

// Función para validar si un número de remito existe
function remitoExiste($numero_remito) {
    $conexion = conectarDB();
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM remitos WHERE numero_remito = ?");
    $stmt->execute([$numero_remito]);
    $resultado = $stmt->fetch();
    return $resultado && (int)$resultado['total'] > 0;
}
?> 