<?php
require_once '../../includes/config.php';
requerirAutenticacion();
verificarPermiso('pedidos', 'crear');

$db = conectarDB();

// Datos para ubicación
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$areas = $db->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area")->fetchAll();

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
        $metodoEntrega = 'No aplica'; // Se definirá en logística

        if (!$idSede || $idSede <= 0 || $nombre === '' || $apellido === '') {
            throw new Exception('Complete todos los campos obligatorios (*)');
        }

        // 1. Guardar Nota Solicitud (OBLIGATORIA para Pedido Insumo según flujo)
        if (empty($_FILES['nota_pedido']) || $_FILES['nota_pedido']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Debe adjuntar la Nota de Solicitud en formato PDF');
        }

        require_once '../../includes/validar_archivo.php';
        $val = validarArchivoPdf($_FILES['nota_pedido']);
        if (!$val['valido']) throw new Exception($val['error']);
        
        $db->beginTransaction();

        $ext = $val['extension'];
        $nombreArchivoNota = 'nota_sol_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadDir = UPLOAD_BASE_DIR . 'pedidos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!move_uploaded_file($_FILES['nota_pedido']['tmp_name'], $uploadDir . $nombreArchivoNota)) {
            throw new Exception('Error al guardar el archivo PDF');
        }

        // 2. Crear Pedido en estado Pendiente
        $stmtP = $db->prepare("INSERT INTO pedidos (
            id_usuario_solicitante, 
            solicitante_nombre, 
            solicitante_apellido, 
            id_sede, 
            id_area, 
            tipo, 
            prioridad, 
            descripcion, 
            estado, 
            fecha_creacion, 
            metodo_entrega, 
            estado_entrega, 
            pdf_nota
        ) VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?,?)");

        $stmtP->execute([
            obtenerUsuarioId(), 
            $nombre, 
            $apellido, 
            $idSede, 
            $idArea ?: null, 
            'Pedido Insumo', 
            $prioridad, 
            $descripcion, 
            'Pendiente', 
            $metodoEntrega, 
            'Pendiente',
            $nombreArchivoNota
        ]);
        
        $idPedido = (int)$db->lastInsertId();

        // Registrar en historial
        $db->prepare("INSERT INTO pedidos_historial (id_pedido, id_usuario, accion, detalle) VALUES (?, ?, 'Creación', 'Pedido de insumos solicitado con nota adjunta')")
           ->execute([$idPedido, obtenerUsuarioId()]);

        $db->commit();
        $_SESSION['mensaje'] = "Pedido de Insumos creado correctamente. Queda pendiente de preparación.";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: listar.php');
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="mb-0"><i class="fas fa-file-signature me-2"></i>Nueva Solicitud de Insumos</h1>
                <a href="listar.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Datos de la Solicitud</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php echo csrf_input(); ?>
                    
                    <div class="row g-4">
                        <!-- Sección Solicitante -->
                        <div class="col-md-6">
                            <h5 class="section-title mb-3"><i class="fas fa-user me-2"></i>Solicitante</h5>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre *</label>
                                <input type="text" class="form-control" name="solicitante_nombre" required placeholder="Ej: Juan">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Apellido *</label>
                                <input type="text" class="form-control" name="solicitante_apellido" required placeholder="Ej: Pérez">
                            </div>
                        </div>

                        <!-- Sección Ubicación -->
                        <div class="col-md-6">
                            <h5 class="section-title mb-3"><i class="fas fa-map-marker-alt me-2"></i>Destino</h5>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Localidad *</label>
                                <select class="form-select" id="id_localidad" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($localidades as $loc): ?>
                                        <option value="<?php echo $loc['id_localidad']; ?>"><?php echo htmlspecialchars($loc['nombre_localidad']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sede *</label>
                                <select class="form-select" id="id_sede" name="id_sede" required disabled>
                                    <option value="">Primero elija localidad</option>
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
                                    <option value="">Seleccione Sede primero...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <select class="form-select" name="prioridad">
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nota de Solicitud (PDF) *</label>
                                <input type="file" class="form-control" name="nota_pedido" accept=".pdf" required>
                                <div class="form-text">Adjunte el archivo PDF con la firma correspondiente.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descripción de Necesidad</label>
                                <textarea class="form-control" name="descripcion" rows="4" placeholder="Describa brevemente qué insumos necesita y para qué..."></textarea>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2 pt-3">
                            <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
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
    // Carga de Sedes
    $('#id_localidad').on('change', function () {
        const id = $(this).val();
        const $sede = $('#id_sede');
        if(!id) { $sede.prop('disabled', true).html('<option value="">Seleccione localidad</option>'); return; }
        
        $sede.prop('disabled', true).html('<option value="">Cargando...</option>');
        $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id })
            .done(r => {
                const lista = (r.data && r.data.sedes) ? r.data.sedes : (r.sedes || []);
                let opts = '<option value="">Seleccione una sede</option>';
                lista.forEach(s => { opts += `<option value="${s.id}">${s.nombre}</option>`; });
                $sede.html(opts).prop('disabled', false);
            })
            .fail(() => $sede.html('<option value="">Error al cargar</option>'));
    });

    // Carga de Áreas
    $('#id_sede').on('change', function () {
        const id = $(this).val();
        const $area = $('#id_area_asignada');
        if(!id) { $area.html('<option value="">Seleccione sede</option>'); return; }
        
        $area.html('<option value="">Cargando...</option>');
        $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id })
            .done(r => {
                const lista = (r.data && r.data.areas) ? r.data.areas : (r.areas || []);
                let opts = '<option value="">Sin área específica</option>';
                lista.forEach(a => { opts += `<option value="${a.id_area || a.id}">${a.nombre_area || a.nombre}</option>`; });
                $area.html(opts);
            })
            .fail(() => $area.html('<option value="">Error al cargar</option>'));
    });

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
