<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Datos base
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$areas = $db->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area")->fetchAll();

// Insumos disponibles (para la tabla del paso 2)
$stmt = $db->query("SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, i.cantidad, ps.nombre_punto AS punto_stock
                    FROM insumos i
                    LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock
                    WHERE i.estado = 'Disponible' AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
                    ORDER BY i.nombre_insumo");
$insumos = $stmt->fetchAll();

// POST para crear la asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        $db->beginTransaction();
        $ids = isset($_POST['id_insumo']) ? (array)$_POST['id_insumo'] : [];
        if (empty($ids)) { throw new Exception('Debe seleccionar al menos un insumo'); }
        $idSede = (int)$_POST['id_sede'];
        $idArea = (int)$_POST['id_area_asignada'];
        $nombre = trim($_POST['nombre_persona_asignada'] ?? '');
        $apellido = trim($_POST['apellido_persona_asignada'] ?? '');
        $fecha = $_POST['fecha_asignacion'] ?: date('Y-m-d');
        $obs = $_POST['observaciones'] ?: null;
        if (!$idSede || !$idArea || $nombre === '' || $apellido === '') { throw new Exception('Complete ubicación y datos de persona'); }

        // Generación robusta con retry (igual que nueva.php)
        $maxRetries = 50; $numero = null; $ok = false; $lastErr = '';
        for ($i = 0; $i < $maxRetries; $i++) {
            $numero = generarNumeroRemito($db);
            try {
                $stmtR = $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?,?,?,?,?,?,?)");
                $stmtR->execute([$numero, $idSede, $idArea, $nombre, $apellido, $fecha, $obs]);
                $ok = true; break;
            } catch (Exception $e) {
                $lastErr = $e->getMessage();
                if (strpos($lastErr, '1062') === false) { throw $e; }
                try {
                    $anioNow = (int)date('Y');
                    $m = $db->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(numero_remito, '_', 1) AS UNSIGNED)) AS maxseq FROM remitos WHERE RIGHT(numero_remito, 4) = ?");
                    $m->execute([strval($anioNow)]);
                    $max = (int)($m->fetch()['maxseq'] ?? 0);
                    $db->prepare("UPDATE remito_secuencia SET ultimo = GREATEST(ultimo, ?) WHERE anio = ?")->execute([$max, $anioNow]);
                } catch (Exception $syncE) {}
            }
        }
        if (!$ok) { throw new Exception('No se pudo asignar número de remito único: ' . $lastErr); }
        $idRemito = (int)$db->lastInsertId();
        $stmtDet = $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,?)");

        $cantVarios = isset($_POST['cantidad_varios']) && is_array($_POST['cantidad_varios']) ? $_POST['cantidad_varios'] : [];
        foreach ($ids as $idIns) {
            $row = $db->prepare("SELECT tipo_insumo, cantidad FROM insumos WHERE id_insumo=? FOR UPDATE");
            $row->execute([$idIns]);
            $ins = $row->fetch();
            if (!$ins) { throw new Exception('Insumo no encontrado: ' . (int)$idIns); }
            $reps = 1;
            if ($ins['tipo_insumo'] === 'Varios') {
                $sol = isset($cantVarios[$idIns]) ? max(1, (int)$cantVarios[$idIns]) : 1;
                if ($sol > (int)$ins['cantidad']) { throw new Exception('Cantidad solicitada supera stock'); }
                $reps = $sol;
            }
            $stmtDet->execute([$idRemito, $idIns, $reps]);

            if ($ins['tipo_insumo'] === 'Varios') {
                $nuevo = (int)$ins['cantidad'] - $reps;
                $estado = $nuevo > 0 ? 'Disponible' : 'Asignado';
                if ($nuevo > 0) {
                    $db->prepare("UPDATE insumos SET cantidad=?, estado=?, id_sede_actual=?, id_area_asignacion_actual=? WHERE id_insumo=?")
                       ->execute([$nuevo, $estado, $idSede, $idArea, $idIns]);
                } else {
                    $db->prepare("UPDATE insumos SET cantidad=?, estado=?, id_sede_actual=?, id_area_asignacion_actual=?, id_punto_stock_actual=NULL WHERE id_insumo=?")
                       ->execute([$nuevo, $estado, $idSede, $idArea, $idIns]);
                }
            } else {
                $db->prepare("UPDATE insumos SET estado='Asignado', id_sede_actual=?, id_area_asignacion_actual=?, id_punto_stock_actual=NULL WHERE id_insumo=?")
                   ->execute([$idSede, $idArea, $idIns]);
            }
        }

        $db->commit();
        $_SESSION['mensaje'] = 'Asignación creada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        if (!empty($_POST['imprimir_remito'])) {
            header('Location: ' . app_base_url() . '/pages/asignaciones/listar.php?imprimir=' . urlencode($numero));
        } else {
            header('Location: ' . app_base_url() . '/pages/asignaciones/listar.php');
        }
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}
?>
<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Nueva Asignación (Pasos)</h4>
            <a href="listar.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Volver</a>
        </div>
    </div>
</div>

<!-- Stepper visual -->
<div class="stepper">
    <div class="step step-1 active"><span class="circle">1</span><span>Datos de Asignación</span></div>
    <div class="divider"></div>
    <div class="step step-2"><span class="circle">2</span><span>Selección de Insumos</span></div>
  </div>

<div id="nueva-pasos">
<div class="card">
    <div class="card-body">
        <form method="POST" id="formPasos" class="needs-validation" novalidate>
            <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <!-- Paso 1: Formulario de cabecera -->
            <div id="paso1">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="row">
                            <div class="col-md-6">
                        <h6 class="mb-3 section-title">Agente Asignado</h6>
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre_persona_asignada" name="nombre_persona_asignada" required>
                            <div class="invalid-feedback">El nombre es obligatorio</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" id="apellido_persona_asignada" name="apellido_persona_asignada" required>
                            <div class="invalid-feedback">El apellido es obligatorio</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Fecha de Asignación *</label>
                            <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback">Seleccione una fecha</div>
                        </div>
                            </div>
                            <div class="col-md-6">
                        <h6 class="mb-3 section-title">Ubicación</h6>
                        <div class="mb-3">
                            <label class="form-label">Localidad *</label>
                            <select class="form-select" id="id_localidad" required>
                                <option value="">Seleccione una localidad</option>
                                <?php foreach ($localidades as $loc): ?>
                                <option value="<?php echo $loc['id_localidad']; ?>"><?php echo htmlspecialchars($loc['nombre_localidad']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione una localidad</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sede *</label>
                            <select class="form-select" id="id_sede" name="id_sede" required>
                                <option value="">Seleccione una sede</option>
                            </select>
                            <div class="invalid-feedback">Seleccione una sede</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área *</label>
                            <select class="form-select" id="id_area_asignada" name="id_area_asignada" required>
                                <option value="">Seleccione un área</option>
                                <?php foreach ($areas as $a): ?>
                                <option value="<?php echo $a['id_area']; ?>"><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione un área</div>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2 justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-primary mt-3 mt-md-0" id="btnSiguiente"><i class="fas fa-arrow-right me-2"></i>Siguiente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Selección de Insumos -->
            <div id="paso2" style="display:none;">
                <div class="row justify-content-center">
                    <div class="col-lg-11">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID...">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Insumo</label>
                                        <select class="form-select form-select-sm" id="filtro_tipo" style="min-width: 280px;">
                                            <option value="">Todos los tipos</option>
                                            <option value="Varios">Varios</option>
                                            <option value="PC Completa">PC Completa</option>
                                            <option value="Notebook">Notebook</option>
                                            <option value="Impresora">Impresora</option>
                                            <option value="Monitor">Monitor</option>
                                            <option value="Escaner">Escaner</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fas fa-eraser" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary" id="contadorSeleccion">0</span>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarFiltrados()" title="Seleccionar filtrados" aria-label="Seleccionar filtrados"><i class="fas fa-check-double" aria-hidden="true"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deseleccionarTodos()" title="Deseleccionar todo" aria-label="Deseleccionar todo"><i class="fas fa-times" aria-hidden="true"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-flat table-hover" id="tablaInsumos">
                                        <tbody>
                                            <?php foreach ($insumos as $ins): ?>
                                            <tr class="fila-insumo" data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" data-texto="<?php echo strtolower(htmlspecialchars($ins['nombre_insumo'])); ?>">
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($ins['nombre_insumo']); ?></strong>
                                                    </div>
                                                </td>
                                                <td><?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?></td>
                                                <td><?php echo $ins['punto_stock'] ? htmlspecialchars($ins['punto_stock']) : '<span class="text-muted">Sin punto</span>'; ?></td>
                                                <td>
                                                    <div class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                                                        <input type="hidden" name="id_insumo[]" value="<?php echo $ins['id_insumo']; ?>" class="hidden-insumo-input" data-tipo="<?php echo htmlspecialchars($ins['tipo_insumo']); ?>" data-max="<?php echo ($ins['tipo_insumo'] === 'Varios') ? (int)$ins['cantidad'] : 1; ?>" disabled style="display:none;">
                                                        <?php if ($ins['tipo_insumo'] === 'Varios' && $ins['cantidad'] > 1): ?>
                                                        <div class="cantidad-input" style="display:none;">
                                                            <?php $cid = 'cantidad_varios_' . (int)$ins['id_insumo']; ?>
                                                            <label for="<?php echo $cid; ?>" class="small text-muted mb-0">Cant.</label>
                                                            <input id="<?php echo $cid; ?>" type="number" class="form-control form-control-sm" name="cantidad_varios[<?php echo $ins['id_insumo']; ?>]" min="1" max="<?php echo (int)$ins['cantidad']; ?>" value="1" style="width:84px;">
                                                        </div>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" data-insumo-id="<?php echo $ins['id_insumo']; ?>" onclick="toggleSeleccionInsumo(<?php echo $ins['id_insumo']; ?>)" title="Seleccionar" aria-label="Seleccionar insumo <?php echo htmlspecialchars($ins['nombre_insumo']); ?>"><i class="fas fa-plus" aria-hidden="true"></i><span class="d-none d-sm-inline"> Seleccionar</span></button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" class="btn btn-secondary" id="btnVolver2"><i class="fas fa-arrow-left me-1"></i>Volver</button>
                                    <button type="button" class="btn btn-primary" onclick="mostrarModalConfirmacion()"><i class="fas fa-eye me-1"></i>Revisar y Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalConfirmacionLabel"><i class="fas fa-check-circle me-2"></i>Confirmar Asignación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-primary mb-2"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h6>
            <p class="mb-1"><strong>Localidad:</strong> <span id="m_localidad"></span></p>
            <p class="mb-1"><strong>Sede:</strong> <span id="m_sede"></span></p>
            <p class="mb-1"><strong>Área:</strong> <span id="m_area"></span></p>
          </div>
          <div class="col-md-6">
            <h6 class="text-primary mb-2"><i class="fas fa-user me-2"></i>Persona</h6>
            <p class="mb-1"><strong>Nombre:</strong> <span id="m_nombre"></span></p>
            <p class="mb-1"><strong>Apellido:</strong> <span id="m_apellido"></span></p>
            <p class="mb-1"><strong>Fecha:</strong> <span id="m_fecha"></span></p>
          </div>
        </div>
        <hr>
        <h6 class="text-primary mb-2"><i class="fas fa-boxes me-2"></i>Insumos</h6>
        <div id="m_insumos" class="table-responsive"></div>
        <div id="m_obs_container" class="mt-3" style="display:none;">
          <h6 class="text-primary mb-2"><i class="fas fa-comment me-2"></i>Observaciones</h6>
          <div class="alert alert-light" id="m_obs"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button type="button" class="btn btn-success" onclick="confirmarAsignacion()"><i class="fas fa-check me-1"></i>Confirmar</button>
        <button type="button" class="btn btn-primary" onclick="confirmarEImprimir()"><i class="fas fa-print me-1"></i>Confirmar e Imprimir</button>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Paso 1 -> Paso 2 y viceversa
$('#btnSiguiente').on('click', function(){
  const form = document.getElementById('formPasos');
  const paso1 = document.getElementById('paso1');
  // Validar solo campos del paso 1
  let valido = true;
  ['id_localidad','id_sede','id_area_asignada','nombre_persona_asignada','apellido_persona_asignada','fecha_asignacion']
    .forEach(id => { const el = document.getElementById(id); if (!el || !el.checkValidity()) { valido = false; el && el.classList.add('is-invalid'); }});
  if (!valido) { paso1.classList.add('was-validated'); return; }
  // Limpiar indicadores de validación globales antes de pasar a Paso 2
  paso1.classList.remove('was-validated');
  form.classList.remove('was-validated');
  $('#paso1').hide();
  $('#paso2').show();
  // Stepper activo
  $('.stepper .step').removeClass('active');
  $('.stepper .step-2').addClass('active');
});
$('#btnVolver, #btnVolver2').on('click', function(){
  $('#paso2').hide();
  $('#paso1').show();
  $('.stepper .step').removeClass('active');
  $('.stepper .step-1').addClass('active');
});

// Limpiar filtros
$('#btnLimpiarFiltros').on('click', function(){
  $('#filtro_busqueda').val('');
  $('#filtro_tipo').val('');
  filtrarInsumos();
  actualizarContadorSeleccionados();
});

// Cargar sedes por localidad
$('#id_localidad').on('change', function(){
  const id = $(this).val();
  setLoading($('#id_sede'), 'Cargando sedes...');
  $.getJSON(`${getAppBase()}/ajax/cargar_sedes.php`, { localidad_id: id })
    .done(r => {
      const data = r && r.data ? r.data : r;
      const lista = data && data.sedes ? data.sedes : [];
      let opts = '<option value="">Seleccione una sede</option>';
      lista.forEach(s => { opts += `<option value="${parseInt(s.id,10)}">${$('<div>').text(s.nombre || '').html()}</option>`; });
      $('#id_sede').html(opts);
    })
    .fail(()=> $('#id_sede').html('<option value="">Seleccione una sede</option>'));
  // Evitar que mover selects empuje Observaciones: no tocar DOM fuera de su contenedor
});
// Cargar áreas por sede
$('#id_sede').on('change', function(){
  const id = $(this).val();
  setLoading($('#id_area_asignada'), 'Cargando áreas...');
  $.getJSON(`${getAppBase()}/ajax/cargar_areas.php`, { sede_id: id })
    .done(r => {
      const data = r && r.data ? r.data : r;
      const lista = data && data.areas ? data.areas : [];
      let opts = '<option value="">Seleccione un área</option>';
      lista.forEach(a => { opts += `<option value="${parseInt(a.id_area || a.id,10)}">${$('<div>').text(a.nombre_area || a.nombre || '').html()}</option>`; });
      $('#id_area_asignada').html(opts);
    })
    .fail(()=> $('#id_area_asignada').html('<option value="">Seleccione un área</option>'));
});

// Filtros de insumos (con debounce en búsqueda)
let filtroTimer = null;
$('#filtro_busqueda').on('input', function(){
  if (filtroTimer) { clearTimeout(filtroTimer); }
  filtroTimer = setTimeout(function(){ filtrarInsumos(); actualizarContadorSeleccionados(); }, 200);
});
$('#filtro_tipo').on('input change', function(){ filtrarInsumos(); actualizarContadorSeleccionados(); });

function filtrarInsumos(){
  const tipo = ($('#filtro_tipo').val() || '').toLowerCase();
  const q = ($('#filtro_busqueda').val() || '').toLowerCase();
  $('.fila-insumo').each(function(){
    const $f = $(this);
    const t = ($f.data('tipo') || '').toLowerCase();
    const txt = ($f.data('texto') || '');
    const selected = !$f.find('.hidden-insumo-input').prop('disabled');
    let show = true;
    if (!selected) {
      if (tipo && t !== tipo) show = false;
      if (q && !txt.includes(q)) show = false;
    }
    $f.toggle(show);
  });
  // Mostrar mensaje si no hay resultados visibles (sin contar filas seleccionadas ocultas)
  const $tbody = $('#tablaInsumos tbody');
  $tbody.find('tr.no-results').remove();
  const visibles = $('#tablaInsumos tbody tr.fila-insumo:visible').length;
  if (visibles === 0) {
    $tbody.append('<tr class="no-results"><td colspan="4" class="text-center text-muted">Sin resultados</td></tr>');
  }
  reorderSelectedFirst();
}

function toggleSeleccionInsumo(id){
  const $h = $(`.hidden-insumo-input[value="${id}"]`);
  const $btn = $(`.btn-seleccionar[data-insumo-id="${id}"]`);
  const $fila = $btn.closest('tr');
  const selected = !$h.prop('disabled');
  if (selected) {
    $h.prop('disabled', true);
    $btn.removeClass('btn-primary').addClass('btn-outline-primary').html('<i class="fas fa-plus"></i> Seleccionar');
    $fila.find('.cantidad-input').hide().find('input').prop('disabled', true);
  } else {
    $h.prop('disabled', false);
    $btn.removeClass('btn-outline-primary').addClass('btn-primary').html('<i class="fas fa-minus"></i> Deseleccionar');
    const tipo = $h.data('tipo');
    const max = parseInt($h.data('max') || 1, 10);
    if (tipo === 'Varios' && max > 1) { $fila.find('.cantidad-input').show().find('input').prop('disabled', false); }
  }
  actualizarContadorSeleccionados();
  reorderSelectedFirst();
}

function seleccionarFiltrados(){
  $('.fila-insumo:visible').each(function(){
    const $f = $(this);
    const $h = $f.find('.hidden-insumo-input');
    const $btn = $f.find('.btn-seleccionar');
    if ($h.prop('disabled')) {
      $h.prop('disabled', false);
      $btn.removeClass('btn-outline-primary').addClass('btn-primary').html('<i class="fas fa-minus"></i> Deseleccionar');
      const tipo = $h.data('tipo');
      const max = parseInt($h.data('max') || 1, 10);
      if (tipo === 'Varios' && max > 1) { $f.find('.cantidad-input').show().find('input').prop('disabled', false); }
    }
  });
  actualizarContadorSeleccionados();
  reorderSelectedFirst();
}

function deseleccionarTodos(){
  $('.hidden-insumo-input').prop('disabled', true);
  $('.btn-seleccionar').removeClass('btn-primary').addClass('btn-outline-primary').html('<i class="fas fa-plus"></i> Seleccionar');
  $('.cantidad-input').hide().find('input').prop('disabled', true);
  actualizarContadorSeleccionados();
  reorderSelectedFirst();
}

function reorderSelectedFirst(){
  const $tbody = $('#tablaInsumos').find('tbody');
  if (!$tbody.length) return;
  const $rows = $tbody.find('tr.fila-insumo');
  const $selected = $rows.filter(function(){ return !$(this).find('.hidden-insumo-input').prop('disabled'); });
  const $others = $rows.not($selected);
  $selected.each(function(){ $tbody.prepend(this); });
  $others.each(function(){ $tbody.append(this); });
}

function actualizarContadorSeleccionados(){
  const total = $('.hidden-insumo-input:not(:disabled)').length;
  $('#contadorSeleccion').text(`${total} seleccionados`);
}

function mostrarModalConfirmacion(){
  const form = document.getElementById('formPasos');
  const total = $('.hidden-insumo-input:not(:disabled)').length;
  if (total === 0) { alert('Debe seleccionar al menos un insumo'); return; }
  // Poblar modal
  $('#m_localidad').text($('#id_localidad option:selected').text());
  $('#m_sede').text($('#id_sede option:selected').text());
  $('#m_area').text($('#id_area_asignada option:selected').text());
  $('#m_nombre').text($('#nombre_persona_asignada').val());
  $('#m_apellido').text($('#apellido_persona_asignada').val());
  $('#m_fecha').text($('#fecha_asignacion').val());
  const obs = ($('#observaciones').val() || '').trim();
  let safe = $('<div>').text(obs).html().replace(/\n/g, '<br>');
  if (!safe) { safe = '<span class="text-muted">Sin observaciones</span>'; }
  $('#m_obs').html(safe);
  $('#m_obs_container').show();

  let rows = `<table class="table table-sm table-striped"><thead class="table-light"><tr><th>Insumo</th><th>Tipo</th><th>Cantidad</th></tr></thead><tbody>`;
  $('.hidden-insumo-input:not(:disabled)').each(function(){
    const $h = $(this); const $fila = $h.closest('tr');
    const nombre = $fila.find('td:first strong').text();
    const tipo = $h.data('tipo');
    let cant = '1';
    if (tipo === 'Varios') {
      const input = $fila.find(`input[name="cantidad_varios[${$h.val()}]"]`);
      cant = input.length ? input.val() : '1';
    }
    rows += `<tr><td><strong>${nombre}</strong></td><td><span class="badge bg-info">${tipo}</span></td><td><span class="badge bg-success">${cant}</span></td></tr>`;
  });
  rows += '</tbody></table>';
  $('#m_insumos').html(rows);

  new bootstrap.Modal(document.getElementById('modalConfirmacion')).show();
}

function confirmarAsignacion(){ $('#formPasos').submit(); }
function confirmarEImprimir(){
  // Agregar flag temporal al form para indicar que debe imprimir tras confirmar
  const form = document.getElementById('formPasos');
  if (!form) return;
  // Abrir/crear ventana nombrada para evitar bloqueadores de popup
  try { window.open('', 'remitoPrint'); } catch(e) {}
  const flag = document.createElement('input');
  flag.type = 'hidden'; flag.name = 'imprimir_remito'; flag.value = '1';
  form.appendChild(flag);
  form.submit();
}
</script>

