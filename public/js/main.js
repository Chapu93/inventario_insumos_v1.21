console.log('main.js cargado correctamente');

// Funciones de utilidad
function showToast(message, type) {
  try {
    const container = document.getElementById('toastContainer');
    if (!container) { alert(message); return; }
    const id = 't' + Date.now();
    const bg = type === 'error' ? 'bg-danger text-white' : (type === 'warning' ? 'bg-warning' : (type === 'success' ? 'bg-success text-white' : 'bg-secondary text-white'));
    const el = document.createElement('div');
    el.className = `toast align-items-center ${bg}`;
    el.id = id;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${$('<div>').text(message || '').html()}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3500 });
    t.show();
    el.addEventListener('hidden.bs.toast', function(){ el.remove(); });
  } catch(e) {
    try { alert(message); } catch(_) {}
  }
}

function disableSelect(sel, txt) {
  const $s = (typeof sel === 'string') ? $(sel) : $(sel);
  $s.prop('disabled', true);
  if (txt) { $s.html(`<option>${txt}</option>`); }
}
function enableSelect(sel) {
  const $s = (typeof sel === 'string') ? $(sel) : $(sel);
  $s.prop('disabled', false);
}
function confirmarAccion(mensaje, url) {
    if (confirm(mensaje)) {
        window.location.href = url;
    }
}

function eliminarItem(id, tipo) {
    console.log('Función eliminarItem llamada con:', id, tipo);
    if (confirm(`¿Está seguro que desea eliminar este ${tipo}?`)) {
        console.log('Redirigiendo a:', `eliminar.php?id=${id}&tipo=${tipo}`);
        window.location.href = `eliminar.php?id=${id}&tipo=${tipo}`;
    }
}

function cambiarEstadoAsignacion(id, nuevoEstado) {
    if (confirm(`¿Está seguro que desea cambiar el estado a "${nuevoEstado}"?`)) {
        window.location.href = `cambiar_estado.php?id=${id}&estado=${nuevoEstado}`;
    }
}

function generarRemitoPDF(numeroRemito) {
  const base = getAppBase();
  console.log('[DEBUG] APP base:', base);
  window.open(`${base}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(numeroRemito)}`, '_blank');
}

// Configurar CSRF en AJAX por defecto
$(function() {
  try {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    if (token && $.ajaxSetup) {
      $.ajaxSetup({
        beforeSend: function(xhr) { xhr.setRequestHeader('X-CSRF-Token', token); }
      });
    }
  } catch(e) { console.warn('CSRF meta no encontrado'); }
});

// Función para cargar insumos por sede
function cargarInsumosPorSede(sedeId, selectId) {
    const sel = (selectId && String(selectId).charAt(0) === '#') ? String(selectId) : `#${selectId}`;
    if (!sedeId) {
        $(sel).html('<option value="">Seleccione un insumo</option>');
        return;
    }
    const url = `${getAppBase()}/ajax/cargar_insumos.php`;
    console.log('[DEBUG] GET', url, { sede_id: sedeId });
    $.ajax({
        url,
        type: 'GET',
        data: { sede_id: sedeId },
        dataType: 'json',
      beforeSend: function(){ disableSelect(sel, 'Cargando...'); },
        success: function(response) {
            console.log('[DEBUG] cargar_insumos response', response);
            const data = response && response.data ? response.data : response;
            const lista = data && data.insumos ? data.insumos : [];
            if (response && response.success && Array.isArray(lista)) {
                let options = '<option value="">Seleccione un insumo</option>';
                lista.forEach(function(it){
                    const tipo = it.tipo_insumo || '';
                    const max = (tipo === 'Varios') ? parseInt(it.cantidad || 0, 10) : 1;
                    let texto = String(it.nombre_insumo || '');
                    if (tipo === 'Varios') {
                        texto += ` (Cantidad: ${max})`;
                    } else {
                        if (it.numero_serie) { texto += ` (S/N: ${it.numero_serie})`; }
                        if (it.id_fisico) { texto += ` (ID: ${it.id_fisico})`; }
                    }
                    texto += ` - ${tipo}`;
                    const safeTipo = tipo.replace(/"/g, '&quot;');
                    options += `<option value="${parseInt(it.id_insumo,10)}" data-tipo="${safeTipo}" data-max="${max}">${$('<div>').text(texto).html()}</option>`;
                });
                $(sel).html(options).trigger('change');
                enableSelect(sel);
            } else {
                console.error('Error al cargar insumos:', response && response.error);
                $(sel).html('<option value="">Error al cargar insumos</option>');
                enableSelect(sel);
                showToast('Error al cargar insumos', 'error');
            }
        },
        error: function(xhr) {
            console.error('Error en la petición AJAX', xhr.status, xhr.responseText);
            $(sel).html('<option value="">Error al cargar insumos</option>');
          enableSelect(sel);
          showToast('Error de red al cargar insumos', 'error');
        }
    });
}

// Función para cargar áreas por sede
function cargarAreasPorSede(sedeId, selectId) {
    const sel = (selectId && String(selectId).charAt(0) === '#') ? String(selectId) : `#${selectId}`;
    if (!sedeId) {
        $(sel).html('<option value="">Seleccione un área</option>');
        return;
    }
    const url = `${getAppBase()}/ajax/cargar_areas.php`;
    console.log('[DEBUG] GET', url, { sede_id: sedeId });
    $.ajax({
        url,
        type: 'GET',
        data: { sede_id: sedeId },
        dataType: 'json',
      beforeSend: function(){ disableSelect(sel, 'Cargando...'); },
        success: function(response) {
            console.log('[DEBUG] cargar_areas response', response);
            const data = response && response.data ? response.data : response;
            const lista = data && data.areas ? data.areas : [];
            if (response && response.success && Array.isArray(lista)) {
                let options = '<option value="">Seleccione un área</option>';
                lista.forEach(function(a){
                    const nombre = a.nombre_area || '';
                    options += `<option value="${parseInt(a.id_area,10)}">${$('<div>').text(nombre).html()}</option>`;
                });
                $(sel).html(options).trigger('change');
                enableSelect(sel);
            } else {
                console.error('Error al cargar áreas:', response && response.error);
                $(sel).html('<option value="">Error al cargar áreas</option>');
                enableSelect(sel);
                showToast('Error al cargar áreas', 'error');
            }
        },
        error: function(xhr) {
            console.error('Error en la petición AJAX', xhr.status, xhr.responseText);
            $(sel).html('<option value="">Error al cargar áreas</option>');
          enableSelect(sel);
          showToast('Error de red al cargar áreas', 'error');
        }
    });
}

// Función para cargar sedes por localidad
function cargarSedesPorLocalidad(localidadId, selectId) {
  const sel = (selectId && String(selectId).charAt(0) === '#') ? String(selectId) : `#${selectId}`;
  if (!localidadId) {
    $(sel).html('<option value="">Seleccione una sede</option>');
    return;
  }
  const url = `${getAppBase()}/ajax/cargar_sedes.php`;
  console.log('[DEBUG] GET', url, { localidad_id: localidadId });
  $.ajax({
    url,
    type: 'GET',
    data: { localidad_id: localidadId },
    dataType: 'json',
    beforeSend: function(){ disableSelect(sel, 'Cargando...'); },
    success: function(response) {
      console.log('[DEBUG] cargar_sedes response', response);
      const data = response && response.data ? response.data : response;
      const lista = data && data.sedes ? data.sedes : [];
      if (response && response.success && Array.isArray(lista)) {
        let options = '<option value="">Seleccione una sede</option>';
        lista.forEach(function(s){
          options += `<option value="${parseInt(s.id,10)}">${$('<div>').text(s.nombre || '').html()}</option>`;
        });
        $(sel).html(options).trigger('change');
        enableSelect(sel);
      } else {
        console.error('Error al cargar sedes:', response && response.error);
        $(sel).html('<option value="">Error al cargar sedes</option>');
        enableSelect(sel);
        showToast('Error al cargar sedes', 'error');
      }
    },
    error: function(xhr) {
      console.error('Error en la petición AJAX', xhr.status, xhr.responseText);
      $(sel).html('<option value="">Error al cargar sedes</option>');
      enableSelect(sel);
      showToast('Error de red al cargar sedes', 'error');
    }
  });
}

// Función para validar formularios
function validarFormulario(formId, e) {
    const form = document.getElementById(formId);
    if (!form) { return true; }
    if (!form.checkValidity()) {
        if (e && typeof e.preventDefault === 'function') { e.preventDefault(); }
        if (e && typeof e.stopPropagation === 'function') { e.stopPropagation(); }
        form.classList.add('was-validated');
        return false;
    }
    form.classList.add('was-validated');
    return true;
}

// Función mejorada para mostrar campos específicos según tipo de insumo
function mostrarCamposEspecificos(tipoInsumo) {
    console.log('mostrarCamposEspecificos llamado con:', tipoInsumo);
    
    // Ocultar todos los campos específicos primero
    $('.campos-especificos').hide();
    $('#campos-varios').hide();
    $('#campos-especificos').hide();
    
    // Limpiar campos cuando se cambia el tipo
    if (tipoInsumo !== 'Varios') {
        $('#subcategoria_varios').val('');
        $('#descripcion_general').val('');
        $('#cantidad').val('1');
        $('#cantidad').prop('readonly', true);
    } else {
        $('#cantidad').prop('readonly', false);
    }
    
    if (tipoInsumo === 'Varios') {
        console.log('Mostrando campos para Varios');
        $('#campos-varios').show();
        $('#campos-especificos').hide();
    } else if (tipoInsumo !== '') {
        console.log('Mostrando campos específicos para:', tipoInsumo);
        $('#campos-varios').hide();
        $('#campos-especificos').show();
        
        // Mostrar campos específicos según tipo
        switch(tipoInsumo) {
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
    
    // Actualizar validación de campos
    actualizarValidacionCampos(tipoInsumo);
}

// Función para actualizar validación de campos según tipo de insumo
function actualizarValidacionCampos(tipo) {
    // Resetear validación de campos específicos
    $('.campos-especificos input, .campos-especificos select').removeClass('is-invalid');
    
    if (tipo !== 'Varios' && tipo !== '') {
        // Hacer obligatorios número de serie e ID físico
        $('#numero_serie, #id_fisico').prop('required', true);
        
        // Hacer obligatorios los campos específicos según tipo
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
    } else {
        // Para tipo "Varios", quitar obligatoriedad de campos específicos
        $('#numero_serie, #id_fisico').prop('required', false);
        $('.campos-especificos input, .campos-especificos select').prop('required', false);
    }
}

// Función para exportar tabla a Excel
function exportarExcel(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    const wb = XLSX.utils.table_to_book(tabla, {sheet: "Sheet1"});
    XLSX.writeFile(wb, `${nombreArchivo}_${new Date().toISOString().split('T')[0]}.xlsx`);
}

// Función para imprimir tabla
function imprimirTabla(tablaId) {
    const tabla = document.getElementById(tablaId);
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
            <head>
                <title>Imprimir ${tablaId}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                ${tabla.outerHTML}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
}

// Función para actualizar contadores del dashboard
function actualizarContadores() {
    $.ajax({
        url: `${getAppBase()}/ajax/contadores_dashboard.php`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#total-insumos').text(response.contadores.total_insumos);
                $('#insumos-disponibles').text(response.contadores.insumos_disponibles);
                $('#insumos-asignados').text(response.contadores.insumos_asignados);
                $('#total-asignaciones').text(response.contadores.total_asignaciones);
            }
        },
        error: function() {
            console.error('Error al actualizar contadores');
        }
    });
}

// Función para inicializar tooltips
function inicializarTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Función para mostrar loading
function mostrarLoading(elemento) {
    $(elemento).html('<div class="loading"></div>');
}

// Función para ocultar loading
function ocultarLoading(elemento, contenido) {
    $(elemento).html(contenido);
}

function getAppBase() {
  if (window.APP_BASE_URL && window.APP_BASE_URL !== '/') {
    return String(window.APP_BASE_URL).replace(/\/$/, '');
  }
  const m = window.location.pathname.match(/^(.*)\/pages\//);
  if (m && m[1] !== undefined) {
    return m[1];
  }
  return '';
}

// UI: manejar cantidades para tipo "Varios"
function inicializarCantidadInsumos() {
  const $select = $('#id_insumo');
  const contenedor = $('#contenedor-cantidades');
  contenedor.empty();
  const seleccionados = $select.find('option:selected');
  if (!seleccionados.length) { return; }
  seleccionados.each(function() {
    const $opt = $(this);
    const id = $opt.val();
    const texto = $opt.text();
    const tipo = $opt.data('tipo');
    const max = parseInt($opt.data('max') || 1, 10);
    if (tipo === 'Varios') {
      const row = `
        <div class="row g-2 align-items-center mb-2 cantidad-item" data-id="${id}">
          <div class="col-sm-8"><small>${texto}</small></div>
          <div class="col-sm-4 d-flex align-items-center gap-2">
            <input type="number" class="form-control form-control-sm cantidad-varios" name="cantidad_varios[${id}]" min="1" max="${max}" value="1">
            <small class="text-muted">max ${max}</small>
          </div>
        </div>`;
      contenedor.append(row);
    }
  });
}

// Mostrar loader en selects dependientes
function setLoading($select, texto) {
  $select.html(`<option value="">${texto}</option>`);
}

// Document ready
$(document).ready(function() {
    // Inicializar tooltips
    inicializarTooltips();
    
    // Actualizar contadores cada 30 segundos
    setInterval(actualizarContadores, 30000);
    
    // Manejar cambio de tipo de insumo
    $('#tipo_insumo').on('change', function() {
        mostrarCamposEspecificos($(this).val());
    });
    
    // Manejar cambio de sede en formularios
    $('#sede_id').on('change', function() {
        const sedeId = $(this).val();
        cargarInsumosPorSede(sedeId, '#insumo_id');
        cargarAreasPorSede(sedeId, '#area_id');
    });
    
    // Validar formularios antes de enviar
    $('form').on('submit', function(e) {
        return validarFormulario(this.id, e);
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Asegurar que los botones de acción funcionen correctamente
    $(document).on('click', '[onclick*="eliminarItem"]', function(e) {
        e.preventDefault();
        const onclick = $(this).attr('onclick');
        const match = onclick.match(/eliminarItem\((\d+),\s*'([^']+)'\)/);
        if (match) {
            const id = match[1];
            const tipo = match[2];
            eliminarItem(id, tipo);
        }
    });

    // React a selección múltiple para dibujar cantidades de Varios
    $('#id_insumo').on('change', inicializarCantidadInsumos);
}); 