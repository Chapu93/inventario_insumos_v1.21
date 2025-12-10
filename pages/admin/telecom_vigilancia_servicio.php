<?php
require_once '../../includes/config.php';

requerirAutenticacion();
verificarPermiso('telecom', 'ver');

$db = conectarDB();

$idVig = isset($_GET['id_vigilancia']) ? (int)$_GET['id_vigilancia'] : 0;
if ($idVig <= 0) { header('Location: telecom_vigilancia.php'); exit; }

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar_disp') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("INSERT INTO sedes_vigilancia_dispositivos (id_vigilancia, tipo_dispositivo, marca, modelo, cantidad, ubicacion, estado) VALUES (?,?,?,?,?,?,?)")
         ->execute([$idVig, trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado'])]);
      $_SESSION['mensaje'] = 'Dispositivo agregado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
      
    } elseif ($accion === 'editar_disp') {
      verificarPermiso('telecom', 'editar');
      $db->prepare("UPDATE sedes_vigilancia_dispositivos SET tipo_dispositivo=?, marca=?, modelo=?, cantidad=?, ubicacion=?, estado=? WHERE id_vigilancia_dispositivo=? AND id_vigilancia=?")
         ->execute([trim($_POST['tipo_dispositivo']), trim($_POST['marca'] ?? ''), trim($_POST['modelo'] ?? ''), max(1,(int)$_POST['cantidad']), trim($_POST['ubicacion'] ?? ''), trim($_POST['estado']), (int)$_POST['id_vigilancia_dispositivo'], $idVig]);
      $_SESSION['mensaje'] = 'Dispositivo actualizado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
      
    } elseif ($accion === 'eliminar_disp') {
      verificarPermiso('telecom', 'eliminar');
      $db->prepare("DELETE FROM sedes_vigilancia_dispositivos WHERE id_vigilancia_dispositivo = ? AND id_vigilancia = ?")->execute([(int)$_POST['id_vigilancia_dispositivo'], $idVig]);
      $_SESSION['mensaje'] = 'Dispositivo eliminado correctamente'; 
      $_SESSION['tipo_mensaje'] = 'success';
    }
    
    header('Location: telecom_vigilancia_servicio.php?id_vigilancia='.(int)$idVig); exit;
    
  } catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage(); 
    $_SESSION['tipo_mensaje'] = 'danger'; 
    header('Location: telecom_vigilancia_servicio.php?id_vigilancia='.(int)$idVig); exit;
  }
}

// Datos del servicio
$serv = $db->prepare("SELECT v.*, s.id_sede, s.nombre_sede, l.id_localidad, l.nombre_localidad 
                      FROM sedes_vigilancia v 
                      JOIN sedes s ON s.id_sede=v.id_sede 
                      JOIN localidades l ON l.id_localidad=s.id_localidad 
                      WHERE v.id_vigilancia=?");
$serv->execute([$idVig]);
$servicio = $serv->fetch();

if (!$servicio) { 
    $_SESSION['mensaje']='Servicio no encontrado'; 
    $_SESSION['tipo_mensaje']='warning'; 
    header('Location: telecom_vigilancia.php'); exit; 
}

// Dispositivos del servicio
$disps = $db->prepare("SELECT * FROM sedes_vigilancia_dispositivos WHERE id_vigilancia=? ORDER BY tipo_dispositivo, marca, modelo");
$disps->execute([$idVig]);
$dispositivos = $disps->fetchAll();

// KPIs
$totalDispositivos = 0;
$camarasActivas = 0;
foreach ($dispositivos as $d) {
    $totalDispositivos += (int)$d['cantidad'];
    if ($d['tipo_dispositivo'] === 'Cámara' && $d['estado'] === 'Activo') {
        $camarasActivas += (int)$d['cantidad'];
    }
}

// Tipos disponibles para filtros
$tiposDisponibles = array_unique(array_column($dispositivos, 'tipo_dispositivo'));
sort($tiposDisponibles);

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2"><i class="fas fa-eye me-2"></i>Detalle de Vigilancia</h1>
                <h4 class="text-secondary mb-0">
                    <?php echo htmlspecialchars($servicio['nombre_localidad']); ?> <span class="text-muted mx-2">/</span> <?php echo htmlspecialchars($servicio['nombre_sede']); ?>
                </h4>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?php echo app_base_url(); ?>/pages/admin/telecom_vigilancia.php">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
                <a class="btn btn-outline-info" href="<?php echo app_base_url(); ?>/pages/admin/sede_detalle.php?id_localidad=<?php echo (int)$servicio['id_localidad']; ?>&id_sede=<?php echo (int)$servicio['id_sede']; ?>">
                    <i class="fas fa-building me-2"></i>Ver Sede
                </a>
                <?php if (tienePermiso('telecom', 'editar')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDisp" onclick="resetModalDisp()">
                    <i class="fas fa-plus me-2"></i>Agregar Dispositivo
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Información del Servicio -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Servicio</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">Proveedor</label>
                    <div class="fs-5"><?php echo htmlspecialchars($servicio['proveedor']); ?></div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">Estado</label>
                    <div>
                        <?php 
                        $e = $servicio['estado_servicio']; 
                        $cls = $e === 'Activo' ? 'bg-success' : ($e === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger'); 
                        ?>
                        <span class="badge <?php echo $cls; ?> fs-6"><?php echo $e; ?></span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">Observaciones</label>
                    <div class="text-muted fst-italic">
                        <?php echo nl2br(htmlspecialchars($servicio['observaciones'] ?: 'Sin observaciones')); ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="h2 mb-0 text-primary"><?php echo $totalDispositivos; ?></div>
                        <div class="small text-muted">Total Dispositivos</div>
                    </div>
                    <div class="col-6">
                        <div class="h2 mb-0 text-success"><?php echo $camarasActivas; ?></div>
                        <div class="small text-muted">Cámaras Activas</div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <?php if (tienePermiso('telecom', 'editar')): ?>
                    <button class="btn btn-outline-warning" onclick='editServ(<?php echo json_encode($servicio, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'>
                        <i class="fas fa-edit me-2"></i>Editar Servicio
                    </button>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('telecom', 'eliminar')): ?>
                    <button class="btn btn-outline-danger" onclick="delServ(<?php echo (int)$servicio['id_vigilancia']; ?>)">
                        <i class="fas fa-trash me-2"></i>Eliminar Servicio
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Lista de Dispositivos -->
    <div class="col-md-8">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="fas fa-cctv me-2"></i>Dispositivos Instalados</h5>
                    <button class="btn btn-sm btn-outline-secondary" id="btnExport">
                        <i class="fas fa-file-export me-1"></i>Exportar CSV
                    </button>
                </div>
                <!-- Filtros integrados en el header -->
                <div class="row g-2">
                    <div class="col-md-6">
                        <select class="form-select form-select-sm" id="filterTipo">
                            <option value="">Todos los tipos</option>
                            <?php foreach($tiposDisponibles as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select form-select-sm" id="filterEstado">
                            <option value="">Todos los estados</option>
                            <option value="Activo">Activo</option>
                            <option value="De Baja">De Baja</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm mb-0" id="tblDisps">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Marca / Modelo</th>
                                <th>Cant.</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dispositivos as $d): ?>
                            <tr>
                                <td class="align-middle"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($d['tipo_dispositivo']); ?></span></td>
                                <td class="align-middle">
                                    <div class="fw-bold"><?php echo htmlspecialchars($d['marca'] ?: '-'); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($d['modelo'] ?: ''); ?></div>
                                </td>
                                <td class="align-middle"><span class="badge bg-secondary rounded-pill"><?php echo (int)$d['cantidad']; ?></span></td>
                                <td class="align-middle"><?php echo htmlspecialchars($d['ubicacion'] ?: '-'); ?></td>
                                <td class="align-middle">
                                    <?php 
                                    $e = $d['estado']; 
                                    $cls = $e === 'Activo' ? 'bg-success' : 'bg-danger'; 
                                    ?>
                                    <span class="badge <?php echo $cls; ?>"><?php echo $e; ?></span>
                                </td>
                                <td class="text-end align-middle">
                                    <div class="btn-group" role="group">
                                        <?php if (tienePermiso('telecom', 'editar')): ?>
                                        <button class="btn btn-sm btn-warning btn-edit-disp" 
                                                data-bs-toggle="tooltip" 
                                                title="Editar"
                                                data-row='<?php echo json_encode($d, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (tienePermiso('telecom', 'eliminar')): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                data-bs-toggle="tooltip" 
                                                title="Eliminar"
                                                onclick="delDisp(<?php echo (int)$d['id_vigilancia_dispositivo']; ?>)">
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
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Servicio (Reutilizado) -->
<div class="modal fade" id="modalServ" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formServ" class="needs-validation" novalidate action="telecom_vigilancia.php">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="accion" value="editar_serv">
                    <input type="hidden" name="id_vigilancia" id="id_vigilancia_serv">
                    <input type="hidden" name="id_sede" value="<?php echo (int)$servicio['id_sede']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Proveedor *</label>
                        <input type="text" name="proveedor" id="proveedor_serv" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado_servicio" id="estado_servicio_serv" required>
                            <option value="Activo">Activo</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="De Baja">De Baja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" id="observaciones_serv" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Dispositivo -->
<div class="modal fade" id="modalDisp" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDispTitle">Agregar Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formDisp" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="accion" id="accionDisp" value="agregar_disp">
                    <input type="hidden" name="id_vigilancia_dispositivo" id="id_vigilancia_dispositivo">
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Dispositivo *</label>
                        <select name="tipo_dispositivo" id="tipo_dispositivo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="DVR">DVR (Grabador Digital)</option>
                            <option value="NVR">NVR (Grabador de Red)</option>
                            <option value="Cámara">Cámara</option>
                            <option value="Sensor">Sensor / Alarma</option>
                            <option value="Monitor">Monitor</option>
                            <option value="Disco Rígido">Disco Rígido</option>
                            <option value="Fuente">Fuente de Alimentación</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" id="marca" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" id="modelo" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" min="1" name="cantidad" id="cantidad" class="form-control" required value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado *</label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="Activo">Activo</option>
                                <option value="De Baja">De Baja</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ubicación en Sede</label>
                        <input type="text" name="ubicacion" id="ubicacion" class="form-control" placeholder="Ej. Entrada principal, Pasillo...">
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

<!-- Forms Ocultos para Eliminar -->
<form id="formDelDisp" method="POST" style="display:none">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="accion" value="eliminar_disp">
    <input type="hidden" name="id_vigilancia_dispositivo" id="del_disp">
</form>

<form id="formDelServ" method="POST" action="telecom_vigilancia.php" style="display:none">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="accion" value="eliminar_serv">
    <input type="hidden" name="id_vigilancia" value="<?php echo (int)$idVig; ?>">
</form>

<script>
// Funciones para Servicio
function editServ(v) { 
    $('#id_vigilancia_serv').val(v.id_vigilancia); 
    $('#proveedor_serv').val(v.proveedor); 
    $('#estado_servicio_serv').val(v.estado_servicio); 
    $('#observaciones_serv').val(v.observaciones || ''); 
    new bootstrap.Modal(document.getElementById('modalServ')).show(); 
}

function delServ(id) { 
    if(confirm('¿ADVERTENCIA: Está seguro de que desea eliminar este servicio y TODOS sus dispositivos? Esta acción no se puede deshacer.')) { 
        document.getElementById('formDelServ').submit(); 
    } 
}

// Funciones para Dispositivos
function resetModalDisp() {
    $('#modalDispTitle').text('Agregar Dispositivo'); 
    $('#accionDisp').val('agregar_disp'); 
    $('#id_vigilancia_dispositivo').val(''); 
    $('#formDisp')[0].reset(); 
    $('#formDisp').removeClass('was-validated');
    $('#cantidad').val(1);
    $('#estado').val('Activo');
}

function editDisp(d) { 
    $('#modalDispTitle').text('Editar Dispositivo'); 
    $('#accionDisp').val('editar_disp'); 
    $('#id_vigilancia_dispositivo').val(d.id_vigilancia_dispositivo); 
    $('#tipo_dispositivo').val(d.tipo_dispositivo); 
    $('#marca').val(d.marca || ''); 
    $('#modelo').val(d.modelo || ''); 
    $('#cantidad').val(d.cantidad || 1); 
    $('#ubicacion').val(d.ubicacion || ''); 
    $('#estado').val(d.estado); 
    new bootstrap.Modal(document.getElementById('modalDisp')).show(); 
}

function delDisp(id) { 
    if(confirm('¿Eliminar este dispositivo?')) { 
        $('#del_disp').val(id); 
        $('#formDelDisp').submit(); 
    } 
}

// Inicialización
$(function() {
    // DataTables con filtros personalizados
    var table = $('#tblDisps').DataTable({
        "dom": 't', // Solo muestra la tabla (sin search, info, pagination)
        "paging": false, // Deshabilita paginación
        "order": [[ 0, "asc" ]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        }
    });

    $('#filterTipo').on('change', function() {
        table.column(0).search(this.value ? '^'+this.value+'$' : '', true, false).draw();
    });
    
    $('#filterEstado').on('change', function() {
        table.column(4).search(this.value ? '^'+this.value+'$' : '', true, false).draw();
    });

    // Validación Bootstrap
    $('.needs-validation').on('submit', function(e) { 
        if(!this.checkValidity()) { 
            e.preventDefault(); 
            e.stopPropagation(); 
        } 
        $(this).addClass('was-validated'); 
    });

    // Event delegation para botones editar dispositivo
    $(document).on('click', '.btn-edit-disp', function(e) {
        e.preventDefault();
        var data = $(this).data('row');
        editDisp(data);
    });

    // Exportar CSV
    $('#btnExport').on('click', function() {
        var csv = [];
        var rows = document.querySelectorAll("#tblDisps tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            
            // Omitir última columna (acciones)
            for (var j = 0; j < cols.length - 1; j++) 
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            
            csv.push(row.join(","));        
        }

        var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        var downloadLink = document.createElement("a");
        downloadLink.download = "dispositivos_vigilancia_<?php echo $idVig; ?>.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
