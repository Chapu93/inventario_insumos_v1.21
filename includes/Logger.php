<?php
/**
 * Sistema de Logging Configurable
 * Permite control total sobre el logging en desarrollo vs producción
 */
class Logger {
    private static $enabled = false; // false en producción, true en desarrollo
    private static $level = 'ERROR'; // INFO, DEBUG, WARNING, ERROR
    private static $logDir = __DIR__ . '/../logs/';
    
    /**
     * Habilitar o deshabilitar el logging
     */
    public static function enable($enable = true) {
        self::$enabled = $enable;
    }
    
    /**
     * Configurar el nivel mínimo de logging
     */
    public static function setLevel($level) {
        $validLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
        if (in_array($level, $validLevels)) {
            self::$level = $level;
        }
    }
    
    /**
     * Log de debugging (solo en desarrollo)
     */
    public static function debug($message, $context = []) {
        if (self::$enabled && self::shouldLog('DEBUG')) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Log informativo
     */
    public static function info($message, $context = []) {
        if (self::$enabled && self::shouldLog('INFO')) {
            self::log('INFO', $message, $context);
        }
    }
    
    /**
     * Log de advertencia
     */
    public static function warning($message, $context = []) {
        if (self::shouldLog('WARNING')) {
            self::log('WARNING', $message, $context);
        }
    }
    
    /**
     * Log de error (siempre se registra)
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Determinar si se debe loguear según el nivel
     */
    private static function shouldLog($level) {
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
        return $levels[$level] >= $levels[self::$level];
    }
    
    /**
     * Realizar el logging
     */
    private static function log($level, $message, $context = []) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
            $user = isset($_SESSION['usuario_id']) ? ' [User:' . $_SESSION['usuario_id'] . ']' : '';
            $ip = isset($_SERVER['REMOTE_ADDR']) ? ' [IP:' . $_SERVER['REMOTE_ADDR'] . ']' : '';
            
            $logMessage = "[{$timestamp}] [{$level}]{$user}{$ip} {$message}{$contextStr}";
            
            // Escribir a error_log de PHP
            @error_log($logMessage);
            
            // También escribir a archivo específico si el directorio existe
            if (is_dir(self::$logDir) || @mkdir(self::$logDir, 0755, true)) {
                $logFile = self::$logDir . 'app_' . date('Y-m-d') . '.log';
                @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
            }
        } catch (Exception $e) {
            // Si falla el logging, no romper la aplicación
            @error_log("Logger error: " . $e->getMessage());
        }
    }
}
