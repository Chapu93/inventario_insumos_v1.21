<?php
require_once '../../includes/config.php';
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-signature me-2"></i>Licitaciones</h1>
    <a class="btn btn-primary" href="licitaciones_nueva.php"><i class="fas fa-plus me-2"></i>Nueva Licitación</a>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaLicitaciones">
        <thead><tr><th>Código Expediente</th><th>Fin</th><th>Insumos</th><th>Acciones</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal removido: creación desde flujo por pasos -->

<script>
const BASE = '<?php echo app_base_url(); ?>';
$(function(){
  // DataTable principal
  $('#tablaLicitaciones').DataTable({
    ajax: { url: BASE + '/ajax/licitaciones_list.php', dataSrc: 'data' },
    columns: [
      { data: 'cod_expediente' },
      { data: 'fecha_finalizacion' },
      { data: 'num_insumos', render: d => `<span class=\"badge bg-primary\">${d||0}</span>` },
      { data: null, orderable:false, searchable:false, render: function(data, type, row){
          return `
            <div class=\"btn-group\">
              <button class=\"btn btn-sm btn-secondary\" onclick=\"verDetalleLic(${row.id_licitacion})\" title=\"Ver\"><i class=\"fas fa-eye\"></i></button>
              <a class=\"btn btn-sm btn-info\" href=\"licitaciones_nueva.php?id=${row.id_licitacion}\" title=\"Editar\"><i class=\"fas fa-edit\"></i></a>
              <button class=\"btn btn-sm btn-danger\" onclick=\"eliminarLic(${row.id_licitacion})\" title=\"Eliminar\"><i class=\"fas fa-trash\"></i></button>
            </div>`;
        } }
    ]
  });

  // Se quita la selección por Select2 en este modal y se guía al flujo por pasos
});

function quitarInsumo(id){ $('#tablaInsumosLic tbody tr[data-id="'+id+'"]').remove(); }

function editarLic(id){
  // cargar cabecera + insumos
  $.getJSON(BASE + '/ajax/licitaciones_get.php', { id }, function(resp){
    if (!resp || !resp.success) { showToast('Error al cargar licitación', 'error'); return; }
    $('#id_licitacion').val(resp.data.id_licitacion);
    $('#cod_expediente').val(resp.data.cod_expediente);
    $('#fecha_finalizacion').val(resp.data.fecha_finalizacion||'');
    $('#descripcion').val(resp.data.descripcion||'');
    const cuerpo = $('#tablaInsumosLic tbody'); cuerpo.empty();
    (resp.data.insumos||[]).forEach(it => {
      cuerpo.append(`<tr data-id="${it.id_insumo}"><td>${it.nombre_insumo}</td><td>${it.tipo_insumo||''}</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarInsumo(${it.id_insumo})"><i class="fas fa-times"></i></button></td></tr>`);
    });
    new bootstrap.Modal(document.getElementById('modalLicitacion')).show();
  });
}

function verDetalleLic(id){
  $.getJSON(BASE + '/ajax/licitaciones_get.php', { id: id }, function(resp){
    if (!resp || !resp.success) { showToast('Error al cargar licitación', 'error'); return; }
    const d = resp.data || {};
    let html = '';
    html += `<div><strong>Expediente:</strong> ${$('<div>').text(d.cod_expediente||'').html()}</div>`;
    html += `<div><strong>Finalización:</strong> ${d.fecha_finalizacion||'-'}</div>`;
    if (d.descripcion) html += `<div><strong>Descripción:</strong> ${$('<div>').text(d.descripcion||'').html()}</div>`;
    html += '<hr><h6>Insumos</h6>';
    const items = (d.insumos||[]).map(function(it){
      const nombre = $('<div>').text(it.nombre_insumo||'').html();
      const tipo = $('<div>').text(it.tipo_insumo||'').html();
      return `<li>${nombre} <small class=\"text-muted\">(${tipo})</small></li>`;
    }).join('');
    html += items ? `<ul>${items}</ul>` : '<div class=\"text-muted\">Sin insumos</div>';
    // Fallback simple: alert legible si no hay modal genérico
    try {
      alert(html.replace(/<[^>]*>/g, '\n').replace(/\n\n+/g, '\n'));
    } catch(e) {
      console.log('Detalle licitación', d);
    }
  });
}

$('#btnGuardarLicitacion').on('click', function(){
  const payload = {
    _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '',
    id_licitacion: $('#id_licitacion').val()||null,
    cod_expediente: $('#cod_expediente').val(),
    fecha_finalizacion: $('#fecha_finalizacion').val()||null,
    descripcion: $('#descripcion').val()||null,
    insumos: $('#tablaInsumosLic tbody tr').map(function(){ return parseInt($(this).data('id'),10); }).get()
  };
  $.ajax({
    url: BASE + '/ajax/licitaciones_save.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    success: function(r){ if (!r.success) { showToast(r.error||'Error', 'error'); return; }
      showToast('Licitación guardada', 'success');
      $('#modalLicitacion').modal('hide');
      try { $('#tablaLicitaciones').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ showToast('Error al guardar', 'error'); }
  });
});

function eliminarLic(id){
  if (!confirm('¿Eliminar licitación?')) return;
  $.post({
    url: BASE + '/ajax/licitaciones_delete.php',
    contentType: 'application/json',
    data: JSON.stringify({ _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '', id }),
    success: function(r){ if (!r.success) { showToast(r.error||'Error', 'error'); return; }
      showToast('Licitación eliminada', 'success');
      try { $('#tablaLicitaciones').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ showToast('Error al eliminar', 'error'); }
  });
}
</script>
<?php include '../../includes/footer.php'; ?>