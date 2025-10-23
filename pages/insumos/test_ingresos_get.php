<?php
/**
 * TEST: Verificar que ingresos_get.php funciona
 * 
 * Acceder a: /inventario_app/pages/insumos/test_ingresos_get.php
 */

require_once '../../includes/config.php';

echo "<h3>Test de ingresos_get.php</h3>";
echo "<pre>";

// 1. Verificar que el archivo existe
$file = __DIR__ . '/../../ajax/ingresos_get.php';
echo "1. Verificar archivo existe:\n";
echo "   Ruta: {$file}\n";
echo "   Existe: " . (file_exists($file) ? "✓ SÍ" : "✗ NO") . "\n\n";

// 2. Verificar BASE_URL
echo "2. Verificar BASE_URL:\n";
echo "   APP_BASE_URL: " . (defined('APP_BASE_URL') ? APP_BASE_URL : 'NO DEFINIDO') . "\n";
echo "   BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NO DEFINIDO') . "\n\n";

// 3. Listar ingresos disponibles
echo "3. Ingresos en la base de datos:\n";
try {
    $db = conectarDB();
    $stmt = $db->query("SELECT id_ingreso, tipo_ingreso, nro_referencia, fecha_finalizacion FROM ingresos ORDER BY id_ingreso DESC LIMIT 5");
    $ingresos = $stmt->fetchAll();
    
    if (empty($ingresos)) {
        echo "   ⚠️ No hay ingresos en la base de datos\n\n";
    } else {
        foreach ($ingresos as $ing) {
            echo "   - ID: {$ing['id_ingreso']} | Tipo: {$ing['tipo_ingreso']} | Nro: {$ing['nro_referencia']} | Fecha: {$ing['fecha_finalizacion']}\n";
        }
        echo "\n";
        
        // 4. Probar con el primer ID
        $testId = $ingresos[0]['id_ingreso'];
        echo "4. Probar ingresos_get.php con ID {$testId}:\n";
        
        // Simular llamada
        $_GET['id'] = $testId;
        
        ob_start();
        include $file;
        $response = ob_get_clean();
        
        echo "   Response:\n";
        $json = json_decode($response, true);
        if ($json) {
            echo "   ✓ JSON válido\n";
            echo "   success: " . ($json['success'] ? 'true' : 'false') . "\n";
            if ($json['success']) {
                echo "   data.tipo_ingreso: {$json['data']['tipo_ingreso']}\n";
                echo "   data.nro_referencia: {$json['data']['nro_referencia']}\n";
                echo "   data.insumos: " . count($json['data']['insumos']) . " insumos\n";
            } else {
                echo "   error: {$json['error']}\n";
            }
        } else {
            echo "   ✗ JSON inválido\n";
            echo "   Raw: {$response}\n";
        }
        echo "\n";
        
        // 5. Mostrar URL que usaría JavaScript
        $baseUrl = app_base_url();
        echo "5. URL que usaría JavaScript:\n";
        echo "   {$baseUrl}/ajax/ingresos_get.php?id={$testId}\n\n";
        
        echo "6. Probar manualmente:\n";
        echo "   <a href='{$baseUrl}/ajax/ingresos_get.php?id={$testId}' target='_blank'>Click aquí para ver JSON</a>\n\n";
    }
    
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n\n";
}

echo "</pre>";

echo "<h4>Siguiente paso:</h4>";
echo "<ol>";
echo "<li>Si todo está en ✓: Abre la consola del navegador (F12) en ingresos_listar.php y click en Ver ingreso</li>";
echo "<li>Verifica los logs en la consola y en el error_log de PHP</li>";
echo "<li>Si ves 404: verifica la ruta BASE en ingresos_listar.php</li>";
echo "<li>Si ves 500: revisa el error_log de PHP</li>";
echo "</ol>";
?>
