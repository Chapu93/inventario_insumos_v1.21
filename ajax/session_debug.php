<?php
// Debug session in AJAX context
require_once '../includes/config.php';

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'cookie_params' => session_get_cookie_params(),
    'authenticated' => estaAutenticado()
], JSON_PRETTY_PRINT);
