<?php
/**
 * Sistema Centralizado de Validación de Archivos
 * 
 * Proporciona validación segura de archivos subidos incluyendo:
 * - Validación de extensión
 * - Validación de MIME type real
 * - Límite de tamaño
 * - Bloqueo de archivos potencialmente peligrosos
 */

if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Acceso directo no permitido');
}

/**
 * Mapeo de extensiones a MIME types permitidos
 */
function obtenerMimeTypesPermitidos() {
    return [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'odt' => ['application/vnd.oasis.opendocument.text'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'txt' => ['text/plain'],
    ];
}

/**
 * Extensiones potencialmente peligrosas que nunca deben permitirse
 */
function obtenerExtensionesPeligrosas() {
    return [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'exe', 'bat', 'cmd', 'com', 'msi',
        'sh', 'bash', 'zsh',
        'js', 'jsx', 'ts', 'tsx',
        'htaccess', 'htpasswd',
        'svg', // SVG puede contener JavaScript malicioso
        'html', 'htm', 'xhtml',
        'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'rb'
    ];
}

/**
 * Mensajes de error de upload
 */
function obtenerMensajeErrorUpload($codigo) {
    $mensajes = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el límite del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo fue parcialmente cargado',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal del servidor',
        UPLOAD_ERR_CANT_WRITE => 'Error de escritura en el servidor',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó el archivo'
    ];
    return $mensajes[$codigo] ?? 'Error desconocido al subir el archivo';
}

/**
 * Validar archivo subido
 * 
 * @param array $archivo - $_FILES['nombre_campo']
 * @param array $opciones - Opciones de validación
 *   - max_size: Tamaño máximo en bytes (default: 10MB)
 *   - extensiones: Array de extensiones permitidas (default: lista completa)
 *   - validar_mime: Si validar MIME type real (default: true)
 *   - bloquear_peligrosos: Si bloquear extensiones peligrosas (default: true)
 * 
 * @return array ['valido' => bool, 'error' => string|null, 'extension' => string|null, 'mime' => string|null]
 */
function validarArchivo($archivo, $opciones = []) {
    // Opciones por defecto
    $defaults = [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'extensiones' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods'],
        'validar_mime' => true,
        'bloquear_peligrosos' => true
    ];
    
    $config = array_merge($defaults, $opciones);
    
    // 1. Verificar que se recibió un archivo
    if (!isset($archivo) || !is_array($archivo)) {
        return ['valido' => false, 'error' => 'No se recibió ningún archivo', 'extension' => null, 'mime' => null];
    }
    
    // 2. Verificar error de upload
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return [
            'valido' => false, 
            'error' => obtenerMensajeErrorUpload($archivo['error']), 
            'extension' => null, 
            'mime' => null
        ];
    }
    
    // 3. Validar tamaño
    if ($archivo['size'] > $config['max_size']) {
        $maxMB = round($config['max_size'] / (1024 * 1024), 1);
        return [
            'valido' => false, 
            'error' => "El archivo excede el tamaño máximo permitido ({$maxMB} MB)", 
            'extension' => null, 
            'mime' => null
        ];
    }
    
    // 4. Obtener y validar extensión
    $nombreOriginal = $archivo['name'];
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    
    if (empty($extension)) {
        return ['valido' => false, 'error' => 'El archivo no tiene extensión', 'extension' => null, 'mime' => null];
    }
    
    // 5. Verificar extensiones peligrosas
    if ($config['bloquear_peligrosos']) {
        $peligrosas = obtenerExtensionesPeligrosas();
        if (in_array($extension, $peligrosas, true)) {
            return [
                'valido' => false, 
                'error' => "Tipo de archivo no permitido por razones de seguridad: .{$extension}", 
                'extension' => $extension, 
                'mime' => null
            ];
        }
    }
    
    // 6. Verificar extensión permitida
    if (!in_array($extension, $config['extensiones'], true)) {
        $permitidas = implode(', ', $config['extensiones']);
        return [
            'valido' => false, 
            'error' => "Extensión no permitida: .{$extension}. Extensiones válidas: {$permitidas}", 
            'extension' => $extension, 
            'mime' => null
        ];
    }
    
    // 7. Validar MIME type real
    $mimeReal = null;
    if ($config['validar_mime']) {
        // Usar finfo para detectar el MIME type real del contenido
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($archivo['tmp_name']);
        
        if ($mimeReal === false) {
            return [
                'valido' => false, 
                'error' => 'No se pudo determinar el tipo de archivo', 
                'extension' => $extension, 
                'mime' => null
            ];
        }
        
        // Obtener MIME types permitidos para esta extensión
        $mimeTypesPermitidos = obtenerMimeTypesPermitidos();
        
        if (isset($mimeTypesPermitidos[$extension])) {
            $mimesValidos = $mimeTypesPermitidos[$extension];
            
            if (!in_array($mimeReal, $mimesValidos, true)) {
                // Log para debugging
                if (class_exists('Logger')) {
                    Logger::warning('MIME type no coincide', [
                        'archivo' => $nombreOriginal,
                        'extension' => $extension,
                        'mime_detectado' => $mimeReal,
                        'mimes_esperados' => $mimesValidos
                    ]);
                }
                
                return [
                    'valido' => false, 
                    'error' => "El contenido del archivo no coincide con la extensión .{$extension}", 
                    'extension' => $extension, 
                    'mime' => $mimeReal
                ];
            }
        }
        // Si la extensión no tiene MIME definido, solo validamos extensión
    }
    
    // Archivo válido
    return [
        'valido' => true, 
        'error' => null, 
        'extension' => $extension, 
        'mime' => $mimeReal
    ];
}

/**
 * Validar archivo para documentos generales (ingresos, pedidos, etc.)
 * Extensiones: PDF, imágenes, documentos Office/LibreOffice, ZIP
 */
function validarArchivoDocumento($archivo, $maxSize = null) {
    return validarArchivo($archivo, [
        'max_size' => $maxSize ?? 10 * 1024 * 1024,
        'extensiones' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods'],
    ]);
}

/**
 * Validar archivo solo PDF
 */
function validarArchivoPdf($archivo, $maxSize = null) {
    return validarArchivo($archivo, [
        'max_size' => $maxSize ?? 10 * 1024 * 1024,
        'extensiones' => ['pdf'],
    ]);
}

/**
 * Validar archivo para planos (sin SVG por seguridad)
 * Extensiones: PDF, PNG, JPG
 */
function validarArchivoPlano($archivo, $maxSize = null) {
    return validarArchivo($archivo, [
        'max_size' => $maxSize ?? 10 * 1024 * 1024,
        'extensiones' => ['pdf', 'png', 'jpg', 'jpeg'],
    ]);
}

/**
 * Validar archivo de imagen
 */
function validarArchivoImagen($archivo, $maxSize = null) {
    return validarArchivo($archivo, [
        'max_size' => $maxSize ?? 5 * 1024 * 1024,
        'extensiones' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ]);
}
