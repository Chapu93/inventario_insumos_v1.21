<?php
require_once '../includes/config.php';

// Verificar que el usuario esté autenticado y la sesión sea válida en el backend
if (!estaAutenticado() || !verificarSesionActiva()) {
    json_error('No autorizado', 401);
}

// Retornar éxito indicando que la sesión se mantiene viva
json_success(['status' => 'alive']);
