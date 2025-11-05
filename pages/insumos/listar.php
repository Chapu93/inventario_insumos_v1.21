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

$sql = "SELECT i.*, ps.nombre_punto, ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona, 
               ing.nro_referencia AS ingreso_referencia, ing.tipo_ingreso{$selectBaja}
        FROM insumos i 
        LEFT JOIN puntos_stock ps ON i.id_punto_stock_actual = ps.id_punto_stock 
        LEFT JOIN areas ar ON i.id_area_asignacion_actual = ar.id_area 
        LEFT JOIN sedes s ON i.id_sede_actual = s.id_sede 
        LEFT JOIN localidades l ON s.id_localidad = l.id_localidad 
        LEFT JOIN zonas z ON l.id_zona = z.id_zona 
        LEFT JOIN ingresos ing ON i.id_ingreso = ing.id_ingreso
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

// (Se quitó filtro de localidades en la UI)
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
                <button type="button" class="btn btn-info" id="btnPlanillaRelevamiento">
                    <i class="fas fa-file-pdf me-2"></i>Planilla de Relevamiento
                </button>
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
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="Disponible" <?php echo $filtro_estado == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="Asignado" <?php echo $filtro_estado == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                <option value="De Baja" <?php echo $filtro_estado == 'De Baja' ? 'selected' : ''; ?>>De Baja</option>
            </select>
        </div>
        
        <div class="col-md-3 ms-md-auto d-flex align-items-end">
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
                            <th>Tipo</th>
                            <th>Condición</th>
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
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaInsumos', 'insumos')">
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
                <div class="mb-3" id="bajaCantidadGroup" style="display:none;">
                    <label class="form-label">Cantidad (solo para tipo "Varios")</label>
                    <input type="number" id="bajaCantidad" class="form-control" min="1" step="1" placeholder="1">
                    <div class="form-text" id="bajaCantidadHelp">Si el insumo es de tipo "Varios" puede indicar cuántas unidades dar de baja.</div>
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
                // Si está asignado, deshabilitar baja y guiar al menú de Devoluciones
                if (data.remito_activo_numero) {
                  const bajaBtn = document.getElementById('btnConfirmarBaja');
                  if (bajaBtn) { bajaBtn.disabled = true; }
                  const alerta = document.getElementById('bajaAlert');
                  if (alerta) {
                    alerta.className = 'alert alert-warning';
                    alerta.innerHTML = `Este insumo está asignado (Remito <strong>${data.remito_activo_numero}</strong>). Debe devolverlo desde el menú <a href="${getAppBase()}/pages/asignaciones/listar.php" class="alert-link">Asignaciones</a> antes de darlo de baja.`;
                    alerta.style.display = 'block';
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
// Eliminar insumo (con confirmación y aviso si está asignado)
window.eliminarInsumo = function(id) {
  if (!id) return;
  // Consultar detalle para saber si está asignado y a quién
  fetch(`ver_ajax.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
      let msg = '¿Desea eliminar este insumo de forma permanente? Esta acción no se puede deshacer.';
      if (data && data.remito_activo_numero && data.persona_asignada) {
        msg = `El insumo está asignado a ${data.persona_asignada} (Remito ${data.remito_activo_numero}).\n` +
              'Si confirma, también se eliminará la asignación asociada.\n¿Confirma eliminar?';
      }
      if (!confirm(msg)) return;
      const token = (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
      fetch(`${getAppBase()}/ajax/insumos_eliminar.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
        body: JSON.stringify({ id_insumo: id })
      })
      .then(r => r.json())
      .then(resp => {
        if (!resp.success) { throw new Error(resp.error || 'Error al eliminar'); }
        showToast('Insumo eliminado correctamente', 'success');
        try { $('#tablaInsumos').DataTable().ajax.reload(); } catch(e) { location.reload(); }
      })
      .catch(err => { showToast(err.message || 'Error al eliminar', 'error'); });
    })
    .catch(() => {
      if (!confirm('¿Eliminar este insumo?')) return;
      const token = (document.querySelector('meta[name="csrf-token"]').content)||'';
      fetch(`${getAppBase()}/ajax/insumos_eliminar.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
        body: JSON.stringify({ id_insumo: id })
      }).then(r=>r.json()).then(resp => {
        if (!resp.success) { throw new Error(resp.error || 'Error'); }
        showToast('Insumo eliminado', 'success');
        try { $('#tablaInsumos').DataTable().ajax.reload(); } catch(e) { location.reload(); }
      }).catch(err => showToast(err.message || 'Error', 'error'));
    });
}
</script>

<script>
$(function(){
  // Orden inicial se define vía data-default-order-col/dir y se aplica en footer
});
</script>

<script>
// Delegación para botones renderizados por DataTables
$(document).on('click', '.btn-eliminar-insumo', function(){
  var id = parseInt($(this).data('id'), 10) || 0;
  if (id) { window.eliminarInsumo(id); }
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
    // Reset botón confirmar por si fue deshabilitado en otra apertura
    const confirmBtn = document.getElementById('btnConfirmarBaja');
    if (confirmBtn) { confirmBtn.disabled = false; }
    // Consultar detalles para configurar límite de cantidad si es tipo "Varios"
    fetch(`ver_ajax.php?id=${BAJA_ID}`)
      .then(r => r.json())
      .then(data => {
        const grp = document.getElementById('bajaCantidadGroup');
        const inp = document.getElementById('bajaCantidad');
        const help = document.getElementById('bajaCantidadHelp');
        if (data && data.insumo_tipo === 'Varios') {
          const max = parseInt(data.insumo_cantidad || 1, 10) || 1;
          grp.style.display = '';
          inp.value = Math.min(1, max);
          inp.min = 1;
          inp.max = Math.max(1, max);
          help.textContent = `Disponible: ${max}. Ingrese una cantidad entre 1 y ${max}.`;
        } else {
          grp.style.display = 'none';
          inp.value = '';
        }
        // Si está asignado (y NO es Varios), mostrar aviso y bloquear confirmación
        if (data && data.remito_activo_numero && data.insumo_tipo !== 'Varios') {
          if (confirmBtn) { confirmBtn.disabled = true; }
          alertBox.className = 'alert alert-warning';
          alertBox.innerHTML = `Este insumo está asignado (Remito <strong>${data.remito_activo_numero}</strong>). Debe devolverlo desde Asignaciones.`;
          // Botón para ir a Asignaciones filtrado por remito
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-sm btn-primary ms-2';
          btn.textContent = 'Ir a Devoluciones';
          btn.addEventListener('click', function(){
            window.location.href = `${getAppBase()}/pages/asignaciones/listar.php?remito=${encodeURIComponent(data.remito_activo_numero)}`;
          });
          const wrapper = document.createElement('div');
          wrapper.className = 'mt-2';
          wrapper.appendChild(btn);
          alertBox.appendChild(wrapper);
          alertBox.style.display = 'block';
        }
      })
      .catch(()=>{
        const grp = document.getElementById('bajaCantidadGroup');
        grp.style.display = 'none';
      });
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
    const qtyInput = document.getElementById('bajaCantidad');
    let cantidad = parseInt((qtyInput && qtyInput.value ? qtyInput.value : '1'), 10) || 1;
    if (qtyInput && qtyInput.max) {
        const max = parseInt(qtyInput.max, 10) || 1;
        cantidad = Math.min(Math.max(1, cantidad), max);
    }
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

<!-- Modal Reponer Stock -->
<div class="modal fade" id="modalReponerStock" tabindex="-1" aria-labelledby="modalReponerStockLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalReponerStockLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Reponer Stock a Oficina
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reponerAlert" class="alert alert-info" style="display:none;"></div>
                
                <div class="mb-3">
                    <strong>Insumo:</strong> <span id="reponerNombre"></span>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-warehouse fa-2x text-primary mb-2"></i>
                                <h6 class="card-title">Depósito</h6>
                                <h3 class="text-primary mb-0"><span id="reponerStockDeposito">0</span></h3>
                                <small class="text-muted">unidades</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x text-success mb-2"></i>
                                <h6 class="card-title">Oficina</h6>
                                <h3 class="text-success mb-0"><span id="reponerStockOficina">0</span></h3>
                                <small class="text-muted">unidades</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="reponerCantidad" class="form-label">Cantidad a reponer *</label>
                    <input type="number" class="form-control" id="reponerCantidad" min="1" required>
                    <small class="form-text text-muted">Máximo: <span id="reponerMaximo">0</span> unidades</small>
                </div>
                
                <div class="mb-3">
                    <label for="reponerObservacion" class="form-label">Observación (opcional)</label>
                    <textarea class="form-control" id="reponerObservacion" rows="2" placeholder="Ej: Reposición semanal"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarReponer">
                    <i class="fas fa-exchange-alt me-1"></i>Reponer
                </button>
            </div>
        </div>
    </div>
</div>

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
          d.estado = $('#estado').val() || '';
        }
      },
      order: [[$t.data('default-order-col') || 1, $t.data('default-order-dir') || 'asc']],
      pageLength: 25,
      columns: [
        { data: 0 },  // Nombre
        { data: 1, orderable: true },  // Tipo
        { data: 2, orderable: true },  // Condición
        { data: 3, orderable: true },  // Cantidad
        { data: 4, orderable: false, searchable: false }  // Acciones
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });
  }
  // Reaplicar con filtros
  $('form').on('submit', function(e){ e.preventDefault(); $('#tablaInsumos').DataTable().ajax.reload(); });
  
  // Delegar edición de cantidades para items asignados (tipo Varios)
  $(document).on('click', '.btn-editar-cantidad', function(){
    var remito = $(this).data('remito');
    var id = parseInt($(this).data('id'), 10) || 0;
    if (!remito || !id) return;
    var nueva = prompt('Nueva cantidad para este insumo (Varios):', '1');
    if (!nueva) return;
    nueva = Math.max(1, parseInt(nueva, 10) || 1);
    fetch(`${getAppBase()}/ajax/remito_items_update.php`, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]')||{}).content || '' },
      body: JSON.stringify({ remito, items: [{ id_insumo: id, cantidad: nueva }] })
    }).then(r=>r.json()).then(resp => {
      if (!resp.success) throw new Error(resp.error||'Error al actualizar');
      showToast('Cantidad actualizada', 'success');
      try { $('#tablaInsumos').DataTable().ajax.reload(null,false); } catch(e) { location.reload(); }
    }).catch(err => showToast(err.message||'Error', 'error'));
  });
  
  // Delegación para botón de reponer stock
  $(document).on('click', '.btn-reponer-stock', function(){
    var id = parseInt($(this).data('id'), 10) || 0;
    var nombre = $(this).data('nombre') || 'Insumo';
    var deposito = parseInt($(this).data('deposito'), 10) || 0;
    
    // Obtener stock actual de oficina haciendo una petición al servidor
    fetch(getAppBase() + '/ajax/insumos_por_ids.php?ids=' + id)
      .then(r => r.json())
      .then(resp => {
        if (!resp.success || !resp.data || resp.data.length === 0) {
          showToast('Error al obtener datos del insumo', 'error');
          return;
        }
        var insumo = resp.data[0];
        var oficina = parseInt(insumo.cantidad_oficina || 0, 10);
        
        // Actualizar modal
        $('#reponerNombre').text(nombre);
        $('#reponerStockDeposito').text(deposito);
        $('#reponerStockOficina').text(oficina);
        $('#reponerMaximo').text(deposito);
        $('#reponerCantidad').attr('max', deposito).val(Math.min(deposito, 10));
        $('#reponerObservacion').val('');
        $('#reponerAlert').hide();
        
        // Resetear botón a estado original
        $('#btnConfirmarReponer').prop('disabled', false)
          .html('<i class="fas fa-exchange-alt me-1"></i>Reponer');
        
        // Guardar ID para usar al confirmar
        $('#btnConfirmarReponer').data('id-insumo', id);
        
        // Mostrar modal
        var modal = new bootstrap.Modal(document.getElementById('modalReponerStock'));
        modal.show();
      })
      .catch(err => {
        showToast('Error al cargar datos: ' + err.message, 'error');
      });
  });
  
  // Confirmar reposición
  $('#btnConfirmarReponer').on('click', function(){
    var id = parseInt($(this).data('id-insumo'), 10) || 0;
    var cantidad = parseInt($('#reponerCantidad').val(), 10) || 0;
    var observacion = $('#reponerObservacion').val().trim();
    var maxDeposito = parseInt($('#reponerMaximo').text(), 10) || 0;
    
    // Validaciones
    if (cantidad <= 0) {
      $('#reponerAlert').removeClass('alert-success').addClass('alert-danger')
        .text('La cantidad debe ser mayor a 0').show();
      return;
    }
    if (cantidad > maxDeposito) {
      $('#reponerAlert').removeClass('alert-success').addClass('alert-danger')
        .text('La cantidad excede el stock disponible en depósito').show();
      return;
    }
    
    // Deshabilitar botón
    $(this).prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin me-1\"></i>Reponiendo...');
    
    // Enviar petición
    fetch(getAppBase() + '/ajax/reponer_stock_oficina.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': (document.querySelector('meta[name=\"csrf-token\"]')||{}).content || ''
      },
      body: JSON.stringify({ id_insumo: id, cantidad: cantidad, observacion: observacion })
    })
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) {
        throw new Error(resp.error || 'Error al reponer stock');
      }
      $('#reponerAlert').removeClass('alert-danger').addClass('alert-success')
        .text(resp.mensaje || 'Stock repuesto correctamente').show();
      
      // Recargar tabla y cerrar modal después de 1 segundo
      setTimeout(() => {
        $('#tablaInsumos').DataTable().ajax.reload(null, false);
        bootstrap.Modal.getInstance(document.getElementById('modalReponerStock')).hide();
        showToast('Stock repuesto correctamente', 'success');
      }, 1000);
    })
    .catch(err => {
      $('#reponerAlert').removeClass('alert-success').addClass('alert-danger')
        .text(err.message).show();
      $('#btnConfirmarReponer').prop('disabled', false)
        .html('<i class=\"fas fa-exchange-alt me-1\"></i>Reponer');
    });
  });
  
  // Evento click para el botón de planilla de relevamiento
  $('#btnPlanillaRelevamiento').on('click', function() {
    mostrarOpcionesRelevamiento();
  });
});

// Funciones globales para el modal de relevamiento
function mostrarOpcionesRelevamiento() {
  const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
  const modal = document.createElement('div');
  modal.className = 'modal fade';
  modal.setAttribute('tabindex', '-1');
  modal.setAttribute('id', 'modalRelevamiento');
  modal.innerHTML = '<div class="modal-dialog modal-dialog-centered">' +
    '<div class="modal-content">' +
    '<div class="modal-header">' +
    '<h5 class="modal-title"><i class="fas fa-file-pdf me-2"></i>Planilla de Relevamiento</h5>' +
    '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
    '</div>' +
    '<div class="modal-body">' +
    '<p class="mb-3">Seleccione la cantidad de formularios por hoja:</p>' +
    '<div class="d-grid gap-2">' +
    '<button type="button" class="btn btn-outline-primary" onclick="generarRelevamiento(4)">' +
    '<i class="fas fa-th-large me-2"></i>4 formularios por hoja (2x2)' +
    '<small class="d-block text-muted mt-1">Más espacio, menos cantidad</small>' +
    '</button>' +
    '<button type="button" class="btn btn-outline-primary" onclick="generarRelevamiento(6)">' +
    '<i class="fas fa-th me-2"></i>6 formularios por hoja (3x2)' +
    '<small class="d-block text-muted mt-1">Más cantidad, formato compacto</small>' +
    '</button>' +
    '</div>' +
    '</div>' +
    '</div>' +
    '</div>';
  document.body.appendChild(modal);
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
  modal.addEventListener('hidden.bs.modal', function () {
    document.body.removeChild(modal);
  });
}

// Función para generar el PDF con la cantidad elegida
function generarRelevamiento(cantidad) {
  const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
  const url = baseUrl + '/pages/reportes/relevamientos_pdf.php?cantidad=' + cantidad;
  window.open(url, '_blank');
  // Cerrar el modal
  const modalEl = document.getElementById('modalRelevamiento');
  if (modalEl) {
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
      modal.hide();
    }
  }
}
</script>