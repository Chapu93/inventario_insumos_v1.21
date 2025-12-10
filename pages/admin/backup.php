<?php
require_once '../../includes/config.php';

requerirAutenticacion();

// Verificar permiso de backup
verificarPermiso('sistema', 'backup');

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    if (!verify_csrf()) {
        $mensaje = 'Error de validación CSRF.';
        $tipo_mensaje = 'danger';
    } else {
        // Configuración de BD
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_name = defined('DB_NAME') ? DB_NAME : 'inventario_insumos_v1';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        
        $filename = 'backup_' . $db_name . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Ruta completa a mysqldump
        $mysqldump = '/opt/lampp/bin/mysqldump';
        
        // Comando
        // Nota: --password va pegado si hay password, o vacío si no.
        // Si el password está vacío, no usar la flag -p o usar --password=""
        $passFlag = empty($db_pass) ? '' : "--password=" . escapeshellarg($db_pass);
        
        $cmd = "{$mysqldump} --user=" . escapeshellarg($db_user) . " {$passFlag} --host=" . escapeshellarg($db_host) . " " . escapeshellarg($db_name);
        
        // Deshabilitar buffer de salida
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Ejecutar y enviar salida directamente
        $returnVar = 0;
        passthru($cmd, $returnVar);
        
        if ($returnVar === 0) {
            registrarAuditoria('backup', 'sistema', 'Backup de base de datos generado', 'sistema', 0);
            exit;
        } else {
            // Si falló, no podemos redirigir porque ya enviamos headers.
            // Pero como es un attachment, el navegador esperará el archivo.
            // Si falla, el archivo descargado contendrá el error o estará vacío.
            // Podríamos intentar capturar stderr.
            exit;
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-database me-2"></i>Copia de Seguridad
        </h1>
        <a href="../../index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Generar Backup</h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    Esta herramienta generará un archivo SQL con la estructura y los datos actuales de la base de datos.
                    El archivo se descargará automáticamente a su computadora.
                </p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Dependiendo del tamaño de la base de datos, este proceso puede tardar unos segundos.
                </div>
                
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    <button type="submit" name="backup" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-file-download me-2"></i>Descargar Copia de Seguridad
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Información de Seguridad</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-check text-success me-2"></i>
                        Solo los administradores pueden acceder a esta función.
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check text-success me-2"></i>
                        Cada descarga queda registrada en el registro de auditoría.
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Guarde el archivo descargado en una ubicación segura. Contiene información sensible.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
