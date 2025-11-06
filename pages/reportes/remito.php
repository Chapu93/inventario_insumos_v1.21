<?php
require_once '../../includes/config.php';

requerirAutenticacion();

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
        $stmtDet = $conexion->prepare("SELECT i.nombre_insumo,
                                       i.tipo_insumo,
                                       i.numero_serie,
                                       i.id_fisico,
                                       d.cantidad,
                                       nb.marca AS nb_marca,
                                       nb.modelo AS nb_modelo,
                                       imp.marca AS imp_marca,
                                       imp.modelo AS imp_modelo,
                                       mon.marca AS mon_marca,
                                       mon.modelo AS mon_modelo,
                                       esc.marca AS esc_marca,
                                       esc.modelo AS esc_modelo
                                       FROM remitos_detalle d
                                       JOIN insumos i ON d.id_insumo = i.id_insumo
                                       JOIN remitos r ON r.id_remito = d.id_remito
                                       LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
                                       LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
                                       LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
                                       LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
                                       LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
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

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-file-alt me-2"></i>Remitos</h1>
        <a class="btn btn-outline-secondary" href="<?php echo app_base_url(); ?>/pages/asignaciones/listar.php">
            <i class="fas fa-arrow-left me-1"></i>Volver a Asignaciones
        </a>
    </div>
    </div>

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
                    <?php
                        $tipo = trim((string)($it['tipo_insumo'] ?? ''));
                        $originalNombre = $it['nombre_insumo'] ?? '';
                        $displayNombre = $originalNombre;
                        $esPc = ($tipo === 'PC Escritorio' || $tipo === 'PC Completa');
                        if ($esPc && !empty($it['pc_sist_op'])) {
                            $displayNombre = $it['pc_sist_op'];
                        } elseif ($tipo !== 'Varios' && !$esPc) {
                            $marca = '';
                            $modelo = '';
                            if (!empty($it['nb_marca']) || !empty($it['nb_modelo'])) {
                                $marca = $it['nb_marca'] ?? '';
                                $modelo = $it['nb_modelo'] ?? '';
                            } elseif (!empty($it['imp_marca']) || !empty($it['imp_modelo'])) {
                                $marca = $it['imp_marca'] ?? '';
                                $modelo = $it['imp_modelo'] ?? '';
                            } elseif (!empty($it['mon_marca']) || !empty($it['mon_modelo'])) {
                                $marca = $it['mon_marca'] ?? '';
                                $modelo = $it['mon_modelo'] ?? '';
                            } elseif (!empty($it['esc_marca']) || !empty($it['esc_modelo'])) {
                                $marca = $it['esc_marca'] ?? '';
                                $modelo = $it['esc_modelo'] ?? '';
                            }
                            $marca = trim((string)$marca);
                            $modelo = trim((string)$modelo);
                            if ($marca !== '' || $modelo !== '') {
                                $displayNombre = trim($marca . ($marca && $modelo ? ' - ' : '') . $modelo);
                            }
                        }
                        $displayNombre = $displayNombre !== '' ? $displayNombre : '-';
                        $showOriginal = $displayNombre !== ($originalNombre ?? '') && ($originalNombre ?? '') !== '';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($displayNombre); ?></strong>
                            <?php if ($showOriginal): ?>
                                <div class="text-muted small"><?php echo htmlspecialchars($originalNombre); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($tipo); ?></td>
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
    <div class="card-header d-flex justify-content-between align-items-center">
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
                <table class="table table-striped datatable" id="tablaRemitos" data-default-order-col="2" data-default-order-dir="desc" data-ssp="1">
                    <thead>
                        <tr>
                            <th>Número de Remito</th>
                            <th>Persona</th>
                            <th>Fecha</th>
                            <th>Sede/Localidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-success" onclick="exportarExcelSinColumnas('tablaRemitos', 'remitos', [0])">
                    <i class="fas fa-file-excel me-2"></i>Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" onclick="imprimirTablaSinColumnas('tablaRemitos', 'remitos', [0])">
                    <i class="fas fa-print me-2"></i>Imprimir
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
$(function(){
  var $t = $('#tablaRemitos');
  if ($.fn && $.fn.DataTable && $t.length) {
    $t.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: getAppBase() + '/ajax/remitos_list_ssp.php',
        type: 'GET'
      },
      order: [[$t.data('default-order-col') || 2, 'desc']],
      pageLength: 25,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4, orderable: false, searchable: false }
      ],
      drawCallback: function(){ inicializarTooltips(); }
    });
  }
});
</script>