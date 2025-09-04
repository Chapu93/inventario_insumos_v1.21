<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo app_base_url(); ?>/public/css/style.css" rel="stylesheet">

    <!-- jQuery early to allow page scripts to run -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Select2 JS early for page-level initializations -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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