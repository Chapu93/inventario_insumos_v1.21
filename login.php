<?php
require_once 'includes/config.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

// Procesar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $resultado = iniciarSesion($username, $password);
        
        if ($resultado['success']) {
            // Redirigir a la página solicitada o al dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? app_base_url() . '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $resultado['mensaje'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            margin-bottom: 5px;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-floating > label {
            color: #6c757d;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #667eea;
        }
        
        .form-control {
            border-left: none;
        }
        
        .input-group:focus-within .input-group-text {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-boxes"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Acceso al sistema</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'info'; ?> d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div><?php echo htmlspecialchars($_SESSION['mensaje']); ?></div>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                    ?>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Usuario o email"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required 
                                       autofocus>
                                <label for="username">Usuario o email</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Contraseña"
                                       required>
                                <label for="password">Contraseña</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <i class="fas fa-shield-alt me-1"></i>
                Sistema seguro con auditoría completa
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">
                <i class="fas fa-info-circle me-1"></i>
                Usuario por defecto: <strong>admin</strong> / Contraseña: <strong>admin123</strong>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
