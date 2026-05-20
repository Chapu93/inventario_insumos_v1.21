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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 (moved to header) -->
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SheetJS (XLSX) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo app_base_url(); ?>/public/js/main.js?v=<?php echo filemtime(__DIR__ . '/../public/js/main.js'); ?>"></script>
    
    <script>
        // Toggle sidebar
        $(document).ready(function() {
            // No manipular directamente el DOM de DataTables (evita romper eventos)
            // Idioma español global para cualquier DataTable
            if ($.fn && $.fn.dataTable) {
                $.extend(true, $.fn.dataTable.defaults, {
                    language: {
                        decimal: ',',
                        thousands: '.',
                        processing: 'Procesando...',
                        search: 'Buscar:',
                        lengthMenu: 'Mostrar _MENU_ registros',
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
            // Sidebar toggle removido: menú siempre visible
            
            // Control de scrollbar correcto en DataTables (evita scrollbar doble y mantiene responsividad)
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
                        lengthMenu: 'Mostrar _MENU_ registros',
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
            
            // Auto-hide alerts after 5 seconds (except permanent alerts like selected items list)
            setTimeout(function() {
                $('.alert:not(.alert-permanent)').fadeOut('slow');
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
                                    var url = BASE + urls[tipo] + item.id;
                                    html += '<a href="' + url + '" class="d-block px-3 py-2 text-decoration-none text-dark busqueda-item" style="transition: background 0.2s;">';
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
                
                // Cerrar al hacer clic fuera
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#busquedaGlobalContainer').length) {
                        $resultados.addClass('d-none');
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
            // ============================================
        });
    </script>
    
    <!-- Toast container (Bootstrap 5) -->
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;" aria-live="polite" aria-atomic="true"></div>

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
    <!-- Modal Sesión Expirada -->
    <div class="modal fade" id="modalSesionExpirada" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalSesionExpiradaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="modalSesionExpiradaLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Sesión Expirada
                    </h5>
                </div>
                <div class="modal-body py-4 text-center">
                    <p class="fs-5 mb-0">Su sesión ha expirado por inactividad. Por favor, vuelva a iniciar sesión para continuar.</p>
                </div>
                <div class="modal-footer justify-content-center">
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
        // Expiración en 30 minutos (1800 segundos = 1800000 milisegundos)
        const TIMEOUT_MS = 1800000;
        const CHECK_INTERVAL_MS = 10000; // Cada 10 segundos
        const STORAGE_KEY = 'sitia_last_activity';
        let modalMostrado = false;

        // Inicializar o registrar última actividad
        function registrarActividad() {
            if (modalMostrado) return;
            localStorage.setItem(STORAGE_KEY, Date.now());
        }

        // Registrar actividad en eventos comunes de interacción
        const eventos = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
        eventos.forEach(evt => {
            document.addEventListener(evt, registrarActividad, { passive: true });
        });

        // Registrar actividad al completar peticiones AJAX
        $(document).ajaxComplete(function() {
            registrarActividad();
        });

        // Interceptar errores AJAX por sesión expirada (HTTP 401)
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 401) {
                mostrarModalExpirado();
            }
        });

        function mostrarModalExpirado() {
            if (modalMostrado) return;
            modalMostrado = true;
            
            // Remover event listeners
            eventos.forEach(evt => {
                document.removeEventListener(evt, registrarActividad);
            });

            // Mostrar modal
            const myModal = new bootstrap.Modal(document.getElementById('modalSesionExpirada'));
            myModal.show();
        }

        // Registrar actividad inicial al cargar la página
        registrarActividad();

        // Verificar inactividad periódicamente
        setInterval(function() {
            if (modalMostrado) return;
            
            const lastActivity = parseInt(localStorage.getItem(STORAGE_KEY) || Date.now(), 10);
            const idleTime = Date.now() - lastActivity;
            
            if (idleTime >= TIMEOUT_MS) {
                mostrarModalExpirado();
            }
        }, CHECK_INTERVAL_MS);
    })();
    </script>
<?php endif; ?>
</body>
</html> 