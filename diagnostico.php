<?php
// Script de diagnóstico para revisar configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head><meta charset='UTF-8'><title>Diagnóstico del Sistema</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}h1{color:#333;}table{background:white;border-collapse:collapse;width:100%;margin:20px 0;}td,th{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#4CAF50;color:white;}tr:nth-child(even){background:#f9f9f9;}.ok{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.warning{color:orange;font-weight:bold;}</style>";
echo "</head><body>";
echo "<h1>🔍 Diagnóstico del Sistema</h1>";

// Incluir config
require_once __DIR__ . '/includes/config.php';

echo "<h2>1. Configuración de Rutas</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valor</th><th>Estado</th></tr>";

$base_url = defined('BASE_URL') ? BASE_URL : 'NO DEFINIDO';
$app_base_url = defined('APP_BASE_URL') ? APP_BASE_URL : 'NO DEFINIDO';
$app_base_url_func = function_exists('app_base_url') ? app_base_url() : 'FUNCIÓN NO EXISTE';

echo "<tr><td>BASE_URL (constante)</td><td><code>$base_url</code></td><td class='" . ($base_url !== 'NO DEFINIDO' ? 'ok' : 'error') . "'>" . ($base_url !== 'NO DEFINIDO' ? '✓ OK' : '✗ ERROR') . "</td></tr>";
echo "<tr><td>APP_BASE_URL (constante)</td><td><code>$app_base_url</code></td><td class='" . ($app_base_url !== 'NO DEFINIDO' ? 'ok' : 'error') . "'>" . ($app_base_url !== 'NO DEFINIDO' ? '✓ OK' : '✗ ERROR') . "</td></tr>";
echo "<tr><td>app_base_url() (función)</td><td><code>$app_base_url_func</code></td><td class='" . ($app_base_url_func !== 'FUNCIÓN NO EXISTE' ? 'ok' : 'error') . "'>" . ($app_base_url_func !== 'FUNCIÓN NO EXISTE' ? '✓ OK' : '✗ ERROR') . "</td></tr>";

$env_app_base = getenv('APP_BASE_URL');
echo "<tr><td>APP_BASE_URL (env)</td><td><code>" . ($env_app_base ? $env_app_base : 'NO DEFINIDO') . "</code></td><td class='warning'>" . ($env_app_base ? '⚠ Desde .env' : 'Sin definir') . "</td></tr>";

echo "</table>";

echo "<h2>2. Rutas de Archivos Estáticos</h2>";
echo "<table>";
echo "<tr><th>Archivo</th><th>Ruta Completa</th><th>Existe</th><th>Ruta URL</th></tr>";

$files = [
    'style.css' => __DIR__ . '/public/css/style.css',
    'main.js' => __DIR__ . '/public/js/main.js',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    $url_path = app_base_url() . '/public/' . ($name === 'style.css' ? 'css/' : 'js/') . $name;
    
    $status = $exists && $readable ? '✓ OK' : '✗ ERROR';
    $class = $exists && $readable ? 'ok' : 'error';
    
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td><code>$path</code></td>";
    echo "<td class='$class'>$status</td>";
    echo "<td><code>$url_path</code></td>";
    echo "</tr>";
    
    if ($exists) {
        $filemtime = filemtime($path);
        $size = filesize($path);
        echo "<tr><td colspan='4' style='background:#f0f0f0;font-size:0.9em;'>Última modificación: " . date('Y-m-d H:i:s', $filemtime) . " | Tamaño: " . number_format($size / 1024, 2) . " KB | filemtime: $filemtime</td></tr>";
    }
}

echo "</table>";

echo "<h2>3. URLs Generadas (Prueba)</h2>";
echo "<table>";
echo "<tr><th>Descripción</th><th>URL Generada</th></tr>";
$css_url = app_base_url() . '/public/css/style.css';
$js_url = app_base_url() . '/public/js/main.js?v=' . (file_exists(__DIR__ . '/public/js/main.js') ? filemtime(__DIR__ . '/public/js/main.js') : 'ERROR');
echo "<tr><td>CSS</td><td><code>$css_url</code></td></tr>";
echo "<tr><td>JS (con cache busting)</td><td><code>$js_url</code></td></tr>";
echo "</table>";

echo "<h2>4. Prueba de Carga de Archivos</h2>";
echo "<p>Verifica que estos enlaces funcionen al hacer clic:</p>";
echo "<ul>";
echo "<li><a href='$css_url' target='_blank'>Abrir style.css</a> - Debería mostrar código CSS</li>";
echo "<li><a href='" . app_base_url() . "/public/js/main.js' target='_blank'>Abrir main.js</a> - Debería mostrar código JavaScript</li>";
echo "</ul>";

echo "<h2>5. Variables de Servidor</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valor</th></tr>";
$server_vars = [
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'NO DEFINIDO',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'NO DEFINIDO',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO',
    'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'NO DEFINIDO',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO',
];
foreach ($server_vars as $var => $value) {
    echo "<tr><td><code>$var</code></td><td><code>$value</code></td></tr>";
}
echo "</table>";

echo "<h2>6. Configuración PHP</h2>";
echo "<table>";
echo "<tr><th>Directiva</th><th>Valor</th></tr>";
$php_config = [
    'PHP Version' => phpversion(),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'timezone' => date_default_timezone_get(),
];
foreach ($php_config as $key => $value) {
    echo "<tr><td><code>$key</code></td><td><code>$value</code></td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>📝 Recomendaciones</h2>";
echo "<ol>";
echo "<li><strong>Limpia la caché del navegador:</strong> Presiona <code>Ctrl + F5</code> (Windows/Linux) o <code>Cmd + Shift + R</code> (Mac)</li>";
echo "<li><strong>Verifica que las URLs generadas sean correctas</strong> para tu configuración de servidor</li>";
echo "<li><strong>Si app_base_url() está vacío o incorrecto:</strong> Crea un archivo <code>.env</code> en la raíz con: <code>APP_BASE_URL=/tu_ruta_base</code></li>";
echo "<li><strong>Verifica los permisos:</strong> Los archivos CSS/JS deben ser legibles por el servidor web</li>";
echo "</ol>";

echo "</body></html>";
?>
