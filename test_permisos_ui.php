<?php
/**
 * Test de Permisos UI
 * Verifica que los botones se muestren/oculten correctamente según los permisos del usuario
 */

require_once 'includes/config.php';

// Verificar autenticación
if (!estaAutenticado()) {
    echo "Error: No autenticado\n";
    exit(1);
}

// Obtener usuario actual
$usuario = obtenerUsuario();
if (!$usuario) {
    echo "Error: No se puede obtener datos del usuario\n";
    exit(1);
}

echo "=== TEST DE PERMISOS UI ===\n";
echo "Usuario: " . $usuario['email'] . "\n";
echo "Rol: " . $usuario['nombre_rol'] . "\n";
echo "\n";

// Módulos y acciones a probar
$modulos_acciones = [
    'insumos' => ['ver', 'editar', 'baja', 'eliminar'],
    'asignaciones' => ['ver', 'devolver', 'eliminar'],
];

// Mostrar permisos del usuario
echo "=== PERMISOS DEL USUARIO ===\n";
foreach ($modulos_acciones as $modulo => $acciones) {
    echo "\nMódulo: $modulo\n";
    foreach ($acciones as $accion) {
        $tiene_permiso = tienePermiso($modulo, $accion);
        $status = $tiene_permiso ? "✓ SÍ" : "✗ NO";
        echo "  - $accion: $status\n";
    }
}

echo "\n=== VISTA PREVIO: BOTONES QUE SE MOSTRARÁN ===\n";
echo "\nMódulo INSUMOS:\n";
if (tienePermiso('insumos', 'ver')) {
    echo "  ✓ Botón VER (ojo)\n";
}
if (tienePermiso('insumos', 'editar')) {
    echo "  ✓ Botón EDITAR (lápiz)\n";
}
if (tienePermiso('insumos', 'baja')) {
    echo "  ✓ Botón BAJA (flecha abajo)\n";
}
if (tienePermiso('insumos', 'eliminar')) {
    echo "  ✓ Botón ELIMINAR (basura)\n";
}
if (tienePermiso('insumos', 'editar')) {
    echo "  ✓ Botón REPONER (para items Varios)\n";
}

echo "\nMódulo ASIGNACIONES:\n";
if (tienePermiso('asignaciones', 'ver')) {
    echo "  ✓ Botón VER (ojo)\n";
    echo "  ✓ Botón IMPRIMIR (impresora)\n";
}
if (tienePermiso('asignaciones', 'devolver')) {
    echo "  ✓ Botón DEVOLVER (deshacer)\n";
}
if (tienePermiso('asignaciones', 'eliminar')) {
    echo "  ✓ Botón ELIMINAR (basura)\n";
}

echo "\n✓ Test completado\n";
?>
