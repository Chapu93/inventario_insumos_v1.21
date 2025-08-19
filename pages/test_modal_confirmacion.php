<?php
require_once '../includes/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modal Confirmación - Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-test-tube me-2"></i>Test Modal de Confirmación</h4>
                    </div>
                    <div class="card-body">
                        <p>Esta página prueba el modal de confirmación implementado en <code>nueva.php</code>.</p>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Funcionalidades a probar:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Validación del formulario antes de mostrar el modal</li>
                                <li>Recopilación de datos del formulario</li>
                                <li>Generación de tabla de insumos seleccionados</li>
                                <li>Manejo de observaciones (mostrar/ocultar)</li>
                                <li>Confirmación y envío del formulario</li>
                            </ul>
                        </div>

                        <hr>

                        <h5>Formulario de Prueba</h5>
                        <form id="formTest" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="localidad" class="form-label">Localidad</label>
                                        <select class="form-select" id="localidad" required>
                                            <option value="">Seleccione...</option>
                                            <option value="1">Buenos Aires</option>
                                            <option value="2">Córdoba</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sede" class="form-label">Sede</label>
                                        <select class="form-select" id="sede" required>
                                            <option value="">Seleccione...</option>
                                            <option value="1">Sede Central</option>
                                            <option value="2">Sede Norte</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="area" class="form-label">Área</label>
                                        <select class="form-select" id="area" required>
                                            <option value="">Seleccione...</option>
                                            <option value="1">Administración</option>
                                            <option value="2">Tecnología</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="apellido" class="form-label">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="fecha" class="form-label">Fecha</label>
                                        <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Insumos Seleccionados (Simulados)</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="insumo1" checked>
                                    <label class="form-check-label" for="insumo1">
                                        Laptop Dell Latitude - S/N: ABC123 - Tipo: Notebook
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="2" id="insumo2" checked>
                                    <label class="form-check-label" for="insumo2">
                                        Mouse Inalámbrico - Tipo: Varios (Stock: 15)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="3" id="insumo3">
                                    <label class="form-check-label" for="insumo3">
                                        Monitor Samsung 24" - S/N: XYZ789 - Tipo: Monitor
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                                    <i class="fas fa-times me-2"></i>Limpiar
                                </button>
                                <button type="button" class="btn btn-primary" onclick="mostrarModalConfirmacion()">
                                    <i class="fas fa-eye me-2"></i>Revisar y Confirmar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalConfirmacionLabel">
                        <i class="fas fa-check-circle me-2"></i>Confirmar Asignación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Revise los datos antes de confirmar la asignación:</strong>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>Ubicación
                            </h6>
                            <div class="mb-2">
                                <strong>Localidad:</strong> <span id="modal-localidad"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Sede:</strong> <span id="modal-sede"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Área:</strong> <span id="modal-area"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Persona Asignada
                            </h6>
                            <div class="mb-2">
                                <strong>Nombre:</strong> <span id="modal-nombre"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Apellido:</strong> <span id="modal-apellido"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Fecha:</strong> <span id="modal-fecha"></span>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-boxes me-2"></i>Insumos Seleccionados
                            </h6>
                            <div id="modal-insumos" class="table-responsive">
                                <!-- Los insumos se cargarán dinámicamente -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3" id="modal-observaciones-container" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-comment me-2"></i>Observaciones
                            </h6>
                            <div class="alert alert-light">
                                <span id="modal-observaciones"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmarAsignacion()">
                        <i class="fas fa-check me-2"></i>Confirmar Asignación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para mostrar el modal de confirmación
        function mostrarModalConfirmacion() {
            // Validar formulario
            const form = document.getElementById('formTest');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            // Validar que hay insumos seleccionados
            const insumosSeleccionados = $('input[type="checkbox"]:checked').length;
            if (insumosSeleccionados === 0) {
                alert('Debe seleccionar al menos un insumo');
                return;
            }
            
            // Recopilar datos del formulario
            const localidad = $('#localidad option:selected').text();
            const sede = $('#sede option:selected').text();
            const area = $('#area option:selected').text();
            const nombre = $('#nombre').val();
            const apellido = $('#apellido').val();
            const fecha = $('#fecha').val();
            const observaciones = $('#observaciones').val();
            
            // Poblar datos en el modal
            $('#modal-localidad').text(localidad);
            $('#modal-sede').text(sede);
            $('#modal-area').text(area);
            $('#modal-nombre').text(nombre);
            $('#modal-apellido').text(apellido);
            $('#modal-fecha').text(fecha);
            
            // Mostrar/ocultar observaciones
            if (observaciones.trim()) {
                $('#modal-observaciones').text(observaciones);
                $('#modal-observaciones-container').show();
            } else {
                $('#modal-observaciones-container').hide();
            }
            
            // Generar tabla de insumos seleccionados
            let tablaInsumos = `
                <table class="table table-sm table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Ubicación Actual</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            $('input[type="checkbox"]:checked').each(function() {
                const $checkbox = $(this);
                const label = $checkbox.next('label').text();
                const partes = label.split(' - ');
                const nombreInsumo = partes[0];
                const tipo = partes[2] ? partes[2].replace('Tipo: ', '') : 'N/A';
                const ubicacion = 'Sede Central';
                
                let cantidad = '1';
                if (tipo === 'Varios') {
                    cantidad = '5'; // Simulado
                }
                
                tablaInsumos += `
                    <tr>
                        <td><strong>${nombreInsumo}</strong></td>
                        <td><span class="badge bg-info">${tipo}</span></td>
                        <td><span class="badge bg-success">${cantidad}</span></td>
                        <td><small>${ubicacion}</small></td>
                    </tr>`;
            });
            
            tablaInsumos += '</tbody></table>';
            $('#modal-insumos').html(tablaInsumos);
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
            modal.show();
        }

        // Función para confirmar la asignación
        function confirmarAsignacion() {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
            modal.hide();
            
            // Simular envío del formulario
            alert('¡Formulario enviado correctamente! Esta es una simulación.');
            console.log('Formulario confirmado y enviado');
        }

        // Función para limpiar el formulario
        function limpiarFormulario() {
            document.getElementById('formTest').reset();
            document.getElementById('formTest').classList.remove('was-validated');
        }

        // Inicialización
        $(document).ready(function() {
            console.log('Test Modal de Confirmación cargado');
        });
    </script>
</body>
</html>
