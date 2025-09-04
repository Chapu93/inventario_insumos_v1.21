    </div> <!-- Cierre del container-fluid -->
    </div> <!-- Cierre del content -->

    <!-- Scripts -->
    <!-- jQuery (moved to header) -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-QF1r5ZZj3G9PZl5G6cOeZrY8D6i9t6g7f5k4h3j2k1l0p9o8n7m6l5k4j3h2g1f0" crossorigin="anonymous"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" integrity="sha384-Fk9XbZ6v8yG5jQ3kLm9nO3sU8h7I6j5K4l3M2n1B0a9Z8Y7X6W5V4U3T2S1R0Q9P" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js" integrity="sha384-Q0w9e8r7t6y5u4i3o2p1n0m9l8k7j6h5g4f3e2d1c0b9a8Z7Y6X5W4V3U2T1S0R9" crossorigin="anonymous"></script>
    <!-- Select2 (moved to header) -->
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" integrity="sha384-tD6QkE8l7d0pQ7s+ZJ9a8C2k5L7m9n1o2p3q4r5s6t7u8v9w0x1y2z3A4B5C6D7E" crossorigin="anonymous"></script>
    
    <!-- SheetJS (XLSX) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js" integrity="sha384-H1I2J3K4L5M6N7O8P9Q0R1S2T3U4V5W6X7Y8Z9A0B1C2D3E4F5G6H7I8J9K0L1M2" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo app_base_url(); ?>/public/js/main.js"></script>
    
    <script>
        // Toggle sidebar
        $(document).ready(function() {
            // Sidebar toggle removido: menú siempre visible
            
            // Inicializar DataTables
            $('.datatable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'asc']],
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