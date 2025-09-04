<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-pbEqbR4QF+ZzS9E1x8J5VYwVnF5W1mJvBN1ZQm1v7YzWCNnQdQ3rL1i0k8bVt8nJcZk3F7QmQX0vQv+8N4Jw0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" integrity="sha384-hZZ6e5e0gM8O8uS9S8h8o8gZfl6E0s7v7HjQ3hZlQkqZ6gYc9Q3WcY3dYb8TzQ5e" crossorigin="anonymous">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" integrity="sha384-D0kTn3c9u2iX8w6N7V6m0r3EoY3c2vJtX9t2x0s9K5iW6m4n2j1h0b8p7e6q5r2u" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" integrity="sha384-4mGk9zZC7Q5rXcG3cQ0Y0QZbX0o9aFh2b1c3u6m6p6q7r8s9t0u1v2w3x4y5z6a" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link href="<?php echo app_base_url(); ?>/public/css/style.css" rel="stylesheet">

    <!-- jQuery early to allow page scripts to run -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-7NfXqQZ5B2zQJxWk1jv6kR9zQ9Oq5Zy3Z9v2a5b5b5s=" crossorigin="anonymous"></script>
    <!-- Select2 JS early for page-level initializations -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" integrity="sha384-8yG7oQ9d5L1qC3k8J2m4Q0Q5W8x9Z6y7U1o2P3Q4R5S6T7U8V9W0X1Y2Z3A4B5C6" crossorigin="anonymous"></script>
    <script>window.APP_BASE_URL = '<?php echo app_base_url(); ?>';</script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-boxes me-2"></i>
                    <?php echo APP_NAME; ?>
                </h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                
                <li>
                    <a href="#insumosSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-box me-2"></i>Insumos
                    </a>
                    <ul class="collapse list-unstyled" id="insumosSubmenu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php">Listar Insumos</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/agregar.php">Agregar Insumo</a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="#asignacionesSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-clipboard-list me-2"></i>Asignaciones
                    </a>
                    <ul class="collapse list-unstyled" id="asignacionesSubmenu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php">Listar Asignaciones</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/asignaciones/nueva.php">Nueva Asignación</a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-cog me-2"></i>Administración
                    </a>
                    <ul class="collapse list-unstyled" id="adminSubmenu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/sedes.php">Gestión de Sedes</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/areas.php">Gestión de Áreas</a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/reportes/remito.php" class="nav-link">
                        <i class="fas fa-file-pdf me-2"></i>Remitos
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    
                    <div class="ms-auto">
                        <span class="navbar-text">
                            <i class="fas fa-user me-2"></i>Sistema de Gestión
                        </span>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid mt-3">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                    ?>
                <?php endif; ?> 