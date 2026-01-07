<?php
// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'inventario_insumos_v1');
define('DB_USER', getenv('DB_USER') ?: 'joaquin');
define('DB_PASS', getenv('DB_PASS') ?: '12345678');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_SOCKET', getenv('DB_SOCKET') ?: null);

// Configuración de la aplicación
define('APP_NAME', 'SITIA');
define('APP_FULL_NAME', 'Sistema de Inventario de Telecomunicaciones, Insumos y Administración');
define('APP_VERSION', '1.0');
define('BASE_URL', rtrim(getenv('APP_BASE_URL') ?: '/inventario_app', '/'));

// Configuración de timezone (importante para fechas)
date_default_timezone_set('America/Argentina/Buenos_Aires');

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

// La sesión se iniciará en auth.php con la configuración correcta de seguridad
// No iniciar aquí para evitar conflictos de parámetros de cookie

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
    // Limpiar cualquier salida previa (warnings, espacios, caracteres invisibles)
    while (ob_get_level()) { ob_end_clean(); }
    
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (defined('TESTING') && TESTING) return;
    exit;
}
function json_success($data = [], int $status = 200): void {
    json_response([
        'success' => true, 
        'data' => $data,
        'timestamp' => date('c')
    ], $status);
}
function json_error(string $message, int $status = 400, array $details = []): void {
    $response = [
        'success' => false, 
        'error' => $message,
        'timestamp' => date('c')
    ];
    if (!empty($details)) {
        $response['details'] = $details;
    }
    json_response($response, $status);
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
        
        // Configurar timezone de MySQL para evitar conversiones automáticas de fechas
        $conexion->exec("SET time_zone = '-03:00'"); // Argentina (UTC-3)
        
        return $conexion;
    } catch(PDOException $e) {
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        die('Error de conexión a la base de datos.');
    }
}

// Función para generar números de remito únicos con formato nnnn_yyyy
// Los números de remito son consecutivos y nunca se reutilizan, incluso si un remito es anulado
function generarNumeroRemito($dbParam = null) {
    $db = $dbParam instanceof PDO ? $dbParam : conectarDB();
    $anio = (int)date('Y');
    try {
        $ownTxn = !$db->inTransaction();
        if ($ownTxn) { $db->beginTransaction(); }
        
        // Crear fila si no existe
        $stmtIns = $db->prepare("INSERT INTO remito_secuencia (anio, ultimo) VALUES (?, 0) ON DUPLICATE KEY UPDATE ultimo = ultimo");
        $stmtIns->execute([$anio]);
        
        // Obtener el último número y bloquearlo para evitar duplicados
        $stmtSel = $db->prepare("SELECT ultimo FROM remito_secuencia WHERE anio = ? FOR UPDATE");
        $stmtSel->execute([$anio]);
        $row = $stmtSel->fetch();
        $actual = $row && isset($row['ultimo']) ? (int)$row['ultimo'] : 0;
        $numeroAUsar = $actual + 1;
        
        // Actualizar secuencia
        $stmtUpd = $db->prepare("UPDATE remito_secuencia SET ultimo = ? WHERE anio = ?");
        $stmtUpd->execute([$numeroAUsar, $anio]);

        if ($ownTxn) { $db->commit(); }
        
        Logger::info("Remito generado", ['numero' => $numeroAUsar, 'anio' => $anio, 'formato' => sprintf('%04d_%d', $numeroAUsar, $anio)]);
        return sprintf('%04d_%d', $numeroAUsar, $anio);
        
    } catch (Throwable $e) {
        if (isset($ownTxn) && $ownTxn && $db->inTransaction()) { $db->rollBack(); }
        Logger::error("Error en generarNumeroRemito", ['error' => $e->getMessage()]);
        // Fallback: obtener el máximo número existente y sumar 1
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

/**
 * Valida que no existan duplicados de numero_serie, id_fisico o id_patrimonio
 * @param string|null $numero_serie
 * @param string|null $id_fisico
 * @param string|null $id_patrimonio
 * @param int|null $id_insumo_excluir - ID del insumo a excluir en ediciones
 * @param PDO|null $pdo
 * @return array ['valido' => bool, 'errores' => array]
 */
function validarInsumoUnico($numero_serie, $id_fisico, $id_patrimonio, $id_insumo_excluir = null, $pdo = null) {
    $conexion = $pdo instanceof PDO ? $pdo : conectarDB();
    $errores = [];
    
    // Validar número de serie si está presente
    if (!empty($numero_serie)) {
        $sql = "SELECT COUNT(*) as total FROM insumos WHERE numero_serie = ?";
        $params = [$numero_serie];
        
        if ($id_insumo_excluir !== null) {
            $sql .= " AND id_insumo != ?";
            $params[] = $id_insumo_excluir;
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        
        if ($resultado && (int)$resultado['total'] > 0) {
            $errores[] = "El número de serie '$numero_serie' ya existe en otro insumo";
        }
    }
    
    // Validar ID físico si está presente
    if (!empty($id_fisico)) {
        $sql = "SELECT COUNT(*) as total FROM insumos WHERE id_fisico = ?";
        $params = [$id_fisico];
        
        if ($id_insumo_excluir !== null) {
            $sql .= " AND id_insumo != ?";
            $params[] = $id_insumo_excluir;
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        
        if ($resultado && (int)$resultado['total'] > 0) {
            $errores[] = "El ID físico '$id_fisico' ya existe en otro insumo";
        }
    }
    
    // Validar ID patrimonio si está presente
    if (!empty($id_patrimonio)) {
        $sql = "SELECT COUNT(*) as total FROM insumos WHERE id_patrimonio = ?";
        $params = [$id_patrimonio];
        
        if ($id_insumo_excluir !== null) {
            $sql .= " AND id_insumo != ?";
            $params[] = $id_insumo_excluir;
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        
        if ($resultado && (int)$resultado['total'] > 0) {
            $errores[] = "El ID patrimonio '$id_patrimonio' ya existe en otro insumo";
        }
    }
    
    return [
        'valido' => empty($errores),
        'errores' => $errores
    ];
}

// Cargar sistema de logging
require_once __DIR__ . '/Logger.php';
$appEnv = getenv('APP_ENV');
$logLevel = getenv('LOG_LEVEL');
Logger::enable($appEnv !== 'production' && $appEnv !== false); // Solo en desarrollo
Logger::setLevel($logLevel !== false ? $logLevel : 'ERROR');

// Cargar sistema de validación de archivos
require_once __DIR__ . '/validar_archivo.php';

/**
 * Procesar archivo adjunto a ingreso
 * @param array $archivo - $_FILES['nombre_archivo']
 * @param int $id_ingreso
 * @param string $tipo_documento - 'remito' o 'documentacion'
 * @param string $uploadDir - Directorio donde guardar
 * @param PDO $db
 * @param int $usuarioId
 * @return array ['success' => bool, 'id_documento' => int|null, 'error' => string|null]
 */
function procesarArchivoAdjunto($archivo, $id_ingreso, $tipo_documento, $uploadDir, $db, $usuarioId) {
    Logger::debug("Procesando archivo adjunto", [
        'nombre' => $archivo['name'],
        'tipo_documento' => $tipo_documento,
        'id_ingreso' => $id_ingreso,
        'size' => $archivo['size'],
        'error' => $archivo['error']
    ]);
    
    // Usar función centralizada de validación
    $validacion = validarArchivoDocumento($archivo);
    
    if (!$validacion['valido']) {
        return ['success' => false, 'error' => $validacion['error']];
    }
    
    
    $extension = $validacion['extension'];
    
    // Generar nombre único
    $nombreUnico = $tipo_documento . '_' . $id_ingreso . '_' . time() . '.' . $extension;
    $rutaCompleta = $uploadDir . $nombreUnico;
    
    Logger::debug("Guardando archivo", [
        'nombreUnico' => $nombreUnico,
        'rutaCompleta' => $rutaCompleta,
        'tmp_name' => $archivo['tmp_name']
    ]);
    
    // Guardar archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        Logger::error("Error al mover archivo", ['ruta' => $rutaCompleta]);
        return ['success' => false, 'error' => 'Error al guardar el archivo'];
    }
    
    // Registrar en base de datos
    try {
        $stmt = $db->prepare(
            'INSERT INTO ingresos_documentos (id_ingreso, nombre_archivo, ruta_archivo, tipo_documento, cargado_por) 
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id_ingreso, basename($archivo['name']), $nombreUnico, $tipo_documento, $usuarioId]);
        $idDocumento = $db->lastInsertId();
        
        Logger::info("Documento registrado", [
            'id_documento' => $idDocumento,
            'id_ingreso' => $id_ingreso,
            'tipo' => $tipo_documento
        ]);
        
        return ['success' => true, 'id_documento' => $idDocumento, 'nombre' => basename($archivo['name'])];
    } catch (Exception $e) {
        // Eliminar archivo si falla el registro
        @unlink($rutaCompleta);
        Logger::error("Error al registrar documento en BD", ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Error al registrar documento: ' . $e->getMessage()];
    }
}

// Cargar sistema de autenticación
require_once __DIR__ . '/auth.php';
 
