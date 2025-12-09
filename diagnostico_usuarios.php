<?php
/**
 * Script de diagnóstico de permisos
 * Muestra todos los usuarios y sus permisos reales
 */
require_once 'includes/config.php';

$db = conectarDB();

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Diagnóstico de Permisos</title>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "</head>\n<body class='p-4'>\n";

echo "<h1>Diagnóstico de Permisos del Sistema</h1>\n";

// Obtener todos los usuarios con sus roles
$usuarios = $db->query("
    SELECT u.id_usuario, u.username, u.email, u.id_rol, r.nombre_rol, 
           r.permisos as permisos_rol, u.permisos_personalizados, u.activo
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id_rol
    ORDER BY u.id_rol, u.username
")->fetchAll();

echo "<div class='alert alert-info'>\n";
echo "<strong>Total de usuarios:</strong> " . count($usuarios) . "\n";
echo "</div>\n";

foreach ($usuarios as $user) {
    $activo = $user['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
    
    echo "<div class='card mb-3'>\n";
    echo "<div class='card-header bg-primary text-white'>\n";
    echo "<h5 class='mb-0'>👤 {$user['username']} ({$user['email']}) {$activo}</h5>\n";
    echo "</div>\n";
    echo "<div class='card-body'>\n";
    
    echo "<p><strong>Rol:</strong> {$user['nombre_rol']} (ID: {$user['id_rol']})</p>\n";
    
    // Determinar qué permisos usa
    if (!empty($user['permisos_personalizados'])) {
        echo "<div class='alert alert-warning'>\n";
        echo "<strong>⚠️ Este usuario tiene PERMISOS PERSONALIZADOS</strong><br>\n";
        echo "Los permisos del rol son sobrescritos por:\n";
        echo "<pre>" . htmlspecialchars($user['permisos_personalizados']) . "</pre>\n";
        echo "</div>\n";
        
        $permisos = json_decode($user['permisos_personalizados'], true);
    } else {
        echo "<div class='alert alert-success'>\n";
        echo "<strong>✓ Este usuario usa los permisos del rol</strong>\n";
        echo "</div>\n";
        
        $permisos = json_decode($user['permisos_rol'], true);
    }
    
    // Mostrar permisos en tabla
    if (is_array($permisos) && !empty($permisos)) {
        echo "<h6>Permisos efectivos:</h6>\n";
        echo "<table class='table table-sm table-bordered'>\n";
        echo "<thead><tr><th>Módulo</th><th>Acciones</th></tr></thead>\n";
        echo "<tbody>\n";
        
        foreach ($permisos as $modulo => $acciones) {
            echo "<tr>\n";
            echo "<td><strong>" . ucfirst($modulo) . "</strong></td>\n";
            echo "<td>";
            if (is_array($acciones)) {
                foreach ($acciones as $accion) {
                    $color = 'primary';
                    if ($accion === 'eliminar') $color = 'danger';
                    elseif ($accion === 'editar') $color = 'warning';
                    elseif ($accion === 'crear') $color = 'success';
                    
                    echo "<span class='badge bg-{$color} me-1'>{$accion}</span> ";
                }
            }
            echo "</td>\n";
            echo "</tr>\n";
        }
        
        echo "</tbody>\n</table>\n";
    } else {
        echo "<div class='alert alert-danger'>\n";
        echo "❌ Este usuario NO tiene permisos definidos\n";
        echo "</div>\n";
    }
    
    echo "</div>\n</div>\n";
}

echo "</body>\n</html>";
?>
