<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="es">
<!-- Inicializar tema antes de cargar CSS para evitar flash - @added v2.0 -->
<script>
(function() {
    var tema = localStorage.getItem('sitia_tema') || 'light';
    document.documentElement.setAttribute('data-theme', tema);
})();
</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    
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
        <nav id="sidebar" class="sidebar" role="navigation" aria-label="Menú lateral">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-boxes me-2"></i>
                    <?php echo APP_NAME; ?>
                </h3>
            </div>

            <?php
                $currentPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
                $isDashboard = strpos($currentPath, '/pages/dashboard.php') !== false;
                $isInsumos = strpos($currentPath, '/pages/insumos/') !== false;
                $isAsignaciones = strpos($currentPath, '/pages/asignaciones/') !== false;
                $isReportes = strpos($currentPath, '/pages/reportes/') !== false;

                // Robust section detection using explicit item lists
                $adminItems = [
                    '/pages/admin/sede_detalle.php',
                    '/pages/admin/sedes.php',
                    '/pages/admin/areas.php',
                    '/pages/admin/telecom_planos.php' // Planos de Sede pertenece a Administración
                ];
                $telecomItems = [
                    '/pages/admin/telecom_internet.php',
                    '/pages/admin/telecom_telefonia.php',
                    '/pages/admin/telecom_red.php',
                    '/pages/admin/telecom_vigilancia.php',
                    '/pages/admin/telecom_vigilancia_servicio.php',
                    '/pages/admin/telecom_resumen.php'
                ];

                $isAdmin = false;
                foreach ($adminItems as $p) { if (strpos($currentPath, $p) !== false) { $isAdmin = true; break; } }
                $isTelecom = false;
                foreach ($telecomItems as $p) { if (strpos($currentPath, $p) !== false) { $isTelecom = true; break; } }
            ?>

            <ul class="list-unstyled components" role="menubar">
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/dashboard.php" class="nav-link <?php echo $isDashboard ? 'active' : ''; ?>" role="menuitem">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                
                
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/pedidos/listar.php" class="nav-link <?php echo strpos($currentPath, '/pages/pedidos/') !== false ? 'active' : ''; ?>" role="menuitem">
                        <i class="fas fa-clipboard-list me-2"></i>Pedidos y Pendientes
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php" class="nav-link <?php echo $isInsumos ? 'active' : ''; ?>" role="menuitem" data-collapse-target="#insumosSubmenu">
                        <i class="fas fa-box me-2"></i>Insumos
                    </a>
                    <ul class="collapse list-unstyled <?php echo $isInsumos ? 'show' : ''; ?>" id="insumosSubmenu" data-bs-parent="#sidebar" role="menu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/ingresos_listar.php" class="<?php echo strpos($currentPath, '/pages/insumos/ingresos_listar.php') !== false ? 'active' : ''; ?>" role="menuitem">Ingresos</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/movimientos.php" class="<?php echo strpos($currentPath, '/pages/insumos/movimientos.php') !== false ? 'active' : ''; ?>" role="menuitem">Movimientos de Stock</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/intervenidos.php" class="<?php echo strpos($currentPath, '/pages/insumos/intervenidos.php') !== false ? 'active' : ''; ?>" role="menuitem">Insumos Intervenidos</a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php" class="nav-link <?php echo ($isAsignaciones || $isReportes) ? 'active' : ''; ?>" role="menuitem" data-collapse-target="#asigSubmenu">
                        <i class="fas fa-clipboard-list me-2"></i>Asignaciones
                    </a>
                    <ul class="collapse list-unstyled <?php echo ($isAsignaciones || $isReportes) ? 'show' : ''; ?>" id="asigSubmenu" data-bs-parent="#sidebar" role="menu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/reportes/remito.php" class="<?php echo strpos($currentPath, '/pages/reportes/remito.php') !== false ? 'active' : ''; ?>" role="menuitem">Remitos</a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/admin/telecom_resumen.php" class="nav-link <?php echo $isTelecom ? 'active' : ''; ?>" role="menuitem" data-collapse-target="#telecomSubmenu">
                        <i class="fas fa-network-wired me-2"></i>Telecomunicaciones
                    </a>
                    <ul class="collapse list-unstyled <?php echo $isTelecom ? 'show' : ''; ?>" id="telecomSubmenu" data-bs-parent="#sidebar" role="menu">
                        <li><a role="menuitem" href="<?php echo app_base_url(); ?>/pages/admin/telecom_internet.php" class="<?php echo strpos($currentPath, '/pages/admin/telecom_internet.php') !== false ? 'active' : ''; ?>">Internet por Sede</a></li>
                        <li><a role="menuitem" href="<?php echo app_base_url(); ?>/pages/admin/telecom_telefonia.php" class="<?php echo strpos($currentPath, '/pages/admin/telecom_telefonia.php') !== false ? 'active' : ''; ?>">Líneas Telefónicas</a></li>
                        <li><a role="menuitem" href="<?php echo app_base_url(); ?>/pages/admin/telecom_red.php" class="<?php echo strpos($currentPath, '/pages/admin/telecom_red.php') !== false ? 'active' : ''; ?>">Infraestructura de Red</a></li>
                        <li><a role="menuitem" href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php" class="<?php echo strpos($currentPath, '/pages/admin/telecom_vigilancia.php') !== false ? 'active' : ''; ?>">Vigilancia</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?php echo app_base_url(); ?>/pages/admin/sede_detalle.php" class="nav-link <?php echo $isAdmin ? 'active' : ''; ?>" role="menuitem" data-collapse-target="#adminSubmenu">
                        <i class="fas fa-cog me-2"></i>Administración
                    </a>
                    <ul class="collapse list-unstyled <?php echo $isAdmin ? 'show' : ''; ?>" id="adminSubmenu" data-bs-parent="#sidebar" role="menu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/sedes.php" class="<?php echo strpos($currentPath, '/pages/admin/sedes.php') !== false ? 'active' : ''; ?>" role="menuitem">Gestión de Sedes</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/areas.php" class="<?php echo strpos($currentPath, '/pages/admin/areas.php') !== false ? 'active' : ''; ?>" role="menuitem">Gestión de Áreas</a>
                        </li>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/telecom_planos.php" class="<?php echo strpos($currentPath, '/pages/admin/telecom_planos.php') !== false ? 'active' : ''; ?>" role="menuitem">Planos de Sede</a>
                        </li>
                        <?php if (tienePermiso('sistema', 'backup')): ?>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/backup.php" class="<?php echo strpos($currentPath, '/pages/admin/backup.php') !== false ? 'active' : ''; ?>" role="menuitem">Copia de Seguridad</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <?php if (estaAutenticado() && tienePermiso('usuarios', 'ver')): 
                    $isUsuarios = strpos($currentPath, '/pages/admin/usuarios/') !== false;
                ?>
                <li>
                    <a href="#usuariosSubmenu" class="nav-link <?php echo $isUsuarios ? 'active' : ''; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $isUsuarios ? 'true' : 'false'; ?>" aria-controls="usuariosSubmenu">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                    <ul class="collapse list-unstyled <?php echo $isUsuarios ? 'show' : ''; ?>" id="usuariosSubmenu" data-bs-parent="#sidebar" role="menu">
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/usuarios/listar.php" class="<?php echo strpos($currentPath, '/pages/admin/usuarios/listar.php') !== false ? 'active' : ''; ?>" role="menuitem">Gestión de Usuarios</a>
                        </li>
                        <?php if (tienePermiso('auditoria', 'ver_todo')): ?>
                        <li>
                            <a href="<?php echo app_base_url(); ?>/pages/admin/auditoria.php" class="<?php echo strpos($currentPath, '/pages/admin/auditoria.php') !== false ? 'active' : ''; ?>" role="menuitem">Auditoría</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary d-lg-none" type="button" id="btnToggleSidebar" aria-label="Alternar menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Búsqueda Global - @added v2.0 -->
                    <?php if (estaAutenticado()): ?>
                    <div class="position-relative mx-3 d-none d-md-block" id="busquedaGlobalContainer">
                        <div class="input-group" style="width: 280px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0 ps-0" 
                                   id="busquedaGlobalInput" 
                                   placeholder="Buscar... (Ctrl+K)"
                                   autocomplete="off">
                        </div>
                        <div id="busquedaGlobalResultados" 
                             class="position-absolute bg-white shadow-lg rounded-3 mt-1 w-100 d-none" 
                             style="z-index: 1050; max-height: 400px; overflow-y: auto; min-width: 320px;">
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Fin Búsqueda Global -->
                    
                    <div class="ms-auto d-flex align-items-center">
                        <?php if (estaAutenticado()): 
                            $usuarioActual = obtenerUsuario();
                        ?>
                            <div class="dropdown">
                                <button class="btn btn-link text-decoration-none dropdown-toggle" type="button" id="dropdownUsuario" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($usuarioActual['nombre'] ?? 'Usuario'); ?></span>
                                    <small class="text-muted d-none d-lg-inline">(<?php echo htmlspecialchars($usuarioActual['nombre_rol'] ?? ''); ?>)</small>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUsuario">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars(trim($usuarioActual['nombre'] . ' ' . $usuarioActual['apellido'])); ?>
                                        </h6>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo app_base_url(); ?>/pages/admin/usuarios/editar.php?id=<?php echo $usuarioActual['id_usuario']; ?>">
                                            <i class="fas fa-user-edit me-2"></i>Mi Perfil
                                        </a>
                                    </li>
                                    <?php if (tienePermiso('usuarios', 'ver')): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo app_base_url(); ?>/pages/admin/usuarios/listar.php">
                                            <i class="fas fa-users me-2"></i>Gestionar Usuarios
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (tienePermiso('auditoria', 'ver_todo')): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo app_base_url(); ?>/pages/admin/auditoria.php">
                                            <i class="fas fa-clipboard-list me-2"></i>Auditoría
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <!-- Toggle Modo Oscuro - @added v2.0 -->
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnToggleTema">
                                            <i class="fas fa-moon me-2" id="iconoTema"></i>
                                            <span id="textoTema">Modo Oscuro</span>
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo app_base_url(); ?>/logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo app_base_url(); ?>/login.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid mt-3">
                <?php if (isset($_SESSION['mensaje'])): 
                    $msgText = htmlspecialchars($_SESSION['mensaje'], ENT_QUOTES);
                    $msgType = $_SESSION['tipo_mensaje'] ?? 'success';
                    // Mapear tipos de alert Bootstrap a tipos de toast
                    $toastType = 'success';
                    if ($msgType === 'danger' || $msgType === 'error') $toastType = 'error';
                    elseif ($msgType === 'warning') $toastType = 'warning';
                    elseif ($msgType === 'info') $toastType = 'success';
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof showToast === 'function') {
                            showToast('<?php echo $msgText; ?>', '<?php echo $toastType; ?>');
                        }
                    });
                </script>
                <?php endif; ?> 