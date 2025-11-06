<?php
require_once '../../../includes/config.php';

// Requerir autenticación y permiso de editar usuarios
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
    $stmt = $db->prepare("SELECT u.*, r.nombre_rol FROM usuarios u 
                          LEFT JOIN roles r ON u.id_rol = r.id_rol 
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

$esYo = $usuario['id_usuario'] == obtenerUsuarioId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validar campos
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $id_rol = (int)($_POST['id_rol'] ?? 0);
        $cambiar_password = !empty($_POST['password']);
        
        if (empty($username)) $errores[] = 'El nombre de usuario es obligatorio';
        if (empty($email)) $errores[] = 'El email es obligatorio';
        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        if (empty($apellido)) $errores[] = 'El apellido es obligatorio';
        if ($id_rol === 0) $errores[] = 'Debe seleccionar un rol';
        
        // Validar formato de email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido';
        }
        
        // Validar username
        if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres';
        }
        
        // Verificar duplicados (excepto el usuario actual)
        if (empty($errores)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? AND id_usuario != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = 'El nombre de usuario ya está en uso';
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id_usuario != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = 'El email ya está registrado';
            }
        }
        
        // Validar contraseña si se está cambiando
        if ($cambiar_password) {
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if (strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            }
            if ($password !== $password_confirm) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }
        
        if (empty($errores)) {
            // Guardar datos anteriores para auditoría
            $datosAntes = [
                'username' => $usuario['username'],
                'email' => $usuario['email'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'id_rol' => $usuario['id_rol']
            ];
            
            // Actualizar usuario
            if ($cambiar_password) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios 
                        SET username = ?, email = ?, nombre = ?, apellido = ?, 
                            id_rol = ?, password_hash = ?, modificado_por = ?
                        WHERE id_usuario = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$username, $email, $nombre, $apellido, $id_rol, 
                               $password_hash, obtenerUsuarioId(), $id]);
            } else {
                $sql = "UPDATE usuarios 
                        SET username = ?, email = ?, nombre = ?, apellido = ?, 
                            id_rol = ?, modificado_por = ?
                        WHERE id_usuario = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$username, $email, $nombre, $apellido, $id_rol, 
                               obtenerUsuarioId(), $id]);
            }
            
            $datosDespues = [
                'username' => $username,
                'email' => $email,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'id_rol' => $id_rol,
                'password_cambiada' => $cambiar_password
            ];
            
            $db->commit();
            
            // Registrar en auditoría
            registrarAuditoria(
                'editar_usuario',
                'usuarios',
                "Usuario editado: {$username}",
                'usuario',
                $id,
                $datosAntes,
                $datosDespues
            );
            
            $_SESSION['mensaje'] = 'Usuario actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            
            header('Location: listar.php');
            exit;
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $errores[] = 'Error al actualizar usuario: ' . $e->getMessage();
        error_log('Error en editar usuario: ' . $e->getMessage());
    }
}

// Obtener roles
try {
    $roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
} catch (Exception $e) {
    $roles = [];
    $errores[] = 'Error al cargar roles';
}
?>
<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-user-edit me-2"></i>Editar Usuario
            <?php if ($esYo): ?>
                <span class="badge bg-info">Mi perfil</span>
            <?php endif; ?>
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
                                   value="<?php echo htmlspecialchars($usuario['username']); ?>"
                                   pattern="[a-zA-Z0-9_]{3,50}"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>"
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
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="apellido" 
                                   name="apellido" 
                                   value="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_rol" class="form-label">Rol *</label>
                        <select class="form-select" 
                                id="id_rol" 
                                name="id_rol" 
                                <?php echo ($esYo && !tienePermiso('usuarios', 'cambiar_rol')) ? 'disabled' : ''; ?>
                                required>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id_rol']; ?>"
                                        <?php echo ($usuario['id_rol'] == $rol['id_rol']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($esYo && !tienePermiso('usuarios', 'cambiar_rol')): ?>
                            <input type="hidden" name="id_rol" value="<?php echo $usuario['id_rol']; ?>">
                            <small class="text-muted">No puedes cambiar tu propio rol</small>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">
                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                        <small class="text-muted">(Dejar en blanco para mantener la actual)</small>
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   minlength="6">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <strong>Fecha de Creación:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?>
                </p>
                
                <?php if ($usuario['ultimo_acceso']): ?>
                <p class="small mb-2">
                    <strong>Último Acceso:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])); ?>
                </p>
                <?php endif; ?>
                
                <?php if ($usuario['fecha_modificacion']): ?>
                <p class="small mb-0">
                    <strong>Última Modificación:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_modificacion'])); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card bg-warning bg-opacity-10 border-warning">
            <div class="card-body">
                <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Importante</h6>
                <ul class="small mb-0">
                    <li>Los cambios se aplicarán inmediatamente</li>
                    <li>Si cambias la contraseña, el usuario deberá usar la nueva</li>
                    <li>El cambio de rol puede restringir o ampliar permisos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Validar contraseñas
    $('#password, #password_confirm').on('input', function() {
        const password = $('#password').val();
        const confirm = $('#password_confirm').val();
        
        if (password || confirm) {
            if (password !== confirm) {
                $('#password_confirm')[0].setCustomValidity('Las contraseñas no coinciden');
            } else {
                $('#password_confirm')[0].setCustomValidity('');
            }
        } else {
            $('#password_confirm')[0].setCustomValidity('');
        }
    });
});
</script>
