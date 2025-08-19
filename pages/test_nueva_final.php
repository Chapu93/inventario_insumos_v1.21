<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Verificar que la base de datos esté funcionando
try {
    $pdo = conectarDB();
    echo "<div class='alert alert-success'>✅ Conexión a la base de datos exitosa</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    exit;
}

// Verificar que las tablas necesarias existan y tengan datos
$tablas = ['localidades', 'sedes', 'areas', 'sede_areas', 'insumos'];
foreach ($tablas as $tabla) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch();
        echo "<div class='alert alert-info'>📊 Tabla '$tabla': {$resultado['total']} registros</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>⚠️ Tabla '$tabla': " . $e->getMessage() . "</div>";
    }
}

// Verificar que sede_areas tenga datos
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sede_areas");
    $resultado = $stmt->fetch();
    if ($resultado['total'] == 0) {
        echo "<div class='alert alert-warning'>⚠️ Tabla 'sede_areas' está vacía. Ejecutando script de población...</div>";
        include_once 'admin/poblar_sede_areas.php';
    } else {
        echo "<div class='alert alert-success'>✅ Tabla 'sede_areas' tiene {$resultado['total']} registros</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error con sede_areas: " . $e->getMessage() . "</div>";
}

// Verificar insumos disponibles
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Disponible'");
    $resultado = $stmt->fetch();
    echo "<div class='alert alert-info'>📦 Insumos disponibles: {$resultado['total']}</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-warning'>⚠️ Error contando insumos: " . $e->getMessage() . "</div>";
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>Test de Nueva Asignación - Verificación Final
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Cambios implementados:</h6>
                    <ul>
                        <li>✅ Observaciones movida arriba de la línea divisoria</li>
                        <li>✅ Primera columna (#) removida de la tabla</li>
                        <li>✅ Checkbox integrado en la columna "Insumo"</li>
                        <li>✅ Headers de tabla cambiados a color oscuro</li>
                        <li>✅ Atributo data-texto corregido (no debe aparecer literalmente)</li>
                    </ul>
                    
                    <h6>Próximos pasos:</h6>
                    <ol>
                        <li>Verificar que la página nueva.php cargue correctamente</li>
                        <li>Confirmar que los dropdowns (localidad, sede, área) carguen datos</li>
                        <li>Verificar que la tabla de insumos muestre correctamente</li>
                        <li>Probar el modal de confirmación</li>
                    </ol>
                    
                    <div class="mt-4">
                        <a href="asignaciones/nueva.php" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-2"></i>Ir a Nueva Asignación
                        </a>
                        <a href="test_nueva_mejorada.php" class="btn btn-secondary">
                            <i class="fas fa-cog me-2"></i>Test Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
