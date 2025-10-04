<?php
// Configurar la zona horaria para Colombia
date_default_timezone_set('America/Bogota');

// Este archivo tiene como único propósito mantener la sesión activa
// No realiza ninguna acción, solo devuelve un estado OK

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    // Si no es un administrador, enviar respuesta de error
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

// Regenerar el ID de sesión para prevenir ataques de fijación de sesión
session_regenerate_id(false);

// Actualizar la última actividad
$_SESSION['last_activity'] = time();

// Responder con éxito
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'timestamp' => time(),
    'message' => 'Ping recibido correctamente'
]);
?> 