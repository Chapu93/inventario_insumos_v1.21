<?php
/**
 * Test de Headers de Seguridad - v2.0
 * 
 * Verifica que los headers HTTP de seguridad estén configurados correctamente
 * Ejecutar: php tests/test_security_headers.php
 */

echo "=== Test Security Headers ===\n\n";

// URL base de la aplicación (ajustar según configuración)
$url = 'http://localhost/inventario_app/';

// Headers esperados
$headersEsperados = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "❌ Error: No se pudo conectar a $url\n";
    echo "   Asegúrate de que Apache esté corriendo.\n";
    exit(1);
}

echo "URL: $url\n";
echo "HTTP Status: $httpCode\n\n";

// Parsear headers
$headerLines = explode("\r\n", $response);
$headers = [];
foreach ($headerLines as $line) {
    if (strpos($line, ':') !== false) {
        list($key, $value) = explode(':', $line, 2);
        $headers[trim($key)] = trim($value);
    }
}

$todosOk = true;

foreach ($headersEsperados as $header => $valorEsperado) {
    if (isset($headers[$header])) {
        if (strpos($headers[$header], $valorEsperado) !== false) {
            echo "✅ $header: {$headers[$header]}\n";
        } else {
            echo "⚠️ $header: {$headers[$header]} (esperado: $valorEsperado)\n";
            $todosOk = false;
        }
    } else {
        echo "❌ $header: NO ENCONTRADO\n";
        $todosOk = false;
    }
}

// Verificar Content-Security-Policy (puede tener variaciones)
if (isset($headers['Content-Security-Policy'])) {
    echo "✅ Content-Security-Policy: (configurado)\n";
} else {
    echo "⚠️ Content-Security-Policy: NO ENCONTRADO\n";
}

echo "\n";
if ($todosOk) {
    echo "✅ Todos los headers de seguridad están correctamente configurados.\n";
} else {
    echo "⚠️ Algunos headers pueden necesitar ajustes.\n";
    echo "   Nota: Si mod_headers no está habilitado, los headers no aparecerán.\n";
}
