<?php
/**
 * Script de prueba de permisos
 * Simula diferentes tipos de usuarios y verifica qué permisos tienen
 */
require_once 'includes/config.php';

requerirAutenticacion();

// Solo administradores pueden ejecutar este test
if (!tienePermiso('usuarios', 'ver')) {
    die('Solo administradores pueden ejecutar este test');
}

$db = conectarDB();

// Obtener todos los roles
$roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();

// Obtener todos los usuarios
$usuarios = $db->query("SELECT u.*, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol ORDER BY u.id_rol, u.username")->fetchAll();

// Módulos a probar
$modulosTest = [
    'insumos' => ['ver', 'crear', 'editar', 'eliminar', 'baja'],
    'asignaciones' => ['ver', 'crear', 'editar', 'anular', 'devolver'],
    'sedes' => ['ver', 'crear', 'editar', 'eliminar'],
    'telecom' => ['ver', 'editar', 'eliminar'],
    'reportes' => ['ver', 'exportar'],
    'usuarios' => ['ver', 'crear', 'editar', 'eliminar', 'cambiar_rol', 'reset_password'],
    'auditoria' => ['ver_todo'],
    'sistema' => ['backup']
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Permisos - Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .permiso-si { background-color: #d4edda !important; }
        .permiso-no { background-color: #f8d7da !important; }
        .sticky-header { position: sticky; top: 0; background: white; z-index: 10; }
        .table-sm td, .table-sm th { padding: 0.3rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-shield-alt me-2"></i>Test de Permisos del Sistema</h1>
                <p class="text-muted">Verificación de permisos por rol y usuario</p>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
            </div>
        </div>

        <!-- Resumen de Roles -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Roles del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Permisos (JSON)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $rol): ?>
                            <tr>
                                <td><?php echo $rol['id_rol']; ?></td>
                                <td><strong><?php echo htmlspecialchars($rol['nombre_rol']); ?></strong></td>
                                <td><?php echo htmlspecialchars($rol['descripcion'] ?? '-'); ?></td>
                                <td><small><code><?php echo htmlspecialchars($rol['permisos']); ?></code></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Matriz de Permisos por Rol -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Matriz de Permisos por Rol</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th rowspan="2">Módulo</th>
                                <th rowspan="2">Acción</th>
                                <?php foreach ($roles as $rol): ?>
                                <th class="text-center"><?php echo htmlspecialchars($rol['nombre_rol']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulosTest as $modulo => $acciones): ?>
                                <?php foreach ($acciones as $index => $accion): ?>
                                <tr>
                                    <?php if ($index === 0): ?>
                                    <td rowspan="<?php echo count($acciones); ?>" class="fw-bold bg-light">
                                        <?php echo ucfirst($modulo); ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo $accion; ?></td>
                                    <?php foreach ($roles as $rol): ?>
                                        <?php
                                        $permisos = json_decode($rol['permisos'], true);
                                        $tiene = isset($permisos[$modulo]) && in_array($accion, $permisos[$modulo]);
                                        ?>
                                        <td class="text-center <?php echo $tiene ? 'permiso-si' : 'permiso-no'; ?>">
                                            <?php if ($tiene): ?>
                                                <i class="fas fa-check text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Permisos por Usuario -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Permisos por Usuario</h5>
            </div>
            <div class="card-body">
                <?php foreach ($roles as $rol): ?>
                    <?php
                    $usuariosRol = array_filter($usuarios, function($u) use ($rol) {
                        return $u['id_rol'] == $rol['id_rol'];
                    });
                    if (empty($usuariosRol)) continue;
                    ?>
                    <h6 class="mt-3 mb-2 text-primary">
                        <i class="fas fa-users me-2"></i><?php echo htmlspecialchars($rol['nombre_rol']); ?>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Permisos Personalizados</th>
                                    <th>Módulos con Acceso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuariosRol as $usuario): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($usuario['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <?php if (!empty($usuario['permisos_personalizados'])): ?>
                                            <span class="badge bg-warning">Sí</span>
                                            <br><small><code><?php echo htmlspecialchars($usuario['permisos_personalizados']); ?></code></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No (usa rol)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $permisosUsuario = !empty($usuario['permisos_personalizados']) 
                                            ? json_decode($usuario['permisos_personalizados'], true)
                                            : json_decode($rol['permisos'], true);
                                        
                                        if (is_array($permisosUsuario)) {
                                            foreach ($permisosUsuario as $mod => $acts) {
                                                if (!empty($acts)) {
                                                    echo '<span class="badge bg-primary me-1">' . htmlspecialchars($mod) . '</span>';
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Test de Función tienePermiso() -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Test de Función tienePermiso() - Usuario Actual</h5>
            </div>
            <div class="card-body">
                <p><strong>Usuario actual:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> 
                   (<?php echo htmlspecialchars($_SESSION['nombre_rol']); ?>)</p>
                
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulosTest as $modulo => $acciones): ?>
                                <?php foreach ($acciones as $accion): ?>
                                <tr>
                                    <td><?php echo ucfirst($modulo); ?></td>
                                    <td><?php echo $accion; ?></td>
                                    <?php $tiene = tienePermiso($modulo, $accion); ?>
                                    <td class="<?php echo $tiene ? 'permiso-si' : 'permiso-no'; ?>">
                                        <?php if ($tiene): ?>
                                            <i class="fas fa-check text-success"></i> SÍ
                                        <?php else: ?>
                                            <i class="fas fa-times text-danger"></i> NO
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Problemas Detectados -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Análisis de Problemas</h5>
            </div>
            <div class="card-body">
                <?php
                $problemas = [];
                
                // Verificar roles sin permisos
                foreach ($roles as $rol) {
                    $permisos = json_decode($rol['permisos'], true);
                    if (empty($permisos) || !is_array($permisos)) {
                        $problemas[] = "El rol '{$rol['nombre_rol']}' no tiene permisos definidos o tiene formato inválido";
                    }
                }
                
                // Verificar usuarios sin rol
                foreach ($usuarios as $usuario) {
                    if (empty($usuario['id_rol'])) {
                        $problemas[] = "El usuario '{$usuario['username']}' no tiene rol asignado";
                    }
                }
                
                if (empty($problemas)):
                ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>No se detectaron problemas evidentes en la configuración de permisos.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-bug me-2"></i>Problemas detectados:</h6>
                        <ul>
                            <?php foreach ($problemas as $problema): ?>
                                <li><?php echo htmlspecialchars($problema); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
