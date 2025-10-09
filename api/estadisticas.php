<?php
// Configurar la zona horaria para Colombia
date_default_timezone_set('America/Bogota');

// Iniciar sesión para verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use models\Estadisticas;
use models\Votos;
use models\DocenteModel;
use models\Candidatos;
use models\EleccionConfigModel;

// Crear instancia del modelo de estadísticas y otros modelos
$estadisticasModel = new Estadisticas();
$votosModel = new Votos();
$docenteModel = new DocenteModel();
$candidatosModel = new Candidatos();
$eleccionModel = new EleccionConfigModel();

// Verificar si hay elecciones activas (no archivadas)
$eleccionActiva = $eleccionModel->getConfiguracionActiva();
$hayEleccionActiva = $eleccionActiva && $eleccionActiva['estado'] !== 'archivada';

// Si no hay elección activa, retornar datos en cero
if (!$hayEleccionActiva) {
    $estadisticas_estudiantes = [
        'totalEstudiantes' => $estadisticasModel->getTotalEstudiantes(),
        'totalVotos' => 0,
        'votosBlanco' => 0,
        'porcentajeParticipacion' => 0
    ];
    
    $estadisticas_docentes = [
        'total_docentes' => $docenteModel->getTotalDocentes(),
        'docentes_votaron' => 0,
        'porcentaje_participacion' => 0
    ];
    
    $totalCandidatos = 0;
    $personeros = [];
    $representantes = [];
} else {
    // Obtener estadísticas de estudiantes para elección activa
    $estadisticas_estudiantes = [
        'totalEstudiantes' => $estadisticasModel->getTotalEstudiantes(),
        'totalVotos' => $estadisticasModel->getTotalVotos(),
        'votosBlanco' => $votosModel->getConteoVotosEnBlanco('PERSONERO') + $votosModel->getConteoVotosEnBlanco('REPRESENTANTE'),
        'porcentajeParticipacion' => $estadisticasModel->getPorcentajeParticipacion()
    ];

    // Obtener estadísticas de docentes
    $estadisticas_docentes = $votosModel->getEstadisticasVotacionDocentes();

    // Obtener información de candidatos
    $totalCandidatos = $estadisticasModel->getTotalCandidatos();

    // Obtener conteo de votos por candidato
    error_log("Solicitando datos de votos por tipo PERSONERO");
    $personeros = $votosModel->getConteoVotosPorTipo('PERSONERO');
    error_log("Solicitando datos de votos por tipo REPRESENTANTE");
    $representantes = $votosModel->getConteoVotosPorTipo('REPRESENTANTE');
}

// Asegurarnos de que los datos sean arrays
$personeros = is_array($personeros) ? $personeros : [];
$representantes = is_array($representantes) ? $representantes : [];

// Procesar y validar los datos
error_log("Procesando datos de personeros...");
foreach ($personeros as &$candidato) {
    // Asegurar que total_votos es un entero y añadir el tipo de candidato
    $candidato['total_votos'] = (int)($candidato['total_votos'] ?? 0);
    $candidato['tipo_candidato'] = 'PERSONERO';
    error_log("Candidato: {$candidato['nombre']} {$candidato['apellido']}, Votos: {$candidato['total_votos']}");
}
error_log("Procesando datos de representantes...");
foreach ($representantes as &$candidato) {
    // Asegurar que total_votos es un entero y añadir el tipo de candidato
    $candidato['total_votos'] = (int)($candidato['total_votos'] ?? 0);
    $candidato['tipo_candidato'] = 'REPRESENTANTE';
    error_log("Candidato: {$candidato['nombre']} {$candidato['apellido']}, Votos: {$candidato['total_votos']}");
}

// Combinar los datos de candidatos
$votosCandidatos = array_merge($personeros, $representantes);

// Depurar los datos que se envían
error_log("Datos de personeros: " . json_encode($personeros));
error_log("Datos de representantes: " . json_encode($representantes));
error_log("Datos combinados: " . json_encode($votosCandidatos));

// Combinar estadísticas
$estadisticas = [
    'totalEstudiantes' => $estadisticas_estudiantes['totalEstudiantes'],
    'totalDocentes' => $estadisticas_docentes['totalDocentes'] ?? 0,
    'totalVotos' => $estadisticas_estudiantes['totalVotos'],
    'totalVotosDocentes' => $estadisticas_docentes['totalVotos'] ?? 0,
    'totalCandidatos' => $totalCandidatos,
    'porcentajeParticipacion' => $estadisticas_estudiantes['porcentajeParticipacion'],
    'votosBlanco' => $estadisticas_estudiantes['votosBlanco']
];

// Obtener votos recientes de estudiantes
$votosRecientesEstudiantes = $estadisticasModel->getVotosRecientes(10);
$votosRecientesEstudiantesFormateados = [];

if (is_array($votosRecientesEstudiantes)) {
    foreach ($votosRecientesEstudiantes as $voto) {
        // Verificar que los datos necesarios existen
        if (!isset($voto['nombre_estudiante']) || !isset($voto['fecha_voto'])) {
            continue; // Saltar este voto si no tiene datos completos
        }
        
        $votosRecientesEstudiantesFormateados[] = [
            'id_voto' => $voto['id_voto'] ?? null,
            'nombre_estudiante' => $voto['nombre_estudiante'] ?? '',
            'apellido_estudiante' => $voto['apellido_estudiante'] ?? '',
            'id_candidato' => $voto['id_candidato'] ?? null,
            'candidato_nombre' => $voto['nombre_candidato'] ?? '',
            'candidato_apellido' => $voto['apellido_candidato'] ?? '',
            'tipo_voto' => $voto['tipo_voto'] ?? '',
            'fecha_voto' => $voto['fecha_voto'] ?? '',
            'voto_blanco' => ($voto['id_candidato'] === null) ? true : false
        ];
    }
}

// Obtener votos recientes de docentes
$votosRecientesDocentes = $votosModel->getVotosRecientesDocentes(10);
$votosRecientesDocentesFormateados = [];

if (is_array($votosRecientesDocentes)) {
    foreach ($votosRecientesDocentes as $voto) {
        // Verificar que los datos necesarios existen
        if (!isset($voto['id_docente']) || !isset($voto['fecha_voto'])) {
            continue; // Saltar este voto si no tiene datos completos
        }
        
        // Obtener nombre del docente si no está presente
        $nombreDocente = $voto['nombre_docente'] ?? '';
        if (empty($nombreDocente) && isset($voto['id_docente'])) {
            $docente = $docenteModel->getDocentePorDocumento($voto['id_docente']);
            $nombreDocente = $docente['nombre'] ?? 'Docente';
        }
        
        $votosRecientesDocentesFormateados[] = [
            'id_voto' => $voto['id_voto'] ?? null,
            'id_docente' => $voto['id_docente'] ?? null,
            'nombre_docente' => $nombreDocente,
            'id_representante' => $voto['codigo_representante'] ?? null,
            'representante_nombre' => $voto['nombre_representante'] ?? '',
            'fecha_voto' => $voto['fecha_voto'] ?? '',
            'voto_blanco' => $voto['voto_blanco'] ?? false
        ];
    }
}

// Establecer cabeceras para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Preparar datos de respuesta completa
$data = [
    'estadisticas' => $estadisticas,
    'votosCandidatos' => $votosCandidatos,
    'personeros' => $personeros,
    'representantes' => $representantes,
    'votosRecientes' => $votosRecientesEstudiantesFormateados,
    'votosRecientesDocentes' => $votosRecientesDocentesFormateados,
    'timestamp' => time()
];

// Devolver datos en formato JSON
echo json_encode($data);
?>
