<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
    </div> <!-- Cierre del container-fluid -->
    
    <!-- Footer -->
    <footer class="app-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        Sistema desarrollado por la Dirección de Informática, Telecomunicaciones y Administración de RUN - SeNAF - R.N. © 2025. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    </div> <!-- Cierre del content -->

    <!-- Scripts -->
    <!-- jQuery (moved to header) -->
    <!-- Bootstrap JS -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo app_base_url(); ?>/public/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Idioma español global para cualquier DataTable (se ejecuta inmediatamente)
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, {
                autoWidth: false,
                width: '100%',
                language: {
                    decimal: ',',
                    thousands: '.',
                    processing: 'Procesando...',
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros totales)',
                    infoPostFix: '',
                    loadingRecords: 'Cargando...',
                    zeroRecords: '<div class="text-center py-3"><i class="fas fa-search fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No se encontraron resultados</p></div>',
                    emptyTable: '<div class="text-center py-3"><i class="fas fa-inbox fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No hay datos disponibles</p></div>',
                    paginate: {
                        first: 'Primero',
                        previous: 'Anterior',
                        next: 'Siguiente',
                        last: 'Último'
                    },
                    aria: {
                        sortAscending: ': activar para ordenar ascendente',
                        sortDescending: ': activar para ordenar descendente'
                    }
                }
            });
        }
    </script>
    <!-- Select2 (moved to header) -->
    <!-- Chart.js -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/chartjs/chart.umd.min.js"></script>
    
    <!-- SheetJS (XLSX) -->
    <script src="<?php echo app_base_url(); ?>/public/vendor/xlsx/xlsx.full.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo app_base_url(); ?>/public/js/main.js?v=<?php echo filemtime(__DIR__ . '/../public/js/main.js'); ?>"></script>
    
    <script>
        // Toggle sidebar
        $(document).ready(function() {
            // Sidebar toggle removido: menú siempre visible
            
            // Control de scrollbar correcto en DataTables y reubicación de controles al card-header
            $(document).on('init.dt', function(e, settings) {
                var api = new $.fn.dataTable.Api(settings);
                var $table = $(api.table().node());
                var $wrapper = $(api.table().container());
                
                // Remover table-responsive del contenedor externo para evitar scrollbar doble abajo
                $wrapper.closest('.table-responsive').removeClass('table-responsive');
                
                // Envolver la tabla en un contenedor responsive interno
                if (!$table.parent().hasClass('table-responsive-inner')) {
                    $table.wrap('<div class="table-responsive-inner"></div>');
                }

                // Mover controles al card-header (si existe)
                var $card = $wrapper.closest('.card');
                var $cardHeader = $card.find('.card-header');
                if ($cardHeader.length) {
                    var $length = $wrapper.find('.dataTables_length');
                    var $filter = $wrapper.find('.dataTables_filter');

                    // Añadirlos ordenadamente al inicio del card-header
                    // Añadirlos ordenadamente al final del card-header
                    if ($length.length && !$cardHeader.find('.dataTables_length').length) {
                        $cardHeader.append($length);
                    }
                    if ($filter.length && !$cardHeader.find('.dataTables_filter').length) {
                        $cardHeader.append($filter);
                    }

                    // Ocultar la primera fila por defecto de DataTables para evitar espacios vacíos en el body (solo si no contiene botones de exportación)
                    var $firstRow = $wrapper.find('.row:first-child');
                    if ($firstRow.length) {
                        if ($firstRow.find('.dt-buttons').length || $firstRow.find('.btn-group').length || $firstRow.find('button').length) {
                            // Si contiene botones, solo ocultamos el filtro o longitud internos para no duplicarlos
                            $firstRow.find('.dataTables_filter, .dataTables_length').addClass('d-none');
                        } else {
                            $firstRow.addClass('d-none');
                        }
                    }
                }
            });

            // Inicializar DataTables (por tabla para permitir orden inicial personalizado)
            $('.datatable').each(function() {
                var $t = $(this);
                if ($.fn.DataTable.isDataTable($t)) { return; }
                // Omitir tablas marcadas para server-side (se inicializan manualmente)
                if ($t.data('ssp') === 1 || String($t.data('ssp')) === '1') { return; }
                var defaultCol = parseInt($t.data('default-order-col')) || 0;
                var defaultDir = String($t.data('default-order-dir') || 'asc');
                var dt = $t.DataTable({
                    language: {
                        decimal: ',',
                        thousands: '.',
                        processing: 'Procesando...',
                        search: 'Buscar:',
                        lengthMenu: 'Mostrar _MENU_',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                        infoFiltered: '(filtrado de _MAX_ registros totales)',
                        infoPostFix: '',
                        loadingRecords: 'Cargando...',
                        zeroRecords: '<div class="text-center py-3"><i class="fas fa-search fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No se encontraron resultados</p></div>',
                        emptyTable: '<div class="text-center py-3"><i class="fas fa-inbox fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No hay datos disponibles</p></div>',
                        paginate: {
                            first: 'Primero',
                            previous: 'Anterior',
                            next: 'Siguiente',
                            last: 'Último'
                        },
                        aria: {
                            sortAscending: ': activar para ordenar ascendente',
                            sortDescending: ': activar para ordenar descendente'
                        }
                    },
                    pagingType: 'full_numbers',
                    order: [[defaultCol, defaultDir]],
                    pageLength: 25,
                    responsive: true,
                    columnDefs: [
                        {
                            targets: -1, // Última columna (acciones)
                            orderable: false,
                            searchable: false
                        }
                    ],
                    drawCallback: function() {
                        // Reinicializar tooltips después de cada redibujado
                        inicializarTooltips();
                        // Placeholder de búsqueda en español
                        try {
                            var wrapper = $t.closest('.dataTables_wrapper');
                            wrapper.find('.dataTables_filter input[type="search"]').attr('placeholder', 'Buscar...');
                        } catch(e) {}
                    }
                });
                // Ajuste inicial del placeholder
                setTimeout(function(){
                    try {
                        var wrapper = $t.closest('.dataTables_wrapper');
                        wrapper.find('.dataTables_filter input[type="search"]').attr('placeholder', 'Buscar...');
                    } catch(e) {}
                }, 0);
            });
            
            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                language: 'es'
            });

            // Placeholder sutil automático para el campo de búsqueda de Select2
            $(document).on('select2:open', function() {
                setTimeout(function() {
                    var $input = $('.select2-container--open .select2-search__field');
                    if ($input.length && !$input.attr('placeholder')) {
                        $input.attr('placeholder', 'Buscar modelo o escribir para filtrar...');
                    }
                }, 0);
            });
            
            // Auto-hide solo para alertas flash/temporales tras 5s (nunca modales, estados ni permanentes)
            setTimeout(function() {
                $('.alert.alert-autoclose, .alert-flash, .alert-dismissible:not(.alert-permanent)')
                    .not('.alert-permanent, .modal .alert, #alertSinStock, #alertRechazo')
                    .fadeOut('slow');
            }, 5000);
            
            // ============================================
            // BÚSQUEDA GLOBAL - @added v2.0
            // Rollback: Eliminar este bloque
            // ============================================
            (function() {
                var $input = $('#busquedaGlobalInput');
                var $resultados = $('#busquedaGlobalResultados');
                var debounceTimer = null;
                var BASE = window.APP_BASE_URL || '';
                
                if (!$input.length) return;
                
                // Íconos por tipo de resultado
                var iconos = {
                    insumo: 'fa-box',
                    pedido: 'fa-clipboard-list', 
                    remito: 'fa-file-alt',
                    sede: 'fa-building'
                };
                
                var colores = {
                    insumo: 'primary',
                    pedido: 'warning',
                    remito: 'success',
                    sede: 'info'
                };
                
                var urls = {
                    insumo: '/pages/insumos/ver.php?id=',
                    pedido: '/pages/pedidos/ver.php?id=',
                    remito: '/pages/asignaciones/listar.php?remito=',
                    sede: '/pages/admin/sede_detalle.php?id='
                };
                
                // Debounce de búsqueda
                $input.on('input', function() {
                    var q = $(this).val().trim();
                    clearTimeout(debounceTimer);
                    
                    if (q.length < 2) {
                        $resultados.addClass('d-none').empty();
                        return;
                    }
                    
                    debounceTimer = setTimeout(function() {
                        buscar(q);
                    }, 300);
                });
                
                function buscar(query) {
                    $.ajax({
                        url: BASE + '/ajax/busqueda_global.php',
                        data: { q: query },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                mostrarResultados(resp.data);
                            }
                        }
                    });
                }
                
                function mostrarResultados(data) {
                    var html = '';
                    var total = data.total || 0;
                    
                    if (total === 0) {
                        html = '<div class="p-3 text-center text-muted"><i class="fas fa-search me-2"></i>No se encontraron resultados</div>';
                    } else {
                        var resultados = data.resultados;
                        var grupos = {
                            'insumos': 'Insumos',
                            'pedidos': 'Pedidos',
                            'remitos': 'Remitos',
                            'sedes': 'Sedes'
                        };
                        
                        for (var grupo in grupos) {
                            if (resultados[grupo] && resultados[grupo].length > 0) {
                                html += '<div class="border-bottom px-3 py-2 bg-light"><small class="text-muted fw-bold text-uppercase">' + grupos[grupo] + '</small></div>';
                                resultados[grupo].forEach(function(item) {
                                    var tipo = item.tipo;
                                    if (tipo === 'insumo') {
                                        html += '<a href="javascript:void(0)" class="d-block px-3 py-2 text-decoration-none busqueda-item busqueda-item-insumo" data-id="' + item.id + '" style="transition: background 0.2s;">';
                                    } else if (tipo === 'remito') {
                                        var remVal = escapeHtml(item.numero_remito || item.titulo || item.id);
                                        html += '<a href="javascript:void(0)" class="d-block px-3 py-2 text-decoration-none busqueda-item busqueda-item-remito" data-remito="' + remVal + '" style="transition: background 0.2s;">';
                                    } else {
                                        var url = BASE + urls[tipo] + item.id;
                                        html += '<a href="' + url + '" class="d-block px-3 py-2 text-decoration-none busqueda-item" style="transition: background 0.2s;">';
                                    }
                                    html += '<i class="fas ' + iconos[tipo] + ' text-' + colores[tipo] + ' me-2"></i>';
                                    html += '<span class="fw-medium">' + escapeHtml(item.titulo) + '</span>';
                                    if (item.subtitulo) {
                                        html += '<small class="text-muted ms-2">' + escapeHtml(item.subtitulo) + '</small>';
                                    }
                                    html += '</a>';
                                });
                            }
                        }
                    }
                    
                    $resultados.html(html).removeClass('d-none');
                }
                
                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str).replace(/[&<>"']/g, function(m) {
                        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
                    });
                }
                
                // Hover para items
                $(document).on('mouseenter', '.busqueda-item', function() {
                    $(this).css('background-color', 'rgba(90, 147, 103, 0.08)');
                }).on('mouseleave', '.busqueda-item', function() {
                    $(this).css('background-color', '');
                });
                
                // Cerrar al hacer clic fuera o al hacer clic en un item
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#busquedaGlobalContainer').length) {
                        $resultados.addClass('d-none');
                    }
                });

                $(document).on('click', '.busqueda-item', function() {
                    $resultados.addClass('d-none');
                });

                // Clic en insumo -> abrir modal rápido
                $(document).on('click', '.busqueda-item-insumo', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (id && typeof window.abrirModalGlobalInsumo === 'function') {
                        window.abrirModalGlobalInsumo(id);
                    }
                });

                // Clic en remito/asignación -> abrir modal rápido
                $(document).on('click', '.busqueda-item-remito', function(e) {
                    e.preventDefault();
                    var remito = $(this).data('remito');
                    if (remito && typeof window.abrirModalGlobalRemito === 'function') {
                        window.abrirModalGlobalRemito(remito);
                    }
                });
                
                // Mostrar al enfocar
                $input.on('focus', function() {
                    if ($resultados.children().length > 0) {
                        $resultados.removeClass('d-none');
                    }
                });
                
                // Atajo Ctrl+K
                $(document).on('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        $input.focus().select();
                    }
                    // ESC para cerrar
                    if (e.key === 'Escape') {
                        $resultados.addClass('d-none');
                        $input.blur();
                    }
                });
            })();

            // Función Global para Ver Insumo en Modal desde el Buscador
            window.abrirModalGlobalInsumo = function(id) {
                var modalEl = document.getElementById('modalGlobalVerInsumo');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                var modalBody = document.getElementById('modalGlobalVerInsumoBody');
                var btnEditar = document.getElementById('btnGlobalEditarInsumo');

                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando detalles del insumo...</p>
                    </div>
                `;
                if (btnEditar) {
                    btnEditar.style.display = 'none';
                }
                modal.show();

                var BASE = window.APP_BASE_URL || '';
                fetch(BASE + '/pages/insumos/ver_ajax.php?id=' + id)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            modalBody.innerHTML = data.html;
                            if (btnEditar) {
                                btnEditar.href = BASE + '/pages/insumos/editar.php?id=' + id + '&return_to=' + encodeURIComponent(window.location.href);
                                btnEditar.style.display = 'inline-block';
                            }
                        } else {
                            modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + (data.error || 'Error al cargar detalles') + '</div>';
                        }
                    })
                    .catch(function() {
                        modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al conectar con el servidor</div>';
                    });
            };

            // Función Global para Ver Asignación en Modal desde el Buscador
            window.abrirModalGlobalRemito = function(remito) {
                var modalEl = document.getElementById('modalGlobalVerAsignacion');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                var cab = document.getElementById('verGlobalCabecera');
                var body = document.getElementById('verGlobalTablaBody');
                var alertBox = document.getElementById('verGlobalAlert');
                var btnIr = document.getElementById('btnGlobalIrAsignaciones');

                alertBox.style.display = 'none';
                cab.innerHTML = '';
                body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando datos de la asignación...</td></tr>';
                
                var BASE = window.APP_BASE_URL || '';
                if (btnIr) {
                    btnIr.href = BASE + '/pages/reportes/remito.php?remito=' + encodeURIComponent(remito);
                }
                modal.show();

                fetch(BASE + '/ajax/remito_detalle.php?remito=' + encodeURIComponent(remito))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.success) { throw new Error(data.error || 'Error al cargar remito'); }
                        var c = data.data ? data.data.cab : data.cab;
                        if (!c) { throw new Error('Estructura de datos inválida'); }
                        var items = data.data ? data.data.items : data.items;

                        if (btnIr && c.numero_remito) {
                            btnIr.href = BASE + '/pages/reportes/remito.php?remito=' + encodeURIComponent(c.numero_remito);
                        }

                        var formatFecha = function(str) {
                            if (!str) return '-';
                            var clean = str.trim().split(' ')[0];
                            var parts = clean.split('-');
                            if (parts.length === 3) {
                                return parts[2].padStart(2, '0') + '/' + parts[1].padStart(2, '0') + '/' + parts[0];
                            }
                            return str;
                        };

                        var fechaAsig = c.fecha_asignacion_formatted || formatFecha(c.fecha_asignacion);
                        var fechaDev = c.fecha_devolucion_formatted || formatFecha(c.fecha_devolucion);

                        var badgeEstado = 'bg-secondary';
                        if (c.estado === 'Activa') badgeEstado = 'bg-warning text-dark';
                        else if (c.estado === 'Devuelta') badgeEstado = 'bg-success';
                        else if (c.estado === 'Anulado' || c.estado === 'Anulada') badgeEstado = 'bg-danger';

                        cab.innerHTML = `
                            <div class="card border-0 mb-3 bg-light">
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong><i class="fas fa-file-alt me-1 text-primary"></i>Remito:</strong> <span class="badge bg-dark">${c.numero_remito || '-'}</span></p>
                                            <p class="mb-1"><strong><i class="fas fa-calendar-alt me-1 text-muted"></i>Fecha Asignación:</strong> ${fechaAsig}</p>
                                            <p class="mb-1"><strong>Estado:</strong> <span class="badge ${badgeEstado}">${c.estado || 'Desconocido'}</span></p>
                                            ${c.estado === 'Devuelta' && c.fecha_devolucion ? `<p class="mb-1"><strong>Fecha Devolución:</strong> ${fechaDev}</p>` : ''}
                                            ${c.nota_solicitud ? `<p class="mb-1"><strong>Nota Solicitud:</strong> <a href="${BASE}/uploads/${c.nota_solicitud}" target="_blank" class="btn btn-xs btn-outline-danger py-0 px-2"><i class="fas fa-file-pdf me-1"></i>Ver Nota</a></p>` : ''}
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong><i class="fas fa-user me-1 text-muted"></i>Persona:</strong> ${c.nombre_persona_asignada || ''} ${c.apellido_persona_asignada || ''}</p>
                                            <p class="mb-1"><strong><i class="fas fa-sitemap me-1 text-muted"></i>Área:</strong> ${c.nombre_area || '-'}</p>
                                            <p class="mb-1"><strong><i class="fas fa-building me-1 text-muted"></i>Sede:</strong> ${c.nombre_sede || '-'} (${c.nombre_localidad || '-'})</p>
                                        </div>
                                    </div>
                                    ${c.observaciones ? `<div class="mt-2 pt-2 border-top small text-muted"><strong>Observaciones:</strong> ${c.observaciones}</div>` : ''}
                                </div>
                            </div>
                        `;

                        var rows = [];
                        if (Array.isArray(items) && items.length > 0) {
                            items.forEach(function(it) {
                                var nombre = it.nombre_insumo || '-';
                                if (it.tipo_insumo === 'PC Escritorio' || it.tipo_insumo === 'PC Completa') {
                                    if (it.pc_sist_op) nombre = 'CPU/' + it.pc_sist_op;
                                } else if (it.nb_marca || it.nb_modelo) {
                                    nombre = [it.nb_marca, it.nb_modelo].filter(Boolean).join(' - ');
                                } else if (it.imp_marca || it.imp_modelo) {
                                    nombre = [it.imp_marca, it.imp_modelo].filter(Boolean).join(' - ');
                                } else if (it.mon_marca || it.mon_modelo) {
                                    nombre = [it.mon_marca, it.mon_modelo].filter(Boolean).join(' - ');
                                } else if (it.esc_marca || it.esc_modelo) {
                                    nombre = [it.esc_marca, it.esc_modelo].filter(Boolean).join(' - ');
                                }

                                var asignados = parseInt(it.cantidad || '0', 10);
                                var devueltos = parseInt(it.cantidad_devuelta || '0', 10);
                                var pendientes = Math.max(0, asignados - devueltos);

                                var specs = [];
                                if (it.tipo_insumo === 'PC Escritorio' || it.tipo_insumo === 'PC Completa') {
                                    if (it.pc_procesador) specs.push('CPU: ' + it.pc_procesador);
                                    if (it.pc_ram) specs.push('RAM: ' + it.pc_ram + ' GB');
                                    if (it.pc_disco) {
                                        var d = it.pc_disco_sec ? (it.pc_disco + ' GB + ' + it.pc_disco_sec + ' GB') : (it.pc_disco + ' GB');
                                        specs.push('Disco: ' + d);
                                    }
                                } else if (it.tipo_insumo === 'Notebook') {
                                    if (it.nb_procesador) specs.push('CPU: ' + it.nb_procesador);
                                    if (it.nb_ram) specs.push('RAM: ' + it.nb_ram + ' GB');
                                    if (it.nb_disco) specs.push('Disco: ' + it.nb_disco + ' GB');
                                } else if (it.tipo_insumo === 'Monitor' && it.mon_pulgadas) {
                                    specs.push(it.mon_pulgadas + '"');
                                }

                                var extraInfo = '';
                                if (it.numero_serie) extraInfo += '<small class="text-muted d-block">S/N: ' + it.numero_serie + '</small>';
                                if (it.id_fisico) extraInfo += '<small class="text-muted d-block">ID: ' + String(it.id_fisico).replace(/[- ]/g, '') + '</small>';
                                if (specs.length > 0) extraInfo += '<small class="text-secondary d-block"><i class="fas fa-microchip me-1"></i>' + specs.join(' | ') + '</small>';

                                rows.push(`
                                    <tr>
                                        <td>
                                            <strong>${nombre}</strong>
                                            ${extraInfo}
                                        </td>
                                        <td><span class="badge ${it.tipo_insumo === 'Varios' ? 'bg-info' : 'bg-primary'}">${it.tipo_insumo}</span></td>
                                        <td><span class="badge bg-dark">${asignados}</span></td>
                                        <td><span class="badge bg-success">${devueltos}</span></td>
                                        <td><span class="badge ${pendientes > 0 ? 'bg-warning text-dark' : 'bg-secondary'}">${pendientes}</span></td>
                                    </tr>
                                `);
                            });
                            body.innerHTML = rows.join('');
                        } else {
                            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Sin ítems en esta asignación</td></tr>';
                        }
                    })
                    .catch(function(err) {
                        alertBox.textContent = err.message || 'Error al cargar asignación';
                        alertBox.style.display = 'block';
                        body.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error al cargar datos</td></tr>';
                    });
            };
            // ============================================
        });
    </script>
    
    <!-- Toast container (Bootstrap 5) -->
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;" aria-live="polite" aria-atomic="true"></div>

    <!-- Modal de Confirmación Global SITIA -->
    <div class="modal fade" id="modalConfirmacionSITIA" tabindex="-1" aria-labelledby="modalConfirmacionSITIALabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalConfirmacionSITIALabel">
                        <i class="fas fa-question-circle text-primary me-2" id="modalConfirmacionIcono"></i>
                        <span id="modalConfirmacionTitulo">Confirmar</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <p class="fs-5 mb-0" id="modalConfirmacionMensaje"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" id="btnConfirmacionCancelar">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" id="btnConfirmacionAceptar">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Global: Ver Detalles de Insumo (Búsqueda Global) -->
    <div class="modal fade" id="modalGlobalVerInsumo" tabindex="-1" aria-labelledby="modalGlobalVerInsumoLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalGlobalVerInsumoLabel">
                        <i class="fas fa-box me-2"></i>Detalles del Insumo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalGlobalVerInsumoBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando detalles del insumo...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="#" class="btn btn-warning" id="btnGlobalEditarInsumo" style="display: none;">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Global: Ver Detalles de Asignación / Remito (Búsqueda Global) -->
    <div class="modal fade" id="modalGlobalVerAsignacion" tabindex="-1" aria-labelledby="modalGlobalVerAsignacionLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalGlobalVerAsignacionLabel">
                        <i class="fas fa-file-alt me-2"></i>Detalle de Asignación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="verGlobalAlert" class="alert alert-danger" style="display:none;"></div>
                    <div id="verGlobalCabecera" class="mb-3"></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Tipo</th>
                                    <th>Asignados</th>
                                    <th>Devueltos</th>
                                    <th>Pendientes</th>
                                </tr>
                            </thead>
                            <tbody id="verGlobalTablaBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <a href="#" class="btn btn-outline-primary btn-sm" id="btnGlobalIrAsignaciones" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Ver Remito Completo
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Global de Visor de Documentos y Archivos (PDF e Imágenes) -->
    <div class="modal fade" id="modalVisorPDF" tabindex="-1" aria-labelledby="modalVisorPDFLabel" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 94vw; height: 92vh; margin: 1.5rem auto;">
            <div class="modal-content h-100 shadow-lg border-0">
                <div class="modal-header py-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i id="visorPDFIcono" class="fas fa-file-pdf text-danger fs-5"></i>
                        <h5 class="modal-title fs-6 fw-bold text-truncate mb-0" id="modalVisorPDFLabel">Visor de Documentos</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" id="btnImprimirVisorPDF" title="Imprimir archivo">
                            <i class="fas fa-print me-1"></i><span class="d-none d-sm-inline">Imprimir</span>
                        </button>
                        <a href="#" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnDescargarVisorPDF" download title="Descargar archivo">
                            <i class="fas fa-download me-1"></i><span class="d-none d-sm-inline">Descargar</span>
                        </a>
                        <a href="#" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnNuevaPestanaVisorPDF" title="Abrir en pestaña nueva">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>
                <div class="modal-body p-0 position-relative d-flex align-items-center justify-content-center" style="background-color: #525659; min-height: 400px; overflow: hidden;">
                    <div id="visorPDFLoading" class="position-absolute text-white text-center" style="z-index: 5;">
                        <i class="fas fa-circle-notch fa-spin fa-2x mb-2 text-primary"></i>
                        <div class="small fw-semibold">Cargando archivo...</div>
                    </div>
                    <!-- Contenedor para imágenes -->
                    <div id="visorImgContainer" class="w-100 h-100 p-3 d-none align-items-center justify-content-center overflow-auto position-relative" style="z-index: 10;">
                        <img id="imgVisor" src="" alt="Previsualización de imagen" class="img-fluid rounded shadow-sm" style="max-height: 100%; max-width: 100%; object-fit: contain; cursor: zoom-in;" title="Clic para ampliar/reducir">
                    </div>
                    <!-- Contenedor para PDFs -->
                    <iframe id="iframeVisorPDF" src="about:blank" class="w-100 h-100 border-0" style="min-height: 100%; position: relative; z-index: 10;" title="Visor de Documentos"></iframe>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- Cierre del wrapper -->

    <script>
    (function(){
      var lastFocus = null;
      // Restaurar foco al cerrar modales y enfocar el primero al abrir
      document.addEventListener('show.bs.modal', function(ev){
        try { lastFocus = document.activeElement; } catch(e) { lastFocus = null; }
        var modal = ev.target;
        setTimeout(function(){
          try {
            var first = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (first) { first.focus(); }
          } catch(e) {}
        }, 0);
      });
      document.addEventListener('hidden.bs.modal', function(){
        try { if (lastFocus && typeof lastFocus.focus === 'function') { lastFocus.focus(); } } catch(e) {}
      });
      
      // Limpiar tooltips antes de navegación
      window.addEventListener('beforeunload', function() {
        try {
          document.querySelectorAll('.tooltip').forEach(function(el) {
            el.remove();
          });
        } catch(e) {}
      });
    })();
    </script>
    
    <!-- Toggle Modo Oscuro - @added v2.0 -->
    <script>
    (function() {
        var btn = document.getElementById('btnToggleTema');
        var icono = document.getElementById('iconoTema');
        var texto = document.getElementById('textoTema');
        
        if (!btn) return;
        
        // Actualizar UI según tema actual
        function actualizarUI() {
            var tema = document.documentElement.getAttribute('data-theme') || 'light';
            if (tema === 'dark') {
                icono.className = 'fas fa-sun me-2';
                texto.textContent = 'Modo Claro';
            } else {
                icono.className = 'fas fa-moon me-2';
                texto.textContent = 'Modo Oscuro';
            }
        }
        
        // Inicializar
        actualizarUI();
        
        // Toggle al hacer clic
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var actual = document.documentElement.getAttribute('data-theme') || 'light';
            var nuevo = actual === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nuevo);
            localStorage.setItem('sitia_tema', nuevo);
            actualizarUI();
        });
    })();
    </script>

<?php if (function_exists('estaAutenticado') && estaAutenticado()): ?>
    <!-- Modal Sesión Expirada / Advertencia -->
    <div class="modal fade" id="modalSesionExpirada" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalSesionExpiradaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white" id="modalSesionHeader">
                    <h5 class="modal-title fw-bold" id="modalSesionExpiradaLabel">
                        <i class="fas fa-exclamation-triangle me-2" id="modalSesionIcono"></i>
                        <span id="modalSesionTitulo">Sesión Expirada</span>
                    </h5>
                </div>
                <div class="modal-body py-4 text-center">
                    <p class="fs-5 mb-0" id="modalSesionMensaje">Su sesión ha expirado por inactividad. Por favor, vuelva a iniciar sesión para continuar.</p>
                </div>
                <div class="modal-footer justify-content-center" id="modalSesionFooter">
                    <a href="<?php echo app_base_url(); ?>/login.php" class="btn btn-danger px-4 shadow-sm">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de control de sesión -->
    <script>
    (function() {
        if (typeof window.APP_BASE_URL === 'undefined') {
            window.APP_BASE_URL = '<?php echo app_base_url(); ?>';
        }
        
        const TIMEOUT_MS = 1800000; // 30 minutos
        const WARNING_MS = 120000;  // 2 minutos de advertencia
        const PING_INTERVAL_MS = 300000; // 5 minutos entre pings
        const STORAGE_KEY = 'sitia_last_activity';
        const PING_KEY = 'sitia_last_ping';
        
        let modalMostrado = false;
        let modoModal = ''; // 'advertencia' o 'expirada'
        let lastActivityWrite = 0;
        let bsModal = null;
        
        function getModal() {
            if (!bsModal) {
                const el = document.getElementById('modalSesionExpirada');
                if (el) {
                    bsModal = new bootstrap.Modal(el);
                }
            }
            return bsModal;
        }

        function registrarActividad() {
            // Si el modal ya está mostrado (sea de advertencia o expirada), ignoramos la actividad de fondo
            if (modalMostrado) return;
            
            const ahora = Date.now();
            if (ahora - lastActivityWrite > 5000) { // Max una escritura en localStorage cada 5 segundos
                localStorage.setItem(STORAGE_KEY, ahora);
                lastActivityWrite = ahora;
            }
        }

        const eventos = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
        eventos.forEach(evt => {
            document.addEventListener(evt, registrarActividad, { passive: true });
        });

        // Registrar actividad al completar peticiones AJAX (excluyendo consultas en segundo plano)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings && (
                (settings.url && (
                    settings.url.indexOf('session_ping.php') !== -1 ||
                    settings.url.indexOf('contadores_dashboard.php') !== -1 ||
                    settings.url.indexOf('notificaciones_check.php') !== -1 ||
                    settings.url.indexOf('notificaciones_marcar.php') !== -1
                )) ||
                settings.skipActivity === true
            )) {
                return;
            }
            registrarActividad();
        });

        // Interceptar errores AJAX por sesión expirada (HTTP 401)
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 401) {
                mostrarModalExpirado();
            }
        });

        function realizarPing() {
            const ahora = Date.now();
            localStorage.setItem(PING_KEY, ahora);
            $.ajax({
                url: window.APP_BASE_URL + '/ajax/session_ping.php',
                method: 'GET',
                dataType: 'json',
                skipActivity: true
            }).fail(function(xhr) {
                if (xhr.status === 401) {
                    mostrarModalExpirado();
                }
            });
        }

        function mantenerSesion() {
            const ahora = Date.now();
            localStorage.setItem(STORAGE_KEY, ahora);
            realizarPing();
            cerrarModal();
        }

        function cerrarModal() {
            const m = getModal();
            if (m) {
                m.hide();
            }
            modalMostrado = false;
            modoModal = '';
        }

        function mostrarAdvertenciaExpiracion(segundos) {
            if (modoModal === 'expirada') return;
            
            modalMostrado = true;
            modoModal = 'advertencia';
            
            // Personalizar modal para Advertencia
            const header = document.getElementById('modalSesionHeader');
            const titulo = document.getElementById('modalSesionTitulo');
            const mensaje = document.getElementById('modalSesionMensaje');
            const footer = document.getElementById('modalSesionFooter');
            const icono = document.getElementById('modalSesionIcono');
            
            if (header) {
                header.className = 'modal-header bg-warning-subtle text-warning-emphasis border-bottom border-warning-subtle';
            }
            if (icono) {
                icono.className = 'fas fa-exclamation-triangle text-warning me-2';
            }
            if (titulo) {
                titulo.textContent = 'Advertencia de Inactividad';
            }
            if (mensaje) {
                mensaje.innerHTML = 'Su sesión está por expirar en <strong id="session-countdown">' + segundos + '</strong> segundos debido a inactividad. ¿Desea continuar conectado?';
            }
            
            if (footer && !document.getElementById('btnMantenerSesion')) {
                footer.innerHTML = `
                    <button type="button" class="btn btn-warning px-4 shadow-sm" id="btnMantenerSesion">
                        <i class="fas fa-sync-alt me-2"></i>Mantener Conectado
                    </button>
                    <a href="${window.APP_BASE_URL}/logout.php" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                `;
                
                // Vincular evento al botón dinámico
                document.getElementById('btnMantenerSesion').addEventListener('click', mantenerSesion);
            } else if (document.getElementById('session-countdown')) {
                document.getElementById('session-countdown').textContent = segundos;
            }
            
            const m = getModal();
            if (m) {
                m.show();
            }
        }

        function mostrarModalExpirado() {
            if (modoModal === 'expirada') return;
            
            modalMostrado = true;
            modoModal = 'expirada';
            
            // Remover listeners de actividad
            eventos.forEach(evt => {
                document.removeEventListener(evt, registrarActividad);
            });

            // Personalizar modal para Expiración
            const header = document.getElementById('modalSesionHeader');
            const titulo = document.getElementById('modalSesionTitulo');
            const mensaje = document.getElementById('modalSesionMensaje');
            const footer = document.getElementById('modalSesionFooter');
            const icono = document.getElementById('modalSesionIcono');
            
            if (header) {
                header.className = 'modal-header bg-danger text-white';
            }
            if (icono) {
                icono.className = 'fas fa-exclamation-circle me-2';
            }
            if (titulo) {
                titulo.textContent = 'Sesión Expirada';
            }
            if (mensaje) {
                mensaje.textContent = 'Su sesión ha expirado por inactividad. Por favor, vuelva a iniciar sesión para continuar.';
            }
            if (footer) {
                footer.innerHTML = `
                    <a href="${window.APP_BASE_URL}/login.php" class="btn btn-danger px-4 shadow-sm">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </a>
                `;
            }
            
            const m = getModal();
            if (m) {
                m.show();
            }
        }

        // Inicializar tiempos en localStorage si no existen, si ya expiraron o son de una sesión previa
        const ahoraInicial = Date.now();
        const serverLastAccess = <?php echo ($_SESSION['ultimo_acceso'] ?? time()); ?> * 1000;
        
        const storedActivity = parseInt(localStorage.getItem(STORAGE_KEY) || 0, 10);
        if (!storedActivity || (ahoraInicial - storedActivity >= TIMEOUT_MS) || (storedActivity < serverLastAccess)) {
            localStorage.setItem(STORAGE_KEY, ahoraInicial);
        }
        
        const storedPing = parseInt(localStorage.getItem(PING_KEY) || 0, 10);
        if (!storedPing || (ahoraInicial - storedPing >= TIMEOUT_MS) || (storedPing < serverLastAccess)) {
            localStorage.setItem(PING_KEY, ahoraInicial);
        }

        // Verificar inactividad periódicamente (cada segundo para exactitud en la cuenta regresiva)
        setInterval(function() {
            if (modalMostrado && modoModal === 'expirada') return;
            
            const ahora = Date.now();
            const lastActivity = parseInt(localStorage.getItem(STORAGE_KEY) || ahora, 10);
            const idleTime = ahora - lastActivity;
            
            if (idleTime >= TIMEOUT_MS) {
                mostrarModalExpirado();
            } else if (idleTime >= (TIMEOUT_MS - WARNING_MS)) {
                const segundosRestantes = Math.ceil((TIMEOUT_MS - idleTime) / 1000);
                mostrarAdvertenciaExpiracion(segundosRestantes);
            } else {
                // Si el usuario reactivó la sesión desde otra pestaña, cerramos la advertencia aquí
                if (modalMostrado && modoModal === 'advertencia') {
                    cerrarModal();
                }
                
                // Realizar ping de keep-alive si hubo actividad real desde el último ping
                const lastPing = parseInt(localStorage.getItem(PING_KEY) || 0, 10);
                if (ahora - lastPing >= PING_INTERVAL_MS) {
                    if (lastActivity > lastPing) {
                        realizarPing();
                    }
                }
            }
        }, 1000);
    })();

    // ============================================
    // SISTEMA DE NOTIFICACIONES EN TIEMPO REAL
    // ============================================
    (function() {
        const NOTIF_CHECK_INTERVAL = 45000; // 45 segundos

        function cargarNotificaciones() {
            $.ajax({
                url: window.APP_BASE_URL + '/ajax/notificaciones_check.php',
                method: 'GET',
                dataType: 'json',
                skipActivity: true,
                success: function(resp) {
                    if (!resp.success) return;

                    // 1. Resumen de Bienvenida al iniciar sesión (1 vez por sesión)
                    if (resp.data.resumen_login) {
                        const r = resp.data.resumen_login;
                        if (r.total > 0) {
                            let msgText = '<strong>¡Hola ' + r.usuario_nombre + '!</strong><br>' +
                                          'Tienes <strong>' + r.total + '</strong> tarea(s) no completada(s) pendientes (' + r.propias + ' propias, ' + r.colaborativas + ' colaborativas).' +
                                          '<div class="mt-2 pt-1 border-top border-light d-flex flex-wrap gap-1">' +
                                          '<a href="' + window.APP_BASE_URL + '/pages/pedidos/listar.php?tab=mis_pedidos" class="btn btn-sm btn-light text-primary fw-bold shadow-sm me-1"><i class="fas fa-user-clock me-1"></i>Mis Tareas</a>' +
                                          '<a href="' + window.APP_BASE_URL + '/pages/pedidos/listar.php?tab=tareas_internas" class="btn btn-sm btn-outline-light fw-bold shadow-sm"><i class="fas fa-users me-1"></i>Tareas Colaborativas</a>' +
                                          '</div>';
                            if (typeof showToast === 'function') {
                                showToast(msgText, 'info', 'toast-notificacion-large');
                            }
                        }
                    }

                    // 2. Disparar Toasts para notificaciones NO mostradas
                    if (resp.data.unshown && resp.data.unshown.length > 0) {
                        const idsMostrados = [];
                        resp.data.unshown.forEach(function(n) {
                            if (typeof showToast === 'function') {
                                showToast('<strong>' + n.titulo + '</strong><br>' + n.mensaje, 'info', 'toast-notificacion-large');
                            }
                            idsMostrados.push(n.id_notificacion);
                        });

                        // Marcar como mostradas en el servidor
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        $.ajax({
                            url: window.APP_BASE_URL + '/ajax/notificaciones_marcar.php',
                            method: 'POST',
                            skipActivity: true,
                            data: {
                                _csrf: csrfToken,
                                accion: 'marcar_mostradas',
                                ids: idsMostrados
                            }
                        });
                    }

                    // 3. Actualizar Badge de la Campana (No leídas)
                    const count = resp.data.unread_count || 0;
                    const $badge = $('#notifBadgeCounter');
                    const $btnNotif = $('#dropdownNotificaciones');
                    if (count > 0) {
                        $badge.text(count > 99 ? '99+' : count).removeClass('d-none');
                        $btnNotif.addClass('has-unread');
                    } else {
                        $badge.addClass('d-none');
                        $btnNotif.removeClass('has-unread');
                    }

                    // 4. Renderizar lista en el Dropdown de la Campana (Aumentado 50% en tamaño)
                    const $container = $('#notifListContainer');
                    if (resp.data.bell_items && resp.data.bell_items.length > 0) {
                        let html = '';
                        resp.data.bell_items.forEach(function(n) {
                            const bgStyle = n.leida == 0 ? 'bg-light fw-bold' : '';
                            const targetUrl = n.url || '#';
                            html += `
                                <a href="${targetUrl}" onclick="marcarNotificacionLeida(${n.id_notificacion}); return true;" 
                                   class="list-group-item list-group-item-action p-3 ${bgStyle} border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="text-primary fw-bold text-truncate me-2" style="max-width: 310px; font-size: 0.96rem;">${n.titulo}</span>
                                        <small class="text-muted" style="font-size: 0.82rem;">${n.fecha}</small>
                                    </div>
                                    <p class="mb-0 text-secondary text-truncate" style="font-size: 0.9rem;">${n.mensaje}</p>
                                </a>
                            `;
                        });
                        $container.html(html);
                    } else {
                        $container.html('<div class="text-center p-4 text-muted fs-6"><i class="fas fa-inbox me-2"></i>Sin notificaciones</div>');
                    }
                }
            });
        }

        // Funciones globales para interacción
        window.marcarNotificacionLeida = function(id) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            $.ajax({
                url: window.APP_BASE_URL + '/ajax/notificaciones_marcar.php',
                method: 'POST',
                data: {
                    _csrf: csrfToken,
                    accion: 'marcar_leida',
                    id: id
                }
            });
        };

        window.marcarTodasNotificacionesLeidas = function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            $.ajax({
                url: window.APP_BASE_URL + '/ajax/notificaciones_marcar.php',
                method: 'POST',
                data: {
                    _csrf: csrfToken,
                    accion: 'marcar_todas_leidas'
                },
                success: function() {
                    $('#notifBadgeCounter').addClass('d-none');
                    $('#dropdownNotificaciones').removeClass('has-unread');
                    $('#notifListContainer .list-group-item').removeClass('bg-light fw-bold');
                    if (typeof showToast === 'function') {
                        showToast('Todas las notificaciones fueron marcadas como leídas', 'success');
                    }
                }
            });
        };

        window.limpiarBandejaNotificaciones = function() {
            const realizarLimpieza = function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                $.ajax({
                    url: window.APP_BASE_URL + '/ajax/notificaciones_marcar.php',
                    method: 'POST',
                    data: {
                        _csrf: csrfToken,
                        accion: 'limpiar_todas'
                    },
                    dataType: 'json',
                    success: function(resp) {
                        $('#notifBadgeCounter').addClass('d-none').text('0');
                        $('#dropdownNotificaciones').removeClass('has-unread');
                        $('#notifListContainer').html('<div class="text-center p-4 text-muted fs-6"><i class="fas fa-inbox me-2"></i>Sin notificaciones</div>');
                        if (typeof showToast === 'function') {
                            showToast('Bandeja de notificaciones limpiada correctamente', 'success');
                        }
                    },
                    error: function() {
                        if (typeof showToast === 'function') {
                            showToast('Error al conectar con el servidor', 'error');
                        }
                    }
                });
            };

            if (typeof showConfirm === 'function') {
                showConfirm({
                    titulo: 'Limpiar Notificaciones',
                    mensaje: '¿Está seguro que desea vaciar la bandeja de notificaciones?',
                    icono: 'fa-trash-alt text-danger',
                    claseBoton: 'btn-danger',
                    textoAceptar: 'Limpiar todo',
                    onConfirm: realizarLimpieza
                });
            } else if (confirm('¿Está seguro que desea vaciar la bandeja de notificaciones?')) {
                realizarLimpieza();
            }
        };

        // Iniciar polling al cargar la página y repetir cada 45s
        $(document).ready(function() {
            cargarNotificaciones();
            setInterval(cargarNotificaciones, NOTIF_CHECK_INTERVAL);
        });
    })();
    </script>
<?php endif; ?>
</body>
</html> 