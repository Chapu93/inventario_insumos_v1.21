<?php
require_once '../../includes/config.php';

$db = conectarDB();

// Migraciones tolerantes (no destructivas) para asegurar columnas clave
try { $db->query("SELECT simetrico FROM sedes_internet LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE sedes_internet ADD COLUMN simetrico TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e2) {} }
try { $db->query("SELECT velocidad_mbps FROM sedes_internet LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE sedes_internet ADD COLUMN velocidad_mbps INT NULL"); } catch (Exception $e2) {} }
// NOTA: Las migraciones de instancia_pendiente y fecha_instalacion se ejecutan manualmente desde los archivos SQL
// Ver: sql/migracion_internet_instancias_pendientes.sql
// Ver: sql/agregar_fecha_instalacion_internet.sql

// Procesar POST (agregar/editar/eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        $accion = $_POST['accion'] ?? '';
        
        if ($accion === 'agregar' || $accion === 'editar') {
            // Obtener valores actuales si es edición (para preservar datos históricos)
            $valoresActuales = null;
            if ($accion === 'editar') {
                $stmtActual = $db->prepare("SELECT instancia_pendiente, fecha_solicitud_autorizacion, archivo_autorizacion, fecha_instalacion, fecha_baja FROM sedes_internet WHERE id_internet = ?");
                $stmtActual->execute([(int)$_POST['id_internet']]);
                $valoresActuales = $stmtActual->fetch();
            }
            
            // Procesar campos comunes
            $estado_servicio = trim($_POST['estado_servicio']);
            
            // Inicializar con valores actuales (preservar histórico) o null si es nuevo
            $instancia_pendiente = $valoresActuales['instancia_pendiente'] ?? null;
            $fecha_solicitud = $valoresActuales['fecha_solicitud_autorizacion'] ?? null;
            $archivo_autorizacion = $valoresActuales['archivo_autorizacion'] ?? null;
            $fecha_instalacion = $valoresActuales['fecha_instalacion'] ?? null;
            $fecha_baja = $valoresActuales['fecha_baja'] ?? null;
            
            // Procesar campos según el estado seleccionado
            if ($estado_servicio === 'Activo') {
                // Estado Activo: solo fecha de instalación obligatoria
                if (!empty($_POST['fecha_instalacion'])) {
                    $fecha_instalacion = $_POST['fecha_instalacion'];
                }
                
            } elseif ($estado_servicio === 'Pendiente') {
                // Estado Pendiente: actualizar instancia y fecha de solicitud
                if (!empty($_POST['instancia_pendiente'])) {
                    $instancia_pendiente = trim($_POST['instancia_pendiente']);
                }
                
                if (!empty($_POST['fecha_solicitud_autorizacion'])) {
                    $fecha_solicitud = $_POST['fecha_solicitud_autorizacion'];
                }
                
                // Si la instancia es "Autorización superior", procesar archivo PDF
                if ($instancia_pendiente === 'Autorización superior') {
                    // Procesar archivo PDF si se sube uno nuevo
                    if (isset($_FILES['archivo_autorizacion']) && $_FILES['archivo_autorizacion']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['archivo_autorizacion'];
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        // Validar que sea PDF
                        if ($extension !== 'pdf') {
                            throw new Exception('Solo se permiten archivos PDF');
                        }
                        
                        // Validar tamaño (5MB máximo)
                        if ($file['size'] > 5 * 1024 * 1024) {
                            throw new Exception('El archivo no debe superar 5MB');
                        }
                        
                        // Asegurar que el directorio existe y tiene permisos
                        $directorioDestino = __DIR__ . '/../../public/uploads/autorizaciones_internet';
                        if (!is_dir($directorioDestino)) {
                            if (!mkdir($directorioDestino, 0755, true)) {
                                throw new Exception('No se pudo crear el directorio de destino');
                            }
                        }
                        
                        if (!is_writable($directorioDestino)) {
                            throw new Exception('El directorio no tiene permisos de escritura. Contacte al administrador.');
                        }
                        
                        // Generar nombre único
                        $nombreArchivo = 'autorizacion_' . date('Ymd_His') . '_' . uniqid() . '.pdf';
                        $rutaDestino = $directorioDestino . '/' . $nombreArchivo;
                        
                        // Mover archivo
                        if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                            $error = error_get_last();
                            throw new Exception('Error al guardar el archivo: ' . ($error['message'] ?? 'Desconocido'));
                        }
                        
                        $archivo_autorizacion = 'public/uploads/autorizaciones_internet/' . $nombreArchivo;
                    }
                    // Si no se sube archivo nuevo, el valor ya está preservado de $valoresActuales
                }
                
            } elseif ($estado_servicio === 'De Baja') {
                // Estado De Baja: fecha de baja obligatoria (ingresada manualmente)
                if (!empty($_POST['fecha_baja'])) {
                    $fecha_baja = $_POST['fecha_baja'];
                }
            }
            
            // IMPORTANTE: Los valores históricos ya están preservados desde $valoresActuales
            // Solo se sobrescriben si el usuario ingresa nuevos valores
            
            if ($accion === 'agregar') {
                $stmt = $db->prepare("INSERT INTO sedes_internet (id_sede, proveedor, tipo_conexion, velocidad_mbps, simetrico, tiene_wifi, estado_servicio, instancia_pendiente, fecha_solicitud_autorizacion, archivo_autorizacion, fecha_instalacion, fecha_baja, observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    (int)$_POST['id_sede'],
                    trim($_POST['proveedor']),
                    trim($_POST['tipo_conexion']),
                    ($_POST['velocidad_mbps'] !== '' ? (int)$_POST['velocidad_mbps'] : null),
                    (isset($_POST['simetrico']) ? 1 : 0),
                    (isset($_POST['tiene_wifi']) ? 1 : 0),
                    $estado_servicio,
                    $instancia_pendiente,
                    $fecha_solicitud,
                    $archivo_autorizacion,
                    $fecha_instalacion,
                    $fecha_baja,
                    ($_POST['observaciones'] ?? null) ?: null,
                ]);
                $_SESSION['mensaje'] = 'Servicio de Internet agregado.';
                $_SESSION['tipo_mensaje'] = 'success';
            } else {
                $stmt = $db->prepare("UPDATE sedes_internet SET id_sede=?, proveedor=?, tipo_conexion=?, velocidad_mbps=?, simetrico=?, tiene_wifi=?, estado_servicio=?, instancia_pendiente=?, fecha_solicitud_autorizacion=?, archivo_autorizacion=?, fecha_instalacion=?, fecha_baja=?, observaciones=? WHERE id_internet=?");
                $stmt->execute([
                    (int)$_POST['id_sede'],
                    trim($_POST['proveedor']),
                    trim($_POST['tipo_conexion']),
                    ($_POST['velocidad_mbps'] !== '' ? (int)$_POST['velocidad_mbps'] : null),
                    (isset($_POST['simetrico']) ? 1 : 0),
                    (isset($_POST['tiene_wifi']) ? 1 : 0),
                    $estado_servicio,
                    $instancia_pendiente,
                    $fecha_solicitud,
                    $archivo_autorizacion,
                    $fecha_instalacion,
                    $fecha_baja,
                    ($_POST['observaciones'] ?? null) ?: null,
                    (int)$_POST['id_internet']
                ]);
                $_SESSION['mensaje'] = 'Servicio de Internet actualizado.';
                $_SESSION['tipo_mensaje'] = 'success';
            }
        } elseif ($accion === 'eliminar') {
            // Obtener y eliminar archivo si existe
            $stmt = $db->prepare("SELECT archivo_autorizacion FROM sedes_internet WHERE id_internet = ?");
            $stmt->execute([(int)$_POST['id_internet']]);
            $row = $stmt->fetch();
            if ($row && !empty($row['archivo_autorizacion'])) {
                $rutaArchivo = __DIR__ . '/../../' . $row['archivo_autorizacion'];
                if (file_exists($rutaArchivo)) {
                    @unlink($rutaArchivo);
                }
            }
            
            $stmt = $db->prepare("DELETE FROM sedes_internet WHERE id_internet = ?");
            $stmt->execute([(int)$_POST['id_internet']]);
            $_SESSION['mensaje'] = 'Servicio de Internet eliminado.';
            $_SESSION['tipo_mensaje'] = 'success';
        }
        header('Location: telecom_internet.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: telecom_internet.php');
        exit;
    }
}

// Listado
$sql = "SELECT si.*, s.nombre_sede, l.nombre_localidad, l.id_localidad
        FROM sedes_internet si
        JOIN sedes s ON s.id_sede = si.id_sede
        JOIN localidades l ON l.id_localidad = s.id_localidad
        ORDER BY l.nombre_localidad, s.nombre_sede";
$internet = $db->query($sql)->fetchAll();

// Sedes para select
$sedes = $db->query("SELECT s.id_sede, s.nombre_sede, l.nombre_localidad FROM sedes s JOIN localidades l ON l.id_localidad = s.id_localidad ORDER BY l.nombre_localidad, s.nombre_sede")->fetchAll();

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-wifi me-2"></i>Internet por Sede</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInternet"><i class="fas fa-plus me-2"></i>Agregar</button>
        </div>
    </div>
    </div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Listado (<?php echo count($internet); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($internet)): ?>
            <div class="text-center py-4">
                <i class="fas fa-wifi fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Sin registros</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaInternet" data-default-order-col="0" data-default-order-dir="asc">
                    <thead>
                        <tr>
                            <th>Localidad</th>
                            <th>Sede</th>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Velocidad (Mbps)</th>
                            <th>Simétrico</th>
                            <th>WiFi</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internet as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nombre_localidad']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nombre_sede']); ?></td>
                                <td><?php echo htmlspecialchars($row['proveedor']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['tipo_conexion']); ?></span></td>
                                <td><span class="badge bg-primary"><?php echo (int)($row['velocidad_mbps'] ?? 0); ?></span></td>
                <td>
                  <?php $sim = (int)($row['simetrico'] ?? 0); ?>
                  <span class="badge <?php echo $sim ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $sim ? 'Sí' : 'No'; ?></span>
                </td>
                <td>
                  <?php $wifi = (int)($row['tiene_wifi'] ?? 0); ?>
                  <span class="badge <?php echo $wifi ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $wifi ? 'Sí' : 'No'; ?></span>
                </td>
                                <td>
                                    <?php $est = $row['estado_servicio']; $cls = ($est==='Activo'?'estado-activa':($est==='Pendiente'?'estado-asignado':'estado-baja')); ?>
                                    <span class="badge <?php echo $cls; ?>"><?php echo $est; ?></span>
                                    
                                    <?php if ($est === 'Activo' && !empty($row['fecha_instalacion'])): ?>
                                        <br><small class="text-muted mt-1 d-block">
                                            <i class="fas fa-calendar-check me-1"></i>Instalado: <?php echo date('d/m/Y', strtotime($row['fecha_instalacion'])); ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php if ($est === 'Pendiente'): ?>
                                        <?php if (!empty($row['instancia_pendiente'])): ?>
                                            <br><small class="text-muted mt-1 d-block">
                                                <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($row['instancia_pendiente']); ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['fecha_solicitud_autorizacion'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-calendar me-1"></i>Solicitud: <?php echo date('d/m/Y', strtotime($row['fecha_solicitud_autorizacion'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php // Mostrar archivo de autorización siempre que exista, independiente del estado ?>
                                    <?php if (!empty($row['archivo_autorizacion'])): ?>
                                        <small class="d-block mt-1">
                                            <a href="<?php echo app_base_url() . '/' . htmlspecialchars($row['archivo_autorizacion']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-danger"
                                               data-bs-toggle="tooltip" 
                                               title="Ver archivo de autorización"
                                               aria-label="Ver PDF">
                                                <i class="fas fa-file-pdf me-1"></i>PDF
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php if ($est === 'De Baja' && !empty($row['fecha_baja'])): ?>
                                        <br><small class="text-muted mt-1 d-block">
                                            <i class="fas fa-calendar-times me-1"></i>Baja: <?php echo date('d/m/Y', strtotime($row['fecha_baja'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles del servicio" onclick='verDetallesInternet(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'><i class="fas fa-eye" aria-hidden="true"></i></button>
                                        <a href="<?php echo app_base_url(); ?>/pages/reportes/internet_historial_pdf.php?id=<?php echo (int)$row['id_internet']; ?>" target="_blank" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Generar PDF" aria-label="Generar PDF del historial"><i class="fas fa-file-pdf" aria-hidden="true"></i></a>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar" aria-label="Editar servicio" onclick='editarInternet(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit" aria-hidden="true"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar" aria-label="Eliminar servicio" onclick="eliminarInternet(<?php echo (int)$row['id_internet']; ?>)"><i class="fas fa-trash" aria-hidden="true"></i></button>
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

<!-- Botones de exportación -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" onclick="exportarExcel('tablaInternet', 'internet')">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaInternet', 'internet')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar/Editar Internet -->
<div class="modal fade" id="modalInternet" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalInternetTitle">Agregar Servicio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formInternet" class="needs-validation" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
          <input type="hidden" name="accion" id="accion" value="agregar">
          <input type="hidden" name="id_internet" id="id_internet">
          <input type="hidden" name="archivo_autorizacion_actual" id="archivo_autorizacion_actual">

          <div class="mb-3">
            <label class="form-label">Localidad *</label>
            <select id="id_localidad" class="form-select" required>
              <option value="">Seleccione una localidad</option>
            </select>
            <div class="invalid-feedback">Seleccione una localidad</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Sede *</label>
            <select name="id_sede" id="id_sede" class="form-select" required>
              <option value="">Seleccione una sede</option>
            </select>
            <div class="invalid-feedback">Seleccione una sede</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Proveedor *</label>
            <input type="text" name="proveedor" id="proveedor" class="form-control" required>
            <div class="invalid-feedback">Proveedor requerido</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo de conexión *</label>
            <select name="tipo_conexion" id="tipo_conexion" class="form-select" required>
              <option value="">Seleccione</option>
              <option>ADSL</option>
              <option>Fibra óptica</option>
              <option>4G</option>
              <option>5G</option>
              <option>Satelital</option>
              <option>Radioenlace</option>
            </select>
            <div class="invalid-feedback">Tipo requerido</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Velocidad (Mbps)</label>
            <input type="number" class="form-control" name="velocidad_mbps" id="velocidad_mbps" min="0" required>
            <div class="invalid-feedback">Ingrese la velocidad</div>
          </div>

          <div class="form-check form-switch my-2">
            <input class="form-check-input" type="checkbox" id="simetrico" name="simetrico" value="1">
            <label class="form-check-label" for="simetrico">Simétrico</label>
          </div>
          <div class="form-check form-switch my-2">
            <input class="form-check-input" type="checkbox" id="tiene_wifi" name="tiene_wifi" value="1">
            <label class="form-check-label" for="tiene_wifi">¿Tiene WiFi?</label>
          </div>

          <div class="mb-3 mt-2">
            <label class="form-label">Estado *</label>
            <select name="estado_servicio" id="estado_servicio" class="form-select" required>
              <option value="">Seleccione un estado</option>
              <option value="Activo">Activo</option>
              <option value="Pendiente">Pendiente</option>
              <option value="De Baja">De Baja</option>
            </select>
            <div class="invalid-feedback">Seleccione un estado</div>
          </div>

          <!-- Campos condicionales para estado ACTIVO -->
          <div id="campos_activo" style="display:none;">
            <div class="alert alert-success mb-3">
              <i class="fas fa-check-circle me-2"></i>El servicio está <strong>Activo</strong>. Complete la fecha de instalación:
            </div>

            <div class="mb-3">
              <label class="form-label">Fecha de Instalación *</label>
              <input type="date" class="form-control" name="fecha_instalacion" id="fecha_instalacion_activo" max="<?php echo date('Y-m-d'); ?>">
              <small class="form-text text-muted">
                <i class="fas fa-calendar-check me-1"></i>Fecha en que se instaló el servicio
              </small>
              <div class="invalid-feedback">Ingrese la fecha de instalación</div>
            </div>
          </div>

          <!-- Campos condicionales para estado PENDIENTE -->
          <div id="campos_pendiente" style="display:none;">
            <div class="alert alert-warning mb-3">
              <i class="fas fa-clock me-2"></i>El servicio está en estado <strong>Pendiente</strong>. Seleccione la instancia del proceso:
            </div>

            <div class="mb-3">
              <label class="form-label">Instancia del Pendiente *</label>
              <select name="instancia_pendiente" id="instancia_pendiente" class="form-select">
                <option value="">Seleccione una instancia</option>
                <option value="Solicitud de presupuesto">Solicitud de presupuesto</option>
                <option value="Autorización superior">Autorización superior</option>
                <option value="Servicio tarifado">Servicio tarifado</option>
              </select>
              <small class="form-text text-muted">
                <i class="fas fa-layer-group me-1"></i>Etapa del proceso de contratación
              </small>
              <div class="invalid-feedback">Seleccione una instancia</div>
            </div>

            <!-- Campos específicos para Autorización superior -->
            <div id="campos_autorizacion_superior" style="display:none;">
              <div class="alert alert-info mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>La instancia <strong>Autorización Superior</strong> requiere información adicional:
              </div>

              <div class="mb-3">
                <label class="form-label">Fecha de Solicitud de Autorización *</label>
                <input type="date" class="form-control" name="fecha_solicitud_autorizacion" id="fecha_solicitud_pendiente" max="<?php echo date('Y-m-d'); ?>">
                <small class="form-text text-muted">
                  <i class="fas fa-calendar me-1"></i>Fecha en que se solicitó la autorización
                </small>
                <div class="invalid-feedback">Ingrese la fecha de solicitud</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Archivo de Autorización (PDF) *</label>
                <input type="file" class="form-control" name="archivo_autorizacion" id="archivo_autorizacion" accept=".pdf">
                <small class="form-text text-muted">
                  <i class="fas fa-file-pdf me-1"></i>Solo archivos PDF. Tamaño máximo: 5MB
                </small>
                <div class="invalid-feedback">Adjunte el archivo PDF de autorización</div>
                
                <!-- Mostrar archivo actual si existe -->
                <div id="archivo_actual_info" style="display:none;" class="mt-2">
                  <div class="alert alert-success py-2 px-3 mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="archivo_actual_nombre"></span>
                    <a href="#" id="archivo_actual_link" target="_blank" class="ms-2 btn btn-sm btn-outline-primary">
                      <i class="fas fa-download me-1"></i>Ver archivo
                    </a>
                    <small class="d-block mt-1 text-muted">Puede subir un nuevo archivo para reemplazarlo</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Campos condicionales para estado DE BAJA -->
          <div id="campos_baja" style="display:none;">
            <div class="alert alert-danger mb-3">
              <i class="fas fa-times-circle me-2"></i>El servicio está <strong>De Baja</strong>. Complete la fecha:
            </div>

            <div class="mb-3">
              <label class="form-label">Fecha de Baja *</label>
              <input type="date" class="form-control" name="fecha_baja" id="fecha_baja_input" max="<?php echo date('Y-m-d'); ?>">
              <small class="form-text text-muted">
                <i class="fas fa-calendar-times me-1"></i>Fecha en que el servicio fue dado de baja
              </small>
              <div class="invalid-feedback">Ingrese la fecha de baja</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="2"></textarea>
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

<form id="formEliminar" method="POST" style="display:none">
  <?php echo csrf_input(); ?>
  <input type="hidden" name="accion" value="eliminar">
  <input type="hidden" name="id_internet" id="del_id">
</form>

<!-- Modal Ver Detalles -->
<div class="modal fade" id="modalVerDetalles" tabindex="-1" aria-labelledby="modalVerDetallesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerDetallesLabel">
          <i class="fas fa-info-circle me-2"></i>Detalles del Servicio de Internet
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Columna izquierda -->
          <div class="col-md-6">
            <h6 class="text-primary border-bottom pb-2 mb-3">
              <i class="fas fa-map-marker-alt me-2"></i>Ubicación
            </h6>
            
            <div class="mb-3">
              <label class="text-muted small">Localidad:</label>
              <p class="mb-1" id="detalle_localidad"></p>
            </div>
            
            <div class="mb-3">
              <label class="text-muted small">Sede:</label>
              <p class="mb-1" id="detalle_sede"></p>
            </div>
            
            <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">
              <i class="fas fa-network-wired me-2"></i>Información Técnica
            </h6>
            
            <div class="mb-3">
              <label class="text-muted small">Proveedor:</label>
              <p class="mb-1" id="detalle_proveedor"></p>
            </div>
            
            <div class="mb-3">
              <label class="text-muted small">Tipo de Conexión:</label>
              <p class="mb-1" id="detalle_tipo_conexion"></p>
            </div>
            
            <div class="mb-3">
              <label class="text-muted small">Velocidad:</label>
              <p class="mb-1" id="detalle_velocidad"></p>
            </div>
            
            <div class="mb-3">
              <label class="text-muted small">Simétrico:</label>
              <p class="mb-1" id="detalle_simetrico"></p>
            </div>
            
            <div class="mb-3">
              <label class="text-muted small">WiFi:</label>
              <p class="mb-1" id="detalle_wifi"></p>
            </div>
          </div>
          
          <!-- Columna derecha -->
          <div class="col-md-6">
            <h6 class="text-primary border-bottom pb-2 mb-3">
              <i class="fas fa-info-circle me-2"></i>Estado del Servicio
            </h6>
            
            <div class="mb-3">
              <label class="text-muted small">Estado:</label>
              <p class="mb-1" id="detalle_estado"></p>
            </div>
            
            <!-- Fecha de instalación (Activo) -->
            <div class="mb-3" id="detalle_fecha_instalacion_container" style="display:none;">
              <label class="text-muted small">Fecha de Instalación:</label>
              <p class="mb-1" id="detalle_fecha_instalacion"></p>
            </div>
            
            <!-- Instancia (Pendiente) -->
            <div class="mb-3" id="detalle_instancia_container" style="display:none;">
              <label class="text-muted small">Instancia:</label>
              <p class="mb-1" id="detalle_instancia"></p>
            </div>
            
            <!-- Fecha de solicitud (Pendiente - Autorización) -->
            <div class="mb-3" id="detalle_fecha_solicitud_container" style="display:none;">
              <label class="text-muted small">Fecha de Solicitud:</label>
              <p class="mb-1" id="detalle_fecha_solicitud"></p>
            </div>
            
            <!-- Archivo de autorización (Pendiente - Autorización) -->
            <div class="mb-3" id="detalle_archivo_container" style="display:none;">
              <label class="text-muted small">Archivo de Autorización:</label>
              <p class="mb-1">
                <a href="#" id="detalle_archivo_link" target="_blank" class="btn btn-sm btn-outline-danger">
                  <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                </a>
              </p>
            </div>
            
            <!-- Fecha de baja (De Baja) -->
            <div class="mb-3" id="detalle_fecha_baja_container" style="display:none;">
              <label class="text-muted small">Fecha de Baja:</label>
              <p class="mb-1" id="detalle_fecha_baja"></p>
            </div>
            
            <!-- Archivo de autorización (si existe, siempre visible) -->
            <div class="mb-3" id="detalle_archivo_global_container" style="display:none;">
              <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-file-pdf me-2"></i>Documentación
              </h6>
              <label class="text-muted small">Archivo de Autorización:</label>
              <p class="mb-1">
                <a href="#" id="detalle_archivo_global_link" target="_blank" class="btn btn-sm btn-outline-danger">
                  <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                </a>
              </p>
            </div>
            
            <!-- Observaciones -->
            <div class="mb-3" id="detalle_observaciones_container" style="display:none;">
              <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-comment-dots me-2"></i>Observaciones
              </h6>
              <p class="mb-1" id="detalle_observaciones"></p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-2"></i>Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo app_base_url(); ?>';
function cargarLocalidades(){
  return $.getJSON(`${BASE}/ajax/localidades_list.php`).done(r=>{
    const $l=$('#id_localidad');
    $l.html('<option value="">Seleccione una localidad</option>');
    if(r.success){ r.data.forEach(x=> $l.append(`<option value="${x.id}">${x.nombre}</option>`)); }
  });
}
function cargarSedes(localidadId, afterLoad){
  const $s=$('#id_sede');
  $s.prop('disabled', true).html('<option value="">Cargando...</option>');
  if(!localidadId){ $s.html('<option value="">Seleccione una sede</option>').prop('disabled', false); return; }
  $.getJSON(`${BASE}/ajax/cargar_sedes.php`, { localidad_id: localidadId }).done(r=>{
    const data = r && r.data ? r.data : r; const lista = data && data.sedes ? data.sedes : [];
    $s.html('<option value="">Seleccione una sede</option>');
    lista.forEach(x=> $s.append(`<option value="${parseInt(x.id,10)}">${x.nombre}</option>`));
    if(typeof afterLoad === 'function'){ afterLoad($s); }
    $s.prop('disabled', false);
  }).fail(()=>{ $s.html('<option value="">Error al cargar</option>').prop('disabled', false); });
}
function editarInternet(row){
  $('#modalInternetTitle').text('Editar Servicio');
  $('#accion').val('editar');
  $('#id_internet').val(row.id_internet);
  if (row.id_localidad) {
    $('#id_localidad').val(row.id_localidad);
    cargarSedes(row.id_localidad, function($s){ $s.val(String(row.id_sede)); });
  }
  $('#proveedor').val(row.proveedor);
  $('#tipo_conexion').val(row.tipo_conexion);
  $('#velocidad_mbps').val(row.velocidad_mbps || '');
  $('#simetrico').prop('checked', (String(row.simetrico) === '1'));
  $('#tiene_wifi').prop('checked', (String(row.tiene_wifi) === '1'));
  $('#estado_servicio').val(row.estado_servicio);
  $('#observaciones').val(row.observaciones || '');
  
  // Cargar datos según el estado
  if (row.estado_servicio === 'Activo' && row.fecha_instalacion) {
    $('#fecha_instalacion_activo').val(row.fecha_instalacion);
  }
  
  if (row.estado_servicio === 'Pendiente') {
    if (row.instancia_pendiente) {
      $('#instancia_pendiente').val(row.instancia_pendiente);
    }
    if (row.fecha_solicitud_autorizacion) {
      $('#fecha_solicitud_pendiente').val(row.fecha_solicitud_autorizacion);
    }
    if (row.archivo_autorizacion) {
      $('#archivo_autorizacion_actual').val(row.archivo_autorizacion);
      const nombreArchivo = row.archivo_autorizacion.split('/').pop();
      $('#archivo_actual_nombre').text(nombreArchivo);
      $('#archivo_actual_link').attr('href', BASE + '/' + row.archivo_autorizacion);
      $('#archivo_actual_info').show();
    }
  }
  
  if (row.estado_servicio === 'De Baja' && row.fecha_baja) {
    $('#fecha_baja_input').val(row.fecha_baja);
  }
  
  // Trigger para mostrar campos condicionales
  toggleCamposPorEstado();
  toggleCamposAutorizacion();
  
  var m = new bootstrap.Modal(document.getElementById('modalInternet'));
  m.show();
}
function verDetallesInternet(row) {
  console.log('Ver detalles:', row);
  
  // Información básica
  $('#detalle_localidad').text(row.nombre_localidad || '-');
  $('#detalle_sede').text(row.nombre_sede || '-');
  $('#detalle_proveedor').text(row.proveedor || '-');
  $('#detalle_tipo_conexion').html('<span class="badge bg-info">' + (row.tipo_conexion || '-') + '</span>');
  $('#detalle_velocidad').html('<span class="badge bg-primary">' + (row.velocidad_mbps ? row.velocidad_mbps + ' Mbps' : 'No especificada') + '</span>');
  
  // Simétrico
  const simetrico = parseInt(row.simetrico) === 1;
  $('#detalle_simetrico').html('<span class="badge ' + (simetrico ? 'bg-success' : 'bg-secondary') + '">' + (simetrico ? 'Sí' : 'No') + '</span>');
  
  // WiFi
  const wifi = parseInt(row.tiene_wifi) === 1;
  $('#detalle_wifi').html('<span class="badge ' + (wifi ? 'bg-success' : 'bg-secondary') + '">' + (wifi ? 'Sí' : 'No') + '</span>');
  
  // Estado
  const estado = row.estado_servicio;
  let estadoClass = 'estado-activa';
  if (estado === 'Pendiente') estadoClass = 'estado-asignado';
  else if (estado === 'De Baja') estadoClass = 'estado-baja';
  $('#detalle_estado').html('<span class="badge ' + estadoClass + '">' + estado + '</span>');
  
  // Ocultar todos los campos condicionales primero
  $('#detalle_fecha_instalacion_container').hide();
  $('#detalle_instancia_container').hide();
  $('#detalle_fecha_solicitud_container').hide();
  $('#detalle_fecha_baja_container').hide();
  $('#detalle_archivo_global_container').hide();
  $('#detalle_observaciones_container').hide();
  
  // Mostrar TODAS las fechas que tengan valor, sin importar el estado actual
  if (row.fecha_instalacion) {
    $('#detalle_fecha_instalacion').html('<i class="fas fa-calendar-check text-success me-2"></i>' + formatearFecha(row.fecha_instalacion));
    $('#detalle_fecha_instalacion_container').show();
  }
  
  if (row.instancia_pendiente) {
    $('#detalle_instancia').html('<i class="fas fa-clock text-warning me-2"></i>' + row.instancia_pendiente);
    $('#detalle_instancia_container').show();
  }
  
  if (row.fecha_solicitud_autorizacion) {
    $('#detalle_fecha_solicitud').html('<i class="fas fa-calendar text-primary me-2"></i>' + formatearFecha(row.fecha_solicitud_autorizacion));
    $('#detalle_fecha_solicitud_container').show();
  }
  
  if (row.fecha_baja) {
    $('#detalle_fecha_baja').html('<i class="fas fa-calendar-times text-danger me-2"></i>' + formatearFecha(row.fecha_baja));
    $('#detalle_fecha_baja_container').show();
  }
  
  // Mostrar archivo de autorización SIEMPRE que exista (independiente del estado)
  if (row.archivo_autorizacion) {
    $('#detalle_archivo_global_link').attr('href', BASE + '/' + row.archivo_autorizacion);
    $('#detalle_archivo_global_container').show();
  }
  
  // Observaciones
  if (row.observaciones) {
    $('#detalle_observaciones').text(row.observaciones);
    $('#detalle_observaciones_container').show();
  }
  
  // Mostrar el modal
  new bootstrap.Modal(document.getElementById('modalVerDetalles')).show();
}

function formatearFecha(fecha) {
  if (!fecha) return '-';
  const partes = fecha.split('-');
  if (partes.length === 3) {
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }
  return fecha;
}

function eliminarInternet(id){
  if(confirm('¿Eliminar servicio de Internet?')){
    $('#del_id').val(id);
    $('#formEliminar').submit();
  }
}
// Funciones para mostrar/ocultar campos condicionales según el estado
function toggleCamposPorEstado() {
  const estado = $('#estado_servicio').val();
  
  // Ocultar todos los grupos de campos primero
  $('#campos_activo').hide();
  $('#campos_pendiente').hide();
  $('#campos_baja').hide();
  $('#campos_autorizacion_superior').hide();
  
  // Limpiar requerimientos
  $('#fecha_instalacion_activo').prop('required', false);
  $('#instancia_pendiente').prop('required', false);
  $('#fecha_solicitud_pendiente').prop('required', false);
  $('#archivo_autorizacion').prop('required', false);
  $('#fecha_baja_input').prop('required', false);
  
  // Mostrar campos según el estado seleccionado
  if (estado === 'Activo') {
    $('#campos_activo').slideDown(200);
    $('#fecha_instalacion_activo').prop('required', true);
    
  } else if (estado === 'Pendiente') {
    $('#campos_pendiente').slideDown(200);
    $('#instancia_pendiente').prop('required', true);
    // Verificar si necesita mostrar campos de autorización
    toggleCamposAutorizacion();
    
  } else if (estado === 'De Baja') {
    $('#campos_baja').slideDown(200);
    $('#fecha_baja_input').prop('required', true);
  }
}

function toggleCamposAutorizacion() {
  const instancia = $('#instancia_pendiente').val();
  const $camposAutorizacion = $('#campos_autorizacion_superior');
  const $fechaSolicitud = $('#fecha_solicitud_pendiente');
  const $archivoAutorizacion = $('#archivo_autorizacion');
  
  if (instancia === 'Autorización superior') {
    $camposAutorizacion.slideDown(200);
    $fechaSolicitud.prop('required', true);
    // Archivo requerido solo si no hay archivo actual
    if (!$('#archivo_autorizacion_actual').val()) {
      $archivoAutorizacion.prop('required', true);
    }
  } else {
    $camposAutorizacion.slideUp(200);
    $fechaSolicitud.prop('required', false).val('');
    $archivoAutorizacion.prop('required', false).val('');
    $('#archivo_autorizacion_actual').val('');
    $('#archivo_actual_info').hide();
  }
}

$('#modalInternet').on('hidden.bs.modal', function(){
  $('#modalInternetTitle').text('Agregar Servicio');
  $('#accion').val('agregar');
  $('#formInternet')[0].reset();
  $('#id_localidad').val('');
  $('#id_sede').html('<option value="">Seleccione una sede</option>');
  $('#estado_servicio').val(''); // Asegurar que no haya estado preseleccionado
  $('#formInternet').removeClass('was-validated');
  
  // Ocultar todos los campos condicionales
  $('#campos_activo').hide();
  $('#campos_pendiente').hide();
  $('#campos_baja').hide();
  $('#campos_autorizacion_superior').hide();
  
  // Limpiar todos los campos condicionales
  $('#fecha_instalacion_activo').val('');
  $('#instancia_pendiente').val('');
  $('#fecha_solicitud_pendiente').val('');
  $('#fecha_baja_input').val('');
  $('#archivo_autorizacion').val('');
  $('#archivo_autorizacion_actual').val('');
  $('#archivo_actual_info').hide();
});
$('#formInternet').on('submit', function(e){
  if(!this.checkValidity()){ e.preventDefault(); e.stopPropagation(); }
  $(this).addClass('was-validated');
});
$(function(){
  const url = new URL(window.location.href);
  const qLoc = url.searchParams.get('id_localidad');
  const qSede = url.searchParams.get('id_sede');
  const qOpen = url.searchParams.get('open');
  cargarLocalidades().done(function(){
    if (qLoc) {
      $('#id_localidad').val(qLoc);
      cargarSedes(qLoc, function($s){
        if(qSede){ $s.val(String(qSede)); }
        if(qOpen==='add'){ new bootstrap.Modal(document.getElementById('modalInternet')).show(); }
      });
    } else if (qOpen==='add') {
      new bootstrap.Modal(document.getElementById('modalInternet')).show();
    }
  });
  $('#id_localidad').on('change', function(){ cargarSedes($(this).val(), null); });
  
  // Event listeners para campos condicionales
  $('#estado_servicio').on('change', toggleCamposPorEstado);
  $('#instancia_pendiente').on('change', toggleCamposAutorizacion);
  
  // Validación adicional del archivo
  $('#archivo_autorizacion').on('change', function() {
    const file = this.files[0];
    if (file) {
      // Validar extensión
      const extension = file.name.split('.').pop().toLowerCase();
      if (extension !== 'pdf') {
        alert('Solo se permiten archivos PDF');
        $(this).val('');
        return;
      }
      // Validar tamaño (5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('El archivo no debe superar 5MB');
        $(this).val('');
        return;
      }
    }
  });
  
  // sin select2 en este modal para igualar estilo
});
</script>

<?php include '../../includes/footer.php'; ?>

