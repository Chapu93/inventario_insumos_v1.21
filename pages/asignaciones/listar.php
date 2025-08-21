<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Filtros
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_insumo = isset($_GET['insumo']) ? $_GET['insumo'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_area = isset($_GET['area']) ? $_GET['area'] : '';

// Consulta agrupada por remito (esquema nuevo)
$sql = "SELECT 
            r.numero_remito,
            r.fecha_asignacion,
            r.nombre_persona_asignada,
            r.apellido_persona_asignada,
            ar.nombre_area,
            s.nombre_sede,
            l.nombre_localidad,
            SUM(d.cantidad) AS cantidad_insumos,
            SUM(CASE WHEN i.estado = 'Asignado' THEN d.cantidad ELSE 0 END) AS activas,
            GROUP_CONCAT(DISTINCT i.nombre_insumo ORDER BY i.nombre_insumo SEPARATOR ', ') AS insumos
        FROM remitos r 
        JOIN remitos_detalle d ON d.id_remito = r.id_remito
        JOIN insumos i ON d.id_insumo = i.id_insumo 
        JOIN areas ar ON r.id_area = ar.id_area 
        JOIN sedes s ON r.id_sede = s.id_sede 
        JOIN localidades l ON s.id_localidad = l.id_localidad 
        WHERE 1=1";

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

$sql .= " ORDER BY r.fecha_asignacion DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$asignaciones = $stmt->fetchAll();

// Datos para filtros
$localidades = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$tipos_insumo = $conexion->query("SELECT DISTINCT tipo_insumo FROM insumos ORDER BY tipo_insumo")->fetchAll();
$areas = $conexion->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area")->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-handshake me-2"></i>Gestión de Asignaciones
            </h1>
            <a href="nueva.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Asignación
            </a>
        </div>
    </div>
</div>

<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>" 
                            <?php echo $filtro_localidad == $loc['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $loc['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="insumo" class="form-label">Tipo de Insumo</label>
            <select name="insumo" id="insumo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_insumo as $tipo): ?>
                    <option value="<?php echo $tipo['tipo_insumo']; ?>" 
                            <?php echo $filtro_insumo == $tipo['tipo_insumo'] ? 'selected' : ''; ?>>
                        <?php echo $tipo['tipo_insumo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="Activa" <?php echo $filtro_estado == 'Activa' ? 'selected' : ''; ?>>Activa</option>
                <option value="Devuelta" <?php echo $filtro_estado == 'Devuelta' ? 'selected' : ''; ?>>Devuelta</option>
            </select>
        </div>
        
        <div class="col-md-2 d-flex align-items-end">
            <div class="d-grid gap-2 w-100">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Limpiar
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
                <table class="table table-striped datatable" id="tablaAsignaciones">
                    <thead>
                        <tr>
                            <th>Persona Asignada</th>
                            <th>Localidad</th>
                            <th>Fecha Asignación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones as $asignacion): ?>
                            <?php 
                                $estado = ((int)$asignacion['activas'] > 0) ? 'Activa' : 'Devuelta';
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($asignacion['nombre_persona_asignada'] . ' ' . $asignacion['apellido_persona_asignada']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($asignacion['nombre_localidad']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?>
                                </td>
                                <td>
                                    <span class="badge estado-<?php echo strtolower($estado); ?>">
                                        <?php echo $estado; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                onclick="abrirVerAsignacion('<?php echo $asignacion['numero_remito']; ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Ver asignación">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                onclick="generarRemitoPDF('<?php echo $asignacion['numero_remito']; ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Imprimir remito">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" onclick="exportarExcel('tablaAsignaciones', 'asignaciones')">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaAsignaciones')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<script>
function cambiarEstadoPorRemito(remito, estado) {
    if (!remito || !estado) return;
    if (confirm(`¿Está seguro que desea cambiar el estado del remito ${remito} a "${estado}"?`)) {
        const base = typeof getAppBase === 'function' ? getAppBase() : '';
        window.location.href = `${base}/pages/asignaciones/cambiar_estado.php?remito=${encodeURIComponent(remito)}&estado=${encodeURIComponent(estado)}`;
    }
}
</script>

<!-- Modal Devolución de Insumos -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDevolucionLabel">
                    <i class="fas fa-undo me-2"></i>Devolver Insumos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="devolucionAlert" style="display:none;"></div>
                <div class="table-responsive">
                    <table class="table table-sm">
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
                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
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

function abrirDevolucion(remito) {
    DEVOLUCION_REM = remito;
    const modal = new bootstrap.Modal(document.getElementById('modalDevolucion'));
    const body = document.getElementById('tablaDevolucionBody');
    const alertBox = document.getElementById('devolucionAlert');
    alertBox.style.display = 'none';
    body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>';
    fetch(`${getAppBase()}/ajax/remito_items.php?remito=${encodeURIComponent(remito)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { throw new Error(data.error || 'Error al cargar items'); }
        const rows = [];
        data.items.forEach(it => {
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
              <td><strong>${it.nombre_insumo}</strong>${it.numero_serie ? `<br><small class=\"text-muted\">S/N: ${it.numero_serie}</small>` : ''}${it.id_fisico ? `<br><small class=\"text-muted\">ID: ${it.id_fisico}</small>` : ''}</td>
              <td><span class="badge ${isVarios ? 'bg-info' : 'bg-primary'}">${it.tipo_insumo}</span></td>
              <td><span class="badge bg-dark">${it.cantidad}</span> ${devueltos > 0 ? `<small class=\"text-muted\">(devueltos: ${devueltos})</small>` : ''}</td>
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

document.getElementById('chkAllDevolver').addEventListener('change', function(){
  document.querySelectorAll('#tablaDevolucionBody .chk-dev').forEach(chk => { chk.checked = this.checked; });
});

document.getElementById('btnConfirmarDevolucion').addEventListener('click', function() {
  const seleccion = [];
  document.querySelectorAll('#tablaDevolucionBody .chk-dev:checked').forEach(chk => {
    const id = parseInt(chk.getAttribute('data-id'), 10);
    const qtyInput = document.querySelector(`#tablaDevolucionBody input[data-id="${id}"]`);
    const cantidad = qtyInput ? Math.max(1, Math.min(parseInt(qtyInput.value || '1', 10), parseInt(qtyInput.getAttribute('data-max') || '1', 10))) : 1;
    seleccion.push({ id_insumo: id, cantidad });
  });
  if (seleccion.length === 0) {
    alert('Debe seleccionar al menos un insumo a devolver');
    return;
  }
  fetch(`${getAppBase()}/ajax/devolver_insumos.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ remito: DEVOLUCION_REM, items: seleccion })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { throw new Error(data.error || 'Error en devolución'); }
    location.reload();
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
<div class="modal fade" id="modalVerAsignacion" tabindex="-1" aria-labelledby="modalVerAsignacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerAsignacionLabel">
                    <i class="fas fa-eye me-2"></i>Detalle de Asignación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
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
      const c = data.cab;
      cab.innerHTML = `
        <div class="row">
          <div class="col-md-6">
            <p class="mb-1"><strong>Remito:</strong> ${c.numero_remito}</p>
            <p class="mb-1"><strong>Fecha:</strong> ${c.fecha_asignacion}</p>
            <p class="mb-1"><strong>Estado:</strong> <span class="badge ${c.estado === 'Activa' ? 'bg-warning' : 'bg-success'}">${c.estado}</span></p>
          </div>
          <div class="col-md-6">
            <p class="mb-1"><strong>Persona:</strong> ${c.nombre_persona_asignada} ${c.apellido_persona_asignada}</p>
            <p class="mb-1"><strong>Área:</strong> ${c.nombre_area}</p>
            <p class="mb-1"><strong>Sede:</strong> ${c.nombre_sede} - ${c.nombre_localidad} (${c.nombre_zona})</p>
          </div>
        </div>
        ${c.observaciones ? `<div class="alert alert-light mt-2">${c.observaciones}</div>` : ''}
      `;
      const rows = [];
      data.items.forEach(it => {
        const asignados = parseInt(it.cantidad || '0', 10);
        const devueltos = parseInt(it.cantidad_devuelta || '0', 10);
        const pendientes = Math.max(0, asignados - devueltos);
        rows.push(`
          <tr>
            <td><strong>${it.nombre_insumo}</strong>${it.numero_serie ? `<br><small class=\"text-muted\">S/N: ${it.numero_serie}</small>` : ''}${it.id_fisico ? `<br><small class=\"text-muted\">ID: ${it.id_fisico}</small>` : ''}</td>
            <td><span class="badge ${it.tipo_insumo === 'Varios' ? 'bg-info' : 'bg-primary'}">${it.tipo_insumo}</span></td>
            <td><span class="badge bg-dark">${asignados}</span></td>
            <td><span class="badge bg-success">${devueltos}</span></td>
            <td><span class="badge ${pendientes > 0 ? 'bg-warning' : 'bg-secondary'}">${pendientes}</span></td>
          </tr>
        `);
      });
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

<?php include '../../includes/footer.php'; ?>