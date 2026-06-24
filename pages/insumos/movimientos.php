<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'ver'); // Ajustar permiso si es necesario
?>
<?php include '../../includes/header.php'; ?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Movimientos de Stock</h1>
  </div>
</div>

<ul class="nav nav-tabs mb-3" id="movimientosTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="movimientos-tab" data-bs-toggle="tab" data-bs-target="#movimientos" type="button" role="tab" aria-controls="movimientos" aria-selected="true">Historial de Movimientos</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="bajas-tab" data-bs-toggle="tab" data-bs-target="#bajas" type="button" role="tab" aria-controls="bajas" aria-selected="false">Bajas</button>
  </li>
</ul>

<div class="tab-content" id="movimientosTabsContent">
  <div class="tab-pane fade show active" id="movimientos" role="tabpanel" aria-labelledby="movimientos-tab">
    <!-- Filtros -->
    <div class="filtros-container">
      <div class="row g-3 align-items-end">
        <div class="col-md-4 col-lg-3">
          <label for="filtroTipoMovimiento" class="form-label fw-bold"><i class="fas fa-filter me-1"></i>Tipo de Movimiento</label>
          <select class="form-select" id="filtroTipoMovimiento">
            <option value="">Todos los movimientos</option>
            <option value="reposicion_oficina">Reposición Oficina</option>
            <option value="devolucion_a_deposito">Devolución a Depósito</option>
            <option value="ajuste_manual">Ajuste Manual</option>
            <option value="ingreso_nuevo">Ingreso Nuevo</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Historial de Movimientos</h5>
      </div>
      <div class="card-body">
        
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tablaMovimientos" data-ssp="1">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Insumo</th>
                <th>Tipo</th>
                <th>Movimiento</th>
                <th>Stock (Antes/Después)</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-success" onclick="exportarExcelSinColumnas('tablaMovimientos', 'movimientos_stock', [])">
            <i class="fas fa-file-excel me-2"></i>Exportar Excel
          </button>
          <button type="button" class="btn btn-secondary" onclick="imprimirTablaSinColumnas('tablaMovimientos', 'movimientos_stock', [])">
            <i class="fas fa-print me-2"></i>Imprimir
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="bajas" role="tabpanel" aria-labelledby="bajas-tab">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-ban me-2"></i>Bajas de Insumos</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tablaBajas" data-ssp="1">
            <thead>
              <tr>
                <th>Fecha Baja</th>
                <th>Insumo</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Observación</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-success" onclick="exportarExcelSinColumnas('tablaBajas', 'bajas', [])">
            <i class="fas fa-file-excel me-2"></i>Exportar Excel
          </button>
          <button type="button" class="btn btn-secondary" onclick="imprimirTablaSinColumnas('tablaBajas', 'bajas', [])">
            <i class="fas fa-print me-2"></i>Imprimir
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para ver detalles de la baja -->
<div class="modal fade" id="modalVerBaja" tabindex="-1" aria-labelledby="modalVerBajaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #fdf2f2; color: #9b1c1c; border-bottom: 1px solid #f8d7da;">
        <h5 class="modal-title fw-bold" id="modalVerBajaLabel">
          <i class="fas fa-eye me-2"></i>Detalle de la Baja
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalVerBajaBody">
        <!-- Se cargará dinámicamente -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(function(){
  if ($.fn && $.fn.DataTable) {
    var dtMovimientos = $('#tablaMovimientos').DataTable({
      processing: true,
      serverSide: true,
      ajax: { 
        url: getAppBase() + '/ajax/historial_movimientos_ssp.php', 
        type: 'GET',
        data: function(d) {
          d.tipo_movimiento = $('#filtroTipoMovimiento').val();
        }
      },
      order: [[0, 'desc']], // Ordenar por fecha DESC
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });

    // Recargar tabla al cambiar filtro
    $('#filtroTipoMovimiento').on('change', function() {
      dtMovimientos.ajax.reload();
    });

    // Tabla de Bajas
    var dtBajas = $('#tablaBajas').DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: getAppBase() + '/ajax/historial_bajas_ssp.php', type: 'GET' },
      order: [[0, 'desc']],
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5, orderable: false, searchable: false }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });

    // Ver detalle de la baja
    $(document).on('click', '.btn-ver-baja', function() {
      var idBaja = $(this).data('id');
      var modal = new bootstrap.Modal(document.getElementById('modalVerBaja'));
      var modalBody = $('#modalVerBajaBody');
      
      modalBody.html('<div class="text-center py-4"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Cargando...</span></div></div>');
      modal.show();
      
      $.ajax({
        url: getAppBase() + '/ajax/obtener_baja_detalle.php',
        method: 'GET',
        data: { id: idBaja },
        dataType: 'json',
        success: function(resp) {
          if (resp.success) {
            var html = resp.html || (resp.data && resp.data.html);
            modalBody.html(html);
          } else {
            modalBody.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + (resp.error || 'Error al cargar los detalles') + '</div>');
          }
        },
        error: function(xhr) {
          modalBody.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Error de comunicación con el servidor.</div>');
        }
      });
    });

    // Ajustar columnas al cambiar de pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e){
      var target = $(e.target).attr('data-bs-target');
      if (target === '#bajas') {
        try { dtBajas.columns.adjust().responsive?.recalc?.(); } catch(e){}
      } else if (target === '#movimientos') {
        try { dtMovimientos.columns.adjust().responsive?.recalc?.(); } catch(e){}
      }
    });
  }
});
</script>
