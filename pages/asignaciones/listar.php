<?php
require_once '../../includes/config.php';

$conexion = conectarDB();

// Filtros
$filtro_sede = isset($_GET['sede']) ? $_GET['sede'] : '';
$filtro_insumo = isset($_GET['insumo']) ? $_GET['insumo'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_area = isset($_GET['area']) ? $_GET['area'] : '';
$filtro_localidad = isset($_GET['localidad']) ? $_GET['localidad'] : '';

// Consulta agrupada por remito (esquema nuevo)
$sql = "SELECT 
            r.numero_remito,
            r.fecha_asignacion,
            r.nombre_persona_asignada,
            r.apellido_persona_asignada,
            ar.nombre_area,
            s.nombre_sede,
            l.nombre_localidad,
            SUM(d.cantidad) AS cantidad_insumos,
            SUM(CASE WHEN i.estado = 'Asignado' THEN d.cantidad ELSE 0 END) AS activas,
            GROUP_CONCAT(DISTINCT i.nombre_insumo ORDER BY i.nombre_insumo SEPARATOR ', ') AS insumos
        FROM remitos r 
        JOIN remitos_detalle d ON d.id_remito = r.id_remito
        JOIN insumos i ON d.id_insumo = i.id_insumo 
        JOIN areas ar ON r.id_area = ar.id_area 
        JOIN sedes s ON r.id_sede = s.id_sede 
        JOIN localidades l ON s.id_localidad = l.id_localidad 
        WHERE 1=1";

$params = [];

if ($filtro_sede) {
    $sql .= " AND s.id_sede = ?";
    $params[] = $filtro_sede;
}

if ($filtro_insumo) {
    $sql .= " AND i.tipo_insumo = ?";
    $params[] = $filtro_insumo;
}

if ($filtro_area) {
    $sql .= " AND r.id_area = ?";
    $params[] = $filtro_area;
}

if ($filtro_localidad) {
    $sql .= " AND l.id_localidad = ?";
    $params[] = $filtro_localidad;
}

$sql .= " GROUP BY r.id_remito";

if ($filtro_estado) {
    if ($filtro_estado === 'Activa') {
        $sql .= " HAVING activas > 0";
    } elseif ($filtro_estado === 'Devuelta') {
        $sql .= " HAVING activas = 0";
    }
}

$sql .= " ORDER BY r.fecha_asignacion DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$asignaciones = $stmt->fetchAll();

// Datos para filtros
$sedes = $conexion->query("SELECT id_sede, nombre_sede FROM sedes ORDER BY nombre_sede")->fetchAll();
$tipos_insumo = $conexion->query("SELECT DISTINCT tipo_insumo FROM insumos ORDER BY tipo_insumo")->fetchAll();
$areas = $conexion->query("SELECT id_area, nombre_area FROM areas ORDER BY nombre_area")->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-handshake me-2"></i>Gestión de Asignaciones
            </h1>
            <a href="nueva.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Asignación
            </a>
        </div>
    </div>
</div>

<div class="filtros-container">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="localidad" class="form-label">Localidad</label>
            <select name="localidad" id="localidad" class="form-select select2">
                <option value="">Todas las localidades</option>
                <?php foreach ($conexion->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad") as $loc): ?>
                    <option value="<?php echo $loc['id_localidad']; ?>" <?php echo $filtro_localidad == $loc['id_localidad'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loc['nombre_localidad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="sede" class="form-label">Sede</label>
            <select name="sede" id="sede" class="form-select select2">
                <option value="">Todas las sedes</option>
                <?php foreach ($sedes as $sede): ?>
                    <option value="<?php echo $sede['id_sede']; ?>" 
                            <?php echo $filtro_sede == $sede['id_sede'] ? 'selected' : ''; ?>>
                        <?php echo $sede['nombre_sede']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="insumo" class="form-label">Tipo de Insumo</label>
            <select name="insumo" id="insumo" class="form-select select2">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_insumo as $tipo): ?>
                    <option value="<?php echo $tipo['tipo_insumo']; ?>" 
                            <?php echo $filtro_insumo == $tipo['tipo_insumo'] ? 'selected' : ''; ?>>
                        <?php echo $tipo['tipo_insumo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select select2">
                <option value="">Todos los estados</option>
                <option value="Activa" <?php echo $filtro_estado == 'Activa' ? 'selected' : ''; ?>>Activa</option>
                <option value="Devuelta" <?php echo $filtro_estado == 'Devuelta' ? 'selected' : ''; ?>>Devuelta</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <label for="area" class="form-label">Área</label>
            <select name="area" id="area" class="form-select select2">
                <option value="">Todas las áreas</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?php echo $area['id_area']; ?>" 
                            <?php echo $filtro_area == $area['id_area'] ? 'selected' : ''; ?>>
                        <?php echo $area['nombre_area']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-2 d-flex align-items-end">
            <div class="d-grid gap-2 w-100">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<script>
$(function(){
  const BASE = typeof getAppBase === 'function' ? getAppBase() : '';
  $('#localidad').on('change', function(){
    const id = $(this).val();
    const $sede = $('#sede');
    const $area = $('#area');
    $sede.html('<option value="">Todas las sedes</option>');
    $area.html('<option value="">Todas las áreas</option>');
    if (!id) { return; }
    $.getJSON(`${BASE}/ajax/sedes_por_localidad.php`, { localidad_id: id })
      .done(r => {
        if (r.success) {
          r.data.forEach(s => { $sede.append(`<option value="${s.id}">${s.nombre}</option>`); });
          $sede.trigger('change.select2');
        }
      });
  });
  $('#sede').on('change', function(){
    const id = $(this).val();
    const $area = $('#area');
    $area.html('<option value="">Todas las áreas</option>');
    if (!id) { return; }
    $.getJSON(`${BASE}/ajax/areas_por_sede.php`, { sede_id: id })
      .done(r => {
        if (r.success) {
          r.data.forEach(a => { $area.append(`<option value="${a.id}">${a.nombre}</option>`); });
          $area.trigger('change.select2');
        }
      });
  });
});
</script>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Listado de Asignaciones (<?php echo count($asignaciones); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($asignaciones)): ?>
            <div class="text-center py-4">
                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron asignaciones</h5>
                <p class="text-muted">No hay asignaciones que coincidan con los filtros aplicados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped datatable" id="tablaAsignaciones">
                    <thead>
                        <tr>
                            <th>Remito</th>
                            <th>Insumos</th>
                            <th>Persona Asignada</th>
                            <th>Área</th>
                            <th>Sede</th>
                            <th>Fecha Asignación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones as $asignacion): ?>
                            <?php 
                                $estado = ((int)$asignacion['activas'] > 0) ? 'Activa' : 'Devuelta';
                                $insumosTexto = $asignacion['insumos'] ?: '—';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($asignacion['numero_remito']); ?></strong>
                                    <div><span class="badge bg-secondary"><?php echo (int)$asignacion['cantidad_insumos']; ?> insumo(s)</span></div>
                                </td>
                                <td style="max-width: 360px; white-space: normal;">
                                    <?php echo htmlspecialchars($insumosTexto); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($asignacion['nombre_persona_asignada'] . ' ' . $asignacion['apellido_persona_asignada']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($asignacion['nombre_area']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($asignacion['nombre_sede']); ?>
                                    <br><small class="text-muted"><?php echo $asignacion['nombre_localidad']; ?></small>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?>
                                </td>
                                <td>
                                    <span class="badge estado-<?php echo strtolower($estado); ?>">
                                        <?php echo $estado; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                onclick="abrirVerAsignacion('<?php echo $asignacion['numero_remito']; ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Ver asignación">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                onclick="generarRemitoPDF('<?php echo $asignacion['numero_remito']; ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Imprimir remito">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-success" 
                                                onclick="abrirDevolucion('<?php echo $asignacion['numero_remito']; ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Devolver insumos">
                                            <i class="fas fa-undo"></i>
                                        </button>
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

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" onclick="exportarExcel('tablaAsignaciones', 'asignaciones')">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
            <button type="button" class="btn btn-secondary" onclick="imprimirTabla('tablaAsignaciones')">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>