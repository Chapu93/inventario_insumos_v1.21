<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'ver');
$db = conectarDB();
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-file-import me-2"></i>Ingresos</h1>
    <?php if (tienePermiso('insumos', 'crear')): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoIngreso">
        <i class="fas fa-plus me-2"></i>Nuevo Ingreso
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Ingresos</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable" id="tablaIngresos">
        <thead>
          <tr>
            <th>Nro. Exp./Notas/Referencia</th>
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
<div class="modal fade" id="modalNuevoIngreso" tabindex="-1" aria-labelledby="modalNuevoIngresoLabel"
  aria-hidden="true">
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
            <select class="form-select" id="nuevo_tipo_ingreso" name="tipo_ingreso" required
              onchange="actualizarLabelReferencia()">
              <option value="">Seleccione tipo</option>
              <option value="fondos">Fondos</option>
              <option value="compra_directa">Compra Directa</option>
              <option value="licitacion">Licitación</option>
              <option value="otros">Otros</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="nuevo_nro_referencia" class="form-label" id="label_nro_referencia">Nro. Exp./Notas/Referencia
              *</label>
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

          <div class="mb-3">
            <label for="nuevo_archivo_remito" class="form-label">
              <i class="fas fa-file-pdf me-1"></i>Adjuntar Remito
            </label>
            <input type="file" class="form-control" id="nuevo_archivo_remito" name="archivo_remito"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt">
            <small class="text-muted">PDF, imágenes o documentos (máx. 10MB)</small>
          </div>

          <div class="mb-3">
            <label for="nuevo_archivo_documentacion" class="form-label">
              <i class="fas fa-file-alt me-1"></i>Adjuntar Documentación
            </label>
            <input type="file" class="form-control" id="nuevo_archivo_documentacion" name="archivo_documentacion"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.xls,.zip,.odt,.ods">
            <small class="text-muted">PDF, imágenes, documentos o archivos comprimidos (máx. 10MB)</small>
          </div>

          <div class="border-top pt-3 mt-3">
            <p class="mb-0 text-muted small">
              <i class="fas fa-info-circle me-2 text-info"></i>
              <strong>Nota:</strong> Los insumos se asignan desde la carga/edición de cada insumo seleccionando este
              ingreso.
            </p>
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

<!-- Modal Exportar Insumos -->
<div class="modal fade" id="modalExportarInsumos" tabindex="-1" aria-labelledby="modalExportarInsumosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalExportarInsumosLabel">
          <i class="fas fa-boxes me-2"></i>Distribución de Insumos - Ref: <span id="spanRefExportar"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row mb-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Filtrar por Tipo</label>
            <select class="form-select form-select-sm" id="filtroExportarTipo" onchange="aplicarFiltrosExportar()">
              <option value="">Todos los tipos</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Filtrar por Localidad</label>
            <select class="form-select form-select-sm" id="filtroExportarLocalidad" onchange="aplicarFiltrosExportar()">
              <option value="">Todas las localidades</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Filtrar por Sede</label>
            <select class="form-select form-select-sm" id="filtroExportarSede" onchange="aplicarFiltrosExportar()">
              <option value="">Todas las sedes</option>
            </select>
          </div>
          <div class="col-md-3 text-end">
            <button type="button" class="btn btn-success shadow-sm btn-sm" id="btnDescargarExcel" onclick="descargarExcelInsumos()" style="display:none;">
              <i class="fas fa-file-excel me-2"></i>Exportar a Excel
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle border" id="tablaExportarInsumos">
            <thead class="table-light">
              <tr>
                <th>Producto</th>
                <th>Tipo</th>
                <th>Nro Serie</th>
                <th>Estado</th>
                <th>Localidad</th>
                <th>Sede Actual</th>
                <th>Área Actual</th>
                <th>Remito</th>
                <th>Asignado A</th>
                <th>Fecha Entrega</th>
              </tr>
            </thead>
            <tbody id="tbodyExportarInsumos">
              <tr><td colspan="10" class="text-center text-muted">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  const BASE = '<?php echo app_base_url(); ?>';
  const PERMISOS = {
    ver: <?php echo tienePermiso('insumos', 'ver') ? 'true' : 'false'; ?>,
    editar: <?php echo tienePermiso('insumos', 'editar') ? 'true' : 'false'; ?>,
    eliminar: <?php echo tienePermiso('insumos', 'eliminar') ? 'true' : 'false'; ?>
  };
  const ES_ADMIN = <?php echo tieneRol([1, 2]) ? 'true' : 'false'; ?>; // Solo Admin/Superadmin pueden eliminar documentos

  // Función para formatear fechas sin conversión de timezone
  function formatearFecha(fecha, conHora = false) {
    if (!fecha) return '-';

    // Si es solo fecha (YYYY-MM-DD)
    if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
      const [anio, mes, dia] = fecha.split('-');
      return `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${anio}`;
    }

    // Si incluye hora (YYYY-MM-DD HH:MM:SS)
    if (conHora && fecha.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
      const [fechaParte, horaParte] = fecha.split(' ');
      const [anio, mes, dia] = fechaParte.split('-');
      return `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${anio} ${horaParte}`;
    }

    return fecha;
  }

  $(function () {
    // DataTable con configuración en español
    $('#tablaIngresos').DataTable({
      ajax: { url: BASE + '/ajax/ingresos_list.php', dataSrc: 'data' },
      columns: [
        { data: 'nro_referencia' },
        {
          data: 'tipo_ingreso', render: function (d) {
            const tipos = {
              'fondos': '<span class="badge bg-success">Fondos</span>',
              'compra_directa': '<span class="badge bg-info">Compra Directa</span>',
              'licitacion': '<span class="badge bg-warning">Licitación</span>',
              'otros': '<span class="badge bg-secondary">Otros</span>'
            };
            return tipos[d] || d;
          }
        },
        {
          data: 'fecha_finalizacion', render: function (d) {
            if (!d) return '-';
            // Parsear manualmente para evitar conversión de timezone
            const partes = d.split('-'); // YYYY-MM-DD
            if (partes.length === 3) {
              const [anio, mes, dia] = partes;
              return `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${anio}`;
            }
            return d;
          }
        },
        { data: 'num_insumos', render: d => `<span class="badge bg-primary">${d || 0}</span>` },
        {
          data: null, orderable: false, searchable: false, render: function (data, type, row) {
            let botones = '<div class="btn-group">';

            if (PERMISOS.ver) {
              botones += `
              <button class="btn btn-sm btn-info" 
                      onclick="verDetalleIngreso(${row.id_ingreso})" 
                      data-bs-toggle="tooltip" 
                      title="Ver detalles"
                      aria-label="Ver detalles del ingreso">
                <i class="fas fa-eye"></i>
              </button>`;
            }

            if (PERMISOS.editar) {
              botones += `
              <a class="btn btn-sm btn-warning" 
                 href="ingresos_editar.php?id=${row.id_ingreso}" 
                 data-bs-toggle="tooltip" 
                 title="Editar ingreso"
                 aria-label="Editar ingreso">
                <i class="fas fa-edit"></i>
              </a>`;
            }

            botones += `
            <button class="btn btn-sm text-white" 
                    style="background-color: #fd7e14; border-color: #fd7e14;"
                    onclick="verDocumentosIngreso(${row.id_ingreso})" 
                    data-bs-toggle="tooltip" 
                    title="Ver documentos"
                    aria-label="Ver documentos adjuntos">
              <i class="fas fa-file"></i>
            </button>`;

            if (PERMISOS.eliminar) {
              botones += `
              <button class="btn btn-sm btn-danger" 
                      onclick="eliminarIngreso(${row.id_ingreso})" 
                      data-bs-toggle="tooltip" 
                      title="Eliminar ingreso"
                      aria-label="Eliminar ingreso">
                <i class="fas fa-trash"></i>
              </button>`;
            }

            // Nuevo botón de Excel / Distribución
            botones += `
            <button class="btn btn-sm btn-success" 
                    onclick="abrirModalExportarInsumos(${row.id_ingreso})" 
                    data-bs-toggle="tooltip" 
                    title="Ver distribución y exportar Excel"
                    aria-label="Ver y exportar a Excel">
              <i class="fas fa-file-excel"></i>
            </button>`;

            botones += '</div>';
            return botones;
          }
        }
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
        zeroRecords: '<div class="text-center py-3"><i class="fas fa-search fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No se encontraron resultados</p></div>',
        emptyTable: '<div class="text-center py-3"><i class="fas fa-file-import fa-2x text-muted mb-2"></i><p class="text-muted mb-0">No hay ingresos registrados</p></div>',
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
      drawCallback: function () {
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

    switch (tipo) {
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
        label.textContent = 'Nro. Exp./Notas/Referencia *';
        if (help) help.textContent = 'Ingrese el número de referencia';
    }
  }

  // Guardar nuevo ingreso
  $('#btnGuardarNuevoIngreso').on('click', function () {
    const form = $('#formNuevoIngreso')[0];
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const fechaInput = $('#nuevo_fecha_finalizacion').val();

    // Crear FormData para manejar archivos
    const formData = new FormData();
    formData.append('_csrf', (document.querySelector('meta[name="csrf-token"]') || {}).content || '');
    formData.append('tipo_ingreso', $('#nuevo_tipo_ingreso').val());
    formData.append('nro_referencia', $('#nuevo_nro_referencia').val());
    formData.append('fecha_finalizacion', fechaInput || null);
    formData.append('descripcion', $('#nuevo_descripcion').val() || null);

    // Agregar archivos si existen
    const archivoRemito = $('#nuevo_archivo_remito')[0].files[0];
    if (archivoRemito) {
      formData.append('archivo_remito', archivoRemito);
    }

    const archivoDocumentacion = $('#nuevo_archivo_documentacion')[0].files[0];
    if (archivoDocumentacion) {
      formData.append('archivo_documentacion', archivoDocumentacion);
    }

    $.ajax({
      url: BASE + '/ajax/ingresos_save_simple.php',
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (r) {
        if (!r.success) {
          showToast(r.error || 'Error al guardar', 'error');
          return;
        }
        showToast('Ingreso creado correctamente', 'success');
        $('#modalNuevoIngreso').modal('hide');
        $('#formNuevoIngreso')[0].reset();
        try { $('#tablaIngresos').DataTable().ajax.reload(); } catch (e) { location.reload(); }
      },
      error: function (xhr) {
        try {
          const response = JSON.parse(xhr.responseText);
          showToast(response.error || 'Error al guardar el ingreso', 'error');
        } catch (e) {
          showToast('Error al guardar el ingreso', 'error');
        }
      }
    });
  });

  // Ver detalle de ingreso
  function verDetalleIngreso(id) {
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

    $.ajax({
      url: BASE + '/ajax/ingresos_get.php',
      data: { id: id },
      dataType: 'json',
      success: function (resp) {
        if (!resp || !resp.success) {
          console.error('Error en respuesta:', resp);
          modalBody.innerHTML = '<div class="alert alert-danger">Error: ' + (resp?.error || 'No se pudo cargar el ingreso') + '</div>';
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
        html += `<div class="col-md-6"><p><strong>${labelsRef[d.tipo_ingreso] || 'Referencia'}:</strong><br><span class="badge bg-primary">${$('<div>').text(d.nro_referencia || '').html()}</span></p></div>`;
        html += `</div>`;
        html += `<div class="row">`;
        html += `<div class="col-md-6"><p><strong>Fecha Finalización:</strong><br>${formatearFecha(d.fecha_finalizacion)}</p></div>`;
        html += `<div class="col-md-6"><p><strong>Fecha Creación:</strong><br>${formatearFecha(d.created_at, true)}</p></div>`;
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
          html += '<thead><tr><th>Insumo</th><th>Cantidad</th></tr></thead>';
          html += '<tbody>';
          d.insumos.forEach(function (it) {
            const tipo = $('<div>').text(it.tipo_insumo || '').html();
            const nombre = $('<div>').text(it.nombre_grupo || '').html();
            const cantidad = parseInt(it.cantidad_total) || 1;
            html += `<tr>
              <td><strong>${nombre}</strong> <span class="badge bg-info ms-1">${tipo}</span></td>
              <td><span class="badge bg-primary">${cantidad}</span></td>
            </tr>`;
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
      },
      error: function (xhr, status, error) {
        console.error('Error AJAX al cargar ingreso:', { xhr, status, error });
        console.error('Response Text:', xhr.responseText);
        console.error('URL llamada:', BASE + '/ajax/ingresos_get.php?id=' + id);
        modalBody.innerHTML = `
        <div class="alert alert-danger">
          <h6><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar el ingreso</h6>
          <p><strong>Status:</strong> ${status}</p>
          <p><strong>Error:</strong> ${error || 'Error desconocido'}</p>
          <p><small>Abre la consola del navegador (F12) para ver más detalles</small></p>
        </div>`;
      }
    });
  }

  // Ver documentos adjuntos del ingreso
  function verDocumentosIngreso(id) {
    // Obtener o crear el modal
    let modalElement = document.getElementById('modalVerDocumentos');
    if (!modalElement) {
      modalElement = crearModalDocumentos();
    }

    const modalBody = document.getElementById('modalVerDocumentosBody');
    const btnEditarDocumentos = document.getElementById('btnEditarDocumentos');

    // Mostrar loading mientras carga
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

    // Mostrar el modal
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.show();

    $.ajax({
      url: BASE + '/ajax/ingresos_documentos_listar.php',
      data: { id: id },
      dataType: 'json',
      success: function (resp) {
        console.log('Respuesta documentos:', resp);

        if (!resp || !resp.success) {
          modalBody.innerHTML = '<p style="padding: 20px; text-align: center; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 0;">Error al cargar documentos</p>';
          btnEditarDocumentos.style.display = 'none';
          return;
        }

        // Los datos están en resp.data (no resp.data.documentos)
        const docs = (resp.data && Array.isArray(resp.data.documentos)) ? resp.data.documentos : [];
        let html = '';

        console.log('Documentos encontrados:', docs.length);

        if (docs.length === 0) {
          html = '<p style="padding: 20px; text-align: center; color: #004085; background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 0;">No hay documentos adjuntos a este ingreso.</p>';
          // Mostrar botón editar cuando no hay documentos
          if (PERMISOS.editar) {
            btnEditarDocumentos.style.display = 'inline-block';
            btnEditarDocumentos.onclick = () => {
              window.location.href = `ingresos_editar.php?id=${id}`;
            };
          }
        } else {
          // Ocultar botón editar cuando hay documentos
          btnEditarDocumentos.style.display = 'none';
          html = '<div class="list-group">';
          docs.forEach(function (doc) {
            const tipoIcon = doc.tipo_documento === 'remito' ? 'fa-receipt' :
              doc.tipo_documento === 'documentacion' ? 'fa-file-alt' : 'fa-file';
            const tipoLabel = doc.tipo_documento.charAt(0).toUpperCase() + doc.tipo_documento.slice(1);

            // Botón eliminar solo para admins
            let btnEliminar = '';
            if (ES_ADMIN) {
              btnEliminar = `
              <button type="button" class="btn btn-sm btn-danger" 
                      onclick="eliminarDocumentoIngreso(${doc.id_documento}, ${id})" 
                      title="Eliminar documento">
                <i class="fas fa-trash"></i>
              </button>`;
            }

            html += `
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1"><i class="fas ${tipoIcon} me-2"></i>${$('<div>').text(doc.nombre_archivo).html()}</h6>
                  <small class="text-muted"><strong>Tipo:</strong> ${tipoLabel}<br><strong>Cargado:</strong> ${doc.fecha_carga}</small>
                </div>
                <div class="btn-group">
                  <a href="${BASE}/ajax/ingresos_descargar_documento.php?id=${doc.id_documento}" 
                     class="btn btn-sm btn-info" 
                     title="Descargar documento"
                     download>
                    <i class="fas fa-download"></i>
                  </a>
                  ${btnEliminar}
                </div>
              </div>
            </div>
          `;
          });
          html += '</div>';
        }

        modalBody.innerHTML = html;
      },
      error: function (xhr) {
        console.error('Error al cargar documentos:', xhr);
        modalBody.innerHTML = '<p style="padding: 20px; text-align: center; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 0;">Error al cargar documentos</p>';
        btnEditarDocumentos.style.display = 'none';
      }
    });
  }

  // Crear modal de documentos si no existe
  function crearModalDocumentos() {
    const modalHtml = `
    <div class="modal fade" id="modalVerDocumentos" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-file me-2"></i>Documentos Adjuntos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="modalVerDocumentosBody">
            <div class="text-center">
              <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btnEditarDocumentos" style="display:none;">
              <i class="fas fa-edit me-2"></i>Agregar Documentos
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    return document.getElementById('modalVerDocumentos');
  }

  // Eliminar ingreso
  function eliminarIngreso(id) {
    showConfirm({
        titulo: 'Eliminar Ingreso',
        mensaje: '¿Está seguro de eliminar este ingreso?<br><br><small class="text-muted">Los insumos asociados quedarán sin ingreso asignado.</small>',
        icono: 'fa-trash-alt text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => {
            $.post({
              url: BASE + '/ajax/ingresos_delete.php',
              contentType: 'application/json',
              data: JSON.stringify({
                _csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                id
              }),
              success: function (r) {
                if (!r.success) {
                  showToast(r.error || 'Error al eliminar', 'error');
                  return;
                }
                showToast('Ingreso eliminado correctamente', 'success');
                try { $('#tablaIngresos').DataTable().ajax.reload(); } catch (e) { location.reload(); }
              },
              error: function () {
                showToast('Error al eliminar el ingreso', 'error');
              }
            });
        }
    });
  }

  // Eliminar documento adjunto (Solo Admin/Superadmin)
  function eliminarDocumentoIngreso(idDocumento, idIngreso) {
    showConfirm({
        titulo: 'Eliminar Documento',
        mensaje: '¿Está seguro de eliminar este documento?<br><br><small class="text-muted">Esta acción no se puede deshacer.</small>',
        icono: 'fa-file-excel text-danger',
        claseBoton: 'btn-danger',
        textoAceptar: 'Eliminar',
        onConfirm: () => {
            $.ajax({
              url: BASE + '/ajax/ingresos_eliminar_documento.php',
              method: 'POST',
              contentType: 'application/json',
              data: JSON.stringify({
                _csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                id: idDocumento
              }),
              success: function (r) {
                if (!r.success) {
                  showToast(r.error || 'Error al eliminar documento', 'error');
                  return;
                }
                showToast('Documento eliminado correctamente', 'success');
                // Recargar la lista de documentos
                verDocumentosIngreso(idIngreso);
              },
              error: function (xhr) {
                try {
                  const response = JSON.parse(xhr.responseText);
                  showToast(response.error || 'Error al eliminar documento', 'error');
                } catch (e) {
                  showToast('Error al eliminar documento', 'error');
                }
              }
            });
        }
    });
  }

  // --- Lógica de Exportación de Insumos y Distribución ---
  let excelDataActual = [];
  let excelDataFiltrada = [];
  let nroReferenciaActual = '';

  function abrirModalExportarInsumos(id_ingreso) {
    const modal = new bootstrap.Modal(document.getElementById('modalExportarInsumos'));
    $('#tbodyExportarInsumos').html('<tr><td colspan="10" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted mb-2"></i><br>Cargando datos de distribución...</td></tr>');
    $('#btnDescargarExcel').hide();
    $('#spanRefExportar').text('...');
    
    // Limpiar filtros
    $('#filtroExportarTipo, #filtroExportarLocalidad, #filtroExportarSede').html('<option value="">Todos</option>');
    
    modal.show();

    $.ajax({
      url: BASE + '/ajax/ingresos_exportar_insumos.php',
      type: 'GET',
      data: { id: id_ingreso },
      dataType: 'json',
      success: function(r) {
        if (!r.success) {
          $('#tbodyExportarInsumos').html(`<tr><td colspan="10" class="text-center text-danger py-3">Error: ${r.error}</td></tr>`);
          return;
        }
        
        nroReferenciaActual = r.data.ingreso.nro_referencia || 'Desconocido';
        $('#spanRefExportar').text(nroReferenciaActual);
        excelDataActual = r.data.data;
        excelDataFiltrada = [...excelDataActual];
        
        if (excelDataActual.length === 0) {
           $('#tbodyExportarInsumos').html('<tr><td colspan="10" class="text-center text-muted py-3">No hay insumos registrados para este ingreso.</td></tr>');
           return;
        }

        // Llenar selects de filtros dinámicamente
        const tipos = [...new Set(excelDataActual.map(i => i.tipo_insumo).filter(Boolean))].sort();
        const localidades = [...new Set(excelDataActual.map(i => i.nombre_localidad).filter(Boolean))].sort();
        const sedes = [...new Set(excelDataActual.map(i => i.nombre_sede).filter(Boolean))].sort();

        $('#filtroExportarTipo').html('<option value="">Todos los tipos</option>' + tipos.map(t => `<option value="${t}">${t}</option>`).join(''));
        $('#filtroExportarLocalidad').html('<option value="">Todas las localidades</option>' + localidades.map(l => `<option value="${l}">${l}</option>`).join(''));
        $('#filtroExportarSede').html('<option value="">Todas las sedes</option>' + sedes.map(s => `<option value="${s}">${s}</option>`).join(''));

        renderTablaExportarInsumos();
        $('#btnDescargarExcel').show();
      },
      error: function() {
        $('#tbodyExportarInsumos').html(`<tr><td colspan="10" class="text-center text-danger py-3">Error de conexión al cargar datos.</td></tr>`);
      }
    });
  }

  function aplicarFiltrosExportar() {
      const tipo = $('#filtroExportarTipo').val();
      const localidad = $('#filtroExportarLocalidad').val();
      const sede = $('#filtroExportarSede').val();

      excelDataFiltrada = excelDataActual.filter(item => {
          if (tipo && item.tipo_insumo !== tipo) return false;
          if (localidad && item.nombre_localidad !== localidad) return false;
          if (sede && item.nombre_sede !== sede) return false;
          return true;
      });

      renderTablaExportarInsumos();
  }

  function renderTablaExportarInsumos() {
      let html = '';
      if (excelDataFiltrada.length === 0) {
          html = '<tr><td colspan="10" class="text-center text-muted py-3">No hay insumos que coincidan con los filtros.</td></tr>';
      } else {
          excelDataFiltrada.forEach(item => {
             let asignadoA = item.nombre_persona_asignada ? (item.nombre_persona_asignada + ' ' + item.apellido_persona_asignada) : '-';
             let colorBadge = item.estado === 'Disponible' ? 'bg-success' : (item.estado === 'Asignado' ? 'bg-primary' : 'bg-secondary');
             
             html += `<tr>
               <td><strong>${$('<div>').text(item.nombre_insumo || '-').html()}</strong></td>
               <td>${$('<div>').text(item.tipo_insumo || '-').html()}</td>
               <td><small class="text-muted">${$('<div>').text(item.numero_serie || '-').html()}</small></td>
               <td><span class="badge ${colorBadge}">${item.estado}</span></td>
               <td>${$('<div>').text(item.nombre_localidad || '-').html()}</td>
               <td>${$('<div>').text(item.nombre_sede || '-').html()}</td>
               <td>${$('<div>').text(item.nombre_area || '-').html()}</td>
               <td>${$('<div>').text(item.numero_remito || '-').html()}</td>
               <td>${$('<div>').text(asignadoA).html()}</td>
               <td>${formatearFecha(item.fecha_asignacion) || '-'}</td>
             </tr>`;
          });
      }
      $('#tbodyExportarInsumos').html(html);
  }

  function descargarExcelInsumos() {
    if (excelDataFiltrada.length === 0) return;
    
    // Preparar array de objetos para SheetJS (usando los datos filtrados)
    const dataAExportar = excelDataFiltrada.map(item => ({
       'Licitación/Referencia': item.nro_referencia || '',
       'Producto': item.nombre_insumo || '',
       'Tipo': item.tipo_insumo || '',
       'Número de Serie': item.numero_serie || '',
       'Estado': item.estado || '',
       'Localidad': item.nombre_localidad || '',
       'Sede Actual': item.nombre_sede || '',
       'Área Actual': item.nombre_area || '',
       'Remito de Entrega': item.numero_remito || '',
       'Asignado A': item.nombre_persona_asignada ? (item.nombre_persona_asignada + ' ' + item.apellido_persona_asignada) : '',
       'Fecha Asignación': formatearFecha(item.fecha_asignacion) || ''
    }));

    // Crear libro usando SheetJS (XLSX global)
    if (typeof XLSX === 'undefined') {
        showToast('Error: Librería de exportación no cargada. Pruebe recargando la página.', 'error');
        return;
    }
    
    const ws = XLSX.utils.json_to_sheet(dataAExportar);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Distribución");
    
    // Generar archivo y descargar
    const filename = `distribucion_insumos_${nroReferenciaActual.replace(/[^a-z0-9]/gi, '_')}.xlsx`;
    XLSX.writeFile(wb, filename);
  }
</script>
<?php include '../../includes/footer.php'; ?>