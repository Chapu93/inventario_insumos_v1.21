<?php
/**
 * Interfaz de Visualización y Diagnóstico de Normalización
 * Motherboards y Procesadores (PCs y Notebooks) con Compatibilidad de Memoria RAM
 */
require_once 'includes/config.php';

$db = conectarDB();

// Mapeo oficial de Motherboards con Tipo de RAM Compatible
function normalizarMotherboard($raw) {
    $t = trim($raw);
    if ($t === '' || $t === 'No disponible') {
        return ['marca' => 'No disponible', 'modelo' => 'No disponible', 'resultado' => 'No disponible', 'ram' => 'N/A', 'logica' => 'Sin información en relevamiento original'];
    }

    // [Marca, Modelo, Tipo RAM, Lógica]
    $m = [
        'Asus Prime B450M-A II'     => ['Asus', 'Prime B450M-A II', 'DDR4', 'Modelo oficial Socket AM4 (4 slots DDR4)'],
        'Asustek Prime B450M-A II'  => ['Asus', 'Prime B450M-A II', 'DDR4', 'Unificación ASUSTeK a Asus (Socket AM4)'],
        'ASUS H81M-C'               => ['Asus', 'H81M-C', 'DDR3', 'Normalización de mayúsculas (Socket LGA1150)'],
        'Asustek H81M-C'            => ['Asus', 'H81M-C', 'DDR3', 'Unificación ASUSTeK a Asus (Socket LGA1150)'],
        'Asust H81M-C'              => ['Asus', 'H81M-C', 'DDR3', 'Corrección tipeo Asust -> Asus (Socket LGA1150)'],
        'Asus H110M-K'              => ['Asus', 'H110M-K', 'DDR4', 'Modelo oficial Socket LGA1151 (2 slots DDR4)'],
        'Asus Prime A520M-A II'     => ['Asus', 'Prime A520M-A II', 'DDR4', 'Modelo oficial Socket AM4 (4 slots DDR4)'],
        'Asustek A520M-A'           => ['Asus', 'Prime A520M-A II', 'DDR4', 'Unificación ASUSTeK y modelo completo (Socket AM4)'],
        'Asus Prime B340M-A'        => ['Asus', 'Prime B450M-A II', 'DDR4', 'Corrección de error de tipeo B340M -> B450M (DDR4)'],
        'Asus M4N68T-M-LE-V2'       => ['Asus', 'M4N68T-M LE V2', 'DDR3', 'Limpieza de guiones excesivos (Socket AM3 DDR3)'],
        'Asus P5KPL-AMSE'           => ['Asus', 'P5KPL-AM SE', 'DDR2', 'Separación de sufijo SE (Socket LGA775 Chipset G31 DDR2)'],
        'Asus 2473h'                => ['Asus', '2A73h', 'DDR3', 'Corrección tipeo OEM HP/Pegatron (Socket FM1 DDR3)'],
        'Asus 2A73H'                => ['Asus', '2A73h', 'DDR3', 'Normalización de mayúsculas (Socket FM1 DDR3)'],
        'Asus All Series'           => ['Asus', 'Desconocido (All Series)', 'Consultar', 'Dato genérico reportado por BIOS'],
        'Asus'                      => ['Asus', 'Desconocido', 'Consultar', 'Solo se registró la marca'],

        'MSI H110M PRO-VH PLUS'     => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Modelo oficial Socket LGA1151 (2 slots DDR4)'],
        'MSI H110M Pro VH-Plus'     => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Estandarización mayúsculas y guiones (DDR4)'],
        'MSI H110M-PRO-VH PLUS'     => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Estandarización de guiones (DDR4)'],
        'MSI H110M-PRO-VH'          => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Unificación a versión PLUS instalada (DDR4)'],
        'MSI MS-7A15'               => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Traducción de código PCB MS-7A15 = H110M PRO-VH PLUS (DDR4)'],
        'MSI-7A154'                 => ['MSI', 'H110M PRO-VH PLUS', 'DDR4', 'Corrección de tipeo en código PCB (DDR4)'],
        'MSI A68HM-E33 v2'          => ['MSI', 'A68HM-E33 V2', 'DDR3', 'Socket FM2+ Chipset A68H (2 slots DDR3)'],
        'MSI A320M-A Pro'           => ['MSI', 'A320M-A PRO', 'DDR4', 'Socket AM4 Chipset A320 (2 slots DDR4)'],

        'Gigabyte A320M-H CF'       => ['Gigabyte', 'GA-A320M-H', 'DDR4', 'Eliminación sufijo BIOS CF (Socket AM4 DDR4)'],
        'Gigabyte A320M-H'          => ['Gigabyte', 'GA-A320M-H', 'DDR4', 'Prefijo oficial GA- (Socket AM4 DDR4)'],
        'Gigabyte A320M-H-CF'       => ['Gigabyte', 'GA-A320M-H', 'DDR4', 'Eliminación sufijo CF y guiones (DDR4)'],
        'Gigabyte GA-A320M-H'       => ['Gigabyte', 'GA-A320M-H', 'DDR4', 'Modelo oficial comercial (2 slots DDR4)'],
        'Gigabyte A320H'            => ['Gigabyte', 'GA-A320M-H', 'DDR4', 'Corrección modelo incompleto (DDR4)'],
        'Gigabyte A520M K V2'       => ['Gigabyte', 'A520M K V2', 'DDR4', 'Socket AM4 Chipset A520 (2 slots DDR4)'],
        'Gigabyte B85M-D3H-A'       => ['Gigabyte', 'GA-B85M-D3H-A', 'DDR3', 'Socket LGA1150 Chipset B85 (4 slots DDR3)'],
        'Gigabyte B85M-B3H-A'       => ['Gigabyte', 'GA-B85M-D3H-A', 'DDR3', 'Corrección error de tipeo B3H -> D3H (DDR3)'],
        'Gigabyte F2A68HM-H'        => ['Gigabyte', 'GA-F2A68HM-H', 'DDR3', 'Socket FM2+ Chipset A68H (2 slots DDR3)'],
        'Gigabyte H110M-H'          => ['Gigabyte', 'GA-H110M-H', 'DDR4', 'Socket LGA1151 Chipset H110 (2 slots DDR4)'],
        'Gigabyte H410M S2H V3'     => ['Gigabyte', 'H410M S2H V3', 'DDR4', 'Socket LGA1200 Chipset H470/H410 (2 slots DDR4)'],
        'Gigabyte H410M H V3'       => ['Gigabyte', 'H410M H V3', 'DDR4', 'Socket LGA1200 (2 slots DDR4)'],
        'Gigabyte G-A78LMTS2'       => ['Gigabyte', 'GA-78LMT-S2', 'DDR3', 'Socket AM3+ Chipset 760G (2 slots DDR3)'],
        'Gigabyte G41MT-S2PT'       => ['Gigabyte', 'GA-G41MT-S2PT', 'DDR3', 'Socket LGA775 Chipset G41 (versión MT usa DDR3)'],
        'Gigabyte GA-G41M-ES2L'     => ['Gigabyte', 'GA-G41M-ES2L', 'DDR2', 'Socket LGA775 Chipset G41 (versión ES2L usa DDR2)'],

        'MB Biostar AM4 A520MHP DD' => ['Biostar', 'A520MHP', 'DDR4', 'Limpieza prefijo MB y sufijos (Socket AM4 2 slots DDR4)'],
        'Biostar A68N-2100'         => ['Biostar', 'A68N-2100', 'DDR3', 'Placa integrada CPU AMD E1 (2 slots DDR3/DDR3L)'],
        'Biostar N6853+'            => ['Biostar', 'N68S3+', 'DDR3', 'Corrección lectura OCR 5->S (Socket AM3 2 slots DDR3)'],
        'Biostar N6853B'            => ['Biostar', 'N68S3B', 'DDR3', 'Corrección lectura OCR 5->S (Socket AM3 2 slots DDR3)'],

        'Asrock A320M-HDV'          => ['ASRock', 'A320M-HDV', 'DDR4', 'Socket AM4 (2 slots DDR4)'],
        'AsRock FM2A68M-DG3+'       => ['ASRock', 'FM2A68M-DG3+', 'DDR3', 'Socket FM2+ (2 slots DDR3)'],
        'AsRock FM2A88M-HD+'        => ['ASRock', 'FM2A88M-HD+', 'DDR3', 'Socket FM2+ Chipset A88X (2 slots DDR3)'],
        'AsRock FM2488M-HD+'        => ['ASRock', 'FM2A88M-HD+', 'DDR3', 'Corrección de tipeo FM2488 -> FM2A88 (DDR3)'],
        'Asrock N68-S'              => ['ASRock', 'N68-S', 'DDR2', 'Socket AM2/AM2+/AM3 (N68-S original usa DDR2)'],
        'Asrock N68-VS3 FX'         => ['ASRock', 'N68-VS3 FX', 'DDR3', 'Socket AM3+ (serie VS3 usa DDR3)'],
        'Asrock G41-VS3'            => ['ASRock', 'G41-VS3', 'DDR3', 'Socket LGA775 Chipset G41 (DDR3)'],
        'Asrock G41C-VS'            => ['ASRock', 'G41C-VS', 'Combo DDR3/DDR2', 'Socket LGA775 Combo (2 slots DDR2 + 2 slots DDR3)'],
        'Asrock H110M-HDV R3.0'     => ['ASRock', 'H110M-HDV R3.0', 'DDR4', 'Socket LGA1151 Chipset H110 (2 slots DDR4)'],
        'Asrock H81M-VG4 R2.0'      => ['ASRock', 'H81M-VG4 R2.0', 'DDR3', 'Socket LGA1150 Chipset H81 (2 slots DDR3)'],
        'Asrock H81M-VGH'           => ['ASRock', 'H81M-VGH', 'DDR3', 'Socket LGA1150 Chipset H81 (2 slots DDR3)'],
        'Asrock M61M-HVS'           => ['ASRock', 'M61M-HVS', 'DDR3', 'Socket AM3 (2 slots DDR3)'],
        'D1800B-ITX'                => ['ASRock', 'D1800B-ITX', 'DDR3L (SO-DIMM)', 'SoC Celeron J1800 (2 slots SO-DIMM DDR3/DDR3L)'],
        'Wolfdale 1333-D667'        => ['ASRock', 'Wolfdale 1333-D667', 'DDR2', 'Socket LGA775 Chipset 945GC (2 slots DDR2)'],

        'Intel DH61H0'              => ['Intel', 'DH61HO', 'DDR3', 'Corrección cero por letra O (Socket LGA1155 2 slots DDR3)'],
        'Intel DH61HO'              => ['Intel', 'DH61HO', 'DDR3', 'Socket LGA1155 Chipset H61 (2 slots DDR3)'],
        'Intel DH61M0'              => ['Intel', 'DH61HO', 'DDR3', 'Corrección de tipeo M->H (DH61MO no existe, es DH61HO)'],
        'Intel D946GZIS'            => ['Intel', 'D946GZIS', 'DDR2', 'Socket LGA775 Chipset 946GZ (2 slots DDR2)'],

        'HP 829D'                   => ['HP', 'ProDesk 600 G3 (829D)', 'DDR4', 'Equipo ProDesk 600 G3 MT (4 slots DDR4)'],
        'Foxconn 2A8C'              => ['Foxconn', '2A8C', 'DDR3', 'Placa OEM HP Pegatron/Foxconn (Socket LGA775 DDR3)'],
    ];

    if (isset($m[$t])) {
        return [
            'marca' => $m[$t][0],
            'modelo' => $m[$t][1],
            'resultado' => $m[$t][0] . ' ' . $m[$t][1],
            'ram' => $m[$t][2],
            'logica' => $m[$t][3]
        ];
    }

    return [
        'marca' => 'Desconocida',
        'modelo' => $t,
        'resultado' => $t,
        'ram' => 'Consultar',
        'logica' => 'Sin regla específica (mantiene valor original)'
    ];
}

// Mapeo oficial de Procesadores con Tipo de RAM Compatible
function normalizarProcesador($raw) {
    $t = trim($raw);
    if ($t === '' || $t === 'No disponible') {
        return ['marca' => 'No disponible', 'modelo' => 'No disponible', 'resultado' => 'No disponible', 'ram' => 'N/A', 'logica' => 'Sin información'];
    }

    // [Marca, Modelo, Tipo RAM, Lógica]
    $p = [
        // Intel PCs
        'Intel Core i3-7100'            => ['Intel', 'Core i3-7100', 'DDR4 / DDR3L', 'Kaby Lake 7ma Gen (DDR4-2400 / DDR3L-1600)'],
        'Intel I3-7100'                 => ['Intel', 'Core i3-7100', 'DDR4 / DDR3L', 'Inclusión de prefijo Core (Kaby Lake)'],
        'Intel Core I3-6100'            => ['Intel', 'Core i3-6100', 'DDR4 / DDR3L', 'Skylake 6ta Gen (DDR4-2133 / DDR3L-1600)'],
        'Intel Core I3-4160'            => ['Intel', 'Core i3-4160', 'DDR3 / DDR3L', 'Haswell 4ta Gen (DDR3-1600)'],
        'Intel Core i3-4170'            => ['Intel', 'Core i3-4170', 'DDR3 / DDR3L', 'Haswell 4ta Gen (DDR3-1600)'],
        'Intel Core I3-3220'            => ['Intel', 'Core i3-3220', 'DDR3', 'Ivy Bridge 3ra Gen (DDR3-1333/1600)'],
        'Intel Core I3-10100'           => ['Intel', 'Core i3-10100', 'DDR4', 'Comet Lake 10ma Gen (DDR4-2666)'],
        'Intel Core i3-3110M'           => ['Intel', 'Core i3-3110M', 'DDR3 / DDR3L', 'Ivy Bridge móvil (DDR3-1600)'],
        'Intel Core I3-470'             => ['Intel', 'Core i3-540', 'DDR3', 'Clarkdale 1ra Gen LGA1156 (DDR3-1333)'],
        'Intel Pentium G3250'           => ['Intel', 'Pentium G3250', 'DDR3 / DDR3L', 'Haswell 4ta Gen (DDR3-1333)'],
        'Intel Pentium G630'            => ['Intel', 'Pentium G630', 'DDR3', 'Sandy Bridge 2da Gen (DDR3-1066)'],
        'Intel Pentium Dual-Core E2160' => ['Intel', 'Pentium E2160', 'DDR2', 'Conroe LGA775 (DDR2-667/800)'],
        'Intel Pentium E2160'           => ['Intel', 'Pentium E2160', 'DDR2', 'Conroe LGA775 (DDR2-667/800)'],
        'Intel Pentium E5400'           => ['Intel', 'Pentium E5400', 'DDR2 / DDR3', 'Wolfdale LGA775 (DDR2 u opcional DDR3 según placa)'],
        'Intel Pentium E5500'           => ['Intel', 'Pentium E5500', 'DDR2 / DDR3', 'Wolfdale LGA775 (DDR2 u opcional DDR3 según placa)'],
        'Intel Pentium E5500 Dual Core' => ['Intel', 'Pentium E5500', 'DDR2 / DDR3', 'Eliminación sufijo Dual Core (DDR2/DDR3)'],
        'Pentium Dual-Core E5500'       => ['Intel', 'Pentium E5500', 'DDR2 / DDR3', 'Inclusión marca Intel (DDR2/DDR3)'],
        'Intel Pentium ES200'           => ['Intel', 'Pentium E5200', 'DDR2 / DDR3', 'Corrección error OCR ES200 -> E5200 (DDR2/DDR3)'],
        'Intel Pentium Dual Core'       => ['Intel', 'Pentium Dual-Core', 'DDR2', 'Genérico serie LGA775 (DDR2)'],
        'Intel Pentium 4'               => ['Intel', 'Pentium 4', 'DDR / DDR2', 'Serie clásica NetBurst (DDR/DDR2)'],
        'Intel Celeron E3400'           => ['Intel', 'Celeron E3400', 'DDR2 / DDR3', 'Wolfdale LGA775 (DDR2/DDR3)'],
        'Intel Celeron J1800'           => ['Intel', 'Celeron J1800', 'DDR3L', 'Bay Trail SoC integrado (DDR3L-1333 SO-DIMM)'],

        // AMD PCs
        'AMD Ryzen 3 3200 G'            => ['AMD', 'Ryzen 3 3200G', 'DDR4', 'Zen+ AM4 (DDR4-2933)'],
        'AMD Ryzen 3 3200G'             => ['AMD', 'Ryzen 3 3200G', 'DDR4', 'Zen+ AM4 (DDR4-2933)'],
        'AMD Ryzen 5 3400 G'            => ['AMD', 'Ryzen 5 3400G', 'DDR4', 'Zen+ AM4 (DDR4-2933)'],
        'AMD Ryzen 5 3400G'             => ['AMD', 'Ryzen 5 3400G', 'DDR4', 'Zen+ AM4 (DDR4-2933)'],
        'AMD Ryzen 5 2400 G'            => ['AMD', 'Ryzen 5 2400G', 'DDR4', 'Zen 1ra Gen AM4 (DDR4-2933)'],
        'AMD A8 9600 R7'                => ['AMD', 'A8-9600', 'DDR4', 'Bristol Ridge AM4 (DDR4-2400)'],
        'AMD A8-9600 R7'                => ['AMD', 'A8-9600', 'DDR4', 'Bristol Ridge AM4 (DDR4-2400)'],
        'AMD A8-9600 Radeon R7'         => ['AMD', 'A8-9600', 'DDR4', 'Bristol Ridge AM4 (DDR4-2400)'],
        'AMD A8-7650 K'                 => ['AMD', 'A8-7650K', 'DDR3', 'Kaveri FM2+ (DDR3-2133)'],
        'AMD A10 9700 R7'               => ['AMD', 'A10-9700', 'DDR4', 'Bristol Ridge AM4 (DDR4-2400)'],
        'AMD A4 4000 Radeon HD 7480D'   => ['AMD', 'A4-4000', 'DDR3', 'Richland FM2 (DDR3-1333)'],
        'AMD A4-4000 Radeon HD 7480D'   => ['AMD', 'A4-4000', 'DDR3', 'Richland FM2 (DDR3-1333)'],
        'AMD A4 4000'                   => ['AMD', 'A4-4000', 'DDR3', 'Richland FM2 (DDR3-1333)'],
        'AMD A4 4250'                   => ['AMD', 'A4-4250', 'DDR3', 'Kabini FM2 (DDR3-1600)'],
        'AMD Athlon II x2 250'          => ['AMD', 'Athlon II X2 250', 'DDR3 / DDR2', 'Regor AM3 (controlador dual DDR2/DDR3)'],
        'AMD Athlon x2 250'             => ['AMD', 'Athlon II X2 250', 'DDR3 / DDR2', 'Regor AM3 (controlador dual DDR2/DDR3)'],
        'AMD Athlon x2 Dual Core'       => ['AMD', 'Athlon 64 X2 Dual-Core', 'DDR2', 'Brisbane AM2 (DDR2-800)'],
        'AMD Athon II x2 250'           => ['AMD', 'Athlon II X2 250', 'DDR3 / DDR2', 'Corrección ortográfica (DDR2/DDR3)'],
        'AMD Semprom 140'               => ['AMD', 'Sempron 140', 'DDR3 / DDR2', 'Sargas AM3 (controlador dual DDR2/DDR3)'],
        'AMD FX-4130'                   => ['AMD', 'FX-4130', 'DDR3', 'Zambezi AM3+ (DDR3-1866)'],
        'AMD E1-2100'                   => ['AMD', 'E1-2100', 'DDR3L', 'Kabini SoC integrado (DDR3L-1333)'],

        // Notebooks
        'Intel Celeron N3450'           => ['Intel', 'Celeron N3450', 'DDR3L / LPDDR4', 'Apollo Lake (DDR3L-1866 / LPDDR4-2400)'],
        'Intel Celeron CPU N3450'       => ['Intel', 'Celeron N3450', 'DDR3L / LPDDR4', 'Apollo Lake (DDR3L / LPDDR4)'],
        'Celeron N3450'                 => ['Intel', 'Celeron N3450', 'DDR3L / LPDDR4', 'Apollo Lake (DDR3L / LPDDR4)'],
        'Intel Celeron N2840'           => ['Intel', 'Celeron N2840', 'DDR3L', 'Bay Trail-M (DDR3L-1333)'],
        'AMD Ryzen 5 7430U'             => ['AMD', 'Ryzen 5 7430U', 'DDR4 / LPDDR4X', 'Barcelo-R Zen 3 (DDR4-3200 / LPDDR4X)'],
        'Ryzen 5 7430U'                 => ['AMD', 'Ryzen 5 7430U', 'DDR4 / LPDDR4X', 'Barcelo-R Zen 3 (DDR4-3200)'],
        'Intel Core I3-6006U'           => ['Intel', 'Core i3-6006U', 'DDR4 / DDR3L', 'Skylake-U (DDR4-2133 / DDR3L-1600)'],
        'Intel I3-6006U'                => ['Intel', 'Core i3-6006U', 'DDR4 / DDR3L', 'Skylake-U (DDR4 / DDR3L)'],
        'Intel I3-7100U'                => ['Intel', 'Core i3-7100U', 'DDR4 / DDR3L', 'Kaby Lake-U (DDR4-2133 / DDR3L-1600)'],
        'Core I3-7020U'                 => ['Intel', 'Core i3-7020U', 'DDR4 / DDR3L', 'Kaby Lake-U (DDR4-2133 / DDR3L-1600)'],
        'Intel Core I5-3320M'           => ['Intel', 'Core i5-3320M', 'DDR3 / DDR3L', 'Ivy Bridge móvil (DDR3-1600)'],
        'Intel Core I5-3320'            => ['Intel', 'Core i5-3320M', 'DDR3 / DDR3L', 'Ivy Bridge móvil (DDR3-1600)'],
        'Intel Core I7-4710'            => ['Intel', 'Core i7-4710MQ', 'DDR3L', 'Haswell móvil (DDR3L-1600)'],
        'Intel Core I7-2620M'           => ['Intel', 'Core i7-2620M', 'DDR3', 'Sandy Bridge móvil (DDR3-1333)'],
    ];

    if (isset($p[$t])) {
        return [
            'marca' => $p[$t][0],
            'modelo' => $p[$t][1],
            'resultado' => $p[$t][0] . ' ' . $p[$t][1],
            'ram' => $p[$t][2],
            'logica' => $p[$t][3]
        ];
    }

    return [
        'marca' => 'Desconocida',
        'modelo' => $t,
        'resultado' => $t,
        'ram' => 'Consultar',
        'logica' => 'Sin regla específica (mantiene valor original)'
    ];
}

// Función helper para badge visual según tipo de RAM
function badgeRam($ram) {
    if (strpos($ram, 'DDR4') !== false) {
        return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold"><i class="fas fa-bolt me-1"></i>' . htmlspecialchars($ram) . '</span>';
    } elseif (strpos($ram, 'DDR3') !== false) {
        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold"><i class="fas fa-memory me-1"></i>' . htmlspecialchars($ram) . '</span>';
    } elseif (strpos($ram, 'DDR2') !== false) {
        return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fw-bold"><i class="fas fa-history me-1"></i>' . htmlspecialchars($ram) . '</span>';
    }
    return '<span class="badge bg-light text-muted border">' . htmlspecialchars($ram) . '</span>';
}

// Consultas a BD
$mothers_db = $db->query("SELECT mother, COUNT(*) as cant FROM pcs_completas GROUP BY mother ORDER BY cant DESC, mother ASC")->fetchAll(PDO::FETCH_ASSOC);
$cpus_pcs_db = $db->query("SELECT procesador, COUNT(*) as cant FROM pcs_completas GROUP BY procesador ORDER BY cant DESC, procesador ASC")->fetchAll(PDO::FETCH_ASSOC);
$cpus_notes_db = $db->query("SELECT procesador, COUNT(*) as cant FROM notebooks GROUP BY procesador ORDER BY cant DESC, procesador ASC")->fetchAll(PDO::FETCH_ASSOC);

// Consolidación de Modelos ÚNICOS Normalizados para Motherboards
$unicos_mothers = [];
foreach ($mothers_db as $row) {
    $n = normalizarMotherboard($row['mother']);
    if ($n['marca'] === 'No disponible') continue;
    $key = $n['marca'] . '###' . $n['modelo'];
    if (!isset($unicos_mothers[$key])) {
        $unicos_mothers[$key] = [
            'marca' => $n['marca'],
            'modelo' => $n['modelo'],
            'resultado' => $n['resultado'],
            'ram' => $n['ram'],
            'total_equipos' => 0,
            'variantes_origen' => []
        ];
    }
    $unicos_mothers[$key]['total_equipos'] += $row['cant'];
    $unicos_mothers[$key]['variantes_origen'][] = ($row['mother'] ?: '(Vacío)') . " (" . $row['cant'] . ")";
}
uasort($unicos_mothers, function($a, $b) {
    if ($a['marca'] === $b['marca']) return strcmp($a['modelo'], $b['modelo']);
    return strcmp($a['marca'], $b['marca']);
});

// Consolidación de Modelos ÚNICOS Normalizados para Procesadores
$unicos_cpus = [];
foreach ($cpus_pcs_db as $row) {
    $n = normalizarProcesador($row['procesador']);
    if ($n['marca'] === 'No disponible') continue;
    $key = $n['marca'] . '###' . $n['modelo'];
    if (!isset($unicos_cpus[$key])) {
        $unicos_cpus[$key] = [
            'marca' => $n['marca'],
            'modelo' => $n['modelo'],
            'resultado' => $n['resultado'],
            'ram' => $n['ram'],
            'pcs' => 0,
            'notebooks' => 0,
            'variantes_origen' => []
        ];
    }
    $unicos_cpus[$key]['pcs'] += $row['cant'];
    $unicos_cpus[$key]['variantes_origen'][] = "PC: " . ($row['procesador'] ?: '(Vacío)') . " (" . $row['cant'] . ")";
}

foreach ($cpus_notes_db as $row) {
    $n = normalizarProcesador($row['procesador']);
    if ($n['marca'] === 'No disponible') continue;
    $key = $n['marca'] . '###' . $n['modelo'];
    if (!isset($unicos_cpus[$key])) {
        $unicos_cpus[$key] = [
            'marca' => $n['marca'],
            'modelo' => $n['modelo'],
            'resultado' => $n['resultado'],
            'ram' => $n['ram'],
            'pcs' => 0,
            'notebooks' => 0,
            'variantes_origen' => []
        ];
    }
    $unicos_cpus[$key]['notebooks'] += $row['cant'];
    $unicos_cpus[$key]['variantes_origen'][] = "NB: " . ($row['procesador'] ?: '(Vacío)') . " (" . $row['cant'] . ")";
}
uasort($unicos_cpus, function($a, $b) {
    if ($a['marca'] === $b['marca']) return strcmp($a['modelo'], $b['modelo']);
    return strcmp($a['marca'], $b['marca']);
});

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propuesta de Clasificación y Normalización | SITIA</title>
    <link href="<?php echo app_base_url(); ?>/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo app_base_url(); ?>/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; font-size: 0.9rem; color: #1e293b; }
        .card { border-radius: 8px; border: none; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1); }
        .badge-cambio { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; }
        .badge-igual { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .badge-marca-asus { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-marca-gigabyte { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .badge-marca-msi { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-marca-biostar { background-color: #fefce8; color: #a16207; border: 1px solid #fef08a; }
        .badge-marca-asrock { background-color: #faf5ff; color: #7e22ce; border: 1px solid #f3e8ff; }
        .badge-marca-intel { background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-marca-amd { background-color: #fff1f2; color: #be123c; border: 1px solid #ffe4e6; }
        .badge-marca-hp { background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; }
        .nav-tabs .nav-link { font-weight: 600; color: #64748b; }
        .nav-tabs .nav-link.active { color: #0284c7; border-bottom: 2px solid #0284c7; background: #fff; }
        .code-pill { font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; color: #334155; }
    </style>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 mb-1 text-primary"><i class="fas fa-microchip me-2"></i>Propuesta de Clasificación y Normalización de Hardware</h2>
            <p class="text-muted mb-0">Estandarización a formato canónico (Marca, Modelo y Tipo de RAM Compatible) en Base de Datos.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalModelosUnicos">
                <i class="fas fa-list-check me-1"></i>Ver Solo Modelos Normalizados (Catálogo Único)
            </button>
            <a href="pages/insumos/listar.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver al Sistema
            </a>
        </div>
    </div>

    <!-- Resumen superior -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 border-start border-4 border-primary">
                <span class="text-muted small text-uppercase fw-bold">Motherboards en PCs</span>
                <h3 class="h4 mb-0 mt-1"><?php echo count($mothers_db); ?> <span class="fs-6 text-muted fw-normal">actuales &rarr; <strong><?php echo count($unicos_mothers); ?> modelos únicos</strong></span></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-start border-4 border-success">
                <span class="text-muted small text-uppercase fw-bold">Procesadores en PCs</span>
                <h3 class="h4 mb-0 mt-1"><?php echo count($cpus_pcs_db); ?> <span class="fs-6 text-muted fw-normal">actuales &rarr; <strong>~22 modelos únicos</strong></span></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-start border-4 border-warning">
                <span class="text-muted small text-uppercase fw-bold">Procesadores en Notebooks</span>
                <h3 class="h4 mb-0 mt-1"><?php echo count($cpus_notes_db); ?> <span class="fs-6 text-muted fw-normal">actuales &rarr; <strong>~8 modelos únicos</strong></span></h3>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 pb-0 d-flex justify-content-between align-items-center">
            <ul class="nav nav-tabs border-0" id="tabsNormalizacion" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="unicos-tab" data-bs-toggle="tab" data-bs-target="#unicos" type="button">
                        <i class="fas fa-layer-group text-success me-2"></i><strong>Catálogo de Modelos Únicos Normalizados</strong>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="mothers-tab" data-bs-toggle="tab" data-bs-target="#mothers" type="button">
                        <i class="fas fa-chess-board text-primary me-2"></i>Mapeo Detallado Motherboards (<?php echo count($mothers_db); ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="cpus-pc-tab" data-bs-toggle="tab" data-bs-target="#cpus-pc" type="button">
                        <i class="fas fa-desktop text-info me-2"></i>Mapeo Detallado Procesadores PC (<?php echo count($cpus_pcs_db); ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="cpus-note-tab" data-bs-toggle="tab" data-bs-target="#cpus-note" type="button">
                        <i class="fas fa-laptop text-warning me-2"></i>Mapeo Detallado Procesadores Notebook (<?php echo count($cpus_notes_db); ?>)
                    </button>
                </li>
            </ul>
            <div class="pb-2">
                <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalModelosUnicos">
                    <i class="fas fa-up-right-and-down-left-from-center me-1"></i>Abrir en Modal
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="tabsContent">
                
                <!-- TAB 0: CATÁLOGO DE MODELOS ÚNICOS -->
                <div class="tab-pane fade show active p-3" id="unicos" role="tabpanel">
                    <div class="alert alert-light border d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <i class="fas fa-circle-info text-primary me-2"></i>
                            <strong>Listado consolidado de modelos únicos</strong> con su correspondiente tipo de memoria RAM compatible.
                        </div>
                        <span class="badge bg-primary fs-6"><?php echo count($unicos_mothers); ?> Mothers | <?php echo count($unicos_cpus); ?> Procesadores</span>
                    </div>

                    <div class="row g-4">
                        <!-- Columna Motherboards Únicas -->
                        <div class="col-lg-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-chess-board me-2"></i>Motherboards Únicas (<?php echo count($unicos_mothers); ?> modelos)</h6>
                                    <span class="badge bg-secondary"><?php echo array_sum(array_column($unicos_mothers, 'total_equipos')); ?> PCs</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Marca</th>
                                                    <th>Modelo Normalizado</th>
                                                    <th>Tipo RAM</th>
                                                    <th class="text-center">PCs</th>
                                                    <th>Variantes que unifica</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; foreach ($unicos_mothers as $m): 
                                                    $bclass = 'badge-marca-' . strtolower($m['marca']);
                                                ?>
                                                <tr>
                                                    <td class="text-muted small"><?php echo $i++; ?></td>
                                                    <td><span class="badge <?php echo $bclass; ?>"><?php echo htmlspecialchars($m['marca']); ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($m['modelo']); ?></strong></td>
                                                    <td><?php echo badgeRam($m['ram']); ?></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $m['total_equipos']; ?></span></td>
                                                    <td><small class="text-muted"><?php echo htmlspecialchars(implode(', ', $m['variantes_origen'])); ?></small></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Procesadores Únicos -->
                        <div class="col-lg-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <h6 class="mb-0 text-success fw-bold"><i class="fas fa-microchip me-2"></i>Procesadores Únicos (<?php echo count($unicos_cpus); ?> modelos)</h6>
                                    <span class="badge bg-secondary">PCs + Notebooks</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Marca</th>
                                                    <th>Modelo Normalizado</th>
                                                    <th>Tipo RAM</th>
                                                    <th class="text-center">PCs</th>
                                                    <th class="text-center">NBs</th>
                                                    <th>Variantes que unifica</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $j = 1; foreach ($unicos_cpus as $c): 
                                                    $bclass = 'badge-marca-' . strtolower($c['marca']);
                                                ?>
                                                <tr>
                                                    <td class="text-muted small"><?php echo $j++; ?></td>
                                                    <td><span class="badge <?php echo $bclass; ?>"><?php echo htmlspecialchars($c['marca']); ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($c['modelo']); ?></strong></td>
                                                    <td><?php echo badgeRam($c['ram']); ?></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $c['pcs'] ?: '-'; ?></span></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $c['notebooks'] ?: '-'; ?></span></td>
                                                    <td><small class="text-muted"><?php echo htmlspecialchars(implode(', ', $c['variantes_origen'])); ?></small></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 1: DETALLE MOTHERBOARDS -->
                <div class="tab-pane fade p-3" id="mothers" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 23%;">Valor Actual en Base de Datos</th>
                                    <th style="width: 6%;" class="text-center">PCs</th>
                                    <th style="width: 10%;">Marca</th>
                                    <th style="width: 18%;">Modelo</th>
                                    <th style="width: 12%;">Tipo RAM</th>
                                    <th style="width: 18%;">Texto Final Normalizado</th>
                                    <th style="width: 13%;">Lógica Aplicada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mothers_db as $row): 
                                    $n = normalizarMotherboard($row['mother']);
                                    $modificado = ($row['mother'] !== $n['resultado']);
                                ?>
                                <tr>
                                    <td><span class="code-pill"><?php echo htmlspecialchars($row['mother'] ?: '(Vacio)'); ?></span></td>
                                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo $row['cant']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($n['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($n['modelo']); ?></td>
                                    <td><?php echo badgeRam($n['ram']); ?></td>
                                    <td>
                                        <?php if ($modificado): ?>
                                            <span class="badge badge-cambio"><i class="fas fa-arrow-right me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-igual"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($n['logica']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: DETALLE PROCESADORES PC -->
                <div class="tab-pane fade p-3" id="cpus-pc" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 23%;">Valor Actual en Base de Datos</th>
                                    <th style="width: 6%;" class="text-center">PCs</th>
                                    <th style="width: 10%;">Marca</th>
                                    <th style="width: 18%;">Modelo</th>
                                    <th style="width: 12%;">Tipo RAM</th>
                                    <th style="width: 18%;">Texto Final Normalizado</th>
                                    <th style="width: 13%;">Lógica Aplicada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cpus_pcs_db as $row): 
                                    $n = normalizarProcesador($row['procesador']);
                                    $modificado = ($row['procesador'] !== $n['resultado']);
                                ?>
                                <tr>
                                    <td><span class="code-pill"><?php echo htmlspecialchars($row['procesador'] ?: '(Vacio)'); ?></span></td>
                                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo $row['cant']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($n['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($n['modelo']); ?></td>
                                    <td><?php echo badgeRam($n['ram']); ?></td>
                                    <td>
                                        <?php if ($modificado): ?>
                                            <span class="badge badge-cambio"><i class="fas fa-arrow-right me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-igual"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($n['logica']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: DETALLE PROCESADORES NOTEBOOK -->
                <div class="tab-pane fade p-3" id="cpus-note" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 23%;">Valor Actual en Base de Datos</th>
                                    <th style="width: 6%;" class="text-center">Equipos</th>
                                    <th style="width: 10%;">Marca</th>
                                    <th style="width: 18%;">Modelo</th>
                                    <th style="width: 12%;">Tipo RAM</th>
                                    <th style="width: 18%;">Texto Final Normalizado</th>
                                    <th style="width: 13%;">Lógica Aplicada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cpus_notes_db as $row): 
                                    $n = normalizarProcesador($row['procesador']);
                                    $modificado = ($row['procesador'] !== $n['resultado']);
                                ?>
                                <tr>
                                    <td><span class="code-pill"><?php echo htmlspecialchars($row['procesador'] ?: '(Vacio)'); ?></span></td>
                                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo $row['cant']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($n['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($n['modelo']); ?></td>
                                    <td><?php echo badgeRam($n['ram']); ?></td>
                                    <td>
                                        <?php if ($modificado): ?>
                                            <span class="badge badge-cambio"><i class="fas fa-arrow-right me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-igual"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($n['resultado']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($n['logica']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL COMPACTO DE MODELOS ÚNICOS NORMALIZADOS CON TIPO DE RAM -->
<div class="modal fade" id="modalModelosUnicos" tabindex="-1" aria-labelledby="modalModelosUnicosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-success" id="modalModelosUnicosLabel">
                    <i class="fas fa-list-check me-2"></i>Catálogo Consolidado: Modelos Únicos con Compatibilidad RAM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">
                    Modelos normalizados con especificación del tipo de memoria RAM con el que opera cada componente.
                </p>

                <div class="row g-4">
                    <!-- Lista Motherboards -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="fas fa-chess-board me-1"></i>Motherboards (<?php echo count($unicos_mothers); ?>)</span>
                                <small><?php echo array_sum(array_column($unicos_mothers, 'total_equipos')); ?> PCs</small>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
                                    <?php foreach ($unicos_mothers as $m): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1"><?php echo htmlspecialchars($m['marca']); ?></span>
                                            <strong><?php echo htmlspecialchars($m['modelo']); ?></strong>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo badgeRam($m['ram']); ?>
                                            <span class="badge bg-light text-muted border"><?php echo $m['total_equipos']; ?></span>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Lista Procesadores -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="fas fa-microchip me-1"></i>Procesadores (<?php echo count($unicos_cpus); ?>)</span>
                                <small>PCs y Notebooks</small>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
                                    <?php foreach ($unicos_cpus as $c): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><?php echo htmlspecialchars($c['marca']); ?></span>
                                            <strong><?php echo htmlspecialchars($c['modelo']); ?></strong>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo badgeRam($c['ram']); ?>
                                            <div class="text-end">
                                                <?php if ($c['pcs']): ?><span class="badge bg-light text-muted border"><?php echo $c['pcs']; ?> PC</span><?php endif; ?>
                                                <?php if ($c['notebooks']): ?><span class="badge bg-light text-muted border"><?php echo $c['notebooks']; ?> NB</span><?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo app_base_url(); ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
