<?php
require_once '../../includes/config.php';

// Verificar conexión a BD
try {
    $conexion = conectarDB();
    $bd_ok = true;
} catch (Exception $e) {
    $bd_ok = false;
    $bd_error = $e->getMessage();
}

// Verificar si hay insumos en la BD
if ($bd_ok) {
    try {
        $stmt = $conexion->query("SELECT COUNT(*) as total FROM insumos");
        $total_insumos = $stmt->fetchColumn();
    } catch (Exception $e) {
        $total_insumos = 'Error: ' . $e->getMessage();
    }
}

// Verificar archivos importantes
$archivos_importantes = [
    'ver_ajax.php' => file_exists('ver_ajax.php'),
    'eliminar.php' => file_exists('eliminar.php'),
    'editar.php' => file_exists('editar.php'),
    'listar.php' => file_exists('listar.php')
];

// Verificar permisos de escritura
$permisos_ok = is_writable('.');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Funcionalidades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Test de Funcionalidades - Sistema de Inventario</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Estado del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Conexión a BD:</span>
                                <?php if ($bd_ok): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Error: <?php echo $bd_error; ?></span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total de Insumos:</span>
                                <span class="badge bg-info"><?php echo $total_insumos; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Permisos de Escritura:</span>
                                <?php if ($permisos_ok): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Limitados</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Archivos del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($archivos_importantes as $archivo => $existe): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?php echo $archivo; ?>:</span>
                                    <?php if ($existe): ?>
                                        <span class="badge bg-success">Presente</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Faltante</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Pruebas de Funcionalidad</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Test AJAX - Ver Insumo</h6>
                                <button class="btn btn-primary btn-sm" onclick="testVerInsumo()">
                                    Probar ver_ajax.php
                                </button>
                                <div id="resultado-ver" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Test de Conexión BD</h6>
                                <button class="btn btn-success btn-sm" onclick="testConexionBD()">
                                    Probar conexión
                                </button>
                                <div id="resultado-bd" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Test de Estructura BD</h6>
                                <button class="btn btn-info btn-sm" onclick="testEstructuraBD()">
                                    Probar estructura
                                </button>
                                <div id="resultado-estructura" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="listar.php" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>Ir a Listar Insumos
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Ir al Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testVerInsumo() {
            const resultado = document.getElementById('resultado-ver');
            resultado.innerHTML = '<div class="alert alert-info">Probando...</div>';
            
            fetch('ver_ajax.php?id=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultado.innerHTML = '<div class="alert alert-success">✓ ver_ajax.php funciona correctamente</div>';
                    } else {
                        resultado.innerHTML = `<div class="alert alert-warning">⚠ ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    resultado.innerHTML = `<div class="alert alert-danger">✗ Error: ${error.message}</div>`;
                });
        }
        
        function testConexionBD() {
            const resultado = document.getElementById('resultado-bd');
            resultado.innerHTML = '<div class="alert alert-info">Probando...</div>';
            
            fetch('test_db.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultado.innerHTML = '<div class="alert alert-success">✓ Conexión a BD exitosa</div>';
                    } else {
                        resultado.innerHTML = `<div class="alert alert-danger">✗ Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    resultado.innerHTML = `<div class="alert alert-danger">✗ Error: ${error.message}</div>`;
                });
        }
        
        function testEstructuraBD() {
            const resultado = document.getElementById('resultado-estructura');
            resultado.innerHTML = '<div class="alert alert-info">Probando...</div>';
            
            fetch('test_structure.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultado.innerHTML = '<div class="alert alert-success">✓ Estructura de BD correcta</div>';
                    } else {
                        resultado.innerHTML = `<div class="alert alert-danger">✗ Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    resultado.innerHTML = `<div class="alert alert-danger">✗ Error: ${error.message}</div>`;
                });
        }
    </script>
</body>
</html> 