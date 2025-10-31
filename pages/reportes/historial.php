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
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="anulados-tab" data-bs-toggle="tab" data-bs-target="#anulados" type="button" role="tab" aria-controls="anulados" aria-selected="false">Anulados</button>
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
  <div class="tab-pane fade" id="devoluciones" role="tabpanel" aria-labelledby="devoluciones-tab">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-undo me-2"></i>Devoluciones por Remito</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>Listado agrupado por remito. Cada fila representa un remito con devoluciones.
        </div>
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tablaDevoluciones" data-ssp="1">
            <thead>
              <tr>
                <th>Remito</th>
                <th>Fecha Asignación</th>
                <th>Fecha Devolución</th>
                <th>Persona</th>
                <th>N° Items</th>
                <th>Tot. Asignados</th>
                <th>Tot. Devueltos</th>
                <th>Tot. Pendientes</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-success" onclick="exportarExcelSinColumnas('tablaDevoluciones', 'devoluciones', [0])">
            <i class="fas fa-file-excel me-2"></i>Exportar Excel
          </button>
          <button type="button" class="btn btn-secondary" onclick="imprimirTablaSinColumnas('tablaDevoluciones', 'devoluciones', [0])">
            <i class="fas fa-print me-2"></i>Imprimir
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane fade" id="anulados" role="tabpanel" aria-labelledby="anulados-tab">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Remitos Anulados</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>Los remitos anulados se conservan en el sistema para mantener el historial completo. La numeración continúa sin reutilizar números de remitos anulados.
        </div>
        <div class="table-responsive">
          <table class="table table-striped datatable" id="tablaAnulados" data-ssp="1">
            <thead>
              <tr>
                <th>Remito</th>
                <th>Fecha Asignación</th>
                <th>Fecha Anulación</th>
                <th>Persona</th>
                <th>Sede</th>
                <th>Área</th>
                <th>Motivo</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-success" onclick="exportarExcelSinColumnas('tablaAnulados', 'remitos_anulados', [0])">
            <i class="fas fa-file-excel me-2"></i>Exportar Excel
          </button>
          <button type="button" class="btn btn-secondary" onclick="imprimirTablaSinColumnas('tablaAnulados', 'remitos_anulados', [0])">
            <i class="fas fa-print me-2"></i>Imprimir
          </button>
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
        { data: 4 }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });

    var dtDev = $('#tablaDevoluciones').DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: getAppBase() + '/ajax/historial_devoluciones_ssp.php', type: 'GET' },
      order: [[2, 'desc']], // Ordenar por fecha de devolución DESC
      pageLength: 25,
      columns: [
        { data: 0 }, // Remito
        { data: 1 }, // Fecha Asignación
        { data: 2 }, // Fecha Devolución
        { data: 3 }, // Persona
        { data: 4 }, // N° Items
        { data: 5 }, // Tot. Asignados
        { data: 6 }, // Tot. Devueltos
        { data: 7 }, // Tot. Pendientes
        { data: 8, orderable: false, searchable: false } // Acciones
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });

    var dtAnulados = $('#tablaAnulados').DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: getAppBase() + '/ajax/remitos_anulados_ssp.php', type: 'GET' },
      order: [[2, 'desc']],
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7, orderable: false, searchable: false }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });

    // Ajustar columnas al cambiar de pestaña (DataTables en tabs ocultos)
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e){
      var target = $(e.target).attr('data-bs-target');
      if (target === '#devoluciones') {
        try { dtDev.columns.adjust().responsive?.recalc?.(); } catch(e){}
      } else if (target === '#anulados') {
        try { dtAnulados.columns.adjust().responsive?.recalc?.(); } catch(e){}
      }
    });
  }
});
</script>

<!-- Modal Remito -->
<div class="modal fade" id="modalRemitoResumen" tabindex="-1" aria-labelledby="modalRemitoResumenLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRemitoResumenLabel"><i class="fas fa-file-alt me-2"></i>Detalle de Remito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="remitoResumenBody">
        <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
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

function mostrarRemitoResumen(numeroRemito) {
  $('#modalRemitoResumen').modal('show');
  $('#remitoResumenBody').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
  $.get(getAppBase() + '/ajax/remito_detalle.php', { remito: numeroRemito }, function(resp) {
    if (!resp || !resp.success) {
      $('#remitoResumenBody').html('<div class="alert alert-warning">No se pudo cargar el remito.</div>');
      return;
    }
    var c = resp.cab;
    var items = resp.items || [];
    
    // Información del remito
    var html = '<div class="mb-3">';
    html += '<h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información del Remito</h6>';
    html += '<div class="row">';
    html += '<div class="col-md-6 mb-2"><strong>Número:</strong> ' + c.numero_remito + '</div>';
    html += '<div class="col-md-6 mb-2"><strong>Fecha Asignación:</strong> ' + (c.fecha_asignacion ? c.fecha_asignacion.substr(0,10) : '-') + '</div>';
    html += '<div class="col-md-6 mb-2"><strong>Persona:</strong> ' + (c.nombre_persona_asignada || '') + ' ' + (c.apellido_persona_asignada || '') + '</div>';
    html += '<div class="col-md-6 mb-2"><strong>Estado:</strong> <span class="badge bg-' + (c.estado === 'Activa' ? 'success' : c.estado === 'Devuelta' ? 'secondary' : 'danger') + '">' + (c.estado || '-') + '</span></div>';
    html += '<div class="col-md-6 mb-2"><strong>Sede:</strong> ' + (c.nombre_sede || '-') + '</div>';
    html += '<div class="col-md-6 mb-2"><strong>Área:</strong> ' + (c.nombre_area || '-') + '</div>';
    if (c.observaciones) {
      html += '<div class="col-12 mb-2"><strong>Observaciones:</strong> ' + (c.observaciones || '-') + '</div>';
    }
    html += '</div>';
    html += '</div>';
    
    // Información de anulación si aplica
    if (c.estado === 'Anulado' && c.motivo_anulacion) {
      html += '<div class="alert alert-warning">';
      html += '<strong><i class="fas fa-exclamation-triangle me-2"></i>Remito Anulado</strong><br>';
      html += '<strong>Motivo:</strong> ' + (c.motivo_anulacion || '-') + '<br>';
      if (c.fecha_anulacion) {
        html += '<small class="text-muted"><strong>Fecha de Anulación:</strong> ' + c.fecha_anulacion + '</small>';
      }
      html += '</div>';
    }
    
    // Información de devolución si aplica
    if (c.estado === 'Devuelta' && c.fecha_devolucion) {
      html += '<div class="alert alert-info">';
      html += '<strong><i class="fas fa-info-circle me-2"></i>Devolución</strong><br>';
      html += '<strong>Fecha de Devolución:</strong> ' + c.fecha_devolucion.substr(0,10);
      html += '</div>';
    }
    
    // Insumos del remito
    html += '<div class="mt-3">';
    html += '<h6 class="mb-3"><i class="fas fa-box me-2"></i>Insumos del Remito (' + items.length + ')</h6>';
    
    if (items.length > 0) {
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-striped align-middle">';
      html += '<thead>';
      html += '<tr>';
      html += '<th>Insumo</th>';
      html += '<th>Tipo</th>';
      html += '<th class="text-center">Cantidad</th>';
      html += '<th class="text-center">Devueltos</th>';
      html += '<th>N° Serie / ID</th>';
      html += '</tr>';
      html += '</thead>';
      html += '<tbody>';
      
      items.forEach(function(item) {
        var info = buildInsumoDisplay(item);
        var displayName = info.display;
        var originalName = info.original;
        var showOriginal = safeTrim(displayName) !== safeTrim(originalName);
        var cantidadDev = parseInt(item.cantidad_devuelta) || 0;
        var cantidad = parseInt(item.cantidad) || 0;
        var pendiente = cantidad - cantidadDev;
        var serie = item.numero_serie || item.id_fisico || '-';
        var isVarios = item.tipo_insumo === 'Varios';
        
        html += '<tr>';
        html += '<td>';
        html += '<strong>' + displayName + '</strong>';
        if (showOriginal) {
          html += '<br><small class="text-muted">' + originalName + '</small>';
        }
        if (item.numero_serie) {
          html += '<br><small class="text-muted">S/N: ' + item.numero_serie + '</small>';
        }
        if (item.id_fisico && !item.numero_serie) {
          html += '<br><small class="text-muted">ID: ' + item.id_fisico + '</small>';
        }
        html += '</td>';
        html += '<td><span class="badge ' + (isVarios ? 'bg-info' : 'bg-primary') + '">' + (item.tipo_insumo || '-') + '</span></td>';
        html += '<td class="text-center"><span class="badge bg-dark">' + cantidad + '</span></td>';
        html += '<td class="text-center">';
        if (cantidadDev > 0) {
          html += '<span class="badge bg-success">' + cantidadDev + '</span>';
          if (pendiente > 0) {
            html += ' <span class="badge bg-warning text-dark">' + pendiente + ' pend.</span>';
          }
        } else {
          html += '<span class="text-muted">-</span>';
        }
        html += '</td>';
        html += '<td><small class="text-muted">' + serie + '</small></td>';
        html += '</tr>';
      });
      
      html += '</tbody>';
      html += '</table>';
      html += '</div>';
    } else {
      html += '<div class="text-center text-muted py-3">No hay insumos registrados</div>';
    }
    
    html += '</div>';
    
    $('#remitoResumenBody').html(html);
  }, 'json').fail(function() {
    $('#remitoResumenBody').html('<div class="alert alert-danger">Error al cargar el remito.</div>');
  });
}
</script>

