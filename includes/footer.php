    </div> <!-- Cierre del container-fluid -->
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
    <script src="<?php echo app_base_url(); ?>/public/js/main.js"></script>
    
    <script>
        // Toggle sidebar
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
            
            // Inicializar DataTables
            $('.datatable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
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
                }
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
</body>
</html> 