<?php
require_once '../../../includes/config.php';

// Requerir autenticación y permiso de gestión de usuarios
requerirAutenticacion();
verificarPermiso('usuarios', 'ver');
?>
<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0"><i class="fas fa-users me-2"></i>Gestión de Usuarios</h1>
        <?php if (tienePermiso('usuarios', 'crear')): ?>
            <a href="crear.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $db = conectarDB();
                        $sql = "SELECT u.*, r.nombre_rol 
                                FROM usuarios u
                                LEFT JOIN roles r ON u.id_rol = r.id_rol
                                ORDER BY u.fecha_creacion DESC";
                        $stmt = $db->query($sql);
                        $usuarios = $stmt->fetchAll();
                        
                        if (empty($usuarios)):
                    ?>
                    <tr>
                        <td colspan="7">
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay usuarios registrados</h5>
                                <p class="text-muted">Agregue nuevos usuarios para comenzar</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $user):
                            $nombreCompleto = htmlspecialchars(trim($user['nombre'] . ' ' . $user['apellido']));
                            $username = htmlspecialchars($user['username']);
                            $email = htmlspecialchars($user['email']);
                            $rol = htmlspecialchars($user['nombre_rol'] ?? 'Sin rol');
                            $ultimoAcceso = $user['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) : 'Nunca';
                            $activo = (int)$user['activo'];
                            $esYo = $user['id_usuario'] == obtenerUsuarioId();
                            
                            // Verificar si tiene permisos personalizados
                            $tienePermisosPersonalizados = !empty($user['permisos_personalizados']);
                            $indicador = '';
                            $titulo = '';
                            
                            if ($tienePermisosPersonalizados) {
                                $permisosPersonalizados = json_decode($user['permisos_personalizados'], true);
                                $permisosRol = json_decode($user['permisos_rol'] ?? '{}', true);
                                
                                if (is_array($permisosPersonalizados) && is_array($permisosRol)) {
                                    // Contar permisos totales
                                    $totalPersonalizados = array_sum(array_map('count', $permisosPersonalizados));
                                    $totalRol = array_sum(array_map('count', $permisosRol));
                                    
                                    if ($totalPersonalizados > $totalRol) {
                                        $indicador = ' <i class="fas fa-arrow-up text-success" title="Más permisos que el rol"></i>';
                                        $titulo = 'Tiene más permisos que el rol asignado';
                                    } elseif ($totalPersonalizados < $totalRol) {
                                        $indicador = ' <i class="fas fa-arrow-down text-danger" title="Menos permisos que el rol"></i>';
                                        $titulo = 'Tiene menos permisos que el rol asignado';
                                    } else {
                                        $indicador = ' <i class="fas fa-circle text-warning" style="font-size: 0.5rem;" title="Permisos modificados"></i>';
                                        $titulo = 'Tiene permisos personalizados';
                                    }
                                }
                            }
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo $username; ?></strong>
                                <?php if ($esYo): ?>
                                    <span class="badge bg-info ms-1">Tú</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $nombreCompleto; ?></td>
                            <td><?php echo $email; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['id_rol'] == 1 ? 'danger' : 
                                        ($user['id_rol'] == 2 ? 'warning' : 
                                        ($user['id_rol'] == 3 ? 'primary' : 'secondary')); 
                                ?>" data-bs-toggle="tooltip" title="<?php echo $titulo; ?>">
                                    <?php echo $rol; ?>
                                </span><?php echo $indicador; ?>
                            </td>
                            <td><?php echo $ultimoAcceso; ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if (tienePermiso('usuarios', 'editar')): ?>
                                        <a href="editar.php?id=<?php echo $user['id_usuario']; ?>" 
                                           class="btn btn-warning" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="fas fa-edit text-dark"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso('usuarios', 'cambiar_rol') && !$esYo): ?>
                                        <button type="button" 
                                                class="btn btn-success btn-cambiar-rol" 
                                                data-id="<?php echo $user['id_usuario']; ?>"
                                                data-nombre="<?php echo $nombreCompleto; ?>"
                                                data-rol-actual="<?php echo $user['id_rol']; ?>"
                                                data-bs-toggle="tooltip" 
                                                title="Cambiar Rol">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso('usuarios', 'editar')): ?>
                                        <a href="permisos.php?id=<?php echo $user['id_usuario']; ?>" 
                                           class="btn btn-info" 
                                           data-bs-toggle="tooltip" 
                                           title="Gestionar Permisos">
                                            <i class="fas fa-eye text-white"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (tienePermiso('usuarios', 'reset_password')): ?>
                                        <a href="reset_password.php?id=<?php echo $user['id_usuario']; ?>" 
                                           class="btn btn-light border" 
                                           data-bs-toggle="tooltip" 
                                           title="Restablecer Contraseña">
                                            <i class="fas fa-key text-warning"></i>
                                        </a>
                                    <?php endif; ?>
                                        
                                    <?php if (tienePermiso('usuarios', 'editar') && !$esYo): ?>
                                        <button type="button" 
                                                class="btn btn-<?php echo $activo ? 'danger' : 'success'; ?> btn-toggle-estado" 
                                                data-id="<?php echo $user['id_usuario']; ?>"
                                                data-nombre="<?php echo $nombreCompleto; ?>"
                                                data-activo="<?php echo $activo; ?>"
                                                data-bs-toggle="tooltip" 
                                                title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="fas fa-<?php echo $activo ? 'trash-alt' : 'check'; ?>"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                        endif; // Close if (empty($usuarios))
                    } catch (Exception $e) {
                        echo '<tr><td colspan="7" class="text-center text-danger">Error al cargar usuarios: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cambiar Rol -->
<div class="modal fade" id="modalCambiarRol" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-tag me-2"></i>Cambiar Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Cambiar rol del usuario: <strong id="nombreUsuarioRol"></strong></p>
                <form id="formCambiarRol">
                    <input type="hidden" id="idUsuarioRol" name="id_usuario">
                    <div class="mb-3">
                        <label for="nuevoRol" class="form-label">Nuevo Rol</label>
                        <select class="form-select" id="nuevoRol" name="id_rol" required>
                            <?php
                            try {
                                $roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
                                foreach ($roles as $rol) {
                                    echo '<option value="' . $rol['id_rol'] . '">' . htmlspecialchars($rol['nombre_rol']) . '</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error al cargar roles</option>';
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarCambioRol">Cambiar Rol</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tablaUsuarios').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[4, 'desc']], // Ordenar por último acceso
        pageLength: 25
    });
    
    // Inicializar tooltips
    inicializarTooltips();
    
    // Cambiar rol
    $('.btn-cambiar-rol').on('click', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const rolActual = $(this).data('rol-actual');
        
        $('#idUsuarioRol').val(id);
        $('#nombreUsuarioRol').text(nombre);
        $('#nuevoRol').val(rolActual);
        
        $('#modalCambiarRol').modal('show');
    });
    
    $('#btnConfirmarCambioRol').on('click', function() {
        const btn = $(this);
        const modal = $('#modalCambiarRol');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cambiando...');
        
        $.ajax({
            url: getAppBase() + '/ajax/usuarios_cambiar_rol.php',
            method: 'POST',
            data: $('#formCambiarRol').serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Cerrar modal primero
                    modal.modal('hide');
                    // Mostrar mensaje y recargar
                    showToast('Rol cambiado correctamente', 'success');
                    setTimeout(function() { 
                        location.reload(); 
                    }, 1000);
                } else {
                    showToast(response.mensaje || 'Error al cambiar rol', 'error');
                    btn.prop('disabled', false).html('Cambiar Rol');
                }
            },
            error: function(xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.error || response.mensaje || 'Error al cambiar rol', 'error');
                } catch(e) {
                    showToast('Error de conexión', 'error');
                }
                btn.prop('disabled', false).html('Cambiar Rol');
            }
        });
    });
    
    // Toggle estado (activar/desactivar)
    $('.btn-toggle-estado').on('click', function() {
        const btn = $(this);
        const id = btn.data('id');
        const nombre = btn.data('nombre');
        const activo = btn.data('activo');
        const accion = activo ? 'desactivar' : 'activar';
        
        if (!confirm(`¿Estás seguro de ${accion} al usuario "${nombre}"?`)) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: getAppBase() + '/ajax/usuarios_toggle_estado.php',
            method: 'POST',
            data: { id_usuario: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(`Usuario ${accion === 'desactivar' ? 'desactivado' : 'activado'} correctamente`, 'success');
                    setTimeout(function() { 
                        location.reload(); 
                    }, 1000);
                } else {
                    showToast(response.mensaje || 'Error al cambiar estado', 'error');
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.error || response.mensaje || 'Error al cambiar estado', 'error');
                } catch(e) {
                    showToast('Error de conexión', 'error');
                }
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
