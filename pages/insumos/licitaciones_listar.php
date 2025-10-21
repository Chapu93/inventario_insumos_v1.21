<?php
require_once '../../includes/config.php';
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-signature me-2"></i>Licitaciones</h1>
    <a class="btn btn-primary" href="licitaciones_pasos.php?new=1"><i class="fas fa-plus me-2"></i>Nueva Licitación</a>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaLicitaciones">
        <thead><tr><th>Código Expediente</th><th>Fin</th><th>Insumos</th></tr></thead>
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
      { data: 'num_insumos', render: d => `<span class="badge bg-primary">${d||0}</span>` }
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