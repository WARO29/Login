<?php
/**
 * API para validar números de tarjetón en tiempo real
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar parámetros requeridos
if (!isset($input['numero']) || !isset($input['tipo_candidato'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros requeridos: numero, tipo_candidato']);
    exit;
}

$numero = trim($input['numero']);
$tipo_candidato = strtoupper(trim($input['tipo_candidato']));
$grado = isset($input['grado']) ? (int)$input['grado'] : null;
$excluir_id = isset($input['excluir_id']) ? (int)$input['excluir_id'] : null;

// Validar que el número no esté vacío
if (empty($numero)) {
    echo json_encode([
        'disponible' => true,
        'mensaje' => ''
    ]);
    exit;
}

// Validar tipo de candidato
if (!in_array($tipo_candidato, ['PERSONERO', 'REPRESENTANTE'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de candidato inválido']);
    exit;
}

// Cargar el modelo
require_once '../autoload.php';
require_once '../config/config.php';

use models\Candidatos;

try {
    $candidatoModel = new Candidatos();
    
    // Verificar si el número ya existe
    $existe = $candidatoModel->existeNumeroTarjeton($numero, $tipo_candidato, $grado, $excluir_id);
    
    if ($existe) {
        // Obtener información del candidato conflictivo
        $conflicto = $candidatoModel->obtenerConflictoNumero($numero, $tipo_candidato, $grado, $excluir_id);
        
        if ($conflicto) {
            if ($tipo_candidato === 'PERSONERO') {
                $mensaje = "El número '$numero' ya está siendo usado por el personero: {$conflicto['nombre']} {$conflicto['apellido']}.";
            } else {
                $mensaje = "El número '$numero' ya está siendo usado por el representante de grado $grado: {$conflicto['nombre']} {$conflicto['apellido']}.";
            }
        } else {
            $mensaje = "El número '$numero' ya está en uso.";
        }
        
        echo json_encode([
            'disponible' => false,
            'mensaje' => $mensaje,
            'conflicto' => $conflicto
        ]);
    } else {
        echo json_encode([
            'disponible' => true,
            'mensaje' => "El número '$numero' está disponible."
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'detalle' => $e->getMessage()
    ]);
}
?>