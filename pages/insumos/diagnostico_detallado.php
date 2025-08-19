<?php
require_once '../../includes/config.php';

// Evitar warnings de headers
ob_start();

// Función para probar AJAX
function testAjax($url, $params = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode == 200 && !$error,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Verificar conexión a BD
try {
    $conexion = conectarDB();
    $bd_ok = true;
    $bd_error = null;
} catch (Exception $e) {
    $bd_ok = false;
    $bd_error = $e->getMessage();
}

// Obtener información de insumos
$insumos_info = [];
if ($bd_ok) {
    try {
        $stmt = $conexion->query("SELECT id_insumo, nombre_insumo, tipo_insumo, cantidad FROM insumos LIMIT 5");
        $insumos_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $insumos_info = ['error' => $e->getMessage()];
    }
}

// Probar ver_ajax.php
$test_ver_ajax = testAjax('http://localhost/inventario_app/pages/insumos/ver_ajax.php', ['id' => 1]);

// Verificar archivos
$archivos = [
    'ver_ajax.php' => file_exists('ver_ajax.php'),
    'eliminar.php' => file_exists('eliminar.php'),
    'editar.php' => file_exists('editar.php'),
    'listar.php' => file_exists('listar.php'),
    'test_funcionalidades.php' => file_exists('test_funcionalidades.php')
];

// Verificar permisos
$permisos = [
    'directorio_actual' => is_writable('.'),
    'ver_ajax.php' => is_readable('ver_ajax.php'),
    'eliminar.php' => is_readable('eliminar.php'),
    'editar.php' => is_readable('editar.php')
];

// Verificar configuración PHP
$php_info = [
    'version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json')
    ]
];

ob_end_clean();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Detallado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-stethoscope me-2"></i>Diagnóstico Detallado del Sistema</h1>
        
        <!-- Estado General -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Estado General</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-database fa-2x <?php echo $bd_ok ? 'text-success' : 'text-danger'; ?>"></i>
                                    <h6>Conexión BD</h6>
                                    <span class="badge <?php echo $bd_ok ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $bd_ok ? 'OK' : 'Error'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-server fa-2x text-info"></i>
                                    <h6>Servidor Web</h6>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-code fa-2x text-warning"></i>
                                    <h6>PHP</h6>
                                    <span class="badge bg-info"><?php echo PHP_VERSION; ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-file-code fa-2x text-primary"></i>
                                    <h6>Archivos</h6>
                                    <span class="badge bg-success"><?php echo count(array_filter($archivos)); ?>/<?php echo count($archivos); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detalles de Base de Datos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-database me-2"></i>Base de Datos</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bd_ok): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Conexión exitosa</strong>
                            </div>
                            <h6>Insumos disponibles:</h6>
                            <?php if (is_array($insumos_info) && !empty($insumos_info)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($insumos_info as $insumo): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><?php echo htmlspecialchars($insumo['nombre_insumo']); ?></span>
                                            <span class="badge bg-secondary">ID: <?php echo $insumo['id_insumo']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No hay insumos en la base de datos
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Error de conexión:</strong> <?php echo $bd_error; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>Configuración PHP</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($php_info['extensions'] as $ext => $loaded): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?php echo $ext; ?></span>
                                    <span class="badge <?php echo $loaded ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $loaded ? 'Cargada' : 'No disponible'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pruebas de Funcionalidad -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-vial me-2"></i>Pruebas de Funcionalidad</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Test ver_ajax.php</h6>
                                <div class="alert <?php echo $test_ver_ajax['success'] ? 'alert-success' : 'alert-danger'; ?>">
                                    <strong>Estado:</strong> 
                                    <?php echo $test_ver_ajax['success'] ? 'OK' : 'Error'; ?>
                                    <br>
                                    <strong>Código HTTP:</strong> <?php echo $test_ver_ajax['http_code']; ?>
                                    <?php if (!$test_ver_ajax['success']): ?>
                                        <br>
                                        <strong>Error:</strong> <?php echo $test_ver_ajax['error']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Archivos del Sistema</h6>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($archivos as $archivo => $existe): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><?php echo $archivo; ?></span>
                                            <span class="badge <?php echo $existe ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $existe ? 'Presente' : 'Faltante'; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Acciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>Acciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="listar.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-list me-2"></i>Listar Insumos
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="test_funcionalidades.php" class="btn btn-info w-100 mb-2">
                                    <i class="fas fa-vial me-2"></i>Test Funcionalidades
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../dashboard.php" class="btn btn-secondary w-100 mb-2">
                                    <i class="fas fa-home me-2"></i>Dashboard
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-warning w-100 mb-2" onclick="location.reload()">
                                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 