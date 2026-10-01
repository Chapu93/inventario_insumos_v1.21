<?php
// zona_localidades_responsables.php
// SITIA - Estructura Territorial de Zonas, Localidades y Delegados Responsables
// Diseñado para imprimir en 1 hoja A4 Horizontal o generar PDF dinámico con campos interactivos

require_once __DIR__ . '/includes/config.php';

// Estructura de Zonas y Localidades de Río Negro (El Bolsón integrado en Zona Andina)
$zonas_datos = [
    1 => ['nombre' => 'ZONA VALLE INFERIOR', 'localidades' => ['Viedma', 'General Conesa']],
    2 => ['nombre' => 'ZONA ALTO VALLE ESTE', 'localidades' => ['Villa Regina', 'Chichinales', 'Ing. Huergo']],
    3 => ['nombre' => 'ZONA ATLÁNTICA', 'localidades' => ['San Antonio Oeste', 'Sierra Grande', 'Valcheta']],
    4 => ['nombre' => 'ZONA ALTO VALLE CENTRO', 'localidades' => ['General Roca', 'Allen']],
    5 => ['nombre' => 'ZONA VALLE MEDIO', 'localidades' => ['Choele Choel', 'Lamarque', 'Luis Beltrán', 'Darwin', 'Belisle', 'Chimpay', 'Río Colorado', 'Colonia Josefa']],
    6 => ['nombre' => 'ZONA ALTO VALLE OESTE I', 'localidades' => ['Cipolletti', 'Fernández Oro']],
    7 => ['nombre' => 'ZONA ALTO VALLE OESTE II', 'localidades' => ['Cinco Saltos']],
    8 => ['nombre' => 'ZONA ALTO VALLE OESTE III', 'localidades' => ['Catriel']],
    9 => ['nombre' => 'ZONA LÍNEA SUR', 'localidades' => ['Ramos Mexía', 'Sierra Colorada', 'Los Menucos', 'Maquinchao', 'Ing. Jacobacci', 'Comallo']],
    10 => ['nombre' => 'ZONA ANDINA', 'localidades' => ['Bariloche', 'El Bolsón']]
];

// Distribución en 4 Columnas para A4 Horizontal
$columnas = [
    [1, 2, 3],       // Valle Inferior, Alto Valle Este, Atlántica
    [4, 5],          // Alto Valle Centro, Valle Medio
    [6, 7, 8, 10],   // Alto Valle Oeste I, II, III, Andina (incluye El Bolsón)
    [9]              // Línea Sur
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonas, Localidades y Delegados Responsables - SITIA</title>
    <link href="<?php echo app_base_url(); ?>/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_base_url(); ?>/public/vendor/fontawesome/css/all.min.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 4mm;
        }
        body {
            background-color: #f1f5f9;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000000;
            margin: 0;
            padding: 8px;
        }
        .page-container {
            max-width: 100%;
            background: #ffffff;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        
        /* Estilo Organigrama institucional */
        .top-header-box {
            border: 1.5px solid #000000;
            border-radius: 5px;
            padding: 4px 8px;
            text-align: center;
            background: #ffffff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .top-title-box {
            border: 1.5px solid #000000;
            border-radius: 5px;
            padding: 6px;
            text-align: center;
            background: #f8fafc;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .col-quarter {
            width: 25%;
            padding: 0 3px;
        }

        /* Contenedor Principal de Zona */
        .zona-container {
            border: 1.5px solid #000000;
            border-radius: 6px;
            margin-bottom: 6px;
            background-color: #ffffff;
            overflow: hidden;
        }
        
        .zona-title {
            background-color: #e2e8f0;
            border-bottom: 1.5px solid #000000;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 10.5px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000000;
        }

        .zona-content {
            padding: 4px;
        }

        /* Tarjetas de Localidad estilo Organigrama */
        .localidad-card {
            border: 1.2px solid #000000;
            border-radius: 4px;
            margin-bottom: 4px;
            background-color: #ffffff;
            text-align: center;
            padding: 3px 5px;
        }
        .localidad-card:last-child {
            margin-bottom: 0;
        }

        .localidad-header {
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #000000;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }

        .delegado-field {
            font-size: 8.5px;
            color: #1e293b;
            padding-top: 1px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .delegado-label {
            font-weight: bold;
            color: #334155;
            font-size: 8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Campo de texto editable para Delegado Responsable */
        .delegado-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 2px 5px;
            font-size: 8.5px;
            color: #0f172a;
            background-color: #ffffff;
            outline: none;
            transition: all 0.2s ease-in-out;
        }
        .delegado-input:focus {
            border-color: #0284c7;
            background-color: #f0f9ff;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        }

        .no-print {
            margin-bottom: 8px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                padding: 0;
                border: none;
            }
            .zona-container {
                border: 1.5px solid #000000 !important;
            }
            .localidad-card {
                border: 1.2px solid #000000 !important;
            }
            .delegado-input {
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
                font-size: 8.5px !important;
                color: #000000 !important;
                font-weight: 500;
            }
        }
    </style>
</head>
<body>

    <form id="formZonasPdf" action="<?php echo app_base_url(); ?>/pages/reportes/zona_localidades_responsables_pdf.php" method="POST" target="_blank">
        <div class="container-fluid no-print text-end">
            <button type="submit" class="btn btn-primary btn-sm me-2 shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Generar PDF Oficial (Con Formulario Editable)
            </button>
            <button type="button" onclick="window.print()" class="btn btn-dark btn-sm me-2 shadow-sm">
                <i class="fas fa-print me-1"></i> Imprimir (Navegador)
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a SITIA
            </a>
        </div>

        <div class="page-container">
            <!-- Encabezado Organigrama -->
            <div class="row align-items-center g-2 mb-2">
                <div class="col-5">
                    <div class="top-header-box">
                        SECRETARÍA DE NIÑEZ, ADOLESCENCIA Y FAMILIA<br>
                        <span style="font-size:8.5px; font-weight:normal;">DIRECCIÓN DE INFORMÁTICA, TELECOMUNICACIONES Y REGISTRO ÚNICO NOMINAL</span>
                    </div>
                </div>
                <div class="col-7">
                    <div class="top-title-box">
                        ESTRUCTURA TERRITORIAL: ZONAS, LOCALIDADES Y DELEGADOS RESPONSABLES
                    </div>
                </div>
            </div>

            <!-- 4 Columnas A4 Horizontal con Zonas y Localidades Claramente Diferenciadas -->
            <div class="d-flex w-100">
                <?php foreach ($columnas as $col_zonas): ?>
                    <div class="col-quarter">
                        <?php foreach ($col_zonas as $id_z): 
                            if (!isset($zonas_datos[$id_z])) continue;
                            $z = $zonas_datos[$id_z];
                        ?>
                            <div class="zona-container">
                                <div class="zona-title">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($z['nombre']); ?>
                                </div>
                                <div class="zona-content">
                                    <?php foreach ($z['localidades'] as $loc): ?>
                                        <div class="localidad-card">
                                            <div class="localidad-header">
                                                <?php echo htmlspecialchars($loc); ?>
                                            </div>
                                            <div class="delegado-field">
                                                <span class="delegado-label">Delegado:</span>
                                                <input type="text" class="delegado-input" name="delegados[<?php echo htmlspecialchars($loc); ?>]" data-loc="<?php echo htmlspecialchars($loc); ?>" placeholder="Escribir nombre...">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Persistencia automática en localStorage de nombres tipeados
        const inputs = document.querySelectorAll('.delegado-input');
        inputs.forEach(input => {
            const locKey = 'sitia_delegado_' + input.getAttribute('data-loc');
            const savedVal = localStorage.getItem(locKey);
            if (savedVal) {
                input.value = savedVal;
            }
            input.addEventListener('input', function() {
                localStorage.setItem(locKey, this.value);
            });
        });
    });
    </script>
</body>
</html>
