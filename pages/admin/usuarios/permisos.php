<?php
require_once '../../../includes/config.php';

// Solo Super Administrador puede gestionar permisos
requerirAutenticacion();
verificarPermiso('usuarios', 'editar');

$id = (int)($_GET['id'] ?? 0);
$errores = [];
$usuario = null;

if ($id === 0) {
    $_SESSION['mensaje'] = 'ID de usuario inválido';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

try {
    $db = conectarDB();
    
    // Obtener datos del usuario
    $stmt = $db->prepare("SELECT u.*, r.nombre_rol, r.permisos as permisos_rol 
                          FROM usuarios u 
                          JOIN roles r ON u.id_rol = r.id_rol 
                          WHERE u.id_usuario = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['mensaje'] = 'Usuario no encontrado';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: listar.php');
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error al cargar usuario: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}

// Definición de módulos y acciones disponibles
$modulosDisponibles = [
    'insumos' => [
        'nombre' => 'Insumos',
        'icono' => 'fas fa-boxes',
        'acciones' => [
            'ver' => 'Ver listado',
            'crear' => 'Crear nuevo',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
            'baja' => 'Dar de baja'
        ]
    ],
    'asignaciones' => [
        'nombre' => 'Asignaciones',
        'icono' => 'fas fa-file-alt',
        'acciones' => [
            'ver' => 'Ver listado',
            'crear' => 'Crear nueva',
            'editar' => 'Editar',
            'anular' => 'Anular',
            'devolver' => 'Devolver insumos'
        ]
    ],
    'reportes' => [
        'nombre' => 'Reportes',
        'icono' => 'fas fa-chart-bar',
        'acciones' => [
            'ver' => 'Ver reportes',
            'exportar' => 'Exportar'
        ]
    ],
    'usuarios' => [
        'nombre' => 'Usuarios',
        'icono' => 'fas fa-users',
        'acciones' => [
            'ver' => 'Ver listado',
            'crear' => 'Crear nuevo',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
            'cambiar_rol' => 'Cambiar rol'
        ]
    ],
    'auditoria' => [
        'nombre' => 'Auditoría',
        'icono' => 'fas fa-history',
        'acciones' => [
            'ver_todo' => 'Ver todo'
        ]
    ],
    'telecom' => [
        'nombre' => 'Telecomunicaciones',
        'icono' => 'fas fa-wifi',
        'acciones' => [
            'ver' => 'Ver',
            'editar' => 'Editar'
        ]
    ],
    'sedes' => [
        'nombre' => 'Sedes',
        'icono' => 'fas fa-building',
        'acciones' => [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar'
        ]
    ],
    'areas' => [
        'nombre' => 'Áreas',
        'icono' => 'fas fa-sitemap',
        'acciones' => [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar'
        ]
    ]
];

// Obtener permisos actuales (personalizados o del rol)
$permisosActuales = [];
if (!empty($usuario['permisos_personalizados'])) {
    $permisosActuales = json_decode($usuario['permisos_personalizados'], true) ?: [];
} else {
    $permisosActuales = json_decode($usuario['permisos_rol'], true) ?: [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Construir array de permisos desde el formulario
        $nuevosPermisos = [];
        foreach ($modulosDisponibles as $modulo => $config) {
            $accionesSeleccionadas = [];
            foreach ($config['acciones'] as $accion => $label) {
                $fieldName = "permiso_{$modulo}_{$accion}";
                if (isset($_POST[$fieldName]) && $_POST[$fieldName] === '1') {
                    $accionesSeleccionadas[] = $accion;
                }
            }
            if (!empty($accionesSeleccionadas)) {
                $nuevosPermisos[$modulo] = $accionesSeleccionadas;
            }
        }
        
        // Guardar permisos personalizados
        $permisosJson = !empty($nuevosPermisos) ? json_encode($nuevosPermisos, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "UPDATE usuarios SET permisos_personalizados = ?, modificado_por = ? WHERE id_usuario = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$permisosJson, obtenerUsuarioId(), $id]);
        
        $db->commit();
        
        // Registrar en auditoría
        registrarAuditoria(
            'editar_permisos_usuario',
            'usuarios',
            "Permisos personalizados actualizados para usuario: {$usuario['username']}",
            'usuario',
            $id,
            ['permisos_anteriores' => $permisosActuales],
            ['permisos_nuevos' => $nuevosPermisos]
        );
        
        $_SESSION['mensaje'] = 'Permisos actualizados correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        
        header('Location: listar.php');
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $errores[] = 'Error al actualizar permisos: ' . $e->getMessage();
        Logger::error('Error al actualizar permisos', ['error' => $e->getMessage(), 'usuario_id' => $id]);
    }
}
?>
<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-user-lock me-2"></i>Gestionar Permisos
        </h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle me-2"></i>Errores:</h6>
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                    <span class="badge bg-info ms-2"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span>
                </h5>
                <small class="text-muted">
                    Usuario: <?php echo htmlspecialchars($usuario['username']); ?> | 
                    Email: <?php echo htmlspecialchars($usuario['email']); ?>
                </small>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formPermisos">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Permisos Personalizados:</strong> Si no selecciona ningún permiso, el usuario usará los permisos predeterminados de su rol.
                        Si selecciona al menos un permiso, se aplicarán <strong>solo</strong> los permisos que marque aquí.
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="seleccionarTodos()">
                            <i class="fas fa-check-square me-1"></i>Seleccionar Todos
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="deseleccionarTodos()">
                            <i class="fas fa-square me-1"></i>Deseleccionar Todos
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="restaurarRol()">
                            <i class="fas fa-undo me-1"></i>Restaurar Permisos del Rol
                        </button>
                    </div>
                    
                    <hr>
                    
                    <?php foreach ($modulosDisponibles as $modulo => $config): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="<?php echo $config['icono']; ?> me-2"></i>
                                    <?php echo $config['nombre']; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($config['acciones'] as $accion => $label): ?>
                                        <?php
                                        $fieldName = "permiso_{$modulo}_{$accion}";
                                        $checked = isset($permisosActuales[$modulo]) && in_array($accion, $permisosActuales[$modulo]);
                                        ?>
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permiso-checkbox" 
                                                       type="checkbox" 
                                                       name="<?php echo $fieldName; ?>" 
                                                       id="<?php echo $fieldName; ?>" 
                                                       value="1"
                                                       data-modulo="<?php echo $modulo; ?>"
                                                       data-accion="<?php echo $accion; ?>"
                                                       <?php echo $checked ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $fieldName; ?>">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar Permisos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-light mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <strong>Rol Actual:</strong><br>
                    <?php echo htmlspecialchars($usuario['nombre_rol']); ?>
                </p>
                
                <p class="small mb-2">
                    <strong>Estado:</strong><br>
                    <?php if (!empty($usuario['permisos_personalizados'])): ?>
                        <span class="badge bg-warning">Permisos Personalizados</span>
                    <?php else: ?>
                        <span class="badge bg-info">Permisos del Rol</span>
                    <?php endif; ?>
                </p>
                
                <hr>
                
                <h6 class="small"><strong>Permisos del Rol:</strong></h6>
                <div id="permisosRol" class="small">
                    <?php
                    $permisosRol = json_decode($usuario['permisos_rol'], true) ?: [];
                    foreach ($permisosRol as $mod => $acciones):
                        if (isset($modulosDisponibles[$mod])):
                    ?>
                        <div class="mb-2">
                            <strong><?php echo $modulosDisponibles[$mod]['nombre']; ?>:</strong><br>
                            <small class="text-muted">
                                <?php echo implode(', ', $acciones); ?>
                            </small>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card bg-warning bg-opacity-10 border-warning">
            <div class="card-body">
                <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Importante</h6>
                <ul class="small mb-0">
                    <li>Los cambios se aplicarán inmediatamente</li>
                    <li>El usuario deberá cerrar sesión y volver a iniciarla para que los cambios surtan efecto</li>
                    <li>Los permisos personalizados tienen prioridad sobre los del rol</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

<script>
// Permisos del rol (para restaurar)
const permisosRol = <?php echo json_encode($permisosRol); ?>;

function seleccionarTodos() {
    document.querySelectorAll('.permiso-checkbox').forEach(cb => cb.checked = true);
}

function deseleccionarTodos() {
    document.querySelectorAll('.permiso-checkbox').forEach(cb => cb.checked = false);
}

function restaurarRol() {
    if (!confirm('¿Restaurar los permisos predeterminados del rol? Esto eliminará los permisos personalizados.')) {
        return;
    }
    
    // Primero deseleccionar todos
    deseleccionarTodos();
    
    // Luego marcar los del rol
    for (const [modulo, acciones] of Object.entries(permisosRol)) {
        acciones.forEach(accion => {
            const checkbox = document.getElementById(`permiso_${modulo}_${accion}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
}

// Validación antes de enviar
document.getElementById('formPermisos').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.permiso-checkbox:checked');
    
    if (checkboxes.length === 0) {
        if (!confirm('No ha seleccionado ningún permiso. El usuario usará los permisos de su rol. ¿Continuar?')) {
            e.preventDefault();
        }
    }
});
</script>
