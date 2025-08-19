<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Button Toggle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-seleccionar {
            min-width: 100px;
        }
        .fila-insumo.highlight {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Test Button Toggle Functionality</h2>
        
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fila-insumo">
                    <td>Test Item 1</td>
                    <td>Varios</td>
                    <td>
                        <input type="hidden" name="id_insumo[]" value="1" class="hidden-insumo-input" data-tipo="Varios" data-max="5" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" data-insumo-id="1" onclick="toggleSeleccionInsumo(1)">
                            <i class="fas fa-plus"></i> Seleccionar
                        </button>
                        <div class="cantidad-input" style="display: none;">
                            <input type="number" class="form-control form-control-sm" name="cantidad_varios[1]" min="1" max="5" value="1" style="width: 80px;">
                        </div>
                    </td>
                </tr>
                <tr class="fila-insumo">
                    <td>Test Item 2</td>
                    <td>PC Completa</td>
                    <td>
                        <input type="hidden" name="id_insumo[]" value="2" class="hidden-insumo-input" data-tipo="PC Completa" data-max="1" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" data-insumo-id="2" onclick="toggleSeleccionInsumo(2)">
                            <i class="fas fa-plus"></i> Seleccionar
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="mt-3">
            <button type="button" class="btn btn-outline-danger" onclick="deseleccionarTodos()">
                <i class="fas fa-times me-1"></i>Deseleccionar Todo
            </button>
        </div>
        
        <div id="contador" class="alert alert-info mt-3"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para alternar la selección de un insumo
        function toggleSeleccionInsumo(idInsumo) {
            const $hiddenInput = $(`.hidden-insumo-input[value="${idInsumo}"]`);
            const $button = $(`.btn-seleccionar[data-insumo-id="${idInsumo}"]`);
            const $fila = $button.closest('tr');
            
            if ($hiddenInput.is(':visible')) {
                // Deseleccionar
                $hiddenInput.hide();
                $button.removeClass('btn-primary').addClass('btn-outline-primary');
                $button.html('<i class="fas fa-plus"></i> Seleccionar');
                $fila.removeClass('highlight');
                
                // Ocultar input de cantidad si existe
                $fila.find('.cantidad-input').hide();
            } else {
                // Seleccionar
                $hiddenInput.show();
                $button.removeClass('btn-outline-primary').addClass('btn-primary');
                $button.html('<i class="fas fa-minus"></i> Deseleccionar');
                
                // Resaltar la fila brevemente
                $fila.addClass('highlight');
                setTimeout(() => $fila.removeClass('highlight'), 1000);
                
                // Mostrar input de cantidad si es tipo "Varios"
                const tipo = $hiddenInput.data('tipo');
                const max = parseInt($hiddenInput.data('max') || 1);
                if (tipo === 'Varios' && max > 1) {
                    $fila.find('.cantidad-input').show();
                }
            }
            
            actualizarContador();
        }
        
        // Función para actualizar contador
        function actualizarContador() {
            const total = $('.hidden-insumo-input').length;
            const seleccionados = $('.hidden-insumo-input:visible').length;
            
            let texto = '';
            if (seleccionados > 0) {
                texto = `${seleccionados} de ${total} items seleccionados`;
            } else {
                texto = `${total} items disponibles`;
            }
            
            $('#contador').text(texto);
        }
        
        // Función para deseleccionar todos
        function deseleccionarTodos() {
            $('.hidden-insumo-input').hide();
            $('.btn-seleccionar').removeClass('btn-primary').addClass('btn-outline-primary');
            $('.btn-seleccionar').html('<i class="fas fa-plus"></i> Seleccionar');
            $('.cantidad-input').hide();
            $('.fila-insumo').removeClass('highlight');
            actualizarContador();
        }
        
        // Inicializar contador
        $(document).ready(function() {
            actualizarContador();
        });
    </script>
</body>
</html>
