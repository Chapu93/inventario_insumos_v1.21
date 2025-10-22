<?php
require_once '../../includes/config.php';
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-signature me-2"></i>Licitaciones</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaLicitacion">
      <i class="fas fa-plus me-2"></i>Nueva Licitación
    </button>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaLicitaciones">
        <thead><tr><th>Código Expediente</th><th>Finalización</th><th>Insumos</th><th>Acciones</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nueva Licitación -->
<div class="modal fade" id="modalNuevaLicitacion" tabindex="-1" aria-labelledby="modalNuevaLicitacionLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevaLicitacionLabel">
          <i class="fas fa-plus-circle me-2"></i>Nueva Licitación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevaLicitacion">
          <div class="mb-3">
            <label for="nuevo_cod_expediente" class="form-label">Código Expediente *</label>
            <input type="text" class="form-control" id="nuevo_cod_expediente" name="cod_expediente" required>
          </div>
          <div class="mb-3">
            <label for="nuevo_fecha_finalizacion" class="form-label">Fecha Finalización</label>
            <input type="date" class="form-control" id="nuevo_fecha_finalizacion" name="fecha_finalizacion">
          </div>
          <div class="mb-3">
            <label for="nuevo_descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="nuevo_descripcion" name="descripcion" rows="3"></textarea>
          </div>
          <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Los insumos se asignan desde la carga/edición de cada insumo seleccionando el Nro. de Expediente.
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarNuevaLicitacion">
          <i class="fas fa-save me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ver Licitación -->
<div class="modal fade" id="modalVerLicitacion" tabindex="-1" aria-labelledby="modalVerLicitacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerLicitacionLabel">
          <i class="fas fa-eye me-2"></i>Detalle de Licitación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalVerLicitacionBody">
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-warning" id="btnEditarDesdever" style="display:none;">
          <i class="fas fa-edit me-2"></i>Editar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo app_base_url(); ?>';
$(function(){
  // DataTable en español
  $('#tablaLicitaciones').DataTable({
    ajax: { url: BASE + '/ajax/licitaciones_list.php', dataSrc: 'data' },
    columns: [
      { data: 'cod_expediente' },
      { data: 'fecha_finalizacion', render: function(d) {
          if (!d) return '-';
          const fecha = new Date(d);
          return fecha.toLocaleDateString('es-AR');
        }
      },
      { data: 'num_insumos', render: d => `<span class="badge bg-primary">${d||0}</span>` },
      { data: null, orderable:false, searchable:false, render: function(data, type, row){
          return `
            <div class="btn-group">
              <button class="btn btn-sm btn-info" onclick="verDetalleLic(${row.id_licitacion})" title="Ver" data-bs-toggle="tooltip"><i class="fas fa-eye"></i></button>
              <a class="btn btn-sm btn-warning" href="licitaciones_nueva_pasos.php?id=${row.id_licitacion}" title="Editar" data-bs-toggle="tooltip"><i class="fas fa-edit"></i></a>
              <button class="btn btn-sm btn-danger" onclick="eliminarLic(${row.id_licitacion})" title="Eliminar" data-bs-toggle="tooltip"><i class="fas fa-trash"></i></button>
            </div>`;
        } }
    ],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
  });
});

// Guardar nueva licitación
$('#btnGuardarNuevaLicitacion').on('click', function(){
  const form = $('#formNuevaLicitacion')[0];
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const payload = {
    _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '',
    cod_expediente: $('#nuevo_cod_expediente').val(),
    fecha_finalizacion: $('#nuevo_fecha_finalizacion').val() || null,
    descripcion: $('#nuevo_descripcion').val() || null
  };
  
  $.ajax({
    url: BASE + '/ajax/licitaciones_save_simple.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    success: function(r){ 
      if (!r.success) { 
        showToast(r.error||'Error al guardar', 'error'); 
        return; 
      }
      showToast('Licitación creada correctamente', 'success');
      $('#modalNuevaLicitacion').modal('hide');
      $('#formNuevaLicitacion')[0].reset();
      try { $('#tablaLicitaciones').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ 
      showToast('Error al guardar la licitación', 'error'); 
    }
  });
});

// Ver detalle de licitación
function verDetalleLic(id){
  const modal = new bootstrap.Modal(document.getElementById('modalVerLicitacion'));
  const modalBody = document.getElementById('modalVerLicitacionBody');
  const btnEditar = document.getElementById('btnEditarDesdever');
  
  // Mostrar loading
  modalBody.innerHTML = `
    <div class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando detalles...</p>
    </div>
  `;
  
  modal.show();
  
  $.getJSON(BASE + '/ajax/licitaciones_get.php', { id: id }, function(resp){
    if (!resp || !resp.success) { 
      showToast('Error al cargar licitación', 'error'); 
      modal.hide();
      return; 
    }
    
    const d = resp.data || {};
    let html = '';
    
    // Información principal
    html += '<div class="row">';
    html += '<div class="col-md-12">';
    html += '<div class="card mb-3">';
    html += '<div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Licitación</h6></div>';
    html += '<div class="card-body">';
    html += `<div class="row">`;
    html += `<div class="col-md-6"><p><strong>Código Expediente:</strong><br><span class="badge bg-primary">${$('<div>').text(d.cod_expediente||'').html()}</span></p></div>`;
    html += `<div class="col-md-6"><p><strong>Fecha Finalización:</strong><br>${d.fecha_finalizacion ? new Date(d.fecha_finalizacion).toLocaleDateString('es-AR') : '-'}</p></div>`;
    html += `</div>`;
    if (d.descripcion) {
      html += `<div class="row"><div class="col-12"><p><strong>Descripción:</strong><br>${$('<div>').text(d.descripcion).html().replace(/\n/g, '<br>')}</p></div></div>`;
    }
    html += '</div></div>';
    
    // Insumos asignados
    html += '<div class="card">';
    html += '<div class="card-header"><h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Asignados (' + (d.insumos?.length || 0) + ')</h6></div>';
    html += '<div class="card-body">';
    
    if (d.insumos && d.insumos.length > 0) {
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-striped">';
      html += '<thead><tr><th>Nombre</th><th>Tipo</th><th>Estado</th></tr></thead>';
      html += '<tbody>';
      d.insumos.forEach(function(it){
        const nombre = $('<div>').text(it.nombre_insumo||'').html();
        const tipo = $('<div>').text(it.tipo_insumo||'').html();
        const estado = $('<div>').text(it.estado||'').html();
        html += `<tr><td><strong>${nombre}</strong></td><td><span class="badge bg-info">${tipo}</span></td><td>${estado}</td></tr>`;
      });
      html += '</tbody></table>';
      html += '</div>';
    } else {
      html += '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>No hay insumos asignados a esta licitación.</div>';
    }
    
    html += '</div></div>';
    html += '</div></div>';
    
    modalBody.innerHTML = html;
    
    // Configurar botón editar
    btnEditar.onclick = () => {
      modal.hide();
      window.location.href = `licitaciones_nueva_pasos.php?id=${id}`;
    };
    btnEditar.style.display = 'inline-block';
  });
}

// Eliminar licitación
function eliminarLic(id){
  if (!confirm('¿Está seguro de eliminar esta licitación?\n\nLos insumos asociados quedarán sin licitación asignada.')) return;
  
  $.post({
    url: BASE + '/ajax/licitaciones_delete.php',
    contentType: 'application/json',
    data: JSON.stringify({ 
      _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '', 
      id 
    }),
    success: function(r){ 
      if (!r.success) { 
        showToast(r.error||'Error al eliminar', 'error'); 
        return; 
      }
      showToast('Licitación eliminada correctamente', 'success');
      try { $('#tablaLicitaciones').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ 
      showToast('Error al eliminar la licitación', 'error'); 
    }
  });
}
</script>
<?php include '../../includes/footer.php'; ?>
