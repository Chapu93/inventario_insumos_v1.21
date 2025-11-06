<?php
require_once '../../../includes/config.php';

// Requerir autenticación y permiso de crear usuarios
requerirAutenticacion();
verificarPermiso('usuarios', 'crear');

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = conectarDB();
        
        // Validar campos requeridos
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $id_rol = (int)($_POST['id_rol'] ?? 0);
        
        if (empty($username)) $errores[] = 'El nombre de usuario es obligatorio';
        if (empty($email)) $errores[] = 'El email es obligatorio';
        if (empty($password)) $errores[] = 'La contraseña es obligatoria';
        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        if (empty($apellido)) $errores[] = 'El apellido es obligatorio';
        if ($id_rol === 0) $errores[] = 'Debe seleccionar un rol';
        
        // Validar formato de email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido';
        }
        
        // Validar username (solo letras, números y guión bajo)
        if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres (solo letras, números y guión bajo)';
        }
        
        // Validar contraseña
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            }
            if ($password !== $password_confirm) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }
        
        // Verificar que username y email no existan
        if (empty($errores)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = 'El nombre de usuario ya está en uso';
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = 'El email ya está registrado';
            }
        }
        
        if (empty($errores)) {
            $db->beginTransaction();
            
            // Hash de contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $sql = "INSERT INTO usuarios (username, email, password_hash, nombre, apellido, id_rol, activo, modificado_por)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $email, $password_hash, $nombre, $apellido, $id_rol, obtenerUsuarioId()]);
            
            $nuevoId = $db->lastInsertId();
            
            $db->commit();
            
            // Registrar en auditoría
            registrarAuditoria(
                'crear_usuario',
                'usuarios',
                "Usuario creado: {$username} ({$nombre} {$apellido})",
                'usuario',
                $nuevoId,
                null,
                [
                    'username' => $username,
                    'email' => $email,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'id_rol' => $id_rol
                ]
            );
            
            $_SESSION['mensaje'] = 'Usuario creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            
            header('Location: listar.php');
            exit;
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $errores[] = 'Error al crear usuario: ' . $e->getMessage();
        error_log('Error en crear usuario: ' . $e->getMessage());
    }
}

// Obtener roles para el select
try {
    $db = conectarDB();
    $roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
} catch (Exception $e) {
    $roles = [];
    $errores[] = 'Error al cargar roles';
}
?>
<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0"><i class="fas fa-user-plus me-2"></i>Crear Usuario</h1>
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
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información del Usuario</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nombre de Usuario *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   pattern="[a-zA-Z0-9_]{3,50}"
                                   required>
                            <small class="text-muted">Solo letras, números y guión bajo (3-50 caracteres)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="apellido" 
                                   name="apellido" 
                                   value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_rol" class="form-label">Rol *</label>
                        <select class="form-select" id="id_rol" name="id_rol" required>
                            <option value="">Seleccionar rol...</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id_rol']; ?>"
                                        <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == $rol['id_rol']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Define los permisos de acceso del usuario</small>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   minlength="6"
                                   required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   minlength="6"
                                   required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <h6>Roles Disponibles:</h6>
                <ul class="small">
                    <?php foreach ($roles as $rol): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($rol['nombre_rol']); ?>:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($rol['descripcion'] ?? ''); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <hr>
                
                <h6>Seguridad:</h6>
                <ul class="small mb-0">
                    <li>Las contraseñas se almacenan encriptadas</li>
                    <li>El usuario recibirá sus credenciales por email (próximamente)</li>
                    <li>Puede cambiar su contraseña desde su perfil</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Validar que las contraseñas coincidan
    $('#password_confirm').on('input', function() {
        const password = $('#password').val();
        const confirm = $(this).val();
        
        if (confirm && password !== confirm) {
            this.setCustomValidity('Las contraseñas no coinciden');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Mostrar descripción del rol seleccionado
    $('#id_rol').on('change', function() {
        const rolId = $(this).val();
        // Aquí podrías agregar lógica para mostrar permisos del rol
    });
});
</script>
