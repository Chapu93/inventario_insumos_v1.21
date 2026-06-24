<?php
/**
 * Endpoint para crear asignación rápida desde el formulario de pedidos
 * Crea un remito simple con un solo insumo y retorna los datos para el pedido
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

// Verificar permisos (necesita poder crear asignaciones)
if (!tienePermiso('asignaciones', 'crear')) {
    json_error('Sin permiso para crear asignaciones', 403);
}

try {
    $db = conectarDB();
    
    // Datos del formulario
    $idSede = (int)($_POST['id_sede'] ?? 0);
    $idArea = (int)($_POST['id_area'] ?? 0);
    $idInsumo = (int)($_POST['id_insumo'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validar datos requeridos
    if (!$idSede || !$idArea || !$idInsumo || !$nombre || !$apellido) {
        json_error('Datos incompletos', 400);
    }
    
    // Verificar que el insumo existe y está disponible
    $stmt = $db->prepare("SELECT id_insumo, tipo_insumo, nombre_insumo, numero_serie, estado FROM insumos WHERE id_insumo = ?");
    $stmt->execute([$idInsumo]);
    $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$insumo) {
        json_error('Insumo no encontrado', 404);
    }
    
    if ($insumo['estado'] !== 'Disponible') {
        json_error('El insumo no está disponible para asignación', 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Generar número de remito
        $numero = generarNumeroRemito($db);
        
        // Crear cabecera de remito
        $stmtCab = $db->prepare("INSERT INTO remitos (numero_remito, id_sede, id_area, nombre_persona_asignada, apellido_persona_asignada, fecha_asignacion, observaciones) VALUES (?,?,?,?,?,CURDATE(),?)");
        $stmtCab->execute([
            $numero,
            $idSede,
            $idArea,
            $nombre,
            $apellido,
            $observaciones ?: null
        ]);
        $idRemito = $db->lastInsertId();
        
        // Crear detalle de remito
        $stmtDet = $db->prepare("INSERT INTO remitos_detalle (id_remito, id_insumo, cantidad) VALUES (?,?,1)");
        $stmtDet->execute([$idRemito, $idInsumo]);
        
        // Actualizar estado del insumo (manejo de stock dual para Varios)
        if ($insumo['tipo_insumo'] === 'Varios') {
            // Recargar info completa para tener cantidades
            $st = $db->prepare("SELECT cantidad, cantidad_oficina, cantidad_deposito FROM insumos WHERE id_insumo = ? FOR UPDATE");
            $st->execute([$idInsumo]);
            $i = $st->fetch();
            
            $reps = 1; // Asignación rápida siempre es 1
            $stockOficina = (int) ($i['cantidad_oficina'] ?? $i['cantidad']);
            $stockDeposito = (int) ($i['cantidad_deposito'] ?? 0);

            $descontarOficina = min($reps, $stockOficina);
            $descontarDeposito = $reps - $descontarOficina;

            $nuevoOficina = $stockOficina - $descontarOficina;
            $nuevoDeposito = $stockDeposito - $descontarDeposito;
            $cantidadTotal = $nuevoOficina + $nuevoDeposito;

            $estado = ($cantidadTotal > 0) ? 'Disponible' : 'Asignado';
            
            if ($cantidadTotal > 0) {
                $db->prepare("UPDATE insumos SET cantidad=?, cantidad_oficina=?, cantidad_deposito=?, estado=?, id_sede_actual=NULL, id_area_asignacion_actual=NULL, es_nuevo = IF(es_nuevo = 1, 0, es_nuevo) WHERE id_insumo=?")
                   ->execute([$cantidadTotal, $nuevoOficina, $nuevoDeposito, $estado, $idInsumo]);
            } else {
                // Si se agotó todo, limpiar punto de stock y dejar ubicación en NULL para tipo Varios (no falsear ubicación de lote)
                $db->prepare("UPDATE insumos SET cantidad=?, cantidad_oficina=?, cantidad_deposito=?, estado=?, id_sede_actual=NULL, id_area_asignacion_actual=NULL, id_punto_stock_actual=NULL, es_nuevo = IF(es_nuevo = 1, 0, es_nuevo) WHERE id_insumo=?")
                   ->execute([$cantidadTotal, $nuevoOficina, $nuevoDeposito, $estado, $idInsumo]);
            }
        } else {
            // Para unitarios, lógica estándar
            $stmtUpd = $db->prepare("UPDATE insumos SET estado = 'Asignado', id_sede_actual = ?, id_area_asignacion_actual = ?, id_punto_stock_actual = NULL, es_nuevo = IF(es_nuevo = 1, 0, es_nuevo) WHERE id_insumo = ?");
            $stmtUpd->execute([$idSede, $idArea, $idInsumo]);
        }
        
        $db->commit();
        
        // Construir texto descriptivo del insumo
        $insumoTexto = $insumo['tipo_insumo'];
        if (!empty($insumo['nombre_insumo'])) {
            $insumoTexto .= ' - ' . $insumo['nombre_insumo'];
        }
        if (!empty($insumo['numero_serie'])) {
            $insumoTexto .= ' (S/N: ' . $insumo['numero_serie'] . ')';
        }
        
        json_success([
            'id_insumo' => $idInsumo,
            'id_remito' => $idRemito,
            'numero_remito' => $numero,
            'insumo_texto' => $insumoTexto
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}
