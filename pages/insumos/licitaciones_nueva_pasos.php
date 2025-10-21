<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Detectar si es edición
$esEdicion = false;
$idLicitacion = null;
$licitacionData = null;
$insumosExistentes = [];

if (isset($_GET['id']) && $_GET['id']) {
    $idLicitacion = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM licitaciones WHERE id_licitacion=?');
    $stmt->execute([$idLicitacion]);
    $licitacionData = $stmt->fetch();
    
    if ($licitacionData) {
        $esEdicion = true;
        $ins = $db->prepare('SELECT id_insumo FROM insumos WHERE id_licitacion=?');
        $ins->execute([$idLicitacion]);
        $insumosExistentes = array_column($ins->fetchAll(), 'id_insumo');
    }
}

// POST para crear/actualizar la licitación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        
        $db->beginTransaction();
        
        $codExpediente = trim($_POST['cod_expediente'] ?? '');
        $fechaFin = $_POST['fecha_finalizacion'] ?: null;
        $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
        $insumos = isset($_POST['insumos']) && is_array($_POST['insumos']) ? array_map('intval', $_POST['insumos']) : [];
        
        if ($codExpediente === '') {
            throw new Exception('El código de expediente es obligatorio');
        }
        
        if ($esEdicion && $idLicitacion) {
            // Actualizar licitación existente
            $stmt = $db->prepare('UPDATE licitaciones SET cod_expediente=?, descripcion=?, fecha_finalizacion=? WHERE id_licitacion=?');
            $stmt->execute([$codExpediente, $descripcion, $fechaFin, $idLicitacion]);
            $id = $idLicitacion;
        } else {
            // Crear nueva licitación
            $stmt = $db->prepare('INSERT INTO licitaciones (cod_expediente, descripcion, fecha_finalizacion) VALUES (?,?,?)');
            $stmt->execute([$codExpediente, $descripcion, $fechaFin]);
            $id = (int)$db->lastInsertId();
        }
        
        // Desasignar insumos actuales de esta licitación
        $db->prepare('UPDATE insumos SET id_licitacion=NULL WHERE id_licitacion=?')->execute([$id]);
        
        // Asignar los nuevos insumos seleccionados
        if (!empty($insumos)) {
            $in = implode(',', array_fill(0, count($insumos), '?'));
            $params = array_merge([$id], $insumos, [$id]);
            $db->prepare("UPDATE insumos SET id_licitacion=? WHERE id_insumo IN ($in) AND (id_licitacion IS NULL OR id_licitacion=?)")
               ->execute($params);
        }
        
        $db->commit();
        $_SESSION['mensaje'] = $esEdicion ? 'Licitación actualizada correctamente' : 'Licitación creada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . app_base_url() . '/pages/insumos/licitaciones_listar.php');
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../../includes/header.php';
?>

<style>
/* Estilos para el wizard de licitaciones */
.wizard-container {
    max-width: 1200px;
    margin: 0 auto;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0e0e0;
    z-index: 0;
}

.wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.wizard-step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e0e0e0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
    color: #999;
    transition: all 0.3s;
}

.wizard-step.active .wizard-step-circle {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.wizard-step.completed .wizard-step-circle {
    background: var(--success-color);
    border-color: var(--success-color);
    color: white;
}

.wizard-step-label {
    font-size: 0.875rem;
    color: #666;
    font-weight: 500;
}

.wizard-step.active .wizard-step-label {
    color: var(--primary-color);
    font-weight: 600;
}

.wizard-step.completed .wizard-step-label {
    color: var(--success-color);
}

.wizard-content {
    display: none;
}

.wizard-content.active {
    display: block;
}

.wizard-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e0e0e0;
}

.insumo-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s;
    cursor: pointer;
    background: white;
}

.insumo-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(90, 147, 103, 0.1);
}

.insumo-card.selected {
    border-color: var(--primary-color);
    background: rgba(90, 147, 103, 0.05);
}

.insumo-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.badge-tipo {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.resumen-item {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

#contadorSeleccionados {
    min-width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="wizard-container">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="mb-0">
                <i class="fas fa-file-signature me-2"></i>
                <?php echo $esEdicion ? 'Editar Licitación' : 'Nueva Licitación'; ?>
            </h1>
            <a href="licitaciones_listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <!-- Wizard Steps -->
    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1">
            <div class="wizard-step-circle">1</div>
            <div class="wizard-step-label">Datos de la Licitación</div>
        </div>
        <div class="wizard-step" data-step="2">
            <div class="wizard-step-circle">2</div>
            <div class="wizard-step-label">Selección de Insumos</div>
        </div>
        <div class="wizard-step" data-step="3">
            <div class="wizard-step-circle">3</div>
            <div class="wizard-step-label">Confirmación</div>
        </div>
    </div>

    <form method="POST" id="formLicitacion" class="needs-validation" novalidate>
        <?php echo csrf_input(); ?>
        <?php if ($esEdicion && $idLicitacion): ?>
            <input type="hidden" name="id_licitacion" value="<?php echo $idLicitacion; ?>">
        <?php endif; ?>

        <!-- Paso 1: Datos de la Licitación -->
        <div class="wizard-content active" data-step="1">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información de la Licitación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cod_expediente" class="form-label">
                                Código de Expediente <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="cod_expediente" 
                                name="cod_expediente" 
                                required
                                value="<?php echo $esEdicion && $licitacionData ? htmlspecialchars($licitacionData['cod_expediente']) : ''; ?>"
                                placeholder="Ej: EXP-2025-001"
                            >
                            <div class="invalid-feedback">Por favor ingrese el código de expediente.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fecha_finalizacion" class="form-label">
                                Fecha de Finalización
                            </label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="fecha_finalizacion" 
                                name="fecha_finalizacion"
                                value="<?php echo $esEdicion && $licitacionData && $licitacionData['fecha_finalizacion'] ? $licitacionData['fecha_finalizacion'] : ''; ?>"
                            >
                        </div>
                        
                        <div class="col-12">
                            <label for="descripcion" class="form-label">
                                Descripción
                            </label>
                            <textarea 
                                class="form-control" 
                                id="descripcion" 
                                name="descripcion" 
                                rows="4"
                                placeholder="Descripción detallada de la licitación..."
                            ><?php echo $esEdicion && $licitacionData && $licitacionData['descripcion'] ? htmlspecialchars($licitacionData['descripcion']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paso 2: Selección de Insumos -->
        <div class="wizard-content" data-step="2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes me-2"></i>Selección de Insumos
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <a href="agregar.php?from=licitacion" class="btn btn-success btn-sm" target="_blank">
                            <i class="fas fa-plus me-1"></i>Nuevo Insumo
                        </a>
                        <span class="badge bg-primary" id="contadorSeleccionados">0</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                class="form-control" 
                                id="filtroBusqueda" 
                                placeholder="Buscar por nombre, serie, ID..."
                            >
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filtroTipo">
                                <option value="">Todos los tipos</option>
                                <option value="Varios">Varios</option>
                                <option value="PC Completa">PC Completa</option>
                                <option value="Notebook">Notebook</option>
                                <option value="Impresora">Impresora</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Escaner">Escaner</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btnLimpiarFiltros">
                                <i class="fas fa-eraser me-1"></i>Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Acciones de selección -->
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSeleccionarTodos">
                            <i class="fas fa-check-double me-1"></i>Seleccionar todos
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeseleccionarTodos">
                            <i class="fas fa-times me-1"></i>Deseleccionar todos
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="btnRecargarInsumos">
                            <i class="fas fa-sync me-1"></i>Recargar
                        </button>
                    </div>

                    <!-- Lista de insumos -->
                    <div id="listaInsumos" style="max-height: 500px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando insumos disponibles...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paso 3: Confirmación -->
        <div class="wizard-content" data-step="3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Confirmación de Licitación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos de la Licitación</h6>
                            <div class="resumen-item">
                                <strong>Código de Expediente:</strong>
                                <div id="resumenCodigo" class="text-muted">-</div>
                            </div>
                            <div class="resumen-item">
                                <strong>Fecha de Finalización:</strong>
                                <div id="resumenFecha" class="text-muted">-</div>
                            </div>
                            <div class="resumen-item">
                                <strong>Descripción:</strong>
                                <div id="resumenDescripcion" class="text-muted">-</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">
                                Insumos Seleccionados 
                                <span class="badge bg-primary" id="resumenCantidad">0</span>
                            </h6>
                            <div id="resumenInsumos" style="max-height: 400px; overflow-y: auto;">
                                <p class="text-muted">No hay insumos seleccionados</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Por favor, revise todos los datos antes de confirmar la licitación.
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Actions -->
        <div class="wizard-actions">
            <button type="button" class="btn btn-secondary" id="btnAnterior" style="display: none;">
                <i class="fas fa-arrow-left me-1"></i>Anterior
            </button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-primary" id="btnSiguiente">
                    Siguiente<i class="fas fa-arrow-right ms-1"></i>
                </button>
                <button type="submit" class="btn btn-success" id="btnConfirmar" style="display: none;">
                    <i class="fas fa-check me-1"></i>Confirmar Licitación
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const BASE = '<?php echo app_base_url(); ?>';
const ES_EDICION = <?php echo $esEdicion ? 'true' : 'false'; ?>;
const INSUMOS_EXISTENTES = <?php echo json_encode($insumosExistentes); ?>;

let pasoActual = 1;
let insumosDisponibles = [];
let insumosSeleccionados = new Set(INSUMOS_EXISTENTES);

// Navegación entre pasos
function irAPaso(paso) {
    // Validar paso actual antes de avanzar
    if (paso > pasoActual) {
        if (pasoActual === 1 && !validarPaso1()) {
            return;
        }
    }
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.wizard-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Actualizar estados de los pasos
    document.querySelectorAll('.wizard-step').forEach(step => {
        const stepNum = parseInt(step.dataset.step);
        step.classList.remove('active', 'completed');
        
        if (stepNum === paso) {
            step.classList.add('active');
        } else if (stepNum < paso) {
            step.classList.add('completed');
        }
    });
    
    // Mostrar contenido del paso
    const content = document.querySelector(`.wizard-content[data-step="${paso}"]`);
    if (content) {
        content.classList.add('active');
    }
    
    // Actualizar botones
    document.getElementById('btnAnterior').style.display = paso > 1 ? 'block' : 'none';
    document.getElementById('btnSiguiente').style.display = paso < 3 ? 'block' : 'none';
    document.getElementById('btnConfirmar').style.display = paso === 3 ? 'block' : 'none';
    
    // Acciones específicas por paso
    if (paso === 2 && insumosDisponibles.length === 0) {
        cargarInsumos();
    } else if (paso === 3) {
        actualizarResumen();
    }
    
    pasoActual = paso;
}

// Validar paso 1
function validarPaso1() {
    const form = document.getElementById('formLicitacion');
    const codExpediente = document.getElementById('cod_expediente');
    
    if (!codExpediente.value.trim()) {
        codExpediente.classList.add('is-invalid');
        codExpediente.focus();
        return false;
    }
    
    codExpediente.classList.remove('is-invalid');
    return true;
}

// Cargar insumos disponibles
function cargarInsumos() {
    const container = document.getElementById('listaInsumos');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2 text-muted">Cargando insumos disponibles...</p></div>';
    
    fetch(BASE + '/ajax/insumos_listar_disponibles_lic.php')
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) {
                throw new Error(resp.error || 'Error al cargar insumos');
            }
            
            insumosDisponibles = resp.data || [];
            renderizarInsumos();
        })
        .catch(err => {
            container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${err.message}</div>`;
        });
}

// Renderizar lista de insumos
function renderizarInsumos() {
    const container = document.getElementById('listaInsumos');
    
    if (insumosDisponibles.length === 0) {
        container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No hay insumos disponibles.</div>';
        return;
    }
    
    const filtro = document.getElementById('filtroBusqueda').value.toLowerCase();
    const tipo = document.getElementById('filtroTipo').value;
    
    const insumosFiltrados = insumosDisponibles.filter(ins => {
        let cumple = true;
        
        if (tipo && ins.tipo !== tipo) {
            cumple = false;
        }
        
        if (filtro) {
            const texto = `${ins.nombre} ${ins.numero_serie || ''} ${ins.id_fisico || ''}`.toLowerCase();
            if (!texto.includes(filtro)) {
                cumple = false;
            }
        }
        
        return cumple;
    });
    
    if (insumosFiltrados.length === 0) {
        container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-filter me-2"></i>No se encontraron insumos con los filtros aplicados.</div>';
        return;
    }
    
    let html = '';
    insumosFiltrados.forEach(ins => {
        const isSelected = insumosSeleccionados.has(ins.id);
        const badgeColor = ins.tipo === 'Varios' ? 'secondary' : 'info';
        
        html += `
            <div class="insumo-card ${isSelected ? 'selected' : ''}" data-id="${ins.id}" data-tipo="${ins.tipo}">
                <div class="d-flex align-items-start">
                    <input 
                        type="checkbox" 
                        class="me-3 mt-1" 
                        ${isSelected ? 'checked' : ''}
                        onchange="toggleInsumo(${ins.id})"
                    >
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${escapeHtml(ins.nombre)}</strong>
                                <span class="badge badge-tipo bg-${badgeColor} ms-2">${escapeHtml(ins.tipo)}</span>
                            </div>
                        </div>
                        ${ins.numero_serie ? `<div class="text-muted small mt-1"><i class="fas fa-barcode me-1"></i>S/N: ${escapeHtml(ins.numero_serie)}</div>` : ''}
                        ${ins.id_fisico ? `<div class="text-muted small"><i class="fas fa-tag me-1"></i>ID: ${escapeHtml(ins.id_fisico)}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    actualizarContador();
}

// Toggle selección de insumo
function toggleInsumo(id) {
    if (insumosSeleccionados.has(id)) {
        insumosSeleccionados.delete(id);
    } else {
        insumosSeleccionados.add(id);
    }
    
    const card = document.querySelector(`.insumo-card[data-id="${id}"]`);
    if (card) {
        card.classList.toggle('selected');
    }
    
    actualizarContador();
}

// Actualizar contador
function actualizarContador() {
    const count = insumosSeleccionados.size;
    document.getElementById('contadorSeleccionados').textContent = count;
}

// Actualizar resumen
function actualizarResumen() {
    const codExpediente = document.getElementById('cod_expediente').value;
    const fechaFin = document.getElementById('fecha_finalizacion').value;
    const descripcion = document.getElementById('descripcion').value;
    
    document.getElementById('resumenCodigo').textContent = codExpediente || '-';
    document.getElementById('resumenFecha').textContent = fechaFin ? formatearFecha(fechaFin) : 'No especificada';
    document.getElementById('resumenDescripcion').textContent = descripcion || 'Sin descripción';
    document.getElementById('resumenCantidad').textContent = insumosSeleccionados.size;
    
    // Renderizar insumos seleccionados
    const container = document.getElementById('resumenInsumos');
    
    if (insumosSeleccionados.size === 0) {
        container.innerHTML = '<p class="text-muted">No hay insumos seleccionados</p>';
        return;
    }
    
    const insumosArray = insumosDisponibles.filter(ins => insumosSeleccionados.has(ins.id));
    
    // Agrupar por tipo
    const porTipo = {};
    insumosArray.forEach(ins => {
        if (!porTipo[ins.tipo]) {
            porTipo[ins.tipo] = [];
        }
        porTipo[ins.tipo].push(ins);
    });
    
    let html = '';
    Object.keys(porTipo).sort().forEach(tipo => {
        html += `
            <div class="mb-3">
                <h6 class="text-primary"><i class="fas fa-box me-2"></i>${tipo} (${porTipo[tipo].length})</h6>
                <ul class="list-unstyled ms-3">
        `;
        
        porTipo[tipo].forEach(ins => {
            html += `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${escapeHtml(ins.nombre)}`;
            if (ins.numero_serie) {
                html += ` <small class="text-muted">(S/N: ${escapeHtml(ins.numero_serie)})</small>`;
            }
            html += `</li>`;
        });
        
        html += '</ul></div>';
    });
    
    container.innerHTML = html;
    
    // Agregar inputs hidden para los insumos seleccionados
    const form = document.getElementById('formLicitacion');
    document.querySelectorAll('input[name="insumos[]"]').forEach(el => el.remove());
    
    insumosSeleccionados.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'insumos[]';
        input.value = id;
        form.appendChild(input);
    });
}

// Utilidades
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatearFecha(fecha) {
    const d = new Date(fecha + 'T00:00:00');
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Event Listeners
document.getElementById('btnSiguiente').addEventListener('click', () => {
    irAPaso(pasoActual + 1);
});

document.getElementById('btnAnterior').addEventListener('click', () => {
    irAPaso(pasoActual - 1);
});

document.getElementById('filtroBusqueda').addEventListener('input', renderizarInsumos);
document.getElementById('filtroTipo').addEventListener('change', renderizarInsumos);

document.getElementById('btnLimpiarFiltros').addEventListener('click', () => {
    document.getElementById('filtroBusqueda').value = '';
    document.getElementById('filtroTipo').value = '';
    renderizarInsumos();
});

document.getElementById('btnSeleccionarTodos').addEventListener('click', () => {
    const filtro = document.getElementById('filtroBusqueda').value.toLowerCase();
    const tipo = document.getElementById('filtroTipo').value;
    
    insumosDisponibles.forEach(ins => {
        let cumple = true;
        
        if (tipo && ins.tipo !== tipo) {
            cumple = false;
        }
        
        if (filtro) {
            const texto = `${ins.nombre} ${ins.numero_serie || ''} ${ins.id_fisico || ''}`.toLowerCase();
            if (!texto.includes(filtro)) {
                cumple = false;
            }
        }
        
        if (cumple) {
            insumosSeleccionados.add(ins.id);
        }
    });
    
    renderizarInsumos();
});

document.getElementById('btnDeseleccionarTodos').addEventListener('click', () => {
    insumosSeleccionados.clear();
    renderizarInsumos();
});

document.getElementById('btnRecargarInsumos').addEventListener('click', () => {
    cargarInsumos();
});

// Validación del formulario
document.getElementById('formLicitacion').addEventListener('submit', (e) => {
    if (insumosSeleccionados.size === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos un insumo para la licitación.');
        return false;
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    irAPaso(1);
});
</script>

<?php include '../../includes/footer.php'; ?>
