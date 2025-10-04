<?php
// votos.php
// Incluir las clases necesarias
require_once __DIR__ . '/../../autoload.php';

use utils\SessionManager;

// Verificar si el usuario está autenticado usando SessionManager
if (!SessionManager::esEstudianteAutenticado()) {
    header("Location: /Login/");
    exit();
}

require_once __DIR__.'/../../config/config.php';

use models\Candidatos;
use models\Votos;
use config\Database;
use utils\CandidatoImageHelper;

// Obtener los candidatos
$candidatosModel = new Candidatos();
$votosModel = new Votos();

// Establecer tiempo límite de votación (5 minutos)
$tiempoLimite = 300; // en segundos
$tiempoTranscurrido = 0;
$porcentajeTiempo = 0;

// Verificar si hay una sesión de votación en curso
if (isset($_SESSION['tiempo_inicio_votacion'])) {
    $tiempoTranscurrido = time() - $_SESSION['tiempo_inicio_votacion'];
    $porcentajeTiempo = min(100, ($tiempoTranscurrido / $tiempoLimite) * 100);
    
    // Convertir segundos restantes a formato legible
    $segundosRestantes = max(0, $tiempoLimite - $tiempoTranscurrido);
    $minutosRestantes = floor($segundosRestantes / 60);
    $segundosRestantes = $segundosRestantes % 60;
}

// Obtener candidatos a Personero y Representante
$personeros = $candidatosModel->getCandidatosPorTipo('PERSONERO');

// Modificar para filtrar representantes por grado del estudiante
$grado_estudiante = $_SESSION['grado'];
// Obtener representantes sólo del mismo grado del estudiante
$representantes = $candidatosModel->getCandidatosPorTipoYGrado('REPRESENTANTE', $grado_estudiante);

// Verificar si el estudiante ya ha votado
$id_estudiante = $_SESSION['estudiante_id'];
$votoPersonero = $votosModel->haVotadoPorTipo($id_estudiante, 'PERSONERO');
$votoRepresentante = $votosModel->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');

// Generar token CSRF para protección contra ataques CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjetón de Votación</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .candidate-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .vote-button {
            width: 100%;
            margin-top: 10px;
        }
        .section-title {
            color: #2c3e50;
            margin: 30px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .navbar {
            margin-bottom: 30px;
        }
        .blank-vote {
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px;
            border: 2px solid #6c757d !important;
            position: relative;
            overflow: hidden;
        }
        .blank-vote.solo-option {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border: 3px solid #17a2b8 !important;
        }
        .blank-vote.solo-option::before {
            background-color: #dc3545;
            content: "Única Opción Disponible";
        }
        .blank-vote::before {
            content: "Opción Democrática";
            position: absolute;
            top: 10px;
            right: -35px;
            background-color: #17a2b8;
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: bold;
            z-index: 1;
        }
        .blank-vote-info {
            background-color: rgba(108, 117, 125, 0.1);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .blank-vote-info p {
            margin-bottom: 0.5rem;
        }
        .timer-container {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress {
            height: 20px;
            margin-top: 10px;
        }
        .vote-status {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .vote-item {
            flex: 1;
            text-align: center;
            padding: 10px;
            margin: 0 5px;
            border-radius: 5px;
        }
        .vote-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .vote-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .voted-card {
            opacity: 0.5;
            pointer-events: none;
        }
        .voted-card.selected {
            opacity: 1;
            border: 3px solid #28a745;
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.2);
        }
        .voted-button {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            cursor: default;
        }
        .voted-button i {
            margin-right: 5px;
        }
        .vote-summary {
            background-color: rgba(0, 0, 0, 0.03);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .vote-summary h4 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .vote-summary-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .vote-summary-item i {
            margin-right: 10px;
            color: #28a745;
        }
        .section-overlay {
            position: relative;
        }
        .section-overlay::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
        }
        .section-overlay-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 101;
            text-align: center;
            color: white;
            width: 80%;
        }
        .section-overlay-content h3 {
            margin-bottom: 15px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
        }
        .section-overlay-content .overlay-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #fff;
        }
        .pending-section {
            animation: pulse 2s infinite;
            border: 2px dashed #ffc107;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }
        .pending-section::before {
            content: "¡Vota aquí!";
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            z-index: 10;
        }
        .direction-arrow {
            text-align: center;
            margin: 20px 0;
            display: none;
        }
        .direction-arrow i {
            font-size: 3rem;
            color: #ffc107;
            animation: bounce 2s infinite;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-30px);
            }
            60% {
                transform: translateY(-15px);
            }
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Votación</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Bienvenido: <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                </span>
                <a href="/Login/logout.php" class="btn btn-danger">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Alertas y Mensajes -->
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje']); unset($_SESSION['tipo']); ?>
        <?php endif; ?>
        
        <!-- Temporizador de votación - solo se muestra cuando el voto está incompleto -->
        <?php if(isset($_SESSION['tiempo_inicio_votacion']) && ($votoPersonero xor $votoRepresentante)): ?>
        <div class="timer-container">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Tiempo restante para completar su voto</h5>
                <span class="badge-tiempo bg-<?= $porcentajeTiempo > 75 ? 'danger' : ($porcentajeTiempo > 50 ? 'warning' : 'success') ?>">
                    <?= sprintf("%02d:%02d", $minutosRestantes, $segundosRestantes) ?>
                </span>
            </div>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $porcentajeTiempo > 75 ? 'danger' : ($porcentajeTiempo > 50 ? 'warning' : 'success') ?>" 
                     role="progressbar" 
                     style="width: <?= $porcentajeTiempo ?>%" 
                     aria-valuenow="<?= $porcentajeTiempo ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                    <?= round($porcentajeTiempo) ?>%
                </div>
            </div>
            <div class="mt-2 small text-muted">
                <strong>Importante:</strong> Debe completar su voto para PERSONERO y REPRESENTANTE dentro del tiempo límite.
                Si no completa ambos votos, todos sus votos serán ANULADOS.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Estado de la Votación -->
        <div class="vote-status">
            <div class="vote-item <?= $votoPersonero ? 'vote-completed' : 'vote-pending' ?>">
                <i class="fas fa-<?= $votoPersonero ? 'check-circle' : 'hourglass-half' ?> me-2"></i>
                <strong>Personero:</strong> <?= $votoPersonero ? 'Voto registrado' : 'Pendiente' ?>
            </div>
            <div class="vote-item <?= $votoRepresentante ? 'vote-completed' : 'vote-pending' ?>">
                <i class="fas fa-<?= $votoRepresentante ? 'check-circle' : 'hourglass-half' ?> me-2"></i>
                <strong>Representante:</strong> <?= $votoRepresentante ? 'Voto registrado' : 'Pendiente' ?>
            </div>
        </div>
        
        <?php if($votoPersonero && $votoRepresentante): ?>
        <div class="alert alert-success">
            <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>¡Votación completa!</h4>
            <p>Has completado tu votación para ambos tipos de candidatos. Tus votos han sido registrados correctamente.</p>
            <hr>
            <div class="d-grid gap-2">
                <a href="/Login/views/confirmacion.php" class="btn btn-success">
                    <i class="fas fa-check-double me-2"></i>Ver confirmación
                </a>
            </div>
        </div>
        <?php elseif(isset($_SESSION['tiempo_inicio_votacion']) && ($votoPersonero || $votoRepresentante)): ?>
        <div class="alert alert-warning">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>¡Votación en progreso!</h4>
            <p>Has votado por un tipo de candidato, pero aún debes completar tu voto para el otro tipo.</p>
            <hr>
            <div class="d-grid gap-2">
                <a href="/Login/cancelar_votacion" class="btn btn-outline-danger" onclick="return confirm('¿Está seguro que desea cancelar su votación? Todos sus votos serán anulados.')">
                    <i class="fas fa-times-circle me-2"></i>Cancelar votación
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Verificar si el estudiante ya ha votado por algún tipo -->
        <?php if($votoPersonero || $votoRepresentante): ?>
        <div class="vote-summary">
            <h4><i class="fas fa-clipboard-check me-2"></i>Resumen de tu votación</h4>
            <?php if($votoPersonero): ?>
            <div class="vote-summary-item">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Personero:</strong> Has ejercido tu voto <?= isset($_SESSION['nombre_personero']) ? 'por ' . htmlspecialchars($_SESSION['nombre_personero']) : '' ?>
                </div>
            </div>
            <?php else: ?>
            <div class="vote-summary-item text-warning">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <div>
                    <strong>Personero:</strong> Aún no has votado
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($votoRepresentante): ?>
            <div class="vote-summary-item">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Representante:</strong> Has ejercido tu voto <?= isset($_SESSION['nombre_representante']) ? 'por ' . htmlspecialchars($_SESSION['nombre_representante']) : '' ?>
                    <?php if(empty($representantes) && isset($_SESSION['nombre_representante']) && $_SESSION['nombre_representante'] == 'Voto en Blanco'): ?>
                    <small class="text-muted d-block">No había candidatos disponibles para el grado <?= htmlspecialchars($grado_estudiante) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="vote-summary-item text-warning">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <div>
                    <strong>Representante:</strong> Aún no has votado
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Sección de Personero -->
        <h2 class="section-title">Candidatos a Personero</h2>
        <?php if($votoRepresentante && !$votoPersonero): ?>
        <div class="direction-arrow" style="display: block;">
            <p><strong>¡Tu próximo paso!</strong></p>
            <i class="fas fa-arrow-down"></i>
        </div>
        <?php endif; ?>
        <div class="row <?= $votoPersonero ? 'section-overlay' : ($votoRepresentante && !$votoPersonero ? 'pending-section' : '') ?>">
            <?php if($votoPersonero): ?>
            <div class="section-overlay-content">
                <div class="overlay-icon">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h3>Ya has votado por un candidato a Personero</h3>
                <p class="lead">No puedes modificar tu voto. Por favor, continúa votando por un Representante si aún no lo has hecho.</p>
            </div>
            <?php endif; ?>
            
            <!-- Candidatos a Personero (si los hay) -->
            <?php if (empty($personeros)): ?>
                <div class="col-12 mb-4">
                    <div class="alert alert-warning">
                        <h4><i class="fas fa-info-circle"></i> Información Importante</h4>
                        <p>No hay candidatos a personero disponibles en este momento. El voto en blanco es la única opción disponible y será contabilizado como una opción válida en el proceso electoral.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($personeros as $personero): ?>
                <div class="col-md-4 mb-4">
                    <div class="candidate-card text-center <?= $votoPersonero ? 'voted-card' : '' ?>">
                        <?= CandidatoImageHelper::generarImagenHTML($personero, 150, 150, 'candidate-image') ?>
                        <h4><?php echo htmlspecialchars($personero['nombre'] . ' ' . $personero['apellido']); ?></h4>
                        <p class="text-muted">Número: <?php echo htmlspecialchars($personero['numero']); ?></p>
                        <form action="/Login/procesar_voto" method="POST">
                            <input type="hidden" name="id_candidato" value="<?php echo (int)$personero['id_candidato']; ?>">
                            <input type="hidden" name="tipo_candidato" value="PERSONERO">
                            <button type="submit" class="btn btn-primary vote-button <?= $votoPersonero ? 'voted-button' : '' ?>" <?= $votoPersonero ? 'disabled' : '' ?>>
                                <?php if($votoPersonero): ?>
                                    <i class="fas fa-check"></i>Voto registrado
                                <?php else: ?>
                                Votar
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Separador antes del voto en blanco -->
            <?php if (!empty($personeros)): ?>
            <div class="col-12 my-3">
                <hr>
                <h4 class="text-center mb-3">O si prefiere, puede:</h4>
            </div>
            <?php endif; ?>
            
            <!-- Voto en Blanco - Personero -->
            <div class="<?= empty($personeros) ? 'col-md-8 mx-auto' : 'col-md-4' ?>">
                <div class="candidate-card blank-vote <?= $votoPersonero ? 'voted-card' : '' ?> <?= empty($personeros) ? 'solo-option' : '' ?>">
                    <div class="text-center mb-3">
                        <i class="fas fa-vote-yea fa-<?= empty($personeros) ? '5x' : '4x' ?> text-secondary"></i>
                    </div>
                    <h4><?= empty($personeros) ? 'Voto en Blanco - Única Opción' : 'Voto en Blanco' ?></h4>
                    <p class="text-muted">Personero</p>
                    <div class="blank-vote-info">
                        <p><strong>¿Qué es el voto en blanco?</strong></p>
                        <p>Es una opción electoral válida que expresa inconformidad, abstención o desacuerdo con los candidatos.</p>
                        <p>Este voto se contabiliza y tiene validez en el proceso electoral.</p>
                        <?php if (empty($personeros)): ?>
                        <p class="text-danger"><strong>Nota:</strong> Al no haber candidatos disponibles, el voto en blanco es la única opción para ejercer su derecho al voto.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!$votoPersonero): ?>
                    <!-- Formulario para voto en blanco de personero -->
                    <form action="/Login/voto_blanco" method="POST" class="voto-blanco-form mb-2">
                        <input type="hidden" name="tipo_voto" value="PERSONERO">
                        <button type="submit" class="btn btn-info <?= empty($personeros) ? 'btn-lg' : '' ?> vote-button w-100">
                            <i class="fas fa-check-circle me-1"></i> Votar en Blanco
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="voted-button btn btn-success w-100 disabled">
                        <i class="fas fa-check"></i> Voto registrado
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dirección entre secciones -->
        <?php if($votoPersonero && !$votoRepresentante): ?>
        <div class="direction-arrow" style="display: block;">
            <p><strong>¡Complete su voto ahora!</strong></p>
            <i class="fas fa-arrow-down"></i>
        </div>
        <?php endif; ?>

        <!-- Sección de Representante -->
        <h2 class="section-title">Candidatos a Representante <span class="badge bg-primary">Grado <?= htmlspecialchars($grado_estudiante) ?></span></h2>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> El sistema muestra únicamente los candidatos a representante de tu mismo grado (<?= htmlspecialchars($grado_estudiante) ?>).
                </div>
            </div>
        </div>
        <div class="row <?= $votoRepresentante ? 'section-overlay' : ($votoPersonero && !$votoRepresentante ? 'pending-section' : '') ?>">
            <?php if($votoRepresentante): ?>
            <div class="section-overlay-content">
                <div class="overlay-icon">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h3>Ya has votado por un candidato a Representante</h3>
                <p class="lead">No puedes modificar tu voto. Por favor, continúa votando por un Personero si aún no lo has hecho.</p>
            </div>
            <?php endif; ?>
            
            <!-- Candidatos a Representante (si los hay) -->
            <?php if (empty($representantes)): ?>
                <div class="col-12 mb-4">
                    <div class="alert alert-warning">
                        <h4><i class="fas fa-info-circle"></i> Información Importante</h4>
                        <p>No hay candidatos a representante disponibles para el grado <?= htmlspecialchars($grado_estudiante) ?> en este momento. El voto en blanco es la única opción disponible y será contabilizado como una opción válida en el proceso electoral.</p>
                        <p><strong>Nota:</strong> Los representantes son específicos para cada grado, y únicamente puedes votar por un representante de tu mismo grado.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($representantes as $representante): ?>
                <div class="col-md-4 mb-4">
                    <div class="candidate-card text-center <?= $votoRepresentante ? 'voted-card' : '' ?>">
                        <?= CandidatoImageHelper::generarImagenHTML($representante, 150, 150, 'candidate-image') ?>
                        <h4><?php echo htmlspecialchars($representante['nombre'] . ' ' . $representante['apellido']); ?></h4>
                        <p class="text-muted">Número: <?php echo htmlspecialchars($representante['numero']); ?></p>
                        <span class="badge bg-secondary mb-2">Grado <?php echo htmlspecialchars($representante['grado']); ?></span>
                        <form action="/Login/procesar_voto" method="POST">
                            <input type="hidden" name="id_candidato" value="<?php echo (int)$representante['id_candidato']; ?>">
                            <input type="hidden" name="tipo_candidato" value="REPRESENTANTE">
                            <button type="submit" class="btn btn-primary vote-button <?= $votoRepresentante ? 'voted-button' : '' ?>" <?= $votoRepresentante ? 'disabled' : '' ?>>
                                <?php if($votoRepresentante): ?>
                                    <i class="fas fa-check"></i>Voto registrado
                                <?php else: ?>
                                Votar
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Separador antes del voto en blanco -->
            <?php if (!empty($representantes)): ?>
            <div class="col-12 my-3">
                <hr>
                <h4 class="text-center mb-3">O si prefiere, puede:</h4>
            </div>
            <?php endif; ?>

            <!-- Voto en Blanco - Representante -->
            <div class="<?= empty($representantes) ? 'col-md-8 mx-auto' : 'col-md-4' ?>">
                <div class="candidate-card blank-vote <?= $votoRepresentante ? 'voted-card' : '' ?> <?= empty($representantes) ? 'solo-option' : '' ?>">
                    <div class="text-center mb-3">
                        <i class="fas fa-vote-yea fa-<?= empty($representantes) ? '5x' : '4x' ?> text-secondary"></i>
                    </div>
                    <h4><?= empty($representantes) ? 'Voto en Blanco - Única Opción' : 'Voto en Blanco' ?></h4>
                    <p class="text-muted">Representante</p>
                    <div class="blank-vote-info">
                        <p><strong>¿Qué es el voto en blanco?</strong></p>
                        <p>Es una opción electoral válida que expresa inconformidad, abstención o desacuerdo con los candidatos.</p>
                        <p>Este voto se contabiliza y tiene validez en el proceso electoral.</p>
                        <?php if (empty($representantes)): ?>
                        <p class="text-danger"><strong>Nota:</strong> Al no haber candidatos disponibles, el voto en blanco es la única opción para ejercer su derecho al voto.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!$votoRepresentante): ?>
                    <!-- Formulario para voto en blanco de representante -->
                    <form action="/Login/voto_blanco" method="POST" class="voto-blanco-form mb-2">
                        <input type="hidden" name="tipo_voto" value="REPRESENTANTE">
                        <button type="submit" class="btn btn-info <?= empty($representantes) ? 'btn-lg' : '' ?> vote-button w-100">
                            <i class="fas fa-check-circle me-1"></i> Votar en Blanco
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="voted-button btn btn-success w-100 disabled">
                        <i class="fas fa-check"></i> Voto registrado
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Confirmación de voto -->
    <script>
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let mensaje = '¿Está seguro de su voto? Esta acción no se puede deshacer.';
            
            // Verificar si es un formulario de voto en blanco
            if (form.classList.contains('voto-blanco-form')) {
                const tipoVotoInput = form.querySelector('input[name="tipo_voto"]');
                if (tipoVotoInput) {
                    const tipoVoto = tipoVotoInput.value.toLowerCase();
                    mensaje = `¿Está seguro de votar en blanco para ${tipoVoto}? Esta acción no se puede deshacer.`;
                }
            }
                
            if (confirm(mensaje)) {
                try {
                    console.log('Enviando formulario:', form.action);
                    form.submit();
                } catch (error) {
                    console.error('Error al enviar el formulario:', error);
                    alert('Ocurrió un error al enviar el formulario. Por favor, intenta nuevamente.');
                }
            }
        });
    });
    
    // Actualizar el temporizador cada segundo
    <?php if(isset($_SESSION['tiempo_inicio_votacion'])): ?>
    const tiempoLimite = <?= $tiempoLimite ?>;
    const tiempoInicio = <?= $_SESSION['tiempo_inicio_votacion'] ?>;
    
    function actualizarTemporizador() {
        const ahora = Math.floor(Date.now() / 1000);
        const tiempoTranscurrido = ahora - tiempoInicio;
        const segundosRestantes = Math.max(0, tiempoLimite - tiempoTranscurrido);
        
        if (segundosRestantes <= 0) {
            window.location.href = "/Login/cancelar_votacion";
            return;
        }
        
        const minutosRestantes = Math.floor(segundosRestantes / 60);
        const segundosDisplay = segundosRestantes % 60;
        const porcentajeTiempo = Math.min(100, (tiempoTranscurrido / tiempoLimite) * 100);
        
        // Actualizar el texto del tiempo SOLO en el temporizador
        document.querySelector('.badge-tiempo').textContent = 
            String(minutosRestantes).padStart(2, '0') + ':' + 
            String(segundosDisplay).padStart(2, '0');
        
        // Actualizar la barra de progreso
        const progressBar = document.querySelector('.progress-bar');
        progressBar.style.width = porcentajeTiempo + '%';
        progressBar.setAttribute('aria-valuenow', porcentajeTiempo);
        progressBar.textContent = Math.round(porcentajeTiempo) + '%';
        
        // Cambiar el color según el tiempo restante SOLO en el temporizador
        if (porcentajeTiempo > 75) {
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-danger';
            document.querySelector('.badge-tiempo').className = 'badge-tiempo bg-danger';
        } else if (porcentajeTiempo > 50) {
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
            document.querySelector('.badge-tiempo').className = 'badge-tiempo bg-warning';
        } else {
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
            document.querySelector('.badge-tiempo').className = 'badge-tiempo bg-success';
        }
        
        setTimeout(actualizarTemporizador, 1000);
    }
    
    actualizarTemporizador();
    <?php endif; ?>
    
    // Refrescar la página cada 30 segundos para verificar si la elección ya comenzó o ha cambiado de estado
    <?php if(!$yaVoto): ?>
    setInterval(function() {
        location.reload();
    }, 30000);
    <?php endif; ?>
    </script>
</body>
</html>