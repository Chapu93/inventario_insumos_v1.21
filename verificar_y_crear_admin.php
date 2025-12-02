<?php
/**
 * Script de verificación y creación del usuario admin
 * Ejecutar: php verificar_y_crear_admin.php
 */

require_once __DIR__ . '/includes/config.php';

try {
    $db = conectarDB();
    
    echo "==============================================\n";
    echo "   VERIFICACIÓN SISTEMA DE USUARIOS\n";
    echo "==============================================\n\n";
    
    // 1. Verificar si existe la tabla usuarios
    echo "1. Verificando tabla 'usuarios'... ";
    try {
        $db->query("SELECT 1 FROM usuarios LIMIT 1");
        echo "✅ EXISTE\n";
    } catch (Exception $e) {
        echo "❌ NO EXISTE\n";
        echo "\n⚠️  ERROR: Debes ejecutar primero el script de migración:\n";
        echo "   mysql -u usuario -p inventario_insumos_v1 < sql/migracion_sistema_usuarios.sql\n\n";
        exit(1);
    }
    
    // 2. Verificar si existe la tabla roles
    echo "2. Verificando tabla 'roles'... ";
    try {
        $db->query("SELECT 1 FROM roles LIMIT 1");
        echo "✅ EXISTE\n";
    } catch (Exception $e) {
        echo "❌ NO EXISTE\n";
        echo "\n⚠️  ERROR: Ejecuta el script de migración completo.\n\n";
        exit(1);
    }
    
    // 3. Verificar si existen roles
    echo "3. Verificando roles... ";
    $roles = $db->query("SELECT COUNT(*) as total FROM roles")->fetch();
    if ($roles['total'] > 0) {
        echo "✅ {$roles['total']} roles encontrados\n";
    } else {
        echo "❌ No hay roles\n";
        echo "\n⚠️  Insertando roles predefinidos...\n";
        
        // Insertar roles básicos
        $db->exec("INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos) VALUES
            (1, 'Super Administrador', 'Acceso total', '{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\"],\"auditoria\":[\"ver_todo\"]}'),
            (2, 'Administrador', 'Gestión de inventario', '{\"insumos\":[\"ver\",\"crear\",\"editar\"],\"asignaciones\":[\"ver\",\"crear\"]}')
        ");
        echo "   ✅ Roles creados\n";
    }
    
    // 4. Verificar usuario admin
    echo "4. Verificando usuario 'admin'... ";
    $stmt = $db->prepare("SELECT id_usuario, username, email, activo FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ EXISTE\n";
        echo "   - ID: {$admin['id_usuario']}\n";
        echo "   - Email: {$admin['email']}\n";
        echo "   - Activo: " . ($admin['activo'] ? 'Sí' : 'No') . "\n";
        
        // Actualizar contraseña por si acaso
        echo "\n5. Actualizando contraseña... ";
        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET password_hash = ?, activo = 1 WHERE username = 'admin'");
        $stmt->execute([$newHash]);
        echo "✅ Contraseña actualizada\n";
        
    } else {
        echo "❌ NO EXISTE\n";
        echo "\n5. Creando usuario admin... ";
        
        // Crear usuario admin
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO usuarios (username, email, password_hash, nombre, apellido, id_rol, activo) 
            VALUES ('admin', 'admin@inventario.local', ?, 'Administrador', 'Sistema', 1, 1)
        ");
        $stmt->execute([$passwordHash]);
        echo "✅ Usuario creado\n";
    }
    
    echo "\n==============================================\n";
    echo "   ✅ VERIFICACIÓN COMPLETADA\n";
    echo "==============================================\n\n";
    echo "🔑 CREDENCIALES DE ACCESO:\n";
    echo "   Usuario:    admin\n";
    echo "   Contraseña: admin123\n\n";
    echo "🌐 URL de Login:\n";
    echo "   http://tu-servidor" . BASE_URL . "/login.php\n\n";
    echo "⚠️  IMPORTANTE: Cambia la contraseña en producción!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
