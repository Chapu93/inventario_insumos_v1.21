<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('pedidos', 'crear');

$usuario = obtenerUsuario();
$userSedeId = $usuario['id_sede'];
$puedeCambiarSede = tienePermiso('pedidos', 'ver_todos'); // Asunción: si ve todos, puede pedir para cualquiera? O mejor 'gestionar'. Usemos 'ver_todos' como proxy de admin/operador global.

// Detectar si viene con un insumo ya creado desde agregar_nueva.php
$insumoPreseleccionado = null;
if (!empty($_GET['insumo_id']) && !empty($_GET['insumo_texto'])) {
    $insumoPreseleccionado = [
        'id' => (int)$_GET['insumo_id'],
        'texto' => urldecode($_GET['insumo_texto'])
    ];
}

// Cargar usuarios para asignación (solo si tiene permisos de gestión)
$usuariosTecnicos = [];
$localidades = []; // Iniciar variable

$db = conectarDB();
// Cargar localidades si tiene permiso
if ($puedeCambiarSede) {
     $localidades = $db->query("SELECT * FROM localidades ORDER BY nombre_localidad")->fetchAll(PDO::FETCH_ASSOC);
}
// Cargar Areas si tenemos sede fija (Usuario normal)
$areasIniciales = [];
if (!$puedeCambiarSede && $userSedeId > 0) {
    // Reutilizamos lógica de cargar areas
    $stmt = $db->prepare("SELECT DISTINCT a.id_area, a.nombre_area FROM areas a JOIN sede_areas sa ON sa.id_area = a.id_area WHERE sa.id_sede = ? AND sa.activa = 1 ORDER BY a.nombre_area");
    $stmt->execute([$userSedeId]);
    $areasIniciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-3">
            <a href="javascript:void(0);" onclick="cancelarPedido()" class="btn btn-outline-secondary me-3"><i class="fas fa-arrow-left"></i></a>
            <h1 class="mb-0">Nuevo Pendiente</h1>
        </div>
        
            <div class="card shadow">
            <div class="card-body">
                <form id="formCrearPedido">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="accion" value="crear">                    
                    
                    <!-- Datos del Solicitante (Externo) -->
                    <h5 class="mb-3 text-secondary border-bottom pb-2">Datos del Solicitante (Agente Externo)</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="solicitante_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="solicitante_nombre" name="solicitante_nombre" required maxlength="100" placeholder="Nombre">
                        </div>
                        <div class="col-md-4">
                            <label for="solicitante_apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="solicitante_apellido" name="solicitante_apellido" required maxlength="100" placeholder="Apellido">
                        </div>
                        <div class="col-md-4">
                            <label for="solicitante_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="solicitante_telefono" name="solicitante_telefono" placeholder="Opcional">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Ubicación del Solicitante (Localidad, Sede, Area) -->
                        <?php if ($puedeCambiarSede): ?>
                            <div class="col-md-4">
                                <label for="localidad" class="form-label">Localidad <span class="text-danger">*</span></label>
                                <select class="form-select" id="localidad" required>
                                    <option value="">Seleccione Localidad</option>
                                    <?php foreach($localidades as $l): ?>
                                        <option value="<?php echo $l['id_localidad']; ?>"><?php echo htmlspecialchars($l['nombre_localidad']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="sede" class="form-label">Sede <span class="text-danger">*</span></label>
                                <select class="form-select" id="sede" name="sede" required disabled>
                                    <option value="">Primero elija localidad</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="area" class="form-label">Área (Opcional)</label>
                                <select class="form-select" id="area" name="area">
                                    <option value="">Cargando áreas...</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="form-label">Sede</label>
                                <input type="text" class="form-control" value="Mi Sede Actual" disabled>
                                <input type="hidden" name="sede" value="<?php echo $userSedeId; ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="area" class="form-label">Área (Opcional)</label>
                                <select class="form-select" id="area" name="area">
                                    <option value="">Seleccione Área</option>
                                    <?php foreach($areasIniciales as $a): ?>
                                        <option value="<?php echo $a['id_area']; ?>"><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h5 class="mb-3 text-secondary border-bottom pb-2">Detalle de Solicitud</h5>
                    
                    <!-- Toggle Modo Insumo -->
                    <div class="mb-3">
                        <div class="btn-group" role="group" aria-label="Modo de selección de insumo">
                            <input type="radio" class="btn-check" name="modoInsumo" id="modoBuscar" value="buscar" checked>
                            <label class="btn btn-success" for="modoBuscar"><i class="fas fa-search me-1"></i>Buscar Insumo Asignado</label>
                            
                            <input type="radio" class="btn-check" name="modoInsumo" id="modoManual" value="manual">
                            <label class="btn btn-success" for="modoManual"><i class="fas fa-keyboard me-1"></i>Ingreso Manual</label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Campo para buscar insumo del sistema -->
                        <div class="col-md-8" id="containerBuscarInsumo">
                            <label for="buscar_insumo" class="form-label">Buscar Insumos Asignados</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="buscar_insumo" placeholder="Escriba para buscar insumo asignado..." autocomplete="off">
                                <div id="resultadosBusqueda" class="dropdown-menu w-100" style="max-height:300px;overflow-y:auto;"></div>
                            </div>
                            <div class="form-text"><i class="fas fa-info-circle me-1"></i>Busque por nombre, N° serie, ID físico o persona asignada.</div>
                        </div>
                        
                        <!-- Botón para crear insumo asignado (redirige a agregar_nueva.php) -->
                        <div class="col-md-8" id="containerCrearAsignacion" style="display:none;">
                            <label class="form-label">Crear Insumo Asignado</label>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" onclick="guardarYCrearManual()" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Crear Insumo Asignado
                                </a>
                            </div>
                            <div class="form-text"><i class="fas fa-info-circle me-1"></i>Se abrirá el formulario para crear un nuevo insumo ya asignado. Al terminar, volverá aquí.</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo de Solicitud <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccione...</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                                <option value="Reparación">Reparación</option>
                                <option value="Soporte">Soporte</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Lista unificada de insumos seleccionados (visible en ambos modos) -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div id="listaInsumosSeleccionados"></div>
                            <!-- Campos ocultos para enviar los IDs -->
                            <input type="hidden" name="insumos_ids" id="insumos_ids" value="">
                            <input type="hidden" name="insumos_textos" id="insumos_textos" value="">
                        </div>
                    </div>
                    

                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                <option value="Media" selected>Media</option>
                                <option value="Alta">Alta</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                    </div>


                    
                    <?php if (!empty($usuariosTecnicos)): ?>
                    <div class="mb-3">
                        <label for="asignado_a" class="form-label">Asignar A (Opcional)</label>
                        <select class="form-select" id="asignado_a" name="asignado_a">
                            <option value="">-- Sin asignar (Pendiente General) --</option>
                            <?php foreach($usuariosTecnicos as $ut): ?>
                                <option value="<?php echo $ut['id_usuario']; ?>"><?php echo htmlspecialchars($ut['nombre'] . ' ' . $ut['apellido']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si no selecciona nadie, quedará disponible para que cualquier técnico lo tome.</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción Detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required placeholder="Describa el problema o solicitud con el mayor detalle posible..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="nota_pdf" class="form-label">Adjuntar Nota (PDF)</label>
                        <input class="form-control" type="file" id="nota_pdf" name="nota_pdf" accept="application/pdf">
                        <div class="form-text">Opcional. Puede adjuntar la nota de pedido correspondiente en formato PDF.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="javascript:void(0);" onclick="cancelarPedido()" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5"><i class="fas fa-print me-2"></i>Crear e Imprimir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    console.log('Document Ready - Pedidos Crear v2.0 - ' + new Date().toISOString());
    
    // ========================================
    // INICIALIZACIÓN DE ARRAY DE INSUMOS CON PERSISTENCIA
    // ========================================
    var STORAGE_KEY = 'pedido_insumos_seleccionados';
    var insumosSeleccionados = []; // Array para selección múltiple (búsqueda + manual)
    
    // Cargar insumos desde sessionStorage
    function cargarInsumosGuardados() {
        try {
            var guardados = sessionStorage.getItem(STORAGE_KEY);
            if (guardados) {
                insumosSeleccionados = JSON.parse(guardados);
                console.log('Insumos cargados desde sessionStorage:', insumosSeleccionados);
            }
        } catch(e) {
            console.error('Error cargando insumos:', e);
            insumosSeleccionados = [];
        }
    }
    
    // Guardar insumos en sessionStorage
    function guardarInsumos() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(insumosSeleccionados));
            console.log('Insumos guardados:', insumosSeleccionados);
        } catch(e) {
            console.error('Error guardando insumos:', e);
        }
    }
    
    // Limpiar sessionStorage (llamar después de enviar el formulario)
    function limpiarInsumosGuardados() {
        sessionStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem('pedido_form_data');
    }
    
    // ========================================
    // PERSISTENCIA DEL FORMULARIO
    // ========================================
    var FORM_STORAGE_KEY = 'pedido_form_data';
    
    function guardarEstadoFormulario() {
        var formData = {
            solicitante_nombre: $('#solicitante_nombre').val(),
            solicitante_apellido: $('#solicitante_apellido').val(),
            solicitante_telefono: $('#solicitante_telefono').val(),
            localidad: $('#localidad').val(),
            sede: $('#sede').val(),
            sede_html: $('#sede').html(),
            area: $('#area').val(),
            tipo: $('#tipo').val(),
            prioridad: $('#priority').val() || $('#prioridad').val(),
            descripcion: $('#descripcion').val()
        };
        sessionStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(formData));
        console.log('Estado del formulario guardado');
    }
    
    function restaurarEstadoFormulario() {
        try {
            var data = sessionStorage.getItem(FORM_STORAGE_KEY);
            if (!data) return;
            var formData = JSON.parse(data);
            
            if (formData.solicitante_nombre) $('#solicitante_nombre').val(formData.solicitante_nombre);
            if (formData.solicitante_apellido) $('#solicitante_apellido').val(formData.solicitante_apellido);
            if (formData.solicitante_telefono) $('#solicitante_telefono').val(formData.solicitante_telefono);
            
            if (formData.localidad) {
                $('#localidad').val(formData.localidad);
                if (formData.sede_html) {
                    $('#sede').html(formData.sede_html).val(formData.sede).prop('disabled', false);
                }
            }
            
            if (formData.area) {
                // El select de área se carga por AJAX, esperamos un poco o lo seteamos si ya cargó
                setTimeout(function() { $('#area').val(formData.area); }, 1000);
            }
            
            if (formData.tipo) $('#tipo').val(formData.tipo);
            if (formData.prioridad) $('#prioridad').val(formData.prioridad);
            if (formData.descripcion) $('#descripcion').val(formData.descripcion);
            
            console.log('Estado del formulario restaurado');
        } catch(e) {
            console.error('Error al restaurar formulario:', e);
        }
    }
    
    // Función global para guardar y saltar a creación manual
    window.guardarYCrearManual = function() {
        guardarEstadoFormulario();
        window.location.href = '<?php echo app_base_url(); ?>/pages/insumos/agregar_nueva.php?retorno=pedido';
    };

    // Función global para cancelar y limpiar todo
    window.cancelarPedido = function() {
        limpiarInsumosGuardados();
        window.location.href = 'listar.php';
    };
    
    // Guardar estado al cambiar cualquier campo importante
    $('#formCrearPedido input, #formCrearPedido select, #formCrearPedido textarea').on('change blur', function() {
        guardarEstadoFormulario();
    });
    
    // Función para actualizar la UI de insumos seleccionados
    function actualizarListaInsumos() {
        var $lista = $('#listaInsumosSeleccionados');
        if (insumosSeleccionados.length === 0) {
            $lista.html('');
            $('#insumos_ids').val('');
            $('#insumos_textos').val('');
            guardarInsumos(); // Guardar estado vacío
            return;
        }
        
        // Usamos clase 'insumos-box' en lugar de 'alert' para que el script global de footer.php no lo borre
        var html = '<div class="card border-success bg-success-subtle py-2 mb-3 shadow-sm px-3"><div class="card-body p-1"><strong><i class="fas fa-boxes me-1"></i>Insumos seleccionados:</strong><br>';
        var ids = [];
        var textos = [];
        
        insumosSeleccionados.forEach(function(item, index) {
            ids.push(item.id);
            var texto = item.texto || (item.tipo + (item.nombre ? ' - ' + item.nombre : '') + (item.numero_serie ? ' (S/N: ' + item.numero_serie + ')' : ''));
            textos.push(texto);
            
            var isLast = index === insumosSeleccionados.length - 1;
            var borderStyle = isLast ? '' : 'border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 6px; margin-bottom: 6px;';
            html += '<div class="d-flex justify-content-between align-items-center" style="' + borderStyle + '">';
            html += '<span>' + texto;
            if (item.asignado_a) html += ' <small class="text-muted">(' + item.asignado_a + ')</small>';
            if (item.origen === 'manual') html += ' <span class="badge bg-secondary ms-1">Manual</span>';
            html += '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 ms-2 btn-remover-insumo" data-index="' + index + '"><i class="fas fa-times"></i></button>';
            html += '</div>';
        });
        html += '</div></div>';
        
        $lista.html(html);
        $('#insumos_ids').val(ids.join(','));
        $('#insumos_textos').val(textos.join('|'));
        guardarInsumos(); // Guardar cada vez que se actualiza
    }
    
    // Cargar insumos guardados al iniciar
    cargarInsumosGuardados();
    
    // ========================================
    // INSUMO PRESELECCIONADO (retorno de agregar_nueva.php)
    // ========================================
    <?php if ($insumoPreseleccionado): ?>
    // Hay un insumo preseleccionado que viene de crear insumo asignado
    // Seleccionar modo "Ingreso Manual"
    $('#modoManual').prop('checked', true);
    $('#containerBuscarInsumo').hide();
    $('#containerCrearAsignacion').show();
    
    // Verificar si el insumo ya existe en la lista (para no duplicar)
    var nuevoInsumoId = <?php echo $insumoPreseleccionado['id']; ?>;
    var yaExiste = insumosSeleccionados.some(function(i) { return i.id == nuevoInsumoId; });
    
    if (!yaExiste) {
        // Agregar el insumo a la lista unificada
        insumosSeleccionados.push({
            id: nuevoInsumoId,
            texto: '<?php echo addslashes($insumoPreseleccionado['texto']); ?>',
            origen: 'manual'
        });
    }
    <?php endif; ?>
    
    // Siempre renderizar la lista (ya sea los guardados o con el nuevo)
    actualizarListaInsumos();
    
    // Restaurar datos del formulario
    restaurarEstadoFormulario();
    
    // ========================================
    // BÚSQUEDA DE INSUMOS (AJAX Simple)
    // ========================================
    var timeoutBusqueda = null;
    var $input = $('#buscar_insumo');
    var $dropdown = $('#resultadosBusqueda');
    
    console.log('Input encontrado:', $input.length > 0);
    console.log('Dropdown encontrado:', $dropdown.length > 0);
    
    $input.on('keyup', function() {
        var q = $(this).val().trim();
        console.log('Buscando:', q);
        clearTimeout(timeoutBusqueda);
        
        if (q.length < 2) {
            $dropdown.removeClass('show').empty();
            return;
        }
        
        timeoutBusqueda = setTimeout(function() {
            // solo_asignados: 1 para filtrar solo insumos asignados
            $.getJSON('<?php echo app_base_url(); ?>/ajax/buscar_insumos_asignados.php', { q: q, solo_asignados: 1 }, function(resp) {
                if (resp.success && resp.data.items.length > 0) {
                    var html = '';
                    resp.data.items.forEach(function(item) {
                        // Verificar si ya está seleccionado
                        var yaSeleccionado = insumosSeleccionados.some(function(i) { return i.id == item.id; });
                        if (yaSeleccionado) return; // No mostrar si ya está seleccionado
                        
                        html += '<a href="#" class="dropdown-item py-2 item-insumo" data-id="' + item.id + '" data-item=\'' + JSON.stringify(item).replace(/'/g, "&#39;") + '\'>';
                        html += '<div><strong>' + item.tipo + '</strong>';
                        if (item.nombre) html += ' - ' + item.nombre;
                        html += '</div>';
                        if (item.numero_serie) html += '<small class="text-muted">S/N: ' + item.numero_serie + '</small><br>';
                        if (item.asignado_a) html += '<small>Asignado a: ' + item.asignado_a + '</small>';
                        html += '</a>';
                    });
                    if (html === '') {
                        $dropdown.html('<div class="dropdown-item text-muted">Todos los resultados ya están seleccionados</div>').addClass('show');
                    } else {
                        $dropdown.html(html).addClass('show');
                    }
                } else {
                    $dropdown.html('<div class="dropdown-item text-muted">No se encontraron insumos asignados</div>').addClass('show');
                }
            }).fail(function() {
                $dropdown.html('<div class="dropdown-item text-danger">Error al buscar</div>').addClass('show');
            });
        }, 300);
    });
    
    // Agregar insumo del dropdown a la lista (selección múltiple)
    $(document).on('click', '.item-insumo', function(e) {
        e.preventDefault();
        var item = JSON.parse($(this).attr('data-item'));
        
        // Verificar si ya está seleccionado
        var yaSeleccionado = insumosSeleccionados.some(function(i) { return i.id == item.id; });
        if (yaSeleccionado) {
            showToast('Este insumo ya está seleccionado', 'warning');
            return;
        }
        
        // Agregar a la lista
        insumosSeleccionados.push(item);
        actualizarListaInsumos();
        
        // Limpiar campo de búsqueda
        $input.val('');
        $dropdown.removeClass('show');
    });
    
    // Remover insumo de la lista
    $(document).on('click', '.btn-remover-insumo', function() {
        var index = $(this).data('index');
        insumosSeleccionados.splice(index, 1);
        actualizarListaInsumos();
    });
    
    // Cerrar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#containerBuscarInsumo').length) {
            $dropdown.removeClass('show');
        }
    });
    
    // Toggle entre modo búsqueda y crear asignación
    $('input[name="modoInsumo"]').change(function() {
        var modo = $(this).val();
        if (modo === 'buscar') {
            $('#containerBuscarInsumo').show();
            $('#containerCrearAsignacion').hide();
        } else {
            $('#containerBuscarInsumo').hide();
            $('#containerCrearAsignacion').show();
        }
        // La lista de insumos seleccionados es unificada, no se limpia al cambiar de modo
    });
    
    // Manejo de Localidad -> Sede
    $('#localidad').change(function() {
        var idLoc = $(this).val();
        var $sede = $('#sede');
        $sede.empty().append('<option value="">Cargando...</option>').prop('disabled', true);

        
        if (idLoc) {
            $.get('<?php echo app_base_url(); ?>/ajax/sedes_por_localidad.php', { localidad_id: idLoc }, function(resp) {
                $sede.empty().append('<option value="">Seleccione Sede</option>');
                if (resp.success && resp.data && resp.data.length > 0) {
                    $.each(resp.data, function(i, s) {
                        $sede.append('<option value="' + s.id + '">' + s.nombre + '</option>');
                    });
                    $sede.prop('disabled', false);
                } else {
                    $sede.append('<option value="">No hay sedes</option>');
                }
            }, 'json').fail(function() {
                $sede.empty().append('<option value="">Error al cargar</option>');
            });
        } else {
            $sede.empty().append('<option value="">Primero elija localidad</option>');
        }
    });
    
    // Cargar todas las áreas al inicio
    function cargarAreas(callback) {
        var $area = $('#area');
        $area.prop('disabled', true).append('<option value="">Cargando...</option>');
        
        $.get('<?php echo app_base_url(); ?>/ajax/areas_listar.php', function(resp) {
            $area.empty().append('<option value="">Seleccione Área</option>');
            if (resp.success && resp.data && resp.data.length > 0) {
                $.each(resp.data, function(i, a) {
                    $area.append('<option value="' + a.id + '">' + a.nombre + '</option>');
                });
                $area.prop('disabled', false); // Enable always
                if (callback) callback();
            } else {
                $area.append('<option value="">No hay áreas disponibles</option>');
            }
        }, 'json').fail(function() {
            $area.empty().append('<option value="">Error al cargar</option>');
        });
    }
    
    // Al cargar áreas, intentar restaurar el valor si existe
    cargarAreas(function() {
        var data = sessionStorage.getItem(FORM_STORAGE_KEY);
        if (data) {
            var formData = JSON.parse(data);
            if (formData.area) $('#area').val(formData.area);
        }
    });

    // Manejo Sede -> Area (YA NO SE USA)
    /*
    $('#sede').change(function() {
         // Logic removed as per user request to decouple
    });
    */

    $('#formCrearPedido').on('submit', function(e) {
        console.log('Submit Event Triggered');
        e.preventDefault();
        
        // Validar que se haya seleccionado al menos un insumo (de cualquier modo)
        var insumosIds = $('#insumos_ids').val();
        if (!insumosIds || insumosIds.trim() === '') {
            showToast('Debe seleccionar al menos un insumo', 'warning');
            return false;
        }
        
        $('#sede').prop('disabled', false); // Fix: Ensure disabled field is sent
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    limpiarInsumosGuardados(); // Limpiar insumos guardados
                    showToast('¡Pedido creado exitosamente!', 'success');
                    
                    // Abrir constancia en nueva pestaña
                    if (resp.id) {
                        window.open('constancia_pdf.php?id=' + resp.id, '_blank');
                    }
                    
                    setTimeout(function(){ window.location.href = 'listar.php'; }, 1000);
                } else {
                    showToast(resp.error || 'Error al crear pedido', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-print me-2"></i>Crear e Imprimir');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                var errMsg = 'Error de conexión (' + status + ')';
                if(xhr.responseJSON && xhr.responseJSON.error) {
                    errMsg = xhr.responseJSON.error;
                }
                showToast(errMsg, 'error');
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar Solicitud');
            }
        });
    });
});

// Función para limpiar la lista de insumos completa (si fuera necesario, pero ya tenemos botones individuales)
function resetearInsumos() {
    insumosSeleccionados = [];
    actualizarListaInsumos();
}
</script>
