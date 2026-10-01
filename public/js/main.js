console.log('main.js cargado correctamente');

// Funciones de utilidad
function showToast(message, type, extraClass = '', customDelay = null) {
  try {
    const container = document.getElementById('toastContainer');
    if (!container) { alert(message); return; }
    const id = 't' + Date.now();
    const bg = type === 'error' ? 'bg-danger text-white' : (type === 'warning' ? 'bg-warning text-dark' : (type === 'success' ? 'bg-success text-white' : 'bg-primary text-white'));
    const el = document.createElement('div');
    el.className = `toast align-items-center position-relative overflow-hidden ${bg} ${extraClass}`;
    el.id = id;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `<div class="d-flex"><div class="toast-body me-auto">${message || ''}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;

    // Duración de notificaciones incrementada un 50% (7000 ms para notificaciones grandes, 4500 ms para estándar)
    const toastDelay = customDelay ? customDelay : (extraClass.includes('toast-notificacion-large') ? 7000 : 4500);

    // Agregar barra de progreso animada en el borde inferior
    const progressBar = document.createElement('div');
    progressBar.className = 'toast-progress-bar';
    progressBar.style.animationDuration = `${toastDelay}ms`;
    if (type === 'warning') {
      progressBar.style.backgroundColor = 'rgba(0, 0, 0, 0.4)';
    }
    el.appendChild(progressBar);

    container.appendChild(el);

    const t = new bootstrap.Toast(el, { delay: toastDelay });
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
// Manejador global para modales apilados (stacked modals) y backdrops
$(document).on('show.bs.modal', '.modal', function () {
    const $this = $(this);
    const $openModals = $('.modal.show').not(this);
    const count = $openModals.length;
    
    if (count > 0) {
        const baseZ = 1060 + (count * 20); // 1080, 1100, etc.
        $this.css('z-index', baseZ);
        
        setTimeout(function() {
            const $backdrops = $('.modal-backdrop');
            if ($backdrops.length > 1) {
                $backdrops.last().css('z-index', baseZ - 5);
            }
        }, 20);
    }
});

$(document).on('shown.bs.modal', '.modal', function () {
    const $this = $(this);
    const $openModals = $('.modal.show').not(this);
    const count = $openModals.length;
    
    if (count > 0) {
        const baseZ = 1060 + (count * 20);
        $this.css('z-index', baseZ);
        const $backdrops = $('.modal-backdrop');
        if ($backdrops.length > 1) {
            $backdrops.last().css('z-index', baseZ - 5);
        }
    }
    
    // Auto-foco en el botón cerrar o primer elemento interactivo del modal superior
    const $focusTarget = $this.find('.btn-close, [autofocus], button:visible').first();
    if ($focusTarget.length) {
        $focusTarget.focus();
    }
});

$(document).on('hidden.bs.modal', '.modal', function () {
    $(this).css('z-index', '');
    // Pequeño retardo para permitir que Bootstrap actualice el DOM y devuelva el foco
    setTimeout(function() {
        const remainingModals = Array.from(document.querySelectorAll('.modal.show'));
        if (remainingModals.length > 0) {
            // Mantener scroll y padding en body para evitar saltos
            $('body').addClass('modal-open');
            // Eliminar backdrops huérfanos excedentes (no puede haber más backdrops que modales abiertos)
            const $backdrops = $('.modal-backdrop');
            if ($backdrops.length > remainingModals.length) {
                $backdrops.slice(remainingModals.length).remove();
            }
            // Ordenar por z-index descendente para identificar el modal que queda en la cima visual
            remainingModals.sort((a, b) => {
                const zA = parseInt(window.getComputedStyle(a).zIndex) || 1050;
                const zB = parseInt(window.getComputedStyle(b).zIndex) || 1050;
                return zB - zA;
            });
            const topModal = remainingModals[0];
            if (topModal) {
                const $focusable = $(topModal).find('.btn-close, button:visible, [autofocus], [tabindex]:not([tabindex="-1"])').first();
                if ($focusable.length) {
                    $focusable.focus();
                } else {
                    $(topModal).focus();
                }
            }
        } else {
            // Si no quedan modales abiertos, remover TODOS los backdrops huérfanos y restaurar el body
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ 'overflow': '', 'padding-right': '' });
            if (document.activeElement && (document.activeElement.classList.contains('btn') || $(document.activeElement).closest('.btn').length)) {
                document.activeElement.blur();
            }
        }
    }, 50);
});

// Desactivar foco residual tras clic con el mouse en botones para evitar halos permanentes
$(document).on('mouseup', '.btn, .btn-close', function () {
    $(this).blur();
});

// Interceptor universal de tecla ESC para N modales apilados (1, 2, ... N modales)
// Se ejecuta en fase de captura (capture: true) en document para garantizar cierre secuencial sin requerir clics
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        const openModals = Array.from(document.querySelectorAll('.modal.show'));
        if (openModals.length >= 1) {
            // Detener inmediatamente para que NO afecte a modales inferiores ni al comportamiento nativo múltiple
            e.stopPropagation();
            e.stopImmediatePropagation();
            e.preventDefault();

            // Ordenar por z-index descendente: el índice 0 siempre es el modal situado al frente visual
            openModals.sort((a, b) => {
                const zA = parseInt(window.getComputedStyle(a).zIndex) || 1050;
                const zB = parseInt(window.getComputedStyle(b).zIndex) || 1050;
                return zB - zA;
            });

            const topModal = openModals[0];
            if (topModal) {
                const bsInst = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
                    ? bootstrap.Modal.getInstance(topModal)
                    : null;
                if (bsInst) {
                    bsInst.hide();
                } else if (typeof jQuery !== 'undefined') {
                    $(topModal).modal('hide');
                }
            }
        }
    }
}, true); // true = capture phase

/**
 * Muestra un modal de información/alerta estilizado
 * @param {Object} options { titulo, mensaje, icono, claseBoton, textoAceptar, onConfirm }
 */
function showAlert(options) {
    const opt = {
        titulo: 'Aviso',
        mensaje: '',
        icono: 'fa-info-circle text-info',
        claseBoton: 'btn-primary',
        textoAceptar: 'Entendido',
        onConfirm: null,
        ...options
    };

    const $modal = $('#modalConfirmacionSITIA');
    
    $('#modalConfirmacionTitulo').text(opt.titulo);
    $('#modalConfirmacionMensaje').html(opt.mensaje);
    $('#modalConfirmacionIcono').removeClass().addClass('fas ' + opt.icono + ' me-2');
    $('#btnConfirmacionAceptar').removeClass().addClass('btn px-4 shadow-sm ' + opt.claseBoton).text(opt.textoAceptar);
    $('#btnConfirmacionCancelar').hide(); // Ocultar cancelar para alertas

    $('#btnConfirmacionAceptar').off('click').on('click', function() {
        if (typeof opt.onConfirm === 'function') opt.onConfirm();
        $modal.modal('hide');
    });

    // Restaurar botón cancelar al cerrar
    $modal.one('hidden.bs.modal', function() {
        $('#btnConfirmacionCancelar').show();
    });

    const modalInstance = bootstrap.Modal.getOrCreateInstance($modal[0]);
    modalInstance.show();
}

/**
 * Muestra un modal de confirmación estilizado
 */
function showConfirm(options) {
    const opt = {
        titulo: 'Confirmar',
        mensaje: '¿Está seguro de realizar esta acción?',
        icono: 'fa-question-circle text-primary',
        claseBoton: 'btn-primary',
        textoAceptar: 'Aceptar',
        onConfirm: null,
        onCancel: null,
        ...options
    };

    const $modal = $('#modalConfirmacionSITIA');
    
    $('#modalConfirmacionTitulo').text(opt.titulo);
    $('#modalConfirmacionMensaje').html(opt.mensaje);
    $('#modalConfirmacionIcono').removeClass().addClass('fas ' + opt.icono + ' me-2');
    $('#btnConfirmacionAceptar').removeClass().addClass('btn px-4 shadow-sm ' + opt.claseBoton).text(opt.textoAceptar);
    $('#btnConfirmacionCancelar').show();

    $('#btnConfirmacionAceptar').off('click').on('click', function() {
        if (typeof opt.onConfirm === 'function') opt.onConfirm();
        $modal.modal('hide');
    });

    $('#btnConfirmacionCancelar').off('click').on('click', function() {
        if (typeof opt.onCancel === 'function') opt.onCancel();
    });

    const modalInstance = bootstrap.Modal.getOrCreateInstance($modal[0]);
    modalInstance.show();
}

function confirmarAccion(mensaje, url) {
    showConfirm({
        mensaje: mensaje,
        onConfirm: () => { window.location.href = url; }
    });
}

function eliminarItem(id, tipo) {
    showConfirm({
        titulo: 'Eliminar ' + tipo.charAt(0).toUpperCase() + tipo.slice(1),
        mensaje: `¿Está seguro que desea eliminar este ${tipo}?`,
        icono: 'fa-exclamation-triangle',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => { window.location.href = `eliminar.php?id=${id}&tipo=${tipo}`; }
    });
}

function cambiarEstadoAsignacion(id, nuevoEstado) {
    showConfirm({
        titulo: 'Cambiar Estado',
        mensaje: `¿Está seguro que desea cambiar el estado a "${nuevoEstado}"?`,
        icono: 'fa-sync-alt',
        onConfirm: () => { window.location.href = `cambiar_estado.php?id=${id}&estado=${nuevoEstado}`; }
    });
}

/**
 * Abre cualquier documento PDF o imagen en el visor modal integrado de SITIA.
 * Permite previsualizar, imprimir directamente o descargar sin salir de la página ni forzar descargas.
 * @param {string} url - URL del archivo (relativa o absoluta)
 * @param {string} titulo - Título a mostrar en la barra del modal
 * @param {string} [tipoForzado] - 'imagen', 'pdf' o null para autodetección
 */
function abrirVisorArchivo(url, titulo, tipoForzado) {
    if (!url) return;
    titulo = titulo || 'Documento';

    const modalEl = document.getElementById('modalVisorPDF');
    if (!modalEl || typeof bootstrap === 'undefined') {
        window.open(url, '_blank');
        return;
    }

    const modalTitle = document.getElementById('modalVisorPDFLabel');
    const icono = document.getElementById('visorPDFIcono');
    const iframe = document.getElementById('iframeVisorPDF');
    const imgContainer = document.getElementById('visorImgContainer');
    const imgElement = document.getElementById('imgVisor');
    const btnDescargar = document.getElementById('btnDescargarVisorPDF');
    const btnNuevaPestana = document.getElementById('btnNuevaPestanaVisorPDF');
    const btnImprimir = document.getElementById('btnImprimirVisorPDF');
    const loader = document.getElementById('visorPDFLoading');

    if (modalTitle) modalTitle.textContent = titulo;
    if (btnDescargar) {
        btnDescargar.href = url;
        btnDescargar.setAttribute('download', titulo.replace(/[^a-zA-Z0-9_\-]/g, '_'));
    }
    if (btnNuevaPestana) {
        btnNuevaPestana.href = url;
    }

    if (loader) loader.style.display = 'block';

    // Autodetección de imagen por URL o por extensión en el título del archivo
    const esImagen = tipoForzado === 'imagen' 
        || /\.(png|jpe?g|webp|gif|bmp|svg)(\?|$)/i.test(url) 
        || /\.(png|jpe?g|webp|gif|bmp|svg)$/i.test(titulo.trim()) 
        || url.toLowerCase().includes('formato=imagen');

    if (esImagen) {
        // --- Modo Imagen ---
        if (icono) icono.className = 'fas fa-image text-primary fs-5';

        if (iframe) {
            iframe.style.display = 'none';
            iframe.src = 'about:blank';
        }

        if (imgContainer && imgElement) {
            imgElement.style.opacity = '0';
            imgElement.style.maxHeight = '100%';
            imgElement.style.maxWidth = '100%';
            imgElement.style.cursor = 'zoom-in';

            imgElement.onload = function() {
                if (loader) loader.style.display = 'none';
                imgElement.style.opacity = '1';
                imgContainer.classList.remove('d-none');
                imgContainer.classList.add('d-flex');
            };
            imgElement.onerror = function() {
                if (loader) loader.style.display = 'none';
                imgContainer.classList.remove('d-none');
                imgContainer.classList.add('d-flex');
            };
            imgElement.src = url;

            // Zoom interactivo al hacer clic
            imgElement.onclick = function() {
                if (this.style.maxHeight === 'none') {
                    this.style.maxHeight = '100%';
                    this.style.maxWidth = '100%';
                    this.style.cursor = 'zoom-in';
                } else {
                    this.style.maxHeight = 'none';
                    this.style.maxWidth = 'none';
                    this.style.cursor = 'zoom-out';
                }
            };
        }

        if (btnImprimir) {
            btnImprimir.onclick = function() {
                const printWin = window.open('', '_blank');
                if (printWin) {
                    printWin.document.write(`
                        <!DOCTYPE html>
                        <html>
                            <head>
                                <title>${titulo}</title>
                                <style>
                                    body { margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #fff; }
                                    img { max-width: 100%; max-height: 96vh; object-fit: contain; }
                                    @media print { body { min-height: auto; } img { max-width: 100%; height: auto; } }
                                </style>
                            </head>
                            <body>
                                <img src="${url}" onload="window.print(); window.close();" />
                            </body>
                        </html>
                    `);
                    printWin.document.close();
                }
            };
        }
    } else {
        // --- Modo PDF / Documento ---
        if (icono) icono.className = 'fas fa-file-pdf text-danger fs-5';

        if (imgContainer) {
            imgContainer.classList.add('d-none');
            imgContainer.classList.remove('d-flex');
        }
        if (imgElement) {
            imgElement.src = '';
        }

        if (iframe) {
            iframe.style.display = 'block';
            iframe.style.opacity = '0';
            iframe.src = url;
            iframe.onload = function() {
                if (loader) loader.style.display = 'none';
                iframe.style.opacity = '1';
            };
        }

        if (btnImprimir) {
            btnImprimir.onclick = function() {
                if (iframe && iframe.contentWindow) {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    } catch (e) {
                        console.warn('Fallback print:', e);
                        const w = window.open(url, '_blank');
                        if (w) {
                            w.addEventListener('load', function() { w.print(); });
                        }
                    }
                }
            };
        }
    }

    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();

    // Auto-foco dentro del visor para garantizar control de teclado
    setTimeout(function() {
        const btnCerrar = modalEl.querySelector('.btn-close');
        if (btnCerrar) btnCerrar.focus();
    }, 150);
}

/**
 * Alias de compatibilidad para abrirVisorArchivo
 */
function abrirVisorPDF(url, titulo) {
    abrirVisorArchivo(url, titulo);
}

function generarRemitoPDF(numeroRemito, idInsumo = null) {
    if (window.event && (window.event.currentTarget || window.event.target)) {
        ultimoElementoVisor = window.event.currentTarget || window.event.target;
    }
    const base = getAppBase();
    console.log('[DEBUG] APP base:', base);
    let url = `${base}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(numeroRemito)}`;
    let titulo = `Remito Nº ${numeroRemito}`;
    if (idInsumo) {
        url += `&id_insumo=${encodeURIComponent(idInsumo)}`;
        titulo = `Remito Individual Nº ${numeroRemito}`;
    }
    abrirVisorArchivo(url, titulo);
}

function generarRemitoIndividualPDF(numeroRemito, idInsumo) {
    generarRemitoPDF(numeroRemito, idInsumo);
}

let ultimoElementoVisor = null;

function generarRemitoDevolucionPDF(numeroDev, triggerEl) {
    if (triggerEl) {
        ultimoElementoVisor = triggerEl;
    } else if (window.event && (window.event.currentTarget || window.event.target)) {
        ultimoElementoVisor = window.event.currentTarget || window.event.target;
    }
    const base = getAppBase();
    let url = `${base}/pages/reportes/remito_devolucion_pdf.php?devolucion=${encodeURIComponent(numeroDev)}`;
    let titulo = `Remito de Devolución Nº ${numeroDev}`;
    abrirVisorArchivo(url, titulo);
}

function generarRemitosDevolucionPorRemitoPDF(numeroRemito, triggerEl) {
    if (triggerEl) {
        ultimoElementoVisor = triggerEl;
    } else if (window.event && (window.event.currentTarget || window.event.target)) {
        ultimoElementoVisor = window.event.currentTarget || window.event.target;
    }
    const base = getAppBase();
    let url = `${base}/pages/reportes/remito_devolucion_pdf.php?remito=${encodeURIComponent(numeroRemito)}`;
    let titulo = `Remitos de Devolución - Remito Nº ${numeroRemito}`;
    abrirVisorArchivo(url, titulo);
}

// Configurar CSRF en AJAX por defecto y eventos del Visor de Archivos
$(function() {
    const modalVisor = document.getElementById('modalVisorPDF');
    if (modalVisor) {
        modalVisor.addEventListener('hidden.bs.modal', function() {
            const iframe = document.getElementById('iframeVisorPDF');
            if (iframe) {
                iframe.src = 'about:blank';
            }
            const imgElement = document.getElementById('imgVisor');
            if (imgElement) {
                imgElement.src = '';
                imgElement.style.maxHeight = '100%';
                imgElement.style.maxWidth = '100%';
                imgElement.style.cursor = 'zoom-in';
            }
            const imgContainer = document.getElementById('visorImgContainer');
            if (imgContainer) {
                imgContainer.classList.add('d-none');
                imgContainer.classList.remove('d-flex');
            }

            // Si hay otro modal abierto (ej: modalVerAsignacion), asegurar modal-open y reubicar scroll
            if (document.querySelectorAll('.modal.show').length > 0) {
                document.body.classList.add('modal-open');
                setTimeout(function() {
                    const modalVer = document.getElementById('modalVerAsignacion');
                    const devCont = document.getElementById('verDevolucionesContainer');
                    if (modalVer && modalVer.classList.contains('show') && devCont) {
                        const rectContainer = devCont.getBoundingClientRect();
                        const rectModal = modalVer.getBoundingClientRect();
                        const targetTop = modalVer.scrollTop + (rectContainer.top - rectModal.top) - 15;
                        modalVer.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
                    } else if (ultimoElementoVisor && typeof ultimoElementoVisor.scrollIntoView === 'function') {
                        ultimoElementoVisor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (ultimoElementoVisor) {
                        try { ultimoElementoVisor.blur(); } catch (err) {}
                    }
                }, 100);
            }
        });
    }

    $(document).on('click', '[data-visor-pdf], [data-visor-archivo]', function(e) {
        e.preventDefault();
        ultimoElementoVisor = this;
        const url = $(this).attr('data-visor-archivo') || $(this).attr('data-visor-pdf') || $(this).attr('href');
        const titulo = $(this).attr('data-visor-titulo') || $(this).attr('title') || $(this).text().trim() || 'Documento';
        abrirVisorArchivo(url, titulo);
    });

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
                        if (it.id_fisico) { texto += ` (ID: ${String(it.id_fisico).replace(/[- ]/g, '')})`; }
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
    if (formId === 'formFiltros') {
        return true;
    }
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
        skipActivity: true,
        xhrFields: { withCredentials: true },
        success: function(response) {
            if (response && response.success && response.contadores) {
                $('#total-insumos').text(response.contadores.total_insumos);
                $('#insumos-disponibles').text(response.contadores.insumos_disponibles);
                $('#insumos-asignados').text(response.contadores.insumos_asignados);
                $('#total-asignaciones').text(response.contadores.total_asignaciones);
            }
        },
        error: function() {
            // Silently fail to avoid console spam
        }
    });
}

// Función mejorada para inicializar tooltips de forma robusta
function inicializarTooltips(contexto) {
    const scope = contexto || document;
    // Limpiar cualquier tooltip huérfano flotante en el body
    try {
        document.querySelectorAll('body > .tooltip').forEach(function(t) {
            t.remove();
        });
    } catch(e) {}

    // Destruir instancias existentes en el scope
    try {
        const existingTooltips = scope.querySelectorAll('[data-bs-toggle="tooltip"]');
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
    const tooltipTriggerList = [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'));
    
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        try {
            new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover',
                delay: { show: 250, hide: 50 },
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
            document.querySelectorAll('body > .tooltip').forEach(function(t) {
                t.remove();
            });
        } catch(e) {}
    }, 50);
}, { passive: true });

// Ocultar tooltip inmediatamente al hacer clic en el botón disparador
$(document).on('click', '[data-bs-toggle="tooltip"]', function() {
    try {
        const instance = bootstrap.Tooltip.getInstance(this);
        if (instance) {
            instance.hide();
        }
    } catch(e) {}
    try {
        document.querySelectorAll('body > .tooltip').forEach(function(t) {
            t.remove();
        });
    } catch(e) {}
});

// Limpiar tooltips residuales al abrir o cerrar cualquier modal
$(document).on('show.bs.modal hide.bs.modal', function() {
    try {
        document.querySelectorAll('body > .tooltip').forEach(function(t) {
            t.remove();
        });
    } catch(e) {}
});

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
            document.querySelectorAll('body > .tooltip').forEach(function(t) {
                t.remove();
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
    
    // Actualizar contadores cada 30 segundos solo si existen en la página actual (Dashboard)
    if ($('#total-insumos').length > 0 || $('#insumos-disponibles').length > 0) {
        setInterval(actualizarContadores, 30000);
    }
    
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
    
    // Auto-hide solo para alertas flash/temporales (nunca modales, estados ni permanentes)
    setTimeout(function() {
        $('.alert.alert-autoclose, .alert-flash, .alert-dismissible:not(.alert-permanent)')
            .not('.alert-permanent, .modal .alert, #alertSinStock, #alertRechazo')
            .fadeOut('slow');
    }, 5000);
  
  // Toggle sidebar universal (Escritorio, Laptops y Móviles) con persistencia
  $('#btnToggleSidebar').on('click', function(e){
    e.preventDefault();
    try {
      const $sidebar = $('#sidebar');
      const $content = $('#content');
      const isDesktop = window.innerWidth > 992;
      
      if (isDesktop) {
        // En desktop: alternar colapso y recordar en localStorage
        const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        $sidebar.toggleClass('active', isCollapsed);
        $content.toggleClass('active', isCollapsed);
        localStorage.setItem('sitia_sidebar_collapsed', isCollapsed ? '1' : '0');
        // Redibujar tablas DataTables si están visibles para recalcular anchos de columna
        setTimeout(function() {
          if (typeof $.fn.dataTable !== 'undefined') {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
          }
        }, 320);
      } else {
        // En móviles/tablets: alternar visualización
        const hidden = $sidebar.hasClass('active');
        if (hidden) {
          $sidebar.removeClass('active');
          $content.removeClass('active');
        } else {
          $sidebar.addClass('active');
          $content.addClass('active');
        }
      }
    } catch(err) {
      console.warn('Error en toggle sidebar:', err);
    }
  });

  // Sincronizar estado inicial de la sidebar en escritorio si ya estaba colapsada
  if (window.innerWidth > 992 && localStorage.getItem('sitia_sidebar_collapsed') === '1') {
    $('#sidebar').addClass('active');
    $('#content').addClass('active');
  }

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

  // =====================================================================
  // MANEJADOR GLOBAL ESTANDARIZADO PARA BOTONES "VOLVER" (.btn-volver)
  // =====================================================================
  $(document).on('click', '.btn-volver', function (e) {
      e.preventDefault();

      // 1. Atributo explícito data-return-url
      const dataUrl = $(this).attr('data-return-url');
      if (dataUrl && dataUrl.trim() !== '') {
          window.location.href = dataUrl;
          return;
      }

      // 2. Parámetro ?return_to= en la URL actual
      const urlParams = new URLSearchParams(window.location.search);
      const returnTo = urlParams.get('return_to');
      if (returnTo && returnTo.trim() !== '') {
          window.location.href = returnTo;
          return;
      }

      // 3. Atributo href explícito del botón si es una ruta válida definida por el backend
      const fallbackHref = $(this).attr('href');
      if (fallbackHref && fallbackHref !== '#' && fallbackHref !== 'javascript:void(0);' && fallbackHref.trim() !== '') {
          window.location.href = fallbackHref;
          return;
      }

      // 4. document.referrer si pertenece al mismo origen y es una página diferente
      if (document.referrer) {
          try {
              const refUrl = new URL(document.referrer);
              const currUrl = new URL(window.location.href);
              // Mismo host/puerto y ruta o query distinta a la actual para evitar recargas en bucle
              if (refUrl.origin === currUrl.origin && (refUrl.pathname !== currUrl.pathname || refUrl.search !== currUrl.search)) {
                  window.location.href = document.referrer;
                  return;
              }
          } catch(err) {}
      }

      // 5. Fallback final seguro: history.back()
      window.history.back();
  });

  // =====================================================================
  // PERSISTENCIA INTELIGENTE DE FILTROS Y PESTAÑAS (sessionStorage)
  // Estandarizado para todo el proyecto SITIA
  // =====================================================================
  window.SITIA_Filtros = {
      getKey: function (path) {
          const p = path || window.location.pathname;
          return 'sitia_filtros_' + p;
      },
      obtenerModulo: function (urlOPath) {
          if (!urlOPath) return '';
          try {
              const path = (urlOPath.indexOf('http') === 0) ? new URL(urlOPath).pathname : urlOPath;
              if (path.indexOf('/pages/pedidos/') !== -1 || path.indexOf('/pages/insumos/intervenidos.php') !== -1) return 'pedidos';
              if (path.indexOf('/pages/insumos/') !== -1) return 'insumos';
              if (path.indexOf('/pages/asignaciones/') !== -1) return 'asignaciones';
              if (path.indexOf('/pages/admin/') !== -1) return 'admin';
              if (path.indexOf('/pages/reportes/') !== -1) return 'reportes';
              if (path.indexOf('/pages/dashboard.php') !== -1) return 'dashboard';
              return '';
          } catch(e) {
              return '';
          }
      },
      guardar: function (path) {
          try {
              const p = path || window.location.pathname;
              const $form = $('.filtros-container form');
              const data = {};

              if ($form.length) {
                  $form.find('input, select, textarea').each(function () {
                      const name = $(this).attr('name') || $(this).attr('id');
                      if (name) {
                          data[name] = $(this).val();
                      }
                  });
              }

              // 1. Pestañas con data-estado (Insumos, Asignaciones)
              const $tabEstado = $('#tabsEstado a.active, .nav-tabs .nav-link.active[data-estado]');
              if ($tabEstado.length && typeof $tabEstado.attr('data-estado') !== 'undefined') {
                  data['_tab_estado'] = $tabEstado.attr('data-estado');
              } else {
                  const hiddenEstado = $('#estado').val();
                  if (hiddenEstado !== undefined && hiddenEstado !== null) {
                      data['_tab_estado'] = hiddenEstado;
                  }
              }

              // 2. Pestañas con data-modo (Pedidos)
              const $tabModo = $('.nav-tabs .nav-link.active[data-modo], .nav-pills .nav-link.active[data-modo]');
              if ($tabModo.length && $tabModo.attr('data-modo')) {
                  data['_tab_modo'] = $tabModo.attr('data-modo');
              } else {
                  const hiddenModo = $('#filtroModo').val();
                  if (hiddenModo) data['_tab_modo'] = hiddenModo;
              }

              // 3. Pestañas nativas Bootstrap
              const $tabActiva = $('.nav-tabs .nav-link.active, .nav-pills .nav-link.active').not('.modal *');
              if ($tabActiva.length) {
                  if ($tabActiva.attr('id')) {
                      data['_tab_id'] = '#' + $tabActiva.attr('id');
                  }
                  const bsTarget = $tabActiva.attr('data-bs-target') || $tabActiva.attr('href');
                  if (bsTarget && bsTarget !== '#') {
                      data['_tab_target'] = bsTarget;
                  }
              }

              // 4. Buscador de DataTables
              const dtInput = document.querySelector('.dataTables_filter input[type="search"], .dataTables_filter input');
              if (dtInput && dtInput.value.trim() !== '') {
                  data['_dt_search'] = dtInput.value;
              } else {
                  data['_dt_search'] = '';
              }

              // 5. Preservar número de página de DataTables si existe en la sesión
              const existingRaw = sessionStorage.getItem(this.getKey(p));
              if (existingRaw) {
                  try {
                      const existing = JSON.parse(existingRaw);
                      if (typeof existing['_dt_page'] !== 'undefined') {
                          data['_dt_page'] = existing['_dt_page'];
                          data['_dt_start'] = existing['_dt_start'];
                      }
                  } catch(e) {}
              }

              sessionStorage.setItem(this.getKey(p), JSON.stringify(data));
          } catch (e) {
              console.warn('[SITIA_Filtros] Error al guardar:', e);
          }
      },
      resetPagina: function (path) {
          try {
              const p = path || window.location.pathname;
              const raw = sessionStorage.getItem(this.getKey(p));
              if (raw) {
                  const data = JSON.parse(raw);
                  delete data['_dt_page'];
                  delete data['_dt_start'];
                  sessionStorage.setItem(this.getKey(p), JSON.stringify(data));
              }
          } catch (e) {}
      },
      obtener: function (path) {
          try {
              const p = path || window.location.pathname;
              // Si el usuario navegó a este módulo desde un módulo distinto (ej. de Pedidos a Insumos),
              // limpiar los filtros para que el módulo empiece limpio por defecto.
              const moduloActual = this.obtenerModulo(p);
              const moduloOrigen = this.obtenerModulo(document.referrer);
              if (moduloActual && moduloOrigen && moduloActual !== moduloOrigen) {
                  this.limpiar(p);
                  return null;
              }

              const raw = sessionStorage.getItem(this.getKey(p));
              return raw ? JSON.parse(raw) : null;
          } catch (e) {
              return null;
          }
      },
      limpiar: function (path) {
          try {
              const p = path || window.location.pathname;
              sessionStorage.removeItem(this.getKey(p));
          } catch (e) {}
      },
      limpiarTodo: function () {
          try {
              const keys = [];
              for (let i = 0; i < sessionStorage.length; i++) {
                  const k = sessionStorage.key(i);
                  if (k && k.startsWith('sitia_filtros_')) keys.push(k);
              }
              keys.forEach(function(k) { sessionStorage.removeItem(k); });
          } catch(e) {}
      },
      restaurar: function () {
          try {
              const data = this.obtener();
              if (!data) return false;

              const $form = $('.filtros-container form');
              if ($form.length) {
                  Object.keys(data).forEach(function (key) {
                      if (key.startsWith('_')) return;
                      const val = data[key];
                      if (val === null || val === undefined || val === '') return;

                      const $input = $form.find(`[name="${key}"], #${key}`);
                      if (!$input.length) return;

                      if ($input.is('select')) {
                          if ($input.find(`option[value="${val}"]`).length > 0) {
                              $input.val(val);
                          } else {
                              $input.attr('data-pending-val', val);
                          }
                      } else {
                          $input.val(val);
                      }
                  });
              }

              // Restaurar pestaña activa
              if (data['_tab_id']) {
                  const tabEl = document.querySelector(data['_tab_id']);
                  if (tabEl && $(tabEl).hasClass('nav-link')) {
                      $(tabEl).trigger('click');
                  }
              } else if (data['_tab_estado']) {
                  const tabEl = document.querySelector(`#tabsEstado a[data-estado="${data['_tab_estado']}"], .nav-tabs .nav-link[data-estado="${data['_tab_estado']}"]`);
                  if (tabEl && !$(tabEl).hasClass('active')) $(tabEl).trigger('click');
              } else if (data['_tab_modo']) {
                  const tabEl = document.querySelector(`.nav-tabs .nav-link[data-modo="${data['_tab_modo']}"], .nav-pills .nav-link[data-modo="${data['_tab_modo']}"]`);
                  if (tabEl && !$(tabEl).hasClass('active')) $(tabEl).trigger('click');
              } else if (data['_tab_target']) {
                  const tabEl = document.querySelector(`[data-bs-target="${data['_tab_target']}"], [href="${data['_tab_target']}"]`);
                  if (tabEl && !$(tabEl).hasClass('active')) {
                      try { bootstrap.Tab.getOrCreateInstance(tabEl).show(); } catch (e) {}
                  }
              }

              // Restaurar buscador de DataTables
              if (data['_dt_search']) {
                  setTimeout(function () {
                      const dtInput = document.querySelector('.dataTables_filter input[type="search"], .dataTables_filter input');
                      if (dtInput) {
                          dtInput.value = data['_dt_search'];
                      }
                      try {
                          $.fn.dataTable.tables({ api: true }).each(function () {
                              this.search(data['_dt_search']);
                          });
                      } catch (e) {}
                  }, 50);
              }

              return true;
          } catch (err) {
              console.warn('[SITIA_Filtros] Error al restaurar:', err);
              return false;
          }
      }
  };

  // Eventos para guardar filtros automáticamente en tiempo real
  $(document).on('change input', '.filtros-container form :input', function () {
      if (window.SITIA_Filtros) {
          window.SITIA_Filtros.resetPagina();
          window.SITIA_Filtros.guardar();
      }
  });

  // Guardar al escribir en el buscador de DataTables
  $(document).on('keyup input', '.dataTables_filter input', function () {
      if (window.SITIA_Filtros) {
          window.SITIA_Filtros.resetPagina();
          window.SITIA_Filtros.guardar();
      }
  });

  // Guardar al cambiar de pestaña
  $(document).on('shown.bs.tab click', '.nav-tabs .nav-link, .nav-pills .nav-link, #tabsEstado a', function (e) {
      if ($(this).closest('.modal').length) return;
      if (window.SITIA_Filtros) {
          window.SITIA_Filtros.resetPagina();
          setTimeout(function () { window.SITIA_Filtros.guardar(); }, 60);
      }
  });

  // Guardar al cambiar de página en DataTables
  $(document).on('page.dt', function (e, settings) {
      setTimeout(function () {
          try {
              const api = new $.fn.dataTable.Api(settings);
              const pageInfo = api.page.info();
              if (pageInfo && typeof pageInfo.page === 'number') {
                  const p = window.location.pathname;
                  const raw = sessionStorage.getItem(SITIA_Filtros.getKey(p));
                  const data = raw ? JSON.parse(raw) : {};
                  data['_dt_page'] = pageInfo.page;
                  data['_dt_start'] = pageInfo.start;
                  data['_dt_length'] = pageInfo.length;
                  sessionStorage.setItem(SITIA_Filtros.getKey(p), JSON.stringify(data));
              }
          } catch(e) {}
      }, 50);
  });

  // Botón de limpiar filtros: borrar estado guardado
  $(document).on('click', '#btnLimpiarFiltros, [id*="Limpiar"], .btn-limpiar', function () {
      SITIA_Filtros.limpiar();
  });

  // Al hacer clic en cualquier enlace del menú de navegación (sidebar o header), limpiar estados guardados
  // Esto garantiza que cambiar de módulo desde el menú siempre comience limpio
  $(document).on('click', '#sidebar a, .sidebar a, .components a, .navbar a, .app-header a', function () {
      const href = $(this).attr('href');
      if (href && href !== '#' && href !== 'javascript:void(0);' && !$(this).hasClass('dropdown-toggle')) {
          if (window.SITIA_Filtros) {
              window.SITIA_Filtros.limpiarTodo();
          }
      }
  });

  // Restauración inicial
  SITIA_Filtros.restaurar();

  // Observador para selects dinámicos (ej: Localidad → Sede)
  setInterval(function () {
      $('select[data-pending-val]').each(function () {
          const $select = $(this);
          const val = $select.attr('data-pending-val');
          if ($select.find(`option[value="${val}"]`).length > 0) {
              $select.val(val);
              $select.removeAttr('data-pending-val');
              try {
                  $.fn.dataTable.tables({ api: true }).each(function() {
                      try { this.ajax.reload(null, false); } catch(err) { this.draw(); }
                  });
              } catch(e) {}
          }
      });
  }, 100);

});
// Actualización dinámica de badges para switch HDD / SSD
window.actualizarBadgeDisco = function(checkbox, badgeId) {
    const badge = document.getElementById(badgeId);
    if (!badge) return;
    if (checkbox.checked) {
        badge.className = 'badge bg-success';
        badge.innerHTML = '<i class="fas fa-bolt me-1"></i>SSD';
    } else {
        badge.className = 'badge bg-secondary';
        badge.innerHTML = '<i class="fas fa-hdd me-1"></i>HDD';
    }
};

$(document).on('change', '.switch-disco', function () {
    const targetId = $(this).data('target-badge');
    if (targetId) {
        window.actualizarBadgeDisco(this, targetId);
    }
});
