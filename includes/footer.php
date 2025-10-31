<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
    </div> <!-- Cierre del container-fluid -->
    
    <!-- Footer -->
    <footer class="app-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        Sistema desarrollado por la Dirección de Informática, Telecomunicación y Administración de RUN - SeNAF - R.N. © 2025. Todos los derechos reservados.
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
                        zeroRecords: 'No se encontraron resultados',
                        emptyTable: 'Ningún dato disponible en la tabla',
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
                        zeroRecords: 'No se encontraron resultados',
                        emptyTable: 'Ningún dato disponible en la tabla',
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
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
    
    <!-- Toast container (Bootstrap 5) -->
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;" aria-live="polite" aria-atomic="true"></div>

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
</body>
</html> 