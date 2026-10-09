<?php
// Restricción de seguridad: solo loopback local
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Acceso denegado: este recurso es exclusivo del entorno local de desarrollo.');
}

define('APP_INIT', true);
require_once __DIR__ . '/../../includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Componentes SITIA</title>
    
    <!-- Script idéntico a header.php: sincronización temprana sin data-theme fijo -->
    <script>
    (function() {
        var tema = localStorage.getItem('sitia_tema') || 'light';
        document.documentElement.setAttribute('data-theme', tema);
    })();
    </script>

    <!-- Vendor CSS -->
    <link href="<?php echo app_base_url(); ?>/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/vendor/fontawesome/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo app_base_url(); ?>/public/vendor/select2/css/select2.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/vendor/select2/css/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Carga de CSS: Fase 6 (css.php) o style.css directo con filemtime -->
    <?php
    $cssLoader = __DIR__ . '/../../includes/css.php';
    if (file_exists($cssLoader)) {
        include $cssLoader;
    } else {
        $stylePath = __DIR__ . '/../../public/css/style.css';
        $v = file_exists($stylePath) ? filemtime($stylePath) : time();
        echo '<link href="' . app_base_url() . '/public/css/style.css?v=' . $v . '" rel="stylesheet">';
    }
    ?>
</head>
<body>
    <!-- Envoltura con el mismo árbol de layout que la aplicación -->
    <div class="wrapper">
        <div id="content" style="margin-left: 0; width: 100%;">
            <div class="container-fluid p-4">
                <h2 class="mb-4"><i class="fas fa-palette me-2"></i>Galería de Componentes y Estados SITIA</h2>

                <!-- 1. STEPPER REAL DE AGREGAR_NUEVA.PHP -->
                <section class="card mb-4">
                    <div class="card-header"><h5>1. Stepper Multi-paso (HTML real)</h5></div>
                    <div class="card-body">
                        <div class="stepper">
                            <div class="step step-1 done"><span class="circle"><i class="fas fa-check"></i></span><span>Datos de Asignación</span></div>
                            <div class="divider done"></div>
                            <div class="step step-2 active"><span class="circle">2</span><span>Alta de Insumo</span></div>
                            <div class="divider"></div>
                            <div class="step step-3"><span class="circle">3</span><span>Confirmación</span></div>
                        </div>
                    </div>
                </section>

                <!-- 2. BOTONES Y ESTADOS (MATRIZ COMPLETA DE VARIANTES Y ESTADOS) -->
                <section class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">2. Botones (Matriz de Variantes x Estados x Temas)</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="btn_primary_in_card_header">Header Action</button>
                    </div>
                    <div class="card-body">
                        <!-- Botones clave para tests de interacción existentes -->
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-4 pb-3 border-bottom">
                            <button type="button" class="btn btn-primary" id="test_focus_trigger">Focus Trigger</button>
                            <button type="button" class="btn btn-outline-primary" id="test_btn_outline">Outline Target</button>
                            <button type="button" class="btn btn-primary" id="test_btn_primary">Primary Normal</button>
                        </div>

                        <!-- Matriz Completa de Variantes x Estados -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle text-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Variante</th>
                                        <th>Normal (Target CDP) <button type="button" class="btn btn-primary btn-xs ms-1" id="btn_primary_in_th">TH Action</button></th>
                                        <th>Hover</th>
                                        <th>Active</th>
                                        <th>Disabled</th>
                                        <th>Focus Visible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-bold">Primary</td>
                                        <td><button type="button" class="btn btn-primary" id="btn_matrix_primary">Primary</button></td>
                                        <td><button type="button" class="btn btn-primary">Hover</button></td>
                                        <td><button type="button" class="btn btn-primary active">Active</button></td>
                                        <td><button type="button" class="btn btn-primary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-primary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Primary</td>
                                        <td><button type="button" class="btn btn-outline-primary" id="btn_matrix_outline_primary">Outline Primary</button></td>
                                        <td><button type="button" class="btn btn-outline-primary">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-primary active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-primary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-primary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Secondary</td>
                                        <td><button type="button" class="btn btn-secondary" id="btn_matrix_secondary">Secondary</button></td>
                                        <td><button type="button" class="btn btn-secondary">Hover</button></td>
                                        <td><button type="button" class="btn btn-secondary active">Active</button></td>
                                        <td><button type="button" class="btn btn-secondary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-secondary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Success</td>
                                        <td><button type="button" class="btn btn-success" id="btn_matrix_success">Success</button></td>
                                        <td><button type="button" class="btn btn-success">Hover</button></td>
                                        <td><button type="button" class="btn btn-success active">Active</button></td>
                                        <td><button type="button" class="btn btn-success disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-success">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Danger</td>
                                        <td><button type="button" class="btn btn-danger" id="btn_matrix_danger">Danger</button></td>
                                        <td><button type="button" class="btn btn-danger">Hover</button></td>
                                        <td><button type="button" class="btn btn-danger active">Active</button></td>
                                        <td><button type="button" class="btn btn-danger disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-danger">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Info</td>
                                        <td><button type="button" class="btn btn-info" id="btn_matrix_info">Info</button></td>
                                        <td><button type="button" class="btn btn-info">Hover</button></td>
                                        <td><button type="button" class="btn btn-info active">Active</button></td>
                                        <td><button type="button" class="btn btn-info disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-info">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Warning</td>
                                        <td><button type="button" class="btn btn-warning" id="btn_matrix_warning">Warning</button></td>
                                        <td><button type="button" class="btn btn-warning">Hover</button></td>
                                        <td><button type="button" class="btn btn-warning active">Active</button></td>
                                        <td><button type="button" class="btn btn-warning disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-warning">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Soft Primary</td>
                                        <td><button type="button" class="btn btn-soft-primary" id="btn_matrix_soft_primary">Soft Primary</button></td>
                                        <td><button type="button" class="btn btn-soft-primary">Hover</button></td>
                                        <td><button type="button" class="btn btn-soft-primary active">Active</button></td>
                                        <td><button type="button" class="btn btn-soft-primary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-soft-primary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Soft Success</td>
                                        <td><button type="button" class="btn btn-soft-success" id="btn_matrix_soft_success">Soft Success</button></td>
                                        <td><button type="button" class="btn btn-soft-success">Hover</button></td>
                                        <td><button type="button" class="btn btn-soft-success active">Active</button></td>
                                        <td><button type="button" class="btn btn-soft-success disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-soft-success">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Soft Warning</td>
                                        <td><button type="button" class="btn btn-soft-warning" id="btn_matrix_soft_warning">Soft Warning</button></td>
                                        <td><button type="button" class="btn btn-soft-warning">Hover</button></td>
                                        <td><button type="button" class="btn btn-soft-warning active">Active</button></td>
                                        <td><button type="button" class="btn btn-soft-warning disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-soft-warning">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Soft Secondary</td>
                                        <td><button type="button" class="btn btn-soft-secondary" id="btn_matrix_soft_secondary">Soft Secondary</button></td>
                                        <td><button type="button" class="btn btn-soft-secondary">Hover</button></td>
                                        <td><button type="button" class="btn btn-soft-secondary active">Active</button></td>
                                        <td><button type="button" class="btn btn-soft-secondary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-soft-secondary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Pastel Brown</td>
                                        <td><button type="button" class="btn btn-pastel-brown" id="btn_matrix_pastel_brown">Pastel Brown</button></td>
                                        <td><button type="button" class="btn btn-pastel-brown">Hover</button></td>
                                        <td><button type="button" class="btn btn-pastel-brown active">Active</button></td>
                                        <td><button type="button" class="btn btn-pastel-brown disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-pastel-brown">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Colaborativa</td>
                                        <td><button type="button" class="btn btn-colaborativa" id="btn_matrix_colaborativa">Colaborativa</button></td>
                                        <td><button type="button" class="btn btn-colaborativa">Hover</button></td>
                                        <td><button type="button" class="btn btn-colaborativa active">Active</button></td>
                                        <td><button type="button" class="btn btn-colaborativa disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-colaborativa">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Btn Close</td>
                                        <td><button type="button" class="btn-close" id="btn_matrix_btn_close" aria-label="Close"></button></td>
                                        <td><button type="button" class="btn-close" aria-label="Close"></button></td>
                                        <td><button type="button" class="btn-close active" aria-label="Close"></button></td>
                                        <td><button type="button" class="btn-close disabled" disabled aria-label="Close"></button></td>
                                        <td><button type="button" class="btn-close" aria-label="Close"></button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Small (.btn-sm)</td>
                                        <td><button type="button" class="btn btn-primary btn-sm" id="btn_matrix_btn_sm">Primary Small</button></td>
                                        <td><button type="button" class="btn btn-primary btn-sm">Hover</button></td>
                                        <td><button type="button" class="btn btn-primary btn-sm active">Active</button></td>
                                        <td><button type="button" class="btn btn-primary btn-sm disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-primary btn-sm">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Light</td>
                                        <td><button type="button" class="btn btn-light" id="btn_matrix_light">Light</button></td>
                                        <td><button type="button" class="btn btn-light">Hover</button></td>
                                        <td><button type="button" class="btn btn-light active">Active</button></td>
                                        <td><button type="button" class="btn btn-light disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-light">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Secondary</td>
                                        <td><button type="button" class="btn btn-outline-secondary" id="btn_matrix_outline_secondary">Outline Secondary</button></td>
                                        <td><button type="button" class="btn btn-outline-secondary">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-secondary active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-secondary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-secondary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Success</td>
                                        <td><button type="button" class="btn btn-outline-success" id="btn_matrix_outline_success">Outline Success</button></td>
                                        <td><button type="button" class="btn btn-outline-success">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-success active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-success disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-success">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Danger</td>
                                        <td><button type="button" class="btn btn-outline-danger" id="btn_matrix_outline_danger">Outline Danger</button></td>
                                        <td><button type="button" class="btn btn-outline-danger">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-danger active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-danger disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-danger">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Warning</td>
                                        <td><button type="button" class="btn btn-outline-warning" id="btn_matrix_outline_warning">Outline Warning</button></td>
                                        <td><button type="button" class="btn btn-outline-warning">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-warning active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-warning disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-warning">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Sidebar Toggle</td>
                                        <td><button type="button" class="btn btn-sidebar-toggle" id="btn_matrix_sidebar_toggle"><i class="fas fa-bars"></i></button></td>
                                        <td><button type="button" class="btn btn-sidebar-toggle"><i class="fas fa-bars"></i></button></td>
                                        <td><button type="button" class="btn btn-sidebar-toggle active"><i class="fas fa-bars"></i></button></td>
                                        <td><button type="button" class="btn btn-sidebar-toggle disabled" disabled><i class="fas fa-bars"></i></button></td>
                                        <td><button type="button" class="btn btn-sidebar-toggle"><i class="fas fa-bars"></i></button></td>
                                    </tr>
                                    <!-- Combinaciones de Botones con Utilidades Bootstrap -->
                                    <tr>
                                        <td class="text-start fw-bold">Info + text-white</td>
                                        <td><button type="button" class="btn btn-info text-white" id="btn_matrix_info_text_white">Info</button></td>
                                        <td><button type="button" class="btn btn-info text-white">Hover</button></td>
                                        <td><button type="button" class="btn btn-info text-white active">Active</button></td>
                                        <td><button type="button" class="btn btn-info text-white disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-info text-white">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Light + text-primary</td>
                                        <td><button type="button" class="btn btn-light text-primary" id="btn_matrix_light_text_primary">Light</button></td>
                                        <td><button type="button" class="btn btn-light text-primary">Hover</button></td>
                                        <td><button type="button" class="btn btn-light text-primary active">Active</button></td>
                                        <td><button type="button" class="btn btn-light text-primary disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-light text-primary">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Link + text-danger</td>
                                        <td><button type="button" class="btn btn-link text-danger" id="btn_matrix_link_text_danger">Link</button></td>
                                        <td><button type="button" class="btn btn-link text-danger">Hover</button></td>
                                        <td><button type="button" class="btn btn-link text-danger active">Active</button></td>
                                        <td><button type="button" class="btn btn-link text-danger disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-link text-danger">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Link + text-muted</td>
                                        <td><button type="button" class="btn btn-link text-muted" id="btn_matrix_link_text_muted">Link</button></td>
                                        <td><button type="button" class="btn btn-link text-muted">Hover</button></td>
                                        <td><button type="button" class="btn btn-link text-muted active">Active</button></td>
                                        <td><button type="button" class="btn btn-link text-muted disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-link text-muted">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Outline Danger + border-opacity-25</td>
                                        <td><button type="button" class="btn btn-outline-danger border-opacity-25" id="btn_matrix_outline_danger_border_opacity_25">Danger</button></td>
                                        <td><button type="button" class="btn btn-outline-danger border-opacity-25">Hover</button></td>
                                        <td><button type="button" class="btn btn-outline-danger border-opacity-25 active">Active</button></td>
                                        <td><button type="button" class="btn btn-outline-danger border-opacity-25 disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-outline-danger border-opacity-25">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Secondary + text-muted</td>
                                        <td><button type="button" class="btn btn-secondary text-muted" id="btn_matrix_secondary_text_muted">Secondary</button></td>
                                        <td><button type="button" class="btn btn-secondary text-muted">Hover</button></td>
                                        <td><button type="button" class="btn btn-secondary text-muted active">Active</button></td>
                                        <td><button type="button" class="btn btn-secondary text-muted disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-secondary text-muted">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Secondary + text-white</td>
                                        <td><button type="button" class="btn btn-secondary text-white" id="btn_matrix_secondary_text_white">Secondary</button></td>
                                        <td><button type="button" class="btn btn-secondary text-white">Hover</button></td>
                                        <td><button type="button" class="btn btn-secondary text-white active">Active</button></td>
                                        <td><button type="button" class="btn btn-secondary text-white disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-secondary text-white">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Success + text-white</td>
                                        <td><button type="button" class="btn btn-success text-white" id="btn_matrix_success_text_white">Success</button></td>
                                        <td><button type="button" class="btn btn-success text-white">Hover</button></td>
                                        <td><button type="button" class="btn btn-success text-white active">Active</button></td>
                                        <td><button type="button" class="btn btn-success text-white disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-success text-white">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Warning + text-dark</td>
                                        <td><button type="button" class="btn btn-warning text-dark" id="btn_matrix_warning_text_dark">Warning</button></td>
                                        <td><button type="button" class="btn btn-warning text-dark">Hover</button></td>
                                        <td><button type="button" class="btn btn-warning text-dark active">Active</button></td>
                                        <td><button type="button" class="btn btn-warning text-dark disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-warning text-dark">Focus</button></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold">Warning + text-white</td>
                                        <td><button type="button" class="btn btn-warning text-white" id="btn_matrix_warning_text_white">Warning</button></td>
                                        <td><button type="button" class="btn btn-warning text-white">Hover</button></td>
                                        <td><button type="button" class="btn btn-warning text-white active">Active</button></td>
                                        <td><button type="button" class="btn btn-warning text-white disabled" disabled>Disabled</button></td>
                                        <td><button type="button" class="btn btn-warning text-white">Focus</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- 3. BADGES Y ESTADOS -->
                <section class="card mb-4">
                    <div class="card-header"><h5>3. Badges y Estados Dinámicos</h5></div>
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-primary" id="badge_matrix_primary">Primary</span>
                        <span class="badge bg-secondary" id="badge_matrix_secondary">Secondary</span>
                        <span class="badge bg-success" id="badge_matrix_success">Success</span>
                        <span class="badge bg-danger" id="badge_matrix_danger">Danger</span>
                        <span class="badge bg-warning" id="badge_matrix_warning">Warning</span>
                        <span class="badge bg-info" id="badge_matrix_info">Info</span>
                        <span class="badge bg-dark" id="badge_matrix_dark">Dark</span>
                        <span class="badge badge-tipo" id="badge_matrix_tipo">Badge Tipo</span>
                        <span class="badge badge-colaborativa" id="badge_matrix_colaborativa">Badge Colaborativa</span>
                        <span class="badge estado-disponible" id="badge_matrix_estado_disponible">estado-disponible</span>
                        <span class="badge estado-activa" id="badge_matrix_estado_activa">estado-activa</span>
                        <span class="badge estado-asignado" id="badge_matrix_estado_asignado">estado-asignado</span>
                        <span class="badge estado-parcial" id="badge_matrix_estado_parcial">estado-parcial</span>
                        <span class="badge estado-baja" id="badge_matrix_estado_baja">estado-baja</span>
                        <span class="badge estado-devuelta" id="badge_matrix_estado_devuelta">estado-devuelta</span>
                        <span class="badge estado-anulado" id="badge_matrix_estado_anulado">estado-anulado</span>
                    </div>
                </section>

                <!-- 4. ALERTAS -->
                <section class="card mb-4">
                    <div class="card-header"><h5>4. Alertas</h5></div>
                    <div class="card-body">
                        <div class="alert alert-success mb-2" id="alert_matrix_success">Alerta Success de prueba</div>
                        <div class="alert alert-warning mb-2" id="alert_matrix_warning">Alerta Warning de prueba</div>
                        <div class="alert alert-danger mb-2" id="alert_matrix_danger">Alerta Danger de prueba</div>
                        <div class="alert alert-info mb-2" id="alert_matrix_info">Alerta Info de prueba</div>
                        <div class="alert alert-light mb-0" id="alert_matrix_light">Alerta Light de prueba</div>
                    </div>
                </section>

                <!-- 5. FORMULARIOS, VALIDACIÓN Y SELECT2 -->
                <section class="card mb-4">
                    <div class="card-header"><h5>5. Formularios, Validación e Input-Groups</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Normal (.form-control)</label>
                            <input type="text" class="form-control" id="test_focus_form_control" value="Texto normal">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Select (.form-select)</label>
                            <select class="form-select" id="test_focus_form_select">
                                <option value="1">Opción 1</option>
                                <option value="2">Opción 2</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Check & Switch (.form-check-input)</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="test_focus_form_check" checked>
                                <label class="form-check-label" for="test_focus_form_check">Checkbox</label>
                            </div>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="test_focus_form_switch" checked>
                                <label class="form-check-label" for="test_focus_form_switch">Switch</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Input Deshabilitado</label>
                            <input type="text" class="form-control" id="gallery_input_disabled" disabled value="Deshabilitado">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Select Deshabilitado</label>
                            <select class="form-select" id="gallery_select_disabled" disabled>
                                <option selected>Select deshabilitado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Input Readonly</label>
                            <input type="text" class="form-control" id="gallery_input_readonly" readonly value="Solo lectura">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selector de Archivos (.form-control)</label>
                            <input type="file" class="form-control" id="gallery_file_input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Input Group</label>
                            <div class="input-group">
                                <span class="input-group-text" id="gallery_input_group_text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" placeholder="Prefijo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select2 Real</label>
                            <select id="gallery_select2" class="form-select select2 w-100">
                                <option value="1">Opción A (Insumo informático)</option>
                                <option value="2">Opción B (Periférico)</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- 6. CABECERAS TEMÁTICAS -->
                <section class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--success" id="gallery_header_success">Success Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--info" id="gallery_header_info">Info Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--warning" id="gallery_header_warning">Warning Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--danger" id="gallery_header_danger">Danger Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                </section>

                <!-- 6b. TARJETAS DE KPI Y ESTADÍSTICAS (DASHBOARD) -->
                <section class="row g-3 mb-4" id="gallery_kpi_section">
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card" id="gallery_kpi_base">
                            <h3>10</h3>
                            <p class="mb-0">Base</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card dashboard-card--primary" id="gallery_kpi_primary">
                            <h3>25</h3>
                            <p class="mb-0">Primary</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card dashboard-card--success" id="gallery_kpi_success">
                            <h3>40</h3>
                            <p class="mb-0">Success</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card dashboard-card--info" id="gallery_kpi_info">
                            <h3>18</h3>
                            <p class="mb-0">Info</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card dashboard-card--warning" id="gallery_kpi_warning">
                            <h3>7</h3>
                            <p class="mb-0">Warning</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="dashboard-card dashboard-card--danger" id="gallery_kpi_danger">
                            <h3>3</h3>
                            <p class="mb-0">Danger</p>
                        </div>
                    </div>
                </section>

                <!-- 7. TABLA DE MUESTRA (CON DATATABLES Y BTN-GROUP) -->
                <section class="card mb-4">
                    <div class="card-header"><h5>7. Tablas (Base, Striped, Hover, DataTables y .btn-group)</h5></div>
                    <div class="card-body">
                        <table class="table table-striped table-hover align-middle mb-0" id="gallery_table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Insumo</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Acciones (.table .btn-group .btn)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Notebook Lenovo ThinkPad</td>
                                    <td>Equipos</td>
                                    <td><span class="badge estado-disponible">Disponible</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" id="btn_matrix_table_btn_group">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Impresora HP LaserJet Pro</td>
                                    <td>Impresión</td>
                                    <td><span class="badge estado-asignado">Asignado</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Monitor Dell 24 Pulgadas</td>
                                    <td>Pantallas</td>
                                    <td><span class="badge estado-disponible">Disponible</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>Switch Cisco Catalyst 24P</td>
                                    <td>Redes</td>
                                    <td><span class="badge estado-activa">Activo</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>Router MikroTik RB4011</td>
                                    <td>Redes</td>
                                    <td><span class="badge estado-activa">Activo</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>6</td>
                                    <td>Scanner Fujitsu fi-7160</td>
                                    <td>Digitalización</td>
                                    <td><span class="badge estado-parcial">Mantenimiento</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary">Editar</button>
                                            <button type="button" class="btn btn-sm btn-secondary">Ver</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- 8. DROPDOWNS Y TOOLTIPS -->
                <section class="card mb-4" id="section_dropdown_tooltip">
                    <div class="card-header"><h5>8. Dropdowns y Tooltips</h5></div>
                    <div class="card-body d-flex gap-4 align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle show" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="true">
                                Dropdown Desplegado
                            </button>
                            <ul class="dropdown-menu show position-static" aria-labelledby="dropdownMenuButton" id="gallery_dropdown_menu">
                                <li><a class="dropdown-item" href="#" id="gallery_dropdown_item_1">Acción 1</a></li>
                                <li><a class="dropdown-item" href="#">Acción 2</a></li>
                                <li><hr class="dropdown-divider" id="gallery_dropdown_divider"></li>
                                <li><a class="dropdown-item text-danger" href="#">Acción Peligrosa</a></li>
                            </ul>
                        </div>
                        <div>
                            <button type="button" class="btn btn-info text-white" id="tooltip_target" data-bs-toggle="tooltip" data-bs-placement="top" title="Tooltip de prueba SITIA">
                                Botón con Tooltip
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 9. COMBINACIONES DE COMPONENTE + UTILIDADES -->
                <section class="card mb-4" id="section_component_utilities">
                    <div class="card-header"><h5>9. Combinaciones de Componente + Utilidades de Bootstrap</h5></div>
                    <div class="card-body">
                        <!-- Badges con Utilidades -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Badges con Utilidades (text-*, bg-*, border*, opacity)</h6>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-info text-dark" id="badge_util_info_text_dark">bg-info text-dark</span>
                                <span class="badge bg-warning text-dark" id="badge_util_warning_text_dark">bg-warning text-dark</span>
                                <span class="badge bg-light text-dark border" id="badge_util_light_text_dark_border">bg-light text-dark border</span>
                                <span class="badge bg-light text-muted border" id="badge_util_light_text_muted_border">bg-light text-muted border</span>
                                <span class="badge bg-light text-primary" id="badge_util_light_text_primary">bg-light text-primary</span>
                                <span class="badge bg-white text-dark border" id="badge_util_white_text_dark_border">bg-white text-dark border</span>
                                <span class="badge bg-primary text-white" id="badge_util_primary_text_white">bg-primary text-white</span>
                                <span class="badge bg-success text-white" id="badge_util_success_text_white">bg-success text-white</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" id="badge_util_primary_subtle">bg-primary-subtle text-primary</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" id="badge_util_success_subtle">bg-success-subtle text-success</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold" id="badge_util_warning_subtle">bg-warning-subtle text-warning-emphasis</span>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fw-bold" id="badge_util_secondary_subtle">bg-secondary-subtle</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success" id="badge_util_success_opacity_10">bg-success opacity 10</span>
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" id="badge_util_warning_opacity_25">bg-warning opacity 25</span>
                            </div>
                        </div>

                        <!-- Alertas con Utilidades -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Alertas con Utilidades (border-*, text-center)</h6>
                            <div class="d-flex flex-column gap-2">
                                <div class="alert alert-warning border-warning mb-0 py-2 px-3" id="alert_util_warning_border_warning">alert-warning + border-warning</div>
                                <div class="alert alert-warning border-0 mb-0 py-2 px-3 shadow-sm" id="alert_util_warning_border_0">alert-warning + border-0</div>
                                <div class="alert alert-info border-info mb-0 py-2 px-3" id="alert_util_info_border_info">alert-info + border-info</div>
                                <div class="alert alert-light border mb-0 py-2 px-3" id="alert_util_light_border">alert-light + border</div>
                                <div class="alert alert-success text-center mb-0 py-2 px-3 shadow-sm" id="alert_util_success_text_center">alert-success + text-center</div>
                            </div>
                        </div>

                        <!-- Dropdown Items con Utilidades -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Dropdown Items con Utilidades (text-danger, text-muted)</h6>
                            <ul class="dropdown-menu show position-static d-inline-block shadow-sm" id="dropdown_util_menu">
                                <li><a class="dropdown-item text-danger" href="#" id="dropdown_util_item_danger">dropdown-item text-danger</a></li>
                                <li><a class="dropdown-item text-muted" href="#" id="dropdown_util_item_muted">dropdown-item text-muted</a></li>
                            </ul>
                        </div>

                        <!-- Card Headers con Utilidades -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Card Headers con Utilidades (bg-*, text-*, border*)</h6>
                            <div class="row g-2">
                                <div class="col-md-3"><div class="card"><div class="card-header bg-primary text-white py-2" id="header_util_primary_text_white">bg-primary text-white</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-success text-white py-2" id="header_util_success_text_white">bg-success text-white</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-warning text-dark py-2" id="header_util_warning_text_dark">bg-warning text-dark</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-warning bg-opacity-10 py-2" id="header_util_warning_opacity_10">bg-warning opacity 10</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-warning-subtle text-warning-emphasis py-2" id="header_util_warning_subtle">warning-subtle emphasis</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-success-subtle py-2" id="header_util_success_subtle">bg-success-subtle</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-light py-2" id="header_util_bg_light">bg-light</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-white py-2" id="header_util_bg_white">bg-white</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header bg-body border-bottom py-2" id="header_util_body_border_bottom">bg-body border-bottom</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                                <div class="col-md-3"><div class="card"><div class="card-header border-0 bg-transparent py-2" id="header_util_border_0_transparent">border-0 bg-transparent</div><div class="card-body p-2"><small class="text-muted">Cuerpo</small></div></div></div>
                            </div>
                        </div>

                        <!-- Modales de muestra con Utilidades -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Partes de Modal con Utilidades (header bg-*, footer bg-*, border-0, text-center)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="modal-content shadow-sm" id="modal_util_card_1">
                                        <div class="modal-header bg-primary text-white py-2" id="modal_util_header_primary"><h6 class="modal-title mb-0">Modal Header Primary</h6></div>
                                        <div class="modal-body py-2 text-center" id="modal_util_body_center"><p class="mb-0">Body text-center</p></div>
                                        <div class="modal-footer bg-light py-1" id="modal_util_footer_light"><small class="text-muted">Footer bg-light</small></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="modal-content shadow-sm" id="modal_util_card_2">
                                        <div class="modal-header bg-success text-white py-2" id="modal_util_header_success"><h6 class="modal-title text-success mb-0" id="modal_util_title_success">Modal Title Success</h6></div>
                                        <div class="modal-body py-2"><p class="mb-0">Body normal</p></div>
                                        <div class="modal-footer border-0 py-1" id="modal_util_footer_border_0"><small class="text-muted">Footer border-0</small></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="modal-content shadow-sm border-0" id="modal_util_content_border_0">
                                        <div class="modal-header bg-danger text-white py-2" id="modal_util_header_danger"><h6 class="modal-title mb-0">Modal Header Danger</h6></div>
                                        <div class="modal-header bg-warning text-dark py-2" id="modal_util_header_warning"><h6 class="modal-title mb-0">Modal Header Warning</h6></div>
                                        <div class="modal-header bg-light border-bottom py-2" id="modal_util_header_light"><h6 class="modal-title mb-0">Header Light Border-bottom</h6></div>
                                        <div class="modal-header bg-warning-subtle text-warning-emphasis border-bottom border-warning-subtle py-2" id="modal_util_header_warning_subtle"><h6 class="modal-title mb-0">Header Warning Subtle</h6></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dashboard Cards con Utilidades -->
                        <div class="mb-2">
                            <h6 class="text-muted mb-2">Dashboard Cards con Utilidades (h-100, gradiente inline)</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="dashboard-card dashboard-card--primary h-100" id="kpi_util_h100">
                                        <h3>15</h3>
                                        <p class="mb-0">KPI primary h-100</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dashboard-card dashboard-card--primary h-100" style="background: linear-gradient(45deg, #FF512F, #DD2476); border-left-color: #DD2476; cursor: pointer;" id="kpi_util_gradient">
                                        <h3>28</h3>
                                        <p class="mb-0">KPI gradient inline</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Vendor Scripts con inicialización estricta -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/jquery/jquery-3.7.0.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/select2/js/select2.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.jQuery || !window.jQuery.fn.select2) {
            throw new Error('Fallo crítico: jQuery o Select2 no se cargaron correctamente en la galería.');
        }
        $('#gallery_select2').select2({
            theme: 'bootstrap-5'
        });

        if (window.jQuery.fn.DataTable) {
            $('#gallery_table').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros"
                }
            });
        }

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el);
        });
    });
    </script>
</body>
</html>
