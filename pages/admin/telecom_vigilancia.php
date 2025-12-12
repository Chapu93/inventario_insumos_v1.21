<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('telecom', 'ver');

$db = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar_serv') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("INSERT INTO sedes_vigilancia (id_sede, proveedor, estado_servicio, observaciones) VALUES (?,?,?,?)")
         ->execute([(int)$_POST['id_sede'], trim($_POST['proveedor']), trim($_POST['estado_servicio']), ($_POST['observaciones'] ?? null) ?: null]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia agregado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
      
    } elseif ($accion === 'editar_serv') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("UPDATE sedes_vigilancia SET id_sede=?, proveedor=?, estado_servicio=?, observaciones=? WHERE id_vigilancia=?")
         ->execute([(int)$_POST['id_sede'], trim($_POST['proveedor']), trim($_POST['estado_servicio']), ($_POST['observaciones'] ?? null) ?: null, (int)$_POST['id_vigilancia']]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia actualizado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
      
    } elseif ($accion === 'eliminar_serv') {
      verificarPermiso('telecom', 'eliminar');
      $db->prepare("DELETE FROM sedes_vigilancia_dispositivos WHERE id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia']]);
      $db->prepare("DELETE FROM sedes_vigilancia WHERE id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia']]);
      $_SESSION['mensaje'] = 'Servicio de vigilancia eliminado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
    }
    
    header('Location: telecom_vigilancia.php'); exit;
    
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); 
    $_SESSION['tipo_mensaje'] = 'danger'; 
    header('Location: telecom_vigilancia.php'); exit;
  }
}

// Filtros
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';
$filtro_sede = isset($_GET['sede']) ? $_GET['sede'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Consultas con filtros
$sql = "SELECT v.*, s.nombre_sede, l.id_localidad, l.nombre_localidad 
        FROM sedes_vigilancia v 
        JOIN sedes s ON s.id_sede=v.id_sede 
        JOIN localidades l ON l.id_localidad=s.id_localidad 
        WHERE 1=1";

$params = [];

// Filtro de localidad
if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

// Filtro de sede
if ($filtro_sede) {
    $sql .= " AND s.id_sede = ?";
    $params[] = $filtro_sede;
}

// Filtro de estado
if ($filtro_estado) {
    $sql .= " AND v.estado_servicio = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY l.nombre_localidad, s.nombre_sede, v.proveedor";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();

// Datos para filtros
$localidades = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad")->fetchAll();
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.id_localidad FROM sedes s JOIN localidades l ON l.id_localidad = s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();
$estados_vigilancia = ['Activo', 'Inactivo', 'De Baja'];

// Conteo de dispositivos y cámaras activas
$rowsDispCount = $db->query("SELECT id_vigilancia, COALESCE(SUM(cantidad),0) AS total_dispositivos FROM sedes_vigilancia_dispositivos GROUP BY id_vigilancia")->fetchAll();
$rowsCamsAct = $db->query("SELECT id_vigilancia, COALESCE(SUM(cantidad),0) AS cam_activas FROM sedes_vigilancia_dispositivos WHERE tipo_dispositivo='Cámara' AND estado='Activo' GROUP BY id_vigilancia")->fetchAll();

$dispCount = [];
$camActivas = [];
foreach ($rowsDispCount as $r) { $dispCount[(int)$r['id_vigilancia']] = (int)$r['total_dispositivos']; }
foreach ($rowsCamsAct as $r) { $camActivas[(int)$r['id_vigilancia']] = (int)$r['cam_activas']; }

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-video me-2"></i>Gestión de Vigilancia</h1>
        <?php if (tienePermiso('telecom', 'editar')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServ" onclick="resetModal()">
            <i class="fas fa-plus me-2"></i>Agregar Servicio
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select">
                <option value="">Todas las localidades</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>" 
                            <?php echo $filtro_localidad == $loc['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo $loc['nombre_localidad']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="sede" class="form-label">Sede</label>
            <select name="sede" id="sede" class="form-select">
                <option value="">Seleccione Localidad</option>
                <?php foreach ($sedes as $s): ?>
                    <option value="<?php echo $s['id_sede']; ?>" data-localidad="<?php echo $s['id_localidad']; ?>"
                            <?php echo $filtro_sede == $s['id_sede'] ? 'selected' : ''; ?>>
                        <?php echo $s['nombre_sede']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach ($estados_vigilancia as $est): ?>
                    <option value="<?php echo $est; ?>" 
                            <?php echo $filtro_estado == $est ? 'selected' : ''; ?>>
                        <?php echo $est; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end">
            <div class="d-grid gap-1 w-100">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="telecom_vigilancia.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Servicios (<?php echo count($servicios); ?>)
        </h5>
        <div class="small text-muted">
            Total dispositivos: <strong><?php echo array_sum($dispCount ?: []); ?></strong> · 
            Cámaras activas: <strong><?php echo array_sum($camActivas ?: []); ?></strong>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($servicios)): ?>
            <div class="text-center py-4">
                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay servicios de vigilancia registrados</h5>
                <p class="text-muted">Agregue el primer servicio para comenzar</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaVigilancia">
                    <thead>
                        <tr>
                            <th>Localidad</th>
                            <th>Sede</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th>Dispositivos</th>
                            <th>Cámaras Activas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($servicios as $v): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['nombre_localidad']); ?></td>
                            <td><strong><?php echo htmlspecialchars($v['nombre_sede']); ?></strong></td>
                            <td><?php echo htmlspecialchars($v['proveedor']); ?></td>
                            <td>
                                <?php 
                                $e = $v['estado_servicio']; 
                                $cls = $e === 'Activo' ? 'bg-success' : ($e === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger'); 
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo (int)($dispCount[(int)$v['id_vigilancia']] ?? 0); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo (int)($camActivas[(int)$v['id_vigilancia']] ?? 0); ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a class="btn btn-sm btn-info" 
                                       href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia_servicio.php?id_vigilancia=<?php echo (int)$v['id_vigilancia']; ?>" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles y dispositivos">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (tienePermiso('telecom', 'editar')): ?>
                                    <button class="btn btn-sm btn-warning btn-edit-serv" 
                                            data-bs-toggle="tooltip" 
                                            title="Editar servicio"
                                            data-row='<?php echo json_encode($v, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso('telecom', 'eliminar')): ?>
                                    <button class="btn btn-sm btn-danger" 
                                            data-bs-toggle="tooltip" 
                                            title="Eliminar servicio"
                                            onclick="delServ(<?php echo (int)$v['id_vigilancia']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agregar/Editar Servicio -->
<div class="modal fade" id="modalServ" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalServTitle">Agregar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formServ" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="accion" id="accionServ" value="agregar_serv">
                    <input type="hidden" name="id_vigilancia" id="id_vigilancia">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Localidad *</label>
                            <select id="id_localidad_serv" class="form-select" required>
                                <option value="">Seleccione una localidad</option>
                            </select>
                            <div class="invalid-feedback">Debe seleccionar una localidad</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sede *</label>
                            <select name="id_sede" id="id_sede_serv" class="form-select" required>
                                <option value="">Seleccione primero una localidad</option>
                            </select>
                            <div class="invalid-feedback">Debe seleccionar una sede</div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor *</label>
                            <input type="text" name="proveedor" id="proveedor" class="form-control" required placeholder="Ej. Prosegur, ADT, Local...">
                            <div class="invalid-feedback">El proveedor es obligatorio</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado *</label>
                            <select class="form-select" name="estado_servicio" id="estado_servicio" required>
                                <option value="Activo">Activo</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="De Baja">De Baja</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" id="observaciones_serv" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="formDelServ" method="POST" style="display:none">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="accion" value="eliminar_serv">
    <input type="hidden" name="id_vigilancia" id="del_serv">
</form>

<script>
const BASE = '<?php echo app_base_url(); ?>';

function resetModal() {
    $('#modalServTitle').text('Agregar Servicio');
    $('#accionServ').val('agregar_serv');
    $('#id_vigilancia').val('');
    $('#formServ')[0].reset();
    $('#formServ').removeClass('was-validated');
    $('#id_sede_serv').html('<option value="">Seleccione primero una localidad</option>');
    // Recargar localidades por si acaso
    cargarLocalidadesServ();
}

function editServ(v) { 
    console.log('Editando servicio:', v);
    $('#modalServTitle').text('Editar Servicio'); 
    $('#accionServ').val('editar_serv'); 
    $('#id_vigilancia').val(v.id_vigilancia); 
    $('#proveedor').val(v.proveedor); 
    $('#estado_servicio').val(v.estado_servicio); 
    $('#observaciones_serv').val(v.observaciones || ''); 
    
    // Cargar localidad y luego sedes
    // Primero aseguramos que las localidades estén cargadas
    cargarLocalidadesServ(function() {
        $('#id_localidad_serv').val(v.id_localidad);
        cargarSedesServ(v.id_localidad, function() {
            $('#id_sede_serv').val(v.id_sede);
            new bootstrap.Modal(document.getElementById('modalServ')).show();
        });
    });
}

function delServ(id) { 
    if(confirm('¿Está seguro de que desea eliminar este servicio y TODOS sus dispositivos asociados?')) { 
        $('#del_serv').val(id); 
        $('#formDelServ').submit(); 
    } 
}

function cargarLocalidadesServ(callback) { 
    $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r => { 
        const $l = $('#id_localidad_serv'); 
        $l.html('<option value="">Seleccione una localidad</option>'); 
        if(r.success) { 
            r.data.forEach(x => $l.append(`<option value="${x.id}">${x.nombre}</option>`)); 
        } 
        if (callback) callback();
    }); 
}

function cargarSedesServ(loc, callback) { 
    const $s = $('#id_sede_serv'); 
    $s.html('<option value="">Cargando...</option>'); 
    if(!loc) { 
        $s.html('<option value="">Seleccione primero una localidad</option>');
        return; 
    } 
    $.getJSON(`${BASE}/ajax/cargar_sedes.php`, { localidad_id: loc }).done(r => { 
        $s.html('<option value="">Seleccione una sede</option>');
        const data = r && r.data ? r.data : r; 
        const lista = data && data.sedes ? data.sedes : []; 
        lista.forEach(x => $s.append(`<option value="${parseInt(x.id,10)}">${x.nombre}</option>`)); 
        if (callback) callback();
    }); 
}

$(function() {
    // Carga inicial
    cargarLocalidadesServ();
    
    $('#id_localidad_serv').on('change', function() { 
        cargarSedesServ($(this).val()); 
    });
    
    // Validación Bootstrap
    $('#formServ').on('submit', function(e) { 
        if(!this.checkValidity()) { 
            e.preventDefault(); 
            e.stopPropagation(); 
        } 
        $(this).addClass('was-validated'); 
    });

    // Event delegation para botones editar
    $(document).on('click', '.btn-edit-serv', function(e) {
        e.preventDefault();
        var data = $(this).data('row');
        editServ(data);
    });
    
    // ========================================
    // FILTRADO DE SEDES POR LOCALIDAD
    // ========================================
    $('#localidad').on('change', function() {
        const idLocalidad = $(this).val();
        const sedeSelect = $('#sede');
        
        // Limpiar y deshabilitar si no hay localidad
        if (!idLocalidad) {
            sedeSelect.html('<option value="">Seleccione Localidad</option>').prop('disabled', true);
            return;
        }
        
        // Filtrar sedes
        const opciones = [`<option value="">Todas las sedes</option>`];
        $('[data-localidad]').each(function() {
            if ($(this).data('localidad') == idLocalidad) {
                opciones.push($(this)[0].outerHTML);
            }
        });
        
        sedeSelect.html(opciones.join('')).prop('disabled', false);
        
        // Mantener selección si existe
        const sedeActual = '<?php echo $filtro_sede; ?>';
        if (sedeActual) {
            sedeSelect.val(sedeActual);
        }
    });
    
    // Inicializar estado de sede al cargar
    if ($('#localidad').val()) {
        $('#localidad').trigger('change');
    } else {
        $('#sede').prop('disabled', true);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
