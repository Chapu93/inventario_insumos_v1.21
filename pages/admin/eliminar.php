<?php
require_once '../../includes/config.php';

requerirAutenticacion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

if ($id <= 0 || empty($tipo)) {
    $_SESSION['mensaje'] = 'Parámetros inválidos';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$conexion = conectarDB();

try {
    // if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    
    switch ($tipo) {
        case 'sede':
            verificarPermiso('sedes', 'eliminar');
            // Verificar si tiene dependencias
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM insumos WHERE id_sede_actual = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar la sede porque tiene insumos asignados');
            }
            
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM remitos WHERE id_sede = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar la sede porque tiene remitos asociados');
            }
            
            $stmt = $conexion->prepare("DELETE FROM sedes WHERE id_sede = ?");
            $stmt->execute([$id]);
            $redirect = 'sedes.php';
            break;
            
        case 'área':
        case 'area':
            verificarPermiso('areas', 'eliminar');
            // Verificar dependencias
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM insumos WHERE id_area_asignacion_actual = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar el área porque tiene insumos asignados');
            }
            
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM remitos WHERE id_area = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar el área porque tiene remitos asociados');
            }
            
            $stmt = $conexion->prepare("DELETE FROM areas WHERE id_area = ?");
            $stmt->execute([$id]);
            $redirect = 'areas.php';
            break;
            
        default:
            throw new Exception('Tipo de elemento no válido');
    }
    
    $_SESSION['mensaje'] = ucfirst($tipo) . ' eliminada correctamente';
    $_SESSION['tipo_mensaje'] = 'success';
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    $redirect = ($tipo == 'sede') ? 'sedes.php' : 'areas.php';
}

header('Location: ' . $redirect);
exit;
?>
