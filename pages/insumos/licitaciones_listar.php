<?php
require_once '../../includes/config.php';
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-signature me-2"></i>Licitaciones</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLicitacion"><i class="fas fa-plus me-2"></i>Nueva Licitación</button>
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

<!-- Modal crear/editar licitación -->
<div class="modal fade" id="modalLicitacion" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-signature me-2"></i>Licitación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formLicitacion" class="needs-validation" novalidate>
          <?php echo csrf_input(); ?>
          <input type="hidden" name="id_licitacion" id="id_licitacion">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Código Expediente *</label>
              <input type="text" class="form-control" name="cod_expediente" id="cod_expediente" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha Finalización</label>
              <input type="date" class="form-control" name="fecha_finalizacion" id="fecha_finalizacion">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción (opcional)</label>
              <textarea class="form-control" name="descripcion" id="descripcion" rows="2"></textarea>
            </div>
          </div>
          <hr>
          <h6 class="mb-2"><i class="fas fa-boxes me-2"></i>Insumos</h6>
          <div class="row g-2 align-items-end">
            <div class="col-md-8">
              <label class="form-label">Agregar insumos existentes</label>
              <select class="form-select select2" id="selInsumos" multiple style="width:100%"></select>
              <small class="text-muted">Puede filtrar por nombre/tipo; se listan sin licitación (asignados o no).</small>
            </div>
            <div class="col-md-4">
              <button type="button" id="btnAgregarInsumos" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Agregar Seleccionados</button>
            </div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-sm" id="tablaInsumosLic">
              <thead><tr><th>Nombre</th><th>Tipo</th><th>Acciones</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarLicitacion">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo app_base_url(); ?>';
$(function(){
  // DataTable principal
  $('#tablaLicitaciones').DataTable({
    ajax: { url: BASE + '/ajax/licitaciones_list.php', dataSrc: 'data' },
    columns: [
      { data: 'cod_expediente' },
      { data: 'fecha_finalizacion' },
      { data: 'num_insumos', render: d => `<span class="badge bg-primary">${d||0}</span>` },
      { data: null, orderable:false, searchable:false, render: row => `
        <div class="btn-group">
          <button class="btn btn-sm btn-info" onclick="editarLic(${row.id_licitacion})" title="Editar"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-danger" onclick="eliminarLic(${row.id_licitacion})" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>` }
    ]
  });

  // Select2 insumos sin licitación (cargar todos, sin filtrar por estado)
  $('#selInsumos').select2({
    theme: 'bootstrap-5',
    ajax: {
      delay: 250,
      url: BASE + '/ajax/insumos_listar_disponibles_lic.php',
      data: params => ({ q: params.term || '' }),
      processResults: data => ({ results: (data.data||[]).map(x => ({ id: x.id, text: x.nombre + ' (' + x.tipo + ')' })) })
    }
  });

  $('#btnAgregarInsumos').on('click', function(){
    const ids = $('#selInsumos').val() || [];
    if (!ids.length) return;
    const cuerpo = $('#tablaInsumosLic tbody');
    ids.forEach(id => {
      if (cuerpo.find(`[data-id="${id}"]`).length) return;
      const txt = $('#selInsumos').find(`option[value="${id}"]`).text();
      const tipo = (txt.match(/\(([^)]+)\)$/)||[])[1] || '';
      cuerpo.append(`<tr data-id="${id}"><td>${txt.replace(/\s*\([^)]*\)$/, '')}</td><td>${tipo}</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarInsumo(${id})"><i class="fas fa-times"></i></button></td></tr>`);
    });
    $('#selInsumos').val(null).trigger('change');
  });
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