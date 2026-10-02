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

                <!-- 2. BOTONES Y ESTADOS -->
                <section class="card mb-4">
                    <div class="card-header"><h5>2. Botones (Variantes, Outline, Soft y Estados)</h5></div>
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-primary" id="test_focus_trigger">Focus Trigger</button>
                        <button type="button" class="btn btn-outline-primary" id="test_btn_outline">Outline Target</button>
                        <button type="button" class="btn btn-primary" id="test_btn_primary">Primary Normal</button>
                        <button type="button" class="btn btn-primary active">Primary Active</button>
                        <button type="button" class="btn btn-primary disabled">Primary Disabled</button>
                        <button type="button" class="btn btn-secondary">Secondary</button>
                        <button type="button" class="btn btn-success">Success</button>
                        <button type="button" class="btn btn-danger">Danger</button>
                        <button type="button" class="btn btn-warning">Warning</button>
                        <button type="button" class="btn btn-info">Info</button>
                        <button type="button" class="btn btn-outline-secondary">Outline Secondary</button>
                        <button type="button" class="btn btn-soft-primary">Soft Primary</button>
                        <button type="button" class="btn btn-soft-success">Soft Success</button>
                        <button type="button" class="btn btn-soft-warning">Soft Warning</button>
                        <button type="button" class="btn btn-soft-secondary">Soft Secondary</button>
                        <button type="button" class="btn btn-pastel-brown">Pastel Brown</button>
                        <button type="button" class="btn btn-colaborativa">Colaborativa</button>
                    </div>
                </section>

                <!-- 3. BADGES Y ESTADOS -->
                <section class="card mb-4">
                    <div class="card-header"><h5>3. Badges y Estados Dinámicos</h5></div>
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-primary">Primary</span>
                        <span class="badge bg-secondary">Secondary</span>
                        <span class="badge bg-success">Success</span>
                        <span class="badge bg-danger">Danger</span>
                        <span class="badge badge-tipo">Badge Tipo</span>
                        <span class="badge badge-colaborativa">Badge Colaborativa</span>
                        <span class="badge estado-disponible">estado-disponible</span>
                        <span class="badge estado-activa">estado-activa</span>
                        <span class="badge estado-asignado">estado-asignado</span>
                        <span class="badge estado-parcial">estado-parcial</span>
                        <span class="badge estado-baja">estado-baja</span>
                        <span class="badge estado-devuelta">estado-devuelta</span>
                        <span class="badge estado-anulado">estado-anulado</span>
                    </div>
                </section>

                <!-- 4. ALERTAS -->
                <section class="card mb-4">
                    <div class="card-header"><h5>4. Alertas</h5></div>
                    <div class="card-body">
                        <div class="alert alert-success mb-2">Alerta Success de prueba</div>
                        <div class="alert alert-warning mb-2">Alerta Warning de prueba</div>
                        <div class="alert alert-danger mb-2">Alerta Danger de prueba</div>
                        <div class="alert alert-info mb-0">Alerta Info de prueba</div>
                    </div>
                </section>

                <!-- 5. FORMULARIOS, VALIDACIÓN Y SELECT2 -->
                <section class="card mb-4">
                    <div class="card-header"><h5>5. Formularios, Validación e Input-Groups</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Normal</label>
                            <input type="text" class="form-control" value="Texto normal">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Válido (.is-valid)</label>
                            <input type="text" class="form-control is-valid" value="Dato correcto">
                            <div class="valid-feedback">Campo validado</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Inválido (.is-invalid)</label>
                            <input type="text" class="form-control is-invalid" value="Dato incorrecto">
                            <div class="invalid-feedback">Error en el dato</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deshabilitado</label>
                            <input type="text" class="form-control" disabled value="Deshabilitado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Input Group</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
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
                            <div class="card-header card-header--success">Success Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--info">Info Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--warning">Warning Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header card-header--danger">Danger Header</div>
                            <div class="card-body"><p class="mb-0">Contenido</p></div>
                        </div>
                    </div>
                </section>

                <!-- 7. TABLA DE MUESTRA -->
                <section class="card mb-4">
                    <div class="card-header"><h5>7. Tablas (Base, Striped y Hover)</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Insumo</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Notebook Lenovo ThinkPad</td>
                                    <td>Equipos</td>
                                    <td><span class="badge estado-disponible">Disponible</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Impresora HP LaserJet</td>
                                    <td>Impresión</td>
                                    <td><span class="badge estado-asignado">Asignado</span></td>
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
                            <ul class="dropdown-menu show position-static" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="#">Acción 1</a></li>
                                <li><a class="dropdown-item" href="#">Acción 2</a></li>
                                <li><hr class="dropdown-divider"></li>
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
            </div>
        </div>
    </div>

    <!-- Vendor Scripts con inicialización estricta -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/jquery/jquery-3.7.0.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/select2/js/select2.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.jQuery || !window.jQuery.fn.select2) {
            throw new Error('Fallo crítico: jQuery o Select2 no se cargaron correctamente en la galería.');
        }
        $('#gallery_select2').select2({
            theme: 'bootstrap-5'
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el);
        });
    });
    </script>
</body>
</html>
