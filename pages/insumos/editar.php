<?php
require_once '../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = conectarDB();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de insumo no válido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}
$id = (int)$_GET['id'];

// Traer insumo
$stmt = $db->prepare("SELECT * FROM insumos WHERE id_insumo = ?");
$stmt->execute([$id]);
$insumo = $stmt->fetch();
if (!$insumo) {
    $_SESSION['mensaje'] = 'El insumo no existe.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: listar.php');
    exit;
}
$tipo_insumo = $insumo['tipo_insumo'];

// Permitir edición aunque esté asignado (según requisito)

// Tablas específicas
$esp = [];
switch ($tipo_insumo) {
    case 'PC Completa':
        $q = $db->prepare("SELECT * FROM pcs_completas WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Notebook':
        $q = $db->prepare("SELECT * FROM notebooks WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Impresora':
        $q = $db->prepare("SELECT * FROM impresoras WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Monitor':
        $q = $db->prepare("SELECT * FROM monitores WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
    case 'Escaner':
        $q = $db->prepare("SELECT * FROM escaneres WHERE id_insumo = ?");
        $q->execute([$id]); $esp = $q->fetch() ?: [];
        break;
}

// Puntos de stock
$puntos_stock = $db->query("SELECT id_punto_stock, nombre_punto FROM puntos_stock ORDER BY nombre_punto")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
        $db->beginTransaction();

        // NO permitir cambiar tipo
        $tipo_fijo = $tipo_insumo;

        $nombre = trim($_POST['nombre_insumo'] ?? '');
        $fecha = $_POST['fecha_adquisicion'] ?: null;
        $estado = $_POST['estado'] ?? 'Disponible';
        $punto = $_POST['id_punto_stock_actual'] ?: null;
        $esNuevo = isset($_POST['es_nuevo']) && $_POST['es_nuevo'] == '1' ? 1 : 0;
        $idLicitacion = !empty($_POST['id_licitacion']) ? (int)$_POST['id_licitacion'] : null;

        // Campos específicos según tipo
        $subcat = $desc = $numero_serie = $id_fisico = $id_patrimonio = null;
        $cantidad = 1;

        if ($tipo_fijo === 'Varios') {
            $subcat = ($_POST['subcategoria_varios'] ?? '') ?: null;
            $desc = ($_POST['descripcion_general'] ?? '') ?: null;
            $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        } else {
            $numero_serie = ($_POST['numero_serie'] ?? '') ?: null;
            $id_fisico = ($_POST['id_fisico'] ?? '') ?: null;
            $id_patrimonio = ($_POST['id_patrimonio'] ?? '') ?: null;
            $cantidad = 1; // fijo

            if ($id_patrimonio === null || trim($id_patrimonio) === '') {
                throw new Exception('El ID Patrimonio es obligatorio para este tipo de insumo.');
            }
        }

        // Actualizar insumo
        $sql = "UPDATE insumos
                SET nombre_insumo = ?, subcategoria_varios = ?, descripcion_general = ?,
                    numero_serie = ?, id_fisico = ?, id_patrimonio = ?, cantidad = ?, fecha_adquisicion = ?,
                    id_punto_stock_actual = ?, id_licitacion = ?, es_nuevo = ?
                WHERE id_insumo = ?";
        $db->prepare($sql)->execute([
            $nombre, $subcat, $desc,
            $numero_serie, $id_fisico, $id_patrimonio, $cantidad, $fecha,
            $punto, $idLicitacion, $esNuevo,
            $id
        ]);

        // Actualizar tabla específica
        switch ($tipo_fijo) {
            case 'PC Completa':
                $db->prepare("DELETE FROM pcs_completas WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO pcs_completas (id_insumo, procesador, ram_gb, almacenamiento_gb, mother) VALUES (?,?,?,?,?)")
                   ->execute([
                       $id,
                       ($_POST['procesador'] ?? null),
                       ($_POST['ram_gb'] ?? null),
                       ($_POST['almacenamiento_gb'] ?? null),
                       ($_POST['mother'] ?? null)
                   ]);
                break;
            case 'Notebook':
                $db->prepare("DELETE FROM notebooks WHERE id_insumo = ?")->execute([$id]);
                $stmt = $db->prepare("INSERT INTO notebooks (id_insumo, marca, modelo, procesador, ram_gb, almacenamiento_gb, cargador, funda, micro_sd, micro_sd_gb, caja, adaptador_red) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $cargador = isset($_POST['cargador']) ? 1 : 0;
                $funda = isset($_POST['funda']) ? 1 : 0;
                $microSd = isset($_POST['micro_sd']) ? 1 : 0;
                $microSdGb = $microSd ? (($_POST['micro_sd_gb'] !== '' ? (int)$_POST['micro_sd_gb'] : null)) : null;
                $caja = isset($_POST['caja']) ? 1 : 0;
                $adaptadorRed = isset($_POST['adaptador_red']) ? 1 : 0;
                $stmt->execute([
                    $id,
                    ($_POST['marca_notebook'] ?? null),
                    ($_POST['modelo_notebook'] ?? null),
                    ($_POST['procesador_notebook'] ?? null),
                    ($_POST['ram_gb_notebook'] ?? null),
                    ($_POST['almacenamiento_gb_notebook'] ?? null),
                    $cargador,
                    $funda,
                    $microSd,
                    $microSdGb,
                    $caja,
                    $adaptadorRed
                ]);
                break;
            case 'Impresora':
                $db->prepare("DELETE FROM impresoras WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO impresoras (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id, ($_POST['marca_impresora'] ?? null), ($_POST['modelo_impresora'] ?? null)]);
                break;
            case 'Monitor':
                $db->prepare("DELETE FROM monitores WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO monitores (id_insumo, marca, modelo, pulgadas, conexion) VALUES (?,?,?,?,?)")
                   ->execute([
                       $id,
                       ($_POST['marca_monitor'] ?? null),
                       ($_POST['modelo_monitor'] ?? null),
                       ($_POST['pulgadas'] ?? null),
                       ($_POST['conexion_monitor'] ?? null)
                   ]);
                break;
            case 'Escaner':
                $db->prepare("DELETE FROM escaneres WHERE id_insumo = ?")->execute([$id]);
                $db->prepare("INSERT INTO escaneres (id_insumo, marca, modelo) VALUES (?,?,?)")
                   ->execute([$id, ($_POST['marca_escaner'] ?? null), ($_POST['modelo_escaner'] ?? null)]);
                break;
        }

        $db->commit();

        $_SESSION['mensaje'] = "Insumo actualizado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        header("Location: listar.php");
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['mensaje'] = "Error al actualizar insumo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: editar.php?id=".$id);
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Insumo</h4>
            <a href="listar.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <form method="POST" id="formInsumo" class="needs-validation" novalidate>
            <?php echo csrf_input(); ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            <h6 class="mb-0">Tipo de Insumo</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($tipo_insumo); ?>" disabled>
                                <input type="hidden" name="tipo_insumo" value="<?php echo htmlspecialchars($tipo_insumo); ?>">
                                <small class="text-muted">El tipo no se puede cambiar.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-2" id="formulario-campos">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información Básica</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label">Nombre del Insumo *</label>
                                <input type="text" class="form-control form-control-sm" name="nombre_insumo" value="<?php echo htmlspecialchars($insumo['nombre_insumo']); ?>" required>
                            </div>

                            <?php if ($tipo_insumo === 'Varios'): ?>
                                <div id="campos-varios">
                                    <div class="mb-2">
                                        <label class="form-label">Subcategoría</label>
                                        <select class="form-select form-select-sm" name="subcategoria_varios">
                                            <option value="">Seleccione subcategoría</option>
                                            <option value="Hardware" <?php echo $insumo['subcategoria_varios']==='Hardware'?'selected':''; ?>>Hardware</option>
                                            <option value="Periféricos" <?php echo $insumo['subcategoria_varios']==='Periféricos'?'selected':''; ?>>Periféricos</option>
                                            <option value="Red" <?php echo $insumo['subcategoria_varios']==='Red'?'selected':''; ?>>Red</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Cantidad *</label>
                                        <input type="number" class="form-control form-control-sm" name="cantidad" value="<?php echo (int)$insumo['cantidad']; ?>" min="1" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Descripción General</label>
                                        <textarea class="form-control form-control-sm" name="descripcion_general" rows="2"><?php echo htmlspecialchars($insumo['descripcion_general'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div id="campos-especificos">
                                    <div class="mb-2">
                                        <label class="form-label">Número de Serie</label>
                                        <input type="text" class="form-control form-control-sm" name="numero_serie" value="<?php echo htmlspecialchars($insumo['numero_serie'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">ID Físico</label>
                                        <input type="text" class="form-control form-control-sm" name="id_fisico" value="<?php echo htmlspecialchars($insumo['id_fisico'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">ID Patrimonio</label>
                                        <input type="text" class="form-control form-control-sm" name="id_patrimonio" value="<?php echo htmlspecialchars($insumo['id_patrimonio'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" class="form-control form-control-sm" value="1" readonly>
                                        <small class="form-text text-muted">Para este tipo de insumo, la cantidad siempre es 1 (carga unitaria)</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Información Común</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <label class="form-label">Fecha de Adquisición</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_adquisicion" value="<?php echo htmlspecialchars($insumo['fecha_adquisicion'] ?? ''); ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($insumo['estado']); ?>" disabled>
                                <small class="text-muted">El estado no se modifica desde esta pantalla.</small>
                            </div>
                            <div class="mb-2">
                                <label for="id_punto_stock_actual" class="form-label">Punto de Almacenamiento</label>
                                <select class="form-select form-select-sm" id="id_punto_stock_actual" name="id_punto_stock_actual">
                                    <option value="">Sin punto de stock</option>
                                    <?php foreach ($puntos_stock as $punto): ?>
                                        <option value="<?php echo $punto['id_punto_stock']; ?>" 
                                                <?php echo $insumo['id_punto_stock_actual'] == $punto['id_punto_stock'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($punto['nombre_punto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="es_nuevo" name="es_nuevo" value="1" <?php echo ($insumo['es_nuevo'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="es_nuevo">
                                        <strong>Insumo Nuevo</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="id_licitacion" class="form-label">Nro. Expediente</label>
                                <select class="form-select form-select-sm" id="id_licitacion" name="id_licitacion">
                                    <option value="">Sin licitación</option>
                                    <?php
                                    $licitaciones = $db->query("SELECT id_licitacion, cod_expediente FROM licitaciones ORDER BY cod_expediente DESC")->fetchAll();
                                    foreach ($licitaciones as $lic):
                                    ?>
                                        <option value="<?php echo $lic['id_licitacion']; ?>" <?php echo ($insumo['id_licitacion'] ?? null) == $lic['id_licitacion'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lic['cod_expediente']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Opcional: Asociar insumo a una licitación</small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($tipo_insumo !== 'Varios'): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-cogs me-2 text-primary"></i>Especificaciones</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if ($tipo_insumo === 'PC Completa'): ?>
                                <div class="mb-2">
                                    <label class="form-label">Procesador</label>
                                    <input type="text" class="form-control form-control-sm" name="procesador" value="<?php echo htmlspecialchars($esp['procesador'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">RAM (GB)</label>
                                    <input type="number" class="form-control form-control-sm" name="ram_gb" value="<?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Almacenamiento (GB)</label>
                                    <input type="number" class="form-control form-control-sm" name="almacenamiento_gb" value="<?php echo htmlspecialchars($esp['almacenamiento_gb'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Mother</label>
                                    <input type="text" class="form-control form-control-sm" name="mother" value="<?php echo htmlspecialchars($esp['mother'] ?? ''); ?>">
                                </div>
                            <?php elseif ($tipo_insumo === 'Notebook'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_notebook" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_notebook" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Procesador</label><input type="text" class="form-control form-control-sm" name="procesador_notebook" value="<?php echo htmlspecialchars($esp['procesador'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">RAM (GB)</label><input type="number" class="form-control form-control-sm" name="ram_gb_notebook" value="<?php echo htmlspecialchars($esp['ram_gb'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Almacenamiento (GB)</label><input type="number" class="form-control form-control-sm" name="almacenamiento_gb_notebook" value="<?php echo htmlspecialchars($esp['almacenamiento_gb'] ?? ''); ?>"></div>
                                <hr>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="cargador" name="cargador" value="1" <?php echo !empty($esp['cargador']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cargador">Cargador</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="funda" name="funda" value="1" <?php echo !empty($esp['funda']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="funda">Funda</label>
                                </div>
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="micro_sd" name="micro_sd" value="1" <?php echo !empty($esp['micro_sd']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="micro_sd">Micro SD</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <input type="number" min="0" step="1" class="form-control form-control-sm" id="micro_sd_gb" name="micro_sd_gb" placeholder="Tamaño (GB)" value="<?php echo isset($esp['micro_sd_gb']) ? (int)$esp['micro_sd_gb'] : ''; ?>" <?php echo !empty($esp['micro_sd']) ? '' : 'disabled'; ?>>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="caja" name="caja" value="1" <?php echo !empty($esp['caja']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="caja">Caja</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="adaptador_red" name="adaptador_red" value="1" <?php echo !empty($esp['adaptador_red']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="adaptador_red">Adaptador de red</label>
                                </div>
                            <?php elseif ($tipo_insumo === 'Impresora'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_impresora" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_impresora" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                            <?php elseif ($tipo_insumo === 'Monitor'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_monitor" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_monitor" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Pulgadas</label><input type="number" step="0.1" class="form-control form-control-sm" name="pulgadas" value="<?php echo htmlspecialchars($esp['pulgadas'] ?? ''); ?>"></div>
                                <div class="mb-2">
                                    <label class="form-label">Conexión</label>
                                    <select class="form-select form-select-sm" name="conexion_monitor">
                                        <?php $cx = $esp['conexion'] ?? ''; ?>
                                        <option value="">Seleccione</option>
                                        <option value="VGA" <?php echo $cx==='VGA'?'selected':''; ?>>VGA</option>
                                        <option value="HDMI" <?php echo $cx==='HDMI'?'selected':''; ?>>HDMI</option>
                                    </select>
                                </div>
                            <?php elseif ($tipo_insumo === 'Escaner'): ?>
                                <div class="mb-2"><label class="form-label">Marca</label><input type="text" class="form-control form-control-sm" name="marca_escaner" value="<?php echo htmlspecialchars($esp['marca'] ?? ''); ?>"></div>
                                <div class="mb-2"><label class="form-label">Modelo</label><input type="text" class="form-control form-control-sm" name="modelo_escaner" value="<?php echo htmlspecialchars($esp['modelo'] ?? ''); ?>"></div>
            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="listar.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
(function(){
  const micro = document.getElementById('micro_sd');
  const gb = document.getElementById('micro_sd_gb');
  if (micro && gb) {
    micro.addEventListener('change', function(){
      if (this.checked) {
        gb.removeAttribute('disabled');
      } else {
        gb.value = '';
        gb.setAttribute('disabled','disabled');
      }
    });
  }
})();
</script>