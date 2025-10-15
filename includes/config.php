<?php
// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'inventario_insumos_v1');
define('DB_USER', getenv('DB_USER') ?: 'joaquin');
define('DB_PASS', getenv('DB_PASS') ?: '12345678');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_SOCKET', getenv('DB_SOCKET') ?: null);

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión de Insumos');
define('APP_VERSION', '1.0');
define('BASE_URL', rtrim(getenv('APP_BASE_URL') ?: '/inventario_app', '/'));

// Configuración de sesión
session_start();

// Marca de inicialización para impedir acceso directo a includes
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Autoload de Composer si existe
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Cargar .env (simple) si existe
function load_env_simple() {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) { return; }
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) { return; }
    foreach ($lines as $line) {
        if (strpos(ltrim($line), '#') === 0) { continue; }
        $pos = strpos($line, '=');
        if ($pos === false) { continue; }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        $val = trim($val, "'\"");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}
load_env_simple();

// Definir APP_BASE_URL como constante a partir de .env o BASE_URL
if (!defined('APP_BASE_URL')) {
    $envBase = getenv('APP_BASE_URL');
    if ($envBase && $envBase !== '/') {
        define('APP_BASE_URL', rtrim($envBase, '/'));
    } elseif (defined('BASE_URL') && BASE_URL !== '') {
        define('APP_BASE_URL', rtrim(BASE_URL, '/'));
    } else {
        // Fallback deducido desde SCRIPT_NAME (menos recomendado)
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        if (strpos($script, '/pages/') !== false) {
            define('APP_BASE_URL', substr($script, 0, strpos($script, '/pages/')));
        } elseif (strpos($script, '/ajax/') !== false) {
            define('APP_BASE_URL', substr($script, 0, strpos($script, '/ajax/')));
        } elseif (preg_match('#^(.*)/index\.php$#', $script, $m)) {
            define('APP_BASE_URL', rtrim($m[1], '/'));
        } else {
            define('APP_BASE_URL', '');
        }
    }
}

function app_base_url(): string { return APP_BASE_URL; }

// Helpers JSON
function json_response($payload, int $status = 200): void {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
}
function json_success($data = [], int $status = 200): void {
    json_response(['success' => true, 'data' => $data], $status);
}
function json_error(string $message, int $status = 400): void {
    json_response(['success' => false, 'error' => $message], $status);
}

// CSRF
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $_SESSION['csrf_token'] = md5(uniqid((string)mt_rand(), true));
        }
    }
    return $_SESSION['csrf_token'];
}
// Helper para insertar input hidden CSRF en formularios server-rendered
function csrf_input(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return "<input type=\"hidden\" name=\"_csrf\" value=\"$t\">";
}
function verify_csrf(): bool {
    $session = isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '';
    $header = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string)$_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    $post = isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : '';
    if ($session === '') { return false; }
    if ($header && hash_equals($session, $header)) { return true; }
    if ($post && hash_equals($session, $post)) { return true; }
    return false;
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

// Función para generar números de remito únicos con formato nnnn_yyyy
function generarNumeroRemito($dbParam = null) {
    $db = $dbParam instanceof PDO ? $dbParam : conectarDB();
    $anio = (int)date('Y');
    try {
        $ownTxn = !$db->inTransaction();
        if ($ownTxn) { $db->beginTransaction(); }
        // Crear fila si no existe (sin modificar valores)
        $stmtIns = $db->prepare("INSERT INTO remito_secuencia (anio, ultimo) VALUES (?, 0) ON DUPLICATE KEY UPDATE ultimo = ultimo");
        $stmtIns->execute([$anio]);

        // Bloquear fila del año y obtener valor actual
        $stmtSel = $db->prepare("SELECT ultimo FROM remito_secuencia WHERE anio = ? FOR UPDATE");
        $stmtSel->execute([$anio]);
        $row = $stmtSel->fetch();
        $actual = $row && isset($row['ultimo']) ? (int)$row['ultimo'] : 0;
        $nuevo = $actual + 1; // siempre desde 1

        $stmtUpd = $db->prepare("UPDATE remito_secuencia SET ultimo = ? WHERE anio = ?");
        $stmtUpd->execute([$nuevo, $anio]);

        if ($ownTxn) { $db->commit(); }
        return sprintf('%04d_%d', $nuevo, $anio);
    } catch (Throwable $e) {
        if (isset($ownTxn) && $ownTxn && $db->inTransaction()) { $db->rollBack(); }
        // Fallback defensivo al método previo (evitar bloqueo por completo)
        $stmt = $db->prepare("SELECT numero_remito FROM remitos WHERE numero_remito LIKE ? ORDER BY numero_remito DESC LIMIT 1");
        $stmt->execute(["%_{$anio}"]);
        $ultimo = $stmt->fetch();
        $secuencia = 0;
        if ($ultimo && isset($ultimo['numero_remito'])) {
            $partes = explode('_', $ultimo['numero_remito']);
            if (!empty($partes[0]) && ctype_digit($partes[0])) {
                $secuencia = (int)$partes[0];
            }
        }
        // Use the same connection for existence checks to avoid extra connections and reduce race windows
        do {
            $secuencia++;
            $numero = sprintf('%04d_%d', $secuencia, $anio);
        } while (remitoExiste($numero, $db));
        return $numero;
    }
}

// Función para validar si un número de remito existe
/**
 * Verifica existencia de remito. Acepta PDO opcional para reusar conexión.
 * @param string $numero_remito
 * @param PDO|null $pdo
 * @return bool
 */
function remitoExiste($numero_remito, $pdo = null) {
    $conexion = $pdo instanceof PDO ? $pdo : conectarDB();
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM remitos WHERE numero_remito = ?");
    $stmt->execute([$numero_remito]);
    $resultado = $stmt->fetch();
    return $resultado && (int)$resultado['total'] > 0;
}
?> 
