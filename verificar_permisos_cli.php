#!/usr/bin/env php
<?php
/**
 * Script CLI para verificar permisos del sistema
 * Uso: php verificar_permisos_cli.php
 */

require_once __DIR__ . '/includes/config.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN DE PERMISOS - SISTEMA DE INVENTARIO         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

$db = conectarDB();

// Obtener todos los roles
echo "📋 ROLES DEL SISTEMA:\n";
echo str_repeat("─", 60) . "\n";
$roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
foreach ($roles as $rol) {
    echo sprintf("  [%d] %s\n", $rol['id_rol'], $rol['nombre_rol']);
    echo sprintf("      Descripción: %s\n", $rol['descripcion'] ?? 'Sin descripción');
    $permisos = json_decode($rol['permisos'], true);
    if ($permisos) {
        echo "      Módulos: " . implode(', ', array_keys($permisos)) . "\n";
    }
    echo "\n";
}

// Obtener todos los usuarios
echo "\n👥 USUARIOS DEL SISTEMA:\n";
echo str_repeat("─", 60) . "\n";
$usuarios = $db->query("SELECT u.*, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol ORDER BY u.id_rol, u.username")->fetchAll();
foreach ($usuarios as $usuario) {
    $activo = $usuario['activo'] ? '✓' : '✗';
    echo sprintf("  %s [%s] %s (%s)\n", 
        $activo,
        $usuario['nombre_rol'], 
        $usuario['username'],
        $usuario['email']
    );
    if (!empty($usuario['permisos_personalizados'])) {
        echo "      ⚠️  Tiene permisos personalizados\n";
    }
}

// Matriz de permisos por rol
echo "\n\n📊 MATRIZ DE PERMISOS POR ROL:\n";
echo str_repeat("─", 60) . "\n";

$modulosTest = [
    'insumos' => ['ver', 'crear', 'editar', 'eliminar', 'baja'],
    'asignaciones' => ['ver', 'crear', 'editar', 'anular', 'devolver'],
    'sedes' => ['ver', 'crear', 'editar', 'eliminar'],
    'telecom' => ['ver', 'editar', 'eliminar'],
    'reportes' => ['ver', 'exportar'],
    'usuarios' => ['ver', 'crear', 'editar', 'eliminar', 'cambiar_rol', 'reset_password'],
    'auditoria' => ['ver_todo'],
    'sistema' => ['backup']
];

// Encabezado
echo sprintf("%-20s %-15s", "MÓDULO", "ACCIÓN");
foreach ($roles as $rol) {
    echo sprintf(" %-12s", substr($rol['nombre_rol'], 0, 12));
}
echo "\n" . str_repeat("─", 60 + (count($roles) * 13)) . "\n";

// Filas
foreach ($modulosTest as $modulo => $acciones) {
    foreach ($acciones as $accion) {
        echo sprintf("%-20s %-15s", ucfirst($modulo), $accion);
        
        foreach ($roles as $rol) {
            $permisos = json_decode($rol['permisos'], true);
            $tiene = isset($permisos[$modulo]) && in_array($accion, $permisos[$modulo]);
            echo sprintf(" %-12s", $tiene ? '✓ SÍ' : '✗ NO');
        }
        echo "\n";
    }
}

// Análisis de problemas
echo "\n\n🔍 ANÁLISIS DE PROBLEMAS:\n";
echo str_repeat("─", 60) . "\n";

$problemas = [];

// Verificar roles sin permisos
foreach ($roles as $rol) {
    $permisos = json_decode($rol['permisos'], true);
    if (empty($permisos) || !is_array($permisos)) {
        $problemas[] = "❌ El rol '{$rol['nombre_rol']}' no tiene permisos definidos";
    }
}

// Verificar usuarios inactivos
$inactivos = array_filter($usuarios, function($u) { return !$u['activo']; });
if (!empty($inactivos)) {
    $problemas[] = "⚠️  Hay " . count($inactivos) . " usuario(s) inactivo(s)";
}

// Verificar usuarios con permisos personalizados
$personalizados = array_filter($usuarios, function($u) { return !empty($u['permisos_personalizados']); });
if (!empty($personalizados)) {
    $problemas[] = "ℹ️  Hay " . count($personalizados) . " usuario(s) con permisos personalizados";
    foreach ($personalizados as $u) {
        echo "    - {$u['username']}\n";
    }
}

if (empty($problemas)) {
    echo "✅ No se detectaron problemas en la configuración de permisos\n";
} else {
    foreach ($problemas as $problema) {
        echo "$problema\n";
    }
}

// Sugerencias
echo "\n\n💡 SUGERENCIAS:\n";
echo str_repeat("─", 60) . "\n";
echo "1. Para probar permisos en el navegador, accede a:\n";
echo "   http://localhost/inventario_app/test_permisos.php\n\n";
echo "2. Para ver permisos de un usuario específico, usa:\n";
echo "   SELECT * FROM usuarios WHERE username = 'tu_usuario';\n\n";
echo "3. Para actualizar permisos de un rol:\n";
echo "   UPDATE roles SET permisos = '{\"modulo\":[\"accion\"]}' WHERE id_rol = X;\n\n";

echo "\n✅ Verificación completada\n\n";
?>
