<?php
require_once '../../../includes/config.php';

// Requerir autenticación y permiso de editar usuarios
requerirAutenticacion();
verificarPermiso('usuarios', 'editar');

// Verificar permiso de reset password
verificarPermiso('usuarios', 'reset_password');

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
    $stmt = $db->prepare("SELECT id_usuario, username, nombre, apellido, email FROM usuarios WHERE id_usuario = ?");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errores[] = 'Error de validación CSRF';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($password !== $password_confirm) {
            $errores[] = 'Las contraseñas no coinciden';
        }
        
        if (empty($errores)) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE usuarios SET password_hash = ?, modificado_por = ? WHERE id_usuario = ?");
                $stmt->execute([$password_hash, obtenerUsuarioId(), $id]);
                
                registrarAuditoria(
                    'reset_password',
                    'usuarios',
                    "Contraseña restablecida para usuario: {$usuario['username']}",
                    'usuario',
                    $id
                );
                
                $_SESSION['mensaje'] = "Contraseña restablecida correctamente para el usuario {$usuario['username']}.";
                $_SESSION['tipo_mensaje'] = 'success';
                
                header('Location: listar.php');
                exit;
                
            } catch (Exception $e) {
                $errores[] = 'Error al actualizar contraseña: ' . $e->getMessage();
                Logger::error('Error en reset password', ['error' => $e->getMessage()]);
            }
        }
    }
}
?>
<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-key me-2"></i>Restablecer Contraseña
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

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="mb-0 text-dark">
                    <i class="fas fa-user-lock me-2"></i>Usuario: <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Estás a punto de cambiar la contraseña para el usuario 
                    <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>.
                    Esta acción no se puede deshacer.
                </p>
                
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="btnTogglePass">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary mb-2" id="btnGenerar">
                            <i class="fas fa-random me-2"></i>Generar Aleatoria
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Nueva Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('#btnTogglePass').on('click', function() {
        const input = $('#password');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Generar contraseña aleatoria
    $('#btnGenerar').on('click', function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        $('#password').val(pass).attr('type', 'text');
        $('#password_confirm').val(pass);
        $('#btnTogglePass i').removeClass('fa-eye').addClass('fa-eye-slash');
        
        // Copiar al portapapeles
        navigator.clipboard.writeText(pass).then(function() {
            showToast('Contraseña generada y copiada al portapapeles', 'success');
        }, function() {
            showToast('Contraseña generada', 'success');
        });
    });
    
    // Validar coincidencia
    $('#password, #password_confirm').on('input', function() {
        const p1 = $('#password').val();
        const p2 = $('#password_confirm').val();
        
        if (p2 && p1 !== p2) {
            $('#password_confirm')[0].setCustomValidity('Las contraseñas no coinciden');
        } else {
            $('#password_confirm')[0].setCustomValidity('');
        }
    });
});
</script>
