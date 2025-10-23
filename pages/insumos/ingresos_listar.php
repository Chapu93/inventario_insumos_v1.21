<?php
require_once '../../includes/config.php';
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-import me-2"></i>Ingresos</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoIngreso">
      <i class="fas fa-plus me-2"></i>Nuevo Ingreso
    </button>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Ingresos</h5></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaIngresos">
        <thead>
          <tr>
            <th>Nro. Referencia</th>
            <th>Tipo</th>
            <th>Finalización</th>
            <th>Insumos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nuevo Ingreso -->
<div class="modal fade" id="modalNuevoIngreso" tabindex="-1" aria-labelledby="modalNuevoIngresoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevoIngresoLabel">
          <i class="fas fa-plus-circle me-2"></i>Nuevo Ingreso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoIngreso">
          <div class="mb-3">
            <label for="nuevo_tipo_ingreso" class="form-label">Tipo de Ingreso *</label>
            <select class="form-select" id="nuevo_tipo_ingreso" name="tipo_ingreso" required onchange="actualizarLabelReferencia()">
              <option value="">Seleccione tipo</option>
              <option value="fondos">Fondos</option>
              <option value="compra_directa">Compra Directa</option>
              <option value="licitacion">Licitación</option>
              <option value="otros">Otros</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="nuevo_nro_referencia" class="form-label" id="label_nro_referencia">Nro. de Referencia *</label>
            <input type="text" class="form-control" id="nuevo_nro_referencia" name="nro_referencia" required>
            <small class="text-muted" id="help_nro_referencia">Ingrese el número de referencia</small>
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
            Los insumos se asignan desde la carga/edición de cada insumo seleccionando este ingreso.
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarNuevoIngreso">
          <i class="fas fa-save me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ver Ingreso -->
<div class="modal fade" id="modalVerIngreso" tabindex="-1" aria-labelledby="modalVerIngresoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerIngresoLabel">
          <i class="fas fa-eye me-2"></i>Detalle de Ingreso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalVerIngresoBody">
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-warning" id="btnEditarDesdeVer" style="display:none;">
          <i class="fas fa-edit me-2"></i>Editar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo app_base_url(); ?>';

$(function(){
  // DataTable con configuración en español
  $('#tablaIngresos').DataTable({
    ajax: { url: BASE + '/ajax/ingresos_list.php', dataSrc: 'data' },
    columns: [
      { data: 'nro_referencia' },
      { data: 'tipo_ingreso', render: function(d) {
          const tipos = {
            'fondos': '<span class="badge bg-success">Fondos</span>',
            'compra_directa': '<span class="badge bg-info">Compra Directa</span>',
            'licitacion': '<span class="badge bg-warning">Licitación</span>',
            'otros': '<span class="badge bg-secondary">Otros</span>'
          };
          return tipos[d] || d;
        }
      },
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
              <button class="btn btn-sm btn-info" onclick="verDetalleIngreso(${row.id_ingreso})" title="Ver"><i class="fas fa-eye"></i></button>
              <a class="btn btn-sm btn-warning" href="ingresos_editar.php?id=${row.id_ingreso}" title="Editar"><i class="fas fa-edit"></i></a>
              <button class="btn btn-sm btn-danger" onclick="eliminarIngreso(${row.id_ingreso})" title="Eliminar"><i class="fas fa-trash"></i></button>
            </div>`;
        } }
    ],
    language: {
      decimal: ',',
      thousands: '.',
      processing: 'Procesando...',
      search: 'Buscar:',
      lengthMenu: 'Mostrar _MENU_ registros',
      info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
      infoEmpty: 'Mostrando 0 a 0 de 0 registros',
      infoFiltered: '(filtrado de _MAX_ registros totales)',
      loadingRecords: 'Cargando...',
      zeroRecords: 'No se encontraron resultados',
      emptyTable: 'Ningún dato disponible en la tabla',
      paginate: {
        first: 'Primero',
        previous: 'Anterior',
        next: 'Siguiente',
        last: 'Último'
      },
      aria: {
        sortAscending: ': activar para ordenar ascendente',
        sortDescending: ': activar para ordenar descendente'
      }
    },
    drawCallback: function() {
      // Inicializar tooltips usando la función global del proyecto
      if (typeof inicializarTooltips === 'function') {
        inicializarTooltips();
      }
    }
  });
});

// Actualizar label según tipo de ingreso
function actualizarLabelReferencia() {
  const select = document.getElementById('nuevo_tipo_ingreso');
  const label = document.getElementById('label_nro_referencia');
  const help = document.getElementById('help_nro_referencia');
  
  if (!select || !label) return;
  
  const tipo = select.value;
  
  switch(tipo) {
    case 'fondos':
      label.innerHTML = '<i class="fas fa-money-bill me-1"></i>Nro. de Nota *';
      if (help) help.textContent = 'Ingrese el número de nota de fondos';
      break;
    case 'licitacion':
      label.innerHTML = '<i class="fas fa-file-signature me-1"></i>Nro. de Expediente *';
      if (help) help.textContent = 'Ingrese el número de expediente de licitación';
      break;
    case 'compra_directa':
      label.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>Nro. de Expediente *';
      if (help) help.textContent = 'Ingrese el número de expediente de compra directa';
      break;
    case 'otros':
      label.innerHTML = '<i class="fas fa-ellipsis-h me-1"></i>Nro. de Referencia *';
      if (help) help.textContent = 'Ingrese el número de referencia';
      break;
    default:
      label.textContent = 'Nro. de Referencia *';
      if (help) help.textContent = 'Ingrese el número de referencia';
  }
}

// Guardar nuevo ingreso
$('#btnGuardarNuevoIngreso').on('click', function(){
  const form = $('#formNuevoIngreso')[0];
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const fechaInput = $('#nuevo_fecha_finalizacion').val();
  
  // Log para debug
  console.log('Fecha del input:', fechaInput);
  
  const payload = {
    _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '',
    tipo_ingreso: $('#nuevo_tipo_ingreso').val(),
    nro_referencia: $('#nuevo_nro_referencia').val(),
    fecha_finalizacion: fechaInput || null,
    descripcion: $('#nuevo_descripcion').val() || null
  };
  
  console.log('Payload enviado:', payload);
  
  $.ajax({
    url: BASE + '/ajax/ingresos_save_simple.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    success: function(r){ 
      if (!r.success) { 
        showToast(r.error||'Error al guardar', 'error'); 
        return; 
      }
      showToast('Ingreso creado correctamente', 'success');
      $('#modalNuevoIngreso').modal('hide');
      $('#formNuevoIngreso')[0].reset();
      try { $('#tablaIngresos').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ 
      showToast('Error al guardar el ingreso', 'error'); 
    }
  });
});

// Ver detalle de ingreso
function verDetalleIngreso(id){
  const modal = new bootstrap.Modal(document.getElementById('modalVerIngreso'));
  const modalBody = document.getElementById('modalVerIngresoBody');
  const btnEditar = document.getElementById('btnEditarDesdeVer');
  
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
  
  $.getJSON(BASE + '/ajax/ingresos_get.php', { id: id }, function(resp){
    if (!resp || !resp.success) { 
      showToast('Error al cargar ingreso', 'error'); 
      modal.hide();
      return; 
    }
    
    const d = resp.data || {};
    let html = '';
    
    // Badges de tipo
    const tiposBadges = {
      'fondos': '<span class="badge bg-success"><i class="fas fa-money-bill me-1"></i>Fondos</span>',
      'compra_directa': '<span class="badge bg-info"><i class="fas fa-shopping-cart me-1"></i>Compra Directa</span>',
      'licitacion': '<span class="badge bg-warning"><i class="fas fa-file-signature me-1"></i>Licitación</span>',
      'otros': '<span class="badge bg-secondary"><i class="fas fa-ellipsis-h me-1"></i>Otros</span>'
    };
    
    // Labels de referencia
    const labelsRef = {
      'fondos': 'Nro. de Nota',
      'compra_directa': 'Nro. de Expediente',
      'licitacion': 'Nro. de Expediente',
      'otros': 'Nro. de Referencia'
    };
    
    // Información principal
    html += '<div class="row">';
    html += '<div class="col-md-12">';
    html += '<div class="card mb-3">';
    html += '<div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Ingreso</h6></div>';
    html += '<div class="card-body">';
    html += `<div class="row">`;
    html += `<div class="col-md-6"><p><strong>Tipo:</strong><br>${tiposBadges[d.tipo_ingreso] || d.tipo_ingreso}</p></div>`;
    html += `<div class="col-md-6"><p><strong>${labelsRef[d.tipo_ingreso] || 'Referencia'}:</strong><br><span class="badge bg-primary">${$('<div>').text(d.nro_referencia||'').html()}</span></p></div>`;
    html += `</div>`;
    html += `<div class="row">`;
    html += `<div class="col-md-6"><p><strong>Fecha Finalización:</strong><br>${d.fecha_finalizacion ? new Date(d.fecha_finalizacion).toLocaleDateString('es-AR') : '-'}</p></div>`;
    html += `<div class="col-md-6"><p><strong>Fecha Creación:</strong><br>${d.created_at ? new Date(d.created_at).toLocaleDateString('es-AR') : '-'}</p></div>`;
    html += `</div>`;
    if (d.descripcion) {
      html += `<div class="row"><div class="col-12"><p><strong>Descripción:</strong><br>${$('<div>').text(d.descripcion).html().replace(/\n/g, '<br>')}</p></div></div>`;
    }
    html += '</div></div>';
    
    // Insumos asignados
    html += '<div class="card">';
    html += '<div class="card-header"><h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Asociados (' + (d.insumos?.length || 0) + ')</h6></div>';
    html += '<div class="card-body">';
    
    if (d.insumos && d.insumos.length > 0) {
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-striped">';
      html += '<thead><tr><th>Nombre</th><th>Tipo</th><th>Cantidad</th><th>Estado</th></tr></thead>';
      html += '<tbody>';
      d.insumos.forEach(function(it){
        const nombre = $('<div>').text(it.nombre_insumo||'').html();
        const tipo = $('<div>').text(it.tipo_insumo||'').html();
        const cantidad = parseInt(it.cantidad) || 1;
        const estado = $('<div>').text(it.estado||'').html();
        html += `<tr><td><strong>${nombre}</strong></td><td><span class="badge bg-info">${tipo}</span></td><td><span class="badge bg-primary">${cantidad}</span></td><td>${estado}</td></tr>`;
      });
      html += '</tbody></table>';
      html += '</div>';
    } else {
      html += '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>No hay insumos asociados a este ingreso.</div>';
    }
    
    html += '</div></div>';
    html += '</div></div>';
    
    modalBody.innerHTML = html;
    
    // Configurar botón editar
    btnEditar.onclick = () => {
      modal.hide();
      window.location.href = `ingresos_editar.php?id=${id}`;
    };
    btnEditar.style.display = 'inline-block';
  });
}

// Eliminar ingreso
function eliminarIngreso(id){
  if (!confirm('¿Está seguro de eliminar este ingreso?\n\nLos insumos asociados quedarán sin ingreso asignado.')) return;
  
  $.post({
    url: BASE + '/ajax/ingresos_delete.php',
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
      showToast('Ingreso eliminado correctamente', 'success');
      try { $('#tablaIngresos').DataTable().ajax.reload(); } catch(e) { location.reload(); }
    },
    error: function(){ 
      showToast('Error al eliminar el ingreso', 'error'); 
    }
  });
}
</script>
<?php include '../../includes/footer.php'; ?>
