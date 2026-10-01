<?php
/**
 * Interfaz de Visualización y Diagnóstico de Insumos Duplicados
 * Cruce: Asignaciones Previas (Pre-10/08) vs Relevamiento Masivo (Post-10/08 / _hist)
 * SITIA - Sistema de Inventario de Telecomunicaciones, Insumos y Administración
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Control de acceso opcional (o permitir en desarrollo local)
$esAdmin = estaAutenticado() && tieneRol([1, 2, 'Super Administrador', 'Superadministrador', 'Administrador']);

$db = conectarDB();

// -----------------------------------------------------------------------------
// FUNCIONES AUXILIARES DE COMPARACIÓN Y LIMPIEZA
// -----------------------------------------------------------------------------

function limpiarCadena(?string $str): string {
    if ($str === null) return '';
    $s = trim($str);
    $s = str_replace([' ', '-', '_', '.', '/', '\\'], '', $s);
    return strtolower($s);
}

function esIdentificadorGenerico(?string $str): bool {
    $c = limpiarCadena($str);
    if ($c === '') return true;
    $genericos = ['sn', 's/n', 'sinnumero', 'sinserie', 'noposee', 'notiene', 'desconocido', 'none', 'null', '0', 's_n'];
    return in_array($c, $genericos, true);
}

function normalizarPersona(?string $nombre, ?string $apellido): array {
    $n = trim($nombre ?? '');
    $a = trim($apellido ?? '');
    $completo = trim("{$n} {$a}");
    
    // Quitar acentos
    $unaccent = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $completo);
    $unaccent = preg_replace('/[^a-zA-Z0-9 ]/', '', $unaccent);
    
    return [
        'original' => $completo,
        'apellido' => trim($a),
        'apellido_limpio' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $a)),
        'nombre_completo_limpio' => strtolower($unaccent)
    ];
}

// -----------------------------------------------------------------------------
// CONSULTA DE DATOS EN BASE DE DATOS
// -----------------------------------------------------------------------------

// 1. Obtener Insumos con asignaciones PREVIAS (Remitos oficiales anteriores al 10/08)
$sqlPre = "
SELECT 
    i.id_insumo,
    i.tipo_insumo,
    i.numero_serie,
    i.id_fisico,
    i.id_patrimonio,
    i.estado,
    i.fecha_adquisicion,
    s.id_sede,
    s.nombre_sede,
    l.id_localidad,
    l.nombre_localidad,
    a.id_area,
    a.nombre_area,
    r.id_remito,
    r.numero_remito,
    r.fecha_asignacion,
    r.nombre_persona_asignada,
    r.apellido_persona_asignada,
    p.procesador AS pc_procesador,
    p.mother AS pc_mother,
    p.ram_gb AS pc_ram,
    p.almacenamiento_gb AS pc_disco,
    nb.marca AS nb_marca,
    nb.modelo AS nb_modelo,
    nb.procesador AS nb_procesador,
    nb.ram_gb AS nb_ram,
    imp.marca AS imp_marca,
    imp.modelo AS imp_modelo,
    mon.marca AS mon_marca,
    mon.modelo AS mon_modelo,
    mon.pulgadas AS mon_pulgadas,
    esc.marca AS esc_marca,
    esc.modelo AS esc_modelo
FROM insumos i
JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
JOIN remitos r ON rd.id_remito = r.id_remito
LEFT JOIN sedes s ON r.id_sede = s.id_sede
LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
LEFT JOIN areas a ON r.id_area = a.id_area
LEFT JOIN pcs_completas p ON i.id_insumo = p.id_insumo
LEFT JOIN notebooks nb ON i.id_insumo = nb.id_insumo
LEFT JOIN impresoras imp ON i.id_insumo = imp.id_insumo
LEFT JOIN monitores mon ON i.id_insumo = mon.id_insumo
LEFT JOIN escaneres esc ON i.id_insumo = esc.id_insumo
WHERE i.tipo_insumo != 'Varios'
  AND (r.numero_remito NOT LIKE '%_hist%' AND r.fecha_asignacion < '2026-08-10')
ORDER BY r.fecha_asignacion DESC, i.id_insumo DESC";

$stmtPre = $db->query($sqlPre);
$insumosPre = $stmtPre->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener Insumos cargados por RELEVAMIENTO / POSTERIORES (Remitos con _hist o >= 10/08)
$sqlPost = "
SELECT 
    i.id_insumo,
    i.tipo_insumo,
    i.numero_serie,
    i.id_fisico,
    i.id_patrimonio,
    i.estado,
    i.fecha_adquisicion,
    s.id_sede,
    s.nombre_sede,
    l.id_localidad,
    l.nombre_localidad,
    a.id_area,
    a.nombre_area,
    r.id_remito,
    r.numero_remito,
    r.fecha_asignacion,
    r.nombre_persona_asignada,
    r.apellido_persona_asignada,
    p.procesador AS pc_procesador,
    p.mother AS pc_mother,
    p.ram_gb AS pc_ram,
    p.almacenamiento_gb AS pc_disco,
    nb.marca AS nb_marca,
    nb.modelo AS nb_modelo,
    nb.procesador AS nb_procesador,
    nb.ram_gb AS nb_ram,
    imp.marca AS imp_marca,
    imp.modelo AS imp_modelo,
    mon.marca AS mon_marca,
    mon.modelo AS mon_modelo,
    mon.pulgadas AS mon_pulgadas,
    esc.marca AS esc_marca,
    esc.modelo AS esc_modelo
FROM insumos i
JOIN remitos_detalle rd ON i.id_insumo = rd.id_insumo
JOIN remitos r ON rd.id_remito = r.id_remito
LEFT JOIN sedes s ON r.id_sede = s.id_sede
LEFT JOIN localidades l ON s.id_localidad = l.id_localidad
LEFT JOIN areas a ON r.id_area = a.id_area
LEFT JOIN pcs_completas p ON i.id_insumo = p.id_insumo
LEFT JOIN notebooks nb ON i.id_insumo = nb.id_insumo
LEFT JOIN impresoras imp ON i.id_insumo = imp.id_insumo
LEFT JOIN monitores mon ON i.id_insumo = mon.id_insumo
LEFT JOIN escaneres esc ON i.id_insumo = esc.id_insumo
WHERE i.tipo_insumo != 'Varios'
  AND (r.numero_remito LIKE '%_hist%' OR r.fecha_asignacion >= '2026-08-10')
ORDER BY r.fecha_asignacion DESC, i.id_insumo DESC";

$stmtPost = $db->query($sqlPost);
$insumosPost = $stmtPost->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// MOTOR DE CRUCE Y DETECCIÓN DE COINCIDENCIAS
// -----------------------------------------------------------------------------

$coincidencias = [];
$totalCriticos = 0;
$totalAltos = 0;
$totalSospechosos = 0;
$localidadesAfectadas = [];

// Indexar POST por tipo_insumo y sede/localidad para búsqueda ultra rápida
$postIndex = [];
foreach ($insumosPost as $post) {
    $locKey = strtolower(trim($post['nombre_localidad'] ?? 'sin_loc'));
    $tipoKey = $post['tipo_insumo'];
    $postIndex[$tipoKey][$locKey][] = $post;
}

// Analizar cada insumo PRE contra los POST candidatos
foreach ($insumosPre as $pre) {
    $tipo = $pre['tipo_insumo'];
    $locKey = strtolower(trim($pre['nombre_localidad'] ?? 'sin_loc'));
    
    if (!isset($postIndex[$tipo][$locKey])) {
        continue;
    }
    
    $candidatosPost = $postIndex[$tipo][$locKey];
    $personaPre = normalizarPersona($pre['nombre_persona_asignada'], $pre['apellido_persona_asignada']);
    $snPreLimpio = limpiarCadena($pre['numero_serie']);
    $snPreEsValido = !esIdentificadorGenerico($pre['numero_serie']);
    $idFisicoPreLimpio = limpiarCadena($pre['id_fisico']);
    $idFisicoPreEsValido = !esIdentificadorGenerico($pre['id_fisico']);

    foreach ($candidatosPost as $post) {
        if ($pre['id_insumo'] === $post['id_insumo']) continue;

        $personaPost = normalizarPersona($post['nombre_persona_asignada'], $post['apellido_persona_asignada']);
        $snPostLimpio = limpiarCadena($post['numero_serie']);
        $snPostEsValido = !esIdentificadorGenerico($post['numero_serie']);
        $idFisicoPostLimpio = limpiarCadena($post['id_fisico']);
        $idFisicoPostEsValido = !esIdentificadorGenerico($post['id_fisico']);

        $score = 0;
        $motivos = [];
        $categoria = 'SOSPECHOSO'; // SOSPECHOSO, ALTO, CRITICO

        // 1. EVALUAR PERSONA / RESPONSABLE ASIGNADO
        $mismaPersona = false;
        $mismoApellido = false;
        if ($personaPre['apellido_limpio'] !== '' && $personaPost['apellido_limpio'] !== '') {
            if ($personaPre['apellido_limpio'] === $personaPost['apellido_limpio']) {
                $mismoApellido = true;
                if ($personaPre['nombre_completo_limpio'] === $personaPost['nombre_completo_limpio']) {
                    $mismaPersona = true;
                }
            } else {
                // Similitud de nombre completo
                similar_text($personaPre['nombre_completo_limpio'], $personaPost['nombre_completo_limpio'], $pct);
                if ($pct >= 85) {
                    $mismaPersona = true;
                }
            }
        }

        $mismaSede = ($pre['id_sede'] && $post['id_sede'] && $pre['id_sede'] === $post['id_sede']);
        $mismaArea = ($pre['id_area'] && $post['id_area'] && $pre['id_area'] === $post['id_area']);

        // 2. EVALUAR NÚMERO DE SERIE
        if ($snPreEsValido && $snPostEsValido) {
            if ($snPreLimpio === $snPostLimpio) {
                $score = 100;
                $categoria = 'CRITICO';
                $motivos[] = 'Número de Serie IDÉNTICO (' . htmlspecialchars($pre['numero_serie']) . ')';
            } else {
                // Posible error tipográfico (Levenshtein)
                $lenMax = max(strlen($snPreLimpio), strlen($snPostLimpio));
                if ($lenMax >= 6) {
                    $lev = levenshtein($snPreLimpio, $snPostLimpio);
                    if ($lev === 1) {
                        if ($mismaPersona || $mismoApellido || $mismaArea) {
                            $score = 98;
                            $categoria = 'CRITICO';
                            $motivos[] = "Error tipográfico en Serial (Misma persona/área): difieren en 1 caracter ('{$pre['numero_serie']}' vs '{$post['numero_serie']}')";
                        } else {
                            $score = 70;
                            $categoria = 'SOSPECHOSO';
                            $motivos[] = "Serial consecutivo en lote ('{$pre['numero_serie']}' vs '{$post['numero_serie']}')";
                        }
                    } elseif ($lev === 2 && ($mismaPersona || $mismoApellido || $mismaArea)) {
                        $score = 85;
                        $categoria = 'ALTO';
                        $motivos[] = "Seriales casi idénticos en misma oficina/persona (diferencia de 2 caracteres)";
                    }
                }
            }
        }

        // 3. EVALUAR ID FÍSICO
        if ($idFisicoPreEsValido && $idFisicoPostEsValido && $idFisicoPreLimpio === $idFisicoPostLimpio) {
            $score = max($score, 99);
            $categoria = 'CRITICO';
            $motivos[] = 'ID Físico IDÉNTICO (' . htmlspecialchars($pre['id_fisico']) . ')';
        }

        // Si no hubo coincidencia de serial, evaluar persona + hardware
        if ($score < 90) {
            $scoreHw = 0;
            $motivosHw = [];

            if ($mismaPersona) {
                $scoreHw += 40;
                $motivosHw[] = 'Misma Persona Asignada (' . htmlspecialchars($personaPre['original']) . ')';
            } elseif ($mismoApellido) {
                $scoreHw += 25;
                $motivosHw[] = 'Mismo Apellido (' . htmlspecialchars($personaPre['apellido']) . ')';
            }

            if ($mismaArea) {
                $scoreHw += 15;
                $motivosHw[] = 'Misma Área (' . htmlspecialchars($pre['nombre_area'] ?? '') . ')';
            }

            // Comparar hardware específico
            if ($tipo === 'PC Escritorio') {
                $procPre = limpiarCadena($pre['pc_procesador']);
                $procPost = limpiarCadena($post['pc_procesador']);
                if ($procPre !== '' && $procPre === $procPost) {
                    $scoreHw += 25;
                    $motivosHw[] = 'Mismo Procesador (' . htmlspecialchars($pre['pc_procesador']) . ')';
                }
                $mothPre = limpiarCadena($pre['pc_mother']);
                $mothPost = limpiarCadena($post['pc_mother']);
                if ($mothPre !== '' && $mothPre === $mothPost) {
                    $scoreHw += 20;
                    $motivosHw[] = 'Misma Motherboard (' . htmlspecialchars($pre['pc_mother']) . ')';
                }
                if ($pre['pc_ram'] && $post['pc_ram'] && $pre['pc_ram'] == $post['pc_ram']) {
                    $scoreHw += 10;
                    $motivosHw[] = "Misma RAM ({$pre['pc_ram']} GB)";
                }
            } elseif ($tipo === 'Notebook') {
                $marcaPre = limpiarCadena($pre['nb_marca']);
                $marcaPost = limpiarCadena($post['nb_marca']);
                if ($marcaPre !== '' && $marcaPre === $marcaPost) {
                    $scoreHw += 20;
                    $motivosHw[] = 'Misma Marca (' . htmlspecialchars($pre['nb_marca']) . ')';
                }
                $modPre = limpiarCadena($pre['nb_modelo']);
                $modPost = limpiarCadena($post['nb_modelo']);
                if ($modPre !== '' && $modPre === $modPost) {
                    $scoreHw += 25;
                    $motivosHw[] = 'Mismo Modelo (' . htmlspecialchars($pre['nb_modelo']) . ')';
                }
            } elseif ($tipo === 'Impresora') {
                $marcaPre = limpiarCadena($pre['imp_marca']);
                $marcaPost = limpiarCadena($post['imp_marca']);
                $modPre = limpiarCadena($pre['imp_modelo']);
                $modPost = limpiarCadena($post['imp_modelo']);
                if ($marcaPre !== '' && $marcaPre === $marcaPost) $scoreHw += 20;
                if ($modPre !== '' && $modPre === $modPost) {
                    $scoreHw += 30;
                    $motivosHw[] = 'Mismo Modelo (' . htmlspecialchars($pre['imp_marca'] . ' ' . $pre['imp_modelo']) . ')';
                }
            } elseif ($tipo === 'Monitor') {
                $marcaPre = limpiarCadena($monPre = $pre['mon_marca']);
                $marcaPost = limpiarCadena($monPost = $post['mon_marca']);
                $modPre = limpiarCadena($pre['mon_modelo']);
                $modPost = limpiarCadena($post['mon_modelo']);
                if ($marcaPre !== '' && $marcaPre === $marcaPost) $scoreHw += 15;
                if ($modPre !== '' && $modPre === $modPost) {
                    $scoreHw += 25;
                    $motivosHw[] = 'Mismo Modelo (' . htmlspecialchars($pre['mon_marca'] . ' ' . $pre['mon_modelo']) . ')';
                }
                if ($pre['mon_pulgadas'] && $post['mon_pulgadas'] && $pre['mon_pulgadas'] == $post['mon_pulgadas']) {
                    $scoreHw += 10;
                }
            }

            // Bono por serie vacía en uno de ellos (sospechoso de sustitución)
            if (($snPreEsValido && !$snPostEsValido) || (!$snPreEsValido && $snPostEsValido) || (!$snPreEsValido && !$snPostEsValido)) {
                if ($mismaPersona) {
                    $scoreHw += 10;
                    $motivosHw[] = 'Uno de los registros carece de número de serie';
                }
            }

            if ($scoreHw >= 65) {
                $score = min($scoreHw, 95);
                $categoria = ($score >= 80) ? 'ALTO' : 'SOSPECHOSO';
                $motivos = array_merge($motivos, $motivosHw);
            }
        }

        // Si califica como coincidencia significativa, registrar
        if ($score >= 65) {
            $loc = $pre['nombre_localidad'] ?: 'Desconocida';
            $localidadesAfectadas[$loc] = ($localidadesAfectadas[$loc] ?? 0) + 1;

            if ($categoria === 'CRITICO') $totalCriticos++;
            elseif ($categoria === 'ALTO') $totalAltos++;
            else $totalSospechosos++;

            $coincidencias[] = [
                'score' => $score,
                'categoria' => $categoria,
                'motivos' => $motivos,
                'localidad' => $loc,
                'sede' => $pre['nombre_sede'] ?: 'Central',
                'tipo_insumo' => $tipo,
                'pre' => $pre,
                'post' => $post
            ];
        }
    }
}

// Ordenar coincidencias por score descendente (más críticos primero)
usort($coincidencias, function($a, $b) {
    if ($a['score'] === $b['score']) {
        return strcmp($a['localidad'], $b['localidad']);
    }
    return $b['score'] <=> $a['score'];
});

// Helper de resumen de hardware
function formatearHardware(array $i): string {
    $t = $i['tipo_insumo'];
    if ($t === 'PC Escritorio') {
        $p = $i['pc_procesador'] ?: 'Sin micro';
        $m = $i['pc_mother'] ?: 'Sin mother';
        $r = $i['pc_ram'] ? "{$i['pc_ram']}GB RAM" : '';
        $d = $i['pc_disco'] ? "{$i['pc_disco']}GB Disco" : '';
        return trim("{$p} | {$m} | {$r} {$d}", " |");
    } elseif ($t === 'Notebook') {
        $m = trim(($i['nb_marca'] ?? '') . ' ' . ($i['nb_modelo'] ?? ''));
        $p = $i['nb_procesador'] ? "CPU: {$i['nb_procesador']}" : '';
        $r = $i['nb_ram'] ? "{$i['nb_ram']}GB RAM" : '';
        return trim("{$m} | {$p} | {$r}", " |") ?: 'Notebook';
    } elseif ($t === 'Impresora') {
        return trim(($i['imp_marca'] ?? '') . ' ' . ($i['imp_modelo'] ?? '')) ?: 'Impresora';
    } elseif ($t === 'Monitor') {
        $p = $i['mon_pulgadas'] ? "{$i['mon_pulgadas']}\"" : '';
        return trim(($i['mon_marca'] ?? '') . ' ' . ($i['mon_modelo'] ?? '') . " {$p}") ?: 'Monitor';
    } elseif ($t === 'Escaner') {
        return trim(($i['esc_marca'] ?? '') . ' ' . ($i['esc_modelo'] ?? '')) ?: 'Escáner';
    }
    return 'Insumo';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITIA - Diagnóstico de Duplicados (Pre-10/08 vs Relevamiento)</title>
    <link href="<?php echo app_base_url(); ?>/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/css/style.css" rel="stylesheet">
    <style>
        :root {
            --sitia-primary: #5a9367;
            --sitia-primary-hover: #487752;
            --sitia-bg: #f4f6f8;
            --card-border: #e2e8f0;
        }
        body {
            background-color: var(--sitia-bg);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #2d3748;
        }
        .header-banner {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: #fff;
            padding: 2.2rem 0;
            border-bottom: 4px solid var(--sitia-primary);
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .stat-card {
            border-radius: 10px;
            border: 1px solid var(--card-border);
            background: #fff;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }
        .stat-val {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }
        .card-duplicado {
            border: 1px solid var(--card-border);
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
        }
        .card-duplicado:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        }
        .col-pre {
            background-color: #f8fafc;
            border-right: 1px solid var(--card-border);
            padding: 1.2rem;
        }
        .col-post {
            background-color: #ffffff;
            padding: 1.2rem;
        }
        .col-middle {
            background-color: #ffffff;
            border-right: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem 0.5rem;
            text-align: center;
        }
        .badge-critico {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
            font-weight: 700;
        }
        .badge-alto {
            background-color: #ffedd5;
            color: #9a3412;
            border: 1px solid #fb923c;
            font-weight: 700;
        }
        .badge-sospechoso {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #facc15;
            font-weight: 600;
        }
        .badge-tag {
            font-size: 0.76rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            display: inline-block;
            margin: 2px;
        }
        .code-box {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.84rem;
            background-color: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            color: #1e293b;
        }
        .sn-highlight {
            background-color: #fef08a;
            color: #854d0e;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #facc15;
        }
        .filter-panel {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
    </style>
</head>
<body>

<div class="header-banner">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1"><i class="fas fa-clone text-warning me-2"></i>Diagnóstico de Duplicados en Inventario</h2>
                <p class="mb-0 text-light opacity-75">
                    Cruce sistemático de <strong>Asignaciones Oficiales Previas (< 10/08)</strong> vs <strong>Campaña de Relevamiento Masivo (Post-10/08 / <code>_hist</code>)</strong>
                </p>
            </div>
            <div>
                <a href="<?php echo app_base_url(); ?>/index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home me-1"></i>Inicio SITIA
                </a>
                <a href="<?php echo app_base_url(); ?>/diagnostico_clasificacion.php" class="btn btn-outline-info">
                    <i class="fas fa-microchip me-1"></i>Catálogo Hardware
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 mb-5">

    <!-- METRICAS SUPERIORES -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Conflictos Críticos</div>
                        <div class="stat-val text-danger mt-1"><?php echo $totalCriticos; ?></div>
                        <div class="small text-muted mt-1">Serial o ID Físico Idéntico / Typo</div>
                    </div>
                    <div class="text-danger opacity-25 fa-2x"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Certeza Alta (~90%)</div>
                        <div class="stat-val text-warning mt-1"><?php echo $totalAltos; ?></div>
                        <div class="small text-muted mt-1">Misma persona y mismo hardware</div>
                    </div>
                    <div class="text-warning opacity-25 fa-2x"><i class="fas fa-user-check fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Sospechosos Mismo Puesto</div>
                        <div class="stat-val text-info mt-1"><?php echo $totalSospechosos; ?></div>
                        <div class="small text-muted mt-1">Misma oficina con serie vacía</div>
                    </div>
                    <div class="text-info opacity-25 fa-2x"><i class="fas fa-desktop fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Coincidencias</div>
                        <div class="stat-val text-success mt-1"><?php echo count($coincidencias); ?></div>
                        <div class="small text-muted mt-1">En <?php echo count($localidadesAfectadas); ?> localidades auditadas</div>
                    </div>
                    <div class="text-success opacity-25 fa-2x"><i class="fas fa-layer-group fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL DE FILTROS EN TIEMPO REAL -->
    <div class="filter-panel">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label form-label-sm fw-bold mb-1"><i class="fas fa-search me-1"></i>Buscar en texto</label>
                <input type="text" id="filtroTexto" class="form-control form-control-sm" placeholder="Buscar por persona, número de serie, modelo, remito...">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm fw-bold mb-1"><i class="fas fa-map-marker-alt me-1"></i>Localidad</label>
                <select id="filtroLocalidad" class="form-select form-select-sm">
                    <option value="">Todas las localidades (<?php echo count($localidadesAfectadas); ?>)</option>
                    <?php 
                    ksort($localidadesAfectadas);
                    foreach ($localidadesAfectadas as $loc => $cant): 
                    ?>
                        <option value="<?php echo htmlspecialchars(strtolower($loc)); ?>"><?php echo htmlspecialchars($loc); ?> (<?php echo $cant; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm fw-bold mb-1"><i class="fas fa-filter me-1"></i>Nivel de Certeza</label>
                <select id="filtroNivel" class="form-select form-select-sm">
                    <option value="">Todos los niveles</option>
                    <option value="CRITICO">🔴 Conflicto Crítico (Serial / ID repetido)</option>
                    <option value="ALTO">🟠 Certeza Alta (Misma persona y hardware)</option>
                    <option value="SOSPECHOSO">🟡 Sospechosos (Mismo puesto / serie vacía)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm fw-bold mb-1"><i class="fas fa-boxes me-1"></i>Tipo de Insumo</label>
                <select id="filtroTipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    <option value="PC Escritorio">PC Escritorio</option>
                    <option value="Notebook">Notebook</option>
                    <option value="Impresora">Impresora</option>
                    <option value="Monitor">Monitor</option>
                    <option value="Escaner">Escáner</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
            <small class="text-muted">
                Mostrando <strong id="contadorVisible"><?php echo count($coincidencias); ?></strong> de <?php echo count($coincidencias); ?> casos detectados
            </small>
            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" id="btnLimpiarFiltros">
                <i class="fas fa-undo me-1"></i>Restablecer filtros
            </button>
        </div>
    </div>

    <!-- LISTADO COMPARATIVO LADO A LADO -->
    <div id="contenedorCoincidencias">
        <?php if (empty($coincidencias)): ?>
            <div class="alert alert-success text-center py-5 shadow-sm">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>¡Excelente! No se detectaron cruces de duplicados</h5>
                <p class="text-muted mb-0">No hay solapamientos entre las asignaciones previas al 10/08 y los relevamientos masivos.</p>
            </div>
        <?php else: ?>
            <?php foreach ($coincidencias as $idx => $item): 
                $pre = $item['pre'];
                $post = $item['post'];
                $cat = $item['categoria'];
                $badgeClass = ($cat === 'CRITICO') ? 'badge-critico' : (($cat === 'ALTO') ? 'badge-alto' : 'badge-sospechoso');
                $textoBusqueda = strtolower(
                    $item['localidad'] . ' ' . $item['sede'] . ' ' . $item['tipo_insumo'] . ' ' .
                    $pre['persona_completa'] . ' ' . $post['persona_completa'] . ' ' .
                    ($pre['numero_serie'] ?? '') . ' ' . ($post['numero_serie'] ?? '') . ' ' .
                    ($pre['id_fisico'] ?? '') . ' ' . ($post['id_fisico'] ?? '') . ' ' .
                    $pre['numero_remito'] . ' ' . $post['numero_remito'] . ' ' .
                    formatearHardware($pre) . ' ' . formatearHardware($post)
                );
            ?>
            <div class="card-duplicado item-coincidencia" 
                 data-categoria="<?php echo $cat; ?>" 
                 data-localidad="<?php echo htmlspecialchars(strtolower($item['localidad'])); ?>"
                 data-tipo="<?php echo htmlspecialchars($item['tipo_insumo']); ?>"
                 data-texto="<?php echo htmlspecialchars($textoBusqueda); ?>">
                
                <!-- HEADER DE LA TARJETA -->
                <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?php echo htmlspecialchars($item['localidad']); ?></span>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($item['sede']); ?></span>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($item['tipo_insumo']); ?></span>
                        <?php if (!empty($pre['nombre_area'])): ?>
                            <small class="text-muted"><i class="fas fa-sitemap me-1"></i><?php echo htmlspecialchars($pre['nombre_area']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?php echo $badgeClass; ?> px-2 py-1">
                            <i class="fas fa-shield-alt me-1"></i><?php echo $item['score']; ?>% Coincidencia (<?php echo $cat; ?>)
                        </span>
                        <span class="text-muted small">#<?php echo ($idx + 1); ?></span>
                    </div>
                </div>

                <!-- CUERPO LADO A LADO -->
                <div class="row g-0">
                    
                    <!-- COLUMNA IZQUIERDA: ASIGNACIÓN PREVIA (< 10/08) -->
                    <div class="col-md-5 col-pre">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary text-white mb-1"><i class="fas fa-history me-1"></i>Asignación Oficial Previa</span>
                                <h6 class="mb-0 fw-bold text-primary">Insumo #<?php echo $pre['id_insumo']; ?></h6>
                            </div>
                            <span class="badge bg-<?php echo ($pre['estado'] === 'Asignado') ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($pre['estado']); ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Responsable Asignado:</small>
                            <strong class="fs-6"><i class="fas fa-user text-muted me-1"></i><?php echo htmlspecialchars($pre['nombre_persona_asignada'] . ' ' . $pre['apellido_persona_asignada']) ?: 'No registrado'; ?></strong>
                        </div>

                        <div class="row g-2 mb-2 small">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Remito Oficial:</span>
                                <span class="code-box"><i class="fas fa-file-invoice me-1"></i><?php echo htmlspecialchars($pre['numero_remito']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Fecha Asignación:</span>
                                <span><i class="far fa-calendar-alt text-muted me-1"></i><?php echo date('d/m/Y', strtotime($pre['fecha_asignacion'])); ?></span>
                            </div>
                        </div>

                        <div class="mb-2 small">
                            <span class="text-muted d-block">Número de Serie:</span>
                            <?php if (!empty($pre['numero_serie']) && !esIdentificadorGenerico($pre['numero_serie'])): ?>
                                <span class="sn-highlight"><i class="fas fa-barcode me-1"></i><?php echo htmlspecialchars($pre['numero_serie']); ?></span>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Sin número de serie cargado</span>
                            <?php endif; ?>
                            <?php if (!empty($pre['id_fisico']) && !esIdentificadorGenerico($pre['id_fisico'])): ?>
                                <span class="code-box ms-1"><i class="fas fa-tag me-1"></i>ID Físico: <?php echo htmlspecialchars($pre['id_fisico']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="p-2 bg-white rounded border small">
                            <span class="text-muted d-block fw-bold mb-1"><i class="fas fa-cogs me-1"></i>Especificaciones Registradas:</span>
                            <div><?php echo htmlspecialchars(formatearHardware($pre)); ?></div>
                        </div>

                        <div class="mt-2 text-end">
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/ver.php?id=<?php echo $pre['id_insumo']; ?>" target="_blank" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver Ficha #<?php echo $pre['id_insumo']; ?>
                            </a>
                        </div>
                    </div>

                    <!-- COLUMNA CENTRAL: DIAGNÓSTICO DEL CRUCE -->
                    <div class="col-md-2 col-middle">
                        <div class="mb-2">
                            <i class="fas fa-exchange-alt fa-2x text-muted opacity-50"></i>
                        </div>
                        <div class="fw-bold mb-2 small text-uppercase text-secondary">
                            Criterios Coincidentes:
                        </div>
                        <div class="w-100 px-2">
                            <?php foreach ($item['motivos'] as $motivo): ?>
                                <span class="badge bg-light text-dark border badge-tag w-100 text-start mb-1 text-wrap">
                                    <i class="fas fa-check-circle text-success me-1"></i><?php echo htmlspecialchars($motivo); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: RELEVAMIENTO (POST-10/08 / _HIST) -->
                    <div class="col-md-5 col-post">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-success text-white mb-1"><i class="fas fa-clipboard-check me-1"></i>Carga Relevamiento (Post-10/08)</span>
                                <h6 class="mb-0 fw-bold text-success">Insumo #<?php echo $post['id_insumo']; ?></h6>
                            </div>
                            <span class="badge bg-<?php echo ($post['estado'] === 'Asignado') ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($post['estado']); ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Responsable en Relevamiento:</small>
                            <strong class="fs-6"><i class="fas fa-user text-muted me-1"></i><?php echo htmlspecialchars($post['nombre_persona_asignada'] . ' ' . $post['apellido_persona_asignada']) ?: 'No registrado'; ?></strong>
                        </div>

                        <div class="row g-2 mb-2 small">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Remito Relevamiento:</span>
                                <span class="code-box text-success fw-bold"><i class="fas fa-file-alt me-1"></i><?php echo htmlspecialchars($post['numero_remito']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Fecha Relevamiento:</span>
                                <span><i class="far fa-calendar-alt text-muted me-1"></i><?php echo date('d/m/Y', strtotime($post['fecha_asignacion'])); ?></span>
                            </div>
                        </div>

                        <div class="mb-2 small">
                            <span class="text-muted d-block">Número de Serie:</span>
                            <?php if (!empty($post['numero_serie']) && !esIdentificadorGenerico($post['numero_serie'])): ?>
                                <span class="sn-highlight"><i class="fas fa-barcode me-1"></i><?php echo htmlspecialchars($post['numero_serie']); ?></span>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Sin número de serie cargado</span>
                            <?php endif; ?>
                            <?php if (!empty($post['id_fisico']) && !esIdentificadorGenerico($post['id_fisico'])): ?>
                                <span class="code-box ms-1"><i class="fas fa-tag me-1"></i>ID Físico: <?php echo htmlspecialchars($post['id_fisico']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="p-2 bg-light rounded border small">
                            <span class="text-muted d-block fw-bold mb-1"><i class="fas fa-cogs me-1"></i>Especificaciones Relevadas:</span>
                            <div><?php echo htmlspecialchars(formatearHardware($post)); ?></div>
                        </div>

                        <div class="mt-2 text-end">
                            <a href="<?php echo app_base_url(); ?>/pages/insumos/ver.php?id=<?php echo $post['id_insumo']; ?>" target="_blank" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver Ficha #<?php echo $post['id_insumo']; ?>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="<?php echo app_base_url(); ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputTexto = document.getElementById('filtroTexto');
    const selectLocalidad = document.getElementById('filtroLocalidad');
    const selectNivel = document.getElementById('filtroNivel');
    const selectTipo = document.getElementById('filtroTipo');
    const contador = document.getElementById('contadorVisible');
    const items = document.querySelectorAll('.item-coincidencia');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');

    function filtrar() {
        const texto = inputTexto.value.toLowerCase().trim();
        const loc = selectLocalidad.value;
        const nivel = selectNivel.value;
        const tipo = selectTipo.value;

        let visibleCount = 0;

        items.forEach(el => {
            const elTexto = el.getAttribute('data-texto');
            const elLoc = el.getAttribute('data-localidad');
            const elNivel = el.getAttribute('data-categoria');
            const elTipo = el.getAttribute('data-tipo');

            const matchTexto = !texto || elTexto.includes(texto);
            const matchLoc = !loc || elLoc === loc;
            const matchNivel = !nivel || elNivel === nivel;
            const matchTipo = !tipo || elTipo === tipo;

            if (matchTexto && matchLoc && matchNivel && matchTipo) {
                el.style.display = '';
                visibleCount++;
            } else {
                el.style.display = 'none';
            }
        });

        if (contador) contador.textContent = visibleCount;
    }

    inputTexto.addEventListener('input', filtrar);
    selectLocalidad.addEventListener('change', filtrar);
    selectNivel.addEventListener('change', filtrar);
    selectTipo.addEventListener('change', filtrar);

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            inputTexto.value = '';
            selectLocalidad.value = '';
            selectNivel.value = '';
            selectTipo.value = '';
            filtrar();
        });
    }
});
</script>

</body>
</html>
