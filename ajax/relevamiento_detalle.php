<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver detalles de relevamientos', 403);
}

$id_remito = isset($_GET['id_remito']) ? (int)$_GET['id_remito'] : 0;
$numero_remito = isset($_GET['remito']) ? trim($_GET['remito']) : '';

if ($id_remito <= 0 && $numero_remito === '') {
    json_error('Parámetro de relevamiento requerido', 400);
}

try {
    $db = conectarDB();

    // 1. Obtener datos de cabecera del relevamiento
    $sqlCab = "SELECT r.*, 
                      s.nombre_sede, 
                      l.nombre_localidad, 
                      z.nombre_zona, 
                      a.nombre_area,
                      aud.fecha_accion as fecha_carga,
                      u.username as tecnico_username, 
                      u.nombre as tecnico_nombre, 
                      u.apellido as tecnico_apellido
               FROM remitos r
               JOIN sedes s ON r.id_sede = s.id_sede
               JOIN localidades l ON s.id_localidad = l.id_localidad
               LEFT JOIN zonas z ON l.id_zona = z.id_zona
               LEFT JOIN areas a ON r.id_area = a.id_area
               LEFT JOIN auditoria_acciones aud ON (aud.entidad_id = r.id_remito AND aud.accion = 'relevamiento_carga_masiva')
               LEFT JOIN usuarios u ON aud.id_usuario = u.id_usuario
               WHERE " . ($id_remito > 0 ? "r.id_remito = ?" : "r.numero_remito = ?") . "
               LIMIT 1";

    $stmtCab = $db->prepare($sqlCab);
    $stmtCab->execute([$id_remito > 0 ? $id_remito : $numero_remito]);
    $cab = $stmtCab->fetch(PDO::FETCH_ASSOC);

    if (!$cab) {
        json_error('Relevamiento no encontrado', 404);
    }

    $id_remito = (int)$cab['id_remito'];

    // 2. Obtener insumos del relevamiento
    $sqlDet = "SELECT d.cantidad, 
                      i.id_insumo, 
                      i.nombre_insumo, 
                      i.tipo_insumo, 
                      i.subcategoria_varios,
                      i.numero_serie, 
                      i.id_fisico, 
                      i.id_patrimonio, 
                      i.estado,
                      pc.procesador as pc_procesador, 
                      pc.ram_gb as pc_ram, 
                      pc.almacenamiento_gb as pc_disco, 
                      pc.sist_op as pc_os, 
                      pc.ssd_o_superior as pc_ssd,
                      nb.marca as nb_marca, 
                      nb.modelo as nb_modelo, 
                      nb.procesador as nb_procesador, 
                      nb.ram_gb as nb_ram, 
                      nb.almacenamiento_gb as nb_disco, 
                      nb.ssd_o_superior as nb_ssd,
                      mon.marca as mon_marca, 
                      mon.modelo as mon_modelo, 
                      mon.pulgadas as mon_pulgadas, 
                      mon.conexion as mon_conexion,
                      imp.marca as imp_marca, 
                      imp.modelo as imp_modelo,
                      esc.marca as esc_marca, 
                      esc.modelo as esc_modelo
               FROM remitos_detalle d
               JOIN insumos i ON d.id_insumo = i.id_insumo
               LEFT JOIN pcs_completas pc ON pc.id_insumo = i.id_insumo
               LEFT JOIN notebooks nb ON nb.id_insumo = i.id_insumo
               LEFT JOIN monitores mon ON mon.id_insumo = i.id_insumo
               LEFT JOIN impresoras imp ON imp.id_insumo = i.id_insumo
               LEFT JOIN escaneres esc ON esc.id_insumo = i.id_insumo
               WHERE d.id_remito = ?
               ORDER BY i.tipo_insumo, i.nombre_insumo";

    $stmtDet = $db->prepare($sqlDet);
    $stmtDet->execute([$id_remito]);
    $items = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    // Formatear fechas
    $cab['fecha_asignacion_formateada'] = $cab['fecha_asignacion'] ? date('d/m/Y', strtotime($cab['fecha_asignacion'])) : '-';
    $cab['fecha_carga_formateada'] = $cab['fecha_carga'] ? date('d/m/Y H:i', strtotime($cab['fecha_carga'])) : '-';
    
    $tecnico = '-';
    if (!empty($cab['tecnico_username'])) {
        $nombreCompleto = trim($cab['tecnico_nombre'] . ' ' . $cab['tecnico_apellido']);
        $tecnico = $nombreCompleto ? ($nombreCompleto . ' (' . $cab['tecnico_username'] . ')') : $cab['tecnico_username'];
    }
    $cab['tecnico_completo'] = $tecnico;

    // Procesar especificaciones amigables de cada insumo
    foreach ($items as &$item) {
        $tipo = $item['tipo_insumo'];
        $detalles = [];

        if ($tipo === 'PC Escritorio' || $tipo === 'PC Completa') {
            if (!empty($item['pc_procesador'])) $detalles[] = 'Proc: ' . $item['pc_procesador'];
            if (!empty($item['pc_ram'])) $detalles[] = 'RAM: ' . $item['pc_ram'] . ' GB';
            if (!empty($item['pc_disco'])) {
                $discoTxt = 'Disco: ' . $item['pc_disco'] . ' GB';
                if (!empty($item['pc_ssd'])) $discoTxt .= ' (SSD)';
                $detalles[] = $discoTxt;
            }
            if (!empty($item['pc_os'])) $detalles[] = 'SO: ' . $item['pc_os'];
        } elseif ($tipo === 'Notebook') {
            if (!empty($item['nb_marca']) || !empty($item['nb_modelo'])) {
                $detalles[] = trim($item['nb_marca'] . ' ' . $item['nb_modelo']);
            }
            if (!empty($item['nb_procesador'])) $detalles[] = 'Proc: ' . $item['nb_procesador'];
            if (!empty($item['nb_ram'])) $detalles[] = 'RAM: ' . $item['nb_ram'] . ' GB';
            if (!empty($item['nb_disco'])) {
                $discoTxt = 'Disco: ' . $item['nb_disco'] . ' GB';
                if (!empty($item['nb_ssd'])) $discoTxt .= ' (SSD)';
                $detalles[] = $discoTxt;
            }
        } elseif ($tipo === 'Monitor') {
            if (!empty($item['mon_marca']) || !empty($item['mon_modelo'])) {
                $detalles[] = trim($item['mon_marca'] . ' ' . $item['mon_modelo']);
            }
            if (!empty($item['mon_pulgadas'])) $detalles[] = $item['mon_pulgadas'] . '"';
            if (!empty($item['mon_conexion'])) $detalles[] = $item['mon_conexion'];
        } elseif ($tipo === 'Impresora') {
            if (!empty($item['imp_marca']) || !empty($item['imp_modelo'])) {
                $detalles[] = trim($item['imp_marca'] . ' ' . $item['imp_modelo']);
            }
        } elseif ($tipo === 'Escaner') {
            if (!empty($item['esc_marca']) || !empty($item['esc_modelo'])) {
                $detalles[] = trim($item['esc_marca'] . ' ' . $item['esc_modelo']);
            }
        } elseif ($tipo === 'Varios') {
            if (!empty($item['subcategoria_varios'])) $detalles[] = 'Subcat: ' . $item['subcategoria_varios'];
        }

        $item['especificaciones_texto'] = count($detalles) ? implode(' | ', $detalles) : '-';
    }
    unset($item);

    // 3. Construir HTML enriquecido para el modal
    ob_start();
    ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <p class="mb-1"><strong>Remito:</strong> <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($cab['numero_remito']); ?></span></p>
            <p class="mb-1"><strong>Fecha Relevamiento:</strong> <?php echo $cab['fecha_asignacion_formateada']; ?></p>
            <p class="mb-1"><strong>Sede:</strong> <?php echo htmlspecialchars($cab['nombre_sede']); ?></p>
            <p class="mb-1"><strong>Localidad:</strong> <?php echo htmlspecialchars($cab['nombre_localidad']); ?><?php if (!empty($cab['nombre_zona'])): ?> <span class="badge bg-info text-dark ms-1"><?php echo htmlspecialchars($cab['nombre_zona']); ?></span><?php endif; ?></p>
            <p class="mb-1"><strong>Área:</strong> <?php echo htmlspecialchars($cab['nombre_area'] ?: 'Sin área especificada'); ?></p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Responsable en Sede:</strong> <?php echo htmlspecialchars(trim($cab['nombre_persona_asignada'] . ' ' . $cab['apellido_persona_asignada']) ?: 'Sin responsable'); ?></p>
            <p class="mb-1"><strong>Cargado por:</strong> <?php echo htmlspecialchars($cab['tecnico_completo']); ?></p>
            <p class="mb-1"><strong>Fecha de Carga:</strong> <?php echo $cab['fecha_carga_formateada']; ?></p>
        </div>
    </div>

    <?php if (!empty($cab['observaciones'])): ?>
        <div class="alert alert-secondary py-2 px-3 mb-3">
            <strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($cab['observaciones'])); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">
            <i class="fas fa-boxes me-2"></i>Equipos Censados (<?php echo count($items); ?>)
        </h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="generarRemitoPDF('<?php echo htmlspecialchars($cab['numero_remito'], ENT_QUOTES); ?>')">
                <i class="fas fa-file-pdf me-1"></i>Ver Remito PDF
            </button>
            <?php if (!empty($cab['remito_firmado'])): ?>
                <?php $urlFirmado = app_base_url() . '/uploads/remitos_firmados/' . htmlspecialchars($cab['remito_firmado']); ?>
                <a href="<?php echo $urlFirmado; ?>" data-visor-archivo="<?php echo $urlFirmado; ?>" data-visor-titulo="Remito Firmado <?php echo htmlspecialchars($cab['numero_remito']); ?>" target="_blank" class="btn btn-sm btn-success">
                    <i class="fas fa-file-signature me-1"></i>Ver Remito Firmado
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Tipo</th>
                    <th>Descripción / Insumo</th>
                    <th>Nº Serie</th>
                    <th>ID Físico</th>
                    <th>Especificaciones</th>
                    <th class="text-center" style="width: 70px;">Cant.</th>
                    <th class="text-center" style="width: 60px;">Ficha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-3 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No hay insumos registrados en este relevamiento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $contador = 1;
                    foreach ($items as $it): 
                        $tipoClass = 'bg-secondary';
                        $tipoIcon = 'fa-box';
                        if ($it['tipo_insumo'] === 'PC Escritorio' || $it['tipo_insumo'] === 'PC Completa') { $tipoClass = 'bg-primary'; $tipoIcon = 'fa-desktop'; }
                        elseif ($it['tipo_insumo'] === 'Notebook') { $tipoClass = 'bg-secondary'; $tipoIcon = 'fa-laptop'; }
                        elseif ($it['tipo_insumo'] === 'Monitor') { $tipoClass = 'bg-info text-dark'; $tipoIcon = 'fa-tv'; }
                        elseif ($it['tipo_insumo'] === 'Impresora') { $tipoClass = 'bg-warning text-dark'; $tipoIcon = 'fa-print'; }
                        elseif ($it['tipo_insumo'] === 'Escaner') { $tipoClass = 'bg-dark'; $tipoIcon = 'fa-scanner'; }
                    ?>
                    <tr>
                        <td class="text-center text-muted"><?php echo $contador++; ?></td>
                        <td>
                            <span class="badge <?php echo $tipoClass; ?>">
                                <i class="fas <?php echo $tipoIcon; ?> me-1"></i><?php echo htmlspecialchars($it['tipo_insumo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($it['nombre_insumo']); ?>
                        </td>
                        <td>
                            <?php if (!empty($it['numero_serie'])): ?>
                                <span class="font-monospace small"><?php echo htmlspecialchars($it['numero_serie']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($it['id_fisico'])): ?>
                                <span class="font-monospace small text-primary"><?php echo htmlspecialchars($it['id_fisico']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php echo htmlspecialchars($it['especificaciones_texto']); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success"><?php echo (int)$it['cantidad']; ?></span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="verInsumo(<?php echo (int)$it['id_insumo']; ?>)" title="Ver ficha del insumo">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();

    json_success([
        'cab' => $cab,
        'items' => $items,
        'html' => $html
    ]);

} catch (Exception $e) {
    Logger::error('Error en ajax/relevamiento_detalle.php', [
        'mensaje' => $e->getMessage(),
        'id_remito' => $id_remito,
        'numero_remito' => $numero_remito
    ]);
    json_error('Error al obtener detalle del relevamiento: ' . $e->getMessage(), 500);
}
