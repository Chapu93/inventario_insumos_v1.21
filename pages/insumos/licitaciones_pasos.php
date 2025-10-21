<?php
require_once '../../includes/config.php';
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Nueva Licitación (Pasos)</h4>
    <a href="licitaciones_listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="stepper mb-3">
      <div class="step step-1 active"><span class="circle">1</span><span>Datos</span></div>
      <div class="divider"></div>
      <div class="step step-2"><span class="circle">2</span><span>Seleccionar / Agregar Insumos</span></div>
      <div class="divider"></div>
      <div class="step step-3"><span class="circle">3</span><span>Confirmación</span></div>
    </div>

    <form id="formLic" class="needs-validation" novalidate>
      <?php echo csrf_input(); ?>
      <div id="paso1">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Código expediente *</label>
            <input type="text" class="form-control" id="cod_expediente" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha finalización</label>
            <input type="date" class="form-control" id="fecha_finalizacion">
          </div>
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" rows="2"></textarea>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button type="button" class="btn btn-primary" id="btnPaso1">Siguiente</button>
        </div>
      </div>

      <div id="paso2" style="display:none;">
        <div class="row g-2 align-items-end mb-2">
          <div class="col-md-5">
            <label class="form-label">Buscar</label>
            <input type="text" class="form-control" id="filtro_busqueda" placeholder="Nombre, S/N, ID...">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo de Insumo</label>
            <select class="form-select form-select-sm" id="filtro_tipo" style="min-width: 280px;">
              <option value="">Todos los tipos</option>
              <option value="Varios">Varios</option>
              <option value="PC Completa">PC Completa</option>
              <option value="Notebook">Notebook</option>
              <option value="Impresora">Impresora</option>
              <option value="Monitor">Monitor</option>
              <option value="Escaner">Escaner</option>
            </select>
          </div>
          <div class="col-md-3 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros"><i class="fas fa-eraser"></i> Limpiar</button>
            <a href="agregar.php?from=licitacion&back=<?php echo urlencode(app_base_url().'/pages/insumos/licitaciones_pasos.php'); ?>" id="btnNuevoInsumo" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nuevo Insumo</a>
          </div>
        </div>
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-secondary" id="contadorSeleccionLic">0</span>
              <button type="button" class="btn btn-outline-primary btn-sm" id="btnSeleccionarFiltrados"><i class="fas fa-check-double"></i> Seleccionar filtrados</button>
              <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeseleccionar"><i class="fas fa-times"></i> Deseleccionar todo</button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-flat table-hover" id="tablaInsumosLicDisponibles">
                <tbody></tbody>
              </table>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button type="button" class="btn btn-secondary" id="btnVolver1">Volver</button>
              <button type="button" class="btn btn-primary" id="btnPaso2">Continuar</button>
            </div>
          </div>
        </div>
      </div>

      <div id="paso3" style="display:none;">
        <h6 class="text-primary mb-2"><i class="fas fa-file-alt me-2"></i>Resumen</h6>
        <div id="resumenLic"></div>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-secondary" id="btnVolver2">Volver</button>
          <button type="button" class="btn btn-success" id="btnFinalizar">Confirmar y Finalizar</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
const BASE = '<?php echo app_base_url(); ?>';
let SELECCION = new Set();

function cargarDisponibles(){
  $.getJSON(BASE + '/ajax/insumos_listar_disponibles_lic.php', function(resp){
    const $tb = $('#tablaInsumosLicDisponibles tbody');
    $tb.empty();
    (resp.data||[]).forEach(it => {
      const saved = parseInt(localStorage.getItem('LIC_CANT_'+it.id)||'1',10) || 1;
      const controls = it.tipo==='Varios' ? `<div class=\"cantidad-input d-inline-flex align-items-center gap-2 ms-2 ${SELECCION.has(it.id)?'':'d-none'}\"><label class=\"small text-muted mb-0\">Cant.</label><input type=\"number\" class=\"form-control form-control-sm\" min=\"1\" max=\"${it.max||1}\" value=\"${saved}\" data-cantidad-id=\"${it.id}\" style=\"width:84px;\"></div>` : '';
      $tb.append(`<tr class=\"fila-insumo\" data-id=\"${it.id}\" data-tipo=\"${it.tipo}\" data-texto=\"${(it.nombre||'').toLowerCase()}\"><td><strong>${it.nombre}</strong></td><td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm ${SELECCION.has(it.id)?'btn-primary':'btn-outline-primary'} btn-sel\" data-id=\"${it.id}\"><i class=\"fas ${SELECCION.has(it.id)?'fa-minus':'fa-plus'}\"></i> ${SELECCION.has(it.id)?'Deseleccionar':'Seleccionar'}</button>${controls}</td></tr>`);
    });
    filtrar();
    actualizarContador();
  });
}

function filtrar(){
  const tipo = ($('#filtro_tipo').val()||'').toLowerCase();
  const q = ($('#filtro_busqueda').val()||'').toLowerCase();
  $('#tablaInsumosLicDisponibles tbody tr').each(function(){
    const $f = $(this);
    const t = ($f.data('tipo')||'').toLowerCase();
    const txt = ($f.data('texto')||'');
    let show = true;
    if (tipo && t!==tipo) show = false;
    if (q && !txt.includes(q)) show = false;
    $f.toggle(show);
  });
}

function actualizarContador(){ $('#contadorSeleccionLic').text(`${SELECCION.size}`); }

$(function(){
  // Restaurar selección guardada y sumar el recién creado si vuelve con added_id
  // Restaurar paso y datos del paso 1 si existían
  try {
    const prev = JSON.parse(localStorage.getItem('LIC_SELECCION')||'[]');
    if (Array.isArray(prev)) { prev.forEach(i=> SELECCION.add(parseInt(i,10))); }
    const p1 = JSON.parse(localStorage.getItem('LIC_PASO1')||'{}');
    if (p1 && (p1.cod_expediente||p1.fecha_finalizacion||p1.descripcion)) {
      $('#cod_expediente').val(p1.cod_expediente||'');
      $('#fecha_finalizacion').val(p1.fecha_finalizacion||'');
      $('#descripcion').val(p1.descripcion||'');
      // Si venimos de volver del alta, quedarnos en paso 2
      $('#paso1').hide(); $('.stepper .step').removeClass('active'); $('.stepper .step-2').addClass('active'); $('#paso2').show();
    }
  } catch(e) {}
  try { const url = new URL(window.location.href); const added = url.searchParams.get('added_id'); if (added) { SELECCION.add(parseInt(added,10)); } } catch(e) {}
  cargarDisponibles();
  $('#filtro_busqueda').on('input', function(){ filtrar(); });
  $('#filtro_tipo').on('change', filtrar);
  $('#btnLimpiarFiltros').on('click', function(){ $('#filtro_busqueda').val(''); $('#filtro_tipo').val(''); filtrar(); });
  
  // Inicializar contador correcto
  actualizarContador();
  $(document).on('click', '.btn-sel', function(){
    const id = parseInt($(this).data('id'),10);
    if (SELECCION.has(id)) {
      SELECCION.delete(id);
      try { localStorage.removeItem('LIC_CANT_'+id); } catch(e) {}
    } else {
      SELECCION.add(id);
      const $row = $(this).closest('tr');
      $row.find('.cantidad-input').removeClass('d-none');
    }
    // Reordenar: seleccionados primero
    const $tbody = $('#tablaInsumosLicDisponibles tbody');
    const $rows = $tbody.find('tr');
    $rows.sort(function(a,b){ const ida = parseInt($(a).data('id'),10); const idb = parseInt($(b).data('id'),10); const sa = SELECCION.has(ida) ? 0 : 1; const sb = SELECCION.has(idb) ? 0 : 1; return sa - sb; });
    $tbody.html($rows);
    actualizarContador();
    // Actualizar botón
    $(this).toggleClass('btn-outline-primary btn-primary').html(`<i class="fas ${SELECCION.has(id)?'fa-minus':'fa-plus'}"></i> ${SELECCION.has(id)?'Deseleccionar':'Seleccionar'}`);
  });
  // Guardar cantidades de 'Varios'
  $(document).on('change', 'input[data-cantidad-id]', function(){ const id = parseInt($(this).data('cantidad-id'),10); const val = Math.max(1, parseInt($(this).val()||'1',10)); try { localStorage.setItem('LIC_CANT_'+id, String(val)); } catch(e) {} });
  $('#btnNuevoInsumo').on('click', function(){ try { localStorage.setItem('LIC_SELECCION', JSON.stringify(Array.from(SELECCION.values()))); } catch(e) {} });

  $('#btnPaso1').on('click', function(){ if (!document.getElementById('cod_expediente').checkValidity()) { document.getElementById('cod_expediente').reportValidity(); return; } try { localStorage.setItem('LIC_PASO1', JSON.stringify({ cod_expediente: $('#cod_expediente').val(), fecha_finalizacion: $('#fecha_finalizacion').val(), descripcion: $('#descripcion').val() })); } catch(e) {} $('#paso1').hide(); $('.stepper .step').removeClass('active'); $('.stepper .step-2').addClass('active'); $('#paso2').show(); });
  $('#btnVolver1').on('click', function(){ $('#paso2').hide(); $('.stepper .step').removeClass('active'); $('.stepper .step-1').addClass('active'); $('#paso1').show(); });
  $('#btnPaso2').on('click', function(){
    const cod = $('#cod_expediente').val(); const fin = $('#fecha_finalizacion').val(); const desc = $('#descripcion').val();
    let html = `<div class=\"mb-2\"><strong>Expediente:</strong> ${cod}</div>`;
    html += `<div class=\"mb-2\"><strong>Finalización:</strong> ${fin||'-'}</div>`;
    if (desc) html += `<div class=\"mb-2\"><strong>Descripción:</strong> ${$('<div>').text(desc).html()}</div>`;
    const ids = Array.from(SELECCION.values());
    if (ids.length === 0) { html += '<hr><div class=\"text-muted\">Sin insumos</div>'; $('#resumenLic').html(html); }
    else {
      $.ajax({ url: BASE + '/ajax/insumos_por_ids.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ ids }) })
       .done(function(r){
         const items = r && r.data ? r.data : [];
         const porTipo = {}; const varios = [];
         items.forEach(it => { const t = it.tipo_insumo || 'N/D'; porTipo[t] = (porTipo[t]||0) + 1; if (t==='Varios') { varios.push(it); } });
         html += '<hr><h6>Resumen por tipo</h6><ul>';
         Object.keys(porTipo).forEach(t => { if (t !== 'Varios') { html += `<li><strong>${t}:</strong> ${porTipo[t]}</li>`; } });
         if (varios.length) { html += '<li><strong>Varios</strong><ul>'; varios.forEach(v => { const key = 'LIC_CANT_'+v.id_insumo; let cant = parseInt(localStorage.getItem(key)||v.cantidad||1,10); if (!Number.isFinite(cant) || cant<1) cant = 1; html += `<li>${$('<div>').text(v.nombre_insumo||'').html()} (Cantidad: ${cant})</li>`; }); html += '</ul></li>'; }
         html += '</ul>';
         $('#resumenLic').html(html);
       })
       .fail(function(){ $('#resumenLic').html(html + '<hr><div class=\"text-danger\">Error al cargar resumen</div>'); });
    }
    $('#paso2').hide(); $('.stepper .step').removeClass('active'); $('.stepper .step-3').addClass('active'); $('#paso3').show();
  });
  $('#btnVolver2').on('click', function(){ $('#paso3').hide(); $('.stepper .step').removeClass('active'); $('.stepper .step-2').addClass('active'); $('#paso2').show(); });
  $('#btnFinalizar').on('click', function(){
    const payload = {
      _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '',
      cod_expediente: $('#cod_expediente').val(),
      fecha_finalizacion: $('#fecha_finalizacion').val()||null,
      descripcion: $('#descripcion').val()||null,
      insumos: Array.from(SELECCION.values())
    };
    $.ajax({ url: BASE + '/ajax/licitaciones_save.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(payload) })
      .done(function(r){ if (!r.success) { showToast(r.error||'Error', 'error'); return; } showToast('Licitación creada', 'success'); window.location.href='licitaciones_listar.php'; })
      .fail(function(){ showToast('Error al guardar', 'error'); });
  });
});
</script>
<?php include '../../includes/footer.php'; ?>