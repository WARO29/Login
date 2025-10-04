<?php
// Suprimir warnings para evitar contaminar la respuesta JSON
error_reporting(E_ERROR | E_PARSE);

// Configurar la zona horaria para Colombia
date_default_timezone_set('America/Bogota');

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../models/Votos.php';
require_once '../models/AdministrativoModel.php';
require_once '../models/RepresentanteDocenteModel.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    // Devolver error si no es administrador
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Crear instancias de los modelos
$votosModel = new models\Votos();
$administrativoModel = new models\AdministrativoModel();
$representanteModel = new models\RepresentanteDocenteModel();

// Obtener estadísticas de votación de administrativos
$estadisticasVotacion = $votosModel->getEstadisticasVotacionAdministrativos();

// Obtener los votos recientes de administrativos (últimos 10)
$votosRecientes = $votosModel->getVotosRecientesAdministrativos(10);
$votosRecientesProcesados = [];

// Procesar los votos recientes para asegurar que todos los campos necesarios estén presentes
if (is_array($votosRecientes)) {
    foreach ($votosRecientes as $voto) {
        // Verificar que los datos necesarios existen
        if (!isset($voto['id_administrativo']) || !isset($voto['fecha_voto'])) {
            continue; // Saltar este voto si no tiene datos completos
        }
        
        $votoFormateado = $voto; // Mantener los datos originales
        
        // Formatear la fecha
        if (isset($voto['fecha_voto'])) {
            $fecha = new DateTime($voto['fecha_voto']);
            $votoFormateado['fecha_formateada'] = $fecha->format('H:i');
            $votoFormateado['hora'] = $fecha->format('H:i');
        } else {
            $votoFormateado['fecha_formateada'] = 'Reciente';
            $votoFormateado['hora'] = 'Reciente';
        }
        
        // Asegurar que el nombre del administrativo esté presente
        if (!isset($voto['nombre_administrativo']) || empty($voto['nombre_administrativo'])) {
            // Intentar obtener el nombre del administrativo desde la base de datos
            if (isset($voto['id_administrativo'])) {
                $administrativo = $administrativoModel->getAdministrativoPorId($voto['id_administrativo']);
                if ($administrativo && isset($administrativo['nombre'])) {
                    $votoFormateado['nombre_administrativo'] = $administrativo['nombre'];
                    $votoFormateado['cargo'] = $administrativo['cargo'] ?? '';
                } else {
                    $votoFormateado['nombre_administrativo'] = 'Administrativo';
                    $votoFormateado['cargo'] = '';
                }
            } else {
                $votoFormateado['nombre_administrativo'] = 'Administrativo';
                $votoFormateado['cargo'] = '';
            }
        }
        
        // Asegurar que la información del representante esté presente
        if ($voto['voto_blanco'] != 1 && (!isset($voto['nombre_representante']) || empty($voto['nombre_representante']))) {
            if (isset($voto['codigo_representante'])) {
                $representante = $representanteModel->getByCodigo($voto['codigo_representante']);
                if ($representante && isset($representante['nombre_repre_docente'])) {
                    $votoFormateado['nombre_representante'] = $representante['nombre_repre_docente'];
                } else {
                    $votoFormateado['nombre_representante'] = 'Representante';
                }
            } else {
                $votoFormateado['nombre_representante'] = 'Representante';
            }
        }
        
        // Agregar campos adicionales para compatibilidad con el formato de estudiantes
        $votoFormateado['tipo'] = 'ADMINISTRATIVO';
        
        // Agregar el voto procesado al array
        $votosRecientesProcesados[] = $votoFormateado;
    }
}

// Reemplazar el array original con el procesado
$votosRecientes = $votosRecientesProcesados;

// Agregar registro para depuración
error_log('Estadísticas de votación administrativos: ' . json_encode($estadisticasVotacion));
error_log('Votos recientes administrativos: ' . json_encode($votosRecientes));

// Adaptar los nombres de los campos para que coincidan con los esperados en el JavaScript
$estadisticasAdaptadas = [
    'total_administrativos' => $estadisticasVotacion['totalAdministrativos'] ?? 0,
    'total_votos' => $estadisticasVotacion['totalVotos'] ?? 0,
    'votos_blancos' => $estadisticasVotacion['totalVotosBlanco'] ?? 0,
    'participacion' => $estadisticasVotacion['porcentajeParticipacion'] ?? 0
];

// Preparar la respuesta
$response = [
    'estadisticas' => $estadisticasAdaptadas,
    'votosRecientes' => $votosRecientes,
    'timestamp' => date('Y-m-d H:i:s') // Agregar timestamp para evitar problemas de caché
];

// Devolver la respuesta en formato JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
echo json_encode($response);
?>
