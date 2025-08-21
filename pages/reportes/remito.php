<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Obtener número de remito desde diferentes parámetros por compatibilidad
$numero_remito = (
    (isset($_GET['remito']) && $_GET['remito'] !== '') ? $_GET['remito'] :
    ((isset($_GET['numero']) && $_GET['numero'] !== '') ? $_GET['numero'] :
    ((isset($_GET['numero_remito']) && $_GET['numero_remito'] !== '') ? $_GET['numero_remito'] : ''))
);

// Cabecera e items del remito solicitado (si corresponde)
$cab = null;
$items_remito = [];

if ($numero_remito !== '') {
    // Intentar nuevo esquema (remitos + remitos_detalle)
    $stmt = $conexion->prepare("SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada,
                                       ar.nombre_area, s.nombre_sede, l.nombre_localidad, z.nombre_zona, r.observaciones
                                FROM remitos r
                                JOIN sedes s ON r.id_sede = s.id_sede
                                JOIN localidades l ON s.id_localidad = l.id_localidad
                                JOIN zonas z ON l.id_zona = z.id_zona
                                JOIN areas ar ON r.id_area = ar.id_area
                                WHERE r.numero_remito = ?
                                LIMIT 1");
    $stmt->execute([$numero_remito]);
    $cab = $stmt->fetch();

    if ($cab) {
        $stmtDet = $conexion->prepare("SELECT i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, d.cantidad
                                       FROM remitos_detalle d
                                       JOIN insumos i ON d.id_insumo = i.id_insumo
                                       JOIN remitos r ON r.id_remito = d.id_remito
                                       WHERE r.numero_remito = ?
                                       ORDER BY i.nombre_insumo");
        $stmtDet->execute([$numero_remito]);
        $items_remito = $stmtDet->fetchAll();
    }
}

// Cargar remitos recientes (cabecera)
$stmt = $conexion->query("SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada,
                                 ar.nombre_area, s.nombre_sede 
                          FROM remitos r
                          JOIN areas ar ON r.id_area = ar.id_area 
                          JOIN sedes s ON r.id_sede = s.id_sede 
                          ORDER BY r.fecha_asignacion DESC 
                          LIMIT 10");
$asignaciones_recientes = $stmt->fetchAll();
?>
<?php include '../../includes/header.php'; ?>

<?php if ($numero_remito !== '' && $cab): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-file-alt me-2"></i>Remito <?php echo htmlspecialchars($cab['numero_remito']); ?>
        </h5>
        <div class="btn-group">
            <a class="btn btn-outline-secondary btn-sm" href="?">
                <i class="fas fa-list me-1"></i>Ver recientes
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="generarRemitoPDF('<?php echo htmlspecialchars($cab['numero_remito']); ?>')">
                <i class="fas fa-print me-1"></i>Imprimir PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cab['fecha_asignacion'])); ?></p>
                <p class="mb-1"><strong>Persona:</strong> <?php echo htmlspecialchars($cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada']); ?></p>
                <p class="mb-1"><strong>Área:</strong> <?php echo htmlspecialchars($cab['nombre_area'] ?: '-'); ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($cab['nombre_sede'] ?: '-'); ?></p>
                <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars(($cab['nombre_localidad'] ?: '-') . ' - Zona: ' . ($cab['nombre_zona'] ?: '-')); ?></p>
            </div>
        </div>

        <?php if (!empty($cab['observaciones'])): ?>
        <div class="alert alert-light mt-3" role="alert">
            <i class="fas fa-comment me-2"></i><strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($cab['observaciones'])); ?>
        </div>
        <?php endif; ?>

        <div class="table-responsive mt-3">
            <table class="table table-striped datatable">
                <thead class="table-light">
                    <tr>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>S/N</th>
                        <th>ID Físico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items_remito as $it): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($it['nombre_insumo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($it['tipo_insumo']); ?></td>
                        <td><span class="badge bg-success"><?php echo isset($it['cantidad']) ? (int)$it['cantidad'] : 1; ?></span></td>
                        <td><small><?php echo htmlspecialchars($it['numero_serie'] ?: '-'); ?></small></td>
                        <td><small><?php echo htmlspecialchars($it['id_fisico'] ?: '-'); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
<?php elseif ($numero_remito !== '' && !$cab): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>El remito solicitado no existe o no se pudo cargar.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>Asignaciones Recientes
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($asignaciones_recientes)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-pdf fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay remitos recientes</h5>
                <p class="text-muted">No se han generado remitos aún</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaRemitos">
                    <thead>
                        <tr>
                            <th>Remito</th>
                            <th>Fecha</th>
                            <th>Persona</th>
                            <th>Área</th>
                            <th>Sede</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones_recientes as $asignacion): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($asignacion['numero_remito']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?></td>
                                <td><?php echo htmlspecialchars($asignacion['nombre_persona_asignada'] . ' ' . $asignacion['apellido_persona_asignada']); ?></td>
                                <td><?php echo htmlspecialchars($asignacion['nombre_area']); ?></td>
                                <td><?php echo htmlspecialchars($asignacion['nombre_sede']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="generarRemitoPDF('<?php echo $asignacion['numero_remito']; ?>')" data-bs-toggle="tooltip" title="Imprimir remito">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <a href="?remito=<?php echo $asignacion['numero_remito']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<?php include '../../includes/footer.php'; ?>