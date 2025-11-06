<?php
require_once '../../includes/config.php';
\nrequerirAutenticacion();

$db = conectarDB();

// Migraciones tolerantes (no destructivas) para asegurar columnas clave
try { $db->query("SELECT simetrico FROM sedes_internet LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE sedes_internet ADD COLUMN simetrico TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e2) {} }
try { $db->query("SELECT velocidad_mbps FROM sedes_internet LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE sedes_internet ADD COLUMN velocidad_mbps INT NULL"); } catch (Exception $e2) {} }
// NOTA: Las migraciones de instancia_pendiente y fecha_instalacion se ejecutan manualmente desde los archivos SQL
// Ver: sql/migracion_internet_instancias_pendientes.sql
// Ver: sql/agregar_fecha_instalacion_internet.sql

// ========================================
// PASO 1: MARCAR SERVICIO COMO "BAJA POR TRASLADO"
// ========================================
if (isset($_POST['marcar_baja_traslado']) && $_POST['marcar_baja_traslado'] === '1') {
    header('Content-Type: application/json');
    
    $id_servicio = (int)$_POST['id_servicio'];
    
    if (empty($id_servicio)) {
        echo json_encode(['success' => false, 'message' => 'ID de servicio no proporcionado']);
        exit;
    }
    
    try {
        // Verificar que el servicio existe y está activo o pendiente
        $stmtCheck = $db->prepare("
            SELECT estado_servicio 
            FROM sedes_internet 
            WHERE id_internet = ?
        ");
        $stmtCheck->execute([$id_servicio]);
        $servicio = $stmtCheck->fetch();
        
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }
        
        if ($servicio['estado_servicio'] === 'Baja por Traslado') {
            echo json_encode(['success' => false, 'message' => 'Este servicio ya fue trasladado']);
            exit;
        }
        
        if ($servicio['estado_servicio'] === 'De Baja') {
            echo json_encode(['success' => false, 'message' => 'Este servicio ya está dado de baja']);
            exit;
        }
        
        // Marcar como "Baja por Traslado" (sin fecha ni PDF aún)
        $stmtUpdate = $db->prepare("
            UPDATE sedes_internet 
            SET estado_servicio = 'Baja por Traslado'
            WHERE id_internet = ?
        ");
        $stmtUpdate->execute([$id_servicio]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Servicio marcado como baja por traslado'
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ========================================
// PASO 2: CREAR NUEVO SERVICIO POR TRASLADO
// ========================================
if (isset($_POST['crear_servicio_traslado']) && $_POST['crear_servicio_traslado'] === '1') {
    
    $id_servicio_anterior = (int)$_POST['id_servicio_anterior'];
    $id_sede = (int)$_POST['id_sede_traslado'];
    
    // Validaciones
    if (empty($id_servicio_anterior) || empty($id_sede)) {
        $_SESSION['mensaje'] = 'Datos incompletos para crear el servicio';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (empty($_POST['fecha_traslado'])) {
        $_SESSION['mensaje'] = 'La fecha del traslado es obligatoria';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Procesar PDF de autorización (OBLIGATORIO)
    $archivoPdfTraslado = null;
    if (isset($_FILES['pdf_traslado']) && $_FILES['pdf_traslado']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['pdf_traslado'];
        
        // Validar tipo
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            $_SESSION['mensaje'] = 'El archivo debe ser PDF';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Validar tamaño (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['mensaje'] = 'El archivo no puede superar 5 MB';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Crear directorio si no existe
        $directorioTraslados = __DIR__ . '/../../public/uploads/autorizaciones_internet';
        if (!is_dir($directorioTraslados)) {
            mkdir($directorioTraslados, 0755, true);
        }
        
        // Generar nombre único
        $nombreArchivo = 'traslado_' . date('Ymd_His') . '_' . uniqid() . '.pdf';
        $rutaCompleta = $directorioTraslados . '/' . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            $archivoPdfTraslado = 'public/uploads/autorizaciones_internet/' . $nombreArchivo;
        } else {
            $lastError = error_get_last();
            $_SESSION['mensaje'] = 'Error al guardar el archivo: ' . ($lastError['message'] ?? 'desconocido');
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $_SESSION['mensaje'] = 'El PDF de autorización del traslado es obligatorio';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // 1. CREAR NUEVO SERVICIO
        // El nuevo servicio SIEMPRE es "Pendiente - Autorización superior"
        // con la misma fecha de solicitud y el mismo PDF
        $stmtNuevo = $db->prepare("
            INSERT INTO sedes_internet (
                id_sede,
                proveedor,
                tipo_conexion,
                velocidad_mbps,
                simetrico,
                tiene_wifi,
                estado_servicio,
                instancia_pendiente,
                fecha_solicitud_autorizacion,
                archivo_autorizacion,
                observaciones,
                id_servicio_trasladado_desde
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // NO agregar observaciones automáticas si es creado por traslado
        // Solo usar las observaciones que ingrese el usuario
        $observacionesNuevas = trim($_POST['observaciones_nuevas'] ?? '');
        
        $stmtNuevo->execute([
            $id_sede, // MISMA SEDE
            $_POST['proveedor_nuevo'],
            $_POST['tipo_conexion_nuevo'],
            !empty($_POST['velocidad_nueva']) ? (int)$_POST['velocidad_nueva'] : null,
            isset($_POST['simetrico_nuevo']) ? 1 : 0,
            isset($_POST['wifi_nuevo']) ? 1 : 0,
            'Pendiente', // SIEMPRE Pendiente
            'Autorización superior', // SIEMPRE Autorización superior
            $_POST['fecha_traslado'], // Misma fecha que el traslado
            $archivoPdfTraslado, // Mismo PDF que el traslado
            trim($observacionesNuevas),
            $id_servicio_anterior // Vínculo con servicio anterior
        ]);
        
        $idNuevoServicio = $db->lastInsertId();
        
        // 2. ACTUALIZAR SERVICIO ANTERIOR (completar la baja por traslado)
        // NOTA: NO se guarda el PDF en el servicio anterior, conserva su archivo_autorizacion original
        $stmtBaja = $db->prepare("
            UPDATE sedes_internet SET
                fecha_traslado = ?,
                id_servicio_trasladado_a = ?
            WHERE id_internet = ?
        ");
        
        $stmtBaja->execute([
            $_POST['fecha_traslado'],
            $idNuevoServicio,
            $id_servicio_anterior
        ]);
        
        $db->commit();
        
        $_SESSION['mensaje'] = "Traslado completado. Servicio #{$id_servicio_anterior} dado de baja. Nuevo servicio #{$idNuevoServicio} creado.";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje'] = 'Error al crear servicio por traslado: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

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

// Listado (incluir datos de traslados)
$sql = "SELECT si.*, 
        s.nombre_sede, 
        l.nombre_localidad, 
        l.id_localidad,
        -- Servicio trasladado A (nuevo)
        si_nuevo.id_internet AS id_trasladado_a,
        s_nuevo.nombre_sede AS sede_trasladado_a,
        -- Servicio trasladado DESDE (anterior)
        si_anterior.id_internet AS id_trasladado_desde,
        s_anterior.nombre_sede AS sede_trasladado_desde
        FROM sedes_internet si
        JOIN sedes s ON s.id_sede = si.id_sede
        JOIN localidades l ON l.id_localidad = s.id_localidad
        LEFT JOIN sedes_internet si_nuevo ON si.id_servicio_trasladado_a = si_nuevo.id_internet
        LEFT JOIN sedes s_nuevo ON si_nuevo.id_sede = s_nuevo.id_sede
        LEFT JOIN sedes_internet si_anterior ON si.id_servicio_trasladado_desde = si_anterior.id_internet
        LEFT JOIN sedes s_anterior ON si_anterior.id_sede = s_anterior.id_sede
        ORDER BY l.nombre_localidad, s.nombre_sede, si.id_internet DESC";
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
                                    <?php 
                                    $est = $row['estado_servicio']; 
                                    if ($est === 'Activo') $cls = 'estado-activa';
                                    elseif ($est === 'Pendiente') $cls = 'estado-asignado';
                                    elseif ($est === 'Baja por Traslado') $cls = 'bg-info text-dark';
                                    else $cls = 'estado-baja';
                                    ?>
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
                                    
                                    <?php if ($est === 'De Baja' && !empty($row['fecha_baja'])): ?>
                                        <br><small class="text-muted mt-1 d-block">
                                            <i class="fas fa-calendar-times me-1"></i>Baja: <?php echo date('d/m/Y', strtotime($row['fecha_baja'])); ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php if ($est === 'Baja por Traslado'): ?>
                                        <?php if (!empty($row['fecha_traslado'])): ?>
                                            <br><small class="text-muted mt-1 d-block">
                                                <i class="fas fa-calendar-times me-1"></i>Traslado: <?php echo date('d/m/Y', strtotime($row['fecha_traslado'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php // Mostrar archivo de autorización siempre que exista ?>
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
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles del servicio" onclick='verDetallesInternet(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'><i class="fas fa-eye" aria-hidden="true"></i></button>
                                        <a href="<?php echo app_base_url(); ?>/pages/reportes/internet_historial_pdf.php?id=<?php echo (int)$row['id_internet']; ?>" target="_blank" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Generar PDF" aria-label="Generar PDF del historial"><i class="fas fa-file-pdf" aria-hidden="true"></i></a>
                                        
                                        <?php if ($row['estado_servicio'] === 'Baja por Traslado'): ?>
                                            <!-- Botón Editar deshabilitado para servicios trasladados -->
                                            <button type="button" class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="No se puede editar un servicio trasladado" aria-label="Editar deshabilitado">
                                                <i class="fas fa-edit" aria-hidden="true"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar" aria-label="Editar servicio" onclick='editarInternet(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit" aria-hidden="true"></i></button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-sm btn-danger btn-eliminar-internet" data-bs-toggle="tooltip" title="Eliminar" aria-label="Eliminar servicio" data-id="<?php echo (int)$row['id_internet']; ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
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
          <!-- Botón Traslado a la izquierda (solo visible en modo edición) -->
          <button type="button" class="btn btn-warning me-auto" id="btnIniciarTraslado" style="display:none;">
            <i class="fas fa-exchange-alt"></i> Traslado
          </button>
          
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

<!-- Modal Crear Servicio por Traslado -->
<div class="modal fade" id="modalTrasladoInternet" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formTrasladoInternet" method="POST" enctype="multipart/form-data">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="crear_servicio_traslado" value="1">
        <input type="hidden" name="id_servicio_anterior" id="traslado_id_servicio_anterior">
        <input type="hidden" name="id_sede_traslado" id="traslado_id_sede">
        
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-exchange-alt"></i>
            Crear Nuevo Servicio por Traslado
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <!-- Información del servicio anterior -->
          <div class="alert alert-info mb-4">
            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Información</h6>
            <p class="mb-2">
              Se dará de baja el servicio <strong id="traslado_servicio_anterior_id"></strong> y se creará uno nuevo en la misma sede con los datos actualizados.
            </p>
            <p class="mb-0">
              <strong>El nuevo servicio se creará en estado:</strong> <span class="badge bg-warning text-dark">Pendiente - Autorización superior</span>
            </p>
          </div>
          
          <!-- Sede (solo lectura) -->
          <div class="mb-3">
            <label class="form-label fw-bold">Sede</label>
            <input type="text" class="form-control bg-light" id="traslado_sede_nombre" disabled>
            <small class="text-muted">La sede permanece igual</small>
          </div>
          
          <hr>
          
          <!-- DATOS DEL TRASLADO -->
          <h6 class="fw-bold mb-3 text-danger">
            <i class="fas fa-file-pdf"></i> 
            DATOS DEL TRASLADO (Obligatorios)
          </h6>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Fecha de Solicitud de Autorización <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="fecha_traslado" id="traslado_fecha" required max="<?php echo date('Y-m-d'); ?>">
              <small class="text-muted">Esta fecha se guardará como inicio del nuevo servicio</small>
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label">PDF de Autorización (Nuevo Servicio) <span class="text-danger">*</span></label>
              <input type="file" class="form-control" name="pdf_traslado" id="traslado_pdf" accept=".pdf" required>
              <small class="text-muted">PDF del nuevo servicio - Máximo 5 MB</small>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Motivo del Traslado</label>
            <textarea class="form-control" name="motivo_traslado" id="traslado_motivo" rows="2" 
                      placeholder="Ej: Migración a fibra óptica, Cambio de proveedor, etc."></textarea>
          </div>
          
          <hr>
          
          <!-- DATOS DEL NUEVO SERVICIO -->
          <h6 class="fw-bold mb-3 text-success">
            <i class="fas fa-edit"></i> 
            DATOS DEL NUEVO SERVICIO
            <small class="fw-normal text-muted">(modificar si es necesario)</small>
          </h6>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Proveedor <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="proveedor_nuevo" id="traslado_proveedor" required>
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo de conexión <span class="text-danger">*</span></label>
              <select class="form-select" name="tipo_conexion_nuevo" id="traslado_tipo_conexion" required>
                <option value="">Seleccione</option>
                <option>ADSL</option>
                <option>Fibra óptica</option>
                <option>4G</option>
                <option>5G</option>
                <option>Satelital</option>
                <option>Radioenlace</option>
              </select>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Velocidad (Mbps) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="velocidad_nueva" id="traslado_velocidad" min="0" required>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-check form-switch my-2">
                <input class="form-check-input" type="checkbox" id="traslado_simetrico" name="simetrico_nuevo" value="1">
                <label class="form-check-label" for="traslado_simetrico">Simétrico</label>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-check form-switch my-2">
                <input class="form-check-input" type="checkbox" id="traslado_wifi" name="wifi_nuevo" value="1">
                <label class="form-check-label" for="traslado_wifi">¿Tiene WiFi?</label>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones_nuevas" id="traslado_observaciones" rows="3"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Crear Nuevo Servicio
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

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
  
  // Mostrar botón de traslado (solo en modo edición)
  $('#btnIniciarTraslado').show();
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
  
  // Limpiar historial previo (evitar duplicación)
  $('#detalle_historial_traslados').remove();
  
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
  
  // La instancia solo se muestra si el estado actual es Pendiente
  if (estado === 'Pendiente' && row.instancia_pendiente) {
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
  
  // Obtener y mostrar historial completo de traslados
  fetch('<?php echo app_base_url(); ?>/ajax/obtener_cadena_traslados.php?id_internet=' + row.id_internet)
    .then(response => response.json())
    .then(cadena => {
      // Si hay más de un servicio en la cadena, mostrar historial de traslados
      if (cadena.length > 1) {
        let htmlHistorial = `
          <div class="mt-4" id="detalle_historial_traslados">
            <h6 class="text-primary border-bottom pb-2 mb-3">
              <i class="fas fa-history me-2"></i>Historial de Traslados
            </h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Proveedor</th>
                    <th>Tecnología</th>
                    <th>Velocidad</th>
                    <th>Fecha Traslado</th>
                  </tr>
                </thead>
                <tbody>
        `;
        
        cadena.forEach((servicio, index) => {
          const esActual = (index === cadena.length - 1);
          const rowClass = esActual ? 'table-success' : '';
          
          htmlHistorial += `
            <tr class="${rowClass}">
              <td>
                ${servicio.proveedor || '-'}
                ${esActual ? '<span class="badge bg-success ms-2">Actual</span>' : ''}
              </td>
              <td>${servicio.tipo_conexion || '-'}</td>
              <td>${servicio.velocidad_mbps ? servicio.velocidad_mbps + ' Mbps' : '-'}</td>
              <td>${servicio.fecha_traslado ? formatearFecha(servicio.fecha_traslado) : '-'}</td>
            </tr>
          `;
        });
        
        htmlHistorial += `
                </tbody>
              </table>
            </div>
            <small class="text-muted">
              <i class="fas fa-info-circle me-1"></i>
              Total de traslados: <strong>${cadena.length - 1}</strong>
            </small>
          </div>
        `;
        
        // Agregar al modal
        document.querySelector('#modalVerDetalles .modal-body').insertAdjacentHTML('beforeend', htmlHistorial);
      }
    })
    .catch(error => {
      console.error('Error al obtener historial de traslados:', error);
    });
  
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
  
  // Ocultar botón de traslado (solo visible en modo edición)
  $('#btnIniciarTraslado').hide();
  
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
  
  // ========================================
  // FLUJO DE TRASLADO
  // ========================================
  
  // Al hacer clic en botón "Traslado" del modal de edición
  $('#btnIniciarTraslado').on('click', function() {
    // Obtener datos del servicio actual desde el formulario de edición
    const idServicio = $('#id_internet').val();
    const idSede = $('#id_sede').val();
    const sedeTexto = $('#id_sede option:selected').text();
    const proveedor = $('#proveedor').val();
    const tipoConexion = $('#tipo_conexion').val();
    const velocidad = $('#velocidad_mbps').val();
    const simetrico = $('#simetrico').is(':checked');
    const wifi = $('#tiene_wifi').is(':checked');
    const observaciones = $('#observaciones').val();
    
    // Validar que haya un servicio seleccionado
    if (!idServicio) {
      alert('Error: No se pudo identificar el servicio');
      return;
    }
    
    // Confirmar acción
    if (!confirm('¿Está seguro de realizar un traslado/migración de este servicio?\n\n' +
                 'Se dará de baja el servicio actual y se abrirá un formulario para crear el nuevo servicio.')) {
      return;
    }
    
    // Enviar solicitud para marcar como "Baja por Traslado"
    const formData = new FormData();
    formData.append('_csrf', $('input[name="_csrf"]').first().val());
    formData.append('marcar_baja_traslado', '1');
    formData.append('id_servicio', idServicio);
    
    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Cerrar modal de edición
        bootstrap.Modal.getInstance(document.getElementById('modalInternet')).hide();
        
        // Pre-cargar datos en el modal de traslado
        $('#traslado_id_servicio_anterior').val(idServicio);
        $('#traslado_id_sede').val(idSede);
        $('#traslado_servicio_anterior_id').text('#' + idServicio);
        $('#traslado_sede_nombre').val(sedeTexto);
        $('#traslado_proveedor').val(proveedor);
        $('#traslado_tipo_conexion').val(tipoConexion);
        $('#traslado_velocidad').val(velocidad);
        $('#traslado_simetrico').prop('checked', simetrico);
        $('#traslado_wifi').prop('checked', wifi);
        $('#traslado_observaciones').val(observaciones);
        
        // Abrir modal de traslado
        new bootstrap.Modal(document.getElementById('modalTrasladoInternet')).show();
      } else {
        alert('Error: ' + (data.message || 'No se pudo marcar el servicio como baja por traslado'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al procesar la solicitud');
    });
  });
  
  // Validación de archivo PDF
  $('#traslado_pdf').on('change', function() {
    const file = this.files[0];
    
    if (file) {
      // Validar tipo
      if (file.type !== 'application/pdf') {
        alert('El archivo debe ser un PDF');
        this.value = '';
        return;
      }
      
      // Validar tamaño (5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('El archivo no puede superar 5 MB');
        this.value = '';
        return;
      }
    }
  });
  
  // Limpiar modal al cerrarlo
  $('#modalTrasladoInternet').on('hidden.bs.modal', function() {
    $('#formTrasladoInternet')[0].reset();
  });
  
  // Event delegation para botón eliminar (evitar conflicto con tooltips)
  $(document).on('click', '.btn-eliminar-internet', function() {
    const id = $(this).data('id');
    if (id) {
      eliminarInternet(id);
    }
  });
});
</script>

<?php include '../../includes/footer.php'; ?>

