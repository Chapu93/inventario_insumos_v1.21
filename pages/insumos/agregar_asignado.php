<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos iniciales
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Lógica idéntica a agregar.php para alta del insumo
        $tipo = $_POST['tipo_insumo'] ?? '';
        $nombre = trim($_POST['nombre_insumo'] ?? '');
        if ($nombre === '' || $tipo === '') {
            throw new Exception('Complete nombre y tipo de insumo');
        }

        $cantidad = ($tipo === 'Varios') ? (int)($_POST['cantidad'] ?: 1) : (int)($_POST['cantidad_especifica'] ?: 1);
        if ($tipo !== 'Varios') {
            $idPatrimonio = trim((string)($_POST['id_patrimonio'] ?? ''));
            if ($idPatrimonio === '') {
                throw new Exception('El ID Patrimonio es obligatorio para este tipo de insumo');
            }
        }

        $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual) VALUES (?,?,?,?,?,?,?,?,?, 'Disponible', ?)");
        $stmt->execute([
            $nombre,
            $tipo,
            ($tipo==='Varios'?($_POST['subcategoria_varios']?:null):null),
            ($tipo==='Varios'?($_POST['descripcion_general']?:null):null),
            ($tipo!=='Varios'?($_POST['numero_serie']?:null):null),
            ($tipo!=='Varios'?($_POST['id_fisico']?:null):null),
            ($tipo!=='Varios'?($_POST['id_patrimonio']?:null):null),
            $cantidad,
            $_POST['fecha_adquisicion'] ?: null,
            $_POST['id_punto_stock_actual'] ?: 2
        ]);
        $idInsumo = (int)$db->lastInsertId();

        if ($tipo !== 'Varios') {
            switch ($tipo) {
                case 'PC Completa':
                    if (!empty($_POST['procesador']) || !empty($_POST['ram_gb']) || !empty($_POST['almacenamiento_gb']) || !empty($_POST['mother'])) {
                        $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['procesador']?:null, $_POST['ram_gb']?:null, $_POST['almacenamiento_gb']?:null, $_POST['mother']?:null]);
                    }
                    break;
                case 'Notebook':
                    if (!empty($_POST['marca_notebook']) || !empty($_POST['modelo_notebook'])) {
                        $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb) VALUES (?,?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_notebook']?:null, $_POST['modelo_notebook']?:null, $_POST['procesador_notebook']?:null, $_POST['ram_gb_notebook']?:null, $_POST['almacenamiento_gb_notebook']?:null]);
                    }
                    break;
                case 'Impresora':
                    if (!empty($_POST['marca_impresora']) || !empty($_POST['modelo_impresora'])) {
                        $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_impresora']?:null, $_POST['modelo_impresora']?:null]);
                    }
                    break;
                case 'Monitor':
                    if (!empty($_POST['marca_monitor']) || !empty($_POST['modelo_monitor'])) {
                        $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_monitor']?:null, $_POST['modelo_monitor']?:null, $_POST['pulgadas']?:null, $_POST['conexion_monitor']?:null]);
                    }
                    break;
                case 'Escaner':
                    if (!empty($_POST['marca_escaner']) || !empty($_POST['modelo_escaner'])) {
                        $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                           ->execute([$idInsumo, $_POST['marca_escaner']?:null, $_POST['modelo_escaner']?:null]);
                    }
                    break;
            }
        }

        // Asignación (debajo del alta), dentro de la misma transacción
        $idSede = (int)($_POST['id_sede'] ?? 0);
        $idArea = (int)($_POST['id_area'] ?? 0);
        $nom = trim($_POST['persona_nombre'] ?? '');
        $ape = trim($_POST['persona_apellido'] ?? '');
        $fechaAsig = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        $obs = $_POST['observaciones'] ?: null;
        if (!$idSede || !$idArea || $nom==='' || $ape==='') {
            throw new Exception('Complete Sede/Área y datos de la persona para asignar');
        }

        $numRemito = generarNumeroRemito();
        $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, estado, observaciones) VALUES (?,?,?,?,?,?,'Activa',?)")
           ->execute([$numRemito, $idSede, $idArea, $nom, $ape, $fechaAsig, $obs]);
        $idRemito = (int)$db->lastInsertId();

        $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)")
           ->execute([$idRemito, $idInsumo, $cantidad]);

        $db->prepare("UPDATE insumos SET estado='Asignado', id_sede_actual=?, id_area_asignacion_actual=? WHERE id_insumo=?")
           ->execute([$idSede, $idArea, $idInsumo]);

        $db->commit();

        $_SESSION['mensaje'] = 'Insumo creado y asignado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: agregar_asignado.php');
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-plus-square me-2"></i>Agregar Insumo Asignado</h4>
            <a href="listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" class="needs-validation" id="formAgregarAsignado" novalidate>
            <!-- Paso 1: Alta de Insumo (mismo comportamiento que agregar.php) -->
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

            <div class="row g-2" id="formulario-campos" style="display: none;">
                <!-- Información Básica y Varios/Unitarios -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información Básica</h6></div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label">Nombre del Insumo *</label>
                                <input type="text" class="form-control form-control-sm w-100" id="nombre_insumo" name="nombre_insumo" required>
                            </div>
                            <div id="campos-varios" style="display:none;">
                                <div class="mb-2">
                                    <label class="form-label">Subcategoría</label>
                                    <select class="form-select form-select-sm w-100" id="subcategoria_varios" name="subcategoria_varios">
                                        <option value="">Seleccione subcategoría</option>
                                        <option value="Hardware">Hardware</option>
                                        <option value="Periféricos">Periféricos</option>
                                        <option value="Red">Red</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Cantidad *</label>
                                    <input type="number" class="form-control form-control-sm w-100" id="cantidad" name="cantidad" value="1" min="1" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Descripción General</label>
                                    <textarea class="form-control form-control-sm w-100" id="descripcion_general" name="descripcion_general" rows="2"></textarea>
                                </div>
                            </div>
                            <div id="campos-especificos" style="display:none;">
                                <div class="mb-2"><label class="form-label">Número de Serie *</label><input type="text" class="form-control form-control-sm w-100" id="numero_serie" name="numero_serie" required></div>
                                <div class="mb-2"><label class="form-label">ID Físico *</label><input type="text" class="form-control form-control-sm w-100" id="id_fisico" name="id_fisico" required></div>
                                <div class="mb-2"><label class="form-label">ID Patrimonio *</label><input type="text" class="form-control form-control-sm w-100" id="id_patrimonio" name="id_patrimonio" required></div>
                                <div class="mb-2"><label class="form-label">Cantidad</label><input type="number" class="form-control form-control-sm w-100" id="cantidad_especifica" name="cantidad_especifica" value="1" min="1" readonly><small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Común -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Información Común</h6></div>
                        <div class="card-body p-3">
                            <div class="mb-2"><label class="form-label">Fecha de Adquisición</label><input type="date" class="form-control form-control-sm w-100" id="fecha_adquisicion" name="fecha_adquisicion" value="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="mb-2">
                                <label class="form-label">Punto de Almacenamiento *</label>
                                <select class="form-select form-select-sm w-100" id="id_punto_stock_actual" name="id_punto_stock_actual" required>
                                    <option value="">Seleccione punto de almacenamiento</option>
                                    <?php foreach ($puntos_stock as $p): ?>
                                        <option value="<?php echo $p['id_punto_stock']; ?>" <?php echo $p['id_punto_stock']==2?'selected':''; ?>><?php echo $p['nombre_punto']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Especificaciones por tipo -->
                <div class="col-md-4" id="columna-especificaciones">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-microchip me-2 text-primary"></i>Especificaciones</h6></div>
                        <div class="card-body p-3">
                            <div class="campos-especificos" id="campos-pc" style="display:none;">
                                <div class="mb-2"><label class="form-label">Procesador *</label><input type="text" class="form-control form-control-sm w-100" id="procesador" name="procesador" required></div>
                                <div class="mb-2"><label class="form-label">RAM (GB) *</label><input type="number" class="form-control form-control-sm w-100" id="ram_gb" name="ram_gb" min="1" required></div>
                                <div class="mb-2"><label class="form-label">Almacenamiento (GB) *</label><input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb" name="almacenamiento_gb" min="1" required></div>
                                <div class="mb-2"><label class="form-label">Motherboard *</label><input type="text" class="form-control form-control-sm w-100" id="mother" name="mother" required></div>
                            </div>
                            <div class="campos-especificos" id="campos-notebook" style="display:none;">
                                <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" id="marca_notebook" name="marca_notebook" required></div>
                                <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" id="modelo_notebook" name="modelo_notebook" required></div>
                                <div class="mb-2"><label class="form-label">Procesador *</label><input type="text" class="form-control form-control-sm w-100" id="procesador_notebook" name="procesador_notebook" required></div>
                                <div class="mb-2"><label class="form-label">RAM (GB) *</label><input type="number" class="form-control form-control-sm w-100" id="ram_gb_notebook" name="ram_gb_notebook" min="1" required></div>
                                <div class="mb-2"><label class="form-label">Almacenamiento (GB) *</label><input type="number" class="form-control form-control-sm w-100" id="almacenamiento_gb_notebook" name="almacenamiento_gb_notebook" min="1" required></div>
                            </div>
                            <div class="campos-especificos" id="campos-impresora" style="display:none;">
                                <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" id="marca_impresora" name="marca_impresora" required></div>
                                <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" id="modelo_impresora" name="modelo_impresora" required></div>
                            </div>
                            <div class="campos-especificos" id="campos-monitor" style="display:none;">
                                <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" id="marca_monitor" name="marca_monitor" required></div>
                                <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" id="modelo_monitor" name="modelo_monitor" required></div>
                                <div class="mb-2"><label class="form-label">Pulgadas *</label><input type="number" class="form-control form-control-sm w-100" id="pulgadas" name="pulgadas" step="0.1" min="1" required></div>
                                <div class="mb-2"><label class="form-label">Conexión *</label><select class="form-select form-select-sm w-100" id="conexion_monitor" name="conexion_monitor" required><option value="">Seleccione conexión</option><option value="VGA">VGA</option><option value="HDMI">HDMI</option></select></div>
                            </div>
                            <div class="campos-especificos" id="campos-escaner" style="display:none;">
                                <div class="mb-2"><label class="form-label">Marca *</label><input type="text" class="form-control form-control-sm w-100" id="marca_escaner" name="marca_escaner" required></div>
                                <div class="mb-2"><label class="form-label">Modelo *</label><input type="text" class="form-control form-control-sm w-100" id="modelo_escaner" name="modelo_escaner" required></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Asignación (debajo) -->
            <div class="row g-2 mt-3">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-share-square me-2"></i>Asignación</h6></div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label">Localidad *</label>
                                <select class="form-select" id="localidad" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($localidades as $l): ?>
                                    <option value="<?php echo $l['id_localidad']; ?>"><?php echo $l['nombre_localidad']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2"><label class="form-label">Sede *</label><select class="form-select" id="sede" name="id_sede" required></select></div>
                            <div class="mb-2"><label class="form-label">Área *</label><select class="form-select" id="area" name="id_area" required></select></div>
                            <div class="row g-2">
                                <div class="col-sm-6"><label class="form-label">Nombre *</label><input type="text" class="form-control" name="persona_nombre" required></div>
                                <div class="col-sm-6"><label class="form-label">Apellido *</label><input type="text" class="form-control" name="persona_apellido" required></div>
                            </div>
                            <div class="mb-2 mt-2"><label class="form-label">Fecha de Asignación *</label><input type="date" class="form-control" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="mb-2"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="2"></textarea></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="row mt-3" id="botones-formulario" style="display: none;">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Guardar y Asignar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    function actualizarValidacionCampos(tipo) {
        $('input, select, textarea').prop('required', false);
        $('#nombre_insumo, #tipo_insumo, #id_punto_stock_actual').prop('required', true);
        if (tipo === 'Varios') {
            $('#cantidad').prop('required', true);
            $('#columna-especificaciones').hide();
        } else if (tipo) {
            $('#numero_serie, #id_fisico, #id_patrimonio, #cantidad_especifica').prop('required', true);
            $('#columna-especificaciones').show();
            switch(tipo) {
                case 'PC Completa': $('#procesador, #ram_gb, #almacenamiento_gb, #mother').prop('required', true); break;
                case 'Notebook': $('#marca_notebook, #modelo_notebook, #procesador_notebook, #ram_gb_notebook, #almacenamiento_gb_notebook').prop('required', true); break;
                case 'Impresora': $('#marca_impresora, #modelo_impresora').prop('required', true); break;
                case 'Monitor': $('#marca_monitor, #modelo_monitor, #pulgadas, #conexion_monitor').prop('required', true); break;
                case 'Escaner': $('#marca_escaner, #modelo_escaner').prop('required', true); break;
            }
        }
    }

    $('#tipo_insumo').on('change', function() {
        const tipo = $(this).val();
        $('.campos-especificos').hide();
        $('#campos-varios').hide();
        $('#campos-especificos').hide();
        if (tipo === 'Varios') {
            $('#campos-varios').show();
        } else if (tipo) {
            $('#campos-especificos').show();
            switch(tipo) {
                case 'PC Completa': $('#campos-pc').show(); break;
                case 'Notebook': $('#campos-notebook').show(); break;
                case 'Impresora': $('#campos-impresora').show(); break;
                case 'Monitor': $('#campos-monitor').show(); break;
                case 'Escaner': $('#campos-escaner').show(); break;
            }
        }
        if (tipo) { $('#formulario-campos, #botones-formulario').show(); } else { $('#formulario-campos, #botones-formulario').hide(); }
        actualizarValidacionCampos(tipo);
    });

    // Selects dependientes
    $('#localidad').on('change', function(){
        const id = $(this).val();
        setLoading($('#sede'), 'Cargando sedes...');
        $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id })
          .done(r => { $('#sede').html(r && r.options ? r.options : '<option value="">Seleccione</option>'); $('#area').html('<option value="">Seleccione</option>'); });
    });
    $('#sede').on('change', function(){
        const id = $(this).val();
        setLoading($('#area'), 'Cargando áreas...');
        $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id })
          .done(r => { $('#area').html(r && r.options ? r.options : '<option value="">Seleccione</option>'); });
    });

    // Validación submit
    $('#formAgregarAsignado').on('submit', function(e){
        if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); $(this).addClass('was-validated'); return false; }
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
        return true;
    });
});
</script>

