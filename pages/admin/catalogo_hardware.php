<?php
/**
 * SITIA - Gestión y Parametrización del Catálogo de Hardware
 * Solo accesible para Administradores y Super Administradores
 */

require_once '../../includes/config.php';

requerirAutenticacion();

// Verificar rol
if (!tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador'])) {
    $_SESSION['mensaje'] = 'No tienes permiso para acceder al catálogo de hardware.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

$conexion = conectarDB();

// Consultar motherboards (activas e inactivas)
$mothers = obtenerCatalogoMotherboards($conexion, false);

// Consultar procesadores (activos e inactivos)
$procesadores = obtenerCatalogoProcesadores(null, $conexion, false);

$pageTitle = 'Catálogo de Hardware';
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1"><i class="fas fa-microchip me-2 text-primary"></i>Catálogo de Hardware</h1>
                <p class="text-muted small mb-0">Parametrización y homologación de componentes informáticos oficiales en SITIA.</p>
            </div>
            <div>
                <a href="<?php echo app_base_url(); ?>/pages/admin/sedes.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Administración
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de Categorías del Catálogo (Modular y Extensible) -->
<ul class="nav nav-tabs mb-4" id="tabsCatalogo" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-mothers-btn" data-bs-toggle="tab" data-bs-target="#tab-mothers" type="button" role="tab" aria-controls="tab-mothers" aria-selected="true">
            <i class="fas fa-chess-board me-2"></i>Motherboards 
            <span class="badge bg-primary ms-1" id="badgeTotalMothers"><?php echo count($mothers); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-cpus-btn" data-bs-toggle="tab" data-bs-target="#tab-cpus" type="button" role="tab" aria-controls="tab-cpus" aria-selected="false">
            <i class="fas fa-microchip me-2"></i>Procesadores 
            <span class="badge bg-primary ms-1" id="badgeTotalCpus"><?php echo count($procesadores); ?></span>
        </button>
    </li>
    <!-- Pestaña preparada para futuras parametrizaciones -->
    <li class="nav-item" role="presentation">
        <button class="nav-link disabled text-muted" type="button" tabindex="-1" aria-disabled="true" data-bs-toggle="tooltip" title="Próximamente: Catálogo de Impresoras y Escáneres">
            <i class="fas fa-print me-2 text-muted"></i>Impresoras y Escáneres <span class="badge bg-secondary ms-1">Próximamente</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="tabsCatalogoContent">
    
    <!-- ========================================== -->
    <!-- TAB 1: MOTHERBOARDS                        -->
    <!-- ========================================== -->
    <div class="tab-pane fade show active" id="tab-mothers" role="tabpanel" aria-labelledby="tab-mothers-btn">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 me-auto">
                    <i class="fas fa-chess-board me-2 text-primary"></i>Modelos de Motherboard Homologados
                </h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalHardwareMother">
                    <i class="fas fa-plus me-1"></i>Nueva Motherboard
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($mothers)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chess-board fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay placas madre registradas</h5>
                        <p class="text-muted">Presione "Nueva Motherboard" para agregar el primer modelo.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle datatable" id="tablaMotherboards">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Memoria RAM Compatible</th>
                                    <th>Estado</th>
                                    <th style="width: 140px;" class="text-nowrap">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mothers as $m): 
                                    $isActivo = !empty($m['activo']);
                                ?>
                                <tr id="row_mother_<?php echo $m['id_mother']; ?>" class="<?php echo !$isActivo ? 'opacity-50' : ''; ?>">
                                    <td><span class="badge bg-secondary">#<?php echo $m['id_mother']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($m['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($m['modelo']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-memory me-1"></i><?php echo htmlspecialchars($m['tipo_ram']); ?>
                                        </span>
                                    </td>
                                    <td id="status_mother_<?php echo $m['id_mother']; ?>">
                                        <?php if ($isActivo): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning text-white btn-editar-mother" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHardwareMother" 
                                                    data-id="<?php echo (int)$m['id_mother']; ?>" 
                                                    data-marca="<?php echo htmlspecialchars($m['marca'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-modelo="<?php echo htmlspecialchars($m['modelo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-ram="<?php echo htmlspecialchars($m['tipo_ram'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    title="Editar modelo">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-toggle-hardware" 
                                                    id="btn_toggle_mother_<?php echo $m['id_mother']; ?>"
                                                    data-tipo="motherboard" 
                                                    data-id="<?php echo (int)$m['id_mother']; ?>" 
                                                    title="<?php echo $isActivo ? 'Desactivar (ocultar de listas)' : 'Activar modelo'; ?>">
                                                <i class="fas <?php echo $isActivo ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted'; ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-eliminar-hardware" 
                                                    data-tipo="motherboard" 
                                                    data-id="<?php echo (int)$m['id_mother']; ?>" 
                                                    data-nombre="<?php echo htmlspecialchars($m['marca'] . ' ' . $m['modelo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    title="Eliminar del catálogo">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: PROCESADORES                        -->
    <!-- ========================================== -->
    <div class="tab-pane fade" id="tab-cpus" role="tabpanel" aria-labelledby="tab-cpus-btn">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 me-auto">
                    <i class="fas fa-microchip me-2 text-primary"></i>Modelos de Procesador Homologados
                </h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalHardwareCpu">
                    <i class="fas fa-plus me-1"></i>Nuevo Procesador
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($procesadores)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-microchip fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay procesadores registrados</h5>
                        <p class="text-muted">Presione "Nuevo Procesador" para agregar el primer modelo.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle datatable" id="tablaProcesadores">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Memoria RAM</th>
                                    <th>Tipo de Equipo</th>
                                    <th>Estado</th>
                                    <th style="width: 140px;" class="text-nowrap">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($procesadores as $p): 
                                    $isActivo = !empty($p['activo']);
                                    $equipoBadge = match($p['tipo_equipo']) {
                                        'pc' => '<span class="badge bg-primary"><i class="fas fa-desktop me-1"></i>PC Escritorio</span>',
                                        'notebook' => '<span class="badge bg-info"><i class="fas fa-laptop me-1"></i>Notebook</span>',
                                        default => '<span class="badge bg-secondary"><i class="fas fa-layer-group me-1"></i>Ambos</span>'
                                    };
                                ?>
                                <tr id="row_cpu_<?php echo $p['id_procesador']; ?>" class="<?php echo !$isActivo ? 'opacity-50' : ''; ?>">
                                    <td><span class="badge bg-secondary">#<?php echo $p['id_procesador']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($p['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['modelo']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-memory me-1"></i><?php echo htmlspecialchars($p['tipo_ram']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $equipoBadge; ?></td>
                                    <td id="status_cpu_<?php echo $p['id_procesador']; ?>">
                                        <?php if ($isActivo): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning text-white btn-editar-cpu" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHardwareCpu" 
                                                    data-id="<?php echo (int)$p['id_procesador']; ?>" 
                                                    data-marca="<?php echo htmlspecialchars($p['marca'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-modelo="<?php echo htmlspecialchars($p['modelo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-ram="<?php echo htmlspecialchars($p['tipo_ram'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-equipo="<?php echo htmlspecialchars($p['tipo_equipo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    title="Editar modelo">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-toggle-hardware" 
                                                    id="btn_toggle_cpu_<?php echo $p['id_procesador']; ?>"
                                                    data-tipo="procesador" 
                                                    data-id="<?php echo (int)$p['id_procesador']; ?>" 
                                                    title="<?php echo $isActivo ? 'Desactivar (ocultar de listas)' : 'Activar modelo'; ?>">
                                                <i class="fas <?php echo $isActivo ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted'; ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-eliminar-hardware" 
                                                    data-tipo="procesador" 
                                                    data-id="<?php echo (int)$p['id_procesador']; ?>" 
                                                    data-nombre="<?php echo htmlspecialchars($p['marca'] . ' ' . $p['modelo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    title="Eliminar del catálogo">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- MODAL: Crear / Editar Motherboard          -->
<!-- ========================================== -->
<div class="modal fade" id="modalHardwareMother" tabindex="-1" aria-labelledby="modalHardwareMotherLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalHardwareMotherLabel">
                    <i class="fas fa-chess-board me-2"></i><span id="titleModalMother">Nueva Motherboard</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formHardwareMother" novalidate>
                <input type="hidden" name="id" id="mother_id" value="0">
                <input type="hidden" name="tipo_hardware" value="motherboard">
                
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="mother_marca" class="form-label form-label-sm fw-bold">Marca *</label>
                        <input type="text" class="form-control" id="mother_marca" name="marca" list="datalistMarcasMother" placeholder="Ej: Asus, MSI, Gigabyte, ASRock, Biostar, HP..." required>
                        <datalist id="datalistMarcasMother">
                            <option value="Asus">
                            <option value="MSI">
                            <option value="Gigabyte">
                            <option value="ASRock">
                            <option value="Biostar">
                            <option value="HP">
                            <option value="Intel">
                            <option value="Foxconn">
                            <option value="Dell">
                            <option value="Lenovo">
                        </datalist>
                        <div class="invalid-feedback">Indique la marca de la placa madre</div>
                    </div>

                    <div class="mb-3">
                        <label for="mother_modelo" class="form-label form-label-sm fw-bold">Modelo *</label>
                        <input type="text" class="form-control" id="mother_modelo" name="modelo" placeholder="Ej: Prime B450M-A II, GA-A320M-H, H110M PRO-VH..." required>
                        <div class="invalid-feedback">Indique el modelo exacto</div>
                    </div>

                    <div class="mb-3">
                        <label for="mother_ram" class="form-label form-label-sm fw-bold">Tipo de Memoria RAM Compatible *</label>
                        <select class="form-select" id="mother_ram" name="tipo_ram" required>
                            <option value="DDR4" selected>DDR4</option>
                            <option value="DDR5">DDR5</option>
                            <option value="DDR3">DDR3</option>
                            <option value="DDR2">DDR2</option>
                            <option value="DDR">DDR</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarMotherAdmin">
                        <i class="fas fa-save me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Crear / Editar Procesador           -->
<!-- ========================================== -->
<div class="modal fade" id="modalHardwareCpu" tabindex="-1" aria-labelledby="modalHardwareCpuLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalHardwareCpuLabel">
                    <i class="fas fa-microchip me-2"></i><span id="titleModalCpu">Nuevo Procesador</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formHardwareCpu" novalidate>
                <input type="hidden" name="id" id="cpu_id" value="0">
                <input type="hidden" name="tipo_hardware" value="procesador">
                
                <div class="modal-body p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="cpu_marca" class="form-label form-label-sm fw-bold">Marca *</label>
                            <select class="form-select" id="cpu_marca" name="marca" required>
                                <option value="AMD" selected>AMD</option>
                                <option value="Intel">Intel</option>
                                <option value="Apple">Apple</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="cpu_equipo" class="form-label form-label-sm fw-bold">Tipo de Equipo *</label>
                            <select class="form-select" id="cpu_equipo" name="tipo_equipo" required>
                                <option value="pc" selected>PC Escritorio</option>
                                <option value="notebook">Notebook</option>
                                <option value="ambos">Ambos (PC y Notebook)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cpu_modelo" class="form-label form-label-sm fw-bold">Modelo de Procesador *</label>
                        <input type="text" class="form-control" id="cpu_modelo" name="modelo" placeholder="Ej: Ryzen 5 5600G, Core i5-12400, Core i3-1115G4..." required>
                        <div class="invalid-feedback">Indique el modelo del procesador</div>
                    </div>

                    <div class="mb-3">
                        <label for="cpu_ram" class="form-label form-label-sm fw-bold">Tipo de Memoria RAM Compatible *</label>
                        <select class="form-select" id="cpu_ram" name="tipo_ram" required>
                            <option value="DDR4" selected>DDR4</option>
                            <option value="DDR5">DDR5</option>
                            <option value="DDR3">DDR3</option>
                            <option value="DDR2">DDR2</option>
                            <option value="DDR">DDR</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCpuAdmin">
                        <i class="fas fa-save me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    'use strict';

    // Reajustar columnas de DataTables al cambiar de pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if ($.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });

    // Al abrir Modal Motherboard (Editar o Nuevo)
    $('#modalHardwareMother').on('show.bs.modal', function(e) {
        var btn = $(e.relatedTarget);
        if (btn && btn.hasClass('btn-editar-mother')) {
            $('#formHardwareMother')[0].reset();
            $('#formHardwareMother').removeClass('was-validated');
            $('#mother_id').val(btn.data('id'));
            $('#mother_marca').val(btn.data('marca'));
            $('#mother_modelo').val(btn.data('modelo'));
            $('#mother_ram').val(btn.data('ram') || 'DDR4');
            $('#titleModalMother').text('Editar Motherboard #' + btn.data('id'));
            setTimeout(function() { $('#mother_modelo').focus(); }, 350);
        } else {
            $('#formHardwareMother')[0].reset();
            $('#formHardwareMother').removeClass('was-validated');
            $('#mother_id').val('0');
            $('#mother_ram').val('DDR4');
            $('#titleModalMother').text('Nueva Motherboard');
            setTimeout(function() { $('#mother_marca').focus(); }, 350);
        }
    });

    // Al abrir Modal Procesador (Editar o Nuevo)
    $('#modalHardwareCpu').on('show.bs.modal', function(e) {
        var btn = $(e.relatedTarget);
        if (btn && btn.hasClass('btn-editar-cpu')) {
            $('#formHardwareCpu')[0].reset();
            $('#formHardwareCpu').removeClass('was-validated');
            $('#cpu_id').val(btn.data('id'));
            $('#cpu_marca').val(btn.data('marca'));
            $('#cpu_modelo').val(btn.data('modelo'));
            $('#cpu_ram').val(btn.data('ram') || 'DDR4');
            $('#cpu_equipo').val(btn.data('equipo') || 'pc');
            $('#titleModalCpu').text('Editar Procesador #' + btn.data('id'));
            setTimeout(function() { $('#cpu_modelo').focus(); }, 350);
        } else {
            $('#formHardwareCpu')[0].reset();
            $('#formHardwareCpu').removeClass('was-validated');
            $('#cpu_id').val('0');
            $('#cpu_marca').val('AMD');
            $('#cpu_ram').val('DDR4');
            $('#cpu_equipo').val('pc');
            $('#titleModalCpu').text('Nuevo Procesador');
            setTimeout(function() { $('#cpu_modelo').focus(); }, 350);
        }
    });

    // Guardar Motherboard (POST estándar SITIA)
    $('#formHardwareMother').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        var $btn = $('#btnGuardarMotherAdmin');
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        $.ajax({
            url: getAppBase() + '/ajax/catalogo_acciones.php',
            method: 'POST',
            data: {
                _csrf: csrfToken,
                accion: 'guardar',
                tipo_hardware: 'motherboard',
                id: $('#mother_id').val(),
                marca: $('#mother_marca').val().trim(),
                modelo: $('#mother_modelo').val().trim(),
                tipo_ram: $('#mother_ram').val()
            },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    showToast(resp.data && resp.data.mensaje ? resp.data.mensaje : 'Guardado correctamente', 'success');
                    $('#modalHardwareMother').modal('hide');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    showToast((resp && resp.error) ? resp.error : 'Error al guardar', 'error');
                }
            },
            error: function(xhr) {
                var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error de comunicación con el servidor';
                showToast(err, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // Guardar Procesador (POST estándar SITIA)
    $('#formHardwareCpu').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        var $btn = $('#btnGuardarCpuAdmin');
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        $.ajax({
            url: getAppBase() + '/ajax/catalogo_acciones.php',
            method: 'POST',
            data: {
                _csrf: csrfToken,
                accion: 'guardar',
                tipo_hardware: 'procesador',
                id: $('#cpu_id').val(),
                marca: $('#cpu_marca').val().trim(),
                modelo: $('#cpu_modelo').val().trim(),
                tipo_ram: $('#cpu_ram').val(),
                tipo_equipo: $('#cpu_equipo').val()
            },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    showToast(resp.data && resp.data.mensaje ? resp.data.mensaje : 'Guardado correctamente', 'success');
                    $('#modalHardwareCpu').modal('hide');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    showToast((resp && resp.error) ? resp.error : 'Error al guardar', 'error');
                }
            },
            error: function(xhr) {
                var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error de comunicación con el servidor';
                showToast(err, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // Toggle Activar / Desactivar
    $(document).on('click', '.btn-toggle-hardware', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var tipo = $btn.data('tipo');
        var id = $btn.data('id');
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        $.ajax({
            url: getAppBase() + '/ajax/catalogo_acciones.php',
            method: 'POST',
            data: {
                _csrf: csrfToken,
                accion: 'toggle_estado',
                tipo_hardware: tipo,
                id: id
            },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    showToast(resp.data && resp.data.mensaje ? resp.data.mensaje : 'Estado actualizado', 'info');
                    var prefijo = (tipo === 'motherboard') ? 'mother' : 'cpu';
                    var $row = $('#row_' + prefijo + '_' + id);
                    var $status = $('#status_' + prefijo + '_' + id);
                    var isActivo = !!(resp.data && resp.data.activo);

                    if (isActivo) {
                        $row.removeClass('opacity-50');
                        $status.html('<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>');
                        $btn.find('i').attr('class', 'fas fa-toggle-on text-success');
                        $btn.attr('title', 'Desactivar (ocultar de listas)');
                    } else {
                        $row.addClass('opacity-50');
                        $status.html('<span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Inactivo</span>');
                        $btn.find('i').attr('class', 'fas fa-toggle-off text-muted');
                        $btn.attr('title', 'Activar modelo');
                    }
                } else {
                    showToast((resp && resp.error) ? resp.error : 'Error al cambiar estado', 'error');
                }
            },
            error: function(xhr) {
                var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error de conexión';
                showToast(err, 'error');
            }
        });
    });

    // Eliminar Hardware
    $(document).on('click', '.btn-eliminar-hardware', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var tipo = $btn.data('tipo');
        var id = $btn.data('id');
        var nombreModelo = $btn.data('nombre') || '';
        var tituloComponente = (tipo === 'motherboard') ? 'Motherboard' : 'Procesador';
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        showConfirm({
            titulo: 'Eliminar ' + tituloComponente,
            mensaje: '¿Está seguro de que desea eliminar el modelo <strong>' + nombreModelo + '</strong> del catálogo?',
            icono: 'fa-trash-alt text-danger',
            claseBoton: 'btn-danger',
            textoAceptar: 'Eliminar',
            onConfirm: function() {
                ejecutarEliminacion(tipo, id, false);
            }
        });

        function ejecutarEliminacion(tipoHardware, idHardware, forzar) {
            $.ajax({
                url: getAppBase() + '/ajax/catalogo_acciones.php',
                method: 'POST',
                data: {
                    _csrf: csrfToken,
                    accion: 'eliminar',
                    tipo_hardware: tipoHardware,
                    id: idHardware,
                    forzar: forzar ? 1 : 0
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        showToast(resp.data && resp.data.mensaje ? resp.data.mensaje : 'Modelo eliminado', 'success');
                        var prefijo = (tipoHardware === 'motherboard') ? 'mother' : 'cpu';
                        $('#row_' + prefijo + '_' + idHardware).fadeOut(400, function() { $(this).remove(); });
                    } else {
                        showToast((resp && resp.error) ? resp.error : 'No se pudo eliminar el modelo', 'error');
                    }
                },
                error: function(xhr) {
                    var res = xhr.responseJSON;
                    if (xhr.status === 409 && res && res.data && res.data.en_uso) {
                        showConfirm({
                            titulo: 'Modelo en uso en el Inventario',
                            mensaje: '<div class="alert alert-warning mb-2 small"><i class="fas fa-exclamation-triangle me-1"></i>' + (res.error || '') + '</div>' +
                                     '<p class="small text-muted mb-0">¿Desea <strong>desactivarlo</strong> para que no aparezca en nuevas cargas (recomendado) o prefiere <strong>forzar la eliminación</strong> del catálogo?</p>',
                            icono: 'fa-exclamation-circle text-warning',
                            claseBoton: 'btn-warning text-dark',
                            textoAceptar: 'Desactivar Modelo',
                            onConfirm: function() {
                                $('#btn_toggle_' + ((tipoHardware === 'motherboard') ? 'mother' : 'cpu') + '_' + idHardware).trigger('click');
                            }
                        });
                    } else {
                        showToast((res && res.error) ? res.error : 'Error al eliminar', 'error');
                    }
                }
            });
        }
    });
});
</script>
