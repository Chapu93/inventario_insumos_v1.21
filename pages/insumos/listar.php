<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Detectar si existe la tabla de bajas
$tieneBajas = false;
try { $conexion->query("SELECT 1 FROM insumos_bajas LIMIT 1"); $tieneBajas = true; } catch (Exception $e) { $tieneBajas = false; }

// Obtener filtros
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$selectBaja = $tieneBajas
    ? ", (SELECT fecha_baja FROM insumos_bajas ib WHERE ib.id_insumo=i.id_insumo ORDER BY fecha_baja DESC LIMIT 1) AS ultima_baja_fecha,
         (SELECT observacion FROM insumos_bajas ib2 WHERE ib2.id_insumo=i.id_insumo ORDER BY fecha_baja DESC LIMIT 1) AS ultima_baja_obs"
    : "";

$sql = "SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona{$selectBaja}
        FROM insumos i 
        LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
        LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area 
        LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
        LEFT JOIN localidades l ON s.id_localidad = l.id_localidad 
        LEFT JOIN zonas z ON l.id_zona = z.id_zona 
        WHERE 1=1";

$params = [];

if ($filtro_tipo) {
    $sql .= " AND i.tipo_insumo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

if ($filtro_estado) {
    $sql .= " AND i.estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY CASE i.estado WHEN 'Disponible' THEN 0 WHEN 'Asignado' THEN 1 ELSE 2 END, i.nombre_insumo ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$insumos = $stmt->fetchAll();

// Obtener datos para filtros
$stmt = $conexion->query("SELECT DISTINCT tipo_insumo FROM insumos ORDER BY tipo_insumo");
$tipos_insumo = $stmt->fetchAll();

$stmt = $conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad");
$localidades = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-boxes me-2"></i>Gestión de Insumos
            </h1>
            <div class="btn-group">
                <a href="agregar.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Agregar Insumo
                </a>
                <a href="agregar_nueva.php" class="btn btn-secondary">
                    <i class="fas fa-plus-square me-2"></i>Agregar Insumo Asignado
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="tipo" class="form-label">Tipo de Insumo</label>
            <select name="tipo" id="tipo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_insumo as $tipo): ?>
                    <option value="<?php echo $tipo['tipo_insumo']; ?>" 
                            <?php echo $filtro_tipo == $tipo['tipo_insumo'] ? 'selected' : ''; ?>>
                        <?php echo $tipo['tipo_insumo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $localidad): ?>
                    <option value="<?php echo $localidad['id_localidad']; ?>" 
                            <?php echo $filtro_localidad == $localidad['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $localidad['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="Disponible" <?php echo $filtro_estado == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="Asignado" <?php echo $filtro_estado == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                <option value="De Baja" <?php echo $filtro_estado == 'De Baja' ? 'selected' : ''; ?>>De Baja</option>
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

<!-- Tabla de insumos -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Insumos (<?php echo count($insumos); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($insumos)): ?>
            <div class="text-center py-4">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron insumos</h5>
                <p class="text-muted">No hay insumos que coincidan con los filtros aplicados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaInsumos" data-default-order-col="1" data-default-order-dir="asc" data-ssp="1">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Botones de exportación -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" onclick="exportarExcel('tablaInsumos', 'insumos')">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaInsumos')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del insumo -->
<div class="modal fade" id="modalVerInsumo" tabindex="-1" aria-labelledby="modalVerInsumoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerInsumoLabel">
                    <i class="fas fa-eye me-2"></i>Detalles del Insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalVerInsumoBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles del insumo...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btnEditarInsumo" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para dar de baja insumo -->
<div class="modal fade" id="modalBajaInsumo" tabindex="-1" aria-labelledby="modalBajaInsumoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBajaInsumoLabel">
                    <i class="fas fa-ban me-2"></i>Dar de baja Insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <p class="mb-1"><strong>Insumo:</strong> <span id="bajaNombreInsumo"></span></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo/Observación</label>
                    <textarea id="bajaObservacion" class="form-control" rows="3" placeholder="Describa el motivo de la baja" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad (solo para tipo "Varios")</label>
                    <input type="number" id="bajaCantidad" class="form-control" min="1" step="1" placeholder="1">
                    <div class="form-text">Si el insumo es de tipo "Varios" puede indicar cuántas unidades dar de baja.</div>
                </div>
                <div id="bajaAlert" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarBaja">
                    <i class="fas fa-check me-2"></i>Confirmar Baja
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para ver detalles del insumo en modal
function verInsumo(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerInsumo'));
    const modalBody = document.getElementById('modalVerInsumoBody');
    const btnEditar = document.getElementById('btnEditarInsumo');
    
    // Mostrar loading
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles del insumo...</p>
        </div>
    `;
    
    modal.show();
    
    // Cargar datos del insumo
    fetch(`ver_ajax.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
                btnEditar.onclick = () => {
                    modal.hide();
                    window.location.href = `editar.php?id=${id}`;
                };
                btnEditar.style.display = 'inline-block';
                // Si el insumo está asignado, ofrecer flujo asistido: devolver y luego baja
                if (data.remito_activo_numero) {
                  const rem = data.remito_activo_numero;
                  const footer = modal._element.querySelector('.modal-footer');
                  if (footer && !footer.querySelector('#btnDevolverYBaja')) {
                    const btn = document.createElement('button');
                    btn.id = 'btnDevolverYBaja';
                    btn.type = 'button';
                    btn.className = 'btn btn-warning';
                    btn.innerHTML = '<i class="fas fa-undo me-2"></i>Devolver y dar de baja';
                    btn.addEventListener('click', function(){
                      // Abrir página de asignaciones con modal de devolución directo
                      const base = getAppBase();
                      window.location.href = `${base}/pages/asignaciones/listar.php?devolver=${encodeURIComponent(rem)}`;
                    });
                    footer.insertBefore(btn, footer.firstChild);
                  }
                }
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles del insumo: ${data.error}
                    </div>
                `;
                btnEditar.style.display = 'none';
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los detalles del insumo: ${error.message}
                </div>
            `;
            btnEditar.style.display = 'none';
        });
}
</script>

<script>
$(function(){
  // Orden inicial se define vía data-default-order-col/dir y se aplica en footer
});
</script>

<script>
let BAJA_ID = 0;
function abrirModalBajaInsumo(id, nombre) {
    BAJA_ID = parseInt(id, 10) || 0;
    document.getElementById('bajaNombreInsumo').textContent = nombre || '';
    document.getElementById('bajaObservacion').value = '';
    const alertBox = document.getElementById('bajaAlert');
    alertBox.style.display = 'none';
    const modal = new bootstrap.Modal(document.getElementById('modalBajaInsumo'));
    modal.show();
}

document.getElementById('btnConfirmarBaja').addEventListener('click', function(){
    const obs = (document.getElementById('bajaObservacion').value || '').trim();
    if (!BAJA_ID) return;
    if (obs.length < 3) {
        const box = document.getElementById('bajaAlert');
        box.className = 'alert alert-warning';
        box.textContent = 'Por favor ingrese un motivo válido (mín. 3 caracteres).';
        box.style.display = 'block';
        return;
    }
    const cantidad = parseInt((document.getElementById('bajaCantidad').value || '1'), 10) || 1;
    fetch(`${getAppBase()}/ajax/insumo_baja.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]')||{}).content || ''
        },
        body: JSON.stringify({ id_insumo: BAJA_ID, observacion: obs, cantidad })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { throw new Error(data.error || 'Error al dar de baja'); }
        const box = document.getElementById('bajaAlert');
        box.className = 'alert alert-success';
        box.textContent = 'Baja registrada correctamente.';
        box.style.display = 'block';
        setTimeout(() => { location.reload(); }, 1200);
    })
    .catch(err => {
        const box = document.getElementById('bajaAlert');
        box.className = 'alert alert-danger';
        box.textContent = err.message;
        box.style.display = 'block';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>

<script>
$(function(){
  var $t = $('#tablaInsumos');
  if ($.fn && $.fn.DataTable && $t.length) {
    $t.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: getAppBase() + '/ajax/insumos_list_ssp.php',
        type: 'GET',
        data: function(d){
          // Enviar filtros actuales
          d.tipo = $('#tipo').val() || '';
          d.localidad = $('#localidad').val() || '';
          d.estado = $('#estado').val() || '';
        }
      },
      order: [[$t.data('default-order-col') || 1, $t.data('default-order-dir') || 'asc']],
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1, orderable: true },
        { data: 2, orderable: true },
        { data: 3, orderable: false, searchable: false }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });
  }
  // Reaplicar con filtros
  $('form').on('submit', function(e){ e.preventDefault(); $('#tablaInsumos').DataTable().ajax.reload(); });
});
</script>