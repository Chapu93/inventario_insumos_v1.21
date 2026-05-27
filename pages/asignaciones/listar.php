<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('asignaciones', 'ver');

$conexion = conectarDB();

// Filtros
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_insumo = isset($_GET['insumo']) ? $_GET['insumo'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_area = isset($_GET['area']) ? $_GET['area'] : '';

// Consulta agrupada por remito (esquema nuevo) - Excluye remitos anulados
$sql = "SELECT 
            r.numero_remito,
            r.fecha_asignacion,
            r.nombre_persona_asignada,
            r.apellido_persona_asignada,
            ar.nombre_area,
            s.nombre_sede,
            l.nombre_localidad,
            SUM(d.cantidad) AS cantidad_insumos,
            SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0), 0)) AS activas,
            GROUP_CONCAT(DISTINCT i.nombre_insumo ORDER BY i.nombre_insumo SEPARATOR ', ') AS insumos
        FROM remitos r 
        JOIN remitos_detalle d ON d.id_remito = r.id_remito
        JOIN insumos i ON d.id_insumo = i.id_insumo 
        LEFT JOIN areas ar ON r.id_area = ar.id_area 
        JOIN sedes s ON r.id_sede = s.id_sede 
        JOIN localidades l ON s.id_localidad = l.id_localidad 
        WHERE r.estado != 'Anulado'";

$params = [];

// Filtro de localidad
if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

if ($filtro_insumo) {
    $sql .= " AND i.tipo_insumo = ?";
    $params[] = $filtro_insumo;
}

if ($filtro_area) {
    $sql .= " AND r.id_area = ?";
    $params[] = $filtro_area;
}

$sql .= " GROUP BY r.id_remito";

if ($filtro_estado) {
    if ($filtro_estado === 'Activa') {
        $sql .= " HAVING activas > 0";
    } elseif ($filtro_estado === 'Devuelta') {
        $sql .= " HAVING activas = 0";
    }
}

$sql .= " ORDER BY r.fecha_asignacion DESC, activas DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$asignaciones = $stmt->fetchAll();

// Datos para filtros
$localidades = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$tipos_insumo = $conexion->query("SELECT DISTINCT tipo_insumo FROM insumos ORDER BY tipo_insumo")->fetchAll();
$areas = $conexion->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area")->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<!-- Modal: Subir Remito Firmado (Reubicado al inicio para mayor compatibilidad) -->
<div class="modal fade" id="modalSubirRemito" tabindex="-1" aria-labelledby="modalSubirRemitoLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalSubirRemitoLabel">
                    <i class="fas fa-upload me-2"></i>Adjuntar Remito Firmado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSubirRemito" enctype="multipart/form-data">
                <input type="hidden" name="id_remito" id="id_remito_firmado">
                <div class="modal-body py-4">
                    <div class="mb-4 text-center">
                        <div class="icon-shape bg-light-primary text-primary rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-pdf fa-2x"></i>
                        </div>
                        <p class="text-muted">Seleccione el documento escaneado (PDF o Imagen)</p>
                    </div>
                    <div class="mb-3">
                        <label for="archivo_remito_firmado" class="form-label fw-bold">Archivo del Remito</label>
                        <input type="file" class="form-control" id="archivo_remito_firmado" name="archivo" accept=".pdf,image/*" required>
                        <div class="form-text mt-2">Formatos permitidos: PDF, JPG, PNG. Máx. 10MB.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between py-3">
                    <button type="button" class="btn btn-outline-danger px-3 shadow-sm" id="btnEliminarRemitoFirmado" style="display: none;">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar Actual
                    </button>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnGuardarRemitoFirmado">
                            <i class="fas fa-check me-1"></i>Subir Archivo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-handshake me-2"></i>Gestión de Asignaciones
            </h1>
            <div class="d-flex gap-2">
                <?php if (tienePermiso('asignaciones', 'crear') && tienePermiso('asignaciones', 'devolver')): ?>
                <button type="button" class="btn btn-soft-warning" id="btnAbrirTransferencia">
                    <i class="fas fa-exchange-alt me-2"></i>Transferir Insumos
                </button>
                <?php endif; ?>
                <?php if (tienePermiso('asignaciones', 'crear')): ?>
                <a href="nueva_pasos.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nueva Asignación
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de Estado -->
<ul class="nav nav-tabs mb-3" id="tabsEstado">
    <li class="nav-item">
        <a class="nav-link active" href="#" data-estado="Activa">
            <i class="fas fa-check-circle me-2 text-success"></i>Activas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-estado="Devuelta">
            <i class="fas fa-undo me-2 text-secondary"></i>Devueltas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-estado="Anulado">
            <i class="fas fa-ban me-2 text-danger"></i>Anulados
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-estado="">
            <i class="fas fa-list me-2 text-primary"></i>Todas
        </a>
    </li>
</ul>

<div class="filtros-container">
    <form method="GET" class="row g-3">
        <input type="hidden" name="estado" id="estado" value="Activa">
        <div class="col-md-3">
            <label for="insumo" class="form-label">Tipo de Insumo</label>
            <select name="insumo" id="insumo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_insumo as $tipo): ?>
                    <option value="<?php echo $tipo['tipo_insumo']; ?>" <?php echo $filtro_insumo == $tipo['tipo_insumo'] ? 'selected' : ''; ?>>
                        <?php echo $tipo['tipo_insumo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>" <?php echo $filtro_localidad == $loc['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $loc['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="sede" class="form-label">Sede</label>
            <select name="sede" id="sede" class="form-select" disabled>
                <option value="">Seleccione Localidad</option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="listar.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Asignaciones (<?php echo count($asignaciones); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($asignaciones)): ?>
            <div class="text-center py-4">
                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron asignaciones</h5>
                <p class="text-muted">No hay asignaciones que coincidan con los filtros aplicados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaAsignaciones" data-default-order-col="2"
                    data-default-order-dir="desc" data-ssp="1">
                    <thead>
                        <tr>
                            <th>Persona Asignada</th>
                            <th>Localidad</th>
                            <th>Fecha Asignación</th>
                            <th data-orderable="true">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalExportarExcel">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary"
                onclick="imprimirTabla('tablaAsignaciones', 'asignaciones')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<script>
    function cambiarEstadoPorRemito(remito, estado) {
        if (!remito || !estado) return;
        showConfirm({
            titulo: 'Cambiar Estado',
            mensaje: `¿Está seguro que desea cambiar el estado del remito ${remito} a "${estado}"?`,
            icono: 'fa-sync-alt text-primary',
            onConfirm: () => {
                const base = typeof getAppBase === 'function' ? getAppBase() : '';
                window.location.href = `${base}/pages/asignaciones/cambiar_estado.php?remito=${encodeURIComponent(remito)}&estado=${encodeURIComponent(estado)}`;
            }
        });
    }
</script>

<script>
    // Eliminar asignación (remito completo)
    window.eliminarAsignacion = function (remito) {
        if (!remito) return;
        // Mostrar modal para pedir motivo de anulación
        $('#remitoAnular').val(remito);
        $('#numeroRemitoAnular').text(remito);
        $('#motivoAnulacion').val('');
        $('#modalAnulacion').modal('show');
    }

    window.confirmarAnulacion = function () {
        const remito = $('#remitoAnular').val();
        const motivo = $('#motivoAnulacion').val().trim();

        if (!motivo) {
            showToast('Debe especificar un motivo de anulación', 'error');
            return;
        }

        const token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        fetch(`${getAppBase()}/ajax/asignacion_eliminar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ remito, motivo })
        })
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) { throw new Error(resp.error || 'Error al anular remito'); }
                $('#modalAnulacion').modal('hide');
                showToast(resp.mensaje || 'Remito anulado correctamente', 'success');
                try { $('#tablaAsignaciones').DataTable().ajax.reload(); } catch (e) { location.reload(); }
            })
            .catch(err => showToast(err.message || 'Error al anular remito', 'error'));
    }
</script>

<!-- Modal Devolución de Insumos -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDevolucionLabel">
                    <i class="fas fa-undo me-2"></i>Devolver Insumos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="devolucionAlert" style="display:none;"></div>
                <div class="table-responsive">
                    <table class="table table-sm w-100 align-middle table-striped" id="tablaDevolucion">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="chkAllDevolver"></th>
                                <th>Insumo</th>
                                <th>Tipo</th>
                                <th>Cantidad Asignada</th>
                                <th>Devolver</th>
                            </tr>
                        </thead>
                        <tbody id="tablaDevolucionBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmarDevolucion">Confirmar Devolución</button>
            </div>
        </div>
    </div>
</div>

<script>
    let DEVOLUCION_REM = '';
    let VER_REM = '';

    function safeTrim(value) {
        return value === undefined || value === null ? '' : String(value).trim();
    }

    function buildInsumoDisplay(item) {
        const original = safeTrim(item && item.nombre_insumo) || '-';
        const tipo = safeTrim(item && item.tipo_insumo);
        const esPc = (tipo === 'PC Escritorio' || tipo === 'PC Completa');
        if (tipo === 'Varios') {
            return { display: original, original };
        }
        if (esPc) {
            const sistOp = safeTrim(item && (item.pc_sist_op ?? item.sist_op));
            if (sistOp) {
                return { display: sistOp, original };
            }
            return { display: original, original };
        }
        const marca = safeTrim(item && item.nb_marca) || safeTrim(item && item.imp_marca) || safeTrim(item && item.mon_marca) || safeTrim(item && item.esc_marca);
        const modelo = safeTrim(item && item.nb_modelo) || safeTrim(item && item.imp_modelo) || safeTrim(item && item.mon_modelo) || safeTrim(item && item.esc_modelo);
        if (marca || modelo) {
            const separator = marca && modelo ? ' - ' : '';
            return { display: (marca + separator + modelo).trim(), original };
        }
        return { display: original, original };
    }

    function abrirDevolucion(remito) {
        DEVOLUCION_REM = remito;
        const modal = new bootstrap.Modal(document.getElementById('modalDevolucion'));
        const body = document.getElementById('tablaDevolucionBody');
        const alertBox = document.getElementById('devolucionAlert');
        alertBox.style.display = 'none';
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>';
        fetch(`${getAppBase()}/ajax/remito_items.php?remito=${encodeURIComponent(remito)}`)
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) { throw new Error(resp.error || 'Error al cargar items'); }
                const items = resp.data && resp.data.items ? resp.data.items : [];
                const rows = [];
                items.forEach(it => {
                    const info = buildInsumoDisplay(it);
                    const displayName = info.display;
                    const originalName = info.original;
                    const showOriginal = safeTrim(displayName) !== safeTrim(originalName);
                    const isVarios = it.tipo_insumo === 'Varios';
                    const devueltos = parseInt(it.cantidad_devuelta || '0', 10);
                    const asignados = parseInt(it.cantidad || '0', 10);
                    const pendientes = Math.max(0, asignados - devueltos);
                    const qtyInput = isVarios
                        ? `<input type="number" class="form-control form-control-sm" min="1" max="${pendientes}" value="${pendientes}" data-id="${it.id_insumo}" data-max="${pendientes}" style="width:90px;" ${pendientes > 0 ? '' : 'disabled'}>`
                        : `<span class="badge bg-secondary">1</span>`;
                    rows.push(`
            <tr>
              <td><input type="checkbox" class="chk-dev" data-id="${it.id_insumo}" ${pendientes > 0 ? 'checked' : 'disabled'}></td>
              <td><strong>${displayName}</strong>${showOriginal ? `<br><small class="text-muted">${originalName}</small>` : ''}${it.numero_serie ? `<br><small class="text-muted">S/N: ${it.numero_serie}</small>` : ''}${it.id_fisico ? `<br><small class="text-muted">ID: ${it.id_fisico}</small>` : ''}</td>
              <td><span class="badge ${isVarios ? 'bg-info' : 'bg-primary'}">${it.tipo_insumo}</span></td>
              <td><span class="badge bg-dark">${it.cantidad}</span> ${devueltos > 0 ? `<small class="text-muted">(devueltos: ${devueltos})</small>` : ''}</td>
              <td>${qtyInput}</td>
            </tr>
          `);
                });
                body.innerHTML = rows.join('') || '<tr><td colspan="5" class="text-center text-muted">Sin items activos</td></tr>';
                document.getElementById('chkAllDevolver').checked = true;
            })
            .catch(err => {
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = err.message;
                alertBox.style.display = 'block';
                body.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error</td></tr>';
            });
        modal.show();
    }

    document.getElementById('chkAllDevolver').addEventListener('change', function () {
        document.querySelectorAll('#tablaDevolucionBody .chk-dev').forEach(chk => { chk.checked = this.checked; });
    });

    document.getElementById('btnConfirmarDevolucion').addEventListener('click', function () {
        const seleccion = [];
        document.querySelectorAll('#tablaDevolucionBody .chk-dev:checked').forEach(chk => {
            const id = parseInt(chk.getAttribute('data-id'), 10);
            const qtyInput = document.querySelector(`#tablaDevolucionBody input[type="number"][data-id="${id}"]`);
            console.log('ID:', id, 'Input encontrado:', qtyInput, 'Valor:', qtyInput?.value);
            const cantidad = qtyInput ? Math.max(1, Math.min(parseInt(qtyInput.value || '1', 10), parseInt(qtyInput.getAttribute('data-max') || '1', 10))) : 1;
            console.log('Cantidad final:', cantidad);
            seleccion.push({ id_insumo: id, cantidad });
        });
        console.log('Selección completa:', seleccion);
        if (seleccion.length === 0) {
            showAlert({
                titulo: 'Selección Requerida',
                mensaje: 'Debe seleccionar al menos un insumo a devolver',
                icono: 'fa-exclamation-circle text-warning'
            });
            return;
        }
        fetch(`${getAppBase()}/ajax/devolver_insumos.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: JSON.stringify({ remito: DEVOLUCION_REM, items: seleccion })
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { throw new Error(data.error || 'Error en devolución'); }
                const alertBox = document.getElementById('devolucionAlert');
                alertBox.className = 'alert alert-success';
                alertBox.textContent = 'Devolución registrada correctamente.';
                alertBox.style.display = 'block';
                setTimeout(() => { location.reload(); }, 1200);
            })
            .catch(err => {
                const alertBox = document.getElementById('devolucionAlert');
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = err.message;
                alertBox.style.display = 'block';
            });
    });
</script>

<!-- Modal Ver Asignación -->
<div class="modal fade" id="modalVerAsignacion" tabindex="-1" aria-labelledby="modalVerAsignacionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVerAsignacionLabel">
                    <i class="fas fa-eye me-2"></i>Detalle de Asignación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="verAlert" style="display:none;"></div>
                <div id="verCabecera" class="mb-3"></div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Tipo</th>
                                <th>Asignados</th>
                                <th>Devueltos</th>
                                <th>Pendientes</th>
                            </tr>
                        </thead>
                        <tbody id="verTablaBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function abrirVerAsignacion(remito) {
        VER_REM = remito;
        const modal = new bootstrap.Modal(document.getElementById('modalVerAsignacion'));
        const cab = document.getElementById('verCabecera');
        const body = document.getElementById('verTablaBody');
        const alertBox = document.getElementById('verAlert');
        alertBox.style.display = 'none';
        cab.innerHTML = '';
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>';
        fetch(`${getAppBase()}/ajax/remito_detalle.php?remito=${encodeURIComponent(remito)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { throw new Error(data.error || 'Error al cargar remito'); }
                const c = data.data ? data.data.cab : data.cab;
                if (!c) { throw new Error('Estructura de datos inválida recibida del servidor'); }
                const items = data.data ? data.data.items : data.items;
                cab.innerHTML = `
        <div class="row">
          <div class="col-md-6">
            <p class="mb-1"><strong>Remito:</strong> ${c.numero_remito || ''}</p>
            <p class="mb-1"><strong>Fecha:</strong> ${c.fecha_asignacion || ''}</p>
            <p class="mb-1"><strong>Estado:</strong> <span class="badge ${c.estado === 'Activa' ? 'bg-warning' : 'bg-success'}">${c.estado || 'Desconocido'}</span></p>
            ${c.estado === 'Devuelta' && c.fecha_devolucion ? `<p class="mb-1"><strong>Fecha devolución:</strong> ${c.fecha_devolucion}</p>` : ''}
            ${c.nota_solicitud ? `<p class="mb-1"><strong>Nota Solicitud:</strong> <a href="${getAppBase()}/uploads/${c.nota_solicitud}" target="_blank" class="btn btn-xs btn-outline-danger py-0 px-1"><i class="fas fa-file-pdf me-1"></i>Ver Nota</a></p>` : ''}
          </div>
          <div class="col-md-6">
            <p class="mb-1"><strong>Persona:</strong> ${c.nombre_persona_asignada || ''} ${c.apellido_persona_asignada || ''}</p>
            <p class="mb-1"><strong>Área:</strong> ${c.nombre_area || ''}</p>
            <p class="mb-1"><strong>Sede:</strong> ${c.nombre_sede || ''} - ${c.nombre_localidad || ''} (${c.nombre_zona || ''})</p>
          </div>
        </div>
        ${c.observaciones ? `<div class="alert alert-light mt-2">${c.observaciones}</div>` : ''}
      `;
                const rows = [];
                if (Array.isArray(items)) {
                    items.forEach(it => {
                        const info = buildInsumoDisplay(it);
                        const displayName = info.display;
                        const originalName = info.original;
                        const showOriginal = safeTrim(displayName) !== safeTrim(originalName);
                        const asignados = parseInt(it.cantidad || '0', 10);
                        const devueltos = parseInt(it.cantidad_devuelta || '0', 10);
                        const pendientes = Math.max(0, asignados - devueltos);
                        // Extraer características si corresponde
                        let specsHtml = '';
                        if (it.tipo_insumo === 'PC Escritorio' || it.tipo_insumo === 'PC Completa') {
                            const cpu = safeTrim(it.pc_procesador);
                            const ram = safeTrim(it.pc_ram);
                            const disco = safeTrim(it.pc_disco);
                            const mbo = safeTrim(it.pc_mother);
                            if (cpu || ram || disco || mbo) {
                                specsHtml += `<div class="d-flex flex-wrap gap-3 mb-0 text-muted small">`;
                                if (cpu) specsHtml += `<span><strong><i class="fas fa-microchip me-1"></i>CPU:</strong> ${cpu}</span>`;
                                if (ram) specsHtml += `<span><strong><i class="fas fa-memory me-1"></i>RAM:</strong> ${ram} GB</span>`;
                                if (disco) specsHtml += `<span><strong><i class="fas fa-hdd me-1"></i>Disco:</strong> ${disco} GB</span>`;
                                if (mbo) specsHtml += `<span><strong><i class="fas fa-chess-board me-1"></i>Motherboard:</strong> ${mbo}</span>`;
                                specsHtml += `</div>`;
                            }
                        } else if (it.tipo_insumo === 'Notebook') {
                            const cpu = safeTrim(it.nb_procesador);
                            const ram = safeTrim(it.nb_ram);
                            const disco = safeTrim(it.nb_disco);
                            if (cpu || ram || disco) {
                                specsHtml += `<div class="d-flex flex-wrap gap-3 mb-0 text-muted small">`;
                                if (cpu) specsHtml += `<span><strong><i class="fas fa-microchip me-1"></i>CPU:</strong> ${cpu}</span>`;
                                if (ram) specsHtml += `<span><strong><i class="fas fa-memory me-1"></i>RAM:</strong> ${ram} GB</span>`;
                                if (disco) specsHtml += `<span><strong><i class="fas fa-hdd me-1"></i>Disco:</strong> ${disco} GB</span>`;
                                specsHtml += `</div>`;
                            }
                        } else if (it.tipo_insumo === 'Monitor') {
                            const puls = safeTrim(it.mon_pulgadas);
                            const conn = safeTrim(it.mon_conexion);
                            if (puls || conn) {
                                specsHtml += `<div class="d-flex flex-wrap gap-3 mb-0 text-muted small">`;
                                if (puls) specsHtml += `<span><strong><i class="fas fa-desktop me-1"></i>Tamaño:</strong> ${puls}"</span>`;
                                if (conn) specsHtml += `<span><strong><i class="fas fa-plug me-1"></i>Conexión:</strong> ${conn}</span>`;
                                specsHtml += `</div>`;
                            }
                        }

                        let detailsHtml = '';
                        if (specsHtml !== '') {
                            detailsHtml = `<div class="mt-1">
                  <a href="javascript:void(0)" class="text-decoration-none small d-inline-block py-1 text-primary w-100" onclick="$(this).next().slideToggle('fast'); $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');">
                    <i class="fas fa-chevron-down me-1"></i>Ver detalles
                  </a>
                  <div style="display:none;" class="bg-light p-2 rounded mt-1 border">
                    ${specsHtml}
                  </div>
                </div>`;
                        }

                        rows.push(`
              <tr>
                <td>
                  <strong>${displayName}</strong>
                  ${showOriginal ? `<br><small class="text-muted">${originalName}</small>` : ''}
                  ${it.numero_serie ? `<br><small class="text-muted">S/N: ${it.numero_serie}</small>` : ''}
                  ${it.id_fisico ? `<br><small class="text-muted">ID: ${it.id_fisico}</small>` : ''}
                  ${detailsHtml}
                </td>
                <td><span class="badge ${it.tipo_insumo === 'Varios' ? 'bg-info' : 'bg-primary'}">${it.tipo_insumo}</span></td>
                <td><span class="badge bg-dark">${asignados}</span></td>
                <td><span class="badge bg-success">${devueltos}</span></td>
                <td><span class="badge ${pendientes > 0 ? 'bg-warning' : 'bg-secondary'}">${pendientes}</span></td>
              </tr>
            `);
                    });
                }
                body.innerHTML = rows.join('') || '<tr><td colspan="5" class="text-center text-muted">Sin ítems</td></tr>';
            })
            .catch(err => {
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = err.message;
                alertBox.style.display = 'block';
            });
        modal.show();
    }
</script>

<!-- Modal Anulación de Remito -->
<div class="modal fade" id="modalAnulacion" tabindex="-1" aria-labelledby="modalAnulacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnulacionLabel">
                    <i class="fas fa-times-circle me-2"></i>Anular Remito
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="remitoAnular">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¿Está seguro de anular el remito <span id="numeroRemitoAnular"></span>?</strong>
                    <p class="mb-0 mt-2 small">Se revertirán los estados de los insumos y el remito quedará marcado como
                        anulado. Esta acción no se puede deshacer.</p>
                </div>
                <div class="mb-3">
                    <label for="motivoAnulacion" class="form-label">Motivo de anulación <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control" id="motivoAnulacion" rows="4" required
                        placeholder="Ingrese el motivo por el cual se anula este remito..."></textarea>
                    <small class="form-text text-muted">El motivo será registrado en el historial del remito.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarAnulacion()">
                    <i class="fas fa-ban me-1"></i>Anular Remito
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transferencia de Insumos -->
<div class="modal fade" id="modalTransferencia" tabindex="-1" aria-labelledby="modalTransferenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTransferenciaLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Transferencia de Insumos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">

                <!-- Paso 1: Buscar persona origen -->
                <div id="transf-paso1">
                    <p class="text-muted mb-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Busque la persona cuyos insumos desea transferir. Se listarán todos sus insumos asignados activos.
                    </p>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="transfBuscarPersona"
                            placeholder="Escriba nombre o apellido (mín. 2 caracteres)..." autocomplete="off">
                    </div>
                    <div id="transfResultadosBusqueda"></div>
                </div>

                <!-- Paso 2: Selección de insumos y datos del destino -->
                <div id="transf-paso2" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2 text-muted"></i>
                            Insumos asignados a: <strong id="transfNombreOrigen"></strong>
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" id="btnTransfVolver">
                            <i class="fas fa-arrow-left me-1"></i>Cambiar persona
                        </button>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" id="transfChkAll" class="form-check-input">
                        <label for="transfChkAll" class="form-check-label small text-muted">Seleccionar / deseleccionar todos</label>
                    </div>
                    <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                        <table class="table table-sm table-striped w-100 align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><i class="fas fa-check text-muted"></i></th>
                                    <th>Insumo</th>
                                    <th>Tipo</th>
                                    <th>Remito</th>
                                    <th>Sede / Área actual</th>
                                    <th style="width:90px;">A transferir</th>
                                </tr>
                            </thead>
                            <tbody id="transfTablaInsumos"></tbody>
                        </table>
                    </div>

                    <hr class="my-3">
                    <h6><i class="fas fa-map-marker-alt me-2 text-muted"></i>Datos del destinatario</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="transfNombreDest" placeholder="Nombre">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="transfApellidoDest" placeholder="Apellido">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Sede <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="transfSedeDest">
                                <option value="">Cargando sedes...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Área <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="transfAreaDest">
                                <option value="">Cargando áreas...</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Observaciones</label>
                            <input type="text" class="form-control form-control-sm" id="transfObservaciones"
                                placeholder="Motivo de la transferencia (opcional)">
                        </div>
                    </div>
                </div>

                <!-- Alerta de resultado -->
                <div id="transfAlerta" class="mt-3" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarTransferencia" style="display:none;">
                    <i class="fas fa-exchange-alt me-1"></i>Confirmar Transferencia
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Estado interno del modal
    var transfOrigen = {}; // { nombre, apellido }

    // Cargar sedes y áreas al abrir el modal
    function cargarSedesYAreas() {
        const base = getAppBase();

        // Sedes: usar modo=sedes del endpoint propio (sin filtro de localidad)
        fetch(base + '/ajax/insumos_por_persona.php?modo=sedes')
            .then(r => r.json())
            .then(resp => {
                const sedes = (resp.success && Array.isArray(resp.data && resp.data.sedes)) ? resp.data.sedes : [];
                const $sel = document.getElementById('transfSedeDest');
                $sel.innerHTML = '<option value="">-- Seleccione sede --</option>';
                sedes.forEach(s => {
                    $sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
                });
                if (!sedes.length) $sel.innerHTML += '<option disabled>Sin sedes disponibles</option>';
            }).catch(() => {});

        // Áreas: areas_listar devuelve array directo en resp.data
        fetch(base + '/ajax/areas_listar.php')
            .then(r => r.json())
            .then(resp => {
                // areas_listar.php usa json_success($areas) → resp.data es el array
                const areas = Array.isArray(resp.data) ? resp.data : [];
                const $sel = document.getElementById('transfAreaDest');
                $sel.innerHTML = '<option value="">-- Seleccione área --</option>';
                areas.forEach(a => {
                    // areas_listar devuelve { id, nombre }
                    $sel.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
                });
                if (!areas.length) $sel.innerHTML += '<option disabled>Sin áreas disponibles</option>';
            }).catch(() => {});
    }

    // Abrir modal
    document.getElementById('btnAbrirTransferencia') && document.getElementById('btnAbrirTransferencia').addEventListener('click', function () {
        // Reset
        document.getElementById('transfBuscarPersona').value = '';
        document.getElementById('transfResultadosBusqueda').innerHTML = '';
        document.getElementById('transfTablaInsumos').innerHTML = '';
        document.getElementById('transfAlerta').style.display = 'none';
        document.getElementById('transfNombreDest').value = '';
        document.getElementById('transfApellidoDest').value = '';
        document.getElementById('transfObservaciones').value = '';
        document.getElementById('transf-paso1').style.display = '';
        document.getElementById('transf-paso2').style.display = 'none';
        document.getElementById('btnConfirmarTransferencia').style.display = 'none';
        transfOrigen = {};
        cargarSedesYAreas();
        new bootstrap.Modal(document.getElementById('modalTransferencia')).show();
    });

    // Buscar persona con debounce
    var buscarTimer;
    document.getElementById('transfBuscarPersona').addEventListener('input', function () {
        clearTimeout(buscarTimer);
        const q = this.value.trim();
        const $res = document.getElementById('transfResultadosBusqueda');
        if (q.length < 2) { $res.innerHTML = ''; return; }
        $res.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Buscando...</div>';
        buscarTimer = setTimeout(function () {
            fetch(getAppBase() + '/ajax/insumos_por_persona.php?modo=buscar&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(resp => {
                    if (!resp.success) throw new Error(resp.error || 'Error');
                    const personas = resp.data.personas || [];
                    if (!personas.length) {
                        $res.innerHTML = '<div class="alert alert-info py-2">No se encontraron personas con insumos asignados.</div>';
                        return;
                    }
                    let html = '<div class="list-group">';
                    personas.forEach(p => {
                        html += `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                            data-nombre="${p.nombre}" data-apellido="${p.apellido}" data-sede="${p.id_sede}" data-area="${p.id_area}">
                            <div>
                                <strong>${p.nombre_completo}</strong>
                                <small class="text-muted d-block">${p.nombre_sede} &mdash; ${p.nombre_area} &mdash; ${p.localidad}</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">${p.total_insumos} insumo(s)</span>
                        </button>`;
                    });
                    html += '</div>';
                    $res.innerHTML = html;
                    // Click en persona
                    $res.querySelectorAll('.list-group-item').forEach(btn => {
                        btn.addEventListener('click', function () {
                            seleccionarPersonaOrigen(this.dataset.nombre, this.dataset.apellido, this.dataset.sede, this.dataset.area);
                        });
                    });
                })
                .catch(err => { $res.innerHTML = `<div class="alert alert-danger py-2">${err.message}</div>`; });
        }, 350);
    });

    // Seleccionar persona origen y cargar sus insumos
    function seleccionarPersonaOrigen(nombre, apellido, idSede, idArea) {
        transfOrigen = { nombre, apellido };
        document.getElementById('transfNombreOrigen').textContent = nombre + ' ' + apellido;
        
        // Pre-completar sede y área del destino (requerimiento: misma que el origen)
        if (idSede) document.getElementById('transfSedeDest').value = idSede;
        if (idArea) document.getElementById('transfAreaDest').value = idArea;

        const $tbody = document.getElementById('transfTablaInsumos');
        $tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Cargando insumos...</td></tr>';
        document.getElementById('transf-paso1').style.display = 'none';
        document.getElementById('transf-paso2').style.display = '';
        document.getElementById('btnConfirmarTransferencia').style.display = '';

        fetch(getAppBase() + '/ajax/insumos_por_persona.php?modo=insumos&nombre=' + encodeURIComponent(nombre) + '&apellido=' + encodeURIComponent(apellido))
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) throw new Error(resp.error || 'Error');
                const insumos = resp.data.insumos || [];
                if (!insumos.length) {
                    $tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin insumos activos</td></tr>';
                    return;
                }
                let rows = '';
                insumos.forEach(i => {
                    const esVarios = i.tipo_insumo === 'Varios';
                    const cantInput = esVarios
                        ? `<input type="number" class="form-control form-control-sm transf-cant" min="1" max="${i.pendiente}" value="${i.pendiente}" data-max="${i.pendiente}" style="width:75px;">`
                        : `<span class="badge bg-secondary">1</span>`;
                    const identif = i.numero_serie ? `<small class="text-muted d-block">S/N: ${i.numero_serie}</small>`
                        : (i.id_fisico ? `<small class="text-muted d-block">ID: ${i.id_fisico}</small>` : '');
                    rows += `<tr>
                        <td><input type="checkbox" class="form-check-input chk-transf" checked
                            data-id="${i.id_insumo}" data-remito="${i.id_remito}" data-max="${i.pendiente}"></td>
                        <td><strong>${i.nombre_insumo || i.tipo_insumo}</strong>${identif}</td>
                        <td><span class="badge ${esVarios ? 'bg-info' : 'bg-primary'}">${i.tipo_insumo}</span></td>
                        <td><small>${i.numero_remito}</small></td>
                        <td><small>${i.sede}<br><span class="text-muted">${i.area}</span></small></td>
                        <td>${cantInput}</td>
                    </tr>`;
                });
                $tbody.innerHTML = rows;
            })
            .catch(err => {
                $tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${err.message}</td></tr>`;
            });
    }

    // Volver al paso 1
    document.getElementById('btnTransfVolver').addEventListener('click', function () {
        document.getElementById('transf-paso1').style.display = '';
        document.getElementById('transf-paso2').style.display = 'none';
        document.getElementById('btnConfirmarTransferencia').style.display = 'none';
        document.getElementById('transfAlerta').style.display = 'none';
    });

    // Seleccionar todos
    document.getElementById('transfChkAll').addEventListener('change', function () {
        document.querySelectorAll('#transfTablaInsumos .chk-transf').forEach(c => { c.checked = this.checked; });
    });

    // Confirmar transferencia
    document.getElementById('btnConfirmarTransferencia').addEventListener('click', function () {
        const $alerta = document.getElementById('transfAlerta');
        $alerta.style.display = 'none';

        // Recolectar insumos seleccionados
        const items = [];
        document.querySelectorAll('#transfTablaInsumos .chk-transf:checked').forEach(chk => {
            const id    = parseInt(chk.dataset.id, 10);
            const remito= parseInt(chk.dataset.remito, 10);
            const cantEl= chk.closest('tr').querySelector('.transf-cant');
            const cant  = cantEl ? Math.max(1, Math.min(parseInt(cantEl.value || '1'), parseInt(chk.dataset.max || '1'))) : 1;
            items.push({ id_insumo: id, id_remito: remito, cantidad: cant });
        });

        if (!items.length) {
            $alerta.className = 'alert alert-warning'; $alerta.textContent = 'Seleccione al menos un insumo.';
            $alerta.style.display = ''; return;
        }

        const nombre   = document.getElementById('transfNombreDest').value.trim();
        const apellido = document.getElementById('transfApellidoDest').value.trim();
        const sede     = document.getElementById('transfSedeDest').value;
        const area     = document.getElementById('transfAreaDest').value;

        if (!nombre || !apellido || !sede || !area) {
            $alerta.className = 'alert alert-warning'; $alerta.textContent = 'Complete todos los datos del destino.';
            $alerta.style.display = ''; return;
        }

        showConfirm({
            titulo: 'Confirmar Transferencia',
            mensaje: `¿Transferir ${items.length} insumo(s) de <strong>${transfOrigen.nombre} ${transfOrigen.apellido}</strong> a <strong>${nombre} ${apellido}</strong>?`,
            icono: 'fa-exchange-alt text-warning',
            onConfirm: function () {
                const btn = document.getElementById('btnConfirmarTransferencia');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';

                const token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                fetch(getAppBase() + '/ajax/transferir_insumos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify({
                        nombre_origen   : transfOrigen.nombre,
                        apellido_origen : transfOrigen.apellido,
                        nombre_destino  : nombre,
                        apellido_destino: apellido,
                        id_sede_destino : parseInt(sede, 10),
                        id_area_destino : parseInt(area, 10),
                        observaciones   : document.getElementById('transfObservaciones').value.trim(),
                        items           : items
                    })
                })
                .then(r => r.json())
                .then(resp => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Confirmar Transferencia';
                    if (!resp.success) throw new Error(resp.error || 'Error en transferencia');
                    $alerta.className = 'alert alert-success';
                    $alerta.innerHTML = `<i class="fas fa-check-circle me-1"></i><strong>Transferencia exitosa.</strong> Remito generado: <strong>${resp.data.numero_remito}</strong> (${resp.data.total_transferidos} insumo(s) transferidos).`;
                    $alerta.style.display = '';
                    document.getElementById('btnConfirmarTransferencia').style.display = 'none';
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('modalTransferencia')).hide();
                        try { $('#tablaAsignaciones').DataTable().ajax.reload(); } catch(e) { location.reload(); }
                    }, 2500);
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Confirmar Transferencia';
                    $alerta.className = 'alert alert-danger';
                    $alerta.textContent = err.message;
                    $alerta.style.display = '';
                });
            }
        });
    });
})();
</script>
<!-- Modal Exportar Excel -->
<div class="modal fade" id="modalExportarExcel" tabindex="-1" aria-labelledby="modalExportarExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="modalExportarExcelLabel">
                    <i class="fas fa-file-excel me-2"></i>Exportar a Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <p class="mb-3 fw-semibold">Seleccione el alcance de la exportación:</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success" id="btnExportarFiltrado">
                        <i class="fas fa-filter me-2"></i>Exportar Filtrado
                    </button>
                    <button type="button" class="btn btn-success" id="btnExportarCompleto">
                        <i class="fas fa-list me-2"></i>Exportar Completo
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    $(document).on('click', '.btn-subir-remito', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const numero = $(this).data('numero');
        const hasFile = $(this).data('has-file') == '1';
        
        const modalEl = document.getElementById('modalSubirRemito');
        if (!modalEl) return;

        document.getElementById('id_remito_firmado').value = id;
        document.getElementById('modalSubirRemitoLabel').innerHTML = (hasFile ? '<i class="fas fa-sync me-2"></i>Reemplazar' : '<i class="fas fa-upload me-2"></i>Adjuntar') + ' Remito Firmado: ' + numero;
        
        // Mostrar/Ocultar botón de eliminar
        document.getElementById('btnEliminarRemitoFirmado').style.display = hasFile ? 'block' : 'none';
        
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // Manejar eliminación
    $(document).on('click', '#btnEliminarRemitoFirmado', function() {
        const id = document.getElementById('id_remito_firmado').value;
        if (!id) return;

        showConfirm({
            titulo: 'Eliminar Remito Firmado',
            mensaje: '¿Está seguro de que desea eliminar el documento escaneado? Esta acción no se puede deshacer.',
            claseBoton: 'btn-danger',
            textoAceptar: 'Eliminar',
            onConfirm: () => {
                const btn = document.getElementById('btnEliminarRemitoFirmado');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch(getAppBase() + '/ajax/asignacion_eliminar_firmado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_remito=${id}&_csrf=${(document.querySelector('meta[name="csrf-token"]') || {}).content || ''}`
                })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i>Eliminar Actual';
                    if (res.success) {
                        showToast('Documento eliminado correctamente', 'success');
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSubirRemito')).hide();
                        try { $('#tablaAsignaciones').DataTable().ajax.reload(null, false); } catch(e) { location.reload(); }
                    } else {
                        showToast(res.error || 'Error al eliminar', 'error');
                    }
                });
            }
        });
    });

    // Manejar envío del formulario
    $(document).on('submit', '#formSubirRemito', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarRemitoFirmado');
        const file = document.getElementById('archivo_remito_firmado');
        
        if (!file.files.length) { showToast('Seleccione un archivo', 'warning'); return; }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
        
        const fd = new FormData(this);
        fd.append('_csrf', (document.querySelector('meta[name="csrf-token"]') || {}).content || '');

        fetch(getAppBase() + '/ajax/asignacion_subir_firmado.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Subir Archivo';
            if (res.success) {
                showToast('Remito firmado guardado correctamente', 'success');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSubirRemito')).hide();
                this.reset();
                try { $('#tablaAsignaciones').DataTable().ajax.reload(null, false); } catch(e) { location.reload(); }
            } else { showToast(res.error || 'Error al subir', 'error'); }
        }).catch(e => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Subir Archivo';
            showToast('Error de conexión', 'error');
        });
    });

    $(function () {
        // Inicializar estado desde URL o por defecto 'Activa'
        const urlParams = new URLSearchParams(window.location.search);
        const estadoInicial = urlParams.get('estado') !== null ? urlParams.get('estado') : 'Activa';

        // Establecer tab activo y valor inicial
        $('#estado').val(estadoInicial);
        $(`#tabsEstado a[data-estado="${estadoInicial}"]`).addClass('active').parent().siblings().find('a').removeClass('active');

        var $t = $('#tablaAsignaciones');
        if ($.fn && $.fn.DataTable && $t.length) {
            var dt = $t.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: getAppBase() + '/ajax/asignaciones_list_ssp.php',
                    type: 'GET',
                    data: function (d) {
                        d.localidad = $('#localidad').val() || '';
                        d.insumo = $('#insumo').val() || '';
                        d.estado = $('#estado').val() || '';
                        d.area = $('#area').val() || '';
                        d.sede = $('#sede').val() || '';
                        // Si hay filtro por remito en la URL, aplicarlo
                        try {
                            const url = new URL(window.location.href);
                            const rem = url.searchParams.get('remito');
                            if (rem) { d.remito = rem; }
                        } catch (e) { }
                    }
                },
                order: [[$t.data('default-order-col') || 2, 'desc']],
                pageLength: 25,
                columns: [
                    { data: 0 },
                    { data: 1 },
                    { data: 2 },
                    { data: 3, orderable: true },
                    { data: 4, orderable: false, searchable: false }
                ],
                drawCallback: function () { inicializarTooltips(); }
            });
        }

        // Manejo de clicks en tabs
        $('#tabsEstado a').on('click', function (e) {
            e.preventDefault();
            $('#tabsEstado a').removeClass('active');
            $(this).addClass('active');

            const nuevoEstado = $(this).data('estado');
            $('#estado').val(nuevoEstado);
            $('#tablaAsignaciones').DataTable().ajax.reload();
        });

        // Carga dinámica de sedes
        $('#localidad').on('change', function () {
            const idLocalidad = $(this).val();
            const $sedeSelect = $('#sede');

            $sedeSelect.empty().append('<option value="">Todas las sedes</option>');

            if (idLocalidad) {
                $sedeSelect.prop('disabled', true).append('<option value="" selected>Cargando...</option>');

                fetch(getAppBase() + '/ajax/cargar_sedes.php?localidad_id=' + idLocalidad)
                    .then(response => response.json())
                    .then(resp => {
                        $sedeSelect.empty().append('<option value="">Todas las sedes</option>');
                        const sedes = (resp.data && resp.data.sedes) ? resp.data.sedes : (resp.sedes || []);

                        if (sedes && sedes.length > 0) {
                            sedes.forEach(sede => {
                                $sedeSelect.append(`<option value="${sede.id}">${sede.nombre}</option>`);
                            });
                            $sedeSelect.prop('disabled', false);
                        } else {
                            $sedeSelect.append('<option value="" disabled>No hay sedes</option>');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        $sedeSelect.empty().append('<option value="">Error al cargar</option>');
                    });
            } else {
                $sedeSelect.prop('disabled', true).append('<option value="">Seleccione Localidad primero</option>');
            }
        });

        // Reaplicar con filtros
        $('form').on('submit', function (e) { e.preventDefault(); $('#tablaAsignaciones').DataTable().ajax.reload(); });

        // Abrir PDF si viene imprimir=<remito>
        try {
            const url = new URL(window.location.href);
            const imp = url.searchParams.get('imprimir');
            if (imp) {
                const base = (typeof getAppBase === 'function') ? getAppBase() : '';
                const win = window.open('', 'remitoPrint');
                if (win) {
                    win.location = `${base}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(imp)}`;
                } else {
                    window.open(`${base}/pages/reportes/remito_pdf.php?remito=${encodeURIComponent(imp)}`, '_blank');
                }
                url.searchParams.delete('imprimir');
                window.history.replaceState({}, document.title, url.toString());
            }
        } catch (e) { }

        // Manejar exportación Excel
        $('#btnExportarFiltrado').off('click').on('click', function() { iniciarExportacionExcel('filtrado'); });
        $('#btnExportarCompleto').off('click').on('click', function() { iniciarExportacionExcel('completo'); });

        function iniciarExportacionExcel(tipo) {
            const $btnFiltrado = $('#btnExportarFiltrado');
            const $btnCompleto = $('#btnExportarCompleto');
            
            $btnFiltrado.prop('disabled', true);
            $btnCompleto.prop('disabled', true);
            
            let originalTextFiltrado = $btnFiltrado.html();
            let originalTextCompleto = $btnCompleto.html();
            
            if (tipo === 'filtrado') {
                $btnFiltrado.html('<i class="fas fa-spinner fa-spin me-2"></i>Exportando...');
            } else {
                $btnCompleto.html('<i class="fas fa-spinner fa-spin me-2"></i>Exportando...');
            }
            
            let params = {
                draw: 1,
                start: 0,
                length: 1000000
            };
            
            if (tipo === 'filtrado') {
                params.localidad = $('#localidad').val() || '';
                params.insumo = $('#insumo').val() || '';
                params.estado = $('#estado').val() || '';
                params.area = $('#area').val() || '';
                params.sede = $('#sede').val() || '';
                
                const dt = $('#tablaAsignaciones').DataTable();
                if (dt && dt.search()) {
                    params.search = { value: dt.search() };
                }
                
                try {
                    const url = new URL(window.location.href);
                    const rem = url.searchParams.get('remito');
                    if (rem) { params.remito = rem; }
                } catch (e) { }
            } else {
                params.localidad = '';
                params.insumo = '';
                params.estado = '';
                params.area = '';
                params.sede = '';
            }
            
            $.ajax({
                url: getAppBase() + '/ajax/asignaciones_list_ssp.php',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    if (response && response.data) {
                        generarArchivoExcel(response.data, tipo);
                        $('#modalExportarExcel').modal('hide');
                    } else {
                        showToast('Error al obtener los datos para la exportación', 'error');
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    showToast('Error de red al exportar los datos', 'error');
                },
                complete: function() {
                    $btnFiltrado.prop('disabled', false).html(originalTextFiltrado);
                    $btnCompleto.prop('disabled', false).html(originalTextCompleto);
                }
            });
        }

        function generarArchivoExcel(dataRows, tipo) {
            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>Persona Asignada</th>
                        <th>Localidad</th>
                        <th>Fecha Asignación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
            const tbody = table.querySelector('tbody');
            
            dataRows.forEach(row => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = row[3] || '';
                const estadoTexto = tempDiv.textContent || tempDiv.innerText || '';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row[0]}</td>
                    <td>${row[1]}</td>
                    <td>${row[2]}</td>
                    <td>${estadoTexto}</td>
                `;
                tbody.appendChild(tr);
            });
            
            const nombreArchivo = `asignaciones_${tipo}_${new Date().toISOString().split('T')[0]}.xlsx`;
            const wb = XLSX.utils.table_to_book(table, {sheet: "Asignaciones"});
            XLSX.writeFile(wb, nombreArchivo);
        }
    });
</script>