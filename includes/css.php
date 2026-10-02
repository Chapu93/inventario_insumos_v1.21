<?php
/**
 * Carga modular y ordenada de hojas de estilo CSS de SITIA.
 * Escanea public/css/ y genera etiquetas <link> para cada módulo numerado (00-*.css a 99-*.css),
 * en orden alfabético estricto, incluyendo versionado por filemtime para evitar caché desactualizada.
 */
$cssDir = __DIR__ . '/../public/css';
$cssFiles = glob($cssDir . '/[0-9][0-9]-*.css');

if ($cssFiles) {
    sort($cssFiles, SORT_STRING);
    foreach ($cssFiles as $file) {
        $filename = basename($file);
        $v = filemtime($file);
        echo '    <link href="' . app_base_url() . '/public/css/' . $filename . '?v=' . $v . '" rel="stylesheet">' . "\n";
    }
}
