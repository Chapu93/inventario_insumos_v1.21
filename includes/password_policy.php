<?php
/**
 * Política de Contraseñas - v2.0
 * 
 * Valida que las contraseñas cumplan con los requisitos de seguridad mínimos.
 * 
 * Rollback: Eliminar este archivo y quitar el require_once donde se use.
 */

/**
 * Valida que una contraseña cumpla con la política de seguridad
 * Reglas: mínimo 6 caracteres, letras y números
 * @param string $password
 * @return array ['valido' => bool, 'errores' => array]
 */
function validarPoliticaPassword($password) {
    $errores = [];
    
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if (!preg_match('/[a-zA-Z]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos una letra';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos un número';
    }
    
    return [
        'valido' => empty($errores),
        'errores' => $errores
    ];
}

/**
 * Retorna las reglas de la política en formato legible para mostrar al usuario
 * @return string
 */
function obtenerReglasPoliticaPassword() {
    return 'Mínimo 6 caracteres, incluyendo letras y números.';
}

/**
 * Genera un mensaje HTML con las reglas para mostrar en formularios
 * @return string
 */
function obtenerReglasPasswordHtml() {
    return '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>' . 
           obtenerReglasPoliticaPassword() . '</small>';
}
