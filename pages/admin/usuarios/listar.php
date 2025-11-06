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
                        
                        foreach ($usuarios as $user):
                            $nombreCompleto = htmlspecialchars(trim($user['nombre'] . ' ' . $user['apellido']));
                            $username = htmlspecialchars($user['username']);
                            $email = htmlspecialchars($user['email']);
                            $rol = htmlspecialchars($user['nombre_rol'] ?? 'Sin rol');
                            $ultimoAcceso = $user['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) : 'Nunca';
                            $activo = (int)$user['activo'];
                            $esYo = $user['id_usuario'] == obtenerUsuarioId();
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
                                ?>">
                                    <?php echo $rol; ?>
                                </span>
                            </td>
                            <td><?php echo $ultimoAcceso; ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (tienePermiso('usuarios', 'editar')): ?>
                                    <a href="editar.php?id=<?php echo $user['id_usuario']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (tienePermiso('usuarios', 'cambiar_rol') && !$esYo): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-warning btn-cambiar-rol" 
                                            data-id="<?php echo $user['id_usuario']; ?>"
                                            data-nombre="<?php echo $nombreCompleto; ?>"
                                            data-rol-actual="<?php echo $user['id_rol']; ?>"
                                            data-bs-toggle="tooltip" 
                                            title="Cambiar Rol">
                                        <i class="fas fa-user-tag"></i>
                                    </button>
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-<?php echo $activo ? 'secondary' : 'success'; ?> btn-toggle-estado" 
                                            data-id="<?php echo $user['id_usuario']; ?>"
                                            data-nombre="<?php echo $nombreCompleto; ?>"
                                            data-activo="<?php echo $activo; ?>"
                                            data-bs-toggle="tooltip" 
                                            title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="fas fa-<?php echo $activo ? 'user-slash' : 'user-check'; ?>"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
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
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cambiando...');
        
        $.ajax({
            url: getAppBase() + '/ajax/usuarios_cambiar_rol.php',
            method: 'POST',
            data: $('#formCambiarRol').serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarMensaje('Rol cambiado correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarMensaje(response.mensaje || 'Error al cambiar rol', 'danger');
                    btn.prop('disabled', false).html('Cambiar Rol');
                }
            },
            error: function() {
                mostrarMensaje('Error de conexión', 'danger');
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
                    mostrarMensaje(`Usuario ${accion === 'desactivar' ? 'desactivado' : 'activado'} correctamente`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarMensaje(response.mensaje || 'Error al cambiar estado', 'danger');
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                mostrarMensaje('Error de conexión', 'danger');
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
