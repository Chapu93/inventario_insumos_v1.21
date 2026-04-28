<?php
/**
 * Endpoint para buscar insumos asignados a una persona
 * Retorna todos los insumos activos de una persona (de todos sus remitos activos)
 * Usado por el modal de transferencia masiva de insumos
 */
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'ver')) {
    json_error('Sin permiso para ver asignaciones', 403);
}

try {
    $db = conectarDB();

    $modo = trim($_GET['modo'] ?? 'buscar'); // 'buscar' | 'insumos'

    // ─── MODO BUSCAR: autocomplete de personas ───────────────────────────────
    if ($modo === 'buscar') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            json_success(['personas' => [], 'mensaje' => 'Ingrese al menos 2 caracteres']);
            exit;
        }

        $like = '%' . $q . '%';

        // Buscar personas con remitos activos que tengan insumos pendientes de devolver
        $sql = "SELECT DISTINCT
                    r.nombre_persona_asignada,
                    r.apellido_persona_asignada,
                    s.id_sede,
                    s.nombre_sede,
                    a.id_area,
                    a.nombre_area,
                    l.nombre_localidad,
                    COUNT(DISTINCT r.id_remito) AS cantidad_remitos,
                    SUM(GREATEST(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0), 0)) AS total_insumos
                FROM remitos r
                JOIN remitos_detalle rd ON rd.id_remito = r.id_remito
                JOIN sedes s ON r.id_sede = s.id_sede
                JOIN areas a ON r.id_area = a.id_area
                JOIN localidades l ON s.id_localidad = l.id_localidad
                WHERE r.estado = 'Activa'
                  AND GREATEST(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0), 0) > 0
                  AND (
                    CONCAT(r.nombre_persona_asignada, ' ', r.apellido_persona_asignada) LIKE ?
                    OR r.apellido_persona_asignada LIKE ?
                    OR r.nombre_persona_asignada LIKE ?
                  )
                GROUP BY
                    r.nombre_persona_asignada,
                    r.apellido_persona_asignada,
                    s.id_sede,
                    a.id_area
                HAVING total_insumos > 0
                ORDER BY r.apellido_persona_asignada, r.nombre_persona_asignada
                LIMIT 20";

        $stmt = $db->prepare($sql);
        $stmt->execute([$like, $like, $like]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $personas = array_map(function ($f) {
            return [
                'nombre'           => $f['nombre_persona_asignada'],
                'apellido'         => $f['apellido_persona_asignada'],
                'nombre_completo'  => $f['nombre_persona_asignada'] . ' ' . $f['apellido_persona_asignada'],
                'id_sede'          => $f['id_sede'],
                'nombre_sede'      => $f['nombre_sede'],
                'id_area'          => $f['id_area'],
                'nombre_area'      => $f['nombre_area'],
                'localidad'        => $f['nombre_localidad'],
                'cantidad_remitos' => (int)$f['cantidad_remitos'],
                'total_insumos'    => (int)$f['total_insumos'],
                // Clave para identificar unívocamente a la persona en el modal
                'key'              => $f['nombre_persona_asignada'] . '|' . $f['apellido_persona_asignada'],
            ];
        }, $filas);

        json_success(['personas' => $personas]);
        exit;
    }

    // ─── MODO INSUMOS: retornar todos los insumos activos de una persona ─────
    if ($modo === 'insumos') {
        $nombre   = trim($_GET['nombre']   ?? '');
        $apellido = trim($_GET['apellido'] ?? '');

        if (!$nombre || !$apellido) {
            json_error('Nombre y apellido son requeridos', 400);
        }

        // Traer todos los insumos pendientes de devolución de todos los remitos activos
        $sql = "SELECT
                    i.id_insumo,
                    i.tipo_insumo,
                    i.nombre_insumo,
                    i.numero_serie,
                    i.id_fisico,
                    i.id_patrimonio,
                    r.id_remito,
                    r.numero_remito,
                    r.id_sede,
                    r.id_area,
                    s.nombre_sede,
                    a.nombre_area,
                    l.nombre_localidad,
                    rd.cantidad,
                    COALESCE(rd.cantidad_devuelta, 0) AS cantidad_devuelta,
                    GREATEST(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0), 0) AS pendiente
                FROM remitos r
                JOIN remitos_detalle rd ON rd.id_remito = r.id_remito
                JOIN insumos i ON i.id_insumo = rd.id_insumo
                JOIN sedes s ON r.id_sede = s.id_sede
                JOIN areas a ON r.id_area = a.id_area
                JOIN localidades l ON s.id_localidad = l.id_localidad
                WHERE r.estado = 'Activa'
                  AND r.nombre_persona_asignada = ?
                  AND r.apellido_persona_asignada = ?
                  AND GREATEST(rd.cantidad - COALESCE(rd.cantidad_devuelta, 0), 0) > 0
                ORDER BY r.fecha_asignacion DESC, i.tipo_insumo, i.nombre_insumo";

        $stmt = $db->prepare($sql);
        $stmt->execute([$nombre, $apellido]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formateamos los datos para el frontend
        $insumos = array_map(function ($f) {
            return [
                'id_insumo'       => (int)$f['id_insumo'],
                'tipo_insumo'     => $f['tipo_insumo'],
                'nombre_insumo'   => $f['nombre_insumo'] ?? '',
                'numero_serie'    => $f['numero_serie'] ?? '',
                'id_fisico'       => $f['id_fisico'] ?? '',
                'id_patrimonio'   => $f['id_patrimonio'] ?? '',
                'id_remito'       => (int)$f['id_remito'],
                'numero_remito'   => $f['numero_remito'],
                'id_sede_origen'  => (int)$f['id_sede'],
                'id_area_origen'  => (int)$f['id_area'],
                'sede'            => $f['nombre_sede'],
                'area'            => $f['nombre_area'],
                'localidad'       => $f['nombre_localidad'],
                'cantidad'        => (int)$f['cantidad'],
                'cantidad_devuelta'=> (int)$f['cantidad_devuelta'],
                'pendiente'       => (int)$f['pendiente'],
            ];
        }, $filas);

        json_success([
            'insumos'  => $insumos,
            'total'    => count($insumos),
            'persona'  => trim($nombre . ' ' . $apellido),
        ]);
        exit;
    }

    // ─── MODO SEDES: todas las sedes sin filtro (para el select del modal) ────
    if ($modo === 'sedes') {
        $stmt = $db->query(
            "SELECT s.id_sede AS id,
                    CONCAT(s.nombre_sede, ' (', l.nombre_localidad, ')') AS nombre
             FROM sedes s
             JOIN localidades l ON s.id_localidad = l.id_localidad
             ORDER BY l.nombre_localidad, s.nombre_sede"
        );
        $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_success(['sedes' => $sedes]);
        exit;
    }

    json_error('Modo inválido', 400);

} catch (Exception $e) {
    Logger::error('Error en insumos_por_persona', ['error' => $e->getMessage()]);
    json_error($e->getMessage(), 500);
}
