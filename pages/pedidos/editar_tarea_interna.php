<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('pedidos', 'gestionar');

$db = conectarDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar.php');
    exit;
}

// Obtener datos del pedido
$stmt = $db->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND tipo = 'Tarea Interna'");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    $_SESSION['mensaje'] = "Pedido no encontrado o no es una Tarea Interna";
    $_SESSION['tipo_mensaje'] = "danger";
    header('Location: listar.php');
    exit;
}

// Datos para ubicación
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Tarea Interna</h2>
            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Detalles de la Tarea</h5>
            </div>
            <div class="card-body p-4">
                <form id="formEditarTarea" class="needs-validation" novalidate>
                    <?php echo csrf_input(); ?>
                    
                    <div class="row g-4">
                        <!-- Sección Solicitante -->
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small">Responsable / Solicitante</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre *</label>
                                <input type="text" class="form-control" name="solicitante_nombre" required value="<?php echo htmlspecialchars($pedido['solicitante_nombre']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Apellido *</label>
                                <input type="text" class="form-control" name="solicitante_apellido" required value="<?php echo htmlspecialchars($pedido['solicitante_apellido']); ?>">
                            </div>
                        </div>

                        <!-- Sección Ubicación -->
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small">Ubicación</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Localidad *</label>
                                <select class="form-select" id="id_localidad" required>
                                    <option value="">Seleccione...</option>
                                    <?php 
                                    // Obtener id_localidad de la sede actual
                                    $stmtLoc = $db->prepare("SELECT id_localidad FROM sedes WHERE id_sede = ?");
                                    $stmtLoc->execute([$pedido['id_sede']]);
                                    $idLocActual = $stmtLoc->fetchColumn();

                                    foreach ($localidades as $loc): ?>
                                        <option value="<?php echo $loc['id_localidad']; ?>" <?php echo $loc['id_localidad'] == $idLocActual ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc['nombre_localidad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sede *</label>
                                <select class="form-select" id="id_sede" name="id_sede" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr class="my-2 opacity-10">
                        </div>

                        <!-- Detalles -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Área</label>
                                <select class="form-select" id="id_area_asignada" name="id_area">
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <select class="form-select" name="prioridad">
                                    <option value="Baja" <?php echo $pedido['prioridad'] === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                    <option value="Media" <?php echo $pedido['prioridad'] === 'Media' ? 'selected' : ''; ?>>Media</option>
                                    <option value="Alta" <?php echo $pedido['prioridad'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descripción de la Tarea</label>
                                <textarea class="form-control" name="descripcion" rows="4"><?php echo htmlspecialchars($pedido['descripcion']); ?></textarea>
                            </div>
                        </div>

                        <input type="hidden" name="tipo" value="Tarea Interna">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="accion" value="editar">

                        <div class="col-12 text-end pt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    function cargarSedes(idLoc, idSedeSel = null) {
        const $sede = $('#id_sede');
        if(!idLoc) { $sede.prop('disabled', true).html('<option value="">Seleccione localidad</option>'); return; }
        
        $sede.prop('disabled', true).html('<option value="">Cargando...</option>');
        $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: idLoc })
            .done(r => {
                const lista = (r.data && r.data.sedes) ? r.data.sedes : (r.sedes || []);
                let opts = '<option value="">Seleccione una sede</option>';
                lista.forEach(s => { 
                    const sel = (idSedeSel && s.id == idSedeSel) ? 'selected' : '';
                    opts += `<option value="${s.id}" ${sel}>${s.nombre}</option>`; 
                });
                $sede.html(opts).prop('disabled', false);
                if(idSedeSel) cargarAreas(idSedeSel, <?php echo $pedido['id_area'] ?: 'null'; ?>);
            })
            .fail(() => $sede.html('<option value="">Error al cargar</option>'));
    }

    function cargarAreas(idSede, idAreaSel = null) {
        const $area = $('#id_area_asignada');
        if(!idSede) { $area.html('<option value="">Seleccione sede</option>'); return; }
        
        $area.html('<option value="">Cargando...</option>');
        $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: idSede })
            .done(r => {
                const lista = (r.data && r.data.areas) ? r.data.areas : (r.areas || []);
                let opts = '<option value="">Sin área específica</option>';
                lista.forEach(a => { 
                    const idA = a.id_area || a.id;
                    const nomA = a.nombre_area || a.nombre;
                    const sel = (idAreaSel && idA == idAreaSel) ? 'selected' : '';
                    opts += `<option value="${idA}" ${sel}>${nomA}</option>`; 
                });
                $area.html(opts);
            })
            .fail(() => $area.html('<option value="">Error al cargar</option>'));
    }

    $('#id_localidad').on('change', function () {
        cargarSedes($(this).val());
    });

    $('#id_sede').on('change', function () {
        cargarAreas($(this).val());
    });

    // Carga inicial
    cargarSedes($('#id_localidad').val(), <?php echo $pedido['id_sede']; ?>);

    // Submit AJAX
    $('#formEditarTarea').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        
        $.ajax({
            url: '<?php echo app_base_url(); ?>/ajax/pedidos_acciones.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.data.mensaje || 'Tarea actualizada correctamente', 'success');
                    setTimeout(function(){ window.location.href = 'ver.php?id=<?php echo $id; ?>'; }, 1000);
                } else {
                    showToast(resp.error || 'Error', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
                }
            },
            error: function() {
                showToast('Error de conexión', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
