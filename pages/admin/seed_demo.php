<?php
require_once '../../includes/config.php';

$db = conectarDB();

$tipos = [
    ['tipo' => 'Varios', 'nombre' => 'Cable HDMI', 'sub' => 'Periféricos'],
    ['tipo' => 'Varios', 'nombre' => 'Patch cord CAT6', 'sub' => 'Red'],
    ['tipo' => 'Varios', 'nombre' => 'Mouse óptico', 'sub' => 'Periféricos'],
    ['tipo' => 'PC Completa', 'nombre' => 'PC Escritorio Oficina', 'sub' => null],
    ['tipo' => 'Notebook', 'nombre' => 'Notebook 14"', 'sub' => null],
    ['tipo' => 'Impresora', 'nombre' => 'Impresora Láser', 'sub' => null],
    ['tipo' => 'Monitor', 'nombre' => 'Monitor 24"', 'sub' => null],
    ['tipo' => 'Escaner', 'nombre' => 'Escáner Documental', 'sub' => null],
    ['tipo' => 'Varios', 'nombre' => 'Teclado USB', 'sub' => 'Periféricos'],
    ['tipo' => 'Varios', 'nombre' => 'Regleta 6 tomas', 'sub' => 'Hardware'],
];

$insertados = 0;
try {
    foreach ($tipos as $i => $t) {
        $tipo = $t['tipo'];
        $nombre = $t['nombre'];
        $sub = $t['sub'];
        if ($tipo === 'Varios') {
            $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, subcategoria_varios, descripcion_general, cantidad, fecha_adquisicion, estado, id_punto_stock_actual) VALUES (?,?,?,?,?, ?, 'Disponible', 2)");
            $stmt->execute([$nombre, $tipo, $sub, 'Demo seed', rand(5, 20), date('Y-m-d')]);
        } else {
            $stmt = $db->prepare("INSERT INTO insumos (nombre_insumo, tipo_insumo, numero_serie, id_fisico, id_patrimonio, cantidad, fecha_adquisicion, estado, id_punto_stock_actual) VALUES (?,?,?,?,?, 1, ?, 'Disponible', 2)");
            $stmt->execute([
                $nombre,
                $tipo,
                strtoupper($tipo) . '-SN' . str_pad((string)($i+1), 4, '0', STR_PAD_LEFT),
                strtoupper($tipo) . '-ID' . str_pad((string)($i+1), 4, '0', STR_PAD_LEFT),
                'PAT' . str_pad((string)($i+1), 5, '0', STR_PAD_LEFT),
                date('Y-m-d')
            ]);
        }
        $insertados++;
    }
    $ok = true;
    $msg = "Insumos de demo insertados: {$insertados}";
} catch (Exception $e) {
    $ok = false;
    $msg = 'Error: ' . $e->getMessage();
}

include '../../includes/header.php';
?>
<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="fas fa-database me-2"></i>Seed de Insumos Demo</h6></div>
  <div class="card-body">
    <?php if (!empty($ok)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php else: ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <a href="<?php echo app_base_url(); ?>/pages/insumos/listar.php" class="btn btn-primary">Ir al listado de insumos</a>
  </div>
</div>
<?php include '../../includes/footer.php'; ?>

