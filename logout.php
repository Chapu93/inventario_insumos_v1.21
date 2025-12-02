<?php
require_once 'includes/config.php';

// Cerrar sesión
cerrarSesion();

// Redirigir al login con mensaje
$_SESSION['mensaje'] = 'Sesión cerrada correctamente';
$_SESSION['tipo_mensaje'] = 'success';

header('Location: ' . app_base_url() . '/login.php');
exit;
