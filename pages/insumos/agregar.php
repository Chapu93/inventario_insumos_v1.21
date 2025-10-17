<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Obtener datos para los select
$stmt = $conexion->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto");
$puntos_stock = $stmt->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF
    if (!verify_csrf()) {
        $_SESSION['mensaje'] = 'CSRF inválido';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar.php');
        exit;
    }
    error_log('Formulario POST recibido en agregar.php');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Verificar que todos los campos necesarios estén presentes
    $campos_requeridos = ['nombre_insumo', 'tipo_insumo'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            error_log('Campo requerido faltante: ' . $campo);
            $_SESSION['mensaje'] = "Error: Campo requerido faltante: " . $campo;
            $_SESSION['tipo_mensaje'] = "danger";
            header("Location: agregar.php");
            exit;
        }
    }
    
    try {
        $conexion->beginTransaction();
        
        $tipo_insumo = $_POST['tipo_insumo'];
        
        // Configurar cantidad según tipo
        $cantidad = ($tipo_insumo == 'Varios') ? ($_POST['cantidad'] ?: 1) : ($_POST['cantidad_especifica'] ?: 1);
        // Validación backend: exigir ID Patrimonio para no "Varios"
        if ($tipo_insumo != 'Varios') {
            $idPat = isset($_POST['id_patrimonio']) ? trim((string)$_POST['id_patrimonio']) : '';
            if ($idPat === '') {
                $_SESSION['mensaje'] = 'Error: El ID Patrimonio es obligatorio para este tipo de insumo.';
                $_SESSION['tipo_mensaje'] = 'danger';
                header('Location: agregar.php');
                exit;
            }
        }
        
        // Insertar insumo principal
        $sql = "INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, 
                                   numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, 
                                   id_punto_stock_actual) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            $_POST['nombre_insumo'],
            $tipo_insumo,
            ($tipo_insumo == 'Varios') ? ($_POST['subcategoria_varios'] ?: null) : null,
            ($tipo_insumo == 'Varios') ? ($_POST['descripcion_general'] ?: null) : null,
            ($tipo_insumo != 'Varios') ? ($_POST['numero_serie'] ?: null) : null,
            ($tipo_insumo != 'Varios') ? ($_POST['id_fisico'] ?: null) : null,
            ($tipo_insumo != 'Varios') ? ($_POST['id_patrimonio'] ?: null) : null,
            $cantidad,
            $_POST['fecha_adquisicion'] ?: null,
            'Disponible', // Estado inicial siempre disponible
            $_POST['id_punto_stock_actual'] ?: 2 // Por defecto Depósito
        ]);
        
        $id_insumo = $conexion->lastInsertId();
        
        // Insertar datos específicos según el tipo (solo para tipos que no son "Varios")
        if ($tipo_insumo != 'Varios') {
            switch ($tipo_insumo) {
                case 'PC Completa':
                    if (!empty($_POST['procesador']) || !empty($_POST['ram_gb']) || !empty($_POST['almacenamiento_gb']) || !empty($_POST['mother'])) {
                        $sql = "INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['procesador'] ?: null, $_POST['ram_gb'] ?: null, $_POST['almacenamiento_gb'] ?: null, $_POST['mother'] ?: null]);
                    }
                    break;
                    
                case 'Notebook':
                    if (!empty($_POST['marca_notebook']) || !empty($_POST['modelo_notebook'])) {
                        $sql = "INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb, cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $cargador = isset($_POST['cargador']) ? 1 : 0;
                        $funda = isset($_POST['funda']) ? 1 : 0;
                        $microSd = isset($_POST['micro_sd']) ? 1 : 0;
                        $microSdGb = $microSd ? (($_POST['micro_sd_gb'] !== '' ? (int)$_POST['micro_sd_gb'] : null)) : null;
                        $caja = isset($_POST['caja']) ? 1 : 0;
                        $adaptadorRed = isset($_POST['adaptador_red']) ? 1 : 0;
                        $stmt->execute([
                            $id_insumo,
                            $_POST['marca_notebook'] ?: null,
                            $_POST['modelo_notebook'] ?: null,
                            $_POST['procesador_notebook'] ?: null,
                            $_POST['ram_gb_notebook'] ?: null,
                            $_POST['almacenamiento_gb_notebook'] ?: null,
                            $cargador,
                            $funda,
                            $microSd,
                            $microSdGb,
                            $caja,
                            $adaptadorRed
                        ]);
                    }
                    break;
                    
                case 'Impresora':
                    if (!empty($_POST['marca_impresora']) || !empty($_POST['modelo_impresora'])) {
                        $sql = "INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_impresora'], $_POST['modelo_impresora']]);
                    }
                    break;
                    
                case 'Monitor':
                    if (!empty($_POST['marca_monitor']) || !empty($_POST['modelo_monitor'])) {
                        $sql = "INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_monitor'], $_POST['modelo_monitor'], $_POST['pulgadas'] ?: null, $_POST['conexion_monitor']]);
                    }
                    break;
                    
                case 'Escaner':
                    if (!empty($_POST['marca_escaner']) || !empty($_POST['modelo_escaner'])) {
                        $sql = "INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?, ?, ?)";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute([$id_insumo, $_POST['marca_escaner'], $_POST['modelo_escaner']]);
                    }
                    break;
            }
        }
        
        $conexion->commit();
        
        error_log('Insumo agregado correctamente, redirigiendo a listar.php');
        error_log('ID del insumo insertado: ' . $id_insumo);
        $_SESSION['mensaje'] = "Insumo agregado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        
        // Asegurar que no haya salida antes del header
        if (headers_sent()) {
            error_log('Headers ya enviados, usando JavaScript para redirección');
            echo "<script>window.location.href = 'listar.php';</script>";
        } else {
            header("Location: listar.php");
        }
        exit;
        
    } catch (Exception $e) {
        $conexion->rollBack();
        error_log('Error al agregar insumo: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $_SESSION['mensaje'] = "Error al agregar insumo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
        
        // Asegurar que no haya salida antes del header
        if (headers_sent()) {
            error_log('Headers ya enviados, usando JavaScript para redirección');
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href = 'agregar.php';</script>";
        } else {
            header("Location: agregar.php");
        }
        exit;
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fas fa-plus me-2"></i>Agregar Insumo
            </h4>
            <a href="listar.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" id="formInsumo" class="needs-validation" novalidate action="agregar.php">
            <?php echo csrf_input(); ?>
            <!-- Selección de tipo de insumo -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            <h6 class="mb-0">Tipo de Insumo *</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-select" id="tipo_insumo" name="tipo_insumo" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="Varios">Varios</option>
                                    <option value="PC Completa">PC Completa</option>
                                    <option value="Notebook">Notebook</option>
                                    <option value="Impresora">Impresora</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Escaner">Escaner</option>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar un tipo de insumo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario en 3 columnas -->
            <div class="row g-2" id="formulario-campos" style="display: none;">
                <!-- COLUMNA 1: Información Básica -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Información Básica
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label id="label-nombre-insumo" for="nombre_insumo" class="form-label">Nombre del Insumo *</label>
                                <input type="text" class="form-control form-control-sm w-100" id="nombre_insumo" name="nombre_insumo" required>
                                <div id="invalid-nombre-insumo" class="invalid-feedback">El nombre del insumo es obligatorio</div>
                            </div>
                            
                            <!-- Campos específicos para tipo "Varios" -->
                            <div id="campos-varios" style="display: none;">
                                <div class="mb-2">
                                    <label for="subcategoria_varios" class="form-label">Subcategoría</label>
                                    <select class="form-select form-select-sm w-100" id="subcategoria_varios" name="subcategoria_varios">
                                        <option value="">Seleccione subcategoría</option>
                                        <option value="Hardware">Hardware</option>
                                        <option value="Periféricos">Periféricos</option>
                                        <option value="Red">Red</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="cantidad" class="form-label">Cantidad *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="cantidad" name="cantidad" value="1" min="1" required>
                                    <div class="invalid-feedback">La cantidad es obligatoria</div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="descripcion_general" class="form-label">Descripción General</label>
                                    <textarea class="form-control form-control-sm w-100" id="descripcion_general" name="descripcion_general" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos específicos para otros tipos -->
                            <div id="campos-especificos" style="display: none;">
                                <div class="mb-2">
                                    <label for="numero_serie" class="form-label">Número de Serie *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="numero_serie" name="numero_serie" required>
                                    <div class="invalid-feedback">El número de serie es obligatorio</div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="id_fisico" class="form-label">ID Físico *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="id_fisico" name="id_fisico" required>
                                    <div class="invalid-feedback">El ID físico es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="id_patrimonio" class="form-label">ID Patrimonio *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="id_patrimonio" name="id_patrimonio" required>
                                    <div class="invalid-feedback">El ID patrimonio es obligatorio</div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="cantidad_especifica" class="form-label">Cantidad</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="cantidad_especifica" name="cantidad_especifica" value="1" min="1" readonly>
                                    <small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1 (carga unitaria)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- COLUMNA 2: Información Común -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-cog me-2 text-primary"></i>Información Común
                            </h6>
                        </div>
                        <div class="card-body px-3 pt-3 pb-0">
                            <div class="mb-2">
                                <label for="fecha_adquisicion" class="form-label">Fecha de Adquisición</label>
                                <input type="date" class="form-control form-control-sm w-100" id="fecha_adquisicion" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-0">
                                <label for="id_punto_stock_actual" class="form-label">Punto de Almacenamiento *</label>
                                <select class="form-select form-select-sm w-100" id="id_punto_stock_actual" name="id_punto_stock_actual" required>
                                    <option value="">Seleccione punto de almacenamiento</option>
                                    <?php foreach ($puntos_stock as $punto): ?>
                                        <option value="<?php echo $punto['id_punto_stock']; ?>" 
                                                <?php echo $punto['id_punto_stock'] == 2 ? 'selected' : ''; ?>>
                                            <?php echo $punto['nombre_punto']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar un punto de almacenamiento</div>
                            </div>
                        </div>
                    </div>
                    <!-- Extras Notebook: tarjeta independiente debajo de Información Común -->
                    <div class="card border-0 shadow-sm mt-2" id="extras-notebook" style="display:none;">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-laptop me-2 text-primary"></i>Accesorios Notebook</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cargador" name="cargador" value="1">
                                <label class="form-check-label" for="cargador">Cargador</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="funda" name="funda" value="1">
                                <label class="form-check-label" for="funda">Funda</label>
                            </div>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="micro_sd" name="micro_sd" value="1">
                                        <label class="form-check-label" for="micro_sd">Micro SD</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="number" min="0" step="1" class="form-control form-control-sm" id="micro_sd_gb" name="micro_sd_gb" placeholder="Tamaño (GB)" disabled>
                                </div>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="caja" name="caja" value="1">
                                <label class="form-check-label" for="caja">Caja</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="adaptador_red" name="adaptador_red" value="1">
                                <label class="form-check-label" for="adaptador_red">Adaptador de red</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- COLUMNA 3: Especificaciones -->
                <div class="col-md-4" id="columna-especificaciones">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-microchip me-2 text-primary"></i>Especificaciones
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Campos para PC Completa -->
                            <div class="campos-especificos" id="campos-pc" style="display: none;">
                                <div class="mb-2">
                                    <label for="procesador" class="form-label">Procesador *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="procesador" name="procesador" required>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="ram_gb" class="form-label">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="ram_gb" name="ram_gb" min="1" required>
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="almacenamiento_gb" class="form-label">Almacenamiento (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb" name="almacenamiento_gb" min="1" required>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="mother" class="form-label">Motherboard *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="mother" name="mother" required>
                                    <div class="invalid-feedback">La motherboard es obligatoria</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Notebook -->
                            <div class="campos-especificos" id="campos-notebook" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_notebook" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_notebook" name="marca_notebook" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_notebook" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_notebook" name="modelo_notebook" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="procesador_notebook" class="form-label">Procesador *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="procesador_notebook" name="procesador_notebook" required>
                                    <div class="invalid-feedback">El procesador es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="ram_gb_notebook" class="form-label">RAM (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="ram_gb_notebook" name="ram_gb_notebook" min="1" required>
                                    <div class="invalid-feedback">La RAM es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="almacenamiento_gb_notebook" class="form-label">Almacenamiento (GB) *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb_notebook" name="almacenamiento_gb_notebook" min="1" required>
                                    <div class="invalid-feedback">El almacenamiento es obligatorio</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Impresora -->
                            <div class="campos-especificos" id="campos-impresora" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_impresora" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_impresora" name="marca_impresora" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_impresora" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_impresora" name="modelo_impresora" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Monitor -->
                            <div class="campos-especificos" id="campos-monitor" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_monitor" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_monitor" name="marca_monitor" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_monitor" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_monitor" name="modelo_monitor" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                                <div class="mb-2">
                                    <label for="pulgadas" class="form-label">Pulgadas *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="pulgadas" name="pulgadas" step="0.1" min="1" required>
                                    <div class="invalid-feedback">Las pulgadas son obligatorias</div>
                                </div>
                                <div class="mb-2">
                                    <label for="conexion_monitor" class="form-label">Conexión *</label>
                                    <select class="form-select form-select-sm w-100" id="conexion_monitor" name="conexion_monitor" required>
                                        <option value="">Seleccione conexión</option>
                                        <option value="VGA">VGA</option>
                                        <option value="HDMI">HDMI</option>
                                    </select>
                                    <div class="invalid-feedback">La conexión es obligatoria</div>
                                </div>
                            </div>
                            
                            <!-- Campos para Escaner -->
                            <div class="campos-especificos" id="campos-escaner" style="display: none;">
                                <div class="mb-2">
                                    <label for="marca_escaner" class="form-label">Marca *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="marca_escaner" name="marca_escaner" required>
                                    <div class="invalid-feedback">La marca es obligatoria</div>
                                </div>
                                <div class="mb-2">
                                    <label for="modelo_escaner" class="form-label">Modelo *</label>
                                    <input type="text" class="form-control form-control-sm w-100" id="modelo_escaner" name="modelo_escaner" required>
                                    <div class="invalid-feedback">El modelo es obligatorio</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="row mt-3" id="botones-formulario" style="display: none;">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i>Guardar Insumo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    console.log('Formulario de agregar cargado');
    
    // Mostrar/ocultar campos según tipo de insumo
    $('#tipo_insumo').on('change', function() {
        const tipo = $(this).val();
        console.log('Tipo seleccionado:', tipo);
        
        // Ocultar todos los campos específicos primero
        $('.campos-especificos').hide();
        $('#campos-varios').hide();
        $('#campos-especificos').hide();
        
        if (tipo === 'Varios') {
            console.log('Mostrando campos para Varios');
            $('#campos-varios').show();
            $('#campos-especificos').hide();
            $('#columna-especificaciones').hide();
        } else if (tipo !== '') {
            console.log('Mostrando campos específicos para:', tipo);
            $('#campos-varios').hide();
            $('#campos-especificos').show();
            $('#columna-especificaciones').show();
            
            // Mostrar campos específicos según tipo
            switch(tipo) {
                case 'PC Completa':
                    $('#campos-pc').show();
                    break;
                case 'Notebook':
                    $('#campos-notebook').show();
                    break;
                case 'Impresora':
                    $('#campos-impresora').show();
                    break;
                case 'Monitor':
                    $('#campos-monitor').show();
                    break;
                case 'Escaner':
                    $('#campos-escaner').show();
                    break;
            }
        }
        // Mostrar card de extras notebook debajo de Información Común
        if (tipo === 'Notebook') {
            $('#extras-notebook').slideDown(150);
        } else {
            $('#extras-notebook').slideUp(150);
            $('#micro_sd').prop('checked', false);
            $('#micro_sd_gb').prop('disabled', true).val('');
        }
        
        // Mostrar/ocultar formulario y botones
        if (tipo === '' || tipo === null) {
            console.log('Ocultando formulario y botones');
            $('#formulario-campos').hide();
            $('#botones-formulario').hide();
        } else {
            console.log('Mostrando formulario y botones');
            $('#formulario-campos').show();
            $('#botones-formulario').show();
        }
        
        // Actualizar validación de campos
        actualizarValidacionCampos(tipo);
    });
    
    // Función para actualizar validación de campos según tipo
    function actualizarValidacionCampos(tipo) {
        console.log('Actualizando validación para tipo:', tipo);
        
        // Resetear todos los campos requeridos
        $('input, select, textarea').prop('required', false);
        
        // Campos básicos siempre requeridos
        $('#nombre_insumo').prop('required', true);
        $('#tipo_insumo').prop('required', true);

        // Ajustar label y mensaje para nombre_insumo según tipo
        if (tipo === 'Varios') {
            $('#label-nombre-insumo').text('Nombre del Insumo *');
            $('#invalid-nombre-insumo').text('El nombre del insumo es obligatorio');
        } else if (tipo !== '') {
            $('#label-nombre-insumo').text('Descripción *');
            $('#invalid-nombre-insumo').text('La descripción es obligatoria');
        }
        
        if (tipo === 'Varios') {
            $('#cantidad').prop('required', true);
        } else if (tipo !== '') {
            $('#numero_serie').prop('required', true);
            $('#id_fisico').prop('required', true);
            $('#id_patrimonio').prop('required', true);
            $('#cantidad_especifica').prop('required', true);
            
            // Campos específicos según tipo
            switch(tipo) {
                case 'PC Completa':
                    $('#procesador, #ram_gb, #almacenamiento_gb, #mother').prop('required', true);
                    break;
                case 'Notebook':
                    $('#marca_notebook, #modelo_notebook, #procesador_notebook, #ram_gb_notebook, #almacenamiento_gb_notebook').prop('required', true);
                    break;
                case 'Impresora':
                    $('#marca_impresora, #modelo_impresora').prop('required', true);
                    break;
                case 'Monitor':
                    $('#marca_monitor, #modelo_monitor, #pulgadas, #conexion_monitor').prop('required', true);
                    break;
                case 'Escaner':
                    $('#marca_escaner, #modelo_escaner').prop('required', true);
                    break;
            }
        }
    }
    
    // Validación del formulario
    $('#formInsumo').on('submit', function(e) {
        console.log('Formulario enviado desde agregar.php');
        console.log('Datos del formulario:', $(this).serialize());
        
        // Validar formulario antes de enviar
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Formulario no válido, mostrando errores');
            $(this).addClass('was-validated');
            return false;
        }
        
        // Mostrar loading
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
        
        // Permitir el envío del formulario
        console.log('Formulario válido, enviando...');
        return true;
    });
    
    // Habilitar/deshabilitar tamaño Micro SD
    $(document).on('change', '#micro_sd', function(){
        const on = $(this).is(':checked');
        $('#micro_sd_gb').prop('disabled', !on);
        if (!on) { $('#micro_sd_gb').val(''); }
    });

    // Manejo de errores de envío
    $(document).on('submit', '#formInsumo', function() {
        // Timeout para detectar si el formulario se queda colgado
        setTimeout(function() {
            if ($('button[type="submit"]').prop('disabled')) {
                console.log('Formulario parece estar colgado, habilitando botón');
                $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar Insumo');
                alert('El formulario parece estar tardando más de lo esperado. Por favor, inténtelo de nuevo.');
            }
        }, 10000); // 10 segundos
    });
});
</script> 