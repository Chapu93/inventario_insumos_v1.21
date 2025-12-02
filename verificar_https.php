<?php
/**
 * Script de diagnóstico para verificar configuración HTTPS
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico HTTPS - SITIA</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #5a9367; }
        .info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; }
        .danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 250px; }
        .badge { 
            padding: 3px 8px; 
            border-radius: 3px; 
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Configuración HTTPS - SITIA</h1>
    
    <div class="card">
        <h2>📋 Estado de la Conexión</h2>
        <?php
        $is_https = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
        
        if ($is_https) {
            echo '<div class="success">';
            echo '<strong>✅ Conexión SEGURA (HTTPS)</strong><br>';
            echo 'Tu sitio está usando HTTPS. Las configuraciones de seguridad están activas.';
            echo '</div>';
        } else {
            echo '<div class="warning">';
            echo '<strong>⚠️ Conexión NO SEGURA (HTTP)</strong><br>';
            echo 'Tu sitio está usando HTTP simple. Las configuraciones de seguridad están adaptadas para desarrollo.<br>';
            echo '<small><strong>Recomendación:</strong> En producción, configura un certificado SSL.</small>';
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>🔧 Variables del Servidor</h2>
        <table>
            <tr>
                <td>HTTPS</td>
                <td>
                    <?php 
                    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                        echo '<span class="badge badge-success">ON</span> ' . htmlspecialchars($_SERVER['HTTPS']);
                    } else {
                        echo '<span class="badge badge-warning">OFF</span> (no establecida o apagada)';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>SERVER_PORT</td>
                <td>
                    <?php 
                    $port = $_SERVER['SERVER_PORT'] ?? 'No establecido';
                    if ($port == 443) {
                        echo '<span class="badge badge-success">' . $port . '</span> (Puerto HTTPS)';
                    } elseif ($port == 80) {
                        echo '<span class="badge badge-warning">' . $port . '</span> (Puerto HTTP)';
                    } else {
                        echo '<span class="badge badge-warning">' . $port . '</span> (Puerto personalizado)';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>HTTP_X_FORWARDED_PROTO</td>
                <td><?php echo isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? '<span class="badge badge-success">' . htmlspecialchars($_SERVER['HTTP_X_FORWARDED_PROTO']) . '</span>' : '<span class="badge badge-warning">No establecido</span>'; ?></td>
            </tr>
            <tr>
                <td>HTTP_X_FORWARDED_SSL</td>
                <td><?php echo isset($_SERVER['HTTP_X_FORWARDED_SSL']) ? '<span class="badge badge-success">' . htmlspecialchars($_SERVER['HTTP_X_FORWARDED_SSL']) . '</span>' : '<span class="badge badge-warning">No establecido</span>'; ?></td>
            </tr>
            <tr>
                <td>REQUEST_SCHEME</td>
                <td>
                    <?php 
                    $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'No establecido';
                    if ($scheme === 'https') {
                        echo '<span class="badge badge-success">' . $scheme . '</span>';
                    } else {
                        echo '<span class="badge badge-warning">' . $scheme . '</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>🍪 Configuración de Sesiones PHP</h2>
        <table>
            <tr>
                <td>session.cookie_secure</td>
                <td>
                    <?php 
                    $secure = ini_get('session.cookie_secure');
                    if ($secure) {
                        echo '<span class="badge badge-success">Activado</span> (Solo HTTPS)';
                    } else {
                        echo '<span class="badge badge-warning">Desactivado</span> (Permite HTTP)';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>session.cookie_httponly</td>
                <td>
                    <?php 
                    $httponly = ini_get('session.cookie_httponly');
                    if ($httponly) {
                        echo '<span class="badge badge-success">Activado</span> (Protección XSS)';
                    } else {
                        echo '<span class="badge badge-danger">Desactivado</span> (No recomendado)';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>session.cookie_samesite</td>
                <td>
                    <?php 
                    $samesite = ini_get('session.cookie_samesite');
                    if ($samesite === 'Strict') {
                        echo '<span class="badge badge-success">Strict</span> (Máxima protección)';
                    } elseif ($samesite === 'Lax') {
                        echo '<span class="badge badge-success">Lax</span> (Compatible con desarrollo)';
                    } else {
                        echo '<span class="badge badge-warning">' . ($samesite ?: 'None') . '</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>session.use_only_cookies</td>
                <td>
                    <?php 
                    $cookies = ini_get('session.use_only_cookies');
                    if ($cookies) {
                        echo '<span class="badge badge-success">Activado</span> (Recomendado)';
                    } else {
                        echo '<span class="badge badge-danger">Desactivado</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>💡 Recomendaciones</h2>
        <?php if (!$is_https): ?>
            <div class="info">
                <strong>Para Desarrollo (HTTP):</strong>
                <ul>
                    <li>✅ La configuración actual es correcta para desarrollo local</li>
                    <li>✅ No verás advertencias de "sitio no seguro"</li>
                    <li>✅ Las sesiones funcionarán normalmente</li>
                </ul>
            </div>
            <div class="warning">
                <strong>Para Producción (HTTPS):</strong>
                <ul>
                    <li>🔒 Instala un certificado SSL (Let's Encrypt es gratis)</li>
                    <li>🔒 Configura tu servidor web para usar HTTPS</li>
                    <li>🔒 Redirige automáticamente HTTP → HTTPS</li>
                    <li>🔒 El sistema detectará automáticamente HTTPS y aplicará seguridad máxima</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Configuración Óptima</strong>
                <ul>
                    <li>Tu sitio está usando HTTPS</li>
                    <li>Todas las medidas de seguridad están activas</li>
                    <li>Las sesiones están protegidas</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>🔗 URL de Acceso Actual</h2>
        <div class="info">
            <strong>Estás accediendo desde:</strong><br>
            <code><?php echo htmlspecialchars(($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?></code>
        </div>
        <?php if (!$is_https): ?>
            <div class="warning">
                <strong>💡 Tip:</strong> Si tienes certificado SSL configurado, accede usando:<br>
                <code>https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/</code>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/login.php" style="
            display: inline-block;
            background: #5a9367;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        ">← Volver al Login</a>
    </div>
</body>
</html>
