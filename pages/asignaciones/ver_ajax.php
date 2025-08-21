<?php
require_once '../../includes/config.php';

header('Content-Type: text/html; charset=UTF-8');

$conexion = conectarDB();
$numero_remito = isset($_GET['remito']) ? trim($_GET['remito']) : '';

if ($numero_remito === '') {
    echo '<div class="alert alert-warning">Número de remito no provisto.</div>';
    exit;
}

// Cabecera
$stmt = $conexion->prepare("SELECT r.numero_remito, r.fecha_asignacion, r.nombre_persona_asignada, r.apellido_persona_asignada,
                                   ar.nombre_area, s.nombre_sede, l.nombre_localidad
                            FROM remitos r
                            JOIN areas ar ON r.id_area = ar.id_area
                            JOIN sedes s ON r.id_sede = s.id_sede
                            JOIN localidades l ON s.id_localidad = l.id_localidad
                            WHERE r.numero_remito = ?
                            LIMIT 1");
$stmt->execute([$numero_remito]);
$cab = $stmt->fetch();

if (!$cab) {
    echo '<div class="alert alert-warning">No se encontró el remito solicitado.</div>';
    exit;
}

// Detalle con devoluciones
$stmtDet = $conexion->prepare("SELECT i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico,
                                      d.cantidad AS cantidad_asignada,
                                      COALESCE(d.cantidad_devuelta, 0) AS cantidad_devuelta
                               FROM remitos_detalle d
                               JOIN insumos i ON d.id_insumo = i.id_insumo
                               JOIN remitos r ON r.id_remito = d.id_remito
                               WHERE r.numero_remito = ?
                               ORDER BY i.nombre_insumo");
$stmtDet->execute([$numero_remito]);
$items = $stmtDet->fetchAll();

$total_asignada = 0; $total_devuelta = 0; $total_pendiente = 0;
foreach ($items as $row) {
    $total_asignada += (int)$row['cantidad_asignada'];
    $total_devuelta += (int)$row['cantidad_devuelta'];
    $total_pendiente += max(0, (int)$row['cantidad_asignada'] - (int)$row['cantidad_devuelta']);
}
$estado_global = ($total_pendiente > 0) ? 'Activa' : 'Devuelta';
?>
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="mb-1"><strong>Remito:</strong> <?php echo htmlspecialchars($cab['numero_remito']); ?></div>
            <div class="mb-1"><strong>Persona:</strong> <?php echo htmlspecialchars($cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada']); ?></div>
            <div class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cab['fecha_asignacion'])); ?></div>
        </div>
        <div class="text-end">
            <div class="mb-1"><strong>Área:</strong> <?php echo htmlspecialchars($cab['nombre_area']); ?></div>
            <div class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($cab['nombre_sede']); ?></div>
            <div class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($cab['nombre_localidad']); ?></div>
        </div>
    </div>
    <div class="mt-2">
        <span class="badge estado-<?php echo strtolower($estado_global); ?>">Estado: <?php echo $estado_global; ?></span>
        <span class="badge bg-secondary ms-2">Asignados: <?php echo (int)$total_asignada; ?></span>
        <span class="badge bg-success ms-2">Devueltos: <?php echo (int)$total_devuelta; ?></span>
        <span class="badge bg-warning text-dark ms-2">Pendientes: <?php echo (int)$total_pendiente; ?></span>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped datatable-modal">
        <thead>
            <tr>
                <th>Insumo</th>
                <th>Tipo</th>
                <th>Asignado</th>
                <th>Devuelto</th>
                <th>Pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): 
            $pend = max(0, (int)$it['cantidad_asignada'] - (int)$it['cantidad_devuelta']);
            $estado = ($pend > 0) ? 'Activo' : 'Devuelto';
        ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($it['nombre_insumo']); ?></strong><br>
                    <small class="text-muted">S/N: <?php echo htmlspecialchars($it['numero_serie'] ?: '-'); ?> | ID: <?php echo htmlspecialchars($it['id_fisico'] ?: '-'); ?></small>
                </td>
                <td><?php echo htmlspecialchars($it['tipo_insumo']); ?></td>
                <td><span class="badge bg-secondary"><?php echo (int)$it['cantidad_asignada']; ?></span></td>
                <td><span class="badge bg-success"><?php echo (int)$it['cantidad_devuelta']; ?></span></td>
                <td><span class="badge bg-warning text-dark"><?php echo (int)$pend; ?></span></td>
                <td>
                    <span class="badge estado-<?php echo strtolower($estado); ?>"><?php echo $estado; ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// Inicializar DataTables para la tabla dentro del modal (opciones simples)
setTimeout(function(){
  if (window.jQuery && $.fn.DataTable) {
    $('.datatable-modal').DataTable({
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      paging: false,
      searching: false,
      info: false,
      ordering: true
    });
  }
  inicializarTooltips && inicializarTooltips();
}, 0);
</script>