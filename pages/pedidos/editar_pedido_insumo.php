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
$stmt = $db->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND tipo = 'Pedido Insumo'");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    $_SESSION['mensaje'] = "Pedido no encontrado o no es un Pedido de Insumo";
    $_SESSION['tipo_mensaje'] = "danger";
    header('Location: listar.php');
    exit;
}

// Datos para ubicación
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();

// POST Procesamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) throw new Exception('CSRF inválido');
        
        $idSede = (int) $_POST['id_sede'];
        $idArea = (int) $_POST['id_area_asignada'];
        $nombre = trim($_POST['solicitante_nombre'] ?? '');
        $apellido = trim($_POST['solicitante_apellido'] ?? '');
        $prioridad = $_POST['prioridad'] ?? 'Media';
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$idSede || $idSede <= 0 || $nombre === '' || $apellido === '') {
            throw new Exception('Complete todos los campos obligatorios (*)');
        }

        $pdfNota = $pedido['pdf_nota'];
        // Procesar Nota Solicitud si se sube una nueva
        if (!empty($_FILES['nota_pedido']) && $_FILES['nota_pedido']['error'] === UPLOAD_ERR_OK) {
            require_once '../../includes/validar_archivo.php';
            $val = validarArchivoPdf($_FILES['nota_pedido']);
            if (!$val['valido']) throw new Exception($val['error']);
            
            $ext = $val['extension'];
            $nombreArchivoNota = 'nota_sol_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (!move_uploaded_file($_FILES['nota_pedido']['tmp_name'], $uploadDir . $nombreArchivoNota)) {
                throw new Exception('Error al guardar el archivo PDF');
            }
            
            // Eliminar anterior si existe
            if ($pdfNota && file_exists($uploadDir . $pdfNota)) {
                @unlink($uploadDir . $pdfNota);
            }
            $pdfNota = $nombreArchivoNota;
        }

        $db->beginTransaction();

        // Actualizar Pedido
        $stmtU = $db->prepare("UPDATE pedidos SET 
            solicitante_nombre = ?, 
            solicitante_apellido = ?, 
            id_sede = ?, 
            id_area = ?, 
            prioridad = ?, 
            descripcion = ?, 
            pdf_nota = ?
            WHERE id_pedido = ?");

        $stmtU->execute([
            $nombre, 
            $apellido, 
            $idSede, 
            $idArea ?: null, 
            $prioridad, 
            $descripcion, 
            $pdfNota,
            $id
        ]);
        
        // Registrar en historial
        $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Edición', 'Pedido de insumos actualizado')")
           ->execute([$id, obtenerUsuarioId()]);

        $db->commit();
        $_SESSION['mensaje'] = "Pedido de Insumos actualizado correctamente.";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ver.php?id=' . $id);
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-edit me-2 text-dark"></i>Editar Solicitud de Insumos</h2>
            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Datos de la Solicitud</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php echo csrf_input(); ?>
                    
                    <div class="row g-4">
                        <!-- Sección Solicitante -->
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small">Solicitante</h6>
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
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small">Destino</h6>
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
                                <select class="form-select" id="id_area_asignada" name="id_area_asignada">
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <select class="form-select" name="prioridad">
                                    <option value="Baja" <?php echo $pedido['prioridad'] === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                    <option value="Media" <?php echo $pedido['prioridad'] === 'Media' ? 'selected' : ''; ?>>Media</option>
                                    <option value="Alta" <?php echo $pedido['prioridad'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nota de Solicitud (PDF)</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="nota_pedido" accept=".pdf">
                                    <?php if (!empty($pedido['pdf_nota'])): ?>
                                    <a href="<?php echo app_base_url(); ?>/uploads/pedidos/<?php echo $pedido['pdf_nota']; ?>" target="_blank" class="btn btn-outline-success">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Adjunte un nuevo archivo solo si desea reemplazar el actual.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descripción de Necesidad</label>
                                <textarea class="form-control" name="descripcion" rows="4"><?php echo htmlspecialchars($pedido['descripcion']); ?></textarea>
                            </div>
                        </div>

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

    // Validación de formularios Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
