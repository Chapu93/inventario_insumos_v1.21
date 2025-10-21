<?php
require_once '../../includes/config.php';
include '../../includes/header.php';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Nueva Licitación</h4>
    <a href="licitaciones_listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form id="formLicFull" class="needs-validation" novalidate>
      <?php echo csrf_input(); ?>
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
      <hr>
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
          <a href="agregar.php?from=licitacion&back=<?php echo urlencode(app_base_url().'/pages/insumos/licitaciones_nueva.php'); ?>" id="btnNuevoInsumo" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nuevo Insumo</a>
        </div>
      </div>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos Disponibles</h6>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSeleccionarFiltrados"><i class="fas fa-check-double"></i> Seleccionar filtrados</button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeseleccionar"><i class="fas fa-times"></i> Deseleccionar todo</button>
            <span class="badge bg-secondary" id="contadorSeleccionLic">0</span>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-flat table-hover" id="tablaInsumosLicDisponibles"><tbody></tbody></table>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-primary" id="btnGuardar">Guardar Licitación</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
const BASE = '<?php echo app_base_url(); ?>';
let SELECCION = new Set();

function actualizarContador(){ document.getElementById('contadorSeleccionLic').textContent = String(SELECCION.size); }

function filtrar(){
  const tipo = (document.getElementById('filtro_tipo').value||'').toLowerCase();
  const q = (document.getElementById('filtro_busqueda').value||'').toLowerCase();
  document.querySelectorAll('#tablaInsumosLicDisponibles tbody tr').forEach(function(tr){
    const t = (tr.getAttribute('data-tipo')||'').toLowerCase();
    const txt = (tr.getAttribute('data-texto')||'');
    let show = true;
    if (tipo && t!==tipo) show = false;
    if (q && !txt.includes(q)) show = false;
    tr.style.display = show ? '' : 'none';
  });
}

function renderFila(it){
  const isSel = SELECCION.has(it.id);
  const saved = parseInt(localStorage.getItem('LIC_CANT_'+it.id)||'1',10) || 1;
  const controls = it.tipo==='Varios'
    ? `<div class="cantidad-input d-inline-flex align-items-center gap-2 ms-2 ${isSel?'':'d-none'}"><label class="small text-muted mb-0">Cant.</label><input type="number" class="form-control form-control-sm" min="1" max="${it.max||1}" value="${saved}" data-cantidad-id="${it.id}" style="width:84px;"></div>`
    : '';
  return `<tr class="fila-insumo" data-id="${it.id}" data-tipo="${it.tipo}" data-texto="${(it.nombre||'').toLowerCase()}"><td><strong>${it.nombre}</strong></td><td class="text-end"><div class="d-inline-flex align-items-center justify-content-end gap-2">${controls}<button type="button" class="btn btn-sm ${isSel?'btn-primary':'btn-outline-primary'} btn-sel" data-id="${it.id}"><i class="fas ${isSel?'fa-minus':'fa-plus'}"></i> ${isSel?'Deseleccionar':'Seleccionar'}</button></div></td></tr>`;
}

function cargarDisponibles(){
  fetch(BASE + '/ajax/insumos_listar_disponibles_lic.php').then(r=>r.json()).then(resp => {
    const tb = document.querySelector('#tablaInsumosLicDisponibles tbody');
    tb.innerHTML = '';
    (resp.data||[]).forEach(it => { tb.insertAdjacentHTML('beforeend', renderFila(it)); });
    filtrar();
    ordenarSeleccionados();
    actualizarContador();
  }).catch(()=>{});
}

function ordenarSeleccionados(){
  const tb = document.querySelector('#tablaInsumosLicDisponibles tbody');
  const rows = Array.from(tb.querySelectorAll('tr'));
  rows.sort((a,b) => {
    const ida = parseInt(a.getAttribute('data-id'),10); const idb = parseInt(b.getAttribute('data-id'),10);
    const sa = SELECCION.has(ida) ? 0 : 1; const sb = SELECCION.has(idb) ? 0 : 1; return sa - sb;
  });
  tb.innerHTML = '';
  rows.forEach(r => tb.appendChild(r));
}

document.addEventListener('click', function(e){
  const selBtn = e.target.closest('.btn-sel');
  if (selBtn) {
    const id = parseInt(selBtn.getAttribute('data-id'),10);
    const tr = selBtn.closest('tr');
    if (SELECCION.has(id)) {
      SELECCION.delete(id);
      const qty = tr.querySelector('.cantidad-input'); if (qty) qty.classList.add('d-none');
      try { localStorage.removeItem('LIC_CANT_'+id); } catch(_){}
    } else {
      SELECCION.add(id);
      const qty = tr.querySelector('.cantidad-input'); if (qty) qty.classList.remove('d-none');
    }
    selBtn.classList.toggle('btn-primary'); selBtn.classList.toggle('btn-outline-primary');
    selBtn.innerHTML = `<i class="fas ${SELECCION.has(id)?'fa-minus':'fa-plus'}"></i> ${SELECCION.has(id)?'Deseleccionar':'Seleccionar'}`;
    ordenarSeleccionados();
    actualizarContador();
  }
  const qtyInput = e.target.closest('input[data-cantidad-id]');
  if (qtyInput) {
    const id = parseInt(qtyInput.getAttribute('data-cantidad-id'),10);
    let val = parseInt(qtyInput.value||'1',10); if (!Number.isFinite(val) || val<1) val = 1; qtyInput.value = String(val);
    try { localStorage.setItem('LIC_CANT_'+id, String(val)); } catch(_){}
  }
});

document.getElementById('filtro_busqueda').addEventListener('input', filtrar);

document.getElementById('filtro_tipo').addEventListener('change', filtrar);

document.getElementById('btnLimpiarFiltros').addEventListener('click', function(){
  document.getElementById('filtro_busqueda').value = '';
  document.getElementById('filtro_tipo').value = '';
  filtrar();
});

document.getElementById('btnSeleccionarFiltrados').addEventListener('click', function(){
  document.querySelectorAll('#tablaInsumosLicDisponibles tbody tr').forEach(function(tr){
    if (tr.style.display === 'none') return;
    const id = parseInt(tr.getAttribute('data-id'),10);
    if (!SELECCION.has(id)) {
      SELECCION.add(id);
      const qty = tr.querySelector('.cantidad-input'); if (qty) qty.classList.remove('d-none');
      const btn = tr.querySelector('.btn-sel'); if (btn) { btn.classList.remove('btn-outline-primary'); btn.classList.add('btn-primary'); btn.innerHTML = '<i class="fas fa-minus"></i> Deseleccionar'; }
    }
  });
  ordenarSeleccionados(); actualizarContador();
});

document.getElementById('btnDeseleccionar').addEventListener('click', function(){
  document.querySelectorAll('#tablaInsumosLicDisponibles tbody tr').forEach(function(tr){
    if (tr.style.display === 'none') return;
    const id = parseInt(tr.getAttribute('data-id'),10);
    if (SELECCION.has(id)) {
      SELECCION.delete(id);
      const qty = tr.querySelector('.cantidad-input'); if (qty) qty.classList.add('d-none');
      const btn = tr.querySelector('.btn-sel'); if (btn) { btn.classList.remove('btn-primary'); btn.classList.add('btn-outline-primary'); btn.innerHTML = '<i class="fas fa-plus"></i> Seleccionar'; }
      try { localStorage.removeItem('LIC_CANT_'+id); } catch(_){}
    }
  });
  ordenarSeleccionados(); actualizarContador();
});

function resumenPayload(){
  return {
    _csrf: (document.querySelector('meta[name="csrf-token"]')||{}).content || '',
    cod_expediente: document.getElementById('cod_expediente').value,
    fecha_finalizacion: document.getElementById('fecha_finalizacion').value || null,
    descripcion: document.getElementById('descripcion').value || null,
    insumos: Array.from(SELECCION.values())
  };
}

document.getElementById('btnGuardar').addEventListener('click', function(){
  const payload = resumenPayload();
  if (!payload.cod_expediente) { alert('Código expediente requerido'); return; }
  fetch(BASE + '/ajax/licitaciones_save.php', { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) })
    .then(r=>r.json())
    .then(resp => {
      if (!resp.success) throw new Error(resp.error||'Error al guardar');
      try { Object.keys(localStorage).forEach(k => { if (k.startsWith('LIC_')) localStorage.removeItem(k); }); } catch(_){}
      window.location.href = 'licitaciones_listar.php';
    })
    .catch(err => alert(err.message||'Error'));
});

// Si volvemos de crear un insumo nuevo
(function init(){
  try { const url = new URL(window.location.href); const added = url.searchParams.get('added_id'); if (added) { SELECCION.add(parseInt(added,10)); } } catch(_){}
  cargarDisponibles(); actualizarContador();
})();
</script>
<?php include '../../includes/footer.php'; ?>