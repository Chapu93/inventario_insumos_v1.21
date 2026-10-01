<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('insumos', 'ver');

$db = conectarDB();

// 1. Obtener lista de localidades para el filtro
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-clipboard-list me-2"></i>Relevamientos
            </h1>
            <div class="btn-group">
                <?php if (tienePermiso('insumos', 'crear')): ?>
                    <a href="relevamiento_cargar.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Cargar Relevamiento
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-info text-white" id="btnPlanillaRelevamiento">
                    <i class="fas fa-file-pdf me-1"></i>Planilla de Relevamiento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtros-container mb-3">
    <form id="formFiltrosRelevamientos" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filtro_localidad" class="form-label">Localidad</label>
            <select id="filtro_localidad" name="id_localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>"><?php echo htmlspecialchars($loc['nombre_localidad']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="filtro_sede" class="form-label">Sede</label>
            <select id="filtro_sede" name="id_sede" class="form-select" disabled>
                <option value="">Seleccione Localidad</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filtro_fecha_desde" class="form-label">Desde</label>
            <input type="date" id="filtro_fecha_desde" name="fecha_desde" class="form-control">
        </div>

        <div class="col-md-2">
            <label for="filtro_fecha_hasta" class="form-label">Hasta</label>
            <input type="date" id="filtro_fecha_hasta" name="fecha_hasta" class="form-control">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiarFiltrosRelevamientos">
                    <i class="fas fa-times me-1"></i>Limpiar
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de relevamientos -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Relevamientos
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable" id="tablaRelevamientos" data-ssp="1" data-default-order-col="1" data-default-order-dir="desc">
                <thead>
                    <tr>
                        <th>Nº Remito</th>
                        <th>Fecha</th>
                        <th>Sede / Localidad</th>
                        <th>Responsable</th>
                        <th>Cargado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Botones de exportación -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" id="btnExportarExcel">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaRelevamientos', 'relevamientos')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal para ver detalle del relevamiento -->
<div class="modal fade" id="modalDetalleRelevamiento" tabindex="-1" aria-labelledby="modalDetalleRelevamientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleRelevamientoLabel">
                    <i class="fas fa-clipboard-check me-2"></i>Detalle del Relevamiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalDetalleRelevamientoBody">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 mb-0">Cargando detalles del relevamiento...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del insumo -->
<div class="modal fade" id="modalVerInsumo" tabindex="-1" aria-labelledby="modalVerInsumoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVerInsumoLabel">
                    <i class="fas fa-eye me-2"></i>Detalles del Insumo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalVerInsumoBody">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 mb-0">Cargando detalles del insumo...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btnEditarInsumo" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Editar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const BASE = getAppBase();

    // 1. Inicializar DataTable con Server-Side Processing
    const dtRelevamientos = $('#tablaRelevamientos').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: BASE + '/ajax/relevamientos_list_ssp.php',
            type: 'GET',
            data: function(d) {
                d.id_localidad = $('#filtro_localidad').val();
                d.id_sede = $('#filtro_sede').val();
                d.fecha_desde = $('#filtro_fecha_desde').val();
                d.fecha_hasta = $('#filtro_fecha_hasta').val();
            }
        },
        columns: [
            { data: 0, orderable: true },
            { data: 1, orderable: true },
            { data: 2, orderable: true },
            { data: 3, orderable: true },
            { data: 4, orderable: true },
            { data: 5, orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        drawCallback: function() {
            if (typeof inicializarTooltips === 'function') {
                inicializarTooltips();
            }
        }
    });

    // 2. Manejo de Localidad -> Sede en cascada
    $('#filtro_localidad').on('change', function() {
        const idLoc = $(this).val();
        const $sede = $('#filtro_sede');
        $sede.html('<option value="">Cargando sedes...</option>').prop('disabled', true);

        if (!idLoc) {
            $sede.html('<option value="">Todas las sedes</option>').prop('disabled', true);
            return;
        }

        $.getJSON(BASE + '/ajax/cargar_sedes.php', { localidad_id: idLoc })
            .done(function(resp) {
                const sedes = resp.data && resp.data.sedes ? resp.data.sedes : (resp.sedes || []);
                let html = '<option value="">Todas las sedes</option>';
                sedes.forEach(s => {
                    html += `<option value="${s.id}">${s.nombre}</option>`;
                });
                $sede.html(html).prop('disabled', false);
            })
            .fail(function() {
                $sede.html('<option value="">Error al cargar sedes</option>').prop('disabled', false);
            });
    });

    // 3. Envío del formulario de filtros
    $('#formFiltrosRelevamientos').on('submit', function(e) {
        e.preventDefault();
        dtRelevamientos.ajax.reload();
    });

    // 4. Limpiar filtros
    $('#btnLimpiarFiltrosRelevamientos').on('click', function() {
        $('#formFiltrosRelevamientos')[0].reset();
        $('#filtro_sede').html('<option value="">Todas las sedes</option>').prop('disabled', true);
        dtRelevamientos.ajax.reload();
    });

    // 5. Ver detalle del relevamiento en modal
    $(document).on('click', '.btn-ver-detalle-relevamiento', function() {
        const idRemito = $(this).data('id');
        const numRemito = $(this).data('numero');
        
        $('#modalDetalleRelevamientoLabel').html(`<i class="fas fa-clipboard-check me-2"></i>Detalle de Relevamiento: ${numRemito}`);
        $('#modalDetalleRelevamientoBody').html(`
            <div class="text-center py-4 text-muted">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 mb-0">Cargando detalles del relevamiento...</p>
            </div>
        `);

        const modalDetalle = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleRelevamiento'));
        modalDetalle.show();

        $.getJSON(BASE + '/ajax/relevamiento_detalle.php', { id_remito: idRemito })
            .done(function(res) {
                if (res.success && res.data) {
                    $('#modalDetalleRelevamientoBody').html(res.data.html);
                } else {
                    $('#modalDetalleRelevamientoBody').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>${res.error || 'Error al cargar los datos del relevamiento.'}
                        </div>
                    `);
                }
            })
            .fail(function() {
                $('#modalDetalleRelevamientoBody').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error de conexión al servidor.
                    </div>
                `);
            });
    });

    // 6. Exportar Excel
    $('#btnExportarExcel').on('click', function() {
        if (typeof exportarExcelSinColumnas === 'function') {
            exportarExcelSinColumnas('tablaRelevamientos', 'relevamientos_insumos', [5]);
        } else {
            window.location.href = BASE + '/pages/insumos/exportar_excel.php';
        }
    });

    // 7. Botón de Planilla de Relevamiento
    $('#btnPlanillaRelevamiento').on('click', function(e) {
        e.preventDefault();
        mostrarOpcionesRelevamiento();
    });
});

// Función para ver detalles del insumo en modal
function verInsumo(id) {
    const modalEl = document.getElementById('modalVerInsumo');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const modalBody = document.getElementById('modalVerInsumoBody');
    const btnEditar = document.getElementById('btnEditarInsumo');

    modalBody.innerHTML = `
        <div class="text-center py-4 text-muted">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Cargando detalles del insumo...</p>
        </div>
    `;

    modal.show();

    fetch(`${getAppBase()}/pages/insumos/ver_ajax.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
                if (btnEditar) {
                    btnEditar.onclick = () => {
                        window.location.href = `${getAppBase()}/pages/insumos/editar.php?id=${id}`;
                    };
                    btnEditar.style.display = 'inline-block';
                }
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles del insumo: ${data.error || 'No disponible'}
                    </div>
                `;
                if (btnEditar) btnEditar.style.display = 'none';
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los detalles del insumo: ${error.message}
                </div>
            `;
            if (btnEditar) btnEditar.style.display = 'none';
        });
}

// Funciones globales para modal de planillas de relevamiento (mismo estándar que insumos/listar.php)
function mostrarOpcionesRelevamiento() {
    const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('id', 'modalRelevamiento');
    modal.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-lg">' +
        '<div class="modal-content">' +
        '<div class="modal-header bg-primary text-white">' +
        '<h5 class="modal-title"><i class="fas fa-file-pdf me-2"></i>Planilla de Relevamiento</h5>' +
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
        '</div>' +
        '<div class="modal-body p-4">' +
        '<div class="row g-3">' +

        '<!-- Opción 1: Planilla de Insumos -->' +
        '<div class="col-md-4">' +
        '<div class="card border-primary h-100">' +
        '<div class="card-body d-flex flex-column justify-content-between">' +
        '<div>' +
        '<h6 class="card-title text-primary fw-bold mb-2">' +
        '<i class="fas fa-desktop me-2"></i>Planilla de Insumos' +
        '</h6>' +
        '<p class="text-muted small mb-3">' +
        'Contiene PC, Impresora, Escáner, Monitores, Periféricos, Estabilizadores y Notebooks.' +
        '</p>' +
        '</div>' +
        '<button type="button" class="btn btn-primary w-100 mt-2" onclick="generarRelevamientoInsumos()">' +
        '<i class="fas fa-file-pdf me-2"></i>Generar Insumos' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- Opción 2: Planilla de Infraestructura de Red -->' +
        '<div class="col-md-4">' +
        '<div class="card border-info h-100">' +
        '<div class="card-body d-flex flex-column justify-content-between">' +
        '<div>' +
        '<h6 class="card-title text-info fw-bold mb-2">' +
        '<i class="fas fa-network-wired me-2"></i>Infraestructura de Red' +
        '</h6>' +
        '<p class="text-muted small mb-3">' +
        'Proveedor, tipo de conexión, velocidad, WiFi y tabla para 15 dispositivos de red.' +
        '</p>' +
        '</div>' +
        '<button type="button" class="btn btn-info text-white w-100 mt-2" onclick="generarRelevamientoRed()">' +
        '<i class="fas fa-file-pdf me-2"></i>Generar Red' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- Opción 3: Planilla de Sistema de Vigilancia -->' +
        '<div class="col-md-4">' +
        '<div class="card border-secondary h-100">' +
        '<div class="card-body d-flex flex-column justify-content-between">' +
        '<div>' +
        '<h6 class="card-title text-secondary fw-bold mb-2">' +
        '<i class="fas fa-video me-2"></i>Sistema de Vigilancia' +
        '</h6>' +
        '<p class="text-muted small mb-3">' +
        'Proveedor, tipo de sistema y tabla de relevamiento para 15 dispositivos de seguridad.' +
        '</p>' +
        '</div>' +
        '<button type="button" class="btn btn-secondary w-100 mt-2" onclick="generarRelevamientoVigilancia()">' +
        '<i class="fas fa-file-pdf me-2"></i>Generar Vigilancia' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '</div><!-- /row -->' +

        '<!-- Opción 4: Planilla Personalizada / Parcial de Insumos -->' +
        '<div class="card border-primary mt-4">' +
        '<div class="card-body">' +
        '<h6 class="card-title text-primary fw-bold mb-2">' +
        '<i class="fas fa-sliders me-2"></i>Planilla Personalizada / Parcial de Insumos' +
        '</h6>' +
        '<p class="text-muted small mb-3">' +
        'Seleccione únicamente los insumos que desea incluir en la planilla. Los campos no seleccionados se ocultarán y la tarjeta adaptará su espacio disponible.' +
        '</p>' +
        '<div id="badgeFormatoResultante" class="badge p-2.5 w-100 fs-6 bg-primary text-white mb-3 text-wrap text-start" style="line-height: 1.4;">' +
        '<i class="fas fa-th-large me-2"></i>Formato resultante: <strong>2x2 Combinado</strong> (2 tarjetas Insumos arriba + 2 tarjetas Notebooks abajo)' +
        '</div>' +
        '<div class="row g-3 mb-3">' +
        '<div class="col-md-6 d-flex flex-column gap-2">' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncPc" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncPc">PC de Escritorio</label>' +
        '</div>' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncMon" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncMon">Monitores</label>' +
        '</div>' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncImp" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncImp">Impresoras</label>' +
        '</div>' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncEsc" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncEsc">Escáneres</label>' +
        '</div>' +
        '</div>' +
        '<div class="col-md-6 d-flex flex-column gap-2">' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncPeri" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncPeri">Periféricos</label>' +
        '</div>' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncEst" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncEst">Estabilizadores</label>' +
        '</div>' +
        '<div class="form-check">' +
        '<input class="form-check-input" type="checkbox" id="chkIncNb" checked>' +
        '<label class="form-check-label fw-semibold" for="chkIncNb">Notebooks</label>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<button type="button" class="btn btn-outline-primary w-100" onclick="generarRelevamientoParcial()">' +
        '<i class="fas fa-filter me-2"></i>Generar Planilla Parcial' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    $(modal).find('.form-check-input').on('change', function () {
        actualizarIndicadorFormatoModal();
    });

    modal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

function cerrarModalRelevamiento() {
    const modalEl = document.getElementById('modalRelevamiento');
    if (modalEl) {
        const bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) {
            bsModal.hide();
        }
    }
}

function actualizarIndicadorFormatoModal() {
    const incPc   = $('#chkIncPc').is(':checked');
    const incMon  = $('#chkIncMon').is(':checked');
    const incImp  = $('#chkIncImp').is(':checked');
    const incEsc  = $('#chkIncEsc').is(':checked');
    const incPeri = $('#chkIncPeri').is(':checked');
    const incEst  = $('#chkIncEst').is(':checked');
    const incNb   = $('#chkIncNb').is(':checked');

    let numCampos = 0;
    if (incPc)   numCampos += 7;
    if (incMon)  numCampos += 4;
    if (incImp)  numCampos += 3;
    if (incEsc)  numCampos += 3;
    if (incPeri) numCampos += 6;
    if (incEst)  numCampos += 1;

    const tieneInsumos = numCampos > 0;
    let textoFormato = '';
    let badgeClass = 'bg-primary';

    if (tieneInsumos && incNb) {
        textoFormato = 'Formato resultante: <strong>2x2 Combinado</strong> (2 tarjetas Insumos arriba + 2 tarjetas Notebooks abajo)';
        badgeClass = 'bg-primary';
    } else if (tieneInsumos && !incNb) {
        if (numCampos <= 18) {
            textoFormato = 'Formato resultante: <strong>2x2</strong> (4 tarjetas de Insumos por hoja - 2 filas x 2 columnas)';
            badgeClass = 'bg-success';
        } else {
            textoFormato = 'Formato resultante: <strong>2x1</strong> (2 tarjetas de Insumos a la altura completa de la página)';
            badgeClass = 'bg-info text-dark';
        }
    } else if (!tieneInsumos && incNb) {
        textoFormato = 'Formato resultante: <strong>2x2</strong> (4 tarjetas de Notebooks por hoja - 2 filas x 2 columnas)';
        badgeClass = 'bg-secondary';
    } else {
        textoFormato = 'Atención: Seleccione al menos un insumo para generar la planilla.';
        badgeClass = 'bg-warning text-dark';
    }

    $('#badgeFormatoResultante')
        .attr('class', 'badge p-2.5 w-100 fs-6 text-wrap text-start mb-3 ' + badgeClass)
        .html('<i class="fas fa-th-large me-2"></i>' + textoFormato);
}

function generarRelevamientoInsumos() {
    const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
    abrirVisorPDF(baseUrl + '/pages/reportes/relevamientos_pdf.php?tipo=insumos', 'Planilla de Relevamiento de Insumos');
    cerrarModalRelevamiento();
}

function generarRelevamientoRed() {
    const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
    abrirVisorPDF(baseUrl + '/pages/reportes/relevamientos_pdf.php?tipo=red', 'Planilla de Infraestructura de Red');
    cerrarModalRelevamiento();
}

function generarRelevamientoVigilancia() {
    const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
    abrirVisorPDF(baseUrl + '/pages/reportes/relevamientos_pdf.php?tipo=vigilancia', 'Planilla de Sistema de Vigilancia');
    cerrarModalRelevamiento();
}

function generarRelevamientoParcial() {
    const baseUrl = getAppBase ? getAppBase() : window.APP_BASE_URL || '';
    const incPc = $('#chkIncPc').is(':checked') ? 1 : 0;
    const incMon = $('#chkIncMon').is(':checked') ? 1 : 0;
    const incImp = $('#chkIncImp').is(':checked') ? 1 : 0;
    const incEsc = $('#chkIncEsc').is(':checked') ? 1 : 0;
    const incPeri = $('#chkIncPeri').is(':checked') ? 1 : 0;
    const incEst = $('#chkIncEst').is(':checked') ? 1 : 0;
    const incNb = $('#chkIncNb').is(':checked') ? 1 : 0;

    const url = baseUrl + '/pages/reportes/relevamientos_pdf.php?tipo=parcial' +
        '&inc_pc=' + incPc +
        '&inc_mon=' + incMon +
        '&inc_imp=' + incImp +
        '&inc_esc=' + incEsc +
        '&inc_peri=' + incPeri +
        '&inc_est=' + incEst +
        '&inc_nb=' + incNb;
    
    abrirVisorPDF(url, 'Planilla de Relevamiento Personalizada');
    cerrarModalRelevamiento();
}
</script>
