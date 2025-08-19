<?php
require_once '../includes/config.php';
include '../includes/header.php';

$conexion = conectarDB();

// Verificar si la tabla sede_areas tiene datos
$stmt = $conexion->query("SELECT COUNT(*) as total FROM sede_areas");
$total_sede_areas = $stmt->fetch()['total'];

// Verificar insumos disponibles
$stmt = $conexion->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Disponible'");
$total_insumos = $stmt->fetch()['total'];

// Verificar sedes
$stmt = $conexion->query("SELECT COUNT(*) as total FROM sedes");
$total_sedes = $stmt->fetch()['total'];

// Verificar áreas
$stmt = $conexion->query("SELECT COUNT(*) as total FROM areas");
$total_areas = $stmt->fetch()['total'];

// Verificar localidades
$stmt = $conexion->query("SELECT COUNT(*) as total FROM localidades");
$total_localidades = $stmt->fetch()['total'];
?>

<div class="row">
    <div class="col-12">
        <h1>Test de Nueva Asignación</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Estado de la Base de Datos</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Localidades:</span>
                        <span class="badge bg-primary"><?php echo $total_localidades; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Sedes:</span>
                        <span class="badge bg-primary"><?php echo $total_sedes; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Áreas:</span>
                        <span class="badge bg-primary"><?php echo $total_areas; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Relaciones Sede-Área:</span>
                        <span class="badge bg-<?php echo $total_sede_areas > 0 ? 'success' : 'danger'; ?>"><?php echo $total_sede_areas; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Insumos Disponibles:</span>
                        <span class="badge bg-primary"><?php echo $total_insumos; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Acciones</h5>
            </div>
            <div class="card-body">
                <?php if ($total_sede_areas == 0): ?>
                    <div class="alert alert-warning">
                        <strong>¡Atención!</strong> La tabla sede_areas está vacía. Esto puede causar problemas al cargar áreas.
                    </div>
                    <a href="../admin/poblar_sede_areas.php" class="btn btn-warning">
                        <i class="fas fa-database me-2"></i>Poblar Sede-Áreas
                    </a>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>¡Perfecto!</strong> La tabla sede_areas tiene datos.
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <a href="asignaciones/nueva.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Probar Nueva Asignación
                </a>
                
                <a href="asignaciones/nueva_simple.php" class="btn btn-secondary">
                    <i class="fas fa-plus me-2"></i>Probar Nueva Asignación (Simple)
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Test de AJAX</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Localidad:</label>
                        <select id="test_localidad" class="form-select">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sede:</label>
                        <select id="test_sede" class="form-select">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Área:</label>
                        <select id="test_area" class="form-select">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Insumos:</label>
                        <select id="test_insumos" class="form-select" multiple>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cargar localidades
    $.getJSON('<?php echo app_base_url(); ?>/ajax/localidades_list.php')
        .done(function(response) {
            if (response.success) {
                $('#test_localidad').html('<option value="">Seleccione una localidad</option>');
                response.data.forEach(function(localidad) {
                    $('#test_localidad').append(`<option value="${localidad.id}">${localidad.nombre}</option>`);
                });
            }
        })
        .fail(function(xhr) {
            console.error('Error cargando localidades:', xhr.status, xhr.responseText);
        });
    
    // Test de carga dependiente
    $('#test_localidad').on('change', function() {
        const localidadId = $(this).val();
        if (localidadId) {
            cargarSedesPorLocalidad(localidadId, 'test_sede');
        }
    });
    
    $('#test_sede').on('change', function() {
        const sedeId = $(this).val();
        if (sedeId) {
            cargarAreasPorSede(sedeId, 'test_area');
            cargarInsumosPorSede(sedeId, 'test_insumos');
        }
    });
    
    $('#test_insumos').on('change', function() {
        console.log('Insumos seleccionados:', $(this).val());
        inicializarCantidadInsumos();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
