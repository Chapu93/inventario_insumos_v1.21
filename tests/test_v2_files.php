<?php
/**
 * Test de Verificación v2.0
 * 
 * Verifica que todos los archivos de la versión 2.0 existan y estén correctos
 * Ejecutar: php tests/test_v2_files.php
 */

echo "=== Test Archivos v2.0 ===\n\n";

$baseDir = dirname(__DIR__);
$todosOk = true;

// Archivos que deben existir (nuevos)
$archivosNuevos = [
    'includes/password_policy.php' => 'Política de Contraseñas',
    'ajax/busqueda_global.php' => 'Búsqueda Global',
    'CHANGELOG.md' => 'Documentación de cambios',
];

echo ">>> Archivos Nuevos\n";
foreach ($archivosNuevos as $archivo => $descripcion) {
    $ruta = $baseDir . '/' . $archivo;
    if (file_exists($ruta)) {
        echo "✅ $archivo - $descripcion\n";
    } else {
        echo "❌ $archivo - NO ENCONTRADO\n";
        $todosOk = false;
    }
}

echo "\n>>> Verificando modificaciones en archivos existentes\n";

// Verificar que .htaccess tenga security headers
$htaccess = file_get_contents($baseDir . '/.htaccess');
if (strpos($htaccess, 'SECURITY HEADERS') !== false) {
    echo "✅ .htaccess - Security Headers configurados\n";
} else {
    echo "❌ .htaccess - Security Headers NO encontrados\n";
    $todosOk = false;
}

// Verificar auth.php tiene rate limiting
$auth = file_get_contents($baseDir . '/includes/auth.php');
if (strpos($auth, 'verificarRateLimitLogin') !== false) {
    echo "✅ auth.php - Rate Limiting implementado\n";
} else {
    echo "❌ auth.php - Rate Limiting NO encontrado\n";
    $todosOk = false;
}

if (strpos($auth, 'session_regenerate_id') !== false) {
    echo "✅ auth.php - Session Regeneration implementado\n";
} else {
    echo "❌ auth.php - Session Regeneration NO encontrado\n";
    $todosOk = false;
}

// Verificar config.php tiene caché
$config = file_get_contents($baseDir . '/includes/config.php');
if (strpos($config, 'getFromCache') !== false) {
    echo "✅ config.php - Sistema de Caché implementado\n";
} else {
    echo "❌ config.php - Sistema de Caché NO encontrado\n";
    $todosOk = false;
}

// Verificar header.php tiene búsqueda global
$header = file_get_contents($baseDir . '/includes/header.php');
if (strpos($header, 'busquedaGlobalInput') !== false) {
    echo "✅ header.php - Búsqueda Global implementada\n";
} else {
    echo "❌ header.php - Búsqueda Global NO encontrada\n";
    $todosOk = false;
}

if (strpos($header, 'data-theme') !== false) {
    echo "✅ header.php - Modo Oscuro implementado\n";
} else {
    echo "❌ header.php - Modo Oscuro NO encontrado\n";
    $todosOk = false;
}

// Verificar footer.php tiene JS de búsqueda
$footer = file_get_contents($baseDir . '/includes/footer.php');
if (strpos($footer, 'busqueda_global.php') !== false) {
    echo "✅ footer.php - JS Búsqueda Global implementado\n";
} else {
    echo "❌ footer.php - JS Búsqueda Global NO encontrado\n";
    $todosOk = false;
}

if (strpos($footer, 'btnToggleTema') !== false) {
    echo "✅ footer.php - JS Toggle Tema implementado\n";
} else {
    echo "❌ footer.php - JS Toggle Tema NO encontrado\n";
    $todosOk = false;
}

// Verificar style.css tiene modo oscuro
$css = file_get_contents($baseDir . '/public/css/style.css');
if (strpos($css, '[data-theme="dark"]') !== false) {
    echo "✅ style.css - Estilos Modo Oscuro implementados\n";
} else {
    echo "❌ style.css - Estilos Modo Oscuro NO encontrados\n";
    $todosOk = false;
}

// Verificar login.php usa rate limiting
$login = file_get_contents($baseDir . '/login.php');
if (strpos($login, 'verificarRateLimitLogin') !== false) {
    echo "✅ login.php - Rate Limiting integrado\n";
} else {
    echo "❌ login.php - Rate Limiting NO integrado\n";
    $todosOk = false;
}

// Verificar dashboard.php usa caché
$dashboard = file_get_contents($baseDir . '/pages/dashboard.php');
if (strpos($dashboard, 'getFromCache') !== false) {
    echo "✅ dashboard.php - Caché implementado\n";
} else {
    echo "❌ dashboard.php - Caché NO implementado\n";
    $todosOk = false;
}

echo "\n";
if ($todosOk) {
    echo "✅ TODOS LOS TESTS PASARON - v2.0 implementado correctamente\n";
    exit(0);
} else {
    echo "❌ ALGUNOS TESTS FALLARON - Revisar implementación\n";
    exit(1);
}
