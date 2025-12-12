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

    if (tipoInsumo !== 'PC Escritorio' && tipoInsumo !== 'PC Completa') {
        $('#sist_op').val('');
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
            case 'PC Escritorio':
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
            case 'PC Escritorio':
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

// Función para exportar tabla a Excel (sin columna Acciones)
function exportarExcel(tablaId, nombreArchivo) {
    console.log('Exportando Excel:', tablaId, nombreArchivo);
    const tabla = document.getElementById(tablaId);
    
    if (!tabla) {
        console.error('Tabla no encontrada:', tablaId);
        alert('Error: No se encontró la tabla');
        return;
    }
    
    // Clonar la tabla para no modificar el original
    const tablaClonada = tabla.cloneNode(true);
    
    // Eliminar última columna (Acciones) del thead
    const theadRows = tablaClonada.querySelectorAll('thead tr');
    theadRows.forEach(function(row) {
        const ths = row.querySelectorAll('th');
        if (ths.length > 0) {
            console.log('Columnas thead antes:', ths.length);
            // Eliminar el último th (Acciones)
            ths[ths.length - 1].remove();
            console.log('Columnas thead después:', ths.length - 1);
        }
    });
    
    // Eliminar última columna (Acciones) del tbody
    const tbodyRows = tablaClonada.querySelectorAll('tbody tr');
    console.log('Filas encontradas:', tbodyRows.length);
    tbodyRows.forEach(function(row) {
        const tds = row.querySelectorAll('td');
        if (tds.length > 0) {
            // Eliminar el último td (Acciones)
            tds[tds.length - 1].remove();
        }
    });
    
    // Exportar la tabla sin la columna de acciones
    console.log('Generando Excel...');
    const wb = XLSX.utils.table_to_book(tablaClonada, {sheet: "Sheet1"});
    XLSX.writeFile(wb, `${nombreArchivo}_${new Date().toISOString().split('T')[0]}.xlsx`);
    console.log('Excel exportado correctamente');
}

function exportarExcelSinColumnas(tablaId, nombreArchivo, indicesExcluir = []) {
    console.log('Exportando Excel con exclusiones:', tablaId, nombreArchivo, indicesExcluir);
    const tabla = document.getElementById(tablaId);

    if (!tabla) {
        console.error('Tabla no encontrada:', tablaId);
        alert('Error: No se encontró la tabla');
        return;
    }

    const tablaClonada = tabla.cloneNode(true);

    const removerColumnas = (selectorFila) => {
        tablaClonada.querySelectorAll(selectorFila).forEach(row => {
            const celdas = Array.from(row.children);
            indicesExcluir.slice().sort((a, b) => b - a).forEach(idx => {
                if (celdas[idx]) { celdas[idx].remove(); }
            });
        });
    };

    removerColumnas('thead tr');
    removerColumnas('tbody tr');

    console.log('Generando Excel (sin columnas)...');
    const wb = XLSX.utils.table_to_book(tablaClonada, {sheet: "Sheet1"});
    XLSX.writeFile(wb, `${nombreArchivo}_${new Date().toISOString().split('T')[0]}.xlsx`);
    console.log('Excel exportado correctamente (sin columnas)');
}

// Función para imprimir tabla (sin columna Acciones)
function imprimirTabla(tablaId, nombreArchivo) {
    console.log('Imprimiendo tabla:', tablaId, nombreArchivo);
    const tabla = document.getElementById(tablaId);
    
    if (!tabla) {
        console.error('Tabla no encontrada:', tablaId);
        alert('Error: No se encontró la tabla');
        return;
    }
    
    // Clonar la tabla para no modificar el original
    const tablaClonada = tabla.cloneNode(true);
    
    // Eliminar última columna (Acciones) del thead
    const theadRows = tablaClonada.querySelectorAll('thead tr');
    theadRows.forEach(function(row) {
        const ths = row.querySelectorAll('th');
        if (ths.length > 0) {
            console.log('Columnas thead antes:', ths.length);
            // Eliminar el último th (Acciones)
            ths[ths.length - 1].remove();
            console.log('Columnas thead después:', ths.length - 1);
        }
    });
    
    // Eliminar última columna (Acciones) del tbody
    const tbodyRows = tablaClonada.querySelectorAll('tbody tr');
    console.log('Filas encontradas:', tbodyRows.length);
    tbodyRows.forEach(function(row) {
        const tds = row.querySelectorAll('td');
        if (tds.length > 0) {
            // Eliminar el último td (Acciones)
            tds[tds.length - 1].remove();
        }
    });
    
    // Determinar el título
    const titulo = nombreArchivo ? nombreArchivo.charAt(0).toUpperCase() + nombreArchivo.slice(1) : tablaId;
    
    console.log('Abriendo ventana de impresión...');
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
            <head>
                <title>Imprimir ${titulo}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h2 { color: #2c3e50; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    @media print {
                        .no-print { display: none; }
                        body { padding: 10px; }
                    }
                </style>
            </head>
            <body>
                <h2>Listado de ${titulo}</h2>
                ${tablaClonada.outerHTML}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
    console.log('Ventana de impresión abierta');
}

function imprimirTablaSinColumnas(tablaId, nombreArchivo, indicesExcluir = []) {
    console.log('Imprimiendo tabla con exclusiones:', tablaId, nombreArchivo, indicesExcluir);
    const tabla = document.getElementById(tablaId);

    if (!tabla) {
        console.error('Tabla no encontrada:', tablaId);
        alert('Error: No se encontró la tabla');
        return;
    }

    const tablaClonada = tabla.cloneNode(true);

    const removerColumnas = (selectorFila) => {
        tablaClonada.querySelectorAll(selectorFila).forEach(row => {
            const celdas = Array.from(row.children);
            indicesExcluir.slice().sort((a, b) => b - a).forEach(idx => {
                if (celdas[idx]) { celdas[idx].remove(); }
            });
        });
    };

    removerColumnas('thead tr');
    removerColumnas('tbody tr');

    const titulo = nombreArchivo ? nombreArchivo.charAt(0).toUpperCase() + nombreArchivo.slice(1) : tablaId;

    console.log('Abriendo ventana de impresión (sin columnas)...');
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
            <head>
                <title>Imprimir ${titulo}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h2 { color: #2c3e50; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    @media print {
                        .no-print { display: none; }
                        body { padding: 10px; }
                    }
                </style>
            </head>
            <body>
                <h2>Listado de ${titulo}</h2>
                ${tablaClonada.outerHTML}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
    console.log('Ventana de impresión abierta (sin columnas)');
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

// Función mejorada para inicializar tooltips de forma robusta
function inicializarTooltips() {
    // Destruir todos los tooltips existentes para evitar duplicados
    try {
        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        existingTooltips.forEach(function(el) {
            const instance = bootstrap.Tooltip.getInstance(el);
            if (instance) {
                instance.dispose();
            }
        });
    } catch(e) {
        console.warn('Error al destruir tooltips existentes:', e);
    }
    
    // Crear nuevos tooltips con configuración unificada
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        try {
            // Configuración unificada para todos los tooltips
            new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover',
                delay: { show: 300, hide: 100 },
                animation: true,
                html: false,
                placement: 'top',
                container: 'body',
                boundary: 'viewport',
                fallbackPlacements: ['bottom', 'left', 'right'],
                customClass: 'custom-tooltip',
                sanitize: true
            });
        } catch(e) {
            console.warn('Error al crear tooltip:', e);
        }
    });
}

// Ocultar todos los tooltips al hacer scroll (mejor performance)
let scrollTimeout;
window.addEventListener('scroll', function() {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function() {
        try {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(function(el) {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) {
                    instance.hide();
                }
            });
        } catch(e) {}
    }, 50);
}, { passive: true });

// Ocultar tooltips al hacer click fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-bs-toggle="tooltip"]')) {
        try {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(function(el) {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) {
                    instance.hide();
                }
            });
        } catch(e) {}
    }
}, { passive: true });

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
  
  // Toggle sidebar on small screens
  $('#btnToggleSidebar').on('click', function(){
    try {
      const $sidebar = $('#sidebar');
      const $content = $('#content');
      // En móviles: si está oculto (margen negativo), remover 'active' para mostrar
      // Si está visible, agregar 'active' para ocultar
      const hidden = $sidebar.hasClass('active');
      if (hidden) { $sidebar.removeClass('active'); $content.removeClass('active'); }
      else { $sidebar.addClass('active'); $content.addClass('active'); }
    } catch(e) {}
  });

  // Cerrar sidebar al navegar (en móviles)
  $(document).on('click', 'a.nav-link, .components a[href]', function(e){
    // No cerrar el sidebar si el link es un toggler de colapso (submenu)
    var isToggler = this.hasAttribute('data-bs-toggle') && this.getAttribute('data-bs-toggle') === 'collapse';
    if (isToggler) { return; }
    
    // Toggle collapse para Insumos y Asignaciones
    var collapseTarget = $(this).data('collapse-target');
    if (collapseTarget) {
      e.preventDefault();
      var $collapse = $(collapseTarget);
      if ($collapse.length) {
        // Usar Bootstrap Collapse API
        var bsCollapse = new bootstrap.Collapse($collapse[0], {
          toggle: true
        });
        // Luego navegar
        setTimeout(function() {
          window.location.href = $(this).attr('href');
        }.bind(this), 200);
      }
      return;
    }
    
    if (window.matchMedia('(max-width: 992px)').matches) {
      $('#sidebar').removeClass('active');
      $('#content').removeClass('active');
    }
  });

  // Al cambiar tamaño de ventana, normalizar estado del sidebar
  function normalizeSidebarByViewport(){
    if (window.matchMedia('(min-width: 992px)').matches) {
      // En escritorio, sidebar siempre visible
      $('#sidebar').removeClass('active');
      $('#content').removeClass('active');
    } else {
      // En móvil, estado cerrado por defecto
      // (se abrirá con el botón hamburguesa)
      // No forzar si el usuario lo abrió manualmente
    }
  }
  $(window).on('resize', normalizeSidebarByViewport);
  // Normalizar al cargar
  normalizeSidebarByViewport();
    
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
  
  // Editar cantidades en modal de confirmación
  $(document).on('change', '#modalConfirmacion input[data-edit-id]', function(){
    var id = $(this).data('edit-id');
    var val = Math.max(1, parseInt($(this).val()||'1', 10));
    try {
      var $hidden = $(`.hidden-insumo-input[value="${id}"]`);
      var tipo = ($hidden.data('tipo')||'');
      if (tipo === 'Varios') {
        var $qty = $(`input[name="cantidad_varios[${id}]" ]`);
        if ($qty.length) { $qty.val(val); }
      }
    } catch(e) {}
  });
}); 