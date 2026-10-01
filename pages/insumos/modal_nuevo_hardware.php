<?php
/**
 * SITIA - Componente Modal y Controlador JS para Alta Rápida de Hardware
 * Solo se incluye para usuarios con roles 'Super Administrador' y 'Administrador'
 */

if (!defined('APP_BASE_URL')) {
    exit;
}
?>

<!-- MODAL: Nueva Motherboard -->
<div class="modal fade" id="modalNuevoHardwareMother" tabindex="-1" aria-labelledby="modalNuevoHardwareMotherLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalNuevoHardwareMotherLabel">
                    <i class="fas fa-chess-board me-2"></i>Agregar Modelo de Motherboard al Catálogo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoHardwareMother" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="tipo_hardware" value="motherboard">
                
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Este modelo quedará registrado en el catálogo central y disponible de inmediato para todos los operadores.
                    </div>

                    <div class="mb-3">
                        <label for="modal_mother_marca" class="form-label form-label-sm fw-bold">Marca *</label>
                        <input type="text" class="form-control" id="modal_mother_marca" name="marca" list="datalistMarcasMother" placeholder="Ej: Asus, MSI, Gigabyte, Biostar, ASRock..." required>
                        <datalist id="datalistMarcasMother">
                            <option value="Asus">
                            <option value="MSI">
                            <option value="Gigabyte">
                            <option value="ASRock">
                            <option value="Biostar">
                            <option value="HP">
                            <option value="Intel">
                            <option value="Dell">
                            <option value="Lenovo">
                        </datalist>
                        <div class="invalid-feedback">Indique la marca de la placa madre</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_mother_modelo" class="form-label form-label-sm fw-bold">Modelo *</label>
                        <input type="text" class="form-control" id="modal_mother_modelo" name="modelo" placeholder="Ej: Prime B450M-A II, GA-A320M-H, H110M PRO-VH..." required>
                        <div class="invalid-feedback">Indique el modelo exacto de la placa madre</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_mother_ram" class="form-label form-label-sm fw-bold">Tipo de Memoria RAM Compatible *</label>
                        <select class="form-select" id="modal_mother_ram" name="tipo_ram" required>
                            <option value="DDR4" selected>DDR4</option>
                            <option value="DDR5">DDR5</option>
                            <option value="DDR3">DDR3</option>
                            <option value="DDR2">DDR2</option>
                            <option value="DDR">DDR</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <div class="invalid-feedback">Seleccione la generación de memoria RAM</div>
                    </div>

                    <div class="p-3 rounded border text-center small text-muted mb-0" style="background-color: var(--sitia-surface-1); border-color: var(--sitia-border) !important;">
                        Vista previa en el sistema: 
                        <strong id="preview_mother_nombre" class="text-primary d-block mt-1 fs-6">Nueva Motherboard</strong>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <a href="<?php echo app_base_url(); ?>/pages/admin/catalogo_hardware.php" target="_blank" class="btn btn-link text-decoration-none text-muted p-0">
                        <i class="fas fa-external-link-alt me-1"></i>Gestionar catálogo completo
                    </a>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarMotherModal">
                            <i class="fas fa-save me-1"></i>Guardar en Catálogo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Nuevo Procesador -->
<div class="modal fade" id="modalNuevoHardwareProcesador" tabindex="-1" aria-labelledby="modalNuevoHardwareProcesadorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalNuevoHardwareProcesadorLabel">
                    <i class="fas fa-microchip me-2"></i>Agregar Modelo de Procesador al Catálogo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoHardwareProcesador" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="tipo_hardware" value="procesador">
                
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Este procesador quedará registrado en el catálogo central para su selección en PC o Notebook.
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="modal_cpu_marca" class="form-label form-label-sm fw-bold">Marca *</label>
                            <select class="form-select" id="modal_cpu_marca" name="marca" required>
                                <option value="AMD" selected>AMD</option>
                                <option value="Intel">Intel</option>
                                <option value="Apple">Apple</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="modal_cpu_equipo" class="form-label form-label-sm fw-bold">Tipo de Equipo *</label>
                            <select class="form-select" id="modal_cpu_equipo" name="tipo_equipo" required>
                                <option value="pc" selected>PC Escritorio</option>
                                <option value="notebook">Notebook</option>
                                <option value="ambos">Ambos (PC y Notebook)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_cpu_modelo" class="form-label form-label-sm fw-bold">Modelo de Procesador *</label>
                        <input type="text" class="form-control" id="modal_cpu_modelo" name="modelo" placeholder="Ej: Ryzen 5 5600G, Core i5-12400, Core i3-1115G4..." required>
                        <div class="invalid-feedback">Indique el modelo exacto del procesador</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_cpu_ram" class="form-label form-label-sm fw-bold">Tipo de Memoria RAM Compatible *</label>
                        <select class="form-select" id="modal_cpu_ram" name="tipo_ram" required>
                            <option value="DDR4" selected>DDR4</option>
                            <option value="DDR5">DDR5</option>
                            <option value="DDR3">DDR3</option>
                            <option value="DDR2">DDR2</option>
                            <option value="DDR">DDR</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <div class="invalid-feedback">Seleccione la generación de memoria RAM compatible</div>
                    </div>

                    <div class="p-3 rounded border text-center small text-muted mb-0" style="background-color: var(--sitia-surface-1); border-color: var(--sitia-border) !important;">
                        Vista previa en el sistema: 
                        <strong id="preview_cpu_nombre" class="text-primary d-block mt-1 fs-6">Nuevo Procesador</strong>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <a href="<?php echo app_base_url(); ?>/pages/admin/catalogo_hardware.php" target="_blank" class="btn btn-link text-decoration-none text-muted p-0">
                        <i class="fas fa-external-link-alt me-1"></i>Gestionar catálogo completo
                    </a>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarCpuModal">
                            <i class="fas fa-save me-1"></i>Guardar en Catálogo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CONTROLADOR JS DEL CATÁLOGO DE HARDWARE -->
<script>
(function($) {
    'use strict';

    // Variable global para recordar qué select originó la apertura del modal
    let $selectHardwareObjetivo = null;

    // Actualizar vista previa en modal Motherboard
    function actualizarPreviewMother() {
        const marca = $('#modal_mother_marca').val().trim() || 'Marca';
        const modelo = $('#modal_mother_modelo').val().trim() || 'Modelo';
        const ram = $('#modal_mother_ram').val();
        $('#preview_mother_nombre').text(`${marca} ${modelo} (${ram})`);
    }

    $('#modal_mother_marca, #modal_mother_modelo, #modal_mother_ram').on('input change', actualizarPreviewMother);

    // Actualizar vista previa en modal Procesador
    function actualizarPreviewCpu() {
        const marca = $('#modal_cpu_marca').val().trim() || 'Marca';
        const modelo = $('#modal_cpu_modelo').val().trim() || 'Modelo';
        const ram = $('#modal_cpu_ram').val();
        $('#preview_cpu_nombre').text(`${marca} ${modelo} (${ram})`);
    }

    $('#modal_cpu_marca, #modal_cpu_modelo, #modal_cpu_ram').on('input change', actualizarPreviewCpu);

    // Click en cualquier botón [+] de alta de hardware
    $(document).on('click', '.btn-nuevo-hardware', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const tipoHardware = $btn.data('tipo'); // 'motherboard' o 'procesador'
        const tipoEquipoPredef = $btn.data('tipo-equipo') || 'pc';

        // Identificar el select asociado (en el mismo input-group o por selector)
        if ($btn.data('target-select')) {
            $selectHardwareObjetivo = $($btn.data('target-select'));
        } else {
            $selectHardwareObjetivo = $btn.closest('.input-group').find('select');
        }

        if (tipoHardware === 'motherboard') {
            $('#formNuevoHardwareMother')[0].reset();
            $('#modal_mother_ram').val('DDR4');
            actualizarPreviewMother();
            const modalEl = document.getElementById('modalNuevoHardwareMother');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            setTimeout(() => $('#modal_mother_marca').focus(), 400);
        } else if (tipoHardware === 'procesador') {
            $('#formNuevoHardwareProcesador')[0].reset();
            $('#modal_cpu_ram').val('DDR4');
            $('#modal_cpu_equipo').val(tipoEquipoPredef);
            actualizarPreviewCpu();
            const modalEl = document.getElementById('modalNuevoHardwareProcesador');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            setTimeout(() => $('#modal_cpu_modelo').focus(), 400);
        }
    });

    // Envío del formulario de Motherboard
    $('#formNuevoHardwareMother').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const $btn = $('#btnGuardarMotherModal');
        const origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        const formData = {
            _csrf: $('meta[name="csrf-token"]').attr('content') || $(form).find('input[name="_csrf"]').val(),
            tipo_hardware: 'motherboard',
            marca: $('#modal_mother_marca').val().trim(),
            modelo: $('#modal_mother_modelo').val().trim(),
            tipo_ram: $('#modal_mother_ram').val()
        };

        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/catalogo_guardar_modelo.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);
                if (resp && resp.success && resp.data) {
                    const data = resp.data;
                    const val = data.valor_completo;
                    const texto = data.etiqueta;

                    // Agregar la opción en todos los selects de motherboard en la pantalla
                    $('select.select-mother-catalog, select[name*="mother"]').each(function() {
                        const $sel = $(this);
                        if ($sel.find(`option[value="${val}"]`).length === 0) {
                            const newOption = new Option(texto, val, false, false);
                            $(newOption).attr('data-ram', data.tipo_ram);
                            $sel.append(newOption);
                        }
                    });

                    // Seleccionar en el select que abrió el modal
                    if ($selectHardwareObjetivo && $selectHardwareObjetivo.length) {
                        $selectHardwareObjetivo.val(val).trigger('change');
                    }

                    // Cerrar modal
                    const modalEl = document.getElementById('modalNuevoHardwareMother');
                    bootstrap.Modal.getInstance(modalEl).hide();

                    if (typeof showToast === 'function') {
                        showToast(resp.data.mensaje || 'Motherboard agregada al catálogo con éxito', 'success');
                    }
                } else {
                    const errMsg = (resp && resp.error) ? resp.error : 'No se pudo guardar la motherboard';
                    if (typeof showToast === 'function') {
                        showToast(errMsg, 'error');
                    } else {
                        alert(errMsg);
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origHtml);
                let msg = 'Error de conexión con el servidor';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r && r.error) msg = r.error;
                } catch(e) {}
                if (typeof showToast === 'function') {
                    showToast(msg, 'error');
                } else {
                    alert(msg);
                }
            }
        });
    });

    // Envío del formulario de Procesador
    $('#formNuevoHardwareProcesador').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const $btn = $('#btnGuardarCpuModal');
        const origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        const formData = {
            _csrf: $('meta[name="csrf-token"]').attr('content') || $(form).find('input[name="_csrf"]').val(),
            tipo_hardware: 'procesador',
            marca: $('#modal_cpu_marca').val().trim(),
            modelo: $('#modal_cpu_modelo').val().trim(),
            tipo_ram: $('#modal_cpu_ram').val(),
            tipo_equipo: $('#modal_cpu_equipo').val()
        };

        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/catalogo_guardar_modelo.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);
                if (resp && resp.success && resp.data) {
                    const data = resp.data;
                    const val = data.valor_completo;
                    const texto = data.etiqueta;

                    // Agregar la opción en todos los selects de procesadores compatibles en la pantalla
                    $('select.select-cpu-catalog, select[name*="procesador"]').each(function() {
                        const $sel = $(this);
                        if ($sel.find(`option[value="${val}"]`).length === 0) {
                            const newOption = new Option(texto, val, false, false);
                            $(newOption).attr('data-ram', data.tipo_ram);
                            $sel.append(newOption);
                        }
                    });

                    // Seleccionar en el select que abrió el modal
                    if ($selectHardwareObjetivo && $selectHardwareObjetivo.length) {
                        $selectHardwareObjetivo.val(val).trigger('change');
                    }

                    // Cerrar modal
                    const modalEl = document.getElementById('modalNuevoHardwareProcesador');
                    bootstrap.Modal.getInstance(modalEl).hide();

                    if (typeof showToast === 'function') {
                        showToast(resp.data.mensaje || 'Procesador agregado al catálogo con éxito', 'success');
                    }
                } else {
                    const errMsg = (resp && resp.error) ? resp.error : 'No se pudo guardar el procesador';
                    if (typeof showToast === 'function') {
                        showToast(errMsg, 'error');
                    } else {
                        alert(errMsg);
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origHtml);
                let msg = 'Error de conexión con el servidor';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r && r.error) msg = r.error;
                } catch(e) {}
                if (typeof showToast === 'function') {
                    showToast(msg, 'error');
                } else {
                    alert(msg);
                }
            }
        });
    });

})(jQuery);
</script>
