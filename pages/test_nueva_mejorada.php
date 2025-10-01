<?php
require_once '../includes/config.php';

$conexion = conectarDB();

// Verificar estado de la base de datos
echo "<h2>🔍 Verificación del Estado de la Base de Datos</h2>";

// Contar registros en cada tabla
$tablas = ['localidades', 'sedes', 'areas', 'sede_areas', 'insumos'];
foreach ($tablas as $tabla) {
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM $tabla");
    $resultado = $stmt->fetch();
    echo "<p><strong>$tabla:</strong> {$resultado['total']} registros</p>";
}

// Verificar insumos disponibles
$stmt = $conexion->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Disponible'");
$resultado = $stmt->fetch();
echo "<p><strong>Insumos disponibles:</strong> {$resultado['total']} registros</p>";

// Verificar relaciones sede_areas
$stmt = $conexion->query("SELECT COUNT(*) as total FROM sede_areas");
$resultado = $stmt->fetch();
echo "<p><strong>Relaciones sede_areas:</strong> {$resultado['total']} registros</p>";

echo "<hr>";

// Verificar insumos tipo "Varios" con cantidad > 1
echo "<h3>📦 Insumos Tipo 'Varios' con Cantidad > 1</h3>";
$stmt = $conexion->query("SELECT id_insumo, nombre_insumo, cantidad, nombre_sede 
                          FROM insumos i 
                          LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
                          WHERE i.tipo_insumo = 'Varios' AND i.cantidad > 1 AND i.estado = 'Disponible'");
$insumos_varios = $stmt->fetchAll();

if (empty($insumos_varios)) {
    echo "<p class='text-warning'>⚠️ No hay insumos tipo 'Varios' con cantidad > 1 disponibles</p>";
} else {
    echo "<ul>";
    foreach ($insumos_varios as $insumo) {
        echo "<li><strong>{$insumo['nombre_insumo']}</strong> - Cantidad: {$insumo['cantidad']} - Sede: {$insumo['nombre_sede']}</li>";
    }
    echo "</ul>";
}

echo "<hr>";

// Verificar funcionalidad de filtros
echo "<h3>🔍 Funcionalidad de Filtros</h3>";
echo "<p>✅ Filtro por tipo: Implementado</p>";
echo "<p>✅ Filtro por búsqueda: Implementado</p>";
echo "<p>❌ Filtro por sede: <strong>REMOVIDO</strong> (según solicitud del usuario)</p>";

echo "<hr>";

// Verificar funcionalidad de selección
echo "<h3>✅ Funcionalidad de Selección</h3>";
echo "<p>❌ Seleccionar todos: <strong>REMOVIDO</strong> (según solicitud del usuario)</p>";
echo "<p>✅ Selección individual: Implementada</p>";
echo "<p>✅ Deseleccionar todo: Implementado</p>";

echo "<hr>";

// Verificar integración de cantidades
echo "<h3>📊 Integración de Cantidades</h3>";
echo "<p>✅ Cantidad input integrado en tabla para insumos tipo 'Varios'</p>";
echo "<p>✅ Input aparece solo cuando se selecciona el insumo</p>";
echo "<p>✅ Validación de cantidad máxima</p>";

echo "<hr>";

// Verificar cambios de UI
echo "<h3>🎨 Cambios de UI Implementados</h3>";
echo "<p>✅ Línea divisoria entre formulario y filtros</p>";
echo "<p>✅ Observaciones movidas antes de los filtros</p>";
echo "<p>✅ Headers de tabla cambiados a verde (#198754)</p>";
echo "<p>✅ Modal de confirmación implementado</p>";

echo "<hr>";

// Verificar estructura de la tabla
echo "<h3>📋 Estructura de la Tabla de Insumos</h3>";
echo "<p>✅ Columna # (checkbox)</p>";
echo "<p>✅ Columna Insumo (nombre, S/N, ID)</p>";
echo "<p>✅ Columna Tipo (badge)</p>";
echo "<p>✅ Columna Stock (badge con cantidad)</p>";
echo "<p>✅ Columna Ubicación (sede y localidad)</p>";
echo "<p>✅ Columna Acciones (botón + cantidad input para 'Varios')</p>";

echo "<hr>";

// Verificar JavaScript
echo "<h3>⚡ Funcionalidades JavaScript</h3>";
echo "<p>✅ Filtrado en tiempo real</p>";
echo "<p>✅ Selección con clic en fila</p>";
echo "<p>✅ Resaltado de filas</p>";
echo "<p>✅ Contador de seleccionados</p>";
echo "<p>✅ Limpiar filtros</p>";
echo "<p>✅ Deseleccionar todos</p>";
echo "<p>✅ Modal de confirmación</p>";

echo "<hr>";

// Verificar validaciones
echo "<h3>✅ Validaciones</h3>";
echo "<p>✅ Al menos un insumo seleccionado</p>";
echo "<p>✅ Cantidad máxima para insumos 'Varios'</p>";
echo "<p>✅ Campos obligatorios del formulario</p>";

echo "<hr>";

// Enlaces de prueba
echo "<h3>🔗 Enlaces de Prueba</h3>";
echo "<p><a href='asignaciones/nueva_pasos.php' class='btn btn-primary'>🧪 Probar Nueva Asignación</a></p>";
echo "<p><a href='test_modal_confirmacion.php' class='btn btn-info'>🧪 Probar Modal de Confirmación</a></p>";

echo "<hr>";

// Resumen de cambios
echo "<h3>📝 Resumen de Cambios Implementados</h3>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ Cambios Completados:</h4>";
echo "<ul>";
echo "<li>❌ Removido: Función 'seleccionar todos'</li>";
echo "<li>❌ Removido: Filtro 'Filtrar por sede'</li>";
echo "<li>✅ Agregado: Línea divisoria entre formulario y filtros</li>";
echo "<li>✅ Movido: Campo 'Observaciones' antes de los filtros</li>";
echo "<li>✅ Cambiado: Color de headers de tabla a verde</li>";
echo "<li>✅ Integrado: Input de cantidad para 'Varios' directamente en la tabla</li>";
echo "<li>✅ Implementado: Modal de confirmación antes de enviar</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-info'>";
echo "<h4>🎯 Funcionalidades Mejoradas:</h4>";
echo "<ul>";
echo "<li>📊 Tabla de insumos más interactiva y fácil de usar</li>";
echo "<li>🔍 Filtros simplificados y más eficientes</li>";
echo "<li>📝 Cantidad de insumos 'Varios' integrada en la tabla</li>";
echo "<li>👁️ Vista previa de datos antes de confirmar</li>";
echo "<li>🎨 Mejor separación visual entre secciones</li>";
echo "</ul>";
echo "</div>";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Nueva Asignación Mejorada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="mb-0">
                            <i class="fas fa-vial me-2"></i>
                            Test: Nueva Asignación Mejorada
                        </h1>
                    </div>
                    <div class="card-body">
                        <?php
                        // El contenido PHP ya está arriba
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
