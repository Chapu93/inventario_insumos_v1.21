<?php
/**
 * Sistema de Autenticación y Autorización
 * Funciones para gestión de sesiones, permisos y auditoría
 */

// Configuración de sesiones seguras
if (session_status() === PHP_SESSION_NONE) {
    // Detectar si estamos en HTTPS
    $is_https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
    
    ini_set('session.cookie_httponly', 1);
    // Solo forzar secure si realmente estamos en HTTPS
    ini_set('session.cookie_secure', $is_https ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    // SameSite Lax es más compatible que Strict para desarrollo
    ini_set('session.cookie_samesite', $is_https ? 'Strict' : 'Lax');
    session_start();
}

// ============================================
// RATE LIMITING PARA LOGIN - Agregado v2.0
// Rollback: Eliminar este bloque y las funciones
// ============================================
define('MAX_LOGIN_ATTEMPTS', 10);
define('LOCKOUT_TIME', 900); // 15 minutos

/**
 * Verifica si la IP está bloqueada por demasiados intentos
 * @param string $ip
 * @return array ['allowed' => bool, 'mensaje' => string|null, 'segundos_restantes' => int|null]
 */
function verificarRateLimitLogin($ip) {
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Resetear si pasó el tiempo de bloqueo
    if (time() - $data['first_attempt'] > LOCKOUT_TIME) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return ['allowed' => true];
    }
    
    if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
        $segundos_restantes = LOCKOUT_TIME - (time() - $data['first_attempt']);
        $minutos = ceil($segundos_restantes / 60);
        return [
            'allowed' => false,
            'mensaje' => "Demasiados intentos fallidos. Intente en {$minutos} minutos.",
            'segundos_restantes' => $segundos_restantes
        ];
    }
    
    return ['allowed' => true];
}

/**
 * Registra un intento de login
 * @param string $ip
 * @param bool $exitoso Si true, resetea el contador
 */
function registrarIntentoLogin($ip, $exitoso = false) {
    $key = 'login_attempts_' . md5($ip);
    
    if ($exitoso) {
        unset($_SESSION[$key]);
        return;
    }
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $_SESSION[$key]['count']++;
}
// ============================================

/**
 * Verificar si hay una sesión activa
 * @return bool
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && 
           isset($_SESSION['token_sesion']) && 
           isset($_SESSION['ultimo_acceso']);
}

/**
 * Obtener el ID del usuario actual
 * @return int|null
 */
function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener datos completos del usuario actual
 * @return array|null
 */
function obtenerUsuario() {
    if (!estaAutenticado()) {
        return null;
    }
    
    try {
        $db = conectarDB();
        $sql = "SELECT u.*, r.nombre_rol, r.permisos 
                FROM usuarios u 
                JOIN roles r ON u.id_rol = r.id_rol 
                WHERE u.id_usuario = ? AND u.activo = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_SESSION['usuario_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        Logger::error("Error al obtener usuario", [
            'mensaje' => $e->getMessage(),
            'usuario_id' => $_SESSION['usuario_id'] ?? 'no_disponible'
        ]);
        return null;
    }
}

/**
 * Verificar si el usuario tiene un permiso específico
 * Prioridad: permisos_personalizados > permisos del rol
 * @param string $modulo Módulo del sistema (insumos, asignaciones, etc.)
 * @param string $accion Acción específica (ver, crear, editar, etc.)
 * @return bool
 */
function tienePermiso($modulo, $accion) {
    $usuario = obtenerUsuario();
    if (!$usuario) {
        return false;
    }
    
    try {
        // Primero verificar si tiene permisos personalizados
        if (!empty($usuario['permisos_personalizados'])) {
            $permisosPersonalizados = json_decode($usuario['permisos_personalizados'], true);
            if (is_array($permisosPersonalizados)) {
                // Usar permisos personalizados
                if (!isset($permisosPersonalizados[$modulo])) {
                    return false;
                }
                return in_array($accion, $permisosPersonalizados[$modulo]);
            }
        }
        
        // Si no tiene permisos personalizados, usar permisos del rol
        $permisos = json_decode($usuario['permisos'], true);
        if (!isset($permisos[$modulo])) {
            return false;
        }
        
        return in_array($accion, $permisos[$modulo]);
    } catch (Exception $e) {
        Logger::error("Error al verificar permiso", [
            'mensaje' => $e->getMessage(),
            'modulo' => $modulo,
            'accion' => $accion
        ]);
        return false;
    }
}

/**
 * Verificar permiso y redirigir si no lo tiene
 * @param string $modulo
 * @param string $accion
 * @param string $redirect URL de redirección (default: index.php)
 */
function verificarPermiso($modulo, $accion, $redirect = null) {
    if (!tienePermiso($modulo, $accion)) {
        $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción';
        $_SESSION['tipo_mensaje'] = 'danger';
        
        if ($redirect === null) {
            $redirect = app_base_url() . '/index.php';
        }
        
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Iniciar sesión de usuario
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'mensaje' => string, 'usuario' => array|null]
 */
function iniciarSesion($username, $password) {
    try {
        $db = conectarDB();
        
        // Buscar usuario
        $sql = "SELECT u.*, r.nombre_rol, r.permisos 
                FROM usuarios u 
                JOIN roles r ON u.id_rol = r.id_rol 
                WHERE (u.username = ? OR u.email = ?) AND u.activo = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            registrarAuditoriaDirecto(null, null, 'login_fallido', 'usuarios', 
                "Intento de login fallido - Usuario no existe: {$username}", 
                null, null, null, null, 'error', 'Usuario no encontrado');
            return ['success' => false, 'mensaje' => 'Usuario o contraseña incorrectos'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $usuario['password_hash'])) {
            registrarAuditoriaDirecto($usuario['id_usuario'], null, 'login_fallido', 'usuarios', 
                "Intento de login fallido - Contraseña incorrecta para: {$username}", 
                null, null, null, null, 'error', 'Contraseña incorrecta');
            return ['success' => false, 'mensaje' => 'Usuario o contraseña incorrectos'];
        }
        
        // Cerrar otras sesiones activas del mismo usuario
        cerrarOtrasSesiones($usuario['id_usuario']);
        
        // Generar token de sesión
        $token = bin2hex(random_bytes(32));
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Crear sesión en BD
        $sqlSesion = "INSERT INTO sesiones (id_usuario, token_sesion, ip_address, user_agent, activa) 
                      VALUES (?, ?, ?, ?, 1)";
        $stmtSesion = $db->prepare($sqlSesion);
        $stmtSesion->execute([$usuario['id_usuario'], $token, $ip, $userAgent]);
        $idSesion = $db->lastInsertId();
        
        // Actualizar último acceso
        $sqlUpdate = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?";
        $db->prepare($sqlUpdate)->execute([$usuario['id_usuario']]);
        
        // Establecer variables de sesión
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['token_sesion'] = $token;
        $_SESSION['id_sesion'] = $idSesion;
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nombre_completo'] = trim($usuario['nombre'] . ' ' . $usuario['apellido']);
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['nombre_rol'] = $usuario['nombre_rol'];
        $_SESSION['ultimo_acceso'] = time();
        
        // Registrar en auditoría
        registrarAuditoria('login', 'usuarios', "Login exitoso: {$usuario['username']}");
        
        // Regenerar ID de sesión para prevenir session fixation - @added v2.0
        session_regenerate_id(true);
        
        return [
            'success' => true, 
            'mensaje' => 'Sesión iniciada correctamente',
            'usuario' => $usuario
        ];
        
    } catch (Exception $e) {
        Logger::error("Error en iniciarSesion", [
            'mensaje' => $e->getMessage(),
            'username' => $username
        ]);
        return ['success' => false, 'mensaje' => 'Error al iniciar sesión: ' . $e->getMessage()];
    }
}

/**
 * Cerrar sesión actual
 */
function cerrarSesion() {
    if (estaAutenticado()) {
        try {
            $db = conectarDB();
            
            // Registrar cierre en auditoría
            registrarAuditoria('logout', 'usuarios', 'Cierre de sesión');
            
            // Marcar sesión como cerrada
            if (isset($_SESSION['id_sesion'])) {
                $sql = "UPDATE sesiones SET activa = 0, fecha_cierre = NOW() WHERE id_sesion = ?";
                $db->prepare($sql)->execute([$_SESSION['id_sesion']]);
            }
        } catch (Exception $e) {
            Logger::error("Error al cerrar sesión", [
                'mensaje' => $e->getMessage(),
                'usuario_id' => $_SESSION['usuario_id'] ?? 'desconocido'
            ]);
        }
    }
    
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    
    // Destruir sesión
    session_destroy();
}

/**
 * Cerrar otras sesiones activas del mismo usuario
 * @param int $idUsuario
 */
function cerrarOtrasSesiones($idUsuario) {
    try {
        $db = conectarDB();
        $sql = "UPDATE sesiones SET activa = 0, fecha_cierre = NOW() 
                WHERE id_usuario = ? AND activa = 1";
        $db->prepare($sql)->execute([$idUsuario]);
    } catch (Exception $e) {
        Logger::error("Error al cerrar otras sesiones", [
            'mensaje' => $e->getMessage(),
            'usuario_id' => $idUsuario
        ]);
    }
}

/**
 * Verificar y actualizar sesión activa
 * Cierra sesión si ha expirado (30 minutos de inactividad)
 * @return bool True si la sesión es válida
 */
function verificarSesionActiva() {
    if (!estaAutenticado()) {
        return false;
    }
    
    // Verificar timeout (30 minutos = 1800 segundos)
    $timeout = 1800;
    if (time() - $_SESSION['ultimo_acceso'] > $timeout) {
        registrarAuditoria('sesion_expirada', 'usuarios', 'Sesión expirada por inactividad');
        cerrarSesion();
        return false;
    }
    
    // Actualizar último acceso
    $_SESSION['ultimo_acceso'] = time();
    
    // Actualizar en BD cada 5 minutos para no sobrecargar
    if (!isset($_SESSION['ultima_actualizacion_bd']) || 
        (time() - $_SESSION['ultima_actualizacion_bd']) > 300) {
        try {
            $db = conectarDB();
            $sql = "UPDATE sesiones SET fecha_ultimo_acceso = NOW() WHERE id_sesion = ?";
            $db->prepare($sql)->execute([$_SESSION['id_sesion']]);
            $_SESSION['ultima_actualizacion_bd'] = time();
        } catch (Exception $e) {
            Logger::error("Error al actualizar sesión", [
                'mensaje' => $e->getMessage(),
                'usuario_id' => $_SESSION['usuario_id'] ?? 'desconocido'
            ]);
        }
    }
    
    return true;
}

/**
 * Cerrar sesiones inactivas (ejecutar periódicamente via cron o al login)
 */
function cerrarSesionesInactivas() {
    try {
        $db = conectarDB();
        // Cerrar sesiones con más de 30 minutos de inactividad
        $sql = "UPDATE sesiones 
                SET activa = 0, fecha_cierre = NOW() 
                WHERE activa = 1 
                AND TIMESTAMPDIFF(MINUTE, fecha_ultimo_acceso, NOW()) > 30";
        $db->exec($sql);
    } catch (Exception $e) {
        Logger::error("Error al cerrar sesiones inactivas", [
            'mensaje' => $e->getMessage()
        ]);
    }
}

/**
 * Registrar acción en auditoría
 * @param string $accion Tipo de acción (crear_insumo, editar_asignacion, etc.)
 * @param string $modulo Módulo del sistema (insumos, asignaciones, etc.)
 * @param string $descripcion Descripción legible de la acción
 * @param string|null $entidadTipo Tipo de entidad afectada (opcional)
 * @param int|null $entidadId ID de la entidad afectada (opcional)
 * @param array|null $datosAntes Estado anterior (opcional)
 * @param array|null $datosDespues Estado posterior (opcional)
 * @param string $resultado 'exito' o 'error'
 * @param string|null $mensajeError Mensaje de error si aplica
 */
function registrarAuditoria(
    $accion, 
    $modulo, 
    $descripcion, 
    $entidadTipo = null, 
    $entidadId = null,
    $datosAntes = null,
    $datosDespues = null,
    $resultado = 'exito',
    $mensajeError = null
) {
    $idUsuario = obtenerUsuarioId();
    $idSesion = $_SESSION['id_sesion'] ?? null;
    
    registrarAuditoriaDirecto(
        $idUsuario,
        $idSesion,
        $accion,
        $modulo,
        $descripcion,
        $entidadTipo,
        $entidadId,
        $datosAntes,
        $datosDespues,
        $resultado,
        $mensajeError
    );
}

/**
 * Registrar acción en auditoría directamente (sin sesión activa)
 * Útil para registrar intentos de login fallidos
 */
function registrarAuditoriaDirecto(
    $idUsuario,
    $idSesion,
    $accion,
    $modulo,
    $descripcion,
    $entidadTipo = null,
    $entidadId = null,
    $datosAntes = null,
    $datosDespues = null,
    $resultado = 'exito',
    $mensajeError = null
) {
    try {
        $db = conectarDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Convertir arrays a JSON
        $jsonAntes = $datosAntes ? json_encode($datosAntes, JSON_UNESCAPED_UNICODE) : null;
        $jsonDespues = $datosDespues ? json_encode($datosDespues, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "INSERT INTO auditoria_acciones 
                (id_usuario, id_sesion, accion, modulo, descripcion, entidad_tipo, entidad_id,
                 datos_antes, datos_despues, ip_address, resultado, mensaje_error)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $idUsuario,
            $idSesion,
            $accion,
            $modulo,
            $descripcion,
            $entidadTipo,
            $entidadId,
            $jsonAntes,
            $jsonDespues,
            $ip,
            $resultado,
            $mensajeError
        ]);
        
    } catch (Exception $e) {
        Logger::error("Error al registrar auditoría", [
            'mensaje' => $e->getMessage(),
            'accion' => $accion,
            'modulo' => $modulo
        ]);
        // No lanzar excepción para no interrumpir el flujo principal
    }
}

/**
 * Requerir autenticación - Redirige a login si no está autenticado
 * @param string|null $redirectUrl URL a la que redirigir tras login exitoso
 */
function requerirAutenticacion($redirectUrl = null) {
    if (!verificarSesionActiva()) {
        if ($redirectUrl === null) {
            $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }
        
        $_SESSION['redirect_after_login'] = $redirectUrl;
        $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página';
        $_SESSION['tipo_mensaje'] = 'warning';
        
        header('Location: ' . app_base_url() . '/login.php');
        exit;
    }
}

/**
 * Verificar si el usuario tiene uno de los roles especificados
 * @param array $rolesPermitidos Array de IDs de roles o nombres de roles
 * @return bool
 */
function tieneRol($rolesPermitidos) {
    $usuario = obtenerUsuario();
    if (!$usuario) {
        return false;
    }
    
    foreach ($rolesPermitidos as $rol) {
        if (is_numeric($rol) && $usuario['id_rol'] == $rol) {
            return true;
        }
        if (is_string($rol) && strcasecmp($usuario['nombre_rol'], $rol) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Requerir uno de los roles especificados
 * @param array $rolesPermitidos
 * @param string|null $redirect
 */
function requerirRol($rolesPermitidos, $redirect = null) {
    if (!tieneRol($rolesPermitidos)) {
        $_SESSION['mensaje'] = 'No tienes permisos suficientes para acceder a esta sección';
        $_SESSION['tipo_mensaje'] = 'danger';
        
        if ($redirect === null) {
            $redirect = app_base_url() . '/index.php';
        }
        
        header('Location: ' . $redirect);
        exit;
    }
}
