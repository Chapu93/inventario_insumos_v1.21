<?php
require_once '../../includes/config.php';
?>
<?php include '../../includes/header.php'; ?>

<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-history me-2"></i>Historial</h1>
    <a class="btn btn-outline-secondary" href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php">
      <i class="fas fa-arrow-left me-1"></i>Volver a Asignaciones
    </a>
  </div>
</div>

<ul class="nav nav-tabs" id="historialTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="bajas-tab" data-bs-toggle="tab" data-bs-target="#bajas" type="button" role="tab" aria-controls="bajas" aria-selected="true">Bajas</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="devoluciones-tab" data-bs-toggle="tab" data-bs-target="#devoluciones" type="button" role="tab" aria-controls="devoluciones" aria-selected="false">Devoluciones</button>
  </li>
</ul>
<div class="tab-content pt-3" id="historialTabsContent">
  <div class="tab-pane fade show active" id="bajas" role="tabpanel" aria-labelledby="bajas-tab">
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
                <th>Remito</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane fade" id="devoluciones" role="tabpanel" aria-labelledby="devoluciones-tab">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-undo me-2"></i>Devoluciones</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tablaDevoluciones" data-ssp="1">
            <thead>
              <tr>
                <th>Remito</th>
                <th>Fecha</th>
                <th>Insumo</th>
                <th>Asignados</th>
                <th>Devueltos</th>
                <th>Pendientes</th>
                <th>Ver Remito</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(function(){
  if ($.fn && $.fn.DataTable) {
    $('#tablaBajas').DataTable({
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

    $('#tablaDevoluciones').DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: getAppBase() + '/ajax/historial_devoluciones_ssp.php', type: 'GET' },
      order: [[1, 'desc']],
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6, orderable: false, searchable: false }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });
  }
});
</script>

<!-- Modal Remito -->
<div class="modal fade" id="modalRemitoResumen" tabindex="-1" aria-labelledby="modalRemitoResumenLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRemitoResumenLabel"><i class="fas fa-file-alt me-2"></i>Resumen de Remito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="remitoResumenBody">
        <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
      </div>
    </div>
  </div>
</div>

<script>
function mostrarRemitoResumen(numeroRemito) {
  $('#modalRemitoResumen').modal('show');
  $('#remitoResumenBody').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
  $.get(getAppBase() + '/ajax/remito_detalle.php', { remito: numeroRemito }, function(resp) {
    if (!resp || !resp.success) {
      $('#remitoResumenBody').html('<div class="alert alert-warning">No se pudo cargar el remito.</div>');
      return;
    }
    var c = resp.cab;
    var html = '<div class="mb-2"><strong>Número:</strong> ' + c.numero_remito + '</div>';
    html += '<div class="mb-2"><strong>Fecha:</strong> ' + (c.fecha_asignacion ? c.fecha_asignacion.substr(0,10) : '-') + '</div>';
    html += '<div class="mb-2"><strong>Persona:</strong> ' + (c.nombre_persona_asignada || '') + ' ' + (c.apellido_persona_asignada || '') + '</div>';
    html += '<div class="mb-2"><strong>Sede:</strong> ' + (c.nombre_sede || '-') + '</div>';
    html += '<div class="mb-2"><strong>Área:</strong> ' + (c.nombre_area || '-') + '</div>';
    html += '<div class="mb-2"><strong>Estado:</strong> ' + (c.estado || '-') + '</div>';
    html += '<div class="mb-2"><strong>Observaciones:</strong> ' + (c.observaciones || '-') + '</div>';
    $('#remitoResumenBody').html(html);
  }, 'json');
}
</script>

