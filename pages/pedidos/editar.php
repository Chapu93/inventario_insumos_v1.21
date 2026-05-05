<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('pedidos', 'gestionar');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

$db = conectarDB();

// Obtener datos del pedido
$stmt = $db->prepare("SELECT p.*, s.id_localidad FROM pedidos p JOIN sedes s ON p.id_sede = s.id_sede WHERE p.id_pedido = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: listar.php');
    exit;
}

// Bloquear edición si está finalizado
if ($pedido['estado'] === 'Completado' || $pedido['estado'] === 'Rechazado') {
    header('Location: ver.php?id=' . $id);
    exit;
}

$usuario = obtenerUsuario();
$puedeCambiarSede = tienePermiso('pedidos', 'ver_todos');
$localidades = $db->query("SELECT * FROM localidades ORDER BY nombre_localidad")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-3">
            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary me-3"><i class="fas fa-arrow-left"></i></a>
            <h1 class="mb-0">Editar Pendiente #<?php echo $id; ?></h1>
        </div>
        
        <div class="card shadow">
            <div class="card-body">
                <form id="formEditarPedido">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    
                    <h5 class="mb-3 text-secondary border-bottom pb-2">Datos del Solicitante (Agente Externo)</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="solicitante_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="solicitante_nombre" name="solicitante_nombre" value="<?php echo htmlspecialchars($pedido['solicitante_nombre']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="solicitante_apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="solicitante_apellido" name="solicitante_apellido" value="<?php echo htmlspecialchars($pedido['solicitante_apellido']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="solicitante_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="solicitante_telefono" name="solicitante_telefono" value="<?php echo htmlspecialchars($pedido['solicitante_telefono']); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="localidad" class="form-label">Localidad <span class="text-danger">*</span></label>
                            <select class="form-select" id="localidad" required>
                                <option value="">Seleccione Localidad</option>
                                <?php foreach($localidades as $l): ?>
                                    <option value="<?php echo $l['id_localidad']; ?>" <?php echo ($l['id_localidad'] == $pedido['id_localidad']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($l['nombre_localidad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sede" class="form-label">Sede <span class="text-danger">*</span></label>
                            <select class="form-select" id="sede" name="id_sede" required>
                                <option value="">Cargando...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="area" class="form-label">Área (Opcional)</label>
                            <select class="form-select" id="area" name="id_area">
                                <option value="">Seleccione Área</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="mb-3 text-secondary border-bottom pb-2">Detalle de Solicitud</h5>
                    
                    <div class="mb-3" id="rowModoInsumo">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="modoInsumo" id="modoBuscar" value="buscar" checked>
                            <label class="btn btn-success" for="modoBuscar"><i class="fas fa-search me-1"></i>Buscar Insumo Asignado</label>
                            <input type="radio" class="btn-check" name="modoInsumo" id="modoManual" value="manual">
                            <label class="btn btn-success" for="modoManual"><i class="fas fa-keyboard me-1"></i>Ingreso Manual</label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Sección Insumos para Pedidos Técnicos -->
                        <div class="col-md-8" id="sectionInsumos">
                            <div id="containerBuscarInsumo">
                                <label for="buscar_insumo" class="form-label">Buscar Insumos Asignados</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" id="buscar_insumo" placeholder="Escriba para buscar..." autocomplete="off">
                                    <div id="resultadosBusqueda" class="dropdown-menu w-100" style="max-height:300px;overflow-y:auto;"></div>
                                </div>
                            </div>

                            <div id="containerCrearAsignacion" style="display:none;">
                                <label class="form-label">Crear Insumo Asignado</label>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" onclick="guardarYCrearManual()" class="btn btn-success">
                                        <i class="fas fa-plus me-2"></i>Crear Insumo Asignado
                                    </a>
                                </div>
                                <div class="form-text"><i class="fas fa-info-circle me-1"></i>Se abrirá el formulario para crear un nuevo insumo. Al terminar, volverá aquí.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo de Solicitud <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="Reparación" <?php echo ($pedido['tipo'] == 'Reparación') ? 'selected' : ''; ?>>Reparación</option>
                                <option value="Mantenimiento" <?php echo ($pedido['tipo'] == 'Mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                                <option value="Soporte" <?php echo ($pedido['tipo'] == 'Soporte') ? 'selected' : ''; ?>>Soporte</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="containerInsumosSeleccionados">
                        <div id="listaInsumosSeleccionados"></div>
                    </div>
                    <input type="hidden" name="id_insumo_relacionado" id="insumos_ids" value="">
                    <input type="hidden" name="insumo_relacionado" id="insumos_textos" value="">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                <option value="Baja" <?php echo ($pedido['prioridad'] == 'Baja') ? 'selected' : ''; ?>>Baja</option>
                                <option value="Media" <?php echo ($pedido['prioridad'] == 'Media') ? 'selected' : ''; ?>>Media</option>
                                <option value="Alta" <?php echo ($pedido['prioridad'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción Detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($pedido['descripcion']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Persistencia para retorno de ingreso manual
    var FORM_STORAGE_KEY = 'pedido_edit_form_data_<?php echo $id; ?>';
    
    window.guardarYCrearManual = function() {
        var formData = {
            solicitante_nombre: $('#solicitante_nombre').val(),
            solicitante_apellido: $('#solicitante_apellido').val(),
            solicitante_telefono: $('#solicitante_telefono').val(),
            localidad: $('#localidad').val(),
            id_sede: $('#sede').val(),
            id_area: $('#area').val(),
            tipo: $('#tipo').val(),
            prioridad: $('#prioridad').val(),
            descripcion: $('#descripcion').val(),
            insumos: insumosSeleccionados
        };
        sessionStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(formData));
        window.location.href = '<?php echo app_base_url(); ?>/pages/insumos/agregar_nueva.php?retorno=pedido_edit&pedido_id=<?php echo $id; ?>';
    };

    var insumosSeleccionados = [];
    
    // 1. Carga inicial desde Base de Datos
    var currentTextos = "<?php echo addslashes($pedido['insumo_relacionado']); ?>";
    var currentIds = "<?php echo $pedido['id_insumo_relacionado']; ?>";
    if (currentTextos) {
        var textosArray = currentTextos.split('|');
        var idsArray = currentIds ? currentIds.split(',') : [];
        textosArray.forEach(function(txt, i) {
            insumosSeleccionados.push({
                id: idsArray[i] || '',
                texto: txt.trim()
            });
        });
    }

    function restaurarEstado() {
        var data = sessionStorage.getItem(FORM_STORAGE_KEY);
        if (!data) return;
        var f = JSON.parse(data);
        if (f.solicitante_nombre) $('#solicitante_nombre').val(f.solicitante_nombre);
        if (f.solicitante_apellido) $('#solicitante_apellido').val(f.solicitante_apellido);
        if (f.solicitante_telefono) $('#solicitante_telefono').val(f.solicitante_telefono);
        if (f.tipo) $('#tipo').val(f.tipo);
        if (f.prioridad) $('#prioridad').val(f.prioridad);
        if (f.descripcion) $('#descripcion').val(f.descripcion);
        
        // La ubicación requiere esperar a que carguen los combos
        setTimeout(function() {
            if (f.localidad) $('#localidad').val(f.localidad).trigger('change');
            setTimeout(function() {
                if (f.id_sede) $('#sede').val(f.id_sede).trigger('change');
                setTimeout(function() {
                    if (f.id_area) $('#area').val(f.id_area);
                }, 500);
            }, 500);
        }, 300);
        
        if (f.insumos) insumosSeleccionados = f.insumos;
        sessionStorage.removeItem(FORM_STORAGE_KEY);
    }

    // Detectar retorno con nuevo insumo
    <?php if (!empty($_GET['insumo_id'])): ?>
        restaurarEstado(); // Restaurar lo que teníamos
        var nuevoInsumo = {
            id: <?php echo (int)$_GET['insumo_id']; ?>,
            texto: '<?php echo addslashes($_GET['insumo_texto'] ?? 'Nuevo Insumo'); ?>'
        };
        insumosSeleccionados.push(nuevoInsumo);
    <?php endif; ?>

    actualizarListaInsumos();

    // Replicar lógica de Localidad -> Sede -> Area de crear.php
    // No es necesario toggle dinámico en esta página ya que es solo para técnicos

    function cargarSedes(idLoc, idSedeSel = null) {
        var $sede = $('#sede');
        $sede.empty().append('<option value="">Cargando...</option>').prop('disabled', true);
        if (!idLoc) return;

        $.get('<?php echo app_base_url(); ?>/ajax/sedes_por_localidad.php', { localidad_id: idLoc }, function(resp) {
            $sede.empty().append('<option value="">Seleccione Sede</option>');
            if (resp.success && resp.data) {
                resp.data.forEach(function(s) {
                    var selected = (s.id == idSedeSel) ? 'selected' : '';
                    $sede.append('<option value="' + s.id + '" ' + selected + '>' + s.nombre + '</option>');
                });
                $sede.prop('disabled', false);
                if (idSedeSel) cargarAreas(idSedeSel, <?php echo $pedido['id_area'] ?: 'null'; ?>);
            }
        }, 'json');
    }

    function cargarAreas(idAreaSel = null) {
        var $area = $('#area');
        $area.empty().append('<option value="">Cargando...</option>').prop('disabled', true);

        $.get('<?php echo app_base_url(); ?>/ajax/areas_listar.php', function(resp) {
            $area.empty().append('<option value="">Seleccione Área</option>');
            if (resp.success && resp.data) {
                resp.data.forEach(function(a) {
                    var selected = (a.id == idAreaSel) ? 'selected' : '';
                    $area.append('<option value="' + a.id + '" ' + selected + '>' + a.nombre + '</option>');
                });
                $area.prop('disabled', false);
            }
        }, 'json');
    }

    $('#localidad').change(function() {
        cargarSedes($(this).val());
    });

    $('#modoBuscar').change(function() {
        $('#containerBuscarInsumo').show();
        $('#containerCrearAsignacion').hide();
    });
    $('#modoManual').change(function() {
        $('#containerBuscarInsumo').hide();
        $('#containerCrearAsignacion').show();
    });

    // Carga inicial de ubicaciones
    cargarSedes($('#localidad').val(), <?php echo $pedido['id_sede']; ?>);
    cargarAreas(<?php echo $pedido['id_area'] ?: 'null'; ?>);

    // Búsqueda de Insumos (Copia exacta de crear.php)
    var timeoutBusqueda = null;
    $('#buscar_insumo').on('keyup', function() {
        var q = $(this).val().trim();
        var $dropdown = $('#resultadosBusqueda');
        clearTimeout(timeoutBusqueda);
        
        if (q.length < 2) {
            $dropdown.removeClass('show').empty();
            return;
        }
        
        timeoutBusqueda = setTimeout(function() {
            $.getJSON('<?php echo app_base_url(); ?>/ajax/buscar_insumos_asignados.php', { q: q, solo_asignados: 1 }, function(resp) {
                if (resp.success && resp.data.items.length > 0) {
                    var html = '';
                    resp.data.items.forEach(function(item) {
                        var yaSel = insumosSeleccionados.some(function(i) { return i.id == item.id; });
                        if (yaSel) return;
                        
                        html += '<a href="#" class="dropdown-item py-2 item-insumo" data-item=\'' + JSON.stringify(item).replace(/'/g, "&#39;") + '\'>';
                        html += '<div><strong>' + item.tipo + '</strong> ' + (item.nombre || '') + '</div>';
                        html += '<small class="text-muted">S/N: ' + (item.numero_serie || 'N/A') + '</small></a>';
                    });
                    $dropdown.html(html).addClass('show');
                } else {
                    $dropdown.html('<div class="dropdown-item text-muted">Sin resultados</div>').addClass('show');
                }
            });
        }, 300);
    });

    $(document).on('click', '.item-insumo', function(e) {
        e.preventDefault();
        var item = JSON.parse($(this).attr('data-item'));
        insumosSeleccionados.push({
            id: item.id,
            texto: item.tipo + (item.nombre ? ' - ' + item.nombre : '') + ' (S/N: ' + (item.numero_serie || 'N/A') + ')'
        });
        actualizarListaInsumos();
        $('#buscar_insumo').val('');
        $('#resultadosBusqueda').removeClass('show');
    });

    $(document).on('click', '.btn-remover-insumo', function() {
        var idx = $(this).data('index');
        insumosSeleccionados.splice(idx, 1);
        actualizarListaInsumos();
    });

    function actualizarListaInsumos() {
        var $lista = $('#listaInsumosSeleccionados');
        if (insumosSeleccionados.length === 0) {
            $lista.html('');
            $('#insumos_ids').val('');
            $('#insumos_textos').val('');
            return;
        }
        
        var html = '<div class="card border-success bg-success-subtle py-2 mb-3 shadow-sm px-3"><div class="card-body p-1"><strong><i class="fas fa-boxes me-1"></i>Insumos seleccionados:</strong><br>';
        var ids = [];
        var textos = [];
        
        insumosSeleccionados.forEach(function(item, index) {
            if (item.id) ids.push(item.id);
            textos.push(item.texto);
            
            var isLast = index === insumosSeleccionados.length - 1;
            var borderStyle = isLast ? '' : 'border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 6px; margin-bottom: 6px;';
            
            html += '<div class="d-flex justify-content-between align-items-center" style="' + borderStyle + '">';
            html += '<span>' + item.texto + '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 ms-2 btn-remover-insumo" data-index="' + index + '"><i class="fas fa-times"></i></button>';
            html += '</div>';
        });
        html += '</div></div>';
        
        $lista.html(html);
        $('#insumos_ids').val(ids.join(','));
        $('#insumos_textos').val(textos.join('|'));
    }

    // Submit AJAX
    $('#formEditarPedido').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        
        var formData = new FormData(this);
        formData.append('id', '<?php echo $id; ?>');
        formData.append('accion', 'editar');
        formData.append('insumos_ids', $('#insumos_ids').val());
        formData.append('insumos_textos', $('#insumos_textos').val());

        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.data.mensaje || 'Pedido actualizado correctamente', 'success');
                    setTimeout(function(){ window.location.href = 'ver.php?id=<?php echo $id; ?>'; }, 1000);
                } else {
                    showToast(resp.error || 'Error', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
                }
            },
            error: function() {
                showToast('Error de conexión', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
            }
        });
    });
});
</script>
